<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Sao_Paulo');

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$relative_path = substr($request_uri, strlen($base_path));

if ($relative_path === '/app' || $relative_path === '/app/') {
    include __DIR__ . '/app.php';
    exit;
}

$user_id = 1;

function ensureHabitRemovalsTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS habit_removals (
        habit_id INT NOT NULL PRIMARY KEY,
        removed_from DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_removed_from (removed_from)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function ensureGoalsMonthlyEnum(PDO $pdo) {
    try {
        $pdo->exec("ALTER TABLE goals MODIFY goal_type ENUM('geral','anual','mensal') DEFAULT 'geral'");
    } catch (Exception $e) {
        error_log('[DB] Falha ao ajustar enum goal_type: ' . $e->getMessage());
    }
}

function getWeekRange(): array {
    $now = new DateTime();
    $dayOfWeek = (int)$now->format('w');
    $daysToMonday = ($dayOfWeek === 0) ? -6 : -($dayOfWeek - 1);
    $monday = (clone $now)->modify("$daysToMonday days");
    $sunday = (clone $monday)->modify('+6 days');
    return [$monday->format('Y-m-d'), $sunday->format('Y-m-d')];
}

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (isset($_GET['api'])) {
    $action = $_GET['api'];
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;

    try {
        ensureHabitRemovalsTable($pdo);
        ensureGoalsMonthlyEnum($pdo);

        if ($action === 'get_week_activities') {
            $stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY status ASC, FIELD(DAYOFWEEK(day_date), 2, 3, 4, 5, 6, 7, 1), day_date ASC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_activity') {
            $stmt = $pdo->prepare("INSERT INTO activities (user_id, title, day_date, status) VALUES (?, ?, ?, 0)");
            $stmt->execute([$user_id, $data['title'] ?? '', $data['date'] ?? date('Y-m-d')]);
            json_response(['success' => true]);
        }

        if ($action === 'update_activity') {
            $stmt = $pdo->prepare("UPDATE activities SET title = ?, day_date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['title'] ?? '', $data['date'] ?? date('Y-m-d'), $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_activity') {
            $stmt = $pdo->prepare("UPDATE activities SET status = 1 - status WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_activity') {
            $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_habits') {
            $month = $_GET['month'] ?? date('Y-m');
            $monthStart = $month . '-01';
            $stmt = $pdo->prepare("SELECT h.*, hr.removed_from FROM habits h LEFT JOIN habit_removals hr ON hr.habit_id = h.id WHERE hr.removed_from IS NULL OR hr.removed_from > ? ORDER BY h.id DESC");
            $stmt->execute([$monthStart]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_habit') {
            $pdo->prepare("INSERT INTO habits (name, checked_dates) VALUES (?, '[]')")->execute([$data['name'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_habit') {
            $id = $data['id'] ?? null;
            $date = $data['date'] ?? date('Y-m-d');
            $json = $pdo->prepare("SELECT checked_dates FROM habits WHERE id = ?");
            $json->execute([$id]);
            $arr = json_decode($json->fetchColumn() ?: '[]', true) ?: [];
            if (in_array($date, $arr)) {
                $arr = array_values(array_diff($arr, [$date]));
            } else {
                $arr[] = $date;
            }
            $upd = $pdo->prepare("UPDATE habits SET checked_dates = ? WHERE id = ?");
            $upd->execute([json_encode($arr), $id]);
            json_response(['success' => true]);
        }

        if ($action === 'remove_habit') {
            $id = $data['id'] ?? null;
            $removedFrom = $data['removed_from'] ?? date('Y-m-d');
            if (!$id) json_response(['success' => false]);
            $stmt = $pdo->prepare("INSERT INTO habit_removals (habit_id, removed_from) VALUES (?, ?) ON DUPLICATE KEY UPDATE removed_from = VALUES(removed_from)");
            $stmt->execute([$id, $removedFrom]);
            json_response(['success' => true]);
        }

        if ($action === 'get_workouts_month') {
            $month = $_GET['month'] ?? date('Y-m');
            $monthStart = $month . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND workout_date BETWEEN ? AND ? ORDER BY workout_date ASC, id DESC");
            $stmt->execute([$user_id, $monthStart, $monthEnd]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'toggle_workout_day') {
            $date = $data['date'] ?? date('Y-m-d');
            $check = $pdo->prepare("SELECT id, done FROM workouts WHERE user_id = ? AND workout_date = ? ORDER BY id DESC LIMIT 1");
            $check->execute([$user_id, $date]);
            $row = $check->fetch();
            if ($row) {
                $pdo->prepare("UPDATE workouts SET done = 1 - done WHERE id = ? AND user_id = ?")->execute([$row['id'], $user_id]);
            } else {
                $pdo->prepare("INSERT INTO workouts (user_id, name, workout_date, done) VALUES (?, ?, ?, 1)")->execute([$user_id, 'Treino', $date]);
            }
            json_response(['success' => true]);
        }

        if ($action === 'get_runs_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM runs WHERE user_id = ? AND run_date BETWEEN ? AND ? ORDER BY run_date DESC, id DESC");
            $stmt->execute([$user_id, $start, $end]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_run') {
            $stmt = $pdo->prepare("INSERT INTO runs (user_id, title, run_date, distance_km, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $data['title'] ?? 'Corrida', $data['date'] ?? date('Y-m-d'), $data['distance'] ?? 0, $data['notes'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'update_run') {
            $stmt = $pdo->prepare("UPDATE runs SET run_date = ?, distance_km = ?, notes = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['date'] ?? date('Y-m-d'), $data['distance'] ?? 0, $data['notes'] ?? '', $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_run') {
            $pdo->prepare("DELETE FROM runs WHERE id = ? AND user_id = ?")->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_daily_message') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $file = __DIR__ . '/mensagens_365.json';
            if (!file_exists($file)) json_response(['error' => 'Arquivo não encontrado']);
            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) json_response(['error' => 'Formato inválido']);
            $text = null;
            $dayOfYear = (int)date('z', strtotime($date));
            foreach ($json as $item) {
                $itemDate = $item['date'] ?? ($item['dia'] ?? null);
                $itemText = $item['texto'] ?? ($item['mensagem'] ?? ($item['message'] ?? null));
                if ($itemDate && $itemText && $itemDate === $date) { $text = $itemText; break; }
            }
            if ($text === null && isset($json[$dayOfYear])) {
                $item = $json[$dayOfYear];
                $text = $item['texto'] ?? ($item['mensagem'] ?? ($item['message'] ?? (is_string($item) ? $item : null)));
            }
            if ($text === null) json_response(['error' => 'Mensagem não encontrada']);
            json_response(['date' => $date, 'text' => $text]);
        }

        if ($action === 'save_finance') {
            $type = ($data['type'] === 'entrada' || $data['type'] === 'income') ? 'income' : 'expense';
            $stmt = $pdo->prepare("INSERT INTO finances (user_id, type, amount, description, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $data['amount'] ?? 0, $data['desc'] ?? '', $data['date'] ?? date('Y-m-d')]);
            json_response(['success' => true]);
        }

        if ($action === 'get_finance_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM finances WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$user_id, $start, $end]);
            $rows = $stmt->fetchAll();
            $income = 0; $expense = 0;
            foreach ($rows as $row) {
                if ($row['type'] === 'income') $income += (float)$row['amount'];
                else $expense += (float)$row['amount'];
            }
            json_response(['income' => $income, 'expense' => $expense, 'items' => $rows]);
        }

        if ($action === 'get_events_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT id, title, start_date, description FROM events WHERE user_id = ? AND start_date BETWEEN ? AND ? ORDER BY start_date ASC");
            $stmt->execute([$user_id, $start . ' 00:00:00', $end . ' 23:59:59']);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_event') {
            $stmt = $pdo->prepare("INSERT INTO events (user_id, title, start_date, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $data['title'] ?? '', ($data['date'] ?? date('Y-m-d')) . ' 09:00:00', $data['description'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'update_event') {
            $stmt = $pdo->prepare("UPDATE events SET title = ?, start_date = ?, description = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['title'] ?? '', ($data['date'] ?? date('Y-m-d')) . ' 09:00:00', $data['description'] ?? '', $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_event') {
            $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?")->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_rules') {
            $stmt = $pdo->prepare("SELECT id, rule_text FROM life_rules WHERE user_id = ? ORDER BY id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_rule') {
            $pdo->prepare("INSERT INTO life_rules (user_id, rule_text) VALUES (?, ?)")->execute([$user_id, $data['rule_text'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'delete_rule') {
            $pdo->prepare("DELETE FROM life_rules WHERE id = ? AND user_id = ?")->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_routine_items') {
            $stmt = $pdo->prepare("SELECT * FROM routine_items WHERE user_id = ? ORDER BY routine_time ASC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_routine_item') {
            $pdo->prepare("INSERT INTO routine_items (user_id, routine_time, activity) VALUES (?, ?, ?)")->execute([$user_id, $data['time'] ?? '08:00', $data['activity'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'update_routine_item') {
            $pdo->prepare("UPDATE routine_items SET routine_time = ?, activity = ? WHERE id = ? AND user_id = ?")->execute([$data['time'] ?? '08:00', $data['activity'] ?? '', $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_routine_item') {
            $pdo->prepare("DELETE FROM routine_items WHERE id = ? AND user_id = ?")->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'save_goal') {
            $pdo->prepare("INSERT INTO goals (user_id, title, difficulty, status, goal_type) VALUES (?, ?, 'media', 0, 'geral')")->execute([$user_id, $data['title'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals') {
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND goal_type = 'geral' ORDER BY status ASC, id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'toggle_goal') {
            $pdo->prepare("UPDATE goals SET status = 1 - status WHERE id = ? AND user_id = ?")->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_goal') {
            $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?")->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals_month') {
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND goal_type = 'mensal' ORDER BY status ASC, id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_goal_month') {
            $pdo->prepare("INSERT INTO goals (user_id, title, difficulty, status, goal_type) VALUES (?, ?, 'media', 0, 'mensal')")->execute([$user_id, $data['title'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_goal_month') {
            $pdo->prepare("UPDATE goals SET status = 1 - status WHERE id = ? AND user_id = ? AND goal_type = 'mensal'")->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_goal_month') {
            $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ? AND goal_type = 'mensal'")->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals_done') {
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND status = 1 ORDER BY id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        json_response(['error' => 'Ação não encontrada']);
    } catch (Exception $e) {
        http_response_code(500);
        json_response(['error' => $e->getMessage()]);
    }
}

$page_title = 'Life Control';
include __DIR__ . '/includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ═══════════════════════════════════════════
   TOKENS & RESET
═══════════════════════════════════════════ */
:root {
  --bg:        #0a0a0f;
  --bg-2:      #0f0f17;
  --bg-3:      #141420;
  --surface:   #16161f;
  --surface-2: #1c1c28;
  --surface-3: #222230;
  --border:    rgba(255,255,255,0.07);
  --border-md: rgba(255,255,255,0.12);
  --border-hi: rgba(255,255,255,0.22);

  --text:    #f0f0f5;
  --text-2:  #a8a8be;
  --text-3:  #5c5c78;

  --accent:      #7c6fff;
  --accent-glow: rgba(124,111,255,0.35);
  --accent-2:    #ff6b6b;
  --accent-3:    #4fffb0;
  --accent-4:    #ffd166;

  --green:  #22d483;
  --red:    #ff5555;
  --yellow: #ffd166;
  --blue:   #4fc3f7;

  --r-sm: 10px;
  --r-md: 16px;
  --r-lg: 22px;
  --r-xl: 30px;

  --font-display: 'Syne', sans-serif;
  --font-body:    'DM Sans', sans-serif;

  --shadow-sm: 0 2px 12px rgba(0,0,0,0.4);
  --shadow-md: 0 8px 32px rgba(0,0,0,0.5);
  --shadow-lg: 0 20px 60px rgba(0,0,0,0.6);
  --glow:      0 0 30px var(--accent-glow);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-body);
  font-size: 14px;
  line-height: 1.6;
  min-height: 100vh;
  overflow-x: hidden;
}

/* Noise texture overlay */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
  opacity: 0.6;
}

/* Ambient glow */
body::after {
  content: '';
  position: fixed;
  top: -200px;
  left: 50%;
  transform: translateX(-50%);
  width: 700px;
  height: 500px;
  background: radial-gradient(ellipse, rgba(124,111,255,0.08) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

/* ═══════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════ */
.lc-app {
  position: relative;
  z-index: 1;
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px 20px 100px;
}

/* ═══════════════════════════════════════════
   HEADER / HERO
═══════════════════════════════════════════ */
.lc-header {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: start;
  gap: 20px;
  margin-bottom: 32px;
  padding: 28px 32px;
  background: var(--surface);
  border: 1px solid var(--border-md);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-md);
  position: relative;
  overflow: hidden;
}

.lc-header::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
}

.lc-header-glow {
  position: absolute;
  top: -60px; right: -60px;
  width: 220px; height: 220px;
  background: radial-gradient(circle, rgba(124,111,255,0.12), transparent 70%);
  pointer-events: none;
}

.lc-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 10px;
}

.lc-eyebrow-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--accent);
  animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.4; transform: scale(0.7); }
}

.lc-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: var(--text);
}

.lc-title span { color: var(--accent); }

.lc-subtitle {
  color: var(--text-2);
  font-size: 13px;
  margin-top: 6px;
}

.lc-week-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: var(--surface-2);
  border: 1px solid var(--border-md);
  border-radius: 999px;
  font-size: 11px;
  color: var(--text-2);
  margin-top: 10px;
}

.lc-header-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
}

/* ═══════════════════════════════════════════
   STATS BAR
═══════════════════════════════════════════ */
.lc-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 28px;
}

.lc-stat {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: border-color 0.2s, transform 0.2s;
  cursor: default;
}

.lc-stat:hover {
  border-color: var(--border-md);
  transform: translateY(-2px);
}

.lc-stat-icon {
  width: 38px; height: 38px;
  border-radius: var(--r-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  margin-bottom: 14px;
}

.lc-stat-icon.purple { background: rgba(124,111,255,0.15); color: var(--accent); }
.lc-stat-icon.green  { background: rgba(34,212,131,0.15);  color: var(--green); }
.lc-stat-icon.red    { background: rgba(255,85,85,0.15);   color: var(--red); }
.lc-stat-icon.yellow { background: rgba(255,209,102,0.15); color: var(--yellow); }

.lc-stat-val {
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: 700;
  line-height: 1;
  color: var(--text);
}

.lc-stat-label {
  font-size: 11px;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-top: 4px;
}

.lc-stat-bar {
  height: 3px;
  background: var(--surface-3);
  border-radius: 99px;
  margin-top: 12px;
  overflow: hidden;
}

.lc-stat-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.8s cubic-bezier(.22,.68,0,1.2);
}

.fill-purple { background: var(--accent); }
.fill-green  { background: var(--green); }
.fill-red    { background: var(--red); }
.fill-yellow { background: var(--yellow); }

/* ═══════════════════════════════════════════
   SECTION GRID
═══════════════════════════════════════════ */
.lc-grid {
  display: grid;
  gap: 16px;
}

.lc-grid-2 { grid-template-columns: repeat(2, 1fr); }
.lc-grid-3 { grid-template-columns: repeat(3, 1fr); }
.lc-grid-12 { grid-template-columns: 1fr 2fr; }
.lc-grid-21 { grid-template-columns: 2fr 1fr; }

.lc-section { margin-bottom: 28px; }

/* ═══════════════════════════════════════════
   CARD
═══════════════════════════════════════════ */
.lc-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 22px;
  box-shadow: var(--shadow-sm);
  transition: border-color 0.2s;
  position: relative;
  overflow: hidden;
}

.lc-card-accent-line {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
}

.lc-card-accent-line.purple { background: linear-gradient(90deg, var(--accent), transparent); }
.lc-card-accent-line.green  { background: linear-gradient(90deg, var(--green), transparent); }
.lc-card-accent-line.red    { background: linear-gradient(90deg, var(--red), transparent); }
.lc-card-accent-line.yellow { background: linear-gradient(90deg, var(--yellow), transparent); }
.lc-card-accent-line.blue   { background: linear-gradient(90deg, var(--blue), transparent); }

.lc-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.lc-card-title {
  font-family: var(--font-display);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-2);
  display: flex;
  align-items: center;
  gap: 8px;
}

.lc-card-title i {
  width: 28px; height: 28px;
  background: var(--surface-2);
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: var(--accent);
}

/* ═══════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════ */
.lc-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: var(--r-sm);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
  white-space: nowrap;
  border: none;
}

.lc-btn-ghost {
  background: var(--surface-2);
  color: var(--text-2);
  border: 1px solid var(--border);
}
.lc-btn-ghost:hover { background: var(--surface-3); color: var(--text); border-color: var(--border-md); }

.lc-btn-primary {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 4px 16px rgba(124,111,255,0.35);
}
.lc-btn-primary:hover { background: #8f84ff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,111,255,0.5); }

.lc-btn-danger {
  background: rgba(255,85,85,0.15);
  color: var(--red);
  border: 1px solid rgba(255,85,85,0.2);
}
.lc-btn-danger:hover { background: rgba(255,85,85,0.25); }

.lc-icon-btn {
  width: 30px; height: 30px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text-3);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  transition: all 0.15s;
}
.lc-icon-btn:hover { color: var(--text); border-color: var(--border-hi); background: var(--surface-3); }

/* ═══════════════════════════════════════════
   ACTIVITIES BOARD
═══════════════════════════════════════════ */
.lc-board {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
}

.lc-day-col {
  background: var(--bg-2);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  padding: 12px 10px;
  min-height: 140px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.lc-day-head {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--text-3);
  padding-bottom: 8px;
  border-bottom: 1px solid var(--border);
}

.lc-day-head.today { color: var(--accent); }

.lc-act-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  transition: border-color 0.15s;
  animation: slide-in 0.25s ease forwards;
}

@keyframes slide-in {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.lc-act-item:hover { border-color: var(--border-md); }
.lc-act-item.done { opacity: 0.5; }
.lc-act-item.done .lc-act-title { text-decoration: line-through; color: var(--text-3); }

.lc-act-cb {
  width: 18px; height: 18px;
  border-radius: 5px;
  border: 1.5px solid var(--border-md);
  background: transparent;
  cursor: pointer;
  flex-shrink: 0;
  margin-top: 1px;
  transition: all 0.15s;
  appearance: none;
  -webkit-appearance: none;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lc-act-cb:checked {
  background: var(--green);
  border-color: var(--green);
}

.lc-act-cb:checked::after {
  content: '';
  width: 5px; height: 9px;
  border: 2px solid #0a0a0f;
  border-top: none;
  border-left: none;
  position: absolute;
  top: 1px;
  transform: rotate(45deg);
}

.lc-act-title {
  font-size: 12px;
  line-height: 1.4;
  flex: 1;
  word-break: break-word;
}

.lc-act-edit {
  flex-shrink: 0;
  opacity: 0;
  transition: opacity 0.15s;
}

.lc-act-item:hover .lc-act-edit { opacity: 1; }

.lc-day-empty {
  font-size: 11px;
  color: var(--text-3);
  padding: 6px 0;
  font-style: italic;
}

/* ═══════════════════════════════════════════
   HABIT TRACKER
═══════════════════════════════════════════ */
.lc-habit-wrap {
  overflow-x: auto;
  border-radius: var(--r-md);
}

.lc-habit-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px;
}

.lc-habit-table th {
  font-size: 10px;
  font-weight: 700;
  color: var(--text-3);
  text-align: center;
  padding: 6px 4px;
  border-bottom: 1px solid var(--border);
}

.lc-habit-table th:first-child { text-align: left; min-width: 160px; }

.lc-habit-table td {
  border-bottom: 1px solid var(--border);
  padding: 6px 4px;
  text-align: center;
  vertical-align: middle;
}

.lc-habit-table td:first-child {
  text-align: left;
  font-size: 12px;
  font-weight: 500;
}

.lc-habit-table tr:hover td { background: rgba(255,255,255,0.02); }

.lc-habit-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: space-between;
}

.lc-habit-dot {
  width: 24px; height: 24px;
  border-radius: 6px;
  border: 1.5px solid var(--border-md);
  background: transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  transition: all 0.15s ease;
  color: transparent;
}

.lc-habit-dot.active {
  background: rgba(34,212,131,0.25);
  border-color: rgba(34,212,131,0.6);
  color: var(--green);
}

.lc-habit-dot:hover:not(.active) {
  background: rgba(255,255,255,0.05);
  border-color: var(--border-hi);
}

/* ═══════════════════════════════════════════
   WORKOUT CALENDAR
═══════════════════════════════════════════ */
.lc-cal-nav {
  display: flex;
  align-items: center;
  gap: 10px;
}

.lc-cal-month {
  font-family: var(--font-display);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  min-width: 100px;
  text-align: center;
  text-transform: capitalize;
}

.lc-cal-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 4px;
}

.lc-cal-wday {
  font-size: 10px;
  font-weight: 700;
  text-align: center;
  color: var(--text-3);
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 4px 0;
}

.lc-cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.lc-cal-cell {
  aspect-ratio: 1;
  border-radius: 8px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  color: var(--text-2);
}

.lc-cal-cell:hover:not(.empty) { border-color: var(--accent); color: var(--text); transform: scale(1.08); }
.lc-cal-cell.empty { background: transparent; border-color: transparent; cursor: default; }
.lc-cal-cell.done { background: rgba(34,212,131,0.2); border-color: rgba(34,212,131,0.4); color: var(--green); }
.lc-cal-cell.today { outline: 2px solid var(--accent); outline-offset: 1px; color: var(--text); }

.lc-cal-legend {
  display: flex;
  gap: 16px;
  margin-top: 12px;
  font-size: 11px;
  color: var(--text-3);
}

.lc-cal-legend span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.lc-legend-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
}

/* ═══════════════════════════════════════════
   GOALS
═══════════════════════════════════════════ */
.lc-goal-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.lc-goal-item {
  background: var(--bg-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.15s;
  animation: slide-in 0.2s ease forwards;
}

.lc-goal-item:hover { border-color: var(--border-md); }

.lc-goal-item.done .lc-goal-text { text-decoration: line-through; color: var(--text-3); }

.lc-goal-cb {
  width: 18px; height: 18px;
  border-radius: 5px;
  border: 1.5px solid var(--border-md);
  background: transparent;
  cursor: pointer;
  flex-shrink: 0;
  appearance: none;
  -webkit-appearance: none;
  position: relative;
  transition: all 0.15s;
}

.lc-goal-cb:checked {
  background: var(--accent);
  border-color: var(--accent);
}

.lc-goal-cb:checked::after {
  content: '';
  width: 5px; height: 9px;
  border: 2px solid #fff;
  border-top: none;
  border-left: none;
  position: absolute;
  top: 1px; left: 4px;
  transform: rotate(45deg);
}

.lc-goal-text {
  flex: 1;
  font-size: 13px;
  line-height: 1.4;
}

/* Progress pill */
.lc-progress-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--text-3);
}

.lc-prog-bar {
  flex: 1;
  height: 4px;
  background: var(--surface-3);
  border-radius: 99px;
  overflow: hidden;
}

.lc-prog-fill {
  height: 100%;
  border-radius: 99px;
  background: var(--accent);
  transition: width 0.6s cubic-bezier(.22,.68,0,1.2);
}

/* ═══════════════════════════════════════════
   FINANCE
═══════════════════════════════════════════ */
.lc-finance-val {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  margin: 6px 0;
}

.lc-finance-val.income { color: var(--green); }
.lc-finance-val.expense { color: var(--red); }
.lc-finance-val.total { color: var(--text); }

/* ═══════════════════════════════════════════
   LIST ITEMS (events, routine, rules)
═══════════════════════════════════════════ */
.lc-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.lc-list-item {
  background: var(--bg-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  animation: slide-in 0.2s ease forwards;
  transition: border-color 0.15s;
}

.lc-list-item:hover { border-color: var(--border-md); }

.lc-list-item-main { flex: 1; min-width: 0; }

.lc-list-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.lc-time-badge {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 700;
  color: var(--accent);
  min-width: 48px;
  flex-shrink: 0;
}

.lc-weekday-tag {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 99px;
  background: rgba(124,111,255,0.12);
  color: var(--accent);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.06em;
  margin-top: 2px;
}

/* ═══════════════════════════════════════════
   DAILY MESSAGE
═══════════════════════════════════════════ */
.lc-message-card {
  position: relative;
  overflow: hidden;
}

.lc-message-card::before {
  content: '"';
  position: absolute;
  top: -20px; left: 16px;
  font-size: 120px;
  font-family: Georgia, serif;
  color: rgba(124,111,255,0.07);
  line-height: 1;
  pointer-events: none;
}

.lc-message-text {
  font-size: 14px;
  line-height: 1.7;
  color: var(--text-2);
  font-style: italic;
  position: relative;
  z-index: 1;
}

/* ═══════════════════════════════════════════
   HOUSE TASKS
═══════════════════════════════════════════ */
.lc-house-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.lc-house-group {
  background: var(--bg-2);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  padding: 14px;
}

.lc-house-group h4 {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--text-3);
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--border);
}

.lc-task-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 0;
  cursor: pointer;
  font-size: 12px;
  color: var(--text-2);
  transition: color 0.15s;
}

.lc-task-row:hover { color: var(--text); }
.lc-task-row.checked { color: var(--text-3); text-decoration: line-through; }

.lc-task-cb {
  width: 16px; height: 16px;
  border-radius: 4px;
  border: 1.5px solid var(--border-md);
  background: transparent;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  flex-shrink: 0;
  transition: all 0.15s;
  position: relative;
}

.lc-task-cb:checked {
  background: rgba(34,212,131,0.3);
  border-color: var(--green);
}

.lc-task-cb:checked::after {
  content: '';
  width: 4px; height: 8px;
  border: 1.5px solid var(--green);
  border-top: none;
  border-left: none;
  position: absolute;
  top: 0px; left: 4px;
  transform: rotate(45deg);
}

/* ═══════════════════════════════════════════
   MODALS
═══════════════════════════════════════════ */
.lc-modal {
  position: fixed;
  inset: 0;
  background: rgba(5,5,10,0.8);
  backdrop-filter: blur(10px);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 20px;
}

.lc-modal.active { display: flex; }

.lc-modal-box {
  width: min(480px, 95vw);
  background: var(--surface);
  border: 1px solid var(--border-md);
  border-radius: var(--r-xl);
  padding: 28px;
  box-shadow: var(--shadow-lg), var(--glow);
  animation: modal-in 0.22s cubic-bezier(.22,.68,0,1.2) forwards;
  position: relative;
  overflow: hidden;
}

.lc-modal-box::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
}

@keyframes modal-in {
  from { opacity: 0; transform: scale(0.94) translateY(10px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

.lc-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 22px;
}

.lc-modal-title {
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
}

.lc-modal-close {
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-2);
  width: 30px; height: 30px;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.lc-modal-close:hover { color: var(--text); background: var(--surface-3); }

/* ═══════════════════════════════════════════
   INPUTS
═══════════════════════════════════════════ */
.lc-input {
  width: 100%;
  background: var(--bg-2);
  color: var(--text);
  border: 1px solid var(--border-md);
  border-radius: var(--r-sm);
  padding: 10px 14px;
  font-family: var(--font-body);
  font-size: 13px;
  outline: none;
  transition: border-color 0.15s;
  margin-bottom: 12px;
}

.lc-input:focus { border-color: var(--accent); }
.lc-input::placeholder { color: var(--text-3); }

.lc-input-row {
  display: flex;
  gap: 10px;
}

.lc-input-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-3);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 5px;
}

/* ═══════════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════════ */
.lc-empty {
  color: var(--text-3);
  font-size: 12px;
  font-style: italic;
  padding: 8px 0;
  text-align: center;
}

/* ═══════════════════════════════════════════
   TAGS / BADGES
═══════════════════════════════════════════ */
.lc-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 99px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.06em;
}

.lc-badge-purple { background: rgba(124,111,255,0.15); color: var(--accent); border: 1px solid rgba(124,111,255,0.2); }
.lc-badge-green  { background: rgba(34,212,131,0.12);  color: var(--green);  border: 1px solid rgba(34,212,131,0.2); }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 1200px) {
  .lc-stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 900px) {
  .lc-grid-3 { grid-template-columns: 1fr 1fr; }
  .lc-grid-12, .lc-grid-21 { grid-template-columns: 1fr; }
  .lc-board { grid-template-columns: repeat(3, 1fr); }
  .lc-house-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  .lc-app { padding: 16px 14px 110px; }
  .lc-header { grid-template-columns: 1fr; padding: 20px; }
  .lc-header-actions { align-items: flex-start; }
  .lc-stats { grid-template-columns: repeat(2, 1fr); gap: 8px; }
  .lc-grid-2, .lc-grid-3 { grid-template-columns: 1fr; }
  .lc-board { grid-template-columns: repeat(2, 1fr); }
  .lc-house-grid { grid-template-columns: 1fr; }
  .lc-modal-box { padding: 20px; }
  .lc-modal-box { border-radius: var(--r-xl) var(--r-xl) 0 0; align-self: flex-end; width: 100%; margin-bottom: 0; }
  .lc-modal { align-items: flex-end; }
}

/* ═══════════════════════════════════════════
   PAGE LOAD — STAGGER REVEAL
═══════════════════════════════════════════ */
@keyframes lc-fade-up {
  from { opacity: 0; transform: translateY(22px); }
  to   { opacity: 1; transform: translateY(0); }
}

.lc-reveal {
  opacity: 0;
  animation: lc-fade-up 0.5s cubic-bezier(.22,.68,0,1.2) forwards;
}

.lc-reveal:nth-child(1)  { animation-delay: 0.05s; }
.lc-reveal:nth-child(2)  { animation-delay: 0.12s; }
.lc-reveal:nth-child(3)  { animation-delay: 0.19s; }
.lc-reveal:nth-child(4)  { animation-delay: 0.26s; }
.lc-reveal:nth-child(5)  { animation-delay: 0.33s; }
.lc-reveal:nth-child(6)  { animation-delay: 0.40s; }
.lc-reveal:nth-child(7)  { animation-delay: 0.47s; }
.lc-reveal:nth-child(8)  { animation-delay: 0.54s; }
.lc-reveal:nth-child(9)  { animation-delay: 0.60s; }
.lc-reveal:nth-child(10) { animation-delay: 0.66s; }

/* ═══════════════════════════════════════════
   BOTTOM NAV (mobile only)
═══════════════════════════════════════════ */
.lc-bottom-nav {
  display: none;
}

@media (max-width: 900px) {
  .lc-bottom-nav {
    display: flex;
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 100;
    background: rgba(15,15,23,0.92);
    backdrop-filter: blur(20px) saturate(1.4);
    -webkit-backdrop-filter: blur(20px) saturate(1.4);
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 8px 4px calc(8px + env(safe-area-inset-bottom));
    gap: 0;
    justify-content: space-around;
    align-items: center;
  }

  .lc-bottom-nav::before {
    content: '';
    position: absolute;
    top: 0; left: 10%; right: 10%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(124,111,255,0.4), transparent);
  }

  .lc-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.18s ease;
    border: none;
    background: transparent;
    color: var(--text-3);
    text-decoration: none;
    flex: 1;
    max-width: 72px;
    position: relative;
    -webkit-tap-highlight-color: transparent;
  }

  .lc-nav-item.active {
    color: var(--accent);
    background: rgba(124,111,255,0.12);
  }

  .lc-nav-item:active {
    transform: scale(0.9);
    background: rgba(124,111,255,0.18);
  }

  .lc-nav-item i {
    font-size: 18px;
    line-height: 1;
    transition: transform 0.18s cubic-bezier(.22,.68,0,1.5);
  }

  .lc-nav-item.active i {
    transform: translateY(-1px) scale(1.1);
  }

  .lc-nav-label {
    font-family: var(--font-display);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    line-height: 1;
  }

  /* Active indicator dot */
  .lc-nav-item.active::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%; transform: translateX(-50%);
    width: 4px; height: 4px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 8px var(--accent);
    animation: dot-pop 0.25s cubic-bezier(.22,.68,0,1.5) forwards;
  }

  @keyframes dot-pop {
    from { transform: translateX(-50%) scale(0); }
    to   { transform: translateX(-50%) scale(1); }
  }

  /* FAB quick-add button */
  .lc-nav-fab {
    width: 50px; height: 50px;
    border-radius: 16px;
    background: var(--accent);
    border: none;
    color: #fff;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(124,111,255,0.5);
    transition: all 0.18s cubic-bezier(.22,.68,0,1.5);
    position: relative;
    top: -4px;
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
  }

  .lc-nav-fab:active {
    transform: scale(0.88) translateY(2px);
    box-shadow: 0 2px 10px rgba(124,111,255,0.35);
  }
}

/* ═══════════════════════════════════════════
   MICRO-INTERACTIONS
═══════════════════════════════════════════ */

/* Ripple on buttons */
.lc-btn, .lc-icon-btn, .lc-cal-cell {
  position: relative;
  overflow: hidden;
}

.lc-btn::after, .lc-icon-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.06);
  opacity: 0;
  transition: opacity 0.15s;
}

.lc-btn:active::after, .lc-icon-btn:active::after {
  opacity: 1;
}

/* Stat card hover lift */
.lc-stat {
  transition: border-color 0.2s, transform 0.22s cubic-bezier(.22,.68,0,1.2), box-shadow 0.22s;
}

.lc-stat:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0,0,0,0.5);
}

/* Card hover subtle glow */
.lc-card {
  transition: border-color 0.2s, box-shadow 0.2s;
}

.lc-card:hover {
  box-shadow: 0 4px 24px rgba(0,0,0,0.45);
  border-color: rgba(255,255,255,0.1);
}

/* List item tap feedback */
.lc-list-item, .lc-goal-item {
  transition: background 0.15s, border-color 0.15s, transform 0.12s;
}
.lc-list-item:active, .lc-goal-item:active {
  transform: scale(0.99);
}

/* Checkbox pulse on check */
@keyframes cb-check {
  0%   { transform: scale(1); }
  40%  { transform: scale(1.25); }
  70%  { transform: scale(0.93); }
  100% { transform: scale(1); }
}

.lc-act-cb:checked,
.lc-goal-cb:checked,
.lc-task-cb:checked {
  animation: cb-check 0.3s cubic-bezier(.22,.68,0,1.5) forwards;
}

/* Goal done strikethrough animate */
.lc-goal-item.done .lc-goal-text {
  transition: color 0.3s, text-decoration 0s;
}

/* Habit dot pop */
.lc-habit-dot.active {
  animation: habit-pop 0.25s cubic-bezier(.22,.68,0,1.5) forwards;
}

@keyframes habit-pop {
  0%   { transform: scale(1); }
  45%  { transform: scale(1.3); }
  100% { transform: scale(1); }
}

/* Modal slide-up on mobile */
@media (max-width: 640px) {
  @keyframes modal-slide-up {
    from { transform: translateY(100%); opacity: 0.8; }
    to   { transform: translateY(0);    opacity: 1;   }
  }
  .lc-modal.active .lc-modal-box {
    animation: modal-slide-up 0.28s cubic-bezier(.22,.68,0,1.2) forwards;
  }
}

/* Workout calendar cell check animation */
.lc-cal-cell.done {
  animation: cal-done 0.3s cubic-bezier(.22,.68,0,1.5) forwards;
}

@keyframes cal-done {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.15); box-shadow: 0 0 12px rgba(34,212,131,0.4); }
  100% { transform: scale(1); }
}
</style>

