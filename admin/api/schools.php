<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/encrypt.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $stmt = $conn->query("SELECT * FROM schools ORDER BY number");
        $schools = $stmt->fetchAll();
        echo json_encode($schools);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("INSERT INTO schools (name, number, address, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['number'],
            $data['address'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("UPDATE schools SET name = ?, number = ?, address = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['number'],
            $data['address'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['id']
        ]);
        
        echo json_encode(['success' => true]);
        break;

    case 'toggle':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE schools SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM schools WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'admins':
        $stmt = $conn->query("
            SELECT u.id, u.name, u.email, u.is_active, s.name as school_name 
            FROM users u 
            LEFT JOIN schools s ON u.school_id = s.id 
            WHERE u.role IN ('admin', 'super_admin')
            ORDER BY u.id
        ");
        $admins = $stmt->fetchAll();
        echo json_encode($admins);
        break;

    case 'create_admin':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $passwordHash = Encrypt::hashPassword($data['password']);
        $role = empty($data['school_id']) ? 'super_admin' : 'admin';
        
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, name, role, school_id, min_id) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $data['email'],
            $passwordHash,
            $data['name'],
            $role,
            $data['school_id'] ?: null
        ]);
        
        echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
        break;

    case 'delete_admin':
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] === 'super_admin' && $user['id'] == $_SESSION['user']['id']) {
            echo json_encode(['error' => 'Cannot delete yourself']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'super_admin')");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>