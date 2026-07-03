<?php
// ARQUIVO: ai_chat.php
// Chat com IA (Claude) que le e escreve dados do app: agua, comida, tarefas, habitos, financas.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_lifeos_shared.php';

header('Content-Type: application/json');

function json_out(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (ANTHROPIC_API_KEY === '') {
    json_out(['ok' => false, 'error' => 'Chave da Anthropic nao configurada no servidor (secrets.php).'], 500);
}

$userId = 1;
$today = date('Y-m-d');
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim((string)($input['message'] ?? ''));
if ($userMessage === '') {
    json_out(['ok' => false, 'error' => 'Mensagem vazia.'], 400);
}

ensure_tables($pdo);

// ===== HISTORICO RECENTE (contexto da conversa) =====
function load_history(PDO $pdo, int $userId, int $limit = 16): array {
    $stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = array_reverse($stmt->fetchAll());
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['role' => $r['role'], 'content' => $r['content']];
    }
    return $out;
}

function save_message(PDO $pdo, int $userId, string $role, string $content): void {
    $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, role, content) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $role, $content]);
}

// ===== CONTEXTO DE HOJE (tarefas/habitos aplicaveis hoje) =====
function tasks_today(PDO $pdo, int $userId, string $today): array {
    $isoDay = (int)date('N', strtotime($today));
    $stmt = $pdo->prepare("SELECT t.id, t.title, t.recurrence, t.recurrence_day, t.due_date, t.status,
        CASE WHEN t.recurrence = 'once' THEN t.status
             ELSE EXISTS(SELECT 1 FROM task_completions tc WHERE tc.task_id = t.id AND tc.done_date = ?)
        END AS done_today
        FROM tasks t WHERE t.user_id = ?");
    $stmt->execute([$today, $userId]);
    $out = [];
    foreach ($stmt->fetchAll() as $t) {
        $applies = false;
        if ($t['recurrence'] === 'daily') $applies = true;
        elseif ($t['recurrence'] === 'weekly') $applies = ((int)$t['recurrence_day'] === $isoDay);
        elseif ($t['recurrence'] === 'once') $applies = ($t['due_date'] === $today);
        if ($applies) {
            $out[] = ['id' => (int)$t['id'], 'title' => $t['title'], 'done' => (bool)$t['done_today']];
        }
    }
    return $out;
}

function habits_today(PDO $pdo, string $today): array {
    $isoDay = (int)date('N', strtotime($today));
    $stmt = $pdo->query("SELECT id, name, checked_dates, recurrence, recurrence_day FROM habits");
    $out = [];
    foreach ($stmt->fetchAll() as $h) {
        $applies = ($h['recurrence'] === 'daily') || ((int)$h['recurrence_day'] === $isoDay);
        if (!$applies) continue;
        $dates = json_decode($h['checked_dates'] ?: '[]', true);
        $done = is_array($dates) && in_array($today, $dates, true);
        $out[] = ['id' => (int)$h['id'], 'name' => $h['name'], 'done' => $done];
    }
    return $out;
}

$ctxTasks = tasks_today($pdo, $userId, $today);
$ctxHabits = habits_today($pdo, $today);
$ctxPoints = points_balance($pdo, $userId);
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_ml),0) FROM water_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$userId, $today]);
$ctxWaterToday = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT description, meal_label FROM food_logs WHERE user_id = ? AND log_date = ? ORDER BY id ASC");
$stmt->execute([$userId, $today]);
$ctxFoodToday = $stmt->fetchAll();

$weekdayNames = ['', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado', 'domingo'];
$weekdayName = $weekdayNames[(int)date('N', strtotime($today))];

$systemPrompt = "Voce e o assistente pessoal dentro do app 'Vida em Controle', de Marcos. Hoje e {$today} ({$weekdayName}).\n\n"
    . "Tarefas/habitos de hoje (use o id certo ao marcar como feito):\n"
    . json_encode(['tarefas' => $ctxTasks, 'habitos' => $ctxHabits], JSON_UNESCAPED_UNICODE) . "\n\n"
    . "Pontuacao atual: {$ctxPoints['balance']} pontos disponiveis, {$ctxPoints['total_earned']} pontos totais ganhos.\n"
    . "Agua bebida hoje: {$ctxWaterToday} ml.\n"
    . "Refeicoes registradas hoje: " . json_encode($ctxFoodToday, JSON_UNESCAPED_UNICODE) . "\n\n"
    . "Regras: responda sempre em portugues, curto e direto (poucas frases), com um tom leve e encorajador. "
    . "Quando o usuario contar algo que bate com uma tarefa ou habito da lista acima, marque como feito usando o id certo. "
    . "Se ele mencionar algo novo que nao existe na lista (uma tarefa nova pra lembrar), pode criar com create_task. "
    . "Nunca invente numeros que voce nao tem — use get_summary se precisar de dados de outros dias/semana/mes. "
    . "Nao repita de volta os dados que ja estao no contexto acima a nao ser que o usuario pergunte.";

$tools = [
    [
        'name' => 'log_water',
        'description' => 'Registra quantidade de agua bebida agora, somando ao total do dia.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['amount_ml' => ['type' => 'integer', 'description' => 'Quantidade em mililitros, ex: 300']],
            'required' => ['amount_ml'],
        ],
    ],
    [
        'name' => 'log_food',
        'description' => 'Registra uma refeicao ou alimento consumido hoje.',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'description' => ['type' => 'string', 'description' => 'O que foi comido, ex: arroz, feijao e frango'],
                'meal_label' => ['type' => 'string', 'description' => 'Rotulo opcional: cafe da manha, almoco, lanche, janta'],
            ],
            'required' => ['description'],
        ],
    ],
    [
        'name' => 'mark_task_done',
        'description' => 'Marca uma tarefa do dia como concluida, usando o id do contexto.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['task_id' => ['type' => 'integer']],
            'required' => ['task_id'],
        ],
    ],
    [
        'name' => 'mark_habit_done',
        'description' => 'Marca um habito do dia como concluido, usando o id do contexto.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['habit_id' => ['type' => 'integer']],
            'required' => ['habit_id'],
        ],
    ],
    [
        'name' => 'create_task',
        'description' => 'Cria uma nova tarefa (algo para lembrar) que ainda nao existe.',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'recurrence' => ['type' => 'string', 'enum' => ['daily', 'weekly', 'once'], 'description' => "'daily'=todo dia, 'weekly'=um dia fixo da semana, 'once'=data unica"],
                'recurrence_day' => ['type' => 'integer', 'description' => '1=segunda ... 7=domingo, obrigatorio se recurrence=weekly'],
                'due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, obrigatorio se recurrence=once'],
            ],
            'required' => ['title', 'recurrence'],
        ],
    ],
    [
        'name' => 'log_finance',
        'description' => 'Registra uma receita ou despesa financeira.',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['income', 'expense']],
                'amount' => ['type' => 'number'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['type', 'amount', 'description'],
        ],
    ],
    [
        'name' => 'get_summary',
        'description' => 'Busca dados agregados que nao estao no contexto: agua/pontos da semana, ou financas/metas do mes.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['scope' => ['type' => 'string', 'enum' => ['week', 'month']]],
            'required' => ['scope'],
        ],
    ],
];

function execute_tool(PDO $pdo, int $userId, string $today, string $name, array $args): array {
    switch ($name) {
        case 'log_water':
            $amount = (int)($args['amount_ml'] ?? 0);
            if ($amount <= 0) return ['ok' => false, 'error' => 'quantidade invalida'];
            $pdo->prepare("INSERT INTO water_logs (user_id, amount_ml, log_date) VALUES (?, ?, ?)")->execute([$userId, $amount, $today]);
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_ml),0) FROM water_logs WHERE user_id = ? AND log_date = ?");
            $stmt->execute([$userId, $today]);
            return ['ok' => true, 'total_hoje_ml' => (int)$stmt->fetchColumn()];

        case 'log_food':
            $desc = trim((string)($args['description'] ?? ''));
            if ($desc === '') return ['ok' => false, 'error' => 'descricao vazia'];
            $meal = trim((string)($args['meal_label'] ?? '')) ?: null;
            $pdo->prepare("INSERT INTO food_logs (user_id, description, meal_label, log_date) VALUES (?, ?, ?, ?)")->execute([$userId, $desc, $meal, $today]);
            return ['ok' => true];

        case 'mark_task_done':
            $id = (int)($args['task_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id, recurrence, status FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $task = $stmt->fetch();
            if (!$task) return ['ok' => false, 'error' => 'tarefa nao encontrada'];
            if ($task['recurrence'] === 'once') {
                if ((int)$task['status'] === 1) return ['ok' => true, 'ja_estava_feita' => true];
                $pdo->prepare("UPDATE tasks SET status = 1 WHERE id = ?")->execute([$id]);
                award_points($pdo, $userId, 10, 'Tarefa', $today);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM task_completions WHERE task_id = ? AND done_date = ?");
                $stmt->execute([$id, $today]);
                if ($stmt->fetch()) return ['ok' => true, 'ja_estava_feita' => true];
                $pdo->prepare("INSERT INTO task_completions (task_id, done_date) VALUES (?, ?)")->execute([$id, $today]);
                award_points($pdo, $userId, 10, 'Tarefa', $today);
            }
            return ['ok' => true];

        case 'mark_habit_done':
            $id = (int)($args['habit_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT checked_dates FROM habits WHERE id = ?");
            $stmt->execute([$id]);
            $raw = $stmt->fetchColumn();
            if ($raw === false) return ['ok' => false, 'error' => 'habito nao encontrado'];
            $arr = json_decode($raw ?: '[]', true);
            if (!is_array($arr)) $arr = [];
            if (in_array($today, $arr, true)) return ['ok' => true, 'ja_estava_feito' => true];
            $arr[] = $today;
            $pdo->prepare("UPDATE habits SET checked_dates = ? WHERE id = ?")->execute([json_encode($arr), $id]);
            award_points($pdo, $userId, 10, 'Habito', $today);
            return ['ok' => true];

        case 'create_task':
            $title = trim((string)($args['title'] ?? ''));
            if ($title === '') return ['ok' => false, 'error' => 'titulo vazio'];
            $recurrence = $args['recurrence'] ?? 'once';
            $recurrenceDay = isset($args['recurrence_day']) ? (int)$args['recurrence_day'] : null;
            $dueDate = $args['due_date'] ?? null;
            $pdo->prepare("INSERT INTO tasks (user_id, title, recurrence, recurrence_day, due_date) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, $title, $recurrence, $recurrenceDay, $dueDate]);
            return ['ok' => true];

        case 'log_finance':
            $type = ($args['type'] ?? '') === 'income' ? 'income' : 'expense';
            $amount = (float)($args['amount'] ?? 0);
            $desc = trim((string)($args['description'] ?? ''));
            if ($amount <= 0) return ['ok' => false, 'error' => 'valor invalido'];
            $pdo->prepare("INSERT INTO fin_transactions (user_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, $type, $amount, $desc, $today]);
            return ['ok' => true];

        case 'get_summary':
            $scope = $args['scope'] ?? 'week';
            if ($scope === 'week') {
                $stmt = $pdo->prepare("SELECT log_date, SUM(amount_ml) AS total FROM water_logs
                    WHERE user_id = ? AND log_date >= DATE_SUB(?, INTERVAL 6 DAY) GROUP BY log_date");
                $stmt->execute([$userId, $today]);
                return ['ok' => true, 'agua_por_dia' => $stmt->fetchAll()];
            }
            $month = date('Y-m', strtotime($today));
            $start = $month . '-01';
            $end = date('Y-m-t', strtotime($start));
            $stmt = $pdo->prepare("SELECT type, SUM(amount) AS total FROM fin_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ? GROUP BY type");
            $stmt->execute([$userId, $start, $end]);
            $fin = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT title, target_amount, current_amount, deadline, status FROM goals WHERE user_id = ?");
            $stmt->execute([$userId]);
            return ['ok' => true, 'financas_mes' => $fin, 'metas' => $stmt->fetchAll()];

        default:
            return ['ok' => false, 'error' => 'ferramenta desconhecida'];
    }
}

function call_anthropic(array $messages, array $tools, string $systemPrompt): array {
    $payload = [
        'model' => 'claude-haiku-4-5-20251001',
        'max_tokens' => 1024,
        'system' => $systemPrompt,
        'messages' => $messages,
        'tools' => $tools,
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($curlErr) {
        throw new Exception('Falha de conexao com a IA: ' . $curlErr);
    }
    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new Exception('Resposta invalida da IA.');
    }
    if (isset($data['error'])) {
        throw new Exception('Erro da Anthropic: ' . ($data['error']['message'] ?? 'desconhecido'));
    }
    return $data;
}

try {
    save_message($pdo, $userId, 'user', $userMessage);
    $messages = load_history($pdo, $userId, 17); // inclui a mensagem que acabou de ser salva

    $finalText = '';
    for ($i = 0; $i < 4; $i++) {
        $response = call_anthropic($messages, $tools, $systemPrompt);
        $stopReason = $response['stop_reason'] ?? '';
        $content = $response['content'] ?? [];
        $messages[] = ['role' => 'assistant', 'content' => $content];

        if ($stopReason !== 'tool_use') {
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $finalText .= $block['text'];
                }
            }
            break;
        }

        $toolResults = [];
        foreach ($content as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $result = execute_tool($pdo, $userId, $today, $block['name'], $block['input'] ?? []);
                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $toolResults];
    }

    if ($finalText === '') {
        $finalText = 'Feito!';
    }
    save_message($pdo, $userId, 'assistant', $finalText);

    json_out(['ok' => true, 'reply' => $finalText]);
} catch (Exception $e) {
    error_log('[AI_CHAT] ' . $e->getMessage());
    json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}