<main class="lc-app">

  <!-- ── HEADER ──────────────────────────────── -->
  <header class="lc-header lc-reveal" id="sec-home">
    <div>
      <div class="lc-eyebrow">
        <span class="lc-eyebrow-dot"></span>
        Life Control System
      </div>
      <h1 class="lc-title">Sua <span>rotina</span>.<br>Seu controle.</h1>
      <p class="lc-subtitle">Atividades · Hábitos · Treinos · Metas · Finanças</p>
      <div class="lc-week-badge">
        <i class="fa-regular fa-calendar" style="color:var(--accent)"></i>
        <span id="weekRangeLabel">Semana atual</span>
      </div>
    </div>
    <div class="lc-header-actions">
      <button class="lc-btn lc-btn-primary" data-modal="modalActivity">
        <i class="fa-solid fa-plus"></i> Nova atividade
      </button>
      <button class="lc-btn lc-btn-ghost" data-modal="modalGoal">
        <i class="fa-solid fa-bullseye"></i> Nova meta
      </button>
    </div>
    <div class="lc-header-glow"></div>
  </header>

  <!-- ── STATS BAR ────────────────────────────── -->
  <div class="lc-stats lc-section lc-reveal">
    <div class="lc-stat">
      <div class="lc-stat-icon purple"><i class="fa-solid fa-check-double"></i></div>
      <div class="lc-stat-val" id="statActivities">—</div>
      <div class="lc-stat-label">Atividades feitas</div>
      <div class="lc-stat-bar"><div class="lc-stat-bar-fill fill-purple" id="statActivitiesBar" style="width:0%"></div></div>
    </div>
    <div class="lc-stat">
      <div class="lc-stat-icon green"><i class="fa-solid fa-dumbbell"></i></div>
      <div class="lc-stat-val" id="statWorkouts">—</div>
      <div class="lc-stat-label">Treinos no mês</div>
      <div class="lc-stat-bar"><div class="lc-stat-bar-fill fill-green" id="statWorkoutsBar" style="width:0%"></div></div>
    </div>
    <div class="lc-stat">
      <div class="lc-stat-icon yellow"><i class="fa-solid fa-fire"></i></div>
      <div class="lc-stat-val" id="statHabits">—</div>
      <div class="lc-stat-label">Hábitos hoje</div>
      <div class="lc-stat-bar"><div class="lc-stat-bar-fill fill-yellow" id="statHabitsBar" style="width:0%"></div></div>
    </div>
    <div class="lc-stat">
      <div class="lc-stat-icon red"><i class="fa-solid fa-bullseye"></i></div>
      <div class="lc-stat-val" id="statGoals">—</div>
      <div class="lc-stat-label">Metas concluídas</div>
      <div class="lc-stat-bar"><div class="lc-stat-bar-fill fill-red" id="statGoalsBar" style="width:0%"></div></div>
    </div>
  </div>

  <!-- ── ACTIVITIES BOARD ─────────────────────── -->
  <section class="lc-card lc-section lc-reveal" id="sec-atividades">
    <div class="lc-card-accent-line purple"></div>
    <div class="lc-card-head">
      <div class="lc-card-title">
        <i class="fa-solid fa-list-check"></i>
        Atividades da semana
      </div>
      <button class="lc-btn lc-btn-ghost" data-modal="modalActivity">
        <i class="fa-solid fa-plus"></i> Adicionar
      </button>
    </div>
    <div class="lc-board" id="activitiesBoard"></div>
  </section>

  <!-- ── EVENTS + FINANCE ─────────────────────── -->
  <div class="lc-grid lc-grid-12 lc-section lc-reveal" id="sec-financas">
    <div class="lc-card">
      <div class="lc-card-accent-line blue"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-calendar-days"></i> Eventos</div>
        <button class="lc-btn lc-btn-ghost" data-modal="modalEvent"><i class="fa-solid fa-plus"></i></button>
      </div>
      <div class="lc-list" id="eventsList"></div>
    </div>

    <div class="lc-card">
      <div class="lc-card-accent-line green"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-wallet"></i> Finanças da semana</div>
        <div style="display:flex;gap:6px;">
          <button class="lc-btn lc-btn-ghost" data-modal="modalFinance" data-finance-type="entrada"><i class="fa-solid fa-arrow-up"></i> Entrada</button>
          <button class="lc-btn lc-btn-ghost" data-modal="modalFinance" data-finance-type="saida"><i class="fa-solid fa-arrow-down"></i> Saída</button>
        </div>
      </div>
      <div class="lc-grid lc-grid-3" style="gap:10px;margin-bottom:0;">
        <div style="text-align:center;">
          <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Entradas</div>
          <div class="lc-finance-val income">R$ <span id="financeIncome">0,00</span></div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Saídas</div>
          <div class="lc-finance-val expense">R$ <span id="financeExpense">0,00</span></div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">
            Saldo total <button class="lc-icon-btn" data-modal="modalFinanceBase" title="Definir base" style="width:18px;height:18px;font-size:9px;vertical-align:middle;"><i class="fa-solid fa-pen"></i></button>
          </div>
          <div class="lc-finance-val total">R$ <span id="financeTotal">0,00</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── HABIT TRACKER ───────────────────────── -->
  <section class="lc-card lc-section lc-reveal" id="sec-habitos">
    <div class="lc-card-accent-line yellow"></div>
    <div class="lc-card-head">
      <div class="lc-card-title"><i class="fa-solid fa-fire-flame-curved"></i> Habit Tracker — <span id="habitMonthLabel" style="color:var(--text-2);text-transform:none;font-weight:400;font-size:12px;letter-spacing:0;"></span></div>
      <button class="lc-btn lc-btn-ghost" data-modal="modalHabit"><i class="fa-solid fa-plus"></i> Novo hábito</button>
    </div>
    <div class="lc-habit-wrap">
      <table class="lc-habit-table" id="habitTable"></table>
    </div>
  </section>

  <!-- ── GOALS + WORKOUT CAL ─────────────────── -->
  <div class="lc-grid lc-grid-3 lc-section lc-reveal" id="sec-treino">

    <!-- Metas gerais -->
    <div class="lc-card">
      <div class="lc-card-accent-line purple"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-bullseye"></i> Metas gerais</div>
        <div style="display:flex;gap:6px;">
          <button class="lc-btn lc-btn-ghost" data-modal="modalGoal"><i class="fa-solid fa-plus"></i></button>
          <button class="lc-btn lc-btn-ghost" data-modal="modalGoalsView">Ver todas</button>
        </div>
      </div>
      <div class="lc-goal-list" id="goalsList"></div>
      <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
        <div style="font-size:11px;color:var(--text-3);margin-bottom:6px;">CONCLUÍDAS</div>
        <div class="lc-goal-list" id="goalsDoneList"></div>
      </div>
    </div>

    <!-- Metas do mês -->
    <div class="lc-card">
      <div class="lc-card-accent-line red"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-regular fa-calendar-check"></i> Metas do mês</div>
        <button class="lc-btn lc-btn-ghost" data-modal="modalGoalMonth"><i class="fa-solid fa-plus"></i></button>
      </div>
      <div class="lc-goal-list" id="monthlyGoalsList"></div>
    </div>

    <!-- Treino do mês -->
    <div class="lc-card">
      <div class="lc-card-accent-line green"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-dumbbell"></i> Treino do mês</div>
        <div class="lc-cal-nav">
          <button class="lc-icon-btn" id="prevMonth">‹</button>
          <div class="lc-cal-month" id="workoutMonthLabel"></div>
          <button class="lc-icon-btn" id="nextMonth">›</button>
        </div>
      </div>
      <div class="lc-cal-weekdays">
        <div class="lc-cal-wday">S</div><div class="lc-cal-wday">T</div>
        <div class="lc-cal-wday">Q</div><div class="lc-cal-wday">Q</div>
        <div class="lc-cal-wday">S</div><div class="lc-cal-wday">S</div>
        <div class="lc-cal-wday">D</div>
      </div>
      <div class="lc-cal-grid" id="workoutCalendar"></div>
      <div class="lc-cal-legend">
        <span><span class="lc-legend-dot" style="background:var(--green)"></span> Treinou</span>
        <span><span class="lc-legend-dot" style="background:var(--surface-3)"></span> Descanso</span>
      </div>
    </div>

  </div>

  <!-- ── ROUTINE + RULES + MESSAGE ───────────── -->
  <div class="lc-grid lc-grid-3 lc-section lc-reveal" id="sec-rotina">

    <div class="lc-card">
      <div class="lc-card-accent-line purple"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-clock"></i> Rotina do dia</div>
        <button class="lc-btn lc-btn-ghost" data-modal="modalRoutine"><i class="fa-solid fa-plus"></i></button>
      </div>
      <div class="lc-list" id="routineList"></div>
    </div>

    <div class="lc-card">
      <div class="lc-card-accent-line red"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-gavel"></i> Regras de vida</div>
        <button class="lc-btn lc-btn-ghost" data-modal="modalRule"><i class="fa-solid fa-plus"></i></button>
      </div>
      <div class="lc-list" id="rulesList"></div>
    </div>

    <div class="lc-card lc-message-card">
      <div class="lc-card-accent-line yellow"></div>
      <div class="lc-card-head">
        <div class="lc-card-title"><i class="fa-solid fa-quote-left"></i> Mensagem do dia</div>
        <div class="lc-badge lc-badge-purple" id="messageDate">Hoje</div>
      </div>
      <p class="lc-message-text" id="dailyMessage">Carregando...</p>
    </div>

  </div>

  <!-- ── HOUSE TASKS ──────────────────────────── -->
  <section class="lc-card lc-section lc-reveal" id="sec-casa">
    <div class="lc-card-accent-line blue"></div>
    <div class="lc-card-head">
      <div class="lc-card-title"><i class="fa-solid fa-house-chimney"></i> Responsabilidades do Marc</div>
      <div class="lc-badge lc-badge-green" id="houseWeekTag">Semana</div>
    </div>
    <div class="lc-house-grid" id="houseGrid">

      <div class="lc-house-group">
        <h4>Diárias</h4>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="diaria-lavar-louca"> Lavar louça</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="diaria-limpar-pia-cozinha"> Limpar pia da cozinha</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="diaria-limpar-migalhas"> Limpar migalhas da bancada</label>
      </div>

      <div class="lc-house-group">
        <h4>Semanais</h4>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semanal-banheiro-vaso"> Lavar banheiro — Vaso</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semanal-banheiro-ralo"> Lavar banheiro — Ralo</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semanal-banheiro-pia"> Lavar banheiro — Pia</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semanal-limpar-geladeira"> Limpar geladeira</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semanal-lavar-panos"> Lavar panos</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semanal-compras"> Compras da semana</label>
      </div>

      <div class="lc-house-group">
        <h4>2× por semana</h4>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="2x-varrer-apartamento"> Varrer apartamento</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="2x-esvaziar-lixos"> Esvaziar lixos</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="2x-limpar-pia-banheiro"> Limpar pia do banheiro</label>
      </div>

      <div class="lc-house-group">
        <h4>Quinzenais</h4>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="quinzenal-armarios"> Limpar armários</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="quinzenal-pa-lixo"> Limpar pá de lixo</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="quinzenal-air-fryer"> Limpar air fryer</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="quinzenal-mesa-cadeiras"> Limpar mesa e cadeiras</label>
      </div>

      <div class="lc-house-group">
        <h4>Mensais</h4>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="mensal-tomadas-fios"> Limpar tomadas e fios</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="mensal-sacada"> Limpar sacada</label>
      </div>

      <div class="lc-house-group">
        <h4>Semestrais</h4>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semestral-tv"> Limpar televisão</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semestral-filtro-maquina"> Filtro da máquina</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semestral-chuveiro"> Limpar chuveiro</label>
        <label class="lc-task-row"><input type="checkbox" class="lc-task-cb" data-task-id="semestral-ventilador"> Limpar ventilador</label>
      </div>

    </div>
  </section>

