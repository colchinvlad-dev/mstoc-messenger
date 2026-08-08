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
    case 'info':
        $chatId = (int)($_GET['chat_id'] ?? 0);
        
        // Проверяем доступ
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $userId]);
        $member = $stmt->fetch();
        
        if (!$member) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        // Информация о чате
        $stmt = $conn->prepare("SELECT * FROM chats WHERE id = ?");
        $stmt->execute([$chatId]);
        $chat = $stmt->fetch();
        
        // Участники
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.role as user_role, u.is_online, cp.role as chat_role
            FROM chat_participants cp
            JOIN users u ON u.id = cp.user_id
            WHERE cp.chat_id = ?
            ORDER BY cp.role = 'owner' DESC, cp.role = 'admin' DESC, u.name ASC
        ");
        $stmt->execute([$chatId]);
        $participants = $stmt->fetchAll();
        
        // Пользователи, которых можно добавить (из той же школы)
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.role as user_role
            FROM users u
            WHERE u.school_id = ? AND u.is_active = 1
            AND u.id NOT IN (SELECT user_id FROM chat_participants WHERE chat_id = ?)
            ORDER BY u.name
            LIMIT 50
        ");
        $stmt->execute([$chat['school_id'], $chatId]);
        $availableUsers = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'chat' => $chat,
            'participants' => $participants,
            'available_users' => $availableUsers,
            'my_role' => $member['role']
        ]);
        break;

    case 'add_member':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $newUserId = (int)($data['user_id'] ?? 0);
        
        // Проверяем права (владелец или админ)
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $userId]);
        $member = $stmt->fetch();
        
        if (!$member || !in_array($member['role'], ['owner', 'admin'])) {
            echo json_encode(['error' => 'Только администратор может добавлять участников']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$chatId, $newUserId]);
        
        // Добавляем системное сообщение
        $userName = $_SESSION['user']['name'];
        $newUserName = $conn->query("SELECT name FROM users WHERE id = $newUserId")->fetch()['name'];
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, message_text, message_type, is_system, system_type, sender_name) VALUES (?, ?, ?, 'system', 1, 'user_added', ?)");
        $stmt->execute([$chatId, $userId, "{$userName} добавил(а) {$newUserName}", $_SESSION['user']['name']]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'remove_member':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $removeUserId = (int)($data['user_id'] ?? 0);
        
        if ($removeUserId == $userId) {
            echo json_encode(['error' => 'Используйте "Покинуть чат"']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $userId]);
        $member = $stmt->fetch();
        
        if (!$member || !in_array($member['role'], ['owner', 'admin'])) {
            echo json_encode(['error' => 'Только администратор может удалять участников']);
            exit;
        }
        
        // Нельзя удалить владельца
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $removeUserId]);
        $toRemove = $stmt->fetch();
        
        if ($toRemove && $toRemove['role'] === 'owner') {
            echo json_encode(['error' => 'Нельзя удалить создателя чата']);
            exit;
        }
        
        // Админ не может удалить другого админа (только владелец)
        if ($member['role'] === 'admin' && $toRemove['role'] === 'admin') {
            echo json_encode(['error' => 'Только владелец может удалить администратора']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $removeUserId]);
        
        // Системное сообщение
        $userName = $_SESSION['user']['name'];
        $removedName = $conn->query("SELECT name FROM users WHERE id = $removeUserId")->fetch()['name'];
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, message_text, message_type, is_system, system_type, sender_name) VALUES (?, ?, ?, 'system', 1, 'user_removed', ?)");
        $stmt->execute([$chatId, $userId, "{$userName} удалил(а) {$removedName}", $_SESSION['user']['name']]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'change_role':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $targetUserId = (int)($data['user_id'] ?? 0);
        $newRole = $data['new_role'] ?? 'member';
        
        // Только владелец может менять роли
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ? AND role = 'owner'");
        $stmt->execute([$chatId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Только владелец может назначать администраторов']);
            exit;
        }
        
        // Нельзя изменить роль владельца
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $targetUserId]);
        $target = $stmt->fetch();
        
        if ($target && $target['role'] === 'owner') {
            echo json_encode(['error' => 'Нельзя изменить роль владельца']);
            exit;
        }
        
        if (!in_array($newRole, ['admin', 'member'])) {
            echo json_encode(['error' => 'Неверная роль']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE chat_participants SET role = ? WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$newRole, $chatId, $targetUserId]);
        
        // Системное сообщение
        $targetName = $conn->query("SELECT name FROM users WHERE id = $targetUserId")->fetch()['name'];
        $roleLabel = $newRole === 'admin' ? 'администратором' : 'участником';
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, message_text, message_type, is_system, system_type, sender_name) VALUES (?, ?, ?, 'system', 1, 'role_changed', ?)");
        $stmt->execute([$chatId, $userId, "{$targetName} назначен(а) {$roleLabel}", $_SESSION['user']['name']]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'edit_chat':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        $newName = trim($data['name'] ?? '');
        $newDescription = trim($data['description'] ?? '');
        
        // Только владелец или админ может редактировать
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $userId]);
        $member = $stmt->fetch();
        
        if (!$member || !in_array($member['role'], ['owner', 'admin'])) {
            echo json_encode(['error' => 'Недостаточно прав']);
            exit;
        }
        
        if (empty($newName)) {
            echo json_encode(['error' => 'Название не может быть пустым']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE chats SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newName, $newDescription, $chatId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'delete_chat':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        
        // Только владелец может удалить
        $stmt = $conn->prepare("SELECT 1 FROM chat_participants WHERE chat_id = ? AND user_id = ? AND role = 'owner'");
        $stmt->execute([$chatId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Только создатель может удалить чат']);
            exit;
        }
        
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM message_read_status WHERE message_id IN (SELECT id FROM messages WHERE chat_id = ?)")->execute([$chatId]);
            $conn->prepare("DELETE FROM message_reactions WHERE message_id IN (SELECT id FROM messages WHERE chat_id = ?)")->execute([$chatId]);
            $conn->prepare("DELETE FROM messages WHERE chat_id = ?")->execute([$chatId]);
            $conn->prepare("DELETE FROM chat_participants WHERE chat_id = ?")->execute([$chatId]);
            $conn->prepare("DELETE FROM chats WHERE id = ?")->execute([$chatId]);
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'leave_chat':
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = (int)($data['chat_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT role FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $userId]);
        $member = $stmt->fetch();
        
        if (!$member) {
            echo json_encode(['error' => 'Вы не участник']);
            exit;
        }
        
        if ($member['role'] === 'owner') {
            echo json_encode(['error' => 'Создатель не может покинуть чат. Удалите чат.']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chatId, $userId]);
        
        $userName = $_SESSION['user']['name'];
        $stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, message_text, message_type, is_system, system_type, sender_name) VALUES (?, ?, ?, 'system', 1, 'user_left', ?)");
        $stmt->execute([$chatId, $userId, "{$userName} покинул(а) чат", $_SESSION['user']['name']]);
        
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
?>