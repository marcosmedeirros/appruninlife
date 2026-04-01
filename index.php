<?php
// ===== INCLUDES =====
require_once __DIR__ . '/config.php';

// ===== SETUP TABELAS EXTRAS =====
try {
    $extra_tables = [
        "CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 1,
            title VARCHAR(255) NOT NULL,
            recurrence ENUM('daily','weekly','monthly','once') DEFAULT 'once',
            recurrence_day TINYINT DEFAULT NULL,
            category VARCHAR(100) DEFAULT 'geral',
            color VARCHAR(7) DEFAULT '#6366f1',
            status TINYINT DEFAULT 0,
            due_date DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS task_completions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            completed_date DATE NOT NULL,
            UNIQUE KEY uniq_task_date (task_id, completed_date)
        )",
        "CREATE TABLE IF NOT EXISTS fin_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 1,
            name VARCHAR(100) NOT NULL,
            type ENUM('income','expense') NOT NULL,
            color VARCHAR(7) DEFAULT '#6366f1',
            icon VARCHAR(50) DEFAULT 'circle'
        )",
        "CREATE TABLE IF NOT EXISTS fin_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 1,
            category_id INT DEFAULT NULL,
            type ENUM('income','expense') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description VARCHAR(255),
            transaction_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS fin_goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 1,
            title VARCHAR(255) NOT NULL,
            target_amount DECIMAL(10,2) NOT NULL,
            current_amount DECIMAL(10,2) DEFAULT 0,
            deadline DATE DEFAULT NULL,
            color VARCHAR(7) DEFAULT '#10b981',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS fin_settings (
          user_id INT PRIMARY KEY,
          initial_balance DECIMAL(10,2) DEFAULT 0
        )"
    ];
    foreach ($extra_tables as $sql) {
        $pdo->exec($sql);
    }
} catch(Exception $e) {}