</main>

<!-- ═══════════════════════════════════════════
     MODALS
═══════════════════════════════════════════ -->

<!-- Activity -->
<div class="lc-modal" id="modalActivity">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Nova atividade</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <input type="hidden" id="activityId">
    <div class="lc-input-label">O que precisa fazer?</div>
    <input class="lc-input" id="activityTitle" placeholder="Ex: Revisar projeto, pagar conta...">
    <div class="lc-input-label">Dia da semana</div>
    <select class="lc-input" id="activityWeekday">
      <option value="1">Segunda-feira</option>
      <option value="2">Terça-feira</option>
      <option value="3">Quarta-feira</option>
      <option value="4">Quinta-feira</option>
      <option value="5">Sexta-feira</option>
      <option value="6">Sábado</option>
      <option value="7">Domingo</option>
    </select>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <button class="lc-btn lc-btn-danger" id="deleteActivity" style="display:none;"><i class="fa-solid fa-trash"></i> Apagar</button>
      <button class="lc-btn lc-btn-primary" id="saveActivity" style="flex:1;"><i class="fa-solid fa-check"></i> Salvar</button>
    </div>
  </div>
</div>

<!-- Habit -->
<div class="lc-modal" id="modalHabit">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Novo hábito</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <div class="lc-input-label">Nome do hábito</div>
    <input class="lc-input" id="habitName" placeholder="Ex: Beber água, meditar, ler...">
    <button class="lc-btn lc-btn-primary" id="saveHabit" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<!-- Event -->
