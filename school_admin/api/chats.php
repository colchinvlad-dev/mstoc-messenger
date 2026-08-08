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
$userId = $_SESSION['user']['id'];
$schoolId = $_SESSION['user']['school_id'] ?? null;

switch ($action) {
    case 'list':
        $schoolIdParam = $_GET['school_id'] ?? $schoolId;
        
        $stmt = $conn->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM chat_participants WHERE chat_id = c.id AND is_active = 1) as participants_count
            FROM chats c
            WHERE c.school_id = ? AND c.id > 0 AND c.is_pinned = 0
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$schoolIdParam]);
        $chats = $stmt->fetchAll();
        
        echo json_encode($chats);
        break;

    case 'get':
        $chatId = (int)($_GET['id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT * FROM chats WHERE id = ? AND school_id = ?");
        $stmt->execute([$chatId, $schoolId]);
        $chat = $stmt->fetch();
        
        if (!$chat) {
            echo json_encode(['error' => 'Chat not found']);
            exit;
        }
        
        // Участники чата
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.role as user_role, cp.role as chat_role
            FROM chat_participants cp
            JOIN users u ON u.id = cp.user_id
            WHERE cp.chat_id = ?
            ORDER BY cp.role = 'owner' DESC, cp.role = 'admin' DESC, u.name ASC
        ");
        $stmt->execute([$chatId]);
        $participants = $stmt->fetchAll();
        
        // Доступные пользователи для добавления
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.role
            FROM users u
            WHERE u.school_id = ? AND u.is_active = 1
            AND u.id NOT IN (SELECT user_id FROM chat_participants WHERE chat_id = ?)
            ORDER BY u.name
            LIMIT 50
        ");
        $stmt->execute([$schoolId, $chatId]);
        $availableUsers = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'chat' => $chat,
            'participants' => $participants,
            'available_users' => $availableUsers
        ]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $conn->beginTransaction();
        try {
            // Создаем чат
            $stmt = $conn->prepare("
                INSERT INTO chats (name, type, school_id, created_by, description)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['type'] ?? 'group',
                $schoolId,
                $userId,
                $data['description'] ?? null
            ]);
            $chatId = $conn->lastInsertId();
            
            // Добавляем создателя как владельца
            $stmt = $conn->prepare("INSERT INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$chatId, $userId]);
            
            // Если указан админ - добавляем
            if (!empty($data['admin_id'])) {
                $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'admin')");
                $stmt->execute([$chatId, (int)$data['admin_id']]);
            }
            
            // Добавляем выбранных участников
            if (!empty($data['participants'])) {
                $participants = json_decode($data['participants'], true);
                if (is_array($participants)) {
                    foreach ($participants as $participantId) {
                        if ($participantId != $userId && $participantId != ($data['admin_id'] ?? 0)) {
                            $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'member')");
                            $stmt->execute([$chatId, (int)$participantId]);
                        }
                    }
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $chatId]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        
        // Проверяем что чат принадлежит школе
        $stmt = $conn->prepare("SELECT 1 FROM chats WHERE id = ? AND school_id = ?");
        $stmt->execute([$chatId, $schoolId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Chat not found']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE chats SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['name'], $data['description'] ?? null, $chatId]);
        
        echo json_encode(['success' => true]);
        break;

    case 'add_member':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $newUserId = (int)($data['user_id'] ?? 0);
        $role = $data['role'] ?? 'member';
        
        if (!in_array($role, ['member', 'admin'])) {
            $role = 'member';
        }
        
        $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$chatId, $newUserId, $role]);
        
        echo json_encode(['success' => true]);
        break;

    case 'remove_member':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $removeUserId = (int)($data['user_id'] ?? 0);
        
        // Нельзя удалить владельца
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $removeUserId]);
        $member = $stmt->fetch();
        
        if ($member && $member['role'] === 'owner') {
            echo json_encode(['error' => 'Cannot remove owner']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $removeUserId]);
        
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['id'] ?? 0);
        
        // Проверяем что чат принадлежит школе
        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND school_id = ?");
        $stmt->execute([$chatId, $schoolId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Chat not found']);
            exit;
        }
        
        $conn->beginTransaction();
        try {
            // Удаляем связанные данные
            $conn->prepare("DELETE FROM message_read_status WHERE message_id IN (SELECT id FROM messages WHERE chat_id = ?)")->execute([$chatId]);
            $conn->prepare("DELETE FROM message_reactions WHERE message_id IN (SELECT id FROM messages WHERE chat_id = ?)")->execute([$chatId]);
            $conn->prepare("DELETE FROM messages WHERE chat_id = ?")->execute([$chatId]);
            $conn->prepare("DELETE FROM chat_participants WHERE chat_id = ?")->execute([$chatId]);
            $conn->prepare("DELETE FROM chats WHERE id = ?")->execute([$chatId]);
            
            // Если это чат класса - отвязываем от класса
            $conn->prepare("UPDATE classes SET chat_id = NULL WHERE chat_id = ?")->execute([$chatId]);
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'change_role':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $targetUserId = (int)($data['user_id'] ?? 0);
        $newRole = $data['new_role'] ?? 'member';
        
        if (!in_array($newRole, ['admin', 'member'])) {
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }
        
        // Нельзя изменить владельца
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $targetUserId]);
        $member = $stmt->fetch();
        
        if (!$member || $member['role'] === 'owner') {
            echo json_encode(['error' => 'Cannot change owner role']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE chat_participants SET role = ? WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$newRole, $chatId, $targetUserId]);
        
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
?>