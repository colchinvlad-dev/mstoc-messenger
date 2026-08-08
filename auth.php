<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/encrypt.php';

// Логируем ошибки в файл, не показываем в браузере
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: index.php?error=1");
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Ищем пользователя по email
    $stmt = $conn->prepare("SELECT id, email, password_hash, name, role, school_id, class_id, min_id, avatar_type, avatar_url, is_active FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Логируем попытку входа
    error_log("Login attempt: email={$email}, user_found=" . ($user ? 'yes' : 'no'));

    if (!$user) {
        // Пользователь не найден - логируем
        $stmt = $conn->prepare("INSERT INTO security_logs (event_type, ip_address, user_agent, details) VALUES ('login_failed', ?, ?, ?)");
        $stmt->execute([$_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '', "User not found: {$email}"]);
        
        header("Location: index.php?error=1");
        exit;
    }

    // Проверяем пароль через password_verify
    $passwordValid = password_verify($password, $user['password_hash']);
    
    error_log("Password check: " . ($passwordValid ? 'valid' : 'invalid') . " for user {$user['id']}");

    if (!$passwordValid) {
        // Неверный пароль - логируем
        $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, details) VALUES (?, 'login_failed', ?, ?, ?)");
        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '', "Wrong password for {$email}"]);
        
        header("Location: index.php?error=1");
        exit;
    }

    // Проверяем активность аккаунта
    if (!$user['is_active']) {
        header("Location: index.php?error=3");
        exit;
    }

    // Успешная авторизация
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

    // Обновляем статус онлайн и время последней активности
    $stmt = $conn->prepare("UPDATE users SET is_online = 1, last_seen = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Создаем токен сессии
    $token = Encrypt::generateToken();
    $sessionId = session_id();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Удаляем старые истекшие сессии
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
    $stmt->execute([$user['id']]);

    // Сохраняем новую сессию в БД
    $stmt = $conn->prepare("INSERT INTO user_sessions (id, user_id, token, ip_address, user_agent, device_info, is_active, expires_at, created_at, last_activity) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())");
    $stmt->execute([
        $sessionId,
        $user['id'],
        $token,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        'Web Browser',
        $expiresAt
    ]);

    // Логируем успешный вход
    $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, details) VALUES (?, 'login_success', ?, ?, ?)");
    $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '', "Successful login"]);

    // Обновляем ID сессии для безопасности
    session_regenerate_id(true);

    error_log("Login successful: user_id={$user['id']}, role={$user['role']}");

    // Редирект в зависимости от роли
    if ($user['role'] === 'super_admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: messenger.php");
    }
    exit;

} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    header("Location: index.php?error=2");
    exit;
}
?>