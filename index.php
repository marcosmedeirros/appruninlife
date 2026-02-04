<?php
require_once __DIR__ . '/config.php';

$user_id = 1;

function ensureHabitRemovalsTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS habit_removals (\n        habit_id INT NOT NULL PRIMARY KEY,\n        removed_from DATE NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        KEY idx_removed_from (removed_from)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function getWeekRange(): array {
    $now = new DateTime();
    $dayOfWeek = (int)$now->format('w');
    $daysToMonday = ($dayOfWeek === 0) ? -6 : -($dayOfWeek - 1);
    $monday = (clone $now)->modify("$daysToMonday days");
    $sunday = (clone $monday)->modify('+6 days');
    return [$monday->format('Y-m-d'), $sunday->format('Y-m-d')];
}

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (isset($_GET['api'])) {
    $action = $_GET['api'];
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;

    try {
        ensureHabitRemovalsTable($pdo);

        if ($action === 'get_week_activities') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? AND day_date BETWEEN ? AND ? ORDER BY day_date ASC, status ASC");
            $stmt->execute([$user_id, $start, $end]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_activity') {
            $stmt = $pdo->prepare("INSERT INTO activities (user_id, title, day_date, status) VALUES (?, ?, ?, 0)");
            $stmt->execute([$user_id, $data['title'] ?? '', $data['date'] ?? date('Y-m-d')]);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_activity') {
            $stmt = $pdo->prepare("UPDATE activities SET status = 1 - status WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_habits') {
            $month = $_GET['month'] ?? date('Y-m');
            $monthStart = $month . '-01';
            $stmt = $pdo->prepare("SELECT h.*, hr.removed_from FROM habits h LEFT JOIN habit_removals hr ON hr.habit_id = h.id WHERE hr.removed_from IS NULL OR hr.removed_from > ? ORDER BY h.id DESC");
            $stmt->execute([$monthStart]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_habit') {
            $pdo->prepare("INSERT INTO habits (name, checked_dates) VALUES (?, '[]')")->execute([$data['name'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_habit') {
            $id = $data['id'] ?? null;
            $date = $data['date'] ?? date('Y-m-d');
            $json = $pdo->prepare("SELECT checked_dates FROM habits WHERE id = ?");
            $json->execute([$id]);
            $arr = json_decode($json->fetchColumn() ?: '[]', true) ?: [];
            if (in_array($date, $arr)) {
                $arr = array_values(array_diff($arr, [$date]));
            } else {
                $arr[] = $date;
            }
            $upd = $pdo->prepare("UPDATE habits SET checked_dates = ? WHERE id = ?");
            $upd->execute([json_encode($arr), $id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_workouts_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND workout_date BETWEEN ? AND ? ORDER BY workout_date DESC, id DESC");
            $stmt->execute([$user_id, $start, $end]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_workout') {
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, workout_date, done) VALUES (?, ?, ?, 0)");
            $stmt->execute([$user_id, $data['name'] ?? '', $data['date'] ?? date('Y-m-d')]);
            json_response(['success' => true]);
        }

        if ($action === 'toggle_workout') {
            $stmt = $pdo->prepare("UPDATE workouts SET done = 1 - done WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_runs_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM runs WHERE user_id = ? AND run_date BETWEEN ? AND ? ORDER BY run_date DESC, id DESC");
            $stmt->execute([$user_id, $start, $end]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_run') {
            $stmt = $pdo->prepare("INSERT INTO runs (user_id, title, run_date, distance_km, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $data['title'] ?? 'Corrida',
                $data['date'] ?? date('Y-m-d'),
                $data['distance'] ?? 0,
                $data['notes'] ?? ''
            ]);
            json_response(['success' => true]);
        }

        if ($action === 'get_daily_photo') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $pdo->prepare("SELECT * FROM daily_photos WHERE user_id = ? AND photo_date = ? LIMIT 1");
            $stmt->execute([$user_id, $date]);
            json_response($stmt->fetch() ?: null);
        }

        if ($action === 'save_daily_photo') {
            $date = $data['date'] ?? date('Y-m-d');
            $stmt = $pdo->prepare("INSERT INTO daily_photos (user_id, photo_date, photo_url) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE photo_url = VALUES(photo_url)");
            $stmt->execute([$user_id, $date, $data['photo_url'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'get_daily_message') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $file = __DIR__ . '/mensagens_365.json';

            if (!file_exists($file)) {
                json_response(['error' => 'Arquivo mensagens_365.json não encontrado']);
            }

            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) {
                json_response(['error' => 'Formato inválido do JSON de mensagens']);
            }

            $text = null;
            $matchedDate = null;
            $dayOfYear = (int)date('z', strtotime($date));

            foreach ($json as $item) {
                $itemDate = $item['date'] ?? ($item['dia'] ?? null);
                $itemText = $item['texto'] ?? ($item['mensagem'] ?? ($item['message'] ?? null));
                if ($itemDate && $itemText && $itemDate === $date) {
                    $text = $itemText;
                    $matchedDate = $itemDate;
                    break;
                }
            }

            if ($text === null && isset($json[$dayOfYear])) {
                $item = $json[$dayOfYear];
                $text = $item['texto'] ?? ($item['mensagem'] ?? ($item['message'] ?? (is_string($item) ? $item : null)));
                $matchedDate = $item['date'] ?? ($item['dia'] ?? null);
            }

            if ($text === null) {
                json_response(['error' => 'Mensagem não encontrada para a data informada']);
            }

            json_response([
                'date' => $date,
                'matched_date' => $matchedDate,
                'text' => $text
            ]);
        }

        if ($action === 'save_finance') {
            $type = ($data['type'] === 'entrada' || $data['type'] === 'income') ? 'income' : 'expense';
            $stmt = $pdo->prepare("INSERT INTO finances (user_id, type, amount, description, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $data['amount'] ?? 0, $data['desc'] ?? '', $data['date'] ?? date('Y-m-d')]);
            json_response(['success' => true]);
        }

        if ($action === 'get_finance_week') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM finances WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$user_id, $start, $end]);
            $rows = $stmt->fetchAll();
            $income = 0; $expense = 0;
            foreach ($rows as $row) {
                $amount = (float)$row['amount'];
                if ($row['type'] === 'income') {
                    $income += $amount;
                } else {
                    $expense += $amount;
                }
            }
            json_response(['income' => $income, 'expense' => $expense, 'items' => $rows]);
        }

        if ($action === 'get_events_week') {
            [$start, $end] = getWeekRange();
            $startDateTime = $start . ' 00:00:00';
            $endDateTime = $end . ' 23:59:59';
            $stmt = $pdo->prepare("SELECT id, title, start_date, description FROM events WHERE user_id = ? AND start_date BETWEEN ? AND ? ORDER BY start_date ASC");
            $stmt->execute([$user_id, $startDateTime, $endDateTime]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'get_rules') {
            $stmt = $pdo->prepare("SELECT * FROM life_rules WHERE user_id = ? ORDER BY id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_rule') {
            $stmt = $pdo->prepare("INSERT INTO life_rules (user_id, rule_text) VALUES (?, ?)");
            $stmt->execute([$user_id, $data['rule_text'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'delete_rule') {
            $stmt = $pdo->prepare("DELETE FROM life_rules WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_routine_items') {
            $stmt = $pdo->prepare("SELECT * FROM routine_items WHERE user_id = ? ORDER BY routine_time ASC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'save_routine_item') {
            $stmt = $pdo->prepare("INSERT INTO routine_items (user_id, routine_time, activity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $data['time'] ?? '08:00', $data['activity'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'delete_routine_item') {
            $stmt = $pdo->prepare("DELETE FROM routine_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'save_goal') {
            $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, difficulty, status, goal_type) VALUES (?, ?, 'media', 0, 'geral')");
            $stmt->execute([$user_id, $data['title'] ?? '']);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals') {
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND goal_type = 'geral' ORDER BY status ASC, id DESC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
        }

        if ($action === 'toggle_goal') {
            $stmt = $pdo->prepare("UPDATE goals SET status = 1 - status, completed_at = CASE WHEN status = 0 THEN NOW() ELSE NULL END WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user_id]);
            json_response(['success' => true]);
        }

        if ($action === 'get_goals_week_done') {
            [$start, $end] = getWeekRange();
            $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? AND status = 1 AND completed_at BETWEEN ? AND ? ORDER BY completed_at DESC");
            $stmt->execute([$user_id, $start . ' 00:00:00', $end . ' 23:59:59']);
            json_response($stmt->fetchAll());
        }

        json_response(['error' => 'Ação não encontrada']);
    } catch (Exception $e) {
        http_response_code(500);
        json_response(['error' => $e->getMessage()]);
    }
}

$page_title = 'Lifestyle - Caderno Semanal';
include __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        color-scheme: dark;
        --bg: #070707;
        --panel: #0e0e0f;
        --panel-2: #151515;
        --text: #ffffff;
        --muted: #b9b9b9;
        --red: #ff2d2d;
        --red-soft: rgba(255, 45, 45, 0.18);
        --shadow: 0 18px 45px rgba(0, 0, 0, 0.45);
        --border: 1px solid rgba(255, 255, 255, 0.08);
        --radius: 18px;
    }
    * { box-sizing: border-box; }
    body {
        background: radial-gradient(circle at top, rgba(255,45,45,0.08), transparent 45%), var(--bg);
        color: var(--text);
        font-family: 'Outfit', sans-serif;
        margin: 0;
    }
    .app {
        max-width: 1300px;
        margin: 0 auto;
        padding: 28px 20px 70px;
    }
    .app-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        padding: 18px 20px;
        border-radius: var(--radius);
        background: linear-gradient(120deg, rgba(255,45,45,0.15), rgba(255,255,255,0.02));
        border: var(--border);
        box-shadow: var(--shadow);
    }
    .app-title h1 {
        margin: 0;
        font-size: 2.1rem;
        letter-spacing: 0.5px;
    }
    .app-title p {
        margin: 6px 0 0;
        color: var(--muted);
    }
    .chip {
        padding: 6px 12px;
        border-radius: 999px;
        background: var(--red-soft);
        color: var(--red);
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
    }
    .app-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
    }
    .tag {
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        font-size: 0.8rem;
        color: var(--muted);
    }
    .section {
        margin-top: 22px;
    }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .section-header h2 {
        font-size: 1.2rem;
        margin: 0;
        font-weight: 600;
    }
    .section-header span {
        color: var(--muted);
        font-size: 0.85rem;
    }
    .grid {
        display: grid;
        gap: 16px;
    }
    .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    .grid-3 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
    .card {
        background: linear-gradient(155deg, var(--panel), var(--panel-2));
        border: var(--border);
        border-radius: var(--radius);
        padding: 18px;
        box-shadow: var(--shadow);
        min-height: 160px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .card-header h3 {
        font-size: 1.05rem;
        margin: 0;
        font-weight: 600;
    }
    .btn {
        background: transparent;
        border: 1px solid var(--red);
        color: var(--red);
        padding: 8px 14px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all .2s ease;
    }
    .btn:hover { background: var(--red); color: #000; }
    .btn-solid { background: var(--red); color: #000; border: none; }
    .list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .list-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .list-item small { color: var(--muted); }
    .muted { color: var(--muted); }
    .divider { height: 1px; background: rgba(255,255,255,0.08); margin: 12px 0; }
    .modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.75);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 20px;
        backdrop-filter: blur(6px);
    }
    .modal.active { display: flex; }
    .modal-content {
        width: min(520px, 95vw);
        background: #0c0c0c;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .modal-header h4 { font-weight: 600; margin: 0; }
    .modal-close { background: transparent; border: none; color: var(--muted); font-size: 1.2rem; }
    .input {
        width: 100%;
        background: #111;
        color: #fff;
        border: 1px solid rgba(255,255,255,0.1);
        padding: 10px 12px;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .habit-grid { overflow-x: auto; padding-bottom: 6px; }
    .habit-table { width: 100%; border-collapse: collapse; min-width: 700px; }
    .habit-table th, .habit-table td {
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding: 8px;
        text-align: center;
        font-size: 0.75rem;
    }
    .habit-table th:first-child, .habit-table td:first-child {
        text-align: left;
        font-weight: 600;
        color: #fff;
    }
    .check {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .check.active { background: var(--red); color: #000; border-color: var(--red); }
    .photo-box {
        width: 100%;
        height: 210px;
        border-radius: 16px;
        background: #111;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .photo-box img { width: 100%; height: 100%; object-fit: cover; }
    @media (max-width: 768px) {
        .app-header { align-items: flex-start; }
        .app-title h1 { font-size: 1.6rem; }
        .app-actions { align-items: flex-start; }
    }
</style>

<main class="app">
    <header class="app-header">
        <div class="app-title">
            <span class="chip">Caderno Lifestyle</span>
            <h1>Controle semanal da vida</h1>
            <p>Um painel completo para hábitos, treinos, finanças e rotinas.</p>
        </div>
        <div class="app-actions">
            <div class="tag" id="weekRange">Semana atual</div>
            <span class="muted">Atualizado automaticamente</span>
        </div>
    </header>

    <section class="section">
        <div class="section-header">
            <h2>Semana em foco</h2>
            <span>Resumo dos próximos passos</span>
        </div>
        <div class="grid grid-3">
        <div class="card">
            <div class="card-header">
                <h3>Atividades da semana</h3>
                <button class="btn" data-modal="modalActivity">Adicionar</button>
            </div>
            <div class="list" id="activitiesList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Mensagem do dia</h3>
                <span class="tag" id="messageDate">Hoje</span>
            </div>
            <p id="dailyMessage" class="muted"></p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Finanças da semana</h3>
                <button class="btn" data-modal="modalFinance">Cadastrar</button>
            </div>
            <div>
                <p><strong>Entrada:</strong> R$ <span id="financeIncome">0</span></p>
                <p><strong>Saída:</strong> R$ <span id="financeExpense">0</span></p>
                <div class="divider"></div>
                <div id="financeList" class="list"></div>
            </div>
        </div>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Performance diária</h2>
            <span>Hábitos e treino</span>
        </div>
        <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3>Habit Tracker</h3>
                <button class="btn" data-modal="modalHabit">Adicionar</button>
            </div>
            <div class="habit-grid">
                <table class="habit-table" id="habitTable"></table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Check-in de treino</h3>
                <button class="btn" data-modal="modalWorkout">Adicionar</button>
            </div>
            <div class="list" id="workoutList"></div>
        </div>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Registros essenciais</h2>
            <span>Corridas, fotos e agenda</span>
        </div>
        <div class="grid grid-3">
        <div class="card">
            <div class="card-header">
                <h3>Cadastro de corrida</h3>
                <button class="btn" data-modal="modalRun">Cadastrar</button>
            </div>
            <div class="list" id="runList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Foto do dia</h3>
                <button class="btn" data-modal="modalPhoto">Cadastrar</button>
            </div>
            <div class="photo-box" id="photoBox">Sem foto hoje</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Eventos (Google Agenda)</h3>
            </div>
            <div class="list" id="eventsList"></div>
        </div>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Planejamento e regras</h2>
            <span>Rotina, regras e metas</span>
        </div>
        <div class="grid grid-3">
        <div class="card">
            <div class="card-header">
                <h3>Rotina do dia</h3>
                <button class="btn" data-modal="modalRoutine">Cadastrar</button>
            </div>
            <div class="list" id="routineList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Regras de vida</h3>
                <button class="btn" data-modal="modalRule">Adicionar</button>
            </div>
            <div class="list" id="rulesList"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Metas</h3>
                <div style="display:flex; gap:8px;">
                    <button class="btn" data-modal="modalGoal">Cadastrar</button>
                    <button class="btn" data-modal="modalGoalsView">Ver metas</button>
                </div>
            </div>
            <p class="muted" id="goalsWeekDone">Sem metas concluídas nesta semana.</p>
        </div>
        </div>
    </section>
</main>

<div class="modal" id="modalActivity">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova atividade da semana</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="activityTitle" placeholder="O que precisa fazer?">
        <input class="input" id="activityDate" type="date">
        <button class="btn btn-solid" id="saveActivity">Salvar</button>
    </div>
</div>

<div class="modal" id="modalHabit">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo hábito</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="habitName" placeholder="Nome do hábito">
        <button class="btn btn-solid" id="saveHabit">Salvar</button>
    </div>
</div>

<div class="modal" id="modalWorkout">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo check-in de treino</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="workoutName" placeholder="Treino (ex: peito + cardio)">
        <input class="input" id="workoutDate" type="date">
        <button class="btn btn-solid" id="saveWorkout">Salvar</button>
    </div>
</div>

<div class="modal" id="modalRun">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo registro de corrida</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="runTitle" placeholder="Título">
        <input class="input" id="runDate" type="date">
        <input class="input" id="runDistance" type="number" step="0.1" placeholder="Distância (km)">
        <input class="input" id="runNotes" placeholder="Observações">
        <button class="btn btn-solid" id="saveRun">Salvar</button>
    </div>
</div>

<div class="modal" id="modalPhoto">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Foto do dia</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="photoDate" type="date">
        <input class="input" id="photoUrl" placeholder="URL da foto (ou cole base64)">
        <input class="input" id="photoFile" type="file" accept="image/*">
        <button class="btn btn-solid" id="savePhoto">Salvar</button>
    </div>
</div>

<div class="modal" id="modalFinance">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Novo lançamento financeiro</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="financeAmount" type="number" step="0.01" placeholder="Valor">
        <select class="input" id="financeType">
            <option value="saida">Saída</option>
            <option value="entrada">Entrada</option>
        </select>
        <input class="input" id="financeDate" type="date">
        <button class="btn btn-solid" id="saveFinance">Salvar</button>
    </div>
</div>

<div class="modal" id="modalRoutine">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova rotina</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="routineTime" type="time">
        <input class="input" id="routineActivity" placeholder="Atividade">
        <button class="btn btn-solid" id="saveRoutine">Salvar</button>
    </div>
</div>

<div class="modal" id="modalRule">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova regra de vida</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="ruleText" placeholder="Escreva a regra">
        <button class="btn btn-solid" id="saveRule">Salvar</button>
    </div>
</div>

<div class="modal" id="modalGoal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Nova meta</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <input class="input" id="goalTitle" placeholder="Título da meta">
        <button class="btn btn-solid" id="saveGoal">Salvar</button>
    </div>
</div>

<div class="modal" id="modalGoalsView">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Metas cadastradas</h4>
            <button class="modal-close" data-close>×</button>
        </div>
        <div class="list" id="goalsList"></div>
    </div>
</div>

<script>
    const api = (action, payload = {}, method = 'POST') => {
        const opts = { method };
        if (method !== 'GET') {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(payload);
        }
        return fetch(`?api=${action}`, opts).then(r => r.json());
    };

    const openModal = (id) => document.getElementById(id)?.classList.add('active');
    const closeModals = () => document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));

    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-modal]')) {
            openModal(e.target.dataset.modal);
        }
        if (e.target.matches('[data-close]')) {
            closeModals();
        }
        if (e.target.classList.contains('modal')) {
            closeModals();
        }
    });

    const formatDate = (dateStr) => {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: '2-digit' });
    };

    const setDefaultDates = () => {
        const today = new Date().toISOString().slice(0, 10);
        ['activityDate','workoutDate','runDate','photoDate','financeDate'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = today;
        });
    };

    const loadActivities = async () => {
        const list = await api('get_week_activities', {}, 'GET');
        const container = document.getElementById('activitiesList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nada por aqui. Adicione uma atividade.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.title}</strong><br>
                    <small>${formatDate(item.day_date)}</small>
                </div>
                <button class="btn" data-id="${item.id}" data-action="toggle-activity">${item.status == 1 ? 'Feito' : 'Check'}</button>
            `;
            container.appendChild(row);
        });
    };

    const loadHabits = async () => {
        const month = new Date().toISOString().slice(0, 7);
        const habits = await fetch(`?api=get_habits&month=${month}`).then(r => r.json());
        const table = document.getElementById('habitTable');
        const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
        let header = '<tr><th>Hábito</th>';
        for (let d = 1; d <= daysInMonth; d++) header += `<th>${d}</th>`;
        header += '</tr>';
        let body = '';
        habits.forEach(habit => {
            const checks = JSON.parse(habit.checked_dates || '[]');
            body += `<tr><td>${habit.name}</td>`;
            for (let d = 1; d <= daysInMonth; d++) {
                const date = `${month}-${String(d).padStart(2, '0')}`;
                const isActive = checks.includes(date);
                body += `<td><span class="check ${isActive ? 'active' : ''}" data-habit="${habit.id}" data-date="${date}">${isActive ? '✓' : ''}</span></td>`;
            }
            body += '</tr>';
        });
        table.innerHTML = header + body;
    };

    const loadWorkouts = async () => {
        const list = await api('get_workouts_week', {}, 'GET');
        const container = document.getElementById('workoutList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nenhum treino registrado.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.name}</strong><br>
                    <small>${formatDate(item.workout_date)}</small>
                </div>
                <button class="btn" data-id="${item.id}" data-action="toggle-workout">${item.done == 1 ? 'Feito' : 'Check'}</button>
            `;
            container.appendChild(row);
        });
    };

    const loadRuns = async () => {
        const list = await api('get_runs_week', {}, 'GET');
        const container = document.getElementById('runList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nenhuma corrida registrada.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.title}</strong><br>
                    <small>${formatDate(item.run_date)} • ${item.distance_km} km</small>
                </div>
                <span class="tag">${item.notes || 'Ok'}</span>
            `;
            container.appendChild(row);
        });
    };

    const loadPhoto = async () => {
        const data = await api('get_daily_photo', {}, 'GET');
        const box = document.getElementById('photoBox');
        if (data && data.photo_url) {
            box.innerHTML = `<img src="${data.photo_url}" alt="Foto do dia">`;
        } else {
            box.innerHTML = 'Sem foto hoje';
        }
    };

    const loadMessage = async () => {
        const data = await api('get_daily_message', {}, 'GET');
        document.getElementById('dailyMessage').textContent = data.text || 'Sem mensagem disponível.';
        document.getElementById('messageDate').textContent = new Date().toLocaleDateString('pt-BR');
    };

    const loadFinance = async () => {
        const data = await api('get_finance_week', {}, 'GET');
        document.getElementById('financeIncome').textContent = data.income.toFixed(2);
        document.getElementById('financeExpense').textContent = data.expense.toFixed(2);
        const list = document.getElementById('financeList');
        list.innerHTML = '';
        data.items.slice(0, 4).forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.type === 'income' ? 'Entrada' : 'Saída'}</strong><br>
                    <small>${formatDate(item.created_at.slice(0,10))}</small>
                </div>
                <span class="tag">R$ ${parseFloat(item.amount).toFixed(2)}</span>
            `;
            list.appendChild(row);
        });
    };

    const loadEvents = async () => {
        const list = await api('get_events_week', {}, 'GET');
        const container = document.getElementById('eventsList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Sem eventos na semana.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.title}</strong><br>
                    <small>${new Date(item.start_date).toLocaleString('pt-BR')}</small>
                </div>
            `;
            container.appendChild(row);
        });
    };

    const loadRules = async () => {
        const list = await api('get_rules', {}, 'GET');
        const container = document.getElementById('rulesList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Adicione uma regra pessoal.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>${item.rule_text}</div>
                <button class="btn" data-id="${item.id}" data-action="delete-rule">Apagar</button>
            `;
            container.appendChild(row);
        });
    };

    const loadRoutine = async () => {
        const list = await api('get_routine_items', {}, 'GET');
        const container = document.getElementById('routineList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Cadastre sua rotina do dia.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>
                    <strong>${item.routine_time.slice(0,5)}</strong> - ${item.activity}
                </div>
                <button class="btn" data-id="${item.id}" data-action="delete-routine">Apagar</button>
            `;
            container.appendChild(row);
        });
    };

    const loadGoals = async () => {
        const list = await api('get_goals', {}, 'GET');
        const container = document.getElementById('goalsList');
        container.innerHTML = '';
        if (!list.length) {
            container.innerHTML = '<div class="muted">Nenhuma meta cadastrada.</div>';
            return;
        }
        list.forEach(item => {
            const row = document.createElement('div');
            row.className = 'list-item';
            row.innerHTML = `
                <div>${item.title}</div>
                <button class="btn" data-id="${item.id}" data-action="toggle-goal">${item.status == 1 ? 'Feita' : 'Concluir'}</button>
            `;
            container.appendChild(row);
        });
    };

    const loadGoalsWeekDone = async () => {
        const list = await api('get_goals_week_done', {}, 'GET');
        const el = document.getElementById('goalsWeekDone');
        if (!list.length) {
            el.textContent = 'Sem metas concluídas nesta semana.';
            return;
        }
        el.textContent = `Metas concluídas na semana: ${list.length}`;
    };

    document.addEventListener('click', async (e) => {
        if (e.target.matches('.check[data-habit]')) {
            await api('toggle_habit', { id: e.target.dataset.habit, date: e.target.dataset.date });
            loadHabits();
        }
        if (e.target.matches('[data-action="toggle-activity"]')) {
            await api('toggle_activity', { id: e.target.dataset.id });
            loadActivities();
        }
        if (e.target.matches('[data-action="toggle-workout"]')) {
            await api('toggle_workout', { id: e.target.dataset.id });
            loadWorkouts();
        }
        if (e.target.matches('[data-action="delete-rule"]')) {
            await api('delete_rule', { id: e.target.dataset.id });
            loadRules();
        }
        if (e.target.matches('[data-action="delete-routine"]')) {
            await api('delete_routine_item', { id: e.target.dataset.id });
            loadRoutine();
        }
        if (e.target.matches('[data-action="toggle-goal"]')) {
            await api('toggle_goal', { id: e.target.dataset.id });
            loadGoals();
            loadGoalsWeekDone();
        }
    });

    document.getElementById('saveActivity').addEventListener('click', async () => {
        await api('save_activity', { title: activityTitle.value, date: activityDate.value });
        closeModals();
        activityTitle.value = '';
        loadActivities();
    });

    document.getElementById('saveHabit').addEventListener('click', async () => {
        await api('save_habit', { name: habitName.value });
        closeModals();
        habitName.value = '';
        loadHabits();
    });

    document.getElementById('saveWorkout').addEventListener('click', async () => {
        await api('save_workout', { name: workoutName.value, date: workoutDate.value });
        closeModals();
        workoutName.value = '';
        loadWorkouts();
    });

    document.getElementById('saveRun').addEventListener('click', async () => {
        await api('save_run', {
            title: runTitle.value,
            date: runDate.value,
            distance: runDistance.value,
            notes: runNotes.value
        });
        closeModals();
        runTitle.value = '';
        runDistance.value = '';
        runNotes.value = '';
        loadRuns();
    });

    const readFileAsDataUrl = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });

    document.getElementById('savePhoto').addEventListener('click', async () => {
        let photo = photoUrl.value;
        if (!photo && photoFile.files[0]) {
            photo = await readFileAsDataUrl(photoFile.files[0]);
        }
        await api('save_daily_photo', { date: photoDate.value, photo_url: photo });
        closeModals();
        photoUrl.value = '';
        photoFile.value = '';
        loadPhoto();
    });

    document.getElementById('saveFinance').addEventListener('click', async () => {
        await api('save_finance', { amount: financeAmount.value, type: financeType.value, date: financeDate.value });
        closeModals();
        financeAmount.value = '';
        loadFinance();
    });

    document.getElementById('saveRoutine').addEventListener('click', async () => {
        await api('save_routine_item', { time: routineTime.value, activity: routineActivity.value });
        closeModals();
        routineActivity.value = '';
        loadRoutine();
    });

    document.getElementById('saveRule').addEventListener('click', async () => {
        await api('save_rule', { rule_text: ruleText.value });
        closeModals();
        ruleText.value = '';
        loadRules();
    });

    document.getElementById('saveGoal').addEventListener('click', async () => {
        await api('save_goal', { title: goalTitle.value });
        closeModals();
        goalTitle.value = '';
        loadGoals();
    });

    const loadWeekRange = () => {
        const now = new Date();
        const day = now.getDay();
        const diffToMonday = day === 0 ? -6 : 1 - day;
        const monday = new Date(now);
        monday.setDate(now.getDate() + diffToMonday);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        const text = `${monday.toLocaleDateString('pt-BR')} - ${sunday.toLocaleDateString('pt-BR')}`;
        document.getElementById('weekRange').textContent = text;
    };

    const init = () => {
        setDefaultDates();
        loadWeekRange();
        loadActivities();
        loadHabits();
        loadWorkouts();
        loadRuns();
        loadPhoto();
        loadMessage();
        loadFinance();
        loadEvents();
        loadRules();
        loadRoutine();
        loadGoals();
        loadGoalsWeekDone();
    };

    init();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