<div class="lc-modal" id="modalEvent">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Evento da semana</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <input type="hidden" id="eventId">
    <div class="lc-input-label">Nome do evento</div>
    <input class="lc-input" id="eventTitle" placeholder="Ex: Reunião, consulta, aniversário...">
    <div class="lc-input-label">Data</div>
    <input class="lc-input" id="eventDate" type="date">
    <button class="lc-btn lc-btn-primary" id="saveEvent" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<!-- Finance -->
<div class="lc-modal" id="modalFinance">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title" id="financeModalTitle">Novo lançamento</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <input type="hidden" id="financeType" value="entrada">
    <div class="lc-input-label">Valor (R$)</div>
    <input class="lc-input" id="financeAmount" type="number" step="0.01" placeholder="0,00">
    <div class="lc-input-label">Data</div>
    <input class="lc-input" id="financeDate" type="date">
    <button class="lc-btn lc-btn-primary" id="saveFinance" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<!-- Finance Base -->
<div class="lc-modal" id="modalFinanceBase">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Saldo base (banco)</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <div class="lc-input-label">Valor atual no banco (R$)</div>
    <input class="lc-input" id="financeBaseModal" type="number" step="0.01" placeholder="0,00">
    <button class="lc-btn lc-btn-primary" id="saveFinanceBase" style="width:100%;"><i class="fa-solid fa-check"></i> Definir</button>
  </div>
