<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch();
}

function ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255) NOT NULL,
        recurrence ENUM('daily','weekly','monthly','once') DEFAULT 'weekly',
        recurrence_day INT DEFAULT NULL,
        due_date DATE DEFAULT NULL,
        legacy_id INT DEFAULT NULL,
        color VARCHAR(20) DEFAULT '#ffffff',
        status TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!column_exists($pdo, 'tasks', 'legacy_id')) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN legacy_id INT DEFAULT NULL AFTER due_date");
    }
    try {
        $pdo->exec("ALTER TABLE tasks ADD UNIQUE KEY uniq_task_legacy_id (legacy_id)");
    } catch (Exception $e) {
        // Ignore if index already exists.
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS task_completions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        done_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_task_date (task_id, done_date),
        KEY idx_task (task_id),
        CONSTRAINT fk_task_completion FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!column_exists($pdo, 'task_completions', 'created_at')) {
        $pdo->exec("ALTER TABLE task_completions ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER done_date");
    }

    if (!column_exists($pdo, 'task_completions', 'done_date')) {
        $pdo->exec("ALTER TABLE task_completions ADD COLUMN done_date DATE NOT NULL AFTER task_id");
        $pdo->exec("UPDATE task_completions SET done_date = DATE(created_at) WHERE done_date IS NULL OR done_date = '0000-00-00'");
        try {
            $pdo->exec("ALTER TABLE task_completions ADD UNIQUE KEY uniq_task_date (task_id, done_date)");
        } catch (Exception $e) {
            // Ignore if index already exists.
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS finance_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        name VARCHAR(100) NOT NULL,
        type ENUM('income','expense') NOT NULL,
        color VARCHAR(20) DEFAULT '#10d9a0',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS finance_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        type ENUM('income','expense') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        category_id INT DEFAULT NULL,
        transaction_date DATE NOT NULL,
        legacy_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_txn_user_date (user_id, transaction_date),
        CONSTRAINT fk_fin_cat FOREIGN KEY (category_id) REFERENCES finance_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!column_exists($pdo, 'finance_transactions', 'legacy_id')) {
        $pdo->exec("ALTER TABLE finance_transactions ADD COLUMN legacy_id INT DEFAULT NULL AFTER transaction_date");
    }
    try {
        $pdo->exec("ALTER TABLE finance_transactions ADD UNIQUE KEY uniq_legacy_id (legacy_id)");
    } catch (Exception $e) {
        // Ignore if index already exists.
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS finance_settings (
        user_id INT PRIMARY KEY,
        initial_balance DECIMAL(10,2) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255) NOT NULL,
        target_amount DECIMAL(10,2) DEFAULT 0,
        current_amount DECIMAL(10,2) DEFAULT 0,
        deadline DATE DEFAULT NULL,
        status TINYINT DEFAULT 0,
        color VARCHAR(20) DEFAULT '#10d9a0',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!column_exists($pdo, 'goals', 'target_amount')) {
        $pdo->exec("ALTER TABLE goals ADD COLUMN target_amount DECIMAL(10,2) DEFAULT 0");
    }
    if (!column_exists($pdo, 'goals', 'current_amount')) {
        $pdo->exec("ALTER TABLE goals ADD COLUMN current_amount DECIMAL(10,2) DEFAULT 0");
    }
    if (!column_exists($pdo, 'goals', 'deadline')) {
        $pdo->exec("ALTER TABLE goals ADD COLUMN deadline DATE DEFAULT NULL");
    }
    if (!column_exists($pdo, 'goals', 'color')) {
        $pdo->exec("ALTER TABLE goals ADD COLUMN color VARCHAR(20) DEFAULT '#10d9a0'");
    }
    if (!column_exists($pdo, 'goals', 'status')) {
        $pdo->exec("ALTER TABLE goals ADD COLUMN status TINYINT DEFAULT 0");
    }
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
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM finance_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
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

    $ins = $pdo->prepare("INSERT IGNORE INTO finance_transactions (user_id, type, amount, description, category_id, transaction_date, created_at, legacy_id)
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

$action = $_GET['api'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

try {
    ensure_tables($pdo);

    $userId = 1;

    if ($action === 'tasks_list') {
        migrate_legacy_activities($pdo, $userId);
        $stmt = $pdo->prepare("SELECT t.*, 
            CASE 
                WHEN t.recurrence = 'once' THEN t.status
                ELSE EXISTS(SELECT 1 FROM task_completions tc WHERE tc.task_id = t.id AND tc.done_date = CURDATE())
            END AS done_today
            FROM tasks t WHERE t.user_id = ? ORDER BY t.id DESC");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
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
        $stmt = $pdo->prepare("SELECT id, recurrence, status FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $task = $stmt->fetch();
        if (!$task) {
            json_response(['ok' => false, 'error' => 'Tarefa nao encontrada.'], 404);
        }

        if ($task['recurrence'] === 'once') {
            $newStatus = (int)!((int)$task['status']);
            $upd = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $upd->execute([$newStatus, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM task_completions WHERE task_id = ? AND done_date = CURDATE()");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                $pdo->prepare("DELETE FROM task_completions WHERE id = ?")->execute([$row['id']]);
            } else {
                $pdo->prepare("INSERT INTO task_completions (task_id, done_date) VALUES (?, CURDATE())")->execute([$id]);
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
        $stmt = $pdo->prepare("SELECT id, name, type, color FROM finance_categories WHERE user_id = ? ORDER BY id DESC");
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
        $stmt = $pdo->prepare("INSERT INTO finance_categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $type, $color]);
        json_response(['ok' => true]);
    }

    if ($action === 'fin_transactions') {
        $month = $_GET['month'] ?? date('Y-m');
        $start = $month . '-01';
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        migrate_legacy_finances($pdo, $userId, $start, $end);
        $stmt = $pdo->prepare("SELECT t.id, t.type, t.amount, t.description, t.category_id, t.transaction_date,
            c.name AS cat_name, c.color AS cat_color
            FROM finance_transactions t
            LEFT JOIN finance_categories c ON c.id = t.category_id
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
            $stmt = $pdo->prepare("UPDATE finance_transactions SET type = ?, amount = ?, description = ?, category_id = ?, transaction_date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$type, $amount, $description, $categoryId ?: null, $date, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO finance_transactions (user_id, type, amount, description, category_id, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $amount, $description, $categoryId ?: null, $date]);
        }

        json_response(['ok' => true]);
    }

    if ($action === 'fin_delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'ID invalido.'], 400);
        }
        $stmt = $pdo->prepare("DELETE FROM finance_transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);
    }

    if ($action === 'fin_summary') {
        $month = $_GET['month'] ?? date('Y-m');
        $start = $month . '-01';
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        migrate_legacy_finances($pdo, $userId, $start, $end);

        $stmt = $pdo->prepare("SELECT type, SUM(amount) AS total FROM finance_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ? GROUP BY type");
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

        $stmt = $pdo->prepare("SELECT initial_balance FROM finance_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch();
        $initial = $settings ? (float)$settings['initial_balance'] : 0.0;

        $stmt = $pdo->prepare("SELECT c.id, c.name, c.color, t.type, SUM(t.amount) AS total
            FROM finance_transactions t
            LEFT JOIN finance_categories c ON c.id = t.category_id
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
        $stmt = $pdo->prepare("SELECT initial_balance FROM finance_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $initial = $row ? (float)$row['initial_balance'] : 0.0;
        json_response(['ok' => true, 'data' => ['initial_balance' => $initial]]);
    }

    if ($action === 'fin_settings_save') {
        $val = isset($input['initial_balance']) ? (float)$input['initial_balance'] : 0.0;
        $stmt = $pdo->prepare("INSERT INTO finance_settings (user_id, initial_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE initial_balance = VALUES(initial_balance)");
        $stmt->execute([$userId, $val]);
        json_response(['ok' => true]);
    }

    if ($action === 'goals_list') {
        $stmt = $pdo->prepare("SELECT id, title, target_amount, current_amount, deadline, status, color FROM goals WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'goal_save') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $title = trim((string)($input['title'] ?? ''));
        $target = isset($input['target_amount']) ? (float)$input['target_amount'] : 0;
        $current = isset($input['current_amount']) ? (float)$input['current_amount'] : 0;
        $deadline = $input['deadline'] ?? null;
        $color = $input['color'] ?? '#10d9a0';
        if ($title === '' || $target <= 0) {
            json_response(['ok' => false, 'error' => 'Campos invalidos.'], 400);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE goals SET title = ?, target_amount = ?, current_amount = ?, deadline = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $target, $current, $deadline ?: null, $color, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, target_amount, current_amount, deadline, color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $target, $current, $deadline ?: null, $color]);
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
        $stmt = $pdo->prepare("UPDATE goals SET status = 1 - status WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
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

    json_response(['ok' => false, 'error' => 'Rota nao encontrada.'], 404);
} catch (Exception $e) {
    error_log('[API_LIFEOS] ' . $e->getMessage());
    $debug = isset($_GET['debug']) && $_GET['debug'] === '1';
    $msg = $debug ? $e->getMessage() : 'Erro no servidor.';
    json_response(['ok' => false, 'error' => $msg], 500);
}
