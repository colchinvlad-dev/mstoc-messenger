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
$schoolId = $_SESSION['user']['school_id'] ?? null;

switch ($action) {
    case 'list':
        $tab = $_GET['tab'] ?? 'all';
        
        // Получаем ID закрепленных чатов чтобы исключить их
        $pinnedIds = [];
        $stmt = $conn->prepare("SELECT id FROM chats WHERE is_pinned = 1");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $pinnedIds[] = $row['id'];
        }
        
        // Загружаем НЕзакрепленные чаты
        $query = "
            SELECT c.*,
                   CASE WHEN c.type = 'private' THEN u2.name ELSE c.name END as participant_name,
                   CASE WHEN c.type = 'private' THEN u2.is_online ELSE 0 END as is_online,
                   u2.role as participant_role,
                   (SELECT message_text FROM messages WHERE id = c.last_message_id) as last_message,
                   c.last_message_at as last_message_time,
                   (SELECT COUNT(*) FROM messages m 
                    WHERE m.chat_id = c.id AND m.sender_id != ? 
                    AND m.created_at > COALESCE(
                        (SELECT last_read_at FROM chat_participants WHERE chat_id = c.id AND user_id = ?), '1970-01-01'
                    )) as unread_count
            FROM chats c
            JOIN chat_participants cp ON cp.chat_id = c.id AND cp.user_id = ? AND cp.is_active = 1
            LEFT JOIN chat_participants cp2 ON cp2.chat_id = c.id AND cp2.user_id != ? AND c.type = 'private'
            LEFT JOIN users u2 ON u2.id = cp2.user_id
            WHERE cp.user_id = ? AND cp.is_active = 1
        ";

        $params = [$userId, $userId, $userId, $userId, $userId];

        // Исключаем закрепленные чаты
        if (!empty($pinnedIds)) {
            $placeholders = implode(',', array_fill(0, count($pinnedIds), '?'));
            $query .= " AND c.id NOT IN ($placeholders)";
            $params = array_merge($params, $pinnedIds);
        }

        // Фильтр по вкладке
        if ($tab === 'staff') {
            $query .= " AND (c.type != 'private' OR u2.role IN ('teacher', 'admin'))";
        } elseif ($tab === 'students') {
            $query .= " AND c.type = 'private' AND u2.role = 'student'";
        } elseif ($tab === 'chats') {
            $query .= " AND c.type IN ('group', 'class', 'subject')";
        }

        $query .= " ORDER BY COALESCE(c.last_message_at, c.created_at) DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $chats = $stmt->fetchAll();

        echo json_encode(['success' => true, 'chats' => $chats]);
        break;

    case 'users':
        $role = $_GET['role'] ?? 'teacher';
        $schoolIdParam = $_GET['school_id'] ?? $schoolId;

        if (!$schoolIdParam) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }

        // Получаем ВСЕХ пользователей нужной роли (включая admin для учителей)
        if ($role === 'teacher') {
            $roleFilter = "AND u.role IN ('teacher', 'admin')";
        } else {
            $roleFilter = "AND u.role = ?";
        }

        $sql = "
            SELECT u.id, u.name, u.role, u.is_online, u.class_id,
                   CONCAT(c.grade, c.letter) as class_name,
                   (SELECT ch.id FROM chats ch
                    JOIN chat_participants cp1 ON cp1.chat_id = ch.id AND cp1.user_id = ?
                    JOIN chat_participants cp2 ON cp2.chat_id = ch.id AND cp2.user_id = u.id
                    WHERE ch.type = 'private' LIMIT 1) as existing_chat_id
            FROM users u
            LEFT JOIN classes c ON u.class_id = c.id
            WHERE u.school_id = ? $roleFilter AND u.id != ? AND u.is_active = 1
            ORDER BY u.name
        ";

        $stmt = $conn->prepare($sql);
        if ($role === 'teacher') {
            $stmt->execute([$userId, $schoolIdParam, $userId]);
        } else {
            $stmt->execute([$userId, $schoolIdParam, $role, $userId]);
        }
        $users = $stmt->fetchAll();

        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'get_or_create_private':
        $data = json_decode(file_get_contents('php://input'), true);
        $targetId = (int)($data['user_id'] ?? 0);

        if (!$targetId || $targetId == $userId) {
            echo json_encode(['error' => 'Invalid user']);
            exit;
        }

        // Ищем существующий приватный чат
        $stmt = $conn->prepare("
            SELECT c.id FROM chats c
            JOIN chat_participants cp1 ON cp1.chat_id = c.id AND cp1.user_id = ?
            JOIN chat_participants cp2 ON cp2.chat_id = c.id AND cp2.user_id = ?
            WHERE c.type = 'private'
            LIMIT 1
        ");
        $stmt->execute([$userId, $targetId]);
        $existing = $stmt->fetch();

        if ($existing) {
            echo json_encode(['success' => true, 'chat_id' => (int)$existing['id']]);
            exit;
        }

        // Создаем новый приватный чат
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("INSERT INTO chats (type, school_id, created_by) VALUES ('private', ?, ?)");
            $stmt->execute([$schoolId, $userId]);
            $chatId = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO chat_participants (chat_id, user_id) VALUES (?, ?)");
            $stmt->execute([$chatId, $userId]);
            $stmt->execute([$chatId, $targetId]);

            $conn->commit();
            echo json_encode(['success' => true, 'chat_id' => $chatId]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'info':
        $chatId = (int)$_GET['chat_id'];

        $stmt = $conn->prepare("SELECT 1 FROM chat_participants WHERE chat_id = ? AND user_id = ? AND is_active = 1");
        $stmt->execute([$chatId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM chats WHERE id = ?");
        $stmt->execute([$chatId]);
        $chat = $stmt->fetch();

        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.role, u.is_online, cp.role as participant_role
            FROM chat_participants cp
            JOIN users u ON u.id = cp.user_id
            WHERE cp.chat_id = ? AND cp.is_active = 1
        ");
        $stmt->execute([$chatId]);
        $participants = $stmt->fetchAll();

        echo json_encode(['success' => true, 'chat' => $chat, 'participants' => $participants]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
?>