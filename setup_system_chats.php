<?php
/**
 * ОДНОРАЗОВЫЙ скрипт настройки системных чатов
 * Запустить: php setup_system_chats.php
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Настройка системных чатов...\n";

// 1. Изменяем ENUM для type, добавляя 'favorite'
try {
    $conn->exec("ALTER TABLE chats MODIFY COLUMN type ENUM('private','group','system','class','subject','admin','support','favorite') NOT NULL DEFAULT 'private'");
    echo "✓ ENUM обновлен\n";
} catch (Exception $e) {
    echo "ENUM уже обновлен или ошибка: " . $e->getMessage() . "\n";
}

// 2. Удаляем старые системные чаты с отрицательными ID
$conn->exec("DELETE FROM messages WHERE chat_id < 0");
$conn->exec("DELETE FROM chat_participants WHERE chat_id < 0");
$conn->exec("DELETE FROM chats WHERE id < 0");
$conn->exec("DELETE FROM chats WHERE id = 0");
echo "✓ Старые чаты удалены\n";

// 3. Получаем всех пользователей
$users = $conn->query("SELECT id, school_id, name FROM users WHERE is_active = 1")->fetchAll();

$conn->beginTransaction();
try {
    foreach ($users as $user) {
        // Избранное
        $stmt = $conn->prepare("INSERT INTO chats (name, type, school_id, created_by, is_pinned, description, created_at, updated_at) VALUES (?, 'favorite', ?, ?, 1, 'Избранное', NOW(), NOW())");
        $stmt->execute(['⭐ Избранное', $user['school_id'], $user['id']]);
        $favId = $conn->lastInsertId();
        
        $stmt = $conn->prepare("INSERT INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'owner')");
        $stmt->execute([$favId, $user['id']]);
        
        // Техподдержка
        $stmt = $conn->prepare("INSERT INTO chats (name, type, school_id, created_by, is_pinned, description, created_at, updated_at) VALUES (?, 'support', ?, ?, 1, 'Техническая поддержка', NOW(), NOW())");
        $stmt->execute(['🔧 Техподдержка', $user['school_id'], $user['id']]);
        $supId = $conn->lastInsertId();
        
        $stmt = $conn->prepare("INSERT INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'owner')");
        $stmt->execute([$supId, $user['id']]);
        
        // Добавляем support-пользователей
        $supportUsers = $conn->query("SELECT id FROM users WHERE role = 'support' AND is_active = 1")->fetchAll();
        foreach ($supportUsers as $su) {
            $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'member')");
            $stmt->execute([$supId, $su['id']]);
        }
        
        echo "  ✓ Чаты для {$user['name']}\n";
    }
    
    // 4. Чат администрации для каждой школы
    $schools = $conn->query("SELECT id, name FROM schools WHERE is_active = 1")->fetchAll();
    foreach ($schools as $school) {
        $stmt = $conn->prepare("INSERT INTO chats (name, type, school_id, created_by, is_pinned, description, created_at, updated_at) VALUES (?, 'admin', ?, 1, 1, 'Обращения к администрации школы', NOW(), NOW())");
        $stmt->execute(['🏫 Администрация', $school['id']]);
        $adminChatId = $conn->lastInsertId();
        
        // Добавляем всех пользователей школы
        $stmt = $conn->prepare("INSERT INTO chat_participants (chat_id, user_id, role) SELECT ?, id, 'member' FROM users WHERE school_id = ? AND is_active = 1");
        $stmt->execute([$adminChatId, $school['id']]);
        
        // Админам даем роль admin
        $stmt = $conn->prepare("UPDATE chat_participants SET role = 'admin' WHERE chat_id = ? AND user_id IN (SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND school_id = ?)");
        $stmt->execute([$adminChatId, $school['id']]);
        
        echo "  ✓ Чат администрации для школы {$school['name']} (ID: {$adminChatId})\n";
    }
    
    $conn->commit();
    echo "\n========================================\n";
    echo "ГОТОВО! Все системные чаты созданы.\n";
    echo "Теперь у каждого пользователя свои персональные чаты.\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "ОШИБКА: " . $e->getMessage() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}
?>