// ===== API HANDLER =====
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $uid = 1;

    try {
        // ---- TASKS ----
        if ($action === 'tasks_list') {
          $today = date('Y-m-d');
          $stmt = $pdo->prepare("SELECT t.*, 
            (SELECT COUNT(*) FROM task_completions tc WHERE tc.task_id=t.id AND tc.completed_date=?) as done_today
            FROM tasks t WHERE t.user_id=? ORDER BY t.created_at DESC");
          $stmt->execute([$today, $uid]);
          $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
          echo json_encode(['ok'=>true,'data'=>$tasks]);
          exit;
        }
        if ($action === 'task_save') {
            $d = json_decode(file_get_contents('php://input'), true);
            if (empty($d['id'])) {
                $s = $pdo->prepare("INSERT INTO tasks (user_id,title,recurrence,recurrence_day,category,color,due_date) VALUES (?,?,?,?,?,?,?)");
                $s->execute([$uid,$d['title'],$d['recurrence']??'once',$d['recurrence_day']??null,$d['category']??'geral',$d['color']??'#6366f1',$d['due_date']??null]);
                echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
            } else {
                $s = $pdo->prepare("UPDATE tasks SET title=?,recurrence=?,recurrence_day=?,category=?,color=?,due_date=? WHERE id=? AND user_id=?");
                $s->execute([$d['title'],$d['recurrence']??'once',$d['recurrence_day']??null,$d['category']??'geral',$d['color']??'#6366f1',$d['due_date']??null,$d['id'],$uid]);
                echo json_encode(['ok'=>true]);
            }
            exit;
        }
        if ($action === 'task_toggle') {
            $d = json_decode(file_get_contents('php://input'), true);
            $today = date('Y-m-d');
            $check = $pdo->prepare("SELECT id FROM task_completions WHERE task_id=? AND completed_date=?");
            $check->execute([$d['id'], $today]);
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM task_completions WHERE task_id=? AND completed_date=?")->execute([$d['id'],$today]);
                echo json_encode(['ok'=>true,'done'=>false]);
            } else {
                $pdo->prepare("INSERT INTO task_completions (task_id,completed_date) VALUES (?,?)")->execute([$d['id'],$today]);
                // If 'once', mark as done
                $t = $pdo->prepare("SELECT recurrence FROM tasks WHERE id=?"); $t->execute([$d['id']]);
                $row = $t->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['recurrence'] === 'once') {
                    $pdo->prepare("UPDATE tasks SET status=1 WHERE id=?")->execute([$d['id']]);
                }
                echo json_encode(['ok'=>true,'done'=>true]);
            }
            exit;
        }
        if ($action === 'task_delete') {
            $d = json_decode(file_get_contents('php://input'), true);
            $pdo->prepare("DELETE FROM tasks WHERE id=? AND user_id=?")->execute([$d['id'],$uid]);
            echo json_encode(['ok'=>true]);
            exit;
        }

        // ---- FINANCES ----
        if ($action === 'fin_summary') {
            $month = $_GET['month'] ?? date('Y-m');
          $s0 = $pdo->prepare("SELECT initial_balance FROM fin_settings WHERE user_id=?");
          $s0->execute([$uid]);
          $row0 = $s0->fetch(PDO::FETCH_ASSOC);
          $initial_balance = $row0 ? (float)$row0['initial_balance'] : 0.0;
            $s = $pdo->prepare("SELECT type, SUM(amount) as total FROM fin_transactions WHERE user_id=? AND DATE_FORMAT(transaction_date,'%Y-%m')=? GROUP BY type");
            $s->execute([$uid,$month]);
            $rows = $s->fetchAll(PDO::FETCH_ASSOC);
            $summary = ['income'=>0,'expense'=>0];
            foreach($rows as $r) $summary[$r['type']] = (float)$r['total'];
          $summary['initial_balance'] = $initial_balance;
          $summary['balance'] = $summary['income'] - $summary['expense'] + $initial_balance;
            // by category
            $s2 = $pdo->prepare("SELECT fc.name, fc.color, fc.icon, ft.type, SUM(ft.amount) as total 
                FROM fin_transactions ft 
                LEFT JOIN fin_categories fc ON fc.id=ft.category_id
                WHERE ft.user_id=? AND DATE_FORMAT(ft.transaction_date,'%Y-%m')=?
                GROUP BY ft.category_id, ft.type ORDER BY total DESC");
            $s2->execute([$uid,$month]);
            $summary['by_category'] = $s2->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok'=>true,'data'=>$summary]);
            exit;
        }
        if ($action === 'fin_settings_get') {
          $s0 = $pdo->prepare("SELECT initial_balance FROM fin_settings WHERE user_id=?");
          $s0->execute([$uid]);
          $row0 = $s0->fetch(PDO::FETCH_ASSOC);
          $initial_balance = $row0 ? (float)$row0['initial_balance'] : 0.0;
          echo json_encode(['ok'=>true,'data'=>['initial_balance'=>$initial_balance]]);
          exit;
        }
        if ($action === 'fin_settings_save') {
          $d = json_decode(file_get_contents('php://input'), true);
          $val = isset($d['initial_balance']) ? (float)$d['initial_balance'] : 0.0;
          $s0 = $pdo->prepare("INSERT INTO fin_settings (user_id, initial_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE initial_balance=VALUES(initial_balance)");
          $s0->execute([$uid, $val]);
          echo json_encode(['ok'=>true]);
          exit;
        }
        if ($action === 'fin_transactions') {
            $month = $_GET['month'] ?? date('Y-m');
            $s = $pdo->prepare("SELECT ft.*, fc.name as cat_name, fc.color as cat_color, fc.icon as cat_icon
                FROM fin_transactions ft
                LEFT JOIN fin_categories fc ON fc.id=ft.category_id
                WHERE ft.user_id=? AND DATE_FORMAT(ft.transaction_date,'%Y-%m')=?
                ORDER BY ft.transaction_date DESC, ft.created_at DESC");
            $s->execute([$uid,$month]);
            echo json_encode(['ok'=>true,'data'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
        if ($action === 'fin_save') {
          $d = json_decode(file_get_contents('php://input'), true);
          if (!empty($d['id'])) {
            $s = $pdo->prepare("UPDATE fin_transactions SET category_id=?, type=?, amount=?, description=?, transaction_date=? WHERE id=? AND user_id=?");
            $s->execute([$d['category_id']??null,$d['type'],$d['amount'],$d['description']??'',$d['date']??date('Y-m-d'),$d['id'],$uid]);
            echo json_encode(['ok'=>true]);
          } else {
            $s = $pdo->prepare("INSERT INTO fin_transactions (user_id,category_id,type,amount,description,transaction_date) VALUES (?,?,?,?,?,?)");
            $s->execute([$uid,$d['category_id']??null,$d['type'],$d['amount'],$d['description']??'',$d['date']??date('Y-m-d')]);
            echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
          }
          exit;
        }
        if ($action === 'fin_delete') {
            $d = json_decode(file_get_contents('php://input'), true);
            $pdo->prepare("DELETE FROM fin_transactions WHERE id=? AND user_id=?")->execute([$d['id'],$uid]);
            echo json_encode(['ok'=>true]);
            exit;
        }
        if ($action === 'cats_list') {
            $s = $pdo->prepare("SELECT * FROM fin_categories WHERE user_id=? ORDER BY name");
            $s->execute([$uid]);
            echo json_encode(['ok'=>true,'data'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
        if ($action === 'cat_save') {
            $d = json_decode(file_get_contents('php://input'), true);
            $s = $pdo->prepare("INSERT INTO fin_categories (user_id,name,type,color,icon) VALUES (?,?,?,?,?)");
            $s->execute([$uid,$d['name'],$d['type'],$d['color']??'#6366f1',$d['icon']??'circle']);
            echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
            exit;
        }
        if ($action === 'goals_list') {
            $s = $pdo->prepare("SELECT * FROM fin_goals WHERE user_id=? ORDER BY created_at DESC");
            $s->execute([$uid]);
            echo json_encode(['ok'=>true,'data'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
        if ($action === 'goal_save') {
            $d = json_decode(file_get_contents('php://input'), true);
            if (empty($d['id'])) {
                $s = $pdo->prepare("INSERT INTO fin_goals (user_id,title,target_amount,current_amount,deadline,color) VALUES (?,?,?,?,?,?)");
                $s->execute([$uid,$d['title'],$d['target_amount'],$d['current_amount']??0,$d['deadline']??null,$d['color']??'#10b981']);
                echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
            } else {
                $s = $pdo->prepare("UPDATE fin_goals SET title=?,target_amount=?,current_amount=?,deadline=?,color=? WHERE id=? AND user_id=?");
                $s->execute([$d['title'],$d['target_amount'],$d['current_amount'],$d['deadline']??null,$d['color']??'#10b981',$d['id'],$uid]);
                echo json_encode(['ok'=>true]);
            }
            exit;
        }
        if ($action === 'goal_delete') {
            $d = json_decode(file_get_contents('php://input'), true);
            $pdo->prepare("DELETE FROM fin_goals WHERE id=? AND user_id=?")->execute([$d['id'],$uid]);
            echo json_encode(['ok'=>true]);
            exit;
        }
        if ($action === 'goal_deposit') {
            $d = json_decode(file_get_contents('php://input'), true);
            $pdo->prepare("UPDATE fin_goals SET current_amount=current_amount+? WHERE id=? AND user_id=?")->execute([$d['amount'],$d['id'],$uid]);
            echo json_encode(['ok'=>true]);
            exit;
        }
    } catch(Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vida em Controle — Marcos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #09090f;
  --surface: #111118;
  --surface2: #16161f;
  --border: rgba(255,255,255,0.07);
  --border2: rgba(255,255,255,0.12);
  --text: #e8e8f0;
  --muted: #7b7b9a;
  --accent: #7c5cfc;
  --accent2: #a78bfa;
  --green: #10d9a0;
  --red: #ff4d6d;
  --yellow: #f5c842;
  --blue: #4da6ff;
  --glow: rgba(124,92,252,0.18);
  --radius: 16px;
  --radius-sm: 10px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  min-height: 100vh;
  overflow-x: hidden;
}

/* NOISE OVERLAY */
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 0;
  opacity: 0.6;
}

/* GLOW ORBS */
.orb {
  position: fixed; border-radius: 50%; filter: blur(90px); pointer-events: none; z-index: 0;
}
.orb-1 { width: 400px; height: 400px; background: rgba(124,92,252,0.12); top: -100px; left: -100px; }
.orb-2 { width: 350px; height: 350px; background: rgba(16,217,160,0.08); bottom: 100px; right: -80px; }
.orb-3 { width: 250px; height: 250px; background: rgba(255,77,109,0.07); top: 50%; left: 60%; }

/* LAYOUT */
.app { position: relative; z-index: 1; max-width: 1400px; margin: 0 auto; padding: 0 24px 60px; }

/* HEADER */
header {
  padding: 32px 0 24px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border);
  margin-bottom: 36px;
}
.logo {
  font-family: 'Syne', sans-serif;
  font-size: 22px; font-weight: 800;
  letter-spacing: -0.5px;
  display: flex; align-items: center; gap: 10px;
}
.logo-icon {
  width: 36px; height: 36px;
  background: var(--accent);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  box-shadow: 0 0 20px var(--glow);
}
.logo-sub { color: var(--muted); font-size: 12px; font-family: 'DM Mono', monospace; font-weight: 300; margin-top: 2px; display: block; }
.header-date {
  font-family: 'DM Mono', monospace;
  font-size: 12px; color: var(--muted);
  text-align: right;
}
.header-date strong { color: var(--text); display: block; font-size: 15px; font-family: 'Syne', sans-serif; }

/* NAV TABS */
.nav-tabs {
  display: flex; gap: 6px;
  margin-bottom: 32px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 5px;
  width: fit-content;
}
.nav-tab {
  padding: 9px 20px;
  border-radius: 10px;
  border: none; cursor: pointer;
  font-family: 'Syne', sans-serif;
  font-size: 13px; font-weight: 600;
  color: var(--muted);
  background: transparent;
  transition: all 0.2s;
  display: flex; align-items: center; gap: 7px;
  white-space: nowrap;
}
.nav-tab:hover { color: var(--text); }
.nav-tab.active {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 4px 16px rgba(124,92,252,0.35);
}

/* PANELS */
.panel { display: none; }
.panel.active { display: block; }

/* SECTION HEADER */
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px;
}
.section-title {
  font-family: 'Syne', sans-serif;
  font-size: 20px; font-weight: 700;
  display: flex; align-items: center; gap: 10px;
}
.section-title span { color: var(--muted); font-size: 13px; font-weight: 400; font-family: 'DM Mono', monospace; }

/* CARDS */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px 22px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.card:hover { border-color: var(--border2); }

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

/* STAT CARDS */
.stat-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}
@media(max-width:900px) { .stat-cards { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px) { .stat-cards { grid-template-columns: 1fr; } }

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px 22px;
  position: relative; overflow: hidden;
}
.stat-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
}
.stat-card.green::before { background: linear-gradient(90deg, var(--green), transparent); }
.stat-card.red::before { background: linear-gradient(90deg, var(--red), transparent); }
.stat-card.purple::before { background: linear-gradient(90deg, var(--accent), transparent); }
.stat-card.yellow::before { background: linear-gradient(90deg, var(--yellow), transparent); }
.stat-card.blue::before { background: linear-gradient(90deg, var(--blue), transparent); }

.stat-label {
  font-size: 11px; font-family: 'DM Mono', monospace;
  color: var(--muted); text-transform: uppercase; letter-spacing: 0.8px;
  margin-bottom: 8px;
}
.stat-value {
  font-family: 'Syne', sans-serif;
  font-size: 28px; font-weight: 700;
}
.stat-value.green { color: var(--green); }
.stat-value.red { color: var(--red); }
.stat-value.purple { color: var(--accent2); }
.stat-value.yellow { color: var(--yellow); }
.stat-value.blue { color: var(--blue); }
.stat-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }

/* BUTTONS */
.btn {
  padding: 9px 18px;
  border-radius: 9px; border: none; cursor: pointer;
  font-family: 'Syne', sans-serif;
  font-size: 13px; font-weight: 600;
  display: inline-flex; align-items: center; gap: 6px;
  transition: all 0.2s;
}
.btn-primary { background: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(124,92,252,0.3); }
.btn-primary:hover { background: #9070ff; transform: translateY(-1px); }
.btn-ghost { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
.btn-ghost:hover { border-color: var(--border2); }
.btn-danger { background: rgba(255,77,109,0.15); color: var(--red); border: 1px solid rgba(255,77,109,0.2); }
.btn-danger:hover { background: rgba(255,77,109,0.25); }
.btn-green { background: rgba(16,217,160,0.15); color: var(--green); border: 1px solid rgba(16,217,160,0.2); }
.btn-green:hover { background: rgba(16,217,160,0.25); }
.btn-sm { padding: 5px 12px; font-size: 11px; border-radius: 7px; }
.btn-icon { width: 32px; height: 32px; padding: 0; justify-content: center; }

/* TASK ITEMS */
.task-list { display: flex; flex-direction: column; gap: 10px; }

/* WEEK BOARD */
.week-board {
  display: grid;
  grid-template-columns: repeat(7, minmax(170px, 1fr));
  gap: 12px;
}
.day-column {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-height: 220px;
}
.day-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.day-header strong {
  color: var(--text);
  font-family: 'Syne', sans-serif;
  font-size: 13px;
  text-transform: none;
  letter-spacing: 0;
}
.day-list { display: flex; flex-direction: column; gap: 8px; }

.task-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  display: flex; align-items: center; gap: 12px;
  transition: all 0.2s;
  cursor: pointer;
}
.task-item:hover { border-color: var(--border2); transform: translateX(2px); }
.task-item.done { opacity: 0.5; }
.task-item.done .task-title { text-decoration: line-through; }

.task-info { flex: 1; min-width: 0; }
.task-title { font-size: 14px; font-weight: 500; }
.task-meta { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 2px; display: flex; gap: 8px; }
.task-cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 1px; }
.recurrence-badge {
  font-size: 10px; font-family: 'DM Mono', monospace;
  padding: 2px 7px; border-radius: 20px;
  background: var(--surface2); color: var(--muted);
}
.task-actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.2s; }
.task-item:hover .task-actions { opacity: 1; }

/* FILTER CHIPS */
.filter-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.chip {
  padding: 5px 13px; border-radius: 20px;
  font-size: 12px; font-family: 'DM Mono', monospace;
  border: 1px solid var(--border); color: var(--muted);
  cursor: pointer; background: transparent;
  transition: all 0.2s;
}
.chip.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.chip:hover:not(.active) { border-color: var(--border2); color: var(--text); }

/* PROGRESS BAR */
.progress-wrap { background: var(--surface2); border-radius: 99px; height: 6px; overflow: hidden; }
.progress-bar { height: 100%; border-radius: 99px; transition: width 0.5s ease; }

/* FINANCE */
.fin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:700px) { .fin-grid { grid-template-columns: 1fr; } }

.txn-list { display: flex; flex-direction: column; gap: 8px; }
.txn-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  transition: all 0.2s;
}
.txn-item:hover { border-color: var(--border2); }
.txn-icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0;
}
.txn-info { flex: 1; min-width: 0; }
.txn-desc { font-size: 14px; font-weight: 500; }
.txn-cat { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; }
.txn-amount { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; }
.txn-amount.income { color: var(--green); }
.txn-amount.expense { color: var(--red); }
.txn-date { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; }
.txn-del { opacity: 0; transition: opacity 0.2s; }
.txn-item:hover .txn-del { opacity: 1; }

