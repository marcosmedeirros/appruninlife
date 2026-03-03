<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

try {

    if ($action === 'list') {
        $bets = $pdo->query("SELECT id, bet_date, odds, stake, result, profit FROM bets ORDER BY bet_date DESC, id DESC")->fetchAll();
        $byBet = [];
        if ($bets) {
            $ids = array_column($bets, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT bet_id, comp, descr, sort_order FROM bet_selections WHERE bet_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                $byBet[$row['bet_id']][] = [
                    'comp' => $row['comp'],
                    'desc' => $row['descr']
                ];
            }
        }

        $apostas = [];
        foreach ($bets as $bet) {
            $apostas[] = [
                'id' => (int)$bet['id'],
                'data' => $bet['bet_date'],
                'odds' => (float)$bet['odds'],
                'valor' => (float)$bet['stake'],
                'gr' => $bet['result'],
                'lucro' => (float)$bet['profit'],
                'selecoes' => $byBet[$bet['id']] ?? []
            ];
        }

        json_response(['apostas' => $apostas]);
    }

    if ($action === 'create' || $action === 'update') {
        $betId = isset($input['id']) ? (int)$input['id'] : 0;
        $date = $input['data'] ?? date('Y-m-d');
        $odds = isset($input['odds']) ? (float)$input['odds'] : 0;
        $valor = isset($input['valor']) ? (float)$input['valor'] : 0;
        $gr = $input['gr'] ?? 'Void';
        $lucro = isset($input['lucro']) ? (float)$input['lucro'] : 0;
        $selecoes = is_array($input['selecoes'] ?? null) ? $input['selecoes'] : [];

        if (!in_array($gr, ['Green', 'Red', 'Void'], true)) {
            $gr = 'Void';
        }

        $pdo->beginTransaction();
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO bets (bet_date, odds, stake, result, profit) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$date, $odds, $valor, $gr, $lucro]);
            $betId = (int)$pdo->lastInsertId();
        } else {
            if ($betId <= 0) {
                $pdo->rollBack();
                json_response(['error' => 'Missing bet id.'], 400);
            }
            $stmt = $pdo->prepare("UPDATE bets SET bet_date = ?, odds = ?, stake = ?, result = ?, profit = ? WHERE id = ?");
            $stmt->execute([$date, $odds, $valor, $gr, $lucro, $betId]);
            $pdo->prepare("DELETE FROM bet_selections WHERE bet_id = ?")->execute([$betId]);
        }

        if ($selecoes) {
            $ins = $pdo->prepare("INSERT INTO bet_selections (bet_id, comp, descr, sort_order) VALUES (?, ?, ?, ?)");
            $order = 1;
            foreach ($selecoes as $sel) {
                $comp = trim((string)($sel['comp'] ?? ''));
                $desc = trim((string)($sel['desc'] ?? ''));
                if ($comp === '' || $desc === '') {
                    continue;
                }
                $ins->execute([$betId, $comp, $desc, $order]);
                $order++;
            }
        }

        $pdo->commit();
        json_response(['success' => true, 'id' => $betId]);
    }

    if ($action === 'delete') {
        $betId = isset($input['id']) ? (int)$input['id'] : 0;
        if ($betId <= 0) {
            json_response(['error' => 'Missing bet id.'], 400);
        }
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM bet_selections WHERE bet_id = ?")->execute([$betId]);
        $pdo->prepare("DELETE FROM bets WHERE id = ?")->execute([$betId]);
        $pdo->commit();
        json_response(['success' => true]);
    }

    json_response(['error' => 'Unknown action.'], 400);
} catch (Exception $e) {
    error_log('[APP_API] ' . $e->getMessage());
    json_response(['error' => 'Server error.'], 500);
}

