<?php
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
if ($action === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Ação inválida.']);
    exit;
}

$pdo = db();

if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        echo json_encode(['ok' => false, 'message' => 'Preencha todos os campos.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'message' => 'Email já cadastrado.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $data = json_encode(defaultUserData(), JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, data_json, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute([$name, $email, $hash, $data]);

    $_SESSION['user_id'] = $pdo->lastInsertId();

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['ok' => false, 'message' => 'Email ou senha inválidos.']);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Ação desconhecida.']);
