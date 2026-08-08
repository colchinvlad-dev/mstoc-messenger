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
    case 'list':
        $chatId = (int)$_GET['chat_id'];
        
        // Проверяем что пользователь участник чата
        $stmt = $conn->prepare("SELECT 1 FROM chat_participants WHERE chat_id = ? AND user_id = ? AND is_active = 1");
        $stmt->execute([$chatId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        // Определяем тип чата
        $stmt = $conn->prepare("SELECT type FROM chats WHERE id = ?");
        $stmt->execute([$chatId]);
        $chat = $stmt->fetch();
        
        // Для персональных чатов показываем только свои сообщения
        if ($chat && in_array($chat['type'], ['admin', 'support', 'favorite'])) {
            $stmt = $conn->prepare("
                SELECT m.*
                FROM messages m
                WHERE m.chat_id = ? AND m.sender_id = ?
                ORDER BY m.created_at ASC
                LIMIT 100
            ");
            $stmt->execute([$chatId, $userId]);
        } else {
            // Общие чаты - все сообщения
            $stmt = $conn->prepare("
                SELECT m.*
                FROM messages m
                WHERE m.chat_id = ?
                ORDER BY m.created_at ASC
                LIMIT 100
            ");
            $stmt->execute([$chatId]);
        }
        
        $messages = $stmt->fetchAll();

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'send':
        $chatId = (int)$_POST['chat_id'];
        $text = trim($_POST['text'] ?? '');

        if (empty($text) && empty($_FILES['file'])) {
            echo json_encode(['error' => 'Empty message']);
            exit;
        }

        // Проверяем доступ
        $stmt = $conn->prepare("SELECT 1 FROM chat_participants WHERE chat_id = ? AND user_id = ? AND is_active = 1");
        $stmt->execute([$chatId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        $filePath = null;
        $fileName = null;
        $fileSize = null;
        $fileMime = null;
        $messageType = 'text';

        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileMime = mime_content_type($file['tmp_name']);

            $uploadDir = __DIR__ . '/../assets/uploads/files/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filePath = '/assets/uploads/files/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
            move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $filePath);

            $messageType = strpos($fileMime, 'image/') === 0 ? 'image' : 'file';
        }

        $stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, message_text, message_type, file_path, file_name, file_size, file_mime_type, sender_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$chatId, $userId, $text, $messageType, $filePath, $fileName, $fileSize, $fileMime, $_SESSION['user']['name']]);

        $messageId = $conn->lastInsertId();

        $stmt = $conn->prepare("UPDATE chats SET last_message_id = ?, last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$messageId, $chatId]);

        $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch();

        echo json_encode(['success' => true, 'message' => $message]);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $messageId = (int)($data['message_id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
        $stmt->execute([$messageId, $userId]);

        echo json_encode(['success' => true]);
        break;

    case 'read':
        $data = json_decode(file_get_contents('php://input'), true);
        $messageIds = $data['message_ids'] ?? [];

        if (!empty($messageIds)) {
            $stmt = $conn->prepare("INSERT IGNORE INTO message_read_status (message_id, user_id) VALUES (?, ?)");
            foreach ($messageIds as $msgId) {
                $stmt->execute([(int)$msgId, $userId]);
            }
        }

        echo json_encode(['success' => true]);
        break;

    case 'search':
        $query = trim($_GET['q'] ?? '');
        $chatId = (int)($_GET['chat_id'] ?? 0);
        
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        if ($chatId > 0) {
            // Поиск в конкретном чате
            $stmt = $conn->prepare("
                SELECT m.* 
                FROM messages m
                JOIN chat_participants cp ON cp.chat_id = m.chat_id AND cp.user_id = ?
                WHERE m.chat_id = ? AND m.message_text LIKE ?
                ORDER BY m.created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$userId, $chatId, "%{$query}%"]);
        } else {
            // Поиск по всем чатам пользователя
            $stmt = $conn->prepare("
                SELECT m.*, c.name as chat_name, c.type as chat_type
                FROM messages m
                JOIN chats c ON c.id = m.chat_id
                JOIN chat_participants cp ON cp.chat_id = m.chat_id AND cp.user_id = ?
                WHERE m.message_text LIKE ?
                ORDER BY m.created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$userId, "%{$query}%"]);
        }
        
        $messages = $stmt->fetchAll();
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
?>