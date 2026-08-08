<?php
/**
 * Веб-версия диагностики авторизации
 * Открыть в браузере: http://mstoc-messenger/debug_auth_web.php
 */

echo "<pre>";
echo "========================================\n";
echo "ДИАГНОСТИКА АВТОРИЗАЦИИ (WEB)\n";
echo "========================================\n\n";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Bcrypt: " . (defined('PASSWORD_BCRYPT') ? 'ДОСТУПЕН' : 'НЕДОСТУПЕН') . "\n\n";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/encrypt.php';

$email = 'superadmin@mtsoc.ru';
$password = 'admin';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Проверяем соединение
    echo "✓ Подключение к БД успешно\n\n";
    
    // Ищем пользователя
    $stmt = $conn->prepare("SELECT id, email, name, role, password_hash, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "✕ Пользователь {$email} не найден!\n\n";
        
        $stmt = $conn->query("SELECT id, email, name, role FROM users");
        $users = $stmt->fetchAll();
        
        echo "Существующие пользователи:\n";
        foreach ($users as $u) {
            echo "  ID: {$u['id']} | {$u['email']} | {$u['name']} | {$u['role']}\n";
        }
        
        echo "\nСоздаем супер-админа...\n";
        $hash = Encrypt::hashPassword($password);
        
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, name, role, school_id, class_id, min_id, is_active) VALUES (?, ?, ?, 'super_admin', NULL, NULL, 1, 1)");
        $stmt->execute([$email, $hash, 'Супер Администратор']);
        
        echo "✓ Супер-админ создан!\n";
        echo "  Email: {$email}\n";
        echo "  Пароль: {$password}\n";
        exit;
    }
    
    echo "✓ Пользователь найден:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Имя: {$user['name']}\n";
    echo "  Роль: {$user['role']}\n";
    echo "  Активен: " . ($user['is_active'] ? 'Да' : 'Нет') . "\n\n";
    
    // Проверяем текущий пароль
    echo "Проверка текущего пароля...\n";
    $isValid = password_verify($password, $user['password_hash']);
    
    if ($isValid) {
        echo "✓ Пароль ВЕРНЫЙ\n\n";
        echo "========================================\n";
        echo "ВСЕ РАБОТАЕТ КОРРЕКТНО!\n";
        echo "Можно входить в систему.\n";
        echo "========================================\n";
    } else {
        echo "✕ Пароль НЕВЕРНЫЙ\n\n";
        
        echo "Информация о хеше:\n";
        $info = password_get_info($user['password_hash']);
        echo "  Алгоритм: {$info['algoName']}\n";
        echo "  Стоимость: " . ($info['options']['cost'] ?? 'N/A') . "\n";
        echo "  Длина хеша: " . strlen($user['password_hash']) . "\n\n";
        
        echo "Генерируем новый хеш...\n";
        $newHash = Encrypt::hashPassword($password);
        echo "  Новый хеш: {$newHash}\n";
        echo "  Проверка: " . (password_verify($password, $newHash) ? '✓ OK' : '✕ FAIL') . "\n\n";
        
        echo "Обновляем пароль в БД...\n";
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
        
        // Проверяем обновление
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updated = $stmt->fetch();
        
        $finalCheck = password_verify($password, $updated['password_hash']);
        echo $finalCheck ? "✓ Пароль успешно обновлен!\n\n" : "✕ Ошибка обновления!\n\n";
        
        echo "========================================\n";
        echo "ДАННЫЕ ДЛЯ ВХОДА:\n";
        echo "  Email: {$email}\n";
        echo "  Пароль: {$password}\n";
        echo "========================================\n";
    }
    
} catch (Exception $e) {
    echo "✕ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . " (строка " . $e->getLine() . ")\n";
}
echo "</pre>";
?>