</div>

<!-- Routine -->
<div class="lc-modal" id="modalRoutine">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Rotina</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <input type="hidden" id="routineId">
    <div class="lc-input-row">
      <div style="flex:1;">
        <div class="lc-input-label">Horário</div>
        <input class="lc-input" id="routineTime" type="time">
      </div>
      <div style="flex:2;">
        <div class="lc-input-label">Atividade</div>
        <input class="lc-input" id="routineActivity" placeholder="Ex: Acordar, treinar...">
      </div>
    </div>
    <button class="lc-btn lc-btn-primary" id="saveRoutine" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<!-- Rule -->
<div class="lc-modal" id="modalRule">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Nova regra de vida</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <input type="hidden" id="ruleId">
    <div class="lc-input-label">Escreva a regra</div>
    <input class="lc-input" id="ruleText" placeholder="Ex: Nunca dormir sem planejar o dia seguinte">
    <button class="lc-btn lc-btn-primary" id="saveRule" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<!-- Goal -->
<div class="lc-modal" id="modalGoal">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Nova meta</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <div class="lc-input-label">Título da meta</div>
    <input class="lc-input" id="goalTitle" placeholder="Ex: Aprender Angular, poupar R$500...">
    <button class="lc-btn lc-btn-primary" id="saveGoal" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<!-- Goals View -->
<div class="lc-modal" id="modalGoalsView">
  <div class="lc-modal-box" style="max-height:80vh;overflow-y:auto;">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Todas as metas</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <div class="lc-goal-list" id="goalsViewList"></div>
  </div>
</div>

<!-- Goal Month -->
<div class="lc-modal" id="modalGoalMonth">
  <div class="lc-modal-box">
    <div class="lc-modal-head">
      <div class="lc-modal-title">Meta do mês</div>
      <button class="lc-modal-close" data-close>×</button>
    </div>
    <div class="lc-input-label">Título da meta</div>
    <input class="lc-input" id="goalMonthTitle" placeholder="O que você quer conquistar esse mês?">
    <button class="lc-btn lc-btn-primary" id="saveGoalMonth" style="width:100%;"><i class="fa-solid fa-check"></i> Salvar</button>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════
   API HELPER
