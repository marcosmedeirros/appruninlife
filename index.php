<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Sao_Paulo');

$user_id = 1;

function ensureHabitRemovalsTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS habit_removals (\n        habit_id INT NOT NULL PRIMARY KEY,\n        removed_from DATE NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        KEY idx_removed_from (removed_from)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function ensureMonthlyRulesTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS monthly_rules (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        user_id INT DEFAULT 1,\n        rule_text TEXT NOT NULL,\n        is_active TINYINT DEFAULT 1,\n        created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
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
        ensureMonthlyRulesTable($pdo);

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

        if ($action === 'get_workouts_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND workout_date BETWEEN ? AND ? ORDER BY workout_date DESC, id DESC");
            $stmt->execute([$user_id, $start, $end]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'get_workouts_month') {
            $month = $_GET['month'] ?? date('Y-m');
            $monthStart = $month . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND workout_date BETWEEN ? AND ? ORDER BY workout_date ASC, id DESC");
            $stmt->execute([$user_id, $monthStart, $monthEnd]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_workout') {
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, workout_date, done) VALUES (?, ?, ?, 0)");
            $stmt->execute([$user_id, $data['name'] ?? '', $data['date'] ?? date('Y-m-d')]);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_workout_day') {
            $date = $data['date'] ?? date('Y-m-d');
            $name = $data['name'] ?? 'Treino';
            $check = $pdo->prepare("SELECT id, done FROM workouts WHERE user_id = ? AND workout_date = ? ORDER BY id DESC LIMIT 1");
            $check->execute([$user_id, $date]);
            $row = $check->fetch();
            if ($row) {
                $stmt = $pdo->prepare("UPDATE workouts SET done = 1 - done WHERE id = ? AND user_id = ?");
                $stmt->execute([$row['id'], $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, workout_date, done) VALUES (?, ?, ?, 1)");
                $stmt->execute([$user_id, $name, $date]);
            }
            json_response(['success' => true]);
        }

        if ($action === 'toggle_workout') {
            $stmt = $pdo->prepare("UPDATE workouts SET done = 1 - done WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
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
            $stmt->execute([
                $user_id,
                $data['title'] ?? 'Corrida',
                $data['date'] ?? date('Y-m-d'),
                $data['distance'] ?? 0,
                $data['notes'] ?? ''
            ]);
            json_response(['success' => true]);
        }

        if ($action === 'update_run') {
            $stmt = $pdo->prepare("UPDATE runs SET run_date = ?, distance_km = ?, notes = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([
                $data['date'] ?? date('Y-m-d'),
                $data['distance'] ?? 0,
                $data['notes'] ?? '',
                $data['id'] ?? 0,
                $user_id
            ]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_run') {
            $stmt = $pdo->prepare("DELETE FROM runs WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_daily_photo') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $pdo->prepare("SELECT id, user_id, photo_date, image_path, created_at FROM board_photos WHERE user_id = ? AND photo_date = ? LIMIT 1");
            $stmt->execute([$user_id, $date]);
            json_response($stmt->fetch() ?: null);
        }

        if ($action === 'get_photos') {
            $stmt = $pdo->prepare("SELECT photo_date, image_path FROM board_photos WHERE user_id = ? ORDER BY photo_date DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_daily_photo') {
            $date = $_POST['date'] ?? date('Y-m-d');
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                json_response(['error' => 'Arquivo de foto inválido']);
            }

            $uploadDir = __DIR__ . '/uploads/board';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $originalName = $_FILES['photo']['name'] ?? 'photo';
            $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg';
            $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            $fileName = 'photo_' . $user_id . '_' . str_replace('-', '', $date) . '_' . time() . '.' . $safeExt;
            $targetPath = $uploadDir . '/' . $fileName;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                json_response(['error' => 'Falha ao salvar a foto']);
            }

            $imagePath = '/uploads/board/' . $fileName;
            $check = $pdo->prepare("SELECT id FROM board_photos WHERE user_id = ? AND photo_date = ? LIMIT 1");
            $check->execute([$user_id, $date]);
            $existingId = $check->fetchColumn();
            if ($existingId) {
                $stmt = $pdo->prepare("UPDATE board_photos SET image_path = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$imagePath, $existingId, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO board_photos (user_id, photo_date, image_path, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $date, $imagePath]);
            }
            json_response(['success' => true]);
        }

        if ($action === 'delete_daily_photo') {
            $date = $data['date'] ?? date('Y-m-d');
            $stmt = $pdo->prepare("SELECT id, image_path FROM board_photos WHERE user_id = ? AND photo_date = ? LIMIT 1");
            $stmt->execute([$user_id, $date]);
            $row = $stmt->fetch();
            if ($row) {
                $pdo->prepare("DELETE FROM board_photos WHERE id = ? AND user_id = ?")->execute([$row['id'], $user_id]);
                $filePath = __DIR__ . '/' . ltrim($row['image_path'], '/');
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            json_response(['success' => true]);
        }

        if ($action === 'get_daily_message') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $file = __DIR__ . '/mensagens_365.json';

            if (!file_exists($file)) {
                json_response(['error' => 'Arquivo mensagens_365.json não encontrado']);
            }

            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) {
                json_response(['error' => 'Formato inválido do JSON de mensagens']);
            }

            $text = null;
            $matchedDate = null;
            $dayOfYear = (int)date('z', strtotime($date));

            foreach ($json as $item) {
                $itemDate = $item['date'] ?? ($item['dia'] ?? null);
                $itemText = $item['texto'] ?? ($item['mensagem'] ?? ($item['message'] ?? null));
                if ($itemDate && $itemText && $itemDate === $date) {
                    $text = $itemText;
                    $matchedDate = $itemDate;
                    break;
                }
            }

            if ($text === null && isset($json[$dayOfYear])) {
                $item = $json[$dayOfYear];
                $text = $item['texto'] ?? ($item['mensagem'] ?? ($item['message'] ?? (is_string($item) ? $item : null)));
                $matchedDate = $item['date'] ?? ($item['dia'] ?? null);
            }

            if ($text === null) {
                json_response(['error' => 'Mensagem não encontrada para a data informada']);
            }

            json_response([
                'date' => $date,
                'matched_date' => $matchedDate,
                'text' => $text
            ]);
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
                $amount = (float)$row['amount'];
                if ($row['type'] === 'income') {
                    $income += $amount;
                } else {
                    $expense += $amount;
                }
            }
            json_response(['income' => $income, 'expense' => $expense, 'items' => $rows]);
        }

        if ($action === 'get_events_week') {
            [$start, $end] = getWeekRange();
            $startDateTime = $start . ' 00:00:00';
            $endDateTime = $end . ' 23:59:59';
            $stmt = $pdo->prepare("SELECT id, title, start_date, description FROM events WHERE user_id = ? AND start_date BETWEEN ? AND ? ORDER BY start_date ASC");
            $stmt->execute([$user_id, $startDateTime, $endDateTime]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_event') {
            $date = $data['date'] ?? date('Y-m-d');
            $startDate = $date . ' 09:00:00';
            $stmt = $pdo->prepare("INSERT INTO events (user_id, title, start_date, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $data['title'] ?? '', $startDate, $data['description'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'update_event') {
            $date = $data['date'] ?? date('Y-m-d');
            $startDate = $date . ' 09:00:00';
            $stmt = $pdo->prepare("UPDATE events SET title = ?, start_date = ?, description = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['title'] ?? '', $startDate, $data['description'] ?? '', $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_event') {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_rules') {
            $stmt = $pdo->prepare("SELECT id, rule_text FROM life_rules WHERE user_id = ? ORDER BY id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_rule') {
            $stmt = $pdo->prepare("INSERT INTO life_rules (user_id, rule_text) VALUES (?, ?)");
            $stmt->execute([$user_id, $data['rule_text'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'update_rule') {
            $stmt = $pdo->prepare("UPDATE life_rules SET rule_text = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['rule_text'] ?? '', $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_rule') {
            $stmt = $pdo->prepare("DELETE FROM life_rules WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_routine_items') {
            $stmt = $pdo->prepare("SELECT * FROM routine_items WHERE user_id = ? ORDER BY routine_time ASC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_routine_item') {
            $stmt = $pdo->prepare("INSERT INTO routine_items (user_id, routine_time, activity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $data['time'] ?? '08:00', $data['activity'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'delete_routine_item') {
            $stmt = $pdo->prepare("DELETE FROM routine_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'save_goal') {
            $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, difficulty, status, goal_type) VALUES (?, ?, 'media', 0, 'geral')");
            $stmt->execute([$user_id, $data['title'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals') {
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND goal_type = 'geral' ORDER BY status ASC, id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'toggle_goal') {
            $stmt = $pdo->prepare("UPDATE goals SET status = 1 - status WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_goal') {
            $stmt = $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals_month') {
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND goal_type = 'mensal' ORDER BY status ASC, id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_goal_month') {
            $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, difficulty, status, goal_type) VALUES (?, ?, 'media', 0, 'mensal')");
            $stmt->execute([$user_id, $data['title'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'update_goal_month') {
            $stmt = $pdo->prepare("UPDATE goals SET title = ? WHERE id = ? AND user_id = ? AND goal_type = 'mensal'");
            $stmt->execute([$data['title'] ?? '', $data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_goal_month') {
            $stmt = $pdo->prepare("UPDATE goals SET status = 1 - status WHERE id = ? AND user_id = ? AND goal_type = 'mensal'");
            $stmt->execute([$data['id'] ?? 0, $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'delete_goal_month') {
            $stmt = $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ? AND goal_type = 'mensal'");
            $stmt->execute([$data['id'] ?? 0, $user_id]);
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

$page_title = 'Planner - Rotina & Metas';
include __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        color-scheme: dark;
        --bg: #000000;
        --surface: #000000;
        --surface-2: #000000;
        --text: #e9eef5;
        --muted: #9aa4b2;
        --accent: #D8B4FE;
        --accent-2: #22d3ee;
        --success: #22c55e;
        --danger: #ef4444;
        --shadow: 0 20px 45px rgba(4, 8, 20, 0.5);
        --border: 1px solid rgba(148, 163, 184, 0.18);
        --radius: 20px;
    }
    * { box-sizing: border-box; }
    body {
        background: radial-gradient(circle at top, rgba(124, 58, 237, 0.12), transparent 45%), var(--bg);
        color: var(--text);
        font-family: 'Montserrat', sans-serif;
        margin: 0;
    }
    .app {
        max-width: 1320px;
        margin: 0 auto;
        padding: 30px 22px 80px;
    }
    .app-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        margin-bottom: 26px;
        padding: 22px 24px;
        border-radius: calc(var(--radius) + 4px);
        background: #000000;
        border: var(--border);
        box-shadow: var(--shadow);
    }
    .app-title h1 {
        margin: 0;
        font-size: 2.2rem;
        letter-spacing: 0.4px;
    }
    .app-title p {
        margin: 8px 0 0;
        color: var(--muted);
    }
    .chip {
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(124, 58, 237, 0.2);
        color: #d8b4fe;
        font-weight: 600;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
    }
    .app-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
    .app-actions-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .week-pill {
        padding: 8px 14px;
        border-radius: 999px;
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--text);
        font-size: 0.85rem;
    }
    .tag {
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        font-size: 0.8rem;
        color: #ffffff;
    }
    .weekday-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        padding: 4px 10px;
    }
    .section {
        margin-top: 26px;
    }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
    }
    .section-header h2 {
        font-size: 1.25rem;
        margin: 0;
        font-weight: 600;
    }
    .section-header span {
        color: var(--muted);
        font-size: 0.85rem;
    }
    .grid {
        display: grid;
        gap: 18px;
    }
    .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    .grid-3 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    .card {
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: var(--radius);
        padding: 18px;
        box-shadow: var(--shadow);
        min-height: 0;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .card-header h3 {
        font-size: 1.05rem;
        margin: 0;
        font-weight: 600;
        color: var(--accent);
    }
    .btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: #ffffff;
        padding: 8px 14px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all .2s ease;
        cursor: pointer;
    }
    .btn:hover { background: rgba(255, 255, 255, 0.08); color: #ffffff; }
    .btn-solid { background: #ffffff; color: #000000; border: none; }
    .list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .list-item {
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .list-item small { color: var(--muted); }
    .muted { color: var(--muted); }
    .divider { height: 1px; background: rgba(255, 255, 255, 0.12); margin: 12px 0; }
    .modal {
        position: fixed;
        inset: 0;
        background: rgba(7, 10, 18, 0.75);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 20px;
        backdrop-filter: blur(8px);
    }
    .modal.active { display: flex; }
    .modal-content {
        width: min(520px, 95vw);
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .modal-header h4 { font-weight: 600; margin: 0; }
    .modal-close { background: transparent; border: none; color: var(--muted); font-size: 1.2rem; }
    .input {
        width: 100%;
        background: #000000;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 10px 12px;
        border-radius: 12px;
        margin-bottom: 10px;
    }
    .habit-grid { overflow: visible; padding-bottom: 6px; }
    .habit-table { width: 100%; border-collapse: collapse; min-width: 0; table-layout: fixed; }
    .habit-table th, .habit-table td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        padding: 8px;
        text-align: center;
        font-size: 0.75rem;
    }
    .habit-table th:first-child, .habit-table td:first-child {
        text-align: left;
        font-weight: 600;
        color: var(--text);
        width: 180px;
        min-width: 180px;
        max-width: 240px;
        white-space: normal;
        word-break: break-word;
    }
    .check {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .check.active { background: rgba(34, 197, 94, 0.35); color: #dcfce7; border-color: rgba(34, 197, 94, 0.6); }
    .photo-box {
        width: 100%;
        height: 210px;
        border-radius: 16px;
        background: #000000;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    .photo-box img { width: 100%; height: 100%; object-fit: cover; }
    .photo-carousel {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: center;
    }
    .photo-carousel-frame {
        width: 100%;
        height: 320px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        overflow: hidden;
        background: #000000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .photo-carousel-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-carousel-nav {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .photo-date {
        color: var(--text);
        font-size: 0.95rem;
        font-weight: 600;
    }
    .card-compact { min-height: 120px; }
    .metric { font-size: 1.6rem; font-weight: 600; margin-top: 6px; color: var(--accent); }
    .finance-metric { color: #ffffff; }
    .input-sm { padding: 8px 10px; font-size: 0.85rem; }
    .activity-item { align-items: center; }
    .activity-done .activity-title { text-decoration: line-through; color: var(--muted); }
    .activity-check { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .activity-check input { display: none; }
    .activity-check span {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #0b0f14;
        background: transparent;
        transition: all 0.2s ease;
    }
    .activity-check input:checked + span {
        background: rgba(34, 197, 94, 0.8);
        border-color: rgba(34, 197, 94, 0.8);
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
    }
    .activity-check input:checked + span::after {
        content: '✓';
        color: #0b0f14;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .list-actions { display: inline-flex; gap: 6px; }
    .goals-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .goal-item.done .goal-title { text-decoration: line-through; color: var(--muted); }
    .goal-check { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .goal-check input { display: none; }
    .goal-check span {
        width: 18px;
        height: 18px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #000000;
        background: transparent;
        transition: all 0.2s ease;
    }
    .goal-check input:checked + span {
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(255, 255, 255, 0.9);
    }
    .goal-check input:checked + span::after {
        content: '✓';
        color: #000000;
        font-size: 0.7rem;
        font-weight: 700;
    }
    @media (max-width: 1024px) {
        .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 768px) {
        .app { padding: 20px 14px 60px; }
        .app-header { padding: 18px; }
        .grid-2, .grid-3 { grid-template-columns: 1fr; }
        .goals-grid { grid-template-columns: 1fr; }
        .week-pill { width: 100%; text-align: center; }
        .app-actions { width: 100%; align-items: stretch; }
        .app-actions-row { width: 100%; justify-content: flex-start; }
        .card-header { flex-wrap: wrap; }
        .photo-carousel-frame { height: 240px; }
        .habit-table th, .habit-table td { padding: 6px; font-size: 0.7rem; }
        .habit-table th:first-child, .habit-table td:first-child { width: 140px; min-width: 140px; }
        #routineGrid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .app-title h1 { font-size: 1.5rem; }
        .chip { font-size: 0.65rem; }
        .btn { width: 100%; justify-content: center; }
        .card-header > div, .card-header > button, .card-header > span { width: 100%; }
        .list-item { flex-direction: column; align-items: flex-start; }
        .list-actions { width: 100%; justify-content: flex-end; }
    }
    .icon-btn.subtle {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }
    .icon-btn.subtle:hover { color: #ffffff; border-color: rgba(255, 255, 255, 0.4); }
    .calendar {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .calendar-weekdays {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 6px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--muted);
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 6px;
    }
    .calendar-cell {
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        min-height: 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        cursor: pointer;
        color: #ffffff;
        transition: all 0.2s ease;
    }
    .calendar-cell:hover { transform: translateY(-2px); border-color: rgba(255, 255, 255, 0.4); color: #ffffff; }
    .calendar-cell.is-empty {
        background: transparent;
        border: 1px dashed rgba(255, 255, 255, 0.12);
        cursor: default;
        box-shadow: none;
    }
    .calendar-cell.is-done {
        background: rgba(34, 197, 94, 0.2);
        border-color: rgba(34, 197, 94, 0.5);
        color: #dcfce7;
    }
    .calendar-cell.is-today {
        outline: 2px solid rgba(255, 255, 255, 0.35);
    }
    .calendar-cell .day { font-size: 0.9rem; font-weight: 600; }
    .calendar-cell .mark { font-size: 0.85rem; color: var(--success); }
    .calendar-nav {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
        cursor: pointer;
    }
    .month-label { font-weight: 600; font-size: 0.9rem; text-transform: capitalize; }
    .calendar-legend {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 8px;
    }
    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .dot-success { background: var(--success); }
    .dot-muted { background: rgba(148, 163, 184, 0.4); }
    @media (max-width: 768px) {
        .app-header { align-items: flex-start; }
        .app-title h1 { font-size: 1.7rem; }
        .app-actions { align-items: flex-start; }
        .app-actions-row { justify-content: flex-start; }
    }
</style>

<main class="app">
    <header class="app-header">
        <div class="app-title">
            <span class="chip">Life Planner</span>
            <h1>Rotina semanal & metas</h1>
            <p>Organize sua semana, acompanhe hábitos e marque treinos do mês.</p>
        </div>
        <div class="app-actions">
            <div class="week-pill" id="weekRange">Semana atual</div>
            <div class="app-actions-row">
                <button class="btn btn-solid" data-modal="modalActivity">Nova atividade</button>
                <button class="btn" data-modal="modalGoal">Nova meta</button>
            </div>
        </div>
    </header>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3>Atividades da semana</h3>
                <button class="btn" data-modal="modalActivity">Adicionar</button>
            </div>
            <div class="list" id="activitiesList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Eventos</h3>
                <button class="btn" data-modal="modalEvent">Adicionar</button>
            </div>
            <div class="list" id="eventsList"></div>
        </div>
    </div>

    <div class="card section">
        <div class="card-header">
            <h3>Rotina do dia</h3>
            <button class="btn" data-modal="modalRoutine">Cadastrar</button>
        </div>
        <div class="grid grid-2" id="routineGrid">
            <div class="list" id="routineListLeft"></div>
            <div class="list" id="routineListRight"></div>
        </div>
    </div>

    <div class="grid grid-3 section">
        <div class="card card-compact">
            <div class="card-header">
                <h3>Entrada</h3>
                <button class="btn" data-modal="modalFinance" data-finance-type="entrada">Lançar</button>
            </div>
            <div class="metric finance-metric">R$ <span id="financeIncome">0</span></div>
        </div>

        <div class="card card-compact">
            <div class="card-header">
                <h3>Saída</h3>
                <button class="btn" data-modal="modalFinance" data-finance-type="saida">Lançar</button>
            </div>
            <div class="metric finance-metric">R$ <span id="financeExpense">0</span></div>
        </div>

        <div class="card card-compact">
            <div class="card-header">
                <h3>Total no banco</h3>
                <button class="btn" data-modal="modalFinanceBase">Definir base</button>
            </div>
            <div class="metric finance-metric">R$ <span id="financeTotal">0</span></div>
        </div>
    </div>

    <div class="card section">
        <div class="card-header">
            <h3>Habit Tracker</h3>
            <button class="btn" data-modal="modalHabit">Adicionar</button>
        </div>
        <div class="habit-grid">
            <table class="habit-table" id="habitTable"></table>
        </div>
    </div>

    <div class="grid grid-3 section">
        <div class="card">
            <div class="card-header">
                <h3>Metas</h3>
                <div style="display:flex; gap:8px;">
                    <button class="btn" data-modal="modalGoal">Cadastrar</button>
                    <button class="btn" data-modal="modalGoalsView">Ver metas</button>
                </div>
            </div>
            <p class="muted" id="goalsWeekDone">Sem metas concluídas ainda.</p>
            <div class="list" id="goalsDoneList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Metas do mes</h3>
                <button class="btn" data-modal="modalGoalMonth">Adicionar</button>
            </div>
            <div class="list" id="monthlyGoalsList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Treino do mês</h3>
                    <span class="muted">Marque o dia em que treinou</span>
                </div>
                <div class="calendar-nav">
                    <button class="icon-btn" id="prevMonth" aria-label="Mês anterior">‹</button>
                    <div class="month-label" id="workoutMonthLabel"></div>
                    <button class="icon-btn" id="nextMonth" aria-label="Próximo mês">›</button>
                </div>
            </div>
            <div class="calendar">
                <div class="calendar-weekdays">
                    <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span><span>Dom</span>
                </div>
                <div class="calendar-grid" id="workoutCalendar"></div>
            </div>
            <div class="calendar-legend">
                <span><span class="dot dot-success"></span> Treinou</span>
                <span><span class="dot dot-muted"></span> Sem treino</span>
            </div>
        </div>
    </div>

    <div class="grid grid-2 section">
        <div class="card">
            <div class="card-header">
                <h3>Regras de vida</h3>
            </div>
            <div class="list" id="rulesList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Mensagem do dia</h3>
                <span class="tag" id="messageDate">Hoje</span>
            </div>
            <p id="dailyMessage" class="muted"></p>
        </div>
    </div>
</main>

<div class="modal" id="modalActivity">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova atividade da semana</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="activityTitle" placeholder="O que precisa fazer?">
        <select class="input" id="activityWeekday">
            <option value="1">Segunda-feira</option>
            <option value="2">Terça-feira</option>
            <option value="3">Quarta-feira</option>
            <option value="4">Quinta-feira</option>
            <option value="5">Sexta-feira</option>
            <option value="6">Sábado</option>
            <option value="7">Domingo</option>
        </select>
        <button class="btn btn-solid" id="saveActivity">Salvar</button>
    </div>
</div>

<div class="modal" id="modalHabit">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo hábito</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="habitName" placeholder="Nome do hábito">
        <button class="btn btn-solid" id="saveHabit">Salvar</button>
    </div>
</div>

<div class="modal" id="modalWorkout">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo check-in de treino</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="workoutName" placeholder="Treino (ex: peito + cardio)">
        <input class="input" id="workoutDate" type="date">
        <button class="btn btn-solid" id="saveWorkout">Salvar</button>
    </div>
</div>

<div class="modal" id="modalRun">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo registro de corrida</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input type="hidden" id="runId">
        <input class="input" id="runDate" type="date">
        <input class="input" id="runDistance" type="number" step="0.1" placeholder="Distância (km)">
        <input class="input" id="runNotes" placeholder="Observações">
        <button class="btn btn-solid" id="saveRun">Salvar</button>
    </div>
</div>

<div class="modal" id="modalPhoto">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Foto do dia</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="photoDate" type="date">
        <input class="input" id="photoFile" type="file" accept="image/*">
        <button class="btn btn-solid" id="savePhoto">Salvar</button>
    </div>
</div>

<div class="modal" id="modalPhotoGallery">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Fotos do dia</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <div class="photo-carousel">
            <div class="photo-carousel-frame" id="photoCarouselFrame">Sem fotos</div>
            <div class="photo-carousel-nav">
                <button class="icon-btn" id="photoPrev" aria-label="Anterior">‹</button>
                <div class="photo-date" id="photoCarouselDate"></div>
                <button class="icon-btn" id="photoNext" aria-label="Próximo">›</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalEvent">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Evento</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input type="hidden" id="eventId">
        <input class="input" id="eventTitle" placeholder="Nome do evento">
        <input class="input" id="eventDate" type="date">
        <button class="btn btn-solid" id="saveEvent">Salvar</button>
    </div>
</div>

<div class="modal" id="modalFinance">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo lançamento financeiro</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="financeAmount" type="number" step="0.01" placeholder="Valor">
        <input type="hidden" id="financeType" value="entrada">
        <input class="input" id="financeDate" type="date">
        <button class="btn btn-solid" id="saveFinance">Salvar</button>
    </div>
</div>

<div class="modal" id="modalFinanceBase">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Definir dinheiro base</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="financeBaseModal" type="number" step="0.01" placeholder="0,00">
        <button class="btn btn-solid" id="saveFinanceBase">Salvar</button>
    </div>
</div>

<div class="modal" id="modalRoutine">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova rotina</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="routineTime" type="time">
        <input class="input" id="routineActivity" placeholder="Atividade">
        <button class="btn btn-solid" id="saveRoutine">Salvar</button>
    </div>
</div>

<div class="modal" id="modalRule">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova regra de vida</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input type="hidden" id="ruleId">
        <input class="input" id="ruleText" placeholder="Escreva a regra">
        <button class="btn btn-solid" id="saveRule">Salvar</button>
    </div>
</div>

<div class="modal" id="modalGoal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova meta</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="goalTitle" placeholder="Título da meta">
        <button class="btn btn-solid" id="saveGoal">Salvar</button>
    </div>
</div>

<div class="modal" id="modalGoalsView">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Metas cadastradas</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <div class="list goals-grid" id="goalsList"></div>
    </div>
</div>

<div class="modal" id="modalGoalMonth">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Meta do mes</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input type="hidden" id="goalMonthId">
        <input class="input" id="goalMonthTitle" placeholder="Titulo da meta do mes">
        <button class="btn btn-solid" id="saveGoalMonth">Salvar</button>
    </div>
</div>

<script>
    const api = (action, payload = {}, method = 'POST') => {
        const opts = { method };
        if (method !== 'GET') {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(payload);
        }
        return fetch(`?api=${action}`, opts).then(r => r.json());
    };

    const openModal = (id) => document.getElementById(id)?.classList.add('active');
    const closeModals = () => document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));

    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-modal]')) {
            if (e.target.dataset.modal === 'modalFinance') {
                const financeType = document.getElementById('financeType');
                if (financeType && e.target.dataset.financeType) {
                    financeType.value = e.target.dataset.financeType;
                }
            }
            if (e.target.dataset.modal === 'modalEvent') {
                const eventId = document.getElementById('eventId');
                const eventTitle = document.getElementById('eventTitle');
                const eventDate = document.getElementById('eventDate');
                if (eventId) eventId.value = '';
                if (eventTitle) eventTitle.value = '';
                if (eventDate) eventDate.value = new Date().toISOString().slice(0, 10);
            }
            if (e.target.dataset.modal === 'modalGoalMonth') {
                const goalMonthId = document.getElementById('goalMonthId');
                const goalMonthTitle = document.getElementById('goalMonthTitle');
                if (goalMonthId) goalMonthId.value = '';
                if (goalMonthTitle) goalMonthTitle.value = '';
            }
            if (e.target.dataset.modal === 'modalRule') {
                const ruleId = document.getElementById('ruleId');
                const ruleText = document.getElementById('ruleText');
                if (ruleId) ruleId.value = '';
                if (ruleText) ruleText.value = '';
            }
            if (e.target.dataset.modal === 'modalRun') {
                const runId = document.getElementById('runId');
                if (runId) runId.value = '';
            }
            if (e.target.dataset.modal === 'modalFinanceBase') {
                const baseModal = document.getElementById('financeBaseModal');
                if (baseModal) {
                    const baseValue = parseFloat(localStorage.getItem('financeBase') || '0') || 0;
                    baseModal.value = baseValue.toFixed(2);
                }
            }
            if (e.target.dataset.modal === 'modalPhotoGallery') {
                loadPhotoGallery();
            }
            openModal(e.target.dataset.modal);
        }
        if (e.target.matches('[data-close]')) {
            closeModals();
        }
        if (e.target.classList.contains('modal')) {
            closeModals();
        }
    });

    const formatDate = (dateStr) => {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: '2-digit' });
    };

    const formatShortDate = (dateStr) => {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    };

    const resolveImageUrl = (path) => {
        if (!path) return '';
        const trimmed = path.trim();
        if (trimmed.startsWith('data:')) return trimmed;
        if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) return trimmed;
        if (trimmed.startsWith('/uploads/')) return trimmed;
        const base = (BASE_PATH || '').replace(/\/+$/, '');
        if (trimmed.startsWith('/')) return `${base}${trimmed}`;
        return `${base}/${trimmed}`;
    };

    const formatWeekdayLabel = (dateStr) => {
        const d = new Date(dateStr + 'T00:00:00');
        const label = d.toLocaleDateString('pt-BR', { weekday: 'long' });
        return label.charAt(0).toUpperCase() + label.slice(1);
    };

    const getTodayWeekdayLabel = () => {
        const d = new Date();
        const label = d.toLocaleDateString('pt-BR', { weekday: 'long' });
        return label.charAt(0).toUpperCase() + label.slice(1);
    };

    const setDefaultDates = () => {
        const today = new Date().toISOString().slice(0, 10);
        ['workoutDate','runDate','photoDate','financeDate','eventDate'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = today;
        });
        const weekdaySelect = document.getElementById('activityWeekday');
        if (weekdaySelect) {
            const jsDay = new Date().getDay();
            const weekday = jsDay === 0 ? 7 : jsDay;
            weekdaySelect.value = String(weekday);
        }
    };

    const getWeekDates = () => {
        const now = new Date();
        const day = now.getDay();
        const diffToMonday = day === 0 ? -6 : 1 - day;
        const monday = new Date(now);
        monday.setDate(now.getDate() + diffToMonday);
        return monday;
    };

    const getDateForWeekday = (weekdayNumber) => {
        const monday = getWeekDates();
        const target = new Date(monday);
        target.setDate(monday.getDate() + (weekdayNumber - 1));
        return target.toISOString().slice(0, 10);
    };

    const loadActivities = async () => {
        const list = await api('get_week_activities', {}, 'GET');
        const container = document.getElementById('activitiesList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nada por aqui. Adicione uma atividade.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item activity-item' + (item.status == 1 ? ' activity-done' : '');
            row.innerHTML = `
                <div>
                    <strong class="activity-title">${item.title}</strong><br>
                    <span class="tag weekday-tag">${formatWeekdayLabel(item.day_date)}</span>
                </div>
                <div class="list-actions">
                    <label class="activity-check">
                        <input type="checkbox" data-id="${item.id}" data-action="toggle-activity" ${item.status == 1 ? 'checked' : ''}>
                        <span></span>
                    </label>
                    <button class="icon-btn subtle" data-action="delete-activity" data-id="${item.id}" aria-label="Apagar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        });
    };

    const loadHabits = async () => {
        const month = new Date().toISOString().slice(0, 7);
        const habits = await fetch(`?api=get_habits&month=${month}`).then(r => r.json());
        const table = document.getElementById('habitTable');
        const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
        let header = '<tr><th>Hábito</th>';
        for (let d = 1; d <= daysInMonth; d++) header += `<th>${d}</th>`;
        header += '</tr>';
        let body = '';
        habits.forEach(habit => {
            const checks = JSON.parse(habit.checked_dates || '[]');
            body += `<tr><td>${habit.name}</td>`;
            for (let d = 1; d <= daysInMonth; d++) {
                const date = `${month}-${String(d).padStart(2, '0')}`;
                const isActive = checks.includes(date);
                body += `<td><span class="check ${isActive ? 'active' : ''}" data-habit="${habit.id}" data-date="${date}">${isActive ? '✓' : ''}</span></td>`;
            }
            body += '</tr>';
        });
        table.innerHTML = header + body;
    };

    let workoutMonth = new Date();

    const getMonthKey = (date) => date.toISOString().slice(0, 7);

    const formatMonthLabel = (date) => {
        const label = date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
        return label.charAt(0).toUpperCase() + label.slice(1);
    };

    const renderWorkoutCalendar = (workouts, current) => {
        const calendar = document.getElementById('workoutCalendar');
        const label = document.getElementById('workoutMonthLabel');
        if (!calendar || !label) return;

        label.textContent = formatMonthLabel(current);

        const year = current.getFullYear();
        const month = current.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startWeekday = (firstDay.getDay() + 6) % 7;
        const totalCells = Math.ceil((startWeekday + lastDay.getDate()) / 7) * 7;

        const workoutsMap = new Map();
        workouts.forEach(item => {
            workoutsMap.set(item.workout_date, item);
        });

        const todayKey = new Date().toISOString().slice(0, 10);
        const monthKey = getMonthKey(current);
        calendar.innerHTML = '';

        for (let i = 0; i < totalCells; i++) {
            const dayNumber = i - startWeekday + 1;
            if (dayNumber < 1 || dayNumber > lastDay.getDate()) {
                const empty = document.createElement('div');
                empty.className = 'calendar-cell is-empty';
                calendar.appendChild(empty);
                continue;
            }

            const dateStr = `${monthKey}-${String(dayNumber).padStart(2, '0')}`;
            const workout = workoutsMap.get(dateStr);
            const isDone = workout && parseInt(workout.done, 10) === 1;

            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'calendar-cell' + (isDone ? ' is-done' : '') + (dateStr === todayKey ? ' is-today' : '');
            cell.dataset.date = dateStr;
            cell.innerHTML = `<span class="day">${dayNumber}</span><span class="mark">${isDone ? '✓' : ''}</span>`;
            calendar.appendChild(cell);
        }
    };

    const loadWorkoutsMonth = async () => {
        const monthKey = getMonthKey(workoutMonth);
        const list = await fetch(`?api=get_workouts_month&month=${monthKey}`).then(r => r.json());
        renderWorkoutCalendar(list, workoutMonth);
    };

    const loadRuns = async () => {
        const list = await api('get_runs_week', {}, 'GET');
        const container = document.getElementById('runList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nenhuma corrida registrada.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${formatShortDate(item.run_date)} - ${item.distance_km} km</strong><br>
                    <small>${item.notes || 'Sem observações'}</small>
                </div>
                <div class="list-actions">
                    <button class="icon-btn subtle" data-action="edit-run" data-id="${item.id}" data-date="${item.run_date}" data-distance="${item.distance_km}" data-notes="${item.notes || ''}" aria-label="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="icon-btn subtle" data-action="delete-run" data-id="${item.id}" aria-label="Apagar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        });
    };

    const loadPhoto = async () => {
        const data = await api('get_daily_photo', {}, 'GET');
        const box = document.getElementById('photoBox');
        if (data && data.image_path) {
            const src = resolveImageUrl(data.image_path);
            box.innerHTML = `<img src="${src}" alt="Foto do dia">`;
        } else {
            box.innerHTML = 'Sem foto hoje';
        }
    };

    let photoGallery = [];
    let photoIndex = 0;

    const renderPhotoCarousel = () => {
        const frame = document.getElementById('photoCarouselFrame');
        const dateEl = document.getElementById('photoCarouselDate');
        if (!frame || !dateEl) return;
        if (!photoGallery.length) {
            frame.textContent = 'Sem fotos';
            dateEl.textContent = '';
            return;
        }
        const item = photoGallery[photoIndex];
        const src = resolveImageUrl(item.image_path);
        frame.innerHTML = `<img src="${src}" alt="Foto do dia">`;
        dateEl.textContent = formatShortDate(item.photo_date);
    };

    const loadPhotoGallery = async () => {
        photoGallery = await api('get_photos', {}, 'GET');
        photoIndex = 0;
        renderPhotoCarousel();
    };

    const loadMessage = async () => {
        const data = await api('get_daily_message', {}, 'GET');
        document.getElementById('dailyMessage').textContent = data.text || 'Sem mensagem disponível.';
        document.getElementById('messageDate').textContent = new Date().toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' });
    };

    const loadFinance = async () => {
        const data = await api('get_finance_week', {}, 'GET');
        document.getElementById('financeIncome').textContent = data.income.toFixed(2);
        document.getElementById('financeExpense').textContent = data.expense.toFixed(2);
        const baseValue = parseFloat(localStorage.getItem('financeBase') || '0') || 0;
        const baseModal = document.getElementById('financeBaseModal');
        if (baseModal && document.activeElement !== baseModal) {
            baseModal.value = baseValue.toFixed(2);
        }
        const total = baseValue + data.income - data.expense;
        document.getElementById('financeTotal').textContent = total.toFixed(2);
    };

    const loadEvents = async () => {
        const list = await api('get_events_week', {}, 'GET');
        const container = document.getElementById('eventsList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Sem eventos na semana.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.title}</strong><br>
                    <span class="tag weekday-tag">${formatWeekdayLabel(item.start_date.slice(0,10))}</span>
                </div>
                <div class="list-actions">
                    <button class="icon-btn subtle" data-action="edit-event" data-id="${item.id}" data-title="${item.title}" data-date="${item.start_date.slice(0,10)}" aria-label="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="icon-btn subtle" data-action="delete-event" data-id="${item.id}" aria-label="Apagar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        });
    };

    const loadRules = async () => {
        const list = await api('get_rules', {}, 'GET');
        const container = document.getElementById('rulesList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Sem regras cadastradas.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>${item.rule_text}</div>
            `;
            container.appendChild(row);
        });
    };

    const loadRoutine = async () => {
        const list = await api('get_routine_items', {}, 'GET');
        const left = document.getElementById('routineListLeft');
        const right = document.getElementById('routineListRight');
        left.innerHTML = '';
        right.innerHTML = '';
        if (!list.length) {
            left.innerHTML = '<div class="muted">Cadastre sua rotina do dia.</div>';
            return;
        }
        const leftItems = list.slice(0, 5);
        const rightItems = list.slice(5, 10);
        const renderList = (items, container) => {
            items.forEach(item => {
                const row = document.createElement('div');
                row.className = 'list-item';
                row.innerHTML = `
                    <div>
                        <strong>${item.activity}</strong><br>
                        <span class="tag weekday-tag">${getTodayWeekdayLabel()}</span>
                    </div>
                    <button class="icon-btn subtle" data-id="${item.id}" data-action="delete-routine" aria-label="Apagar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                `;
                container.appendChild(row);
            });
        };
        renderList(leftItems, left);
        renderList(rightItems, right);
    };

    const loadGoals = async () => {
        const list = await api('get_goals', {}, 'GET');
        const container = document.getElementById('goalsList');
        container.innerHTML = '';
        const pending = list.filter(item => parseInt(item.status, 10) === 0);
        if (!pending.length) {
            container.innerHTML = '<div class="muted">Nenhuma meta pendente.</div>';
            return;
        }
        pending.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>${item.title}</div>
                <div class="list-actions">
                    <button class="btn" data-id="${item.id}" data-action="toggle-goal">${item.status == 1 ? 'Feita' : 'Concluir'}</button>
                    <button class="icon-btn subtle" data-id="${item.id}" data-action="delete-goal" aria-label="Apagar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        });
    };

    const loadMonthlyGoals = async () => {
        const list = await api('get_goals_month', {}, 'GET');
        const container = document.getElementById('monthlyGoalsList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nenhuma meta do mes.</div>';
            return;
        }
        list.forEach(item => {
            const isDone = parseInt(item.status, 10) === 1;
            const row = document.createElement('div');
            row.className = 'list-item goal-item' + (isDone ? ' done' : '');
            row.innerHTML = `
                <div>
                    <div class="goal-title">${item.title}</div>
                </div>
                <div class="list-actions">
                    <label class="goal-check">
                        <input type="checkbox" data-action="toggle-goal-month" data-id="${item.id}" ${isDone ? 'checked' : ''}>
                        <span></span>
                    </label>
                    <button class="icon-btn subtle" data-action="edit-goal-month" data-id="${item.id}" data-title="${item.title}" aria-label="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="icon-btn subtle" data-action="delete-goal-month" data-id="${item.id}" aria-label="Apagar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        });
    };

    const loadGoalsDone = async () => {
        const list = await api('get_goals_done', {}, 'GET');
        const el = document.getElementById('goalsWeekDone');
        const listEl = document.getElementById('goalsDoneList');
        listEl.innerHTML = '';
        if (!list.length) {
            el.textContent = 'Sem metas concluídas ainda.';
            return;
        }
        el.textContent = `Metas concluídas: ${list.length}`;
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `<div>${item.title}</div>`;
            listEl.appendChild(row);
        });
    };

    document.addEventListener('click', async (e) => {
        if (e.target.matches('.check[data-habit]')) {
            await api('toggle_habit', { id: e.target.dataset.habit, date: e.target.dataset.date });
            loadHabits();
        }
        const workoutCell = e.target.closest('.calendar-cell[data-date]');
        if (workoutCell) {
            await api('toggle_workout_day', { date: workoutCell.dataset.date });
            loadWorkoutsMonth();
        }
        if (e.target.closest('[data-action="edit-event"]')) {
            const btn = e.target.closest('[data-action="edit-event"]');
            document.getElementById('eventId').value = btn.dataset.id;
            document.getElementById('eventTitle').value = btn.dataset.title;
            document.getElementById('eventDate').value = btn.dataset.date;
            openModal('modalEvent');
        }
        if (e.target.closest('[data-action="delete-event"]')) {
            const btn = e.target.closest('[data-action="delete-event"]');
            await api('delete_event', { id: btn.dataset.id });
            loadEvents();
        }
        if (e.target.matches('[data-action="delete-rule"]')) {
            await api('delete_rule', { id: e.target.dataset.id });
            loadRules();
        }
        if (e.target.closest('[data-action="edit-rule"]')) {
            const btn = e.target.closest('[data-action="edit-rule"]');
            const ruleId = document.getElementById('ruleId');
            const ruleText = document.getElementById('ruleText');
            if (ruleId) ruleId.value = btn.dataset.id;
            if (ruleText) ruleText.value = btn.dataset.text || '';
            openModal('modalRule');
        }
        if (e.target.matches('[data-action="delete-routine"]')) {
            await api('delete_routine_item', { id: e.target.dataset.id });
            loadRoutine();
        }
        if (e.target.closest('[data-action="delete-activity"]')) {
            const btn = e.target.closest('[data-action="delete-activity"]');
            await api('delete_activity', { id: btn.dataset.id });
            loadActivities();
        }
        if (e.target.closest('[data-action="edit-run"]')) {
            const btn = e.target.closest('[data-action="edit-run"]');
            document.getElementById('runId').value = btn.dataset.id;
            document.getElementById('runDate').value = btn.dataset.date;
            document.getElementById('runDistance').value = btn.dataset.distance;
            document.getElementById('runNotes').value = btn.dataset.notes;
            openModal('modalRun');
        }
        if (e.target.closest('[data-action="delete-run"]')) {
            const btn = e.target.closest('[data-action="delete-run"]');
            await api('delete_run', { id: btn.dataset.id });
            loadRuns();
        }
        if (e.target.closest('[data-action="delete-goal"]')) {
            const btn = e.target.closest('[data-action="delete-goal"]');
            await api('delete_goal', { id: btn.dataset.id });
            loadGoals();
            loadGoalsDone();
        }
        if (e.target.closest('[data-action="edit-goal-month"]')) {
            const btn = e.target.closest('[data-action="edit-goal-month"]');
            document.getElementById('goalMonthId').value = btn.dataset.id;
            document.getElementById('goalMonthTitle').value = btn.dataset.title;
            openModal('modalGoalMonth');
        }
        if (e.target.closest('[data-action="delete-goal-month"]')) {
            const btn = e.target.closest('[data-action="delete-goal-month"]');
            await api('delete_goal_month', { id: btn.dataset.id });
            loadMonthlyGoals();
        }
        if (e.target.closest('[data-action="toggle-goal"]')) {
            const btn = e.target.closest('[data-action="toggle-goal"]');
            await api('toggle_goal', { id: btn.dataset.id });
            loadGoals();
            loadGoalsDone();
        }
    });

    document.addEventListener('change', async (e) => {
        if (e.target.matches('[data-action="toggle-activity"]')) {
            await api('toggle_activity', { id: e.target.dataset.id });
            loadActivities();
        }
        if (e.target.matches('[data-action="toggle-goal-month"]')) {
            await api('toggle_goal_month', { id: e.target.dataset.id });
            loadMonthlyGoals();
        }
    });

    document.getElementById('saveActivity').addEventListener('click', async () => {
        const weekday = parseInt(activityWeekday.value, 10);
        const date = getDateForWeekday(weekday);
        await api('save_activity', { title: activityTitle.value, date });
        closeModals();
        activityTitle.value = '';
        loadActivities();
    });

    document.getElementById('saveHabit').addEventListener('click', async () => {
        await api('save_habit', { name: habitName.value });
        closeModals();
        habitName.value = '';
        loadHabits();
    });

    document.getElementById('saveWorkout').addEventListener('click', async () => {
        await api('save_workout', { name: workoutName.value, date: workoutDate.value });
        closeModals();
        workoutName.value = '';
        loadWorkoutsMonth();
    });

    document.getElementById('saveRun').addEventListener('click', async () => {
        const runId = document.getElementById('runId').value;
        if (runId) {
            await api('update_run', {
                id: runId,
                date: runDate.value,
                distance: runDistance.value,
                notes: runNotes.value
            });
            closeModals();
            runId.value = '';
            runDistance.value = '';
            runNotes.value = '';
            loadRuns();
            return;
        }
        await api('save_run', {
            title: 'Corrida',
            date: runDate.value,
            distance: runDistance.value,
            notes: runNotes.value
        });
        closeModals();
        runDistance.value = '';
        runNotes.value = '';
        loadRuns();
    });

    const readFileAsDataUrl = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });

    document.getElementById('savePhoto').addEventListener('click', async () => {
        if (!photoFile.files[0]) {
            return;
        }
        const formData = new FormData();
        formData.append('photo', photoFile.files[0]);
        formData.append('date', photoDate.value);
        await fetch(`?api=save_daily_photo`, {
            method: 'POST',
            body: formData
        }).then(r => r.json());
        closeModals();
        photoFile.value = '';
        loadPhoto();
    });

    document.getElementById('deletePhoto')?.addEventListener('click', async () => {
        const today = new Date().toISOString().slice(0, 10);
        await api('delete_daily_photo', { date: today });
        loadPhoto();
    });

    document.getElementById('photoPrev')?.addEventListener('click', () => {
        if (!photoGallery.length) return;
        photoIndex = (photoIndex - 1 + photoGallery.length) % photoGallery.length;
        renderPhotoCarousel();
    });

    document.getElementById('photoNext')?.addEventListener('click', () => {
        if (!photoGallery.length) return;
        photoIndex = (photoIndex + 1) % photoGallery.length;
        renderPhotoCarousel();
    });

    document.getElementById('saveEvent').addEventListener('click', async () => {
        const id = document.getElementById('eventId').value;
        const payload = { title: eventTitle.value, date: eventDate.value };
        if (id) {
            await api('update_event', { ...payload, id });
        } else {
            await api('save_event', payload);
        }
        closeModals();
        eventTitle.value = '';
        eventId.value = '';
        loadEvents();
    });

    document.getElementById('saveFinance').addEventListener('click', async () => {
        await api('save_finance', { amount: financeAmount.value, type: financeType.value, date: financeDate.value });
        closeModals();
        financeAmount.value = '';
        loadFinance();
    });

    document.getElementById('saveFinanceBase').addEventListener('click', () => {
        const input = document.getElementById('financeBaseModal');
        const value = parseFloat(input.value || '0') || 0;
        localStorage.setItem('financeBase', value.toString());
        const income = parseFloat(document.getElementById('financeIncome').textContent || '0') || 0;
        const expense = parseFloat(document.getElementById('financeExpense').textContent || '0') || 0;
        const total = value + income - expense;
        document.getElementById('financeTotal').textContent = total.toFixed(2);
        closeModals();
    });

    document.getElementById('saveRoutine').addEventListener('click', async () => {
        await api('save_routine_item', { time: routineTime.value, activity: routineActivity.value });
        closeModals();
        routineActivity.value = '';
        loadRoutine();
    });

    document.getElementById('saveRule').addEventListener('click', async () => {
        const id = document.getElementById('ruleId').value;
        const ruleInput = document.getElementById('ruleText');
        const text = ruleInput ? ruleInput.value : '';
        if (id) {
            await api('update_rule', { id, rule_text: text });
        } else {
            await api('save_rule', { rule_text: text });
        }
        closeModals();
        if (ruleInput) ruleInput.value = '';
        document.getElementById('ruleId').value = '';
        loadRules();
    });

    document.getElementById('saveGoal').addEventListener('click', async () => {
        await api('save_goal', { title: goalTitle.value });
        closeModals();
        goalTitle.value = '';
        loadGoals();
    });

    document.getElementById('saveGoalMonth').addEventListener('click', async () => {
        const id = document.getElementById('goalMonthId').value;
        const title = goalMonthTitle.value;
        if (id) {
            await api('update_goal_month', { id, title });
        } else {
            await api('save_goal_month', { title });
        }
        closeModals();
        goalMonthTitle.value = '';
        goalMonthId.value = '';
        loadMonthlyGoals();
    });

    const loadWeekRange = () => {
        const now = new Date();
        const day = now.getDay();
        const diffToMonday = day === 0 ? -6 : 1 - day;
        const monday = new Date(now);
        monday.setDate(now.getDate() + diffToMonday);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        const text = `${monday.toLocaleDateString('pt-BR')} - ${sunday.toLocaleDateString('pt-BR')}`;
        document.getElementById('weekRange').textContent = text;
    };

    const init = () => {
        setDefaultDates();
        loadWeekRange();
        loadActivities();
        loadHabits();
        loadWorkoutsMonth();
        loadRuns();
        loadPhoto();
        loadMessage();
        loadFinance();
        loadEvents();
        loadRules();
        loadRoutine();
        loadGoals();
        loadGoalsDone();
        loadMonthlyGoals();
    };

    init();

    document.getElementById('prevMonth')?.addEventListener('click', () => {
        workoutMonth.setMonth(workoutMonth.getMonth() - 1);
        loadWorkoutsMonth();
    });

    document.getElementById('nextMonth')?.addEventListener('click', () => {
        workoutMonth.setMonth(workoutMonth.getMonth() + 1);
        loadWorkoutsMonth();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
