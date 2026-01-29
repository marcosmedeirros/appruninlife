<?php
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

try {
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

        // Do NOT auto-login. Return success so frontend redirects to login.
        echo json_encode(['ok' => true, 'message' => 'Cadastro realizado. Faça login.']);
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

    if ($action === 'forgot') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            echo json_encode(['ok' => false, 'message' => 'Informe o email.']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            // For security, return ok true but don't reveal existence
            echo json_encode(['ok' => true, 'message' => 'Se o email existir, você receberá instruções.']);
            exit;
        }

        $token = bin2hex(random_bytes(16));
        $expires = time() + 3600; // 1 hour

        // store token in a simple file store
        $storeFile = __DIR__ . '/data/password_resets.json';
        if (!is_dir(dirname($storeFile))) {
            mkdir(dirname($storeFile), 0755, true);
        }
        $store = [];
        if (file_exists($storeFile)) {
            $store = json_decode(file_get_contents($storeFile), true) ?: [];
        }
        $store[$token] = ['email' => $email, 'expires' => $expires];
        file_put_contents($storeFile, json_encode($store, JSON_UNESCAPED_UNICODE));

        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']) . "/reset.php?token={$token}";

        // Try to send email. If mail isn't configured, return link in response for manual copy.
        $subject = 'Recuperação de senha - RunInLife';
        $message = "Clique no link para redefinir sua senha: {$resetLink}";
        $headers = 'From: no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
        $sent = false;
        if (function_exists('mail')) {
            $sent = @mail($email, $subject, $message, $headers);
        }

        echo json_encode(['ok' => true, 'sent' => $sent, 'link' => $resetLink, 'message' => 'Se o email existir, você receberá instruções.']);
        exit;
    }

    if ($action === 'reset') {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($token === '' || $password === '') {
            echo json_encode(['ok' => false, 'message' => 'Token ou senha ausente.']);
            exit;
        }
        $storeFile = __DIR__ . '/data/password_resets.json';
        if (!file_exists($storeFile)) {
            echo json_encode(['ok' => false, 'message' => 'Token inválido.']);
            exit;
        }
        $store = json_decode(file_get_contents($storeFile), true) ?: [];
        if (!isset($store[$token])) {
            echo json_encode(['ok' => false, 'message' => 'Token inválido.']);
            exit;
        }
        $row = $store[$token];
        if ($row['expires'] < time()) {
            unset($store[$token]);
            file_put_contents($storeFile, json_encode($store, JSON_UNESCAPED_UNICODE));
            echo json_encode(['ok' => false, 'message' => 'Token expirado.']);
            exit;
        }
        $email = $row['email'];
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            echo json_encode(['ok' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        // remove token
        unset($store[$token]);
        file_put_contents($storeFile, json_encode($store, JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok' => true, 'message' => 'Senha atualizada. Faça login.']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Ação desconhecida.']);
} catch (Throwable $e) {
    http_response_code(500);
    // return error message for debugging; in production log instead
    echo json_encode(['ok' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
