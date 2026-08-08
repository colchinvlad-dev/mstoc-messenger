<?php
ob_start();
session_start();
require_once __DIR__ . '/config/database.php';

$token = isset($_GET['token']) ? filter_var($_GET['token'], FILTER_SANITIZE_STRING) : null;

if ($token === 'demo') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Ищем любого активного пользователя
        $stmt = $conn->prepare("SELECT * FROM users WHERE is_active = 1 AND role != 'super_admin' ORDER BY id LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'school_id' => $user['school_id'],
                'class_id' => $user['class_id'],
                'min_id' => $user['min_id'],
                'avatar_type' => $user['avatar_type'],
                'avatar_url' => $user['avatar_url']
            ];

            $stmt = $conn->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            session_regenerate_id(true);

            ob_end_clean();
            header("Location: messenger.php");
            exit;
        }
    } catch (Exception $e) {
        error_log("Demo login error: " . $e->getMessage());
    }
}

ob_end_clean();
header("Location: index.php?error=2");
exit();
?>