══════════════════════════════════════════════ */
const api = (action, payload = {}, method = 'POST') => {
  const opts = { method };
  if (method !== 'GET') {
    opts.headers = { 'Content-Type': 'application/json' };
    opts.body = JSON.stringify(payload);
  }
  return fetch(`?api=${action}`, opts).then(r => r.json());
};

/* ══════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════ */
const openModal = id => document.getElementById(id)?.classList.add('active');
const closeModals = () => document.querySelectorAll('.lc-modal').forEach(m => m.classList.remove('active'));

document.addEventListener('click', e => {
  const trigger = e.target.closest('[data-modal]');
  if (trigger) {
    const modalId = trigger.dataset.modal;

    if (modalId === 'modalFinance') {
      const t = trigger.dataset.financeType || 'entrada';
      document.getElementById('financeType').value = t;
      document.getElementById('financeModalTitle').textContent = t === 'entrada' ? 'Registrar entrada' : 'Registrar saída';
    }
    if (modalId === 'modalActivity') {
      document.getElementById('activityId').value = '';
      document.getElementById('activityTitle').value = '';
      const jsDay = new Date().getDay();
      document.getElementById('activityWeekday').value = String(jsDay === 0 ? 7 : jsDay);
      document.getElementById('deleteActivity').style.display = 'none';
    }
    if (modalId === 'modalEvent') {
      document.getElementById('eventId').value = '';
      document.getElementById('eventTitle').value = '';
      document.getElementById('eventDate').value = todayStr();
    }
    if (modalId === 'modalFinanceBase') {
      const v = parseFloat(localStorage.getItem('financeBase') || '0') || 0;
      document.getElementById('financeBaseModal').value = v.toFixed(2);
    }
    if (modalId === 'modalGoalsView') loadGoalsView();
    openModal(modalId);
    return;
  }
  if (e.target.matches('[data-close]') || e.target.classList.contains('lc-modal')) closeModals();
});

/* ══════════════════════════════════════════════
   DATE HELPERS
══════════════════════════════════════════════ */
const todayStr = () => new Date().toISOString().slice(0, 10);

const getMondayOfWeek = () => {
  const now = new Date();
  const day = now.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  const monday = new Date(now);
  monday.setDate(now.getDate() + diff);
  return monday;
};

const getDateForWeekday = n => {
  const monday = getMondayOfWeek();
  const d = new Date(monday);
  d.setDate(monday.getDate() + (n - 1));
  return d.toISOString().slice(0, 10);
};

const getWeekdayNum = dateStr => {
  const d = new Date(dateStr + 'T00:00:00');
  const jsDay = d.getDay();
  return jsDay === 0 ? 7 : jsDay;
};

const fmtDate = dateStr => {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: '2-digit' });
};

const fmtWeekdayFull = dateStr => {
  const d = new Date(dateStr.slice(0,10) + 'T00:00:00');
  const l = d.toLocaleDateString('pt-BR', { weekday: 'long' });
  return l.charAt(0).toUpperCase() + l.slice(1);
};

const fmtMonthLabel = date => {
  const l = date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
  return l.charAt(0).toUpperCase() + l.slice(1);
};

const setDefaultDates = () => {
  const t = todayStr();
  ['workoutDate','financeDate','eventDate'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = t;
  });
};

/* ══════════════════════════════════════════════
   WEEK RANGE
══════════════════════════════════════════════ */
const loadWeekRange = () => {
  const monday = getMondayOfWeek();
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  document.getElementById('weekRangeLabel').textContent =
    `${monday.toLocaleDateString('pt-BR')} — ${sunday.toLocaleDateString('pt-BR')}`;
};

/* ══════════════════════════════════════════════
   ACTIVITIES
══════════════════════════════════════════════ */
const loadActivities = async () => {
  const list = await api('get_week_activities', {}, 'GET');
  const container = document.getElementById('activitiesBoard');
  container.innerHTML = '';

  const today = todayStr();
  const days = [
    { id: 1, label: 'Segunda', short: 'Seg' },
    { id: 2, label: 'Terça',   short: 'Ter' },
    { id: 3, label: 'Quarta',  short: 'Qua' },
    { id: 4, label: 'Quinta',  short: 'Qui' },
    { id: 5, label: 'Sexta',   short: 'Sex' },
    { id: 'weekend', label: 'Sáb/Dom', short: 'S/D' }
  ];

  const grouped = new Map();
  days.forEach(d => grouped.set(d.id, []));
  list.forEach(item => {
    const n = getWeekdayNum(item.day_date);
    const key = (n === 6 || n === 7) ? 'weekend' : n;
    if (!grouped.has(key)) grouped.set(key, []);
    grouped.get(key).push(item);
  });

  let doneCount = 0, totalCount = 0;

  days.forEach(day => {
    const items = grouped.get(day.id) || [];
    const dateForDay = day.id !== 'weekend' ? getDateForWeekday(day.id) : null;
    const isToday = dateForDay === today;
    totalCount += items.length;
    doneCount += items.filter(i => parseInt(i.status) === 1).length;

    const col = document.createElement('div');
    col.className = 'lc-day-col';
    col.innerHTML = `<div class="lc-day-head${isToday ? ' today' : ''}">${day.short}${isToday ? ' ·' : ''}</div>`;

    const itemsDiv = document.createElement('div');
    itemsDiv.style.display = 'flex';
    itemsDiv.style.flexDirection = 'column';
    itemsDiv.style.gap = '6px';

    if (!items.length) {
      itemsDiv.innerHTML = '<div class="lc-day-empty">Livre</div>';
    } else {
      items.forEach(item => {
        const isDone = parseInt(item.status) === 1;
        const div = document.createElement('div');
        div.className = 'lc-act-item' + (isDone ? ' done' : '');
        div.innerHTML = `
          <input type="checkbox" class="lc-act-cb" data-action="toggle-activity" data-id="${item.id}" ${isDone ? 'checked' : ''}>
          <span class="lc-act-title">${item.title}</span>
          <button class="lc-icon-btn lc-act-edit" data-action="edit-activity"
            data-id="${item.id}" data-title="${item.title}" data-weekday="${getWeekdayNum(item.day_date)}">
            <i class="fa-solid fa-pen"></i>
          </button>`;
        itemsDiv.appendChild(div);
      });
    }
    col.appendChild(itemsDiv);
    container.appendChild(col);
  });

  // Update stat
  const pct = totalCount ? Math.round((doneCount / totalCount) * 100) : 0;
  document.getElementById('statActivities').textContent = `${doneCount}/${totalCount}`;
  document.getElementById('statActivitiesBar').style.width = pct + '%';
};

