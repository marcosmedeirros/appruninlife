<?php
// ARQUIVO: config.php

// ===== DETECÇÃO DO BASE PATH =====
require_once __DIR__ . '/includes/paths.php';

// Log de erros simples para produção
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_error.log');

$db_host = 'localhost';
$db_name = 'u289267434_runlife';
$db_user = 'u289267434_runlife';
$db_pass = 'Zonete@13';


try {
    // Conectar sem o banco para criá-lo se não existir
    try {
        $pdo_temp = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        unset($pdo_temp);
    } catch (Exception $e) {
        // Em alguns hosts a criação do banco não é permitida. Segue para conexão normal.
    }
    
    // Agora conectar ao banco
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1. Criação das Tabelas essenciais
    $sql_setup = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100),
        password_hash VARCHAR(255)
    );
    CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255),
        day_date DATE,
        status TINYINT DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS habits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        checked_dates TEXT
    );
    CREATE TABLE IF NOT EXISTS workouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        name VARCHAR(255),
        workout_date DATE,
        done TINYINT DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255),
        difficulty ENUM('facil','media','dificil') DEFAULT 'media',
        status TINYINT DEFAULT 0,
        goal_type ENUM('geral','anual') DEFAULT 'geral',
        completed_at DATETIME DEFAULT NULL
    );
    CREATE TABLE IF NOT EXISTS finances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        type ENUM('income','expense'),
        amount DECIMAL(10,2),
        description VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255),
        start_date DATETIME,
        description TEXT
    );
    CREATE TABLE IF NOT EXISTS routine_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        routine_time TIME NOT NULL,
        activity VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS life_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        rule_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS runs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        title VARCHAR(255) NOT NULL,
        run_date DATE NOT NULL,
        distance_km DECIMAL(6,2) DEFAULT 0,
        notes VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS daily_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        photo_date DATE NOT NULL,
        photo_url MEDIUMTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_date (user_id, photo_date)
    );
    CREATE TABLE IF NOT EXISTS user_settings (
        user_id INT PRIMARY KEY,
        total_xp INT DEFAULT 0
    );
    ";
    try {
        $pdo->exec($sql_setup);
    } catch (Exception $e) {
        error_log('[DB] Erro ao criar tabelas: ' . $e->getMessage());
        throw $e;
    }


    // --- AUTENTICAÇÃO: INSERE O USUÁRIO PADRÃO ---
    // Hash para '123456'. IMPORTANTE: O login simples é feito pela comparação '123456' no index.php. 
    // Este hash está aqui apenas por boa prática de DB.
    $hashed_password = password_hash('123456', PASSWORD_BCRYPT); 
    $pdo->prepare("
        INSERT INTO users (id, name, email, password_hash) 
        VALUES (1, 'Marcos Medeiros', 'marcosmedeirros', ?) 
        ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), password_hash=VALUES(password_hash)
    ")->execute([$hashed_password]);

    // Garante que a linha de XP exista para o usuário 1
    $pdo->exec("INSERT INTO user_settings (user_id, total_xp) VALUES (1, 0) ON DUPLICATE KEY UPDATE user_id=1");

    // NOTA: O POPULAMENTO INICIAL (INSERT INTO game_tasks / game_rewards) FOI REMOVIDO DAQUI
    
} catch (Exception $e) {
    error_log('[DB] Falha geral: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erro ao conectar no banco. Verifique credenciais e permissões.';
    exit;
}
?>