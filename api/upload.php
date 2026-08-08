<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'avatar':
        if (empty($_FILES['avatar'])) {
            echo json_encode(['error' => 'No file']);
            exit;
        }

        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['error' => 'Invalid file type']);
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['error' => 'File too large (max 2MB)']);
            exit;
        }

        $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'avatar_' . $_SESSION['user']['id'] . '_' . time() . '.jpg';
        $filePath = '/assets/uploads/avatars/' . $fileName;

        // Создаем изображение
        $source = imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$source) {
            echo json_encode(['error' => 'Failed to process image']);
            exit;
        }

        // Ресайз до 200x200
        $width = imagesx($source);
        $height = imagesy($source);
        $size = min($width, $height);
        
        $newImage = imagecreatetruecolor(200, 200);
        imagecopyresampled($newImage, $source, 0, 0, ($width - $size) / 2, ($height - $size) / 2, 200, 200, $size, $size);
        imagejpeg($newImage, __DIR__ . '/../' . $filePath, 85);

        imagedestroy($source);
        imagedestroy($newImage);

        // Обновляем в БД
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE users SET avatar_type = 'custom', avatar_url = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$filePath, $_SESSION['user']['id']]);
        
        // Обновляем сессию
        $_SESSION['user']['avatar_url'] = $filePath;
        $_SESSION['user']['avatar_type'] = 'custom';

        echo json_encode(['success' => true, 'url' => $filePath]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>