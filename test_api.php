<?php
// Arquivo de teste rápido
header('Content-Type: application/json');

if (!file_exists(__DIR__ . '/config.php')) {
    echo json_encode(['error' => 'config.php não encontrado']);
    exit;
}

require_once __DIR__ . '/config.php';

// Teste simples
try {
    $bets = $pdo->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bets'")->fetch();
    echo json_encode([
        'status' => 'ok',
        'message' => 'Banco conectado',
        'bets_table_exists' => $bets['count'] > 0,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