/* GOALS */
.goal-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px;
}
.goal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.goal-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; }
.goal-amounts { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 8px; }
.goal-pct { font-size: 22px; font-weight: 700; font-family: 'Syne', sans-serif; }

/* MODALS */
.modal-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(0,0,0,0.7); backdrop-filter: blur(6px);
  display: none; align-items: center; justify-content: center;
  padding: 20px;
}
.modal-overlay.open { display: flex; }
.modal {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 20px;
  padding: 28px;
  width: 100%; max-width: 480px;
  max-height: 90vh; overflow-y: auto;
  animation: modalIn 0.2s ease;
}
@keyframes modalIn { from { opacity:0; transform: scale(0.95) translateY(10px); } to { opacity:1; transform: scale(1) translateY(0); } }
.modal-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 22px; }

/* FORMS */
.form-group { margin-bottom: 16px; }
.form-label { font-size: 12px; font-family: 'DM Mono', monospace; color: var(--muted); margin-bottom: 6px; display: block; letter-spacing: 0.5px; }
.form-control {
  width: 100%; padding: 10px 14px;
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 9px; color: var(--text);
  font-family: 'DM Sans', sans-serif; font-size: 14px;
  transition: border-color 0.2s;
  outline: none;
}
.form-control:focus { border-color: var(--accent); }
.form-control option { background: var(--surface2); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

/* COLOR PICKER ROW */
.color-row { display: flex; gap: 8px; flex-wrap: wrap; }
.color-swatch {
  width: 28px; height: 28px; border-radius: 7px;
  cursor: pointer; border: 2px solid transparent;
  transition: all 0.15s;
}
.color-swatch.selected { border-color: #fff; transform: scale(1.1); }

/* CATEGORY BADGES */
.cat-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-family: 'DM Mono', monospace;
}

/* MONTH PICKER */
.month-nav { display: flex; align-items: center; gap: 12px; }
.month-nav button {
  background: var(--surface2); border: 1px solid var(--border);
  color: var(--text); border-radius: 8px;
  width: 32px; height: 32px; cursor: pointer;
  font-size: 16px; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.month-nav button:hover { border-color: var(--accent); color: var(--accent); }
.month-display { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; }

/* EMPTY STATE */
.empty {
  text-align: center; padding: 48px 20px;
  color: var(--muted);
}
.empty-icon { font-size: 40px; margin-bottom: 12px; }
.empty-text { font-size: 14px; }

/* SCROLLBAR */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 99px; }

/* TOAST */
.toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.toast {
  padding: 12px 18px; border-radius: 11px;
  font-size: 13px; font-weight: 500;
  background: var(--surface2); border: 1px solid var(--border2);
  color: var(--text);
  animation: toastIn 0.3s ease;
  display: flex; align-items: center; gap: 8px;
}
@keyframes toastIn { from { opacity:0; transform: translateX(20px); } to { opacity:1; transform:translateX(0); } }
.toast.ok::before { content: '✓'; color: var(--green); }
.toast.err::before { content: '✕'; color: var(--red); }

/* RESPONSIVENESS */
@media(max-width:768px) {
  .stat-cards { grid-template-columns: 1fr 1fr; }
  .nav-tabs { overflow-x: auto; width: 100%; }
  .cards-grid { grid-template-columns: 1fr; }
  .week-board { grid-template-columns: repeat(2, minmax(160px, 1fr)); }
}
@media(max-width:480px) {
  .stat-cards { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .week-board { grid-template-columns: 1fr; }
}

/* CHART BARS */
.chart-bars { display: flex; align-items: flex-end; gap: 6px; height: 80px; margin-top: 12px; }
.chart-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; height: 100%; justify-content: flex-end; }
.chart-bar { width: 100%; border-radius: 5px 5px 0 0; min-height: 4px; transition: height 0.4s ease; }
.chart-bar-label { font-size: 9px; color: var(--muted); font-family: 'DM Mono', monospace; }

/* SECTION DIVIDER */
.section-divider { border: none; border-top: 1px solid var(--border); margin: 28px 0; }

/* OVERVIEW LAYOUT */
.overview-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 20px;
}
@media(max-width:900px) { .overview-grid { grid-template-columns: 1fr; } }

