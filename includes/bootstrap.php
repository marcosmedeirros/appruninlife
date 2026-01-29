<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
        $key = trim($key);
        $value = $value === null ? '' : trim($value);
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $config;
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['database'],
        $db['charset']
    );

    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}

function defaultUserData(): array
{
    return [
        'points' => 0,
        'categories' => [
            ['id' => uniqid('cat_', true), 'name' => 'Saúde'],
            ['id' => uniqid('cat_', true), 'name' => 'Trabalho'],
        ],
        'tasks' => [],
        'goals' => [],
        'habits' => [],
        'workouts' => [],
        'logs' => [],
    ];
}
