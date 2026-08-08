<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/encrypt.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $role = $_GET['role'] ?? 'teacher';
        $schoolId = $_GET['school_id'] ?? $_SESSION['user']['school_id'];
        
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.role, u.is_active, u.class_id,
                   CONCAT(c.grade, c.letter) as class_name
            FROM users u
            LEFT JOIN classes c ON u.class_id = c.id
            WHERE u.school_id = ? AND u.role = ?
            ORDER BY u.name
        ");
        $stmt->execute([$schoolId, $role]);
        $users = $stmt->fetchAll();
        
        echo json_encode($users);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $schoolId = $data['school_id'] ?? $_SESSION['user']['school_id'];
        
        $passwordHash = Encrypt::hashPassword($data['password']);
        
        $stmt = $conn->prepare("
            INSERT INTO users (email, password_hash, name, role, school_id, class_id, min_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $data['email'],
            $passwordHash,
            $data['name'],
            $data['role'],
            $schoolId,
            $data['class_id'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
        break;

    case 'toggle':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>