.card-title {
  font-family: 'Syne', sans-serif;
  font-size: 14px; font-weight: 700;
  color: var(--muted);
  margin-bottom: 16px;
  text-transform: uppercase; letter-spacing: 0.5px;
  font-size: 11px;
}
</style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="toast-wrap" id="toastWrap"></div>

<div class="app">

  <!-- HEADER -->
  <header>
    <div class="logo">
      <div class="logo-icon">⚡</div>
      <div>
        Vida em Controle
        <span class="logo-sub">dashboard pessoal / marcos</span>
      </div>
    </div>
    <div class="header-date">
      <strong id="todayDate"></strong>
      <span id="todayDay"></span>
    </div>
  </header>

  <!-- NAV -->
  <nav class="nav-tabs" role="tablist">
    <button class="nav-tab active" onclick="switchTab('overview')" id="tab-overview">
      <span>🏠</span> Visão Geral
    </button>
    <button class="nav-tab" onclick="switchTab('tasks')" id="tab-tasks">
      <span>✅</span> Atividades
    </button>
    <button class="nav-tab" onclick="switchTab('finance')" id="tab-finance">
      <span>💰</span> Finanças
    </button>
    <button class="nav-tab" onclick="switchTab('goals')" id="tab-goals">
      <span>🎯</span> Metas
    </button>
  </nav>

  <!-- ===== PANEL: OVERVIEW ===== -->
  <div class="panel active" id="panel-overview">

    <div class="stat-cards" id="overviewStats">
      <div class="stat-card purple">
        <div class="stat-label">Atividades Hoje</div>
        <div class="stat-value purple" id="ov-tasks-done">—</div>
        <div class="stat-sub" id="ov-tasks-sub">de — total</div>
      </div>
      <div class="stat-card green">
        <div class="stat-label">Receita Mensal</div>
        <div class="stat-value green" id="ov-income">—</div>
        <div class="stat-sub">mês atual</div>
      </div>
      <div class="stat-card red">
        <div class="stat-label">Despesas Mês</div>
        <div class="stat-value red" id="ov-expense">—</div>
        <div class="stat-sub">mês atual</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-label">Saldo Líquido</div>
        <div class="stat-value blue" id="ov-balance">—</div>
        <div class="stat-sub">receita − despesas</div>
      </div>
    </div>

    <div class="overview-grid">
      <!-- Tasks preview -->
      <div class="card">
        <div class="card-title">Atividades de Hoje</div>
        <div id="ov-task-list" class="task-list">
          <div class="empty"><div class="empty-icon">📋</div><div class="empty-text">Carregando…</div></div>
        </div>
      </div>

      <!-- Finance mini -->
      <div style="display:flex; flex-direction:column; gap:16px;">
        <div class="card">
          <div class="card-title">Últimas Transações</div>
          <div id="ov-txn-list" class="txn-list" style="gap:8px;max-height:220px;overflow-y:auto;">
            <div class="empty"><div class="empty-text" style="padding:20px 0">Carregando…</div></div>
          </div>
        </div>
        <div class="card">
          <div class="card-title">Progresso das Metas</div>
          <div id="ov-goals-list" style="display:flex;flex-direction:column;gap:12px;max-height:180px;overflow-y:auto;">
            <div class="empty"><div class="empty-text" style="padding:10px 0">Carregando…</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== PANEL: TASKS ===== -->
  <div class="panel" id="panel-tasks">
    <div class="section-header">
      <div class="section-title">Atividades <span id="tasks-week-label">Semana</span></div>
      <button class="btn btn-primary" onclick="openTaskModal()">+ Nova Atividade</button>
    </div>

    <div id="taskBoard" class="week-board">
      <div class="empty" style="grid-column:1/-1"><div class="empty-icon">✅</div><div class="empty-text">Nenhuma atividade ainda.</div></div>
    </div>
  </div>

  <!-- ===== PANEL: FINANCE ===== -->
  <div class="panel" id="panel-finance">
    <div class="section-header">
      <div class="section-title">Finanças</div>
      <div style="display:flex;gap:10px;align-items:center;">
        <div class="month-nav">
          <button onclick="changeMonth(-1)">‹</button>
          <span class="month-display" id="finMonthDisplay">—</span>
          <button onclick="changeMonth(1)">›</button>
        </div>
        <button class="btn btn-primary" onclick="openTxnModal()">+ Lançamento</button>
        <button class="btn btn-ghost btn-sm" onclick="openInitialBalanceModal()">Saldo inicial</button>
        <button class="btn btn-ghost btn-sm" onclick="openCatModal()">⚙ Categorias</button>
      </div>
    </div>

    <div class="stat-cards" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card green">
        <div class="stat-label">Receitas</div>
        <div class="stat-value green" id="fin-income">R$ 0,00</div>
      </div>
      <div class="stat-card red">
        <div class="stat-label">Despesas</div>
        <div class="stat-value red" id="fin-expense">R$ 0,00</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-label">Saldo</div>
        <div class="stat-value blue" id="fin-balance">R$ 0,00</div>
        <div class="stat-sub" id="fin-initial">saldo inicial: R$ 0,00</div>
      </div>
    </div>

    <div class="fin-grid">
      <!-- Transactions -->
      <div>
        <div class="filter-chips" id="finFilters" style="margin-bottom:16px">
          <button class="chip active" data-ftype="all" onclick="setFinFilter('all',this)">Todos</button>
          <button class="chip" data-ftype="income" onclick="setFinFilter('income',this)">Receitas</button>
          <button class="chip" data-ftype="expense" onclick="setFinFilter('expense',this)">Despesas</button>
        </div>
        <div id="txnList" class="txn-list">
          <div class="empty"><div class="empty-icon">💸</div><div class="empty-text">Nenhum lançamento.</div></div>
        </div>
      </div>

      <!-- By Category -->
      <div>
        <div class="card">
          <div class="card-title">Por Categoria</div>
          <div id="catBreakdown" style="display:flex;flex-direction:column;gap:10px;">
            <div class="empty"><div class="empty-text">Sem dados.</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== PANEL: GOALS ===== -->
  <div class="panel" id="panel-goals">
    <div class="section-header">
      <div class="section-title">Metas</div>
      <button class="btn btn-primary" onclick="openGoalModal()">+ Nova Meta</button>
    </div>
    <div id="goalsList" class="cards-grid">
      <div class="empty" style="grid-column:1/-1"><div class="empty-icon">🎯</div><div class="empty-text">Nenhuma meta cadastrada.</div></div>
    </div>
  </div>

</div><!-- /app -->


<!-- ===== MODAL: TASK ===== -->
<div class="modal-overlay" id="taskModal">
  <div class="modal">
    <div class="modal-title" id="taskModalTitle">Nova Atividade</div>
    <div class="form-group">
      <label class="form-label">TÍTULO</label>
      <input type="text" id="task-title" class="form-control" placeholder="Ex: Tomar remédio, Revisar código…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">RECORRÊNCIA</label>
        <select id="task-recurrence" class="form-control" onchange="toggleRecurrenceDay()">
          <option value="weekly">Toda semana</option>
          <option value="monthly">Todo mês</option>
          <option value="once">Não repetir</option>
        </select>
      </div>
      <div class="form-group" id="rec-day-group">
        <label class="form-label">DIA DA SEMANA</label>
        <select id="task-rec-day" class="form-control"></select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">COR</label>
      <div class="color-row" id="taskColorRow"></div>
    </div>
    <input type="hidden" id="task-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('taskModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveTask()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: TRANSACTION ===== -->
<div class="modal-overlay" id="txnModal">
  <div class="modal">
    <div class="modal-title">Novo Lançamento</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">TIPO</label>
        <select id="txn-type" class="form-control" onchange="loadCatsInModal()">
          <option value="expense">Despesa</option>
          <option value="income">Receita</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">VALOR (R$)</label>
        <input type="number" id="txn-amount" class="form-control" placeholder="0,00" min="0" step="0.01">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">DESCRIÇÃO</label>
      <input type="text" id="txn-desc" class="form-control" placeholder="Ex: Supermercado, Salário…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">CATEGORIA</label>
        <select id="txn-cat" class="form-control">
          <option value="">— sem categoria —</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">DATA</label>
        <input type="date" id="txn-date" class="form-control">
      </div>
    </div>
    <input type="hidden" id="txn-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('txnModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveTxn()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: CATEGORY ===== -->
<div class="modal-overlay" id="catModal">
  <div class="modal">
    <div class="modal-title">Categorias</div>
    <div id="catList" style="margin-bottom:20px; display:flex; flex-direction:column; gap:8px;"></div>
    <hr class="section-divider">
    <div class="modal-title" style="font-size:15px;">Nova Categoria</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">NOME</label>
        <input type="text" id="cat-name" class="form-control" placeholder="Alimentação">
      </div>
      <div class="form-group">
        <label class="form-label">TIPO</label>
        <select id="cat-type" class="form-control">
          <option value="expense">Despesa</option>
          <option value="income">Receita</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">COR</label>
      <div class="color-row" id="catColorRow"></div>
    </div>
    <input type="hidden" id="cat-color-val" value="#6366f1">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('catModal')">Fechar</button>
      <button class="btn btn-primary" onclick="saveCat()">Adicionar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: GOAL ===== -->
<div class="modal-overlay" id="goalModal">
  <div class="modal">
    <div class="modal-title" id="goalModalTitle">Nova Meta</div>
    <div class="form-group">
      <label class="form-label">TÍTULO</label>
      <input type="text" id="goal-title" class="form-control" placeholder="Ex: Viagem de férias, Reserva…">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">VALOR ALVO (R$)</label>
        <input type="number" id="goal-target" class="form-control" placeholder="0,00">
      </div>
      <div class="form-group">
        <label class="form-label">JÁ TENHO (R$)</label>
        <input type="number" id="goal-current" class="form-control" placeholder="0,00">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">PRAZO</label>
        <input type="date" id="goal-deadline" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">COR</label>
        <div class="color-row" id="goalColorRow"></div>
        <input type="hidden" id="goal-color-val" value="#10b981">
      </div>
    </div>
    <input type="hidden" id="goal-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('goalModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveGoal()">Salvar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: DEPOSIT ===== -->
<div class="modal-overlay" id="depositModal">
  <div class="modal" style="max-width:360px">
    <div class="modal-title">Adicionar Valor à Meta</div>
    <div class="form-group">
      <label class="form-label">VALOR (R$)</label>
      <input type="number" id="deposit-amount" class="form-control" placeholder="0,00" min="0" step="0.01">
    </div>
    <input type="hidden" id="deposit-goal-id">
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('depositModal')">Cancelar</button>
      <button class="btn btn-green" onclick="doDeposit()">Adicionar</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: INITIAL BALANCE ===== -->
<div class="modal-overlay" id="initialBalanceModal">
  <div class="modal" style="max-width:360px">
    <div class="modal-title">Saldo Inicial</div>
    <div class="form-group">
      <label class="form-label">VALOR (R$)</label>
      <input type="number" id="initial-balance" class="form-control" placeholder="0,00" step="0.01">
    </div>
    <div class="form-actions">
      <button class="btn btn-ghost" onclick="closeModal('initialBalanceModal')">Cancelar</button>
      <button class="btn btn-primary" onclick="saveInitialBalance()">Salvar</button>
    </div>
  </div>
</div>

<script>
// ===== GLOBALS =====
const COLORS = ['#6366f1','#a78bfa','#10d9a0','#f5c842','#ff4d6d','#4da6ff','#fb923c','#e879f9','#34d399','#f87171'];
const DAYS_WEEK = ['','Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
const MONTHS_PT = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const MONTHS_FULL = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

let allTasks = [];
let allTxns = [];
let allCats = [];
let allGoals = [];
let finFilter = 'all';
let currentMonth = new Date();
let selectedTaskColor = '#6366f1';
let selectedCatColor = '#6366f1';

function fmtBRL(val) {
  return 'R$ ' + parseFloat(val||0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function fmtDate(d) {
  if (!d) return '';
  const parts = d.split('-');
  return parts[2]+'/'+parts[1]+'/'+parts[0];
}
function getMonthStr(offset=0) {
  const d = new Date(currentMonth);
  d.setMonth(d.getMonth() + offset);
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');
}
function api(action, method='GET', body=null, params='') {
  const url = `?api=${action}${params}`;
  const opts = { method, headers: {'Content-Type':'application/json'} };
  if (body) opts.body = JSON.stringify(body);
  return fetch(url, opts).then(r => r.json());
}
function toast(msg, type='ok') {
  const w = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.textContent = msg;
  w.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ===== TABS =====
function switchTab(tab) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-'+tab).classList.add('active');
  document.getElementById('tab-'+tab).classList.add('active');
  if (tab === 'finance' || tab === 'overview') loadFinance();
  if (tab === 'goals' || tab === 'overview') loadGoals();
  if (tab === 'tasks' || tab === 'overview') loadTasks();
}

// ===== DATE HEADER =====
function initDate() {
  const now = new Date();
  const days = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
  document.getElementById('todayDate').textContent = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}`;
  document.getElementById('todayDay').textContent = days[now.getDay()];
}

function getWeekStartDate(baseDate = new Date()) {
  const d = new Date(baseDate);
  const day = (d.getDay() + 6) % 7; // 0=Mon
  d.setDate(d.getDate() - day);
  d.setHours(0,0,0,0);
  return d;
}

function getWeekDates(baseDate = new Date()) {
  const start = getWeekStartDate(baseDate);
  return Array.from({length:7}, (_,i) => {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    return d;
  });
}

function toISODate(d) {
  return d.toISOString().split('T')[0];
}

function dayIndexFromDate(d) {
  const jsDay = d.getDay();
  return jsDay === 0 ? 7 : jsDay; // 1=Mon..7=Sun
}

function dayIndexFromISO(iso) {
  if (!iso) return null;
  const d = new Date(iso + 'T00:00:00');
  return dayIndexFromDate(d);
}

function fmtShortDate(d) {
  return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}`;
}

function taskAppliesToDate(t, dateObj) {
  const rec = t.recurrence || 'weekly';
  const dayIdx = dayIndexFromDate(dateObj);
  if (rec === 'daily') return true;
  if (rec === 'weekly') return parseInt(t.recurrence_day || 0, 10) === dayIdx;
  if (rec === 'once') return t.due_date && toISODate(dateObj) === t.due_date;
  if (rec === 'monthly') return parseInt(t.recurrence_day || 0, 10) === dateObj.getDate();
  return false;
}

// ===== COLOR PICKERS =====
function buildColorRow(rowId, selectedVal, onSelect) {
  const row = document.getElementById(rowId);
  row.innerHTML = '';
  COLORS.forEach(c => {
    const s = document.createElement('div');
    s.className = 'color-swatch' + (c === selectedVal ? ' selected' : '');
    s.style.background = c;
    s.onclick = () => {
      row.querySelectorAll('.color-swatch').forEach(x => x.classList.remove('selected'));
      s.classList.add('selected');
      onSelect(c);
    };
    row.appendChild(s);
  });
}

// ===== TASKS =====
async function loadTasks() {
  const res = await api('tasks_list');
  allTasks = res.data || [];
  renderTasks();
  renderOverviewTasks();
  updateOverviewStats();
}

function renderTasks() {
  const board = document.getElementById('taskBoard');
  const weekDates = getWeekDates(new Date());
  const start = weekDates[0];
  const end = weekDates[6];
  document.getElementById('tasks-week-label').textContent = `Semana ${fmtShortDate(start)} – ${fmtShortDate(end)}`;

  board.innerHTML = weekDates.map((d, idx) => {
    const dayIdx = idx + 1; // 1=Mon..7=Sun
    const dayName = DAYS_WEEK[dayIdx];
    const items = allTasks.filter(t => taskAppliesToDate(t, d));
    const itemsHtml = items.length
      ? items.map(t => taskItemHTML(t)).join('')
      : '<div class="empty"><div class="empty-text">Sem atividades.</div></div>';
    return `<div class="day-column">
      <div class="day-header"><strong>${dayName}</strong><span>${fmtShortDate(d)}</span></div>
      <div class="day-list">${itemsHtml}</div>
    </div>`;
  }).join('');
}

function renderOverviewTasks() {
  const el = document.getElementById('ov-task-list');
  const today = new Date();
  const items = allTasks.filter(t => taskAppliesToDate(t, today));
  if (!items.length) {
    el.innerHTML = '<div class="empty"><div class="empty-text">Sem atividades hoje.</div></div>';
    return;
  }
  el.innerHTML = items.slice(0,6).map(t => taskItemHTML(t, true)).join('');
}

function taskItemHTML(t, compact=false) {
  const done = t.done_today == 1;
  return `<div class="task-item${done?' done':''}" id="task-item-${t.id}">
    <div class="task-info">
      <div class="task-title">${esc(t.title)}</div>
    </div>
    ${!compact ? `<div class="task-actions">
      <button class="btn btn-ghost btn-icon btn-sm" onclick="event.stopPropagation();editTask(${t.id})" title="Editar">✏</button>
      <button class="btn btn-danger btn-icon btn-sm" onclick="event.stopPropagation();deleteTask(${t.id})" title="Excluir">✕</button>
    </div>` : ''}
  </div>`;
}

async function toggleTask(id) {
  await api('task_toggle','POST',{id});
  loadTasks();
}

function openTaskModal(editData=null) {
  document.getElementById('task-id').value = editData?.id || '';
  document.getElementById('task-title').value = editData?.title || '';
  const recValue = editData?.recurrence || 'weekly';
  document.getElementById('task-recurrence').value = recValue;
  selectedTaskColor = editData?.color || '#6366f1';
  buildColorRow('taskColorRow', selectedTaskColor, c => selectedTaskColor = c);
  const selectedDay = recValue === 'once' ? dayIndexFromISO(editData?.due_date) : editData?.recurrence_day;
  toggleRecurrenceDay(selectedDay);
  document.getElementById('taskModalTitle').textContent = editData ? 'Editar Atividade' : 'Nova Atividade';
  openModal('taskModal');
}

function toggleRecurrenceDay(selected=null) {
  const rec = document.getElementById('task-recurrence').value;
  const sel = document.getElementById('task-rec-day');
  const label = document.querySelector('#rec-day-group .form-label');
  document.getElementById('rec-day-group').style.display = '';
  if (rec === 'monthly') {
    label.textContent = 'DIA DO MÊS';
    const fallback = new Date().getDate();
    const pick = selected || fallback;
    sel.innerHTML = Array.from({length:31},(_,i) =>
      `<option value="${i+1}" ${pick==i+1?'selected':''}>${i+1}</option>`).join('');
  } else {
    label.textContent = 'DIA DA SEMANA';
    const fallback = dayIndexFromDate(new Date());
    const pick = selected || fallback;
    sel.innerHTML = DAYS_WEEK.map((d,i) => i===0?'':
      `<option value="${i}" ${pick==i?'selected':''}>${d}</option>`).join('');
  }
}

function editTask(id) {
  const t = allTasks.find(x => x.id == id);
  if (t) openTaskModal(t);
}

async function saveTask() {
  const title = document.getElementById('task-title').value.trim();
  if (!title) { toast('Informe o título', 'err'); return; }
  const rec = document.getElementById('task-recurrence').value;
  const recDay = document.getElementById('task-rec-day').value;
  if (!recDay) { toast('Informe o dia', 'err'); return; }
  let dueDate = null;
  if (rec === 'once') {
    const start = getWeekStartDate(new Date());
    const d = new Date(start);
    d.setDate(start.getDate() + (parseInt(recDay,10) - 1));
    dueDate = toISODate(d);
  }
  const body = {
    id: document.getElementById('task-id').value,
    title,
    recurrence: rec,
    recurrence_day: rec === 'once' ? null : recDay,
    color: selectedTaskColor,
    due_date: rec === 'once' ? dueDate : null
  };
  const res = await api('task_save','POST',body);
  if (res.ok) { toast('Salvo!'); closeModal('taskModal'); loadTasks(); }
  else toast(res.error||'Erro','err');
}

async function deleteTask(id) {
  if (!confirm('Excluir esta atividade?')) return;
  await api('task_delete','POST',{id});
  toast('Excluída!');
  loadTasks();
}

// ===== FINANCE =====
function updateMonthDisplay() {
  const d = new Date(currentMonth);
  document.getElementById('finMonthDisplay').textContent = MONTHS_FULL[d.getMonth()] + ' ' + d.getFullYear();
}
function changeMonth(dir) {
  currentMonth.setMonth(currentMonth.getMonth() + dir);
  updateMonthDisplay();
  loadFinance();
}

async function loadFinance() {
  updateMonthDisplay();
  const month = getMonthStr();
  const [sumRes, txnRes] = await Promise.all([
    api('fin_summary','GET',null,`&month=${month}`),
    api('fin_transactions','GET',null,`&month=${month}`)
  ]);
  if (sumRes.ok) {
    const s = sumRes.data;
    document.getElementById('fin-income').textContent = fmtBRL(s.income);
    document.getElementById('fin-expense').textContent = fmtBRL(s.expense);
    const bal = s.balance;
    const balEl = document.getElementById('fin-balance');
    balEl.textContent = fmtBRL(bal);
    balEl.className = 'stat-value ' + (bal >= 0 ? 'green' : 'red');
    document.getElementById('fin-initial').textContent = `saldo inicial: ${fmtBRL(s.initial_balance || 0)}`;
    // Overview stats
    document.getElementById('ov-income').textContent = fmtBRL(s.income);
    document.getElementById('ov-expense').textContent = fmtBRL(s.expense);
    document.getElementById('ov-balance').textContent = fmtBRL(s.balance);
    renderCatBreakdown(s.by_category||[]);
  }
  if (txnRes.ok) {
    allTxns = txnRes.data || [];
    renderTxns();
    renderOverviewTxns();
  }
}

function setFinFilter(f, el) {
  finFilter = f;
  document.querySelectorAll('#finFilters .chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  renderTxns();
}

function renderTxns() {
  const list = document.getElementById('txnList');
  let txns = allTxns;
  if (finFilter !== 'all') txns = txns.filter(t => t.type === finFilter);
  if (!txns.length) {
    list.innerHTML = '<div class="empty"><div class="empty-icon">💸</div><div class="empty-text">Nenhum lançamento.</div></div>';
    return;
  }
  list.innerHTML = txns.map(t => txnItemHTML(t)).join('');
}

function renderOverviewTxns() {
  const el = document.getElementById('ov-txn-list');
  const recent = [...allTxns].slice(0,5);
  if (!recent.length) { el.innerHTML = '<div class="empty"><div class="empty-text">Sem lançamentos.</div></div>'; return; }
  el.innerHTML = recent.map(t => txnItemHTML(t, true)).join('');
}

function txnItemHTML(t, compact=false) {
  const sign = t.type === 'income' ? '+' : '-';
  const icon = t.cat_icon || (t.type === 'income' ? '💵' : '💳');
  const color = t.cat_color || (t.type === 'income' ? 'var(--green)' : 'var(--red)');
  return `<div class="txn-item">
    <div class="txn-icon" style="background:${color}20">${icon}</div>
    <div class="txn-info">
      <div class="txn-desc">${esc(t.description||'—')}</div>
      <div class="txn-cat">${esc(t.cat_name||'sem categoria')} · ${fmtDate(t.transaction_date)}</div>
    </div>
    <div>
      <div class="txn-amount ${t.type}">${sign}${fmtBRL(t.amount)}</div>
    </div>
    ${!compact ? `
      <button class="btn btn-ghost btn-icon btn-sm txn-del" onclick="editTxn(${t.id})" title="Editar">✏</button>
      <button class="btn btn-danger btn-icon btn-sm txn-del" onclick="deleteTxn(${t.id})" title="Excluir">✕</button>
    ` : ''}
  </div>`;
}

function renderCatBreakdown(cats) {
  const el = document.getElementById('catBreakdown');
  const expenses = cats.filter(c => c.type === 'expense');
  if (!expenses.length) { el.innerHTML = '<div class="empty"><div class="empty-text">Sem despesas.</div></div>'; return; }
  const max = Math.max(...expenses.map(c => parseFloat(c.total)));
  el.innerHTML = expenses.map(c => {
    const pct = max > 0 ? Math.round(parseFloat(c.total)/max*100) : 0;
    return `<div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
        <span style="font-size:13px;display:flex;align-items:center;gap:6px">
          <span style="width:8px;height:8px;border-radius:50%;background:${c.color||'var(--accent)'}; display:inline-block"></span>
          ${esc(c.name||'sem cat')}
        </span>
        <span style="font-family:'DM Mono',monospace;font-size:12px;color:var(--red)">${fmtBRL(c.total)}</span>
      </div>
      <div class="progress-wrap">
        <div class="progress-bar" style="width:${pct}%;background:${c.color||'var(--accent)'}"></div>
      </div>
    </div>`;
  }).join('');
}

function openTxnModal() {
  document.getElementById('txn-id').value = '';
  document.getElementById('txn-amount').value = '';
  document.getElementById('txn-desc').value = '';
  document.getElementById('txn-date').value = new Date().toISOString().split('T')[0];
  loadCatsInModal();
  openModal('txnModal');
}

function editTxn(id) {
  const t = allTxns.find(x => x.id == id);
  if (!t) return;
  document.getElementById('txn-id').value = t.id;
  document.getElementById('txn-type').value = t.type;
  document.getElementById('txn-amount').value = t.amount;
  document.getElementById('txn-desc').value = t.description || '';
  document.getElementById('txn-date').value = t.transaction_date;
  loadCatsInModal().then(() => {
    document.getElementById('txn-cat').value = t.category_id || '';
  });
  openModal('txnModal');
}

async function loadCatsInModal() {
  const type = document.getElementById('txn-type').value;
  const sel = document.getElementById('txn-cat');
  sel.innerHTML = '<option value="">— sem categoria —</option>';
  const cats = allCats.filter(c => c.type === type);
  cats.forEach(c => {
    const o = document.createElement('option');
    o.value = c.id; o.textContent = c.name;
    sel.appendChild(o);
  });
}

async function saveTxn() {
  const amount = parseFloat(document.getElementById('txn-amount').value);
  if (!amount || amount <= 0) { toast('Informe um valor válido','err'); return; }
  const body = {
    id: document.getElementById('txn-id').value,
    type: document.getElementById('txn-type').value,
    amount,
    description: document.getElementById('txn-desc').value,
    category_id: document.getElementById('txn-cat').value || null,
    date: document.getElementById('txn-date').value
  };
  const res = await api('fin_save','POST',body);
  if (res.ok) { toast('Lançamento salvo!'); closeModal('txnModal'); loadFinance(); }
  else toast(res.error||'Erro','err');
}

async function deleteTxn(id) {
  if (!confirm('Excluir este lançamento?')) return;
  await api('fin_delete','POST',{id});
  toast('Excluído!');
  loadFinance();
}

// ===== CATEGORIES =====
async function loadCats() {
  const res = await api('cats_list');
  allCats = res.data || [];
}

function openCatModal() {
  buildColorRow('catColorRow', selectedCatColor, c => { selectedCatColor = c; document.getElementById('cat-color-val').value = c; });
  renderCatList();
  openModal('catModal');
}

function renderCatList() {
  const el = document.getElementById('catList');
  if (!allCats.length) { el.innerHTML = '<div class="empty"><div class="empty-text">Nenhuma categoria.</div></div>'; return; }
  el.innerHTML = allCats.map(c =>
    `<div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--surface2);border-radius:9px;">
      <div style="width:10px;height:10px;border-radius:50%;background:${c.color};flex-shrink:0"></div>
      <span style="flex:1;font-size:13px">${esc(c.name)}</span>
      <span class="cat-badge" style="background:${c.type==='income'?'rgba(16,217,160,0.15)':'rgba(255,77,109,0.15)'}; color:${c.type==='income'?'var(--green)':'var(--red)'}">
        ${c.type==='income'?'receita':'despesa'}
      </span>
    </div>`
  ).join('');
}

async function saveCat() {
  const name = document.getElementById('cat-name').value.trim();
  if (!name) { toast('Informe o nome','err'); return; }
  const body = {
    name,
    type: document.getElementById('cat-type').value,
    color: document.getElementById('cat-color-val').value || '#6366f1'
  };
  const res = await api('cat_save','POST',body);
  if (res.ok) {
    toast('Categoria criada!');
    document.getElementById('cat-name').value = '';
    await loadCats();
    renderCatList();
  } else toast(res.error||'Erro','err');
}

// ===== GOALS =====
async function loadGoals() {
  const res = await api('goals_list');
  allGoals = res.data || [];
  renderGoals();
  renderOverviewGoals();
}

function renderGoals() {
  const el = document.getElementById('goalsList');
  if (!allGoals.length) {
    el.innerHTML = '<div class="empty" style="grid-column:1/-1"><div class="empty-icon">🎯</div><div class="empty-text">Nenhuma meta cadastrada.</div></div>';
    return;
  }
  el.innerHTML = allGoals.map(g => goalCardHTML(g)).join('');
}

function renderOverviewGoals() {
  const el = document.getElementById('ov-goals-list');
  if (!allGoals.length) { el.innerHTML = '<div class="empty"><div class="empty-text">Sem metas.</div></div>'; return; }
  el.innerHTML = allGoals.slice(0,3).map(g => {
    const pct = Math.min(100, Math.round(parseFloat(g.current_amount)/parseFloat(g.target_amount)*100)||0);
    return `<div>
      <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px">
        <span>${esc(g.title)}</span><span style="font-family:'DM Mono',monospace;color:var(--muted)">${pct}%</span>
      </div>
      <div class="progress-wrap">
        <div class="progress-bar" style="width:${pct}%;background:${g.color||'var(--green)'}"></div>
      </div>
    </div>`;
  }).join('');
}

function goalCardHTML(g) {
  const pct = Math.min(100, Math.round(parseFloat(g.current_amount)/parseFloat(g.target_amount)*100)||0);
  const left = Math.max(0, parseFloat(g.target_amount) - parseFloat(g.current_amount));
  return `<div class="goal-item" style="border-top:3px solid ${g.color||'var(--green)'}">
    <div class="goal-header">
      <div class="goal-title">${esc(g.title)}</div>
      <div style="display:flex;gap:6px">
        <button class="btn btn-green btn-sm" onclick="openDeposit(${g.id})">+ Depositar</button>
        <button class="btn btn-ghost btn-sm" onclick="editGoal(${g.id})">✏</button>
        <button class="btn btn-danger btn-sm" onclick="deleteGoal(${g.id})">✕</button>
      </div>
    </div>
    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:10px">
      <div class="goal-pct" style="color:${g.color||'var(--green)'}">${pct}%</div>
      <div style="font-size:13px;color:var(--muted)">concluído</div>
    </div>
    <div class="progress-wrap" style="height:8px;margin-bottom:8px">
      <div class="progress-bar" style="width:${pct}%;background:${g.color||'var(--green)'}"></div>
    </div>
    <div class="goal-amounts">
      <span>${fmtBRL(g.current_amount)} acumulado</span>
      <span>Meta: ${fmtBRL(g.target_amount)}</span>
    </div>
    ${left > 0 ? `<div style="font-size:12px;color:var(--muted);margin-top:6px">Faltam ${fmtBRL(left)}</div>` : `<div style="font-size:12px;color:var(--green);margin-top:6px">🎉 Meta atingida!</div>`}
    ${g.deadline ? `<div style="font-size:11px;color:var(--muted);margin-top:4px;font-family:'DM Mono',monospace">Prazo: ${fmtDate(g.deadline)}</div>` : ''}
  </div>`;
}

function openGoalModal(editData=null) {
  document.getElementById('goal-id').value = editData?.id || '';
  document.getElementById('goal-title').value = editData?.title || '';
  document.getElementById('goal-target').value = editData?.target_amount || '';
  document.getElementById('goal-current').value = editData?.current_amount || '';
  document.getElementById('goal-deadline').value = editData?.deadline || '';
  const color = editData?.color || '#10b981';
  document.getElementById('goal-color-val').value = color;
  buildColorRow('goalColorRow', color, c => document.getElementById('goal-color-val').value = c);
  document.getElementById('goalModalTitle').textContent = editData ? 'Editar Meta' : 'Nova Meta';
  openModal('goalModal');
}

function editGoal(id) {
  const g = allGoals.find(x => x.id == id);
  if (g) openGoalModal(g);
}

async function saveGoal() {
  const title = document.getElementById('goal-title').value.trim();
  const target = parseFloat(document.getElementById('goal-target').value);
  if (!title || !target) { toast('Preencha título e valor','err'); return; }
  const body = {
    id: document.getElementById('goal-id').value,
    title, target_amount: target,
    current_amount: parseFloat(document.getElementById('goal-current').value)||0,
    deadline: document.getElementById('goal-deadline').value||null,
    color: document.getElementById('goal-color-val').value
  };
  const res = await api('goal_save','POST',body);
  if (res.ok) { toast('Meta salva!'); closeModal('goalModal'); loadGoals(); }
  else toast(res.error||'Erro','err');
}

async function deleteGoal(id) {
  if (!confirm('Excluir esta meta?')) return;
  await api('goal_delete','POST',{id});
  toast('Meta excluída!');
  loadGoals();
}

function openDeposit(id) {
  document.getElementById('deposit-goal-id').value = id;
  document.getElementById('deposit-amount').value = '';
  openModal('depositModal');
}
async function doDeposit() {
  const amount = parseFloat(document.getElementById('deposit-amount').value);
  if (!amount || amount <= 0) { toast('Informe um valor','err'); return; }
  const id = document.getElementById('deposit-goal-id').value;
  const res = await api('goal_deposit','POST',{id,amount});
  if (res.ok) { toast('Valor adicionado!'); closeModal('depositModal'); loadGoals(); }
  else toast(res.error||'Erro','err');
}

function openInitialBalanceModal() {
  api('fin_settings_get').then(res => {
    if (res.ok) {
      document.getElementById('initial-balance').value = res.data.initial_balance || 0;
    } else {
      document.getElementById('initial-balance').value = 0;
    }
    openModal('initialBalanceModal');
  });
}

async function saveInitialBalance() {
  const val = parseFloat(document.getElementById('initial-balance').value);
  if (Number.isNaN(val)) { toast('Informe um valor válido','err'); return; }
  const res = await api('fin_settings_save','POST',{initial_balance: val});
  if (res.ok) { toast('Saldo inicial salvo!'); closeModal('initialBalanceModal'); loadFinance(); }
  else toast(res.error||'Erro','err');
}

// ===== OVERVIEW STATS =====
function updateOverviewStats() {
  const today = new Date();
  const todayTasks = allTasks.filter(t => taskAppliesToDate(t, today));
  const done = todayTasks.filter(t => t.done_today == 1).length;
  const total = todayTasks.length;
  document.getElementById('ov-tasks-done').textContent = `${done}/${total}`;
  document.getElementById('ov-tasks-sub').textContent = `de ${total} total`;
}

function esc(str) {
  if (!str) return '';
  return str.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close modal on outside click
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

// ===== INIT =====
async function init() {
  initDate();
  updateMonthDisplay();
  await loadCats();
  await Promise.all([loadTasks(), loadFinance(), loadGoals()]);
}
init();
</script>
</body>
</html>