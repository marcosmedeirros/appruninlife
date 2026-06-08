<?php
/**
 * mcp.php — Servidor MCP do RunInLife Betting Manager
 *
 * Conecte no Claude Desktop adicionando em claude_desktop_config.json:
 *   "runinlife": { "url": "https://marcosmedeiros.page/mcp.php" }
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Mcp-Session-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function text(string $content): array {
    return ['content' => [['type' => 'text', 'text' => $content]]];
}

function err(string $msg): array {
    return ['content' => [['type' => 'text', 'text' => $msg]], 'isError' => true];
}

function jsonText($data): array {
    return text(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ──────────────────────────────────────────────
// Schema das ferramentas
// ──────────────────────────────────────────────

$TOOLS = [
    [
        'name'        => 'list_bets',
        'description' => 'Lista todas as apostas registradas com seleções, odds e resultado.',
        'inputSchema' => ['type' => 'object', 'properties' => new stdClass()],
    ],
    [
        'name'        => 'get_stats',
        'description' => 'Retorna estatísticas consolidadas: total de apostas, greens, reds, winrate, lucro total, ROI.',
        'inputSchema' => ['type' => 'object', 'properties' => new stdClass()],
    ],
    [
        'name'        => 'create_bet',
        'description' => 'Registra uma nova aposta.',
        'inputSchema' => [
            'type'       => 'object',
            'required'   => ['data', 'odds', 'valor', 'gr', 'lucro'],
            'properties' => [
                'data'     => ['type' => 'string',  'description' => 'Data da aposta no formato YYYY-MM-DD'],
                'odds'     => ['type' => 'number',  'description' => 'Cotação/odds'],
                'valor'    => ['type' => 'number',  'description' => 'Valor apostado (stake)'],
                'gr'       => ['type' => 'string',  'enum' => ['Green', 'Red', 'Void'], 'description' => 'Resultado'],
                'lucro'    => ['type' => 'number',  'description' => 'Lucro (+) ou prejuízo (-) em valor absoluto'],
                'selecoes' => [
                    'type'        => 'array',
                    'description' => 'Seleções da aposta (para múltiplas)',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'comp' => ['type' => 'string', 'description' => 'Competição/campeonato'],
                            'desc' => ['type' => 'string', 'description' => 'Descrição (ex: Flamengo vence)'],
                            'time' => ['type' => 'string', 'description' => 'Time ou mercado'],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'name'        => 'update_bet',
        'description' => 'Atualiza os dados de uma aposta existente.',
        'inputSchema' => [
            'type'       => 'object',
            'required'   => ['id'],
            'properties' => [
                'id'       => ['type' => 'integer', 'description' => 'ID da aposta'],
                'data'     => ['type' => 'string'],
                'odds'     => ['type' => 'number'],
                'valor'    => ['type' => 'number'],
                'gr'       => ['type' => 'string', 'enum' => ['Green', 'Red', 'Void']],
                'lucro'    => ['type' => 'number'],
                'selecoes' => ['type' => 'array'],
            ],
        ],
    ],
    [
        'name'        => 'delete_bet',
        'description' => 'Remove uma aposta pelo ID.',
        'inputSchema' => [
            'type'       => 'object',
            'required'   => ['id'],
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'ID da aposta a deletar'],
            ],
        ],
    ],
    [
        'name'        => 'get_settings',
        'description' => 'Retorna configurações da conta, como banca inicial.',
        'inputSchema' => ['type' => 'object', 'properties' => new stdClass()],
    ],
    [
        'name'        => 'set_settings',
        'description' => 'Atualiza a banca inicial.',
        'inputSchema' => [
            'type'       => 'object',
            'required'   => ['initial_bankroll'],
            'properties' => [
                'initial_bankroll' => ['type' => 'number', 'description' => 'Valor da banca inicial'],
            ],
        ],
    ],
];

// ──────────────────────────────────────────────
// Handlers de cada tool
// ──────────────────────────────────────────────

function handleTool(string $name, array $args, PDO $pdo): array {
    switch ($name) {

        case 'list_bets': {
            $bets = $pdo->query(
                "SELECT id, bet_date, odds, stake, result, profit FROM bets ORDER BY bet_date DESC, id DESC"
            )->fetchAll(PDO::FETCH_ASSOC);

            $byBet = [];
            if ($bets) {
                $ids = array_column($bets, 'id');
                $ph  = implode(',', array_fill(0, count($ids), '?'));
                $st  = $pdo->prepare(
                    "SELECT bet_id, comp, descr, team, sort_order
                       FROM bet_selections
                      WHERE bet_id IN ($ph)
                      ORDER BY sort_order ASC, id ASC"
                );
                $st->execute($ids);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $byBet[$row['bet_id']][] = [
                        'comp' => $row['comp'],
                        'desc' => $row['descr'],
                        'time' => $row['team'],
                    ];
                }
            }

            $result = [];
            foreach ($bets as $bet) {
                $result[] = [
                    'id'       => (int)$bet['id'],
                    'data'     => $bet['bet_date'],
                    'odds'     => (float)$bet['odds'],
                    'valor'    => (float)$bet['stake'],
                    'gr'       => $bet['result'],
                    'lucro'    => (float)$bet['profit'],
                    'selecoes' => $byBet[$bet['id']] ?? [],
                ];
            }
            return jsonText(['total' => count($result), 'apostas' => $result]);
        }

        case 'get_stats': {
            $bets = $pdo->query("SELECT result, stake, profit FROM bets")->fetchAll(PDO::FETCH_ASSOC);
            $total    = count($bets);
            $greens   = 0;
            $reds     = 0;
            $lucro    = 0.0;
            $investido = 0.0;
            foreach ($bets as $b) {
                if ($b['result'] === 'Green') $greens++;
                if ($b['result'] === 'Red')   $reds++;
                $lucro    += (float)$b['profit'];
                $investido += (float)$b['stake'];
            }
            return jsonText([
                'total_apostas'   => $total,
                'greens'          => $greens,
                'reds'            => $reds,
                'voids'           => $total - $greens - $reds,
                'winrate'         => $total > 0 ? round($greens / $total * 100, 1) . '%' : '0%',
                'lucro_total'     => round($lucro, 2),
                'total_investido' => round($investido, 2),
                'roi'             => $investido > 0 ? round($lucro / $investido * 100, 2) . '%' : '0%',
            ]);
        }

        case 'create_bet':
        case 'update_bet': {
            $betId   = isset($args['id']) ? (int)$args['id'] : 0;
            $date    = $args['data']  ?? date('Y-m-d');
            $odds    = isset($args['odds'])  ? (float)$args['odds']  : 0;
            $valor   = isset($args['valor']) ? (float)$args['valor'] : 0;
            $gr      = in_array($args['gr'] ?? '', ['Green', 'Red', 'Void']) ? $args['gr'] : 'Void';
            $lucro   = isset($args['lucro']) ? (float)$args['lucro'] : 0;
            $selecoes = is_array($args['selecoes'] ?? null) ? $args['selecoes'] : [];

            $pdo->beginTransaction();
            try {
                if ($name === 'create_bet') {
                    $st = $pdo->prepare(
                        "INSERT INTO bets (bet_date, odds, stake, result, profit) VALUES (?, ?, ?, ?, ?)"
                    );
                    $st->execute([$date, $odds, $valor, $gr, $lucro]);
                    $betId = (int)$pdo->lastInsertId();
                } else {
                    if ($betId <= 0) {
                        $pdo->rollBack();
                        return err('id é obrigatório para update.');
                    }
                    $st = $pdo->prepare(
                        "UPDATE bets SET bet_date=?, odds=?, stake=?, result=?, profit=? WHERE id=?"
                    );
                    $st->execute([$date, $odds, $valor, $gr, $lucro, $betId]);
                    $pdo->prepare("DELETE FROM bet_selections WHERE bet_id=?")->execute([$betId]);
                }

                if ($selecoes) {
                    $ins   = $pdo->prepare(
                        "INSERT INTO bet_selections (bet_id, comp, descr, team, sort_order) VALUES (?, ?, ?, ?, ?)"
                    );
                    $order = 1;
                    foreach ($selecoes as $sel) {
                        $comp = trim((string)($sel['comp'] ?? ''));
                        $desc = trim((string)($sel['desc'] ?? ''));
                        $time = trim((string)($sel['time'] ?? ''));
                        if ($comp === '' || $desc === '') continue;
                        $ins->execute([$betId, $comp, $desc, $time, $order++]);
                    }
                }

                $pdo->commit();
                return text("Aposta salva com id=$betId.");

            } catch (Exception $e) {
                $pdo->rollBack();
                return err('Erro ao salvar: ' . $e->getMessage());
            }
        }

        case 'delete_bet': {
            $betId = isset($args['id']) ? (int)$args['id'] : 0;
            if ($betId <= 0) return err('id é obrigatório.');
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM bet_selections WHERE bet_id=?")->execute([$betId]);
                $pdo->prepare("DELETE FROM bets WHERE id=?")->execute([$betId]);
                $pdo->commit();
                return text("Aposta $betId deletada.");
            } catch (Exception $e) {
                $pdo->rollBack();
                return err('Erro ao deletar: ' . $e->getMessage());
            }
        }

        case 'get_settings': {
            $st = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
            $st->execute(['initial_bankroll']);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return jsonText(['initial_bankroll' => $row ? (float)$row['setting_value'] : 0.0]);
        }

        case 'set_settings': {
            $value = isset($args['initial_bankroll']) ? (float)$args['initial_bankroll'] : 0.0;
            $st = $pdo->prepare(
                "INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );
            $st->execute(['initial_bankroll', (string)$value]);
            return text("Banca inicial atualizada para $value.");
        }

        default:
            return err("Ferramenta '$name' não encontrada.");
    }
}

// ──────────────────────────────────────────────
// Roteamento JSON-RPC
// ──────────────────────────────────────────────

$body    = file_get_contents('php://input');
$request = json_decode($body, true);

if (!is_array($request)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$id     = $request['id']     ?? null;
$method = $request['method'] ?? '';

function rpc($id, $result): string {
    return json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result], JSON_UNESCAPED_UNICODE);
}

function rpcErr($id, int $code, string $msg): string {
    return json_encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $msg]]);
}

switch ($method) {

    case 'initialize':
        echo rpc($id, [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => ['tools' => new stdClass()],
            'serverInfo'      => ['name' => 'runinlife-bets', 'version' => '1.0.0'],
        ]);
        break;

    case 'notifications/initialized':
        http_response_code(202);
        break;

    case 'tools/list':
        global $TOOLS;
        echo rpc($id, ['tools' => $TOOLS]);
        break;

    case 'tools/call':
        $toolName = $request['params']['name']      ?? '';
        $toolArgs = $request['params']['arguments'] ?? [];
        try {
            $result = handleTool($toolName, (array)$toolArgs, $pdo);
        } catch (Exception $e) {
            $result = err('Erro interno: ' . $e->getMessage());
        }
        echo rpc($id, $result);
        break;

    case 'ping':
        echo rpc($id, new stdClass());
        break;

    default:
        echo rpcErr($id, -32601, 'Method not found');
}
