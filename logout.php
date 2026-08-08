<?php
ob_start();
session_start();

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Обновляем статус
        $stmt = $conn->prepare("UPDATE users SET is_online = 0, last_seen = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);

        // Логируем выход
        $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, details) VALUES (?, 'logout', ?, ?, ?)");
        $stmt->execute([$_SESSION['user']['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '', "User logged out"]);

    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Очищаем сессию
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

ob_end_clean();
header('Location: index.php');
exit();
?>