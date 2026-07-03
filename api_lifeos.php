<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_lifeos_shared.php';

header('Content-Type: application/json');

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function migrate_legacy_activities(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
    $stmt->execute([$userId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $stmt = $pdo->prepare("SELECT id, title, day_date, status FROM activities WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return;
    }

    $ins = $pdo->prepare("INSERT IGNORE INTO tasks (user_id, title, recurrence, recurrence_day, due_date, legacy_id, color, status)
        VALUES (?, ?, 'once', NULL, ?, ?, '#ffffff', ?)");
    foreach ($rows as $row) {
        $ins->execute([
            $userId,
            $row['title'],
            $row['day_date'],
            $row['id'],
            (int)$row['status']
        ]);
    }
}

function migrate_legacy_finances(PDO $pdo, int $userId, string $start, string $end): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fin_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
    $stmt->execute([$userId, $start, $end]);
    $hasNew = (int)$stmt->fetchColumn() > 0;
    if ($hasNew) {
        return;
    }

    $stmt = $pdo->prepare("SELECT id, type, amount, description, DATE(created_at) AS txn_date, created_at
        FROM finances WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ? ORDER BY id ASC");
    $stmt->execute([$userId, $start, $end]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return;
    }

    $ins = $pdo->prepare("INSERT IGNORE INTO fin_transactions (user_id, type, amount, description, category_id, transaction_date, created_at, legacy_id)
        VALUES (?, ?, ?, ?, NULL, ?, ?, ?)");
    foreach ($rows as $row) {
        $ins->execute([
            $userId,
            $row['type'],
            $row['amount'],
            $row['description'],
            $row['txn_date'],
            $row['created_at'],
            $row['id']
        ]);
    }
}

function calc_month_start_balance(PDO $pdo, int $userId, string $start): float {
    $stmt = $pdo->prepare("SELECT initial_balance FROM fin_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();
    $base = $settings ? (float)$settings['initial_balance'] : 0.0;

    $stmt = $pdo->prepare("SELECT type, SUM(amount) AS total
        FROM fin_transactions
        WHERE user_id = ? AND transaction_date < ?
        GROUP BY type");
    $stmt->execute([$userId, $start]);
    $income = 0.0;
    $expense = 0.0;
    foreach ($stmt->fetchAll() as $row) {
        if ($row['type'] === 'income') {
            $income = (float)$row['total'];
        } elseif ($row['type'] === 'expense') {
            $expense = (float)$row['total'];
        }
    }

    return $base + $income - $expense;
}

$action = $_GET['api'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

try {
    ensure_tables($pdo);

    $userId = 1;
    $today = date('Y-m-d');

    if ($action === 'tasks_list') {
        migrate_legacy_activities($pdo, $userId);
        $stmt = $pdo->prepare("SELECT t.*,
            CASE
                WHEN t.recurrence = 'once' THEN t.status
                ELSE EXISTS(SELECT 1 FROM task_completions tc WHERE tc.task_id = t.id AND tc.done_date = ?)
            END AS done_today,
            (SELECT GROUP_CONCAT(tc2.done_date ORDER BY tc2.done_date SEPARATOR ',')
             FROM task_completions tc2
             WHERE tc2.task_id = t.id
             AND tc2.done_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)) AS done_dates
            FROM tasks t WHERE t.user_id = ? ORDER BY t.id DESC");
        $stmt->execute([$today, $userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'habits_list') {
        $stmt = $pdo->query("SELECT id, name, checked_dates, recurrence, recurrence_day, show_in_tasks FROM habits ORDER BY id DESC");
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'habit_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $name = trim((string)($input['name'] ?? ''));
        $recurrence = $input['recurrence'] ?? 'daily';
        $recurrenceDay = isset($input['recurrence_day']) ? (int)$input['recurrence_day'] : null;
        $showInTasks = !empty($input['show_in_tasks']) ? 1 : 0;
        if ($name === '') {
            json_response(['ok' => false, 'error' => 'Nome obrigatorio.'], 400);
        }
        if (!in_array($recurrence, ['daily', 'weekly'], true)) {
            $recurrence = 'daily';
        }
        if ($recurrence !== 'weekly') {
            $recurrenceDay = null;
        } else {
            if ($recurrenceDay === null || $recurrenceDay < 1 || $recurrenceDay > 7) {
                $recurrenceDay = 1;
            }
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE habits SET name = ?, recurrence = ?, recurrence_day = ?, show_in_tasks = ? WHERE id = ?");
            $stmt->execute([$name, $recurrence, $recurrenceDay, $showInTasks, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO habits (name, checked_dates, recurrence, recurrence_day, show_in_tasks) VALUES (?, '[]', ?, ?, ?)");
            $stmt->execute([$name, $recurrence, $recurrenceDay, $showInTasks]);
        }
        json_response(['ok' => true]);
    }

    if ($action === 'habit_toggle') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $date = $input['date'] ?? date('Y-m-d');
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("SELECT checked_dates FROM habits WHERE id = ?");
        $stmt->execute([$id]);
        $raw = $stmt->fetchColumn();
        $arr = json_decode($raw ?: '[]', true);
        if (!is_array($arr)) {
            $arr = [];
        }
        if (in_array($date, $arr, true)) {
            $arr = array_values(array_diff($arr, [$date]));
            $wasAdded = false;
        } else {
            $arr[] = $date;
            $wasAdded = true;
        }
        $upd = $pdo->prepare("UPDATE habits SET checked_dates = ? WHERE id = ?");
        $upd->execute([json_encode($arr), $id]);
        if ($date === $today) {
            award_points($pdo, $userId, $wasAdded ? 10 : -10, 'Habito', $today);
        }
        json_response(['ok' => true]);
    }

    if ($action === 'habit_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM habits WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['ok' => true]);
    }

    if ($action === 'task_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $title = trim((string)($input['title'] ?? ''));
        if ($title === '') {
            json_response(['ok' => false, 'error' => 'Titulo obrigatorio.'], 400);
        }
        $recurrence = $input['recurrence'] ?? 'weekly';
        $recurrenceDay = isset($input['recurrence_day']) ? (int)$input['recurrence_day'] : null;
        $dueDate = $input['due_date'] ?? null;
        $color = $input['color'] ?? '#ffffff';

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, recurrence = ?, recurrence_day = ?, due_date = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $recurrence, $recurrenceDay ?: null, $dueDate ?: null, $color, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, recurrence, recurrence_day, due_date, color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $recurrence, $recurrenceDay ?: null, $dueDate ?: null, $color]);
        }

        json_response(['ok' => true]);
    }

    if ($action === 'task_toggle') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $toggleDate = isset($input['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date'])
            ? $input['date'] : $today;

        $stmt = $pdo->prepare("SELECT id, recurrence, status FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $task = $stmt->fetch();
        if (!$task) {
            json_response(['ok' => false, 'error' => 'Tarefa nao encontrada.'], 404);
        }

        $isToday = ($toggleDate === $today);

        if ($task['recurrence'] === 'once') {
            $newStatus = (int)!((int)$task['status']);
            $upd = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $upd->execute([$newStatus, $id, $userId]);
            if ($isToday) {
                award_points($pdo, $userId, $newStatus ? 10 : -10, 'Tarefa', $today);
            }
        } else {
            $stmt = $pdo->prepare("SELECT id FROM task_completions WHERE task_id = ? AND done_date = ?");
            $stmt->execute([$id, $toggleDate]);
            $row = $stmt->fetch();
            if ($row) {
                $pdo->prepare("DELETE FROM task_completions WHERE id = ?")->execute([$row['id']]);
                if ($isToday) {
                    award_points($pdo, $userId, -10, 'Tarefa', $today);
                }
            } else {
                $pdo->prepare("INSERT INTO task_completions (task_id, done_date) VALUES (?, ?)")->execute([$id, $toggleDate]);
                if ($isToday) {
                    award_points($pdo, $userId, 10, 'Tarefa', $today);
                }
            }
        }

        json_response(['ok' => true]);
    }

    if ($action === 'task_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'cats_list') {
        $stmt = $pdo->prepare("SELECT id, name, type, color, icon FROM fin_categories WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'cat_save') {
        $name = trim((string)($input['name'] ?? ''));
        $type = $input['type'] ?? 'expense';
        $color = $input['color'] ?? '#10d9a0';
        if ($name === '') {
            json_response(['ok' => false, 'error' => 'Nome obrigatorio.'], 400);
        }
        $stmt = $pdo->prepare("INSERT INTO fin_categories (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $type, $color, 'circle']);
        json_response(['ok' => true]);
    }

    if ($action === 'fin_transactions') {
        $month = $_GET['month'] ?? date('Y-m');
        $start = $month . '-01';
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        migrate_legacy_finances($pdo, $userId, $start, $end);
        $stmt = $pdo->prepare("SELECT t.id, t.type, t.amount, t.description, t.category_id, t.transaction_date,
            c.name AS cat_name, c.color AS cat_color
            FROM fin_transactions t
            LEFT JOIN fin_categories c ON c.id = t.category_id
            WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
            ORDER BY t.transaction_date DESC, t.id DESC");
        $stmt->execute([$userId, $start, $end]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'fin_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $type = $input['type'] ?? 'expense';
        $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
        $description = $input['description'] ?? null;
        $categoryId = isset($input['category_id']) ? (int)$input['category_id'] : null;
        $date = $input['date'] ?? date('Y-m-d');
        if ($amount <= 0) {
            json_response(['ok' => false, 'error' => 'Valor invalido.'], 400);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE fin_transactions SET type = ?, amount = ?, description = ?, category_id = ?, transaction_date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$type, $amount, $description, $categoryId ?: null, $date, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fin_transactions (user_id, type, amount, description, category_id, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $amount, $description, $categoryId ?: null, $date]);
        }

        json_response(['ok' => true]);
    }

    if ($action === 'fin_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM fin_transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'fin_summary') {
        $month = $_GET['month'] ?? date('Y-m');
        $start = $month . '-01';
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        migrate_legacy_finances($pdo, $userId, $start, $end);

        $stmt = $pdo->prepare("SELECT type, SUM(amount) AS total FROM fin_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ? GROUP BY type");
        $stmt->execute([$userId, $start, $end]);
        $income = 0;
        $expense = 0;
        foreach ($stmt->fetchAll() as $row) {
            if ($row['type'] === 'income') {
                $income = (float)$row['total'];
            } elseif ($row['type'] === 'expense') {
                $expense = (float)$row['total'];
            }
        }

        $initial = calc_month_start_balance($pdo, $userId, $start);

        $stmt = $pdo->prepare("SELECT c.id, c.name, c.color, t.type, SUM(t.amount) AS total
            FROM fin_transactions t
            LEFT JOIN fin_categories c ON c.id = t.category_id
            WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
            GROUP BY c.id, c.name, c.color, t.type
            ORDER BY total DESC");
        $stmt->execute([$userId, $start, $end]);
        $byCategory = $stmt->fetchAll();

        $balance = $initial + $income - $expense;
        json_response(['ok' => true, 'data' => [
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'initial_balance' => $initial,
            'by_category' => $byCategory
        ]]);
    }

    if ($action === 'fin_settings_get') {
        $stmt = $pdo->prepare("SELECT initial_balance FROM fin_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $initial = $row ? (float)$row['initial_balance'] : 0.0;
        json_response(['ok' => true, 'data' => ['initial_balance' => $initial]]);
    }

    if ($action === 'fin_settings_save') {
        $val = isset($input['initial_balance']) ? (float)$input['initial_balance'] : 0.0;
        $stmt = $pdo->prepare("INSERT INTO fin_settings (user_id, initial_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE initial_balance = VALUES(initial_balance)");
        $stmt->execute([$userId, $val]);
        json_response(['ok' => true]);
    }

    if ($action === 'goals_list') {
        $stmt = $pdo->prepare("SELECT id, title, target_amount, current_amount, deadline, status, color, goal_term FROM goals WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'goal_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $title = trim((string)($input['title'] ?? ''));
        $target = array_key_exists('target_amount', $input) ? (float)$input['target_amount'] : null;
        $current = array_key_exists('current_amount', $input) ? (float)$input['current_amount'] : null;
        $deadline = array_key_exists('deadline', $input) ? $input['deadline'] : null;
        $color = isset($input['color']) && trim((string)$input['color']) !== '' ? (string)$input['color'] : null;
        $goalTerm = $input['goal_term'] ?? 'short';
        if ($title === '') {
            json_response(['ok' => false, 'error' => 'Titulo obrigatorio.'], 400);
        }
        if (!in_array($goalTerm, ['short', 'long'], true)) {
            $goalTerm = 'short';
        }

        if ($id > 0) {
            $prevStmt = $pdo->prepare("SELECT target_amount, current_amount, deadline, color FROM goals WHERE id = ? AND user_id = ?");
            $prevStmt->execute([$id, $userId]);
            $prev = $prevStmt->fetch();
            if (!$prev) {
                json_response(['ok' => false, 'error' => 'Meta nao encontrada.'], 404);
            }
            $targetVal = $target !== null ? $target : (float)$prev['target_amount'];
            $currentVal = $current !== null ? $current : (float)$prev['current_amount'];
            $deadlineVal = $deadline !== null ? ($deadline ?: null) : $prev['deadline'];
            $colorVal = $color !== null ? $color : ($prev['color'] ?: '#10d9a0');

            $stmt = $pdo->prepare("UPDATE goals SET title = ?, target_amount = ?, current_amount = ?, deadline = ?, color = ?, goal_term = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $targetVal, $currentVal, $deadlineVal, $colorVal, $goalTerm, $id, $userId]);
        } else {
            $targetVal = $target !== null ? $target : 0;
            $currentVal = $current !== null ? $current : 0;
            $deadlineVal = $deadline ?: null;
            $colorVal = $color !== null ? $color : '#10d9a0';

            $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, target_amount, current_amount, deadline, color, goal_term) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $targetVal, $currentVal, $deadlineVal, $colorVal, $goalTerm]);
        }
        json_response(['ok' => true]);
    }

    if ($action === 'goal_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'goal_toggle') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("SELECT status FROM goals WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $goal = $stmt->fetch();
        if (!$goal) {
            json_response(['ok' => false, 'error' => 'Meta nao encontrada.'], 404);
        }
        $newStatus = 1 - (int)$goal['status'];
        $stmt = $pdo->prepare("UPDATE goals SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newStatus, $id, $userId]);
        award_points($pdo, $userId, $newStatus ? 50 : -50, 'Meta concluida', $today);
        json_response(['ok' => true]);
    }

    if ($action === 'goal_deposit') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
        if ($id <= 0 || $amount <= 0) {
            json_response(['ok' => false, 'error' => 'Dados invalidos.'], 400);
        }
        $stmt = $pdo->prepare("UPDATE goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'points_summary') {
        $bal = points_balance($pdo, $userId);
        $earnedPositive = max(0, $bal['total_earned']);
        $level = intdiv($earnedPositive, 100) + 1;
        $xpInto = $earnedPositive % 100;

        $stmt = $pdo->prepare("SELECT event_date, SUM(points) AS total FROM point_events
            WHERE user_id = ? AND event_date >= DATE_SUB(?, INTERVAL 6 DAY) AND event_date <= ?
            GROUP BY event_date");
        $stmt->execute([$userId, $today, $today]);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[$r['event_date']] = (int)$r['total'];
        }
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day", strtotime($today)));
            $week[] = ['date' => $d, 'points' => max(0, $map[$d] ?? 0)];
        }

        json_response(['ok' => true, 'data' => [
            'balance' => $bal['balance'],
            'total_earned' => $bal['total_earned'],
            'level' => $level,
            'xp_into_level' => $xpInto,
            'xp_for_level' => 100,
            'week' => $week
        ]]);
    }

    if ($action === 'rewards_list') {
        $stmt = $pdo->prepare("SELECT id, title, cost, icon FROM rewards WHERE user_id = ? AND active = 1 ORDER BY cost ASC, id DESC");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'reward_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $title = trim((string)($input['title'] ?? ''));
        $cost = isset($input['cost']) ? (int)$input['cost'] : 0;
        $icon = trim((string)($input['icon'] ?? ''));
        if ($icon === '') {
            $icon = '🎁';
        }
        if ($title === '' || $cost <= 0) {
            json_response(['ok' => false, 'error' => 'Preencha nome e um custo valido.'], 400);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE rewards SET title = ?, cost = ?, icon = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $cost, $icon, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rewards (user_id, title, cost, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $cost, $icon]);
        }
        json_response(['ok' => true]);
    }

    if ($action === 'reward_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("UPDATE rewards SET active = 0 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'reward_redeem') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("SELECT id, title, cost FROM rewards WHERE id = ? AND user_id = ? AND active = 1");
        $stmt->execute([$id, $userId]);
        $reward = $stmt->fetch();
        if (!$reward) {
            json_response(['ok' => false, 'error' => 'Recompensa nao encontrada.'], 404);
        }

        $bal = points_balance($pdo, $userId);
        if ($bal['balance'] < (int)$reward['cost']) {
            json_response(['ok' => false, 'error' => 'Pontos insuficientes.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO reward_redemptions (user_id, reward_id, title, cost) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $reward['id'], $reward['title'], $reward['cost']]);
        json_response(['ok' => true]);
    }

    if ($action === 'redemptions_list') {
        $stmt = $pdo->prepare("SELECT id, title, cost, redeemed_at FROM reward_redemptions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'water_add') {
        $amount = isset($input['amount_ml']) ? (int)$input['amount_ml'] : 0;
        if ($amount <= 0) {
            json_response(['ok' => false, 'error' => 'Quantidade invalida.'], 400);
        }
        $stmt = $pdo->prepare("INSERT INTO water_logs (user_id, amount_ml, log_date) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $amount, $today]);
        json_response(['ok' => true]);
    }

    if ($action === 'water_summary') {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_ml),0) AS total FROM water_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$userId, $today]);
        $todayTotal = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT log_date, SUM(amount_ml) AS total FROM water_logs
            WHERE user_id = ? AND log_date >= DATE_SUB(?, INTERVAL 6 DAY) AND log_date <= ?
            GROUP BY log_date");
        $stmt->execute([$userId, $today, $today]);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[$r['log_date']] = (int)$r['total'];
        }
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day", strtotime($today)));
            $week[] = ['date' => $d, 'ml' => $map[$d] ?? 0];
        }

        json_response(['ok' => true, 'data' => ['today_ml' => $todayTotal, 'week' => $week]]);
    }

    if ($action === 'food_add') {
        $description = trim((string)($input['description'] ?? ''));
        $mealLabel = trim((string)($input['meal_label'] ?? '')) ?: null;
        if ($description === '') {
            json_response(['ok' => false, 'error' => 'Descricao obrigatoria.'], 400);
        }
        $stmt = $pdo->prepare("INSERT INTO food_logs (user_id, description, meal_label, log_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $description, $mealLabel, $today]);
        json_response(['ok' => true]);
    }

    if ($action === 'food_today') {
        $stmt = $pdo->prepare("SELECT id, description, meal_label, created_at FROM food_logs WHERE user_id = ? AND log_date = ? ORDER BY id ASC");
        $stmt->execute([$userId, $today]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'food_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM food_logs WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'chat_history') {
        $stmt = $pdo->prepare("SELECT role, content, created_at FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 30");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => array_reverse($stmt->fetchAll())]);
    }

    json_response(['ok' => false, 'error' => 'Rota nao encontrada.'], 404);
} catch (Exception $e) {
    error_log('[API_LIFEOS] ' . $e->getMessage());
    $debug = isset($_GET['debug']) && $_GET['debug'] === '1';
    $msg = $debug ? $e->getMessage() : 'Erro no servidor.';
    json_response(['ok' => false, 'error' => $msg], 500);
}
