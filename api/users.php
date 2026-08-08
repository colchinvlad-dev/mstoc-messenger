<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$action = $_GET['action'] ?? '';
$userId = $_SESSION['user']['id'];

switch ($action) {
    case 'profile':
        $targetId = (int)($_GET['user_id'] ?? $userId);

        $stmt = $conn->prepare("
            SELECT id, name, email, role, school_id, class_id, avatar_type, avatar_url, 
                   is_online, last_seen, privacy_last_seen, privacy_messages
            FROM users WHERE id = ?
        ");
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        echo json_encode(['success' => true, 'user' => $user]);
        break;

    case 'online':
        $data = json_decode(file_get_contents('php://input'), true);
        $isOnline = (bool)($data['is_online'] ?? false);

        $stmt = $conn->prepare("UPDATE users SET is_online = ?, last_seen = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$isOnline ? 1 : 0, $userId]);

        echo json_encode(['success' => true]);
        break;

    case 'search':
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }

        $schoolId = $_SESSION['user']['school_id'] ?? null;
        
        if ($schoolId) {
            $stmt = $conn->prepare("
                SELECT id, name, role, is_online
                FROM users
                WHERE name LIKE ? AND id != ? AND is_active = 1 AND school_id = ?
                LIMIT 20
            ");
            $stmt->execute(["%{$query}%", $userId, $schoolId]);
        } else {
            $stmt = $conn->prepare("
                SELECT id, name, role, is_online
                FROM users
                WHERE name LIKE ? AND id != ? AND is_active = 1
                LIMIT 20
            ");
            $stmt->execute(["%{$query}%", $userId]);
        }
        
        $users = $stmt->fetchAll();
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'list':
        // Для админ-панели
        if (!in_array($_SESSION['user']['role'], ['admin', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        
        $role = $_GET['role'] ?? 'teacher';
        $schoolId = $_GET['school_id'] ?? $_SESSION['user']['school_id'];
        
        $stmt = $conn->prepare("
            SELECT id, name, email, role, is_active, class_id,
                   (SELECT CONCAT(grade, letter) FROM classes WHERE id = users.class_id) as class_name
            FROM users
            WHERE school_id = ? AND role = ?
            ORDER BY name
        ");
        $stmt->execute([$schoolId, $role]);
        $users = $stmt->fetchAll();
        
        echo json_encode($users);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>