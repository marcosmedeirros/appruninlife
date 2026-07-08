<?php
// ARQUIVO: api_lifeos_shared.php
// Funcoes de banco (migrations + pontos) usadas por api_lifeos.php e ai_chat.php.

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

    $pdo->exec("CREATE TABLE IF NOT EXISTS fin_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        name VARCHAR(100) NOT NULL,
        type ENUM('income','expense') NOT NULL,
        color VARCHAR(20) DEFAULT '#10d9a0',
        icon VARCHAR(50) DEFAULT 'circle',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS fin_transactions (
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
        CONSTRAINT fk_fin_cat FOREIGN KEY (category_id) REFERENCES fin_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if (!column_exists($pdo, 'fin_transactions', 'legacy_id')) {
        $pdo->exec("ALTER TABLE fin_transactions ADD COLUMN legacy_id INT DEFAULT NULL AFTER transaction_date");
    }
    try {
        $pdo->exec("ALTER TABLE fin_transactions ADD UNIQUE KEY uniq_legacy_id (legacy_id)");
    } catch (Exception $e) {
        // Ignore if index already exists.
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS fin_settings (
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
        goal_term ENUM('short','long') DEFAULT 'short',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS habits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        checked_dates TEXT,
        recurrence ENUM('daily','weekly') DEFAULT 'daily',
        recurrence_day INT DEFAULT NULL,
        show_in_tasks TINYINT DEFAULT 0
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
    if (!column_exists($pdo, 'goals', 'goal_term')) {
        $pdo->exec("ALTER TABLE goals ADD COLUMN goal_term ENUM('short','long') DEFAULT 'short'");
    }
    if (!column_exists($pdo, 'habits', 'recurrence')) {
        $pdo->exec("ALTER TABLE habits ADD COLUMN recurrence ENUM('daily','weekly') DEFAULT 'daily'");
    }
    if (!column_exists($pdo, 'habits', 'recurrence_day')) {
        $pdo->exec("ALTER TABLE habits ADD COLUMN recurrence_day INT DEFAULT NULL");
    }
    if (!column_exists($pdo, 'habits', 'show_in_tasks')) {
        $pdo->exec("ALTER TABLE habits ADD COLUMN show_in_tasks TINYINT DEFAULT 0");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS point_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        points INT NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        event_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_date (user_id, event_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255) NOT NULL,
        cost INT NOT NULL DEFAULT 50,
        icon VARCHAR(10) DEFAULT '🎁',
        active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reward_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        reward_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        cost INT NOT NULL,
        redeemed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS water_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        amount_ml INT NOT NULL,
        log_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_date (user_id, log_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS food_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        description VARCHAR(500) NOT NULL,
        meal_label VARCHAR(50) DEFAULT NULL,
        log_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_date (user_id, log_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        role ENUM('user','assistant') NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        note_date DATE NOT NULL,
        content TEXT,
        photo_data MEDIUMTEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_date (user_id, note_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function award_points(PDO $pdo, int $userId, int $points, string $reason, string $date): void {
    if ($points === 0) {
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO point_events (user_id, points, reason, event_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $points, $reason, $date]);
}

function points_balance(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) AS total FROM point_events WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalEarned = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cost),0) AS total FROM reward_redemptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalSpent = (int)$stmt->fetchColumn();

    return [
        'total_earned' => $totalEarned,
        'total_spent' => $totalSpent,
        'balance' => $totalEarned - $totalSpent
    ];
}
