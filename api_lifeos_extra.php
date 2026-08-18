<?php
// ARQUIVO: api_lifeos_extra.php
// Endpoints da remodelagem 2026: treinos, corridas e o bootstrap unico do app.
// Incluido por api_lifeos.php logo antes do 404 — usa json_response(), migrate_*()
// e calc_month_start_balance() ja definidas la.

function handle_extra_actions(PDO $pdo, string $action, array $input, int $userId, string $today): void {

    // ===== TREINOS =====

    if ($action === 'workout_plan_get') {
        $stmt = $pdo->prepare("SELECT weekday, name, type FROM workout_plan WHERE user_id = ? ORDER BY weekday ASC");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'workout_plan_save') {
        $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
        $stmt = $pdo->prepare("INSERT INTO workout_plan (user_id, weekday, name, type) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), type = VALUES(type)");
        foreach ($items as $item) {
            $weekday = isset($item['weekday']) ? (int)$item['weekday'] : 0;
            if ($weekday < 1 || $weekday > 7) {
                continue;
            }
            $type = $item['type'] ?? 'rest';
            if (!in_array($type, ['gym', 'run', 'other', 'rest'], true)) {
                $type = 'rest';
            }
            $name = trim((string)($item['name'] ?? ''));
            if ($type === 'rest') {
                $name = '';
            }
            $stmt->execute([$userId, $weekday, $name !== '' ? $name : null, $type]);
        }
        json_response(['ok' => true]);
    }

    // Marca/desmarca o treino de um dia. O registro nasce na primeira marcacao.
    if ($action === 'workout_toggle') {
        $date = $input['date'] ?? $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            $date = $today;
        }
        $stmt = $pdo->prepare("SELECT id, done FROM workouts WHERE user_id = ? AND workout_date = ?");
        $stmt->execute([$userId, $date]);
        $row = $stmt->fetch();

        $name = trim((string)($input['name'] ?? ''));
        $type = $input['type'] ?? 'other';
        if (!in_array($type, ['gym', 'run', 'other', 'rest'], true)) {
            $type = 'other';
        }

        if ($row) {
            $newDone = (int)!((int)$row['done']);
            $pdo->prepare("UPDATE workouts SET done = ?, name = COALESCE(NULLIF(?, ''), name), type = ? WHERE id = ?")
                ->execute([$newDone, $name, $type, $row['id']]);
        } else {
            $newDone = 1;
            $pdo->prepare("INSERT INTO workouts (user_id, name, workout_date, done, type) VALUES (?, ?, ?, 1, ?)")
                ->execute([$userId, $name !== '' ? $name : 'Treino', $date, $type]);
        }
        json_response(['ok' => true, 'data' => ['done' => $newDone]]);
    }

    if ($action === 'workout_logs') {
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-60 days'));
        $to = $_GET['to'] ?? $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$from)) {
            $from = date('Y-m-d', strtotime('-60 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$to)) {
            $to = $today;
        }
        $stmt = $pdo->prepare("SELECT id, name, workout_date, done, type FROM workouts
            WHERE user_id = ? AND workout_date BETWEEN ? AND ? ORDER BY workout_date DESC");
        $stmt->execute([$userId, $from, $to]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // ===== CORRIDAS =====

    if ($action === 'runs_list') {
        $stmt = $pdo->prepare("SELECT id, title, run_date, distance_km, duration_min, notes
            FROM runs WHERE user_id = ? ORDER BY run_date DESC, id DESC LIMIT 120");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'run_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $date = $input['date'] ?? $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            $date = $today;
        }
        $distance = isset($input['distance_km']) ? (float)$input['distance_km'] : 0;
        $duration = isset($input['duration_min']) ? (int)$input['duration_min'] : 0;
        $notes = trim((string)($input['notes'] ?? ''));
        $title = trim((string)($input['title'] ?? ''));
        if ($title === '') {
            $title = 'Corrida';
        }
        if ($distance <= 0 && $duration <= 0) {
            json_response(['ok' => false, 'error' => 'Informe a distancia ou o tempo.'], 400);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE runs SET title = ?, run_date = ?, distance_km = ?, duration_min = ?, notes = ?
                WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $date, $distance, $duration, $notes !== '' ? $notes : null, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO runs (user_id, title, run_date, distance_km, duration_min, notes)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $date, $distance, $duration, $notes !== '' ? $notes : null]);
        }
        json_response(['ok' => true]);
    }

    if ($action === 'run_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $pdo->prepare("DELETE FROM runs WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    // ===== BOOTSTRAP =====
    // Uma unica chamada com tudo que o app precisa para abrir, para a troca de abas
    // ser instantanea (sem request por aba).

    if ($action === 'bootstrap') {
        migrate_legacy_activities($pdo, $userId);

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', (string)$month)) {
            $month = date('Y-m');
        }
        $monthStart = $month . '-01';
        $monthEnd = (new DateTime($monthStart))->modify('last day of this month')->format('Y-m-d');
        migrate_legacy_finances($pdo, $userId, $monthStart, $monthEnd);

        $stmt = $pdo->prepare("SELECT t.id, t.title, t.area, t.priority, t.recurrence, t.recurrence_day,
                t.due_date, t.color, t.status,
                CASE
                    WHEN t.recurrence = 'once' THEN t.status
                    ELSE EXISTS(SELECT 1 FROM task_completions tc WHERE tc.task_id = t.id AND tc.done_date = ?)
                END AS done_today,
                (SELECT GROUP_CONCAT(tc2.done_date ORDER BY tc2.done_date SEPARATOR ',')
                 FROM task_completions tc2
                 WHERE tc2.task_id = t.id AND tc2.done_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS done_dates
            FROM tasks t WHERE t.user_id = ? AND t.archived = 0
            ORDER BY t.priority DESC, t.id DESC");
        $stmt->execute([$today, $userId]);
        $tasks = $stmt->fetchAll();

        $habits = $pdo->query("SELECT id, name, checked_dates, recurrence, recurrence_day FROM habits ORDER BY id ASC")->fetchAll();

        $stmt = $pdo->prepare("SELECT weekday, name, type FROM workout_plan WHERE user_id = ? ORDER BY weekday ASC");
        $stmt->execute([$userId]);
        $plan = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT name, workout_date, done, type FROM workouts
            WHERE user_id = ? AND workout_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
            ORDER BY workout_date DESC");
        $stmt->execute([$userId]);
        $workoutLogs = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT id, title, run_date, distance_km, duration_min, notes
            FROM runs WHERE user_id = ? ORDER BY run_date DESC, id DESC LIMIT 40");
        $stmt->execute([$userId]);
        $runs = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT type, SUM(amount) AS total FROM fin_transactions
            WHERE user_id = ? AND transaction_date BETWEEN ? AND ? GROUP BY type");
        $stmt->execute([$userId, $monthStart, $monthEnd]);
        $income = 0.0;
        $expense = 0.0;
        foreach ($stmt->fetchAll() as $row) {
            if ($row['type'] === 'income') {
                $income = (float)$row['total'];
            } elseif ($row['type'] === 'expense') {
                $expense = (float)$row['total'];
            }
        }
        $initial = calc_month_start_balance($pdo, $userId, $monthStart);

        $stmt = $pdo->prepare("SELECT t.id, t.type, t.amount, t.description, t.category_id, t.transaction_date,
                c.name AS cat_name, c.color AS cat_color
            FROM fin_transactions t
            LEFT JOIN fin_categories c ON c.id = t.category_id
            WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
            ORDER BY t.transaction_date DESC, t.id DESC");
        $stmt->execute([$userId, $monthStart, $monthEnd]);
        $transactions = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT id, name, type, color FROM fin_categories WHERE user_id = ? ORDER BY name ASC");
        $stmt->execute([$userId]);
        $categories = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT id, title, target_amount, current_amount, deadline, status, color, goal_term
            FROM goals WHERE user_id = ? ORDER BY status ASC, id DESC");
        $stmt->execute([$userId]);
        $goals = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT content FROM daily_notes WHERE user_id = ? AND note_date = ?");
        $stmt->execute([$userId, $today]);
        $noteToday = (string)($stmt->fetchColumn() ?: '');

        json_response(['ok' => true, 'data' => [
            'today' => $today,
            'month' => $month,
            'tasks' => $tasks,
            'habits' => $habits,
            'workout_plan' => $plan,
            'workout_logs' => $workoutLogs,
            'runs' => $runs,
            'finance' => [
                'income' => $income,
                'expense' => $expense,
                'initial_balance' => $initial,
                'balance' => $initial + $income - $expense,
                'transactions' => $transactions,
                'categories' => $categories
            ],
            'goals' => $goals,
            'note_today' => $noteToday
        ]]);
    }
}
