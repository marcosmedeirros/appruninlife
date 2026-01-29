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

$stmt = $pdo->prepare('SELECT id, name, email, points, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Usuário não encontrado.']);
    exit;
}

if ($action === 'get') {
    $data = defaultUserData();
    $data['points'] = (int) $user['points'];

    $profileStmt = $pdo->prepare('SELECT display_name, avatar_url, bio, focus_area FROM profiles WHERE user_id = ? LIMIT 1');
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();
    $data['profile'] = [
        'displayName' => $profile['display_name'] ?? $user['name'],
        'avatarUrl' => $profile['avatar_url'] ?? '',
        'bio' => $profile['bio'] ?? '',
        'focusArea' => $profile['focus_area'] ?? '',
    ];

    $categories = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ? ORDER BY id ASC');
    $categories->execute([$userId]);
    $data['categories'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'name' => $row['name'],
    ], $categories->fetchAll());

    $tasks = $pdo->prepare('SELECT id, category_id, title, type, scheduled_at, completed FROM tasks WHERE user_id = ? ORDER BY scheduled_at ASC, id ASC');
    $tasks->execute([$userId]);
    $data['tasks'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'type' => $row['type'],
        'categoryId' => $row['category_id'] ? (string) $row['category_id'] : null,
        'date' => $row['scheduled_at'],
        'completed' => (bool) $row['completed'],
    ], $tasks->fetchAll());

    $goals = $pdo->prepare('SELECT id, title, description, deadline, completed FROM goals WHERE user_id = ? ORDER BY id DESC');
    $goals->execute([$userId]);
    $data['goals'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'deadline' => $row['deadline'],
        'completed' => (bool) $row['completed'],
    ], $goals->fetchAll());

    $habits = $pdo->prepare('SELECT id, title, motivation FROM habits WHERE user_id = ? ORDER BY id DESC');
    $habits->execute([$userId]);
    $habitRows = $habits->fetchAll();
    $data['habits'] = [];
    foreach ($habitRows as $habit) {
        $logs = $pdo->prepare('SELECT log_date FROM habit_logs WHERE habit_id = ? ORDER BY log_date DESC');
        $logs->execute([$habit['id']]);
        $data['habits'][] = [
            'id' => (string) $habit['id'],
            'title' => $habit['title'],
            'motivation' => $habit['motivation'],
            'logs' => array_map(fn($row) => $row['log_date'], $logs->fetchAll()),
        ];
    }

    $workouts = $pdo->prepare('SELECT id, title, duration, intensity, completed FROM workouts WHERE user_id = ? ORDER BY id DESC');
    $workouts->execute([$userId]);
    $data['workouts'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'duration' => (int) $row['duration'],
        'intensity' => $row['intensity'],
        'completed' => (bool) $row['completed'],
    ], $workouts->fetchAll());

    $logs = $pdo->prepare('SELECT id, title, points, type, created_at FROM logs WHERE user_id = ? ORDER BY created_at DESC');
    $logs->execute([$userId]);
    $data['logs'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'points' => (int) $row['points'],
        'type' => $row['type'],
        'date' => $row['created_at'],
    ], $logs->fetchAll());

    $achievements = $pdo->prepare('SELECT id, title, description, points_reward FROM achievements ORDER BY id ASC');
    $achievements->execute();
    $unlockedStmt = $pdo->prepare('SELECT achievement_id FROM user_achievements WHERE user_id = ?');
    $unlockedStmt->execute([$userId]);
    $unlockedIds = array_column($unlockedStmt->fetchAll(), 'achievement_id');
    $data['achievements'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'points' => (int) $row['points_reward'],
        'unlocked' => in_array($row['id'], $unlockedIds, false),
    ], $achievements->fetchAll());

    $today = date('Y-m-d');
    $missions = $pdo->prepare('SELECT id, title, description, points_reward FROM daily_missions WHERE active_date = ? ORDER BY id ASC');
    $missions->execute([$today]);
    $missionRows = $missions->fetchAll();
    $userMissionStmt = $pdo->prepare('SELECT mission_id, completed FROM user_missions WHERE user_id = ?');
    $userMissionStmt->execute([$userId]);
    $userMissionMap = [];
    foreach ($userMissionStmt->fetchAll() as $row) {
        $userMissionMap[$row['mission_id']] = (bool) $row['completed'];
    }
    $data['missions'] = array_map(fn($row) => [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'points' => (int) $row['points_reward'],
        'completed' => $userMissionMap[$row['id']] ?? false,
    ], $missionRows);

    $ranking = $pdo->query('SELECT name, points FROM users ORDER BY points DESC, id ASC LIMIT 10');
    $data['ranking'] = array_map(fn($row) => [
        'name' => $row['name'],
        'points' => (int) $row['points'],
    ], $ranking->fetchAll());

    $admin = null;
    if ($user['role'] === 'admin') {
        $stats = $pdo->query('SELECT COUNT(*) AS total_users FROM users');
        $statsRow = $stats->fetch();
        $admin = [
            'totalUsers' => (int) ($statsRow['total_users'] ?? 0),
        ];
    }

    echo json_encode([
        'ok' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ],
        'data' => $data,
        'admin' => $admin,
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

    $data = $payload['data'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE users SET points = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([(int) ($data['points'] ?? 0), $userId]);

        $profile = $data['profile'] ?? [];
        $profileStmt = $pdo->prepare('INSERT INTO profiles (user_id, display_name, avatar_url, bio, focus_area, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), avatar_url = VALUES(avatar_url), bio = VALUES(bio), focus_area = VALUES(focus_area), updated_at = NOW()');
        $profileStmt->execute([
            $userId,
            $profile['displayName'] ?? $user['name'],
            $profile['avatarUrl'] ?? null,
            $profile['bio'] ?? null,
            $profile['focusArea'] ?? null,
        ]);

        $pdo->prepare('DELETE FROM categories WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM tasks WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM goals WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM habits WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM workouts WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM logs WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM user_achievements WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM user_missions WHERE user_id = ?')->execute([$userId]);

        $categoryMap = [];
        if (!empty($data['categories'])) {
            $stmt = $pdo->prepare('INSERT INTO categories (user_id, name) VALUES (?, ?)');
            foreach ($data['categories'] as $category) {
                $stmt->execute([$userId, $category['name'] ?? 'Categoria']);
                $categoryMap[$category['id']] = $pdo->lastInsertId();
            }
        }

        if (!empty($data['tasks'])) {
            $stmt = $pdo->prepare('INSERT INTO tasks (user_id, category_id, title, type, scheduled_at, completed) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($data['tasks'] as $task) {
                $categoryId = $task['categoryId'] ?? null;
                $stmt->execute([
                    $userId,
                    $categoryId && isset($categoryMap[$categoryId]) ? $categoryMap[$categoryId] : null,
                    $task['title'] ?? '',
                    $task['type'] ?? 'Atividade',
                    $task['date'] ?? null,
                    !empty($task['completed']) ? 1 : 0,
                ]);
            }
        }

        if (!empty($data['goals'])) {
            $stmt = $pdo->prepare('INSERT INTO goals (user_id, title, description, deadline, completed) VALUES (?, ?, ?, ?, ?)');
            foreach ($data['goals'] as $goal) {
                $stmt->execute([
                    $userId,
                    $goal['title'] ?? '',
                    $goal['description'] ?? null,
                    $goal['deadline'] ?? null,
                    !empty($goal['completed']) ? 1 : 0,
                ]);
            }
        }

        if (!empty($data['habits'])) {
            $stmt = $pdo->prepare('INSERT INTO habits (user_id, title, motivation) VALUES (?, ?, ?)');
            $logStmt = $pdo->prepare('INSERT INTO habit_logs (habit_id, log_date) VALUES (?, ?)');
            foreach ($data['habits'] as $habit) {
                $stmt->execute([$userId, $habit['title'] ?? '', $habit['motivation'] ?? null]);
                $habitId = $pdo->lastInsertId();
                if (!empty($habit['logs'])) {
                    foreach ($habit['logs'] as $logDate) {
                        $logStmt->execute([$habitId, $logDate]);
                    }
                }
            }
        }

        if (!empty($data['workouts'])) {
            $stmt = $pdo->prepare('INSERT INTO workouts (user_id, title, duration, intensity, completed) VALUES (?, ?, ?, ?, ?)');
            foreach ($data['workouts'] as $workout) {
                $stmt->execute([
                    $userId,
                    $workout['title'] ?? '',
                    (int) ($workout['duration'] ?? 0),
                    $workout['intensity'] ?? 'Leve',
                    !empty($workout['completed']) ? 1 : 0,
                ]);
            }
        }

        if (!empty($data['logs'])) {
            $stmt = $pdo->prepare('INSERT INTO logs (user_id, title, points, type, created_at) VALUES (?, ?, ?, ?, ?)');
            foreach ($data['logs'] as $log) {
                $stmt->execute([
                    $userId,
                    $log['title'] ?? '',
                    (int) ($log['points'] ?? 0),
                    $log['type'] ?? 'Sistema',
                    $log['date'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }

        if (!empty($data['achievements'])) {
            $stmt = $pdo->prepare('INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, NOW())');
            foreach ($data['achievements'] as $achievement) {
                if (!empty($achievement['unlocked'])) {
                    $stmt->execute([$userId, (int) $achievement['id']]);
                }
            }
        }

        if (!empty($data['missions'])) {
            $stmt = $pdo->prepare('INSERT INTO user_missions (user_id, mission_id, completed, completed_at) VALUES (?, ?, ?, ?)');
            foreach ($data['missions'] as $mission) {
                $completed = !empty($mission['completed']);
                $stmt->execute([
                    $userId,
                    (int) $mission['id'],
                    $completed ? 1 : 0,
                    $completed ? date('Y-m-d H:i:s') : null,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'admin_save') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Sem permissão.']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Dados inválidos.']);
        exit;
    }

    if (!empty($payload['mission'])) {
        $mission = $payload['mission'];
        $stmt = $pdo->prepare('INSERT INTO daily_missions (title, description, points_reward, active_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $mission['title'] ?? 'Missão diária',
            $mission['description'] ?? null,
            (int) ($mission['points'] ?? 0),
            $mission['date'] ?? date('Y-m-d'),
        ]);
    }

    if (!empty($payload['achievement'])) {
        $achievement = $payload['achievement'];
        $stmt = $pdo->prepare('INSERT INTO achievements (title, description, points_reward) VALUES (?, ?, ?)');
        $stmt->execute([
            $achievement['title'] ?? 'Conquista',
            $achievement['description'] ?? null,
            (int) ($achievement['points'] ?? 0),
        ]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Ação desconhecida.']);
