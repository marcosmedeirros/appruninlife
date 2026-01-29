<?php
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Não autenticado.']);
    exit;
}

$action = $_GET['action'] ?? 'get';
$pdo = db();
$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, name, email, data_json FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Usuário não encontrado.']);
    exit;
}

if ($action === 'get') {
    $data = $user['data_json'] ? json_decode($user['data_json'], true) : defaultUserData();
    echo json_encode([
        'ok' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ],
        'data' => $data,
    ]);
    exit;
}

if ($action === 'save') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload) || !isset($payload['data'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Dados inválidos.']);
        exit;
    }

    $dataJson = json_encode($payload['data'], JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare('UPDATE users SET data_json = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$dataJson, $userId]);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Ação desconhecida.']);