/* ══════════════════════════════════════════════
   HABITS
══════════════════════════════════════════════ */
const loadHabits = async () => {
  const month = new Date().toISOString().slice(0, 7);
  const habits = await fetch(`?api=get_habits&month=${month}`).then(r => r.json());
  const table = document.getElementById('habitTable');
  const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
  const today = parseInt(new Date().toISOString().slice(8, 10));

  // Month label
  const ml = document.getElementById('habitMonthLabel');
  if (ml) {
    const lbl = new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    ml.textContent = lbl.charAt(0).toUpperCase() + lbl.slice(1);
  }

  let header = '<thead><tr><th>Hábito</th>';
  for (let d = 1; d <= daysInMonth; d++) header += `<th style="${d === today ? 'color:var(--accent)' : ''}">${d}</th>`;
  header += '</tr></thead>';

  let body = '<tbody>';
  let totalToday = 0, doneToday = 0;
  habits.forEach(habit => {
    const checks = JSON.parse(habit.checked_dates || '[]');
    const todayFull = `${month}-${String(today).padStart(2, '0')}`;
    if (checks.includes(todayFull)) doneToday++;
    totalToday++;

    body += `<tr><td>
      <div class="lc-habit-name-row">
        <span style="font-size:12px;">${habit.name}</span>
        <button class="lc-icon-btn" data-action="delete-habit" data-id="${habit.id}" style="flex-shrink:0;">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </td>`;

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${month}-${String(d).padStart(2, '0')}`;
      const isActive = checks.includes(dateStr);
      body += `<td><span class="lc-habit-dot ${isActive ? 'active' : ''}" data-habit="${habit.id}" data-date="${dateStr}">${isActive ? '✓' : ''}</span></td>`;
    }
    body += '</tr>';
  });
  body += '</tbody>';
  table.innerHTML = header + body;

  // Stat
  const pct = totalToday ? Math.round((doneToday / totalToday) * 100) : 0;
  document.getElementById('statHabits').textContent = `${doneToday}/${totalToday}`;
  document.getElementById('statHabitsBar').style.width = pct + '%';
};

/* ══════════════════════════════════════════════
   WORKOUT CALENDAR
══════════════════════════════════════════════ */
let workoutMonth = new Date();

const renderWorkoutCalendar = (workouts, current) => {
  const calendar = document.getElementById('workoutCalendar');
  const label = document.getElementById('workoutMonthLabel');
  if (!calendar || !label) return;
  label.textContent = fmtMonthLabel(current);

  const year = current.getFullYear();
  const month = current.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startWeekday = (firstDay.getDay() + 6) % 7;
  const totalCells = Math.ceil((startWeekday + lastDay.getDate()) / 7) * 7;
  const monthKey = `${year}-${String(month + 1).padStart(2, '0')}`;
  const todayKey = todayStr();
  const map = new Map(workouts.map(w => [w.workout_date, w]));

  calendar.innerHTML = '';
  let doneCount = 0;

  for (let i = 0; i < totalCells; i++) {
    const dayNum = i - startWeekday + 1;
    if (dayNum < 1 || dayNum > lastDay.getDate()) {
      const empty = document.createElement('div');
      empty.className = 'lc-cal-cell empty';
      calendar.appendChild(empty);
      continue;
    }
    const dateStr = `${monthKey}-${String(dayNum).padStart(2, '0')}`;
    const w = map.get(dateStr);
    const isDone = w && parseInt(w.done) === 1;
    if (isDone) doneCount++;

    const cell = document.createElement('button');
    cell.type = 'button';
    cell.className = 'lc-cal-cell' + (isDone ? ' done' : '') + (dateStr === todayKey ? ' today' : '');
    cell.dataset.date = dateStr;
    cell.textContent = dayNum;
    calendar.appendChild(cell);
  }

  // Stat (only current month)
  const isCurrentMonth = current.getFullYear() === new Date().getFullYear() && current.getMonth() === new Date().getMonth();
  if (isCurrentMonth) {
    const pct = Math.min(Math.round((doneCount / 20) * 100), 100);
    document.getElementById('statWorkouts').textContent = doneCount;
    document.getElementById('statWorkoutsBar').style.width = pct + '%';
  }
};

const loadWorkoutsMonth = async () => {
  const monthKey = `${workoutMonth.getFullYear()}-${String(workoutMonth.getMonth() + 1).padStart(2, '0')}`;
  const list = await fetch(`?api=get_workouts_month&month=${monthKey}`).then(r => r.json());
  renderWorkoutCalendar(list, workoutMonth);
};

/* ══════════════════════════════════════════════
   FINANCE
══════════════════════════════════════════════ */
const loadFinance = async () => {
  const data = await api('get_finance_week', {}, 'GET');
  document.getElementById('financeIncome').textContent = data.income.toFixed(2);
  document.getElementById('financeExpense').textContent = data.expense.toFixed(2);
  const base = parseFloat(localStorage.getItem('financeBase') || '0') || 0;
  document.getElementById('financeTotal').textContent = (base + data.income - data.expense).toFixed(2);
};

/* ══════════════════════════════════════════════
   EVENTS
══════════════════════════════════════════════ */
const loadEvents = async () => {
  const list = await api('get_events_week', {}, 'GET');
  const container = document.getElementById('eventsList');
  if (!list.length) { container.innerHTML = '<div class="lc-empty">Sem eventos nessa semana.</div>'; return; }
  container.innerHTML = '';
  list.forEach(item => {
    const row = document.createElement('div');
    row.className = 'lc-list-item';
    row.innerHTML = `
      <div class="lc-list-item-main">
        <div style="font-size:13px;font-weight:600;">${item.title}</div>
        <div class="lc-weekday-tag">${fmtWeekdayFull(item.start_date)}</div>
      </div>
      <div class="lc-list-actions">
        <button class="lc-icon-btn" data-action="edit-event" data-id="${item.id}" data-title="${item.title}" data-date="${item.start_date.slice(0,10)}"><i class="fa-solid fa-pen"></i></button>
        <button class="lc-icon-btn" data-action="delete-event" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>
      </div>`;
    container.appendChild(row);
  });
};

/* ══════════════════════════════════════════════
   ROUTINE
══════════════════════════════════════════════ */
const loadRoutine = async () => {
  const list = await api('get_routine_items', {}, 'GET');
  const container = document.getElementById('routineList');
  if (!list.length) { container.innerHTML = '<div class="lc-empty">Sem rotina cadastrada.</div>'; return; }
  container.innerHTML = '';
  list.forEach(item => {
    const row = document.createElement('div');
    row.className = 'lc-list-item';
    row.innerHTML = `
      <span class="lc-time-badge">${(item.routine_time || '').slice(0,5)}</span>
      <div class="lc-list-item-main" style="font-size:13px;">${item.activity}</div>
      <div class="lc-list-actions">
        <button class="lc-icon-btn" data-action="edit-routine" data-id="${item.id}" data-time="${item.routine_time||''}" data-activity="${item.activity||''}"><i class="fa-solid fa-pen"></i></button>
        <button class="lc-icon-btn" data-action="delete-routine" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>
      </div>`;
    container.appendChild(row);
  });
};

/* ══════════════════════════════════════════════
   RULES
══════════════════════════════════════════════ */
const loadRules = async () => {
  const list = await api('get_rules', {}, 'GET');
  const container = document.getElementById('rulesList');
  if (!list.length) { container.innerHTML = '<div class="lc-empty">Sem regras cadastradas.</div>'; return; }
  container.innerHTML = '';
  list.forEach(item => {
    const row = document.createElement('div');
    row.className = 'lc-list-item';
    row.innerHTML = `
      <div class="lc-list-item-main" style="font-size:13px;">${item.rule_text}</div>
      <div class="lc-list-actions">
        <button class="lc-icon-btn" data-action="delete-rule" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>
      </div>`;
    container.appendChild(row);
  });
};

/* ══════════════════════════════════════════════
   GOALS
══════════════════════════════════════════════ */
const loadGoals = async () => {
  const list = await api('get_goals', {}, 'GET');
  const pending = list.filter(i => parseInt(i.status) === 0);
  const container = document.getElementById('goalsList');
  if (!pending.length) { container.innerHTML = '<div class="lc-empty">Sem metas pendentes.</div>'; return; }
  container.innerHTML = '';
  pending.forEach(item => {
    const div = document.createElement('div');
    div.className = 'lc-goal-item';
    div.innerHTML = `
      <input type="checkbox" class="lc-goal-cb" data-action="toggle-goal" data-id="${item.id}">
      <span class="lc-goal-text">${item.title}</span>
      <button class="lc-icon-btn" data-action="delete-goal" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>`;
    container.appendChild(div);
  });
};

const loadGoalsDone = async () => {
  const list = await api('get_goals_done', {}, 'GET');
  const container = document.getElementById('goalsDoneList');
  const stat = document.getElementById('statGoals');
  const bar  = document.getElementById('statGoalsBar');
  stat.textContent = list.length;
  bar.style.width = Math.min(list.length * 10, 100) + '%';

  if (!list.length) { container.innerHTML = '<div class="lc-empty">Nenhuma ainda.</div>'; return; }
  container.innerHTML = '';
  list.slice(0, 5).forEach(item => {
    const div = document.createElement('div');
    div.className = 'lc-goal-item done';
    div.innerHTML = `
      <input type="checkbox" class="lc-goal-cb" checked data-action="toggle-goal" data-id="${item.id}">
      <span class="lc-goal-text">${item.title}</span>`;
    container.appendChild(div);
  });
};

const loadGoalsView = async () => {
  const list = await api('get_goals', {}, 'GET');
  const container = document.getElementById('goalsViewList');
  if (!list.length) { container.innerHTML = '<div class="lc-empty">Sem metas cadastradas.</div>'; return; }
  container.innerHTML = '';
  list.forEach(item => {
    const isDone = parseInt(item.status) === 1;
    const div = document.createElement('div');
    div.className = 'lc-goal-item' + (isDone ? ' done' : '');
    div.innerHTML = `
      <input type="checkbox" class="lc-goal-cb" data-action="toggle-goal" data-id="${item.id}" ${isDone ? 'checked' : ''}>
      <span class="lc-goal-text">${item.title}</span>
      <button class="lc-icon-btn" data-action="delete-goal" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>`;
    container.appendChild(div);
  });
};

const loadMonthlyGoals = async () => {
  const list = await api('get_goals_month', {}, 'GET');
  const container = document.getElementById('monthlyGoalsList');
  if (!list.length) { container.innerHTML = '<div class="lc-empty">Sem metas do mês.</div>'; return; }
  container.innerHTML = '';
  list.forEach(item => {
    const isDone = parseInt(item.status) === 1;
    const div = document.createElement('div');
    div.className = 'lc-goal-item' + (isDone ? ' done' : '');
    div.innerHTML = `
      <input type="checkbox" class="lc-goal-cb" data-action="toggle-goal-month" data-id="${item.id}" ${isDone ? 'checked' : ''}>
      <span class="lc-goal-text">${item.title}</span>
      <button class="lc-icon-btn" data-action="delete-goal-month" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>`;
    container.appendChild(div);
  });
};

/* ══════════════════════════════════════════════
   DAILY MESSAGE
══════════════════════════════════════════════ */
const loadMessage = async () => {
  const data = await api('get_daily_message', {}, 'GET');
  document.getElementById('dailyMessage').textContent = data.text || 'Sem mensagem disponível.';
  document.getElementById('messageDate').textContent = new Date().toLocaleDateString('pt-BR', { day: '2-digit', month: 'long' });
};

/* ══════════════════════════════════════════════
   HOUSE TASKS
══════════════════════════════════════════════ */
const loadHouseTasks = () => {
  const monday = getMondayOfWeek();
  const weekKey = monday.toISOString().slice(0, 10);
  const savedWeek = localStorage.getItem('houseTasksWeek');
  const status = JSON.parse(localStorage.getItem('houseTasksStatus') || '{}');

  const LONG_FREQ = new Set(['quinzenal', 'mensal', 'semestral']);
  const checkboxes = document.querySelectorAll('#houseGrid input[type="checkbox"][data-task-id]');

  if (savedWeek !== weekKey) {
    localStorage.setItem('houseTasksWeek', weekKey);
    checkboxes.forEach(cb => {
      const freq = cb.dataset.frequency || 'weekly';
      if (!LONG_FREQ.has(freq)) delete status[cb.dataset.taskId];
    });
  }

  checkboxes.forEach(cb => {
    const id = cb.dataset.taskId;
    cb.checked = Boolean(status[id]);
    const row = cb.closest('.lc-task-row');
    if (row) row.classList.toggle('checked', Boolean(status[id]));
    if (cb.dataset.bound !== 'true') {
      cb.addEventListener('change', () => {
        if (cb.checked) status[id] = true;
        else delete status[id];
        localStorage.setItem('houseTasksStatus', JSON.stringify(status));
        const r = cb.closest('.lc-task-row');
        if (r) r.classList.toggle('checked', cb.checked);
      });
      cb.dataset.bound = 'true';
    }
  });

  localStorage.setItem('houseTasksStatus', JSON.stringify(status));

  const tag = document.getElementById('houseWeekTag');
  if (tag) {
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    tag.textContent = `${monday.toLocaleDateString('pt-BR')} — ${sunday.toLocaleDateString('pt-BR')}`;
  }
};

/* ══════════════════════════════════════════════
   DELEGATED CLICK EVENTS
══════════════════════════════════════════════ */
document.addEventListener('click', async e => {
  // Habit toggle
  if (e.target.matches('.lc-habit-dot[data-habit]')) {
    await api('toggle_habit', { id: e.target.dataset.habit, date: e.target.dataset.date });
    loadHabits();
    return;
  }
  // Delete habit
  const delHabit = e.target.closest('[data-action="delete-habit"]');
  if (delHabit) {
    if (!confirm('Apagar este hábito?')) return;
    await api('remove_habit', { id: delHabit.dataset.id });
    loadHabits();
    return;
  }
  // Workout calendar
  const wCell = e.target.closest('.lc-cal-cell[data-date]');
  if (wCell) {
    await api('toggle_workout_day', { date: wCell.dataset.date });
    loadWorkoutsMonth();
    return;
  }
  // Edit event
  const editEvent = e.target.closest('[data-action="edit-event"]');
  if (editEvent) {
    document.getElementById('eventId').value = editEvent.dataset.id;
    document.getElementById('eventTitle').value = editEvent.dataset.title;
    document.getElementById('eventDate').value = editEvent.dataset.date;
    openModal('modalEvent');
    return;
  }
  // Delete event
  const delEvent = e.target.closest('[data-action="delete-event"]');
  if (delEvent) { await api('delete_event', { id: delEvent.dataset.id }); loadEvents(); return; }

  // Edit activity
  const editAct = e.target.closest('[data-action="edit-activity"]');
  if (editAct) {
    document.getElementById('activityId').value = editAct.dataset.id;
    document.getElementById('activityTitle').value = editAct.dataset.title || '';
    document.getElementById('activityWeekday').value = editAct.dataset.weekday || '1';
    document.getElementById('deleteActivity').style.display = 'inline-flex';
    openModal('modalActivity');
    return;
  }
  // Edit routine
  const editRoutine = e.target.closest('[data-action="edit-routine"]');
  if (editRoutine) {
    document.getElementById('routineId').value = editRoutine.dataset.id;
    document.getElementById('routineTime').value = editRoutine.dataset.time || '';
    document.getElementById('routineActivity').value = editRoutine.dataset.activity || '';
    openModal('modalRoutine');
    return;
  }
  // Delete routine
  const delRoutine = e.target.closest('[data-action="delete-routine"]');
  if (delRoutine) { await api('delete_routine_item', { id: delRoutine.dataset.id }); loadRoutine(); return; }

  // Delete rule
  const delRule = e.target.closest('[data-action="delete-rule"]');
  if (delRule) { await api('delete_rule', { id: delRule.dataset.id }); loadRules(); return; }

  // Toggle goal
  const togGoal = e.target.closest('[data-action="toggle-goal"]');
  if (togGoal && e.type === 'click' && !e.target.matches('input')) {
    await api('toggle_goal', { id: togGoal.dataset.id }); loadGoals(); loadGoalsDone(); return;
  }
  // Delete goal
  const delGoal = e.target.closest('[data-action="delete-goal"]');
  if (delGoal) { await api('delete_goal', { id: delGoal.dataset.id }); loadGoals(); loadGoalsDone(); return; }

  // Delete goal month
  const delGoalM = e.target.closest('[data-action="delete-goal-month"]');
  if (delGoalM) { await api('delete_goal_month', { id: delGoalM.dataset.id }); loadMonthlyGoals(); return; }
});

/* ══════════════════════════════════════════════
   CHANGE EVENTS
══════════════════════════════════════════════ */
document.addEventListener('change', async e => {
  if (e.target.matches('[data-action="toggle-activity"]')) {
    await api('toggle_activity', { id: e.target.dataset.id });
    loadActivities();
    return;
  }
  if (e.target.matches('[data-action="toggle-goal"]')) {
    await api('toggle_goal', { id: e.target.dataset.id });
    loadGoals(); loadGoalsDone();
    return;
  }
  if (e.target.matches('[data-action="toggle-goal-month"]')) {
    await api('toggle_goal_month', { id: e.target.dataset.id });
    loadMonthlyGoals();
    return;
  }
});

/* ══════════════════════════════════════════════
   SAVE HANDLERS
══════════════════════════════════════════════ */
document.getElementById('saveActivity').addEventListener('click', async () => {
  const id = document.getElementById('activityId').value;
  const title = document.getElementById('activityTitle').value.trim();
  if (!title) return;
  const weekday = parseInt(document.getElementById('activityWeekday').value);
  const date = getDateForWeekday(weekday);
  if (id) await api('update_activity', { id, title, date });
  else await api('save_activity', { title, date });
  closeModals();
  document.getElementById('activityTitle').value = '';
  document.getElementById('activityId').value = '';
  loadActivities();
});

document.getElementById('deleteActivity').addEventListener('click', async () => {
  const id = document.getElementById('activityId').value;
  if (!id) return;
  await api('delete_activity', { id });
  closeModals();
  document.getElementById('activityId').value = '';
  document.getElementById('activityTitle').value = '';
  loadActivities();
});

document.getElementById('saveHabit').addEventListener('click', async () => {
  const name = document.getElementById('habitName').value.trim();
  if (!name) return;
  await api('save_habit', { name });
  closeModals();
  document.getElementById('habitName').value = '';
  loadHabits();
});

document.getElementById('saveEvent').addEventListener('click', async () => {
  const id = document.getElementById('eventId').value;
  const title = document.getElementById('eventTitle').value.trim();
  const date = document.getElementById('eventDate').value;
  if (!title) return;
  if (id) await api('update_event', { id, title, date });
  else await api('save_event', { title, date });
  closeModals();
  document.getElementById('eventTitle').value = '';
  document.getElementById('eventId').value = '';
  loadEvents();
});

document.getElementById('saveFinance').addEventListener('click', async () => {
  const amount = document.getElementById('financeAmount').value;
  const type = document.getElementById('financeType').value;
  const date = document.getElementById('financeDate').value;
  if (!amount) return;
  await api('save_finance', { amount, type, date });
  closeModals();
  document.getElementById('financeAmount').value = '';
  loadFinance();
});

document.getElementById('saveFinanceBase').addEventListener('click', () => {
  const val = parseFloat(document.getElementById('financeBaseModal').value || '0') || 0;
  localStorage.setItem('financeBase', val.toString());
  loadFinance();
  closeModals();
});

document.getElementById('saveRoutine').addEventListener('click', async () => {
  const id = document.getElementById('routineId').value;
  const time = document.getElementById('routineTime').value;
  const activity = document.getElementById('routineActivity').value.trim();
  if (!activity) return;
  if (id) await api('update_routine_item', { id, time, activity });
  else await api('save_routine_item', { time, activity });
  closeModals();
  document.getElementById('routineActivity').value = '';
  document.getElementById('routineTime').value = '';
  document.getElementById('routineId').value = '';
  loadRoutine();
});

document.getElementById('saveRule').addEventListener('click', async () => {
  const text = document.getElementById('ruleText').value.trim();
  if (!text) return;
  await api('save_rule', { rule_text: text });
  closeModals();
  document.getElementById('ruleText').value = '';
  loadRules();
});

document.getElementById('saveGoal').addEventListener('click', async () => {
  const title = document.getElementById('goalTitle').value.trim();
  if (!title) return;
  await api('save_goal', { title });
  closeModals();
  document.getElementById('goalTitle').value = '';
  loadGoals();
});

document.getElementById('saveGoalMonth').addEventListener('click', async () => {
  const title = document.getElementById('goalMonthTitle').value.trim();
  if (!title) return;
  await api('save_goal_month', { title });
  closeModals();
  document.getElementById('goalMonthTitle').value = '';
  loadMonthlyGoals();
});

/* ══════════════════════════════════════════════
   CALENDAR NAV
══════════════════════════════════════════════ */
document.getElementById('prevMonth').addEventListener('click', () => {
  workoutMonth.setMonth(workoutMonth.getMonth() - 1);
  loadWorkoutsMonth();
});
document.getElementById('nextMonth').addEventListener('click', () => {
  workoutMonth.setMonth(workoutMonth.getMonth() + 1);
  loadWorkoutsMonth();
});

/* ══════════════════════════════════════════════
   INIT
══════════════════════════════════════════════ */
const init = () => {
  setDefaultDates();
  loadWeekRange();
  loadActivities();
  loadHabits();
  loadWorkoutsMonth();
  loadFinance();
  loadEvents();
  loadRoutine();
  loadRules();
  loadGoals();
  loadGoalsDone();
  loadMonthlyGoals();
  loadMessage();
  loadHouseTasks();
};

init();
</script>

<!-- ═══════════════════════════════════════════
     BOTTOM NAV (mobile)
═══════════════════════════════════════════ -->
<nav class="lc-bottom-nav" id="bottomNav" aria-label="Navegação principal">
  <a href="#sec-home" class="lc-nav-item active" data-nav="home">
    <i class="fa-solid fa-house"></i>
    <span class="lc-nav-label">Início</span>
  </a>
  <a href="#sec-atividades" class="lc-nav-item" data-nav="atividades">
    <i class="fa-solid fa-list-check"></i>
    <span class="lc-nav-label">Tarefas</span>
  </a>
  <button class="lc-nav-fab" data-modal="modalActivity" aria-label="Adicionar atividade">
    <i class="fa-solid fa-plus"></i>
  </button>
  <a href="#sec-habitos" class="lc-nav-item" data-nav="habitos">
    <i class="fa-solid fa-fire-flame-curved"></i>
    <span class="lc-nav-label">Hábitos</span>
  </a>
  <a href="#sec-treino" class="lc-nav-item" data-nav="treino">
    <i class="fa-solid fa-dumbbell"></i>
    <span class="lc-nav-label">Treino</span>
  </a>
</nav>

<script>
/* ═══════════════════════════════════════════
   BOTTOM NAV — active state on scroll
═══════════════════════════════════════════ */
(function() {
  const navItems = document.querySelectorAll('.lc-nav-item[data-nav]');
  const sections = [
    { id: 'sec-home',       nav: 'home' },
    { id: 'sec-atividades', nav: 'atividades' },
    { id: 'sec-habitos',    nav: 'habitos' },
    { id: 'sec-treino',     nav: 'treino' },
  ];

  const setActive = (navKey) => {
    navItems.forEach(item => {
      item.classList.toggle('active', item.dataset.nav === navKey);
    });
  };

  // Intersection observer — highlight whichever section is most visible
  if ('IntersectionObserver' in window) {
    const visible = {};
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        visible[entry.target.id] = entry.intersectionRatio;
      });
      let bestId = null, bestRatio = 0;
      sections.forEach(s => {
        const r = visible[s.id] || 0;
        if (r > bestRatio) { bestRatio = r; bestId = s.id; }
      });
      if (bestId) {
        const found = sections.find(s => s.id === bestId);
        if (found) setActive(found.nav);
      }
    }, { threshold: [0, 0.1, 0.3, 0.5] });

    sections.forEach(s => {
      const el = document.getElementById(s.id);
      if (el) observer.observe(el);
    });
  }

  // Smooth scroll + haptic feedback on nav tap
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      if (navigator.vibrate) navigator.vibrate(8);
    });
  });

  // FAB haptic
  const fab = document.querySelector('.lc-nav-fab');
  if (fab) {
    fab.addEventListener('click', () => {
      if (navigator.vibrate) navigator.vibrate(12);
    });
  }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>