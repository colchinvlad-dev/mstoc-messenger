<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

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
        $schoolId = $_GET['school_id'] ?? $_SESSION['user']['school_id'];
        
        $stmt = $conn->prepare("
            SELECT ic.*, CONCAT(c.grade, c.letter) as class_name
            FROM invitation_codes ic
            LEFT JOIN classes c ON ic.class_id = c.id
            WHERE ic.school_id = ?
            ORDER BY ic.created_at DESC
        ");
        $stmt->execute([$schoolId]);
        $invitations = $stmt->fetchAll();
        
        echo json_encode($invitations);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $schoolId = $data['school_id'] ?? $_SESSION['user']['school_id'];
        
        // Генерируем уникальный код
        $code = 'INV' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        $stmt = $conn->prepare("
            INSERT INTO invitation_codes (code, app_id, app_code, school_id, class_id, user_type, created_by, max_uses, expires_at)
            VALUES (?, 5, '725821', ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([
            $code,
            $schoolId,
            $data['class_id'] ?: null,
            $data['user_type'] ?? 'teacher',
            $_SESSION['user']['id'],
            $data['max_uses'] ?? 1
        ]);
        
        echo json_encode(['success' => true, 'code' => $code, 'id' => $conn->lastInsertId()]);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM invitation_codes WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}