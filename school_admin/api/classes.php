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

// Проверяем и добавляем колонку chat_id если её нет
try {
    $conn->exec("ALTER TABLE classes ADD COLUMN IF NOT EXISTS chat_id INT DEFAULT NULL");
    $conn->exec("ALTER TABLE classes ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Колонки уже существуют
}

switch ($action) {
    case 'list':
        if (!$schoolId) {
            echo json_encode([]);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, 
                   u.name as teacher_name,
                   (SELECT COUNT(*) FROM users WHERE class_id = c.id AND is_active = 1) as students_count
            FROM classes c
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.school_id = ?
            ORDER BY c.grade, c.letter
        ");
        $stmt->execute([$schoolId]);
        $classes = $stmt->fetchAll();
        
        echo json_encode($classes);
        break;

    case 'get':
        $classId = (int)($_GET['id'] ?? 0);
        
        if (!$schoolId) {
            echo json_encode(['error' => 'No school assigned']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, u.name as teacher_name
            FROM classes c
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.id = ? AND c.school_id = ?
        ");
        $stmt->execute([$classId, $schoolId]);
        $class = $stmt->fetch();
        
        if (!$class) {
            echo json_encode(['error' => 'Class not found']);
            exit;
        }
        
        // Ученики класса
        $stmt = $conn->prepare("
            SELECT id, name, email, is_active
            FROM users WHERE class_id = ? AND role = 'student'
            ORDER BY name
        ");
        $stmt->execute([$classId]);
        $students = $stmt->fetchAll();
        
        // Учителя
        $stmt = $conn->prepare("
            SELECT id, name FROM users
            WHERE school_id = ? AND role IN ('teacher', 'admin') AND is_active = 1
            ORDER BY name
        ");
        $stmt->execute([$schoolId]);
        $teachers = $stmt->fetchAll();
        
        // Ученики не в классе
        $stmt = $conn->prepare("
            SELECT id, name FROM users
            WHERE school_id = ? AND role = 'student' AND is_active = 1
            AND (class_id IS NULL OR class_id != ?)
            ORDER BY name
        ");
        $stmt->execute([$schoolId, $classId]);
        $availableStudents = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'class' => $class,
            'students' => $students,
            'teachers' => $teachers,
            'available_students' => $availableStudents
        ]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Логируем для отладки
        error_log("Create class data: " . json_encode($data));
        
        if (!$schoolId) {
            echo json_encode(['error' => 'No school assigned']);
            exit;
        }
        
        $grade = (int)($data['grade'] ?? 0);
        $letter = trim($data['letter'] ?? '');
        
        if ($grade < 1 || $grade > 11 || empty($letter)) {
            echo json_encode(['error' => 'Укажите класс (1-11) и букву']);
            exit;
        }
        
        try {
            $conn->beginTransaction();
            
            // Создаем класс БЕЗ chat_id сначала
            $stmt = $conn->prepare("
                INSERT INTO classes (school_id, name, grade, letter, teacher_id, room_number, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $schoolId,
                $data['name'] ?: "{$grade}{$letter}",
                $grade,
                $letter,
                !empty($data['teacher_id']) ? (int)$data['teacher_id'] : null,
                $data['room_number'] ?? null,
                $data['description'] ?? null
            ]);
            $classId = $conn->lastInsertId();
            
            // Создаем чат для класса
            $chatName = !empty($data['chat_name']) ? $data['chat_name'] : "Класс {$grade}{$letter}";
            $stmt = $conn->prepare("
                INSERT INTO chats (name, type, school_id, created_by, description)
                VALUES (?, 'class', ?, ?, ?)
            ");
            $stmt->execute([
                $chatName,
                $schoolId,
                $userId,
                $data['chat_description'] ?? ''
            ]);
            $chatId = $conn->lastInsertId();
            
            // Обновляем класс с chat_id
            $stmt = $conn->prepare("UPDATE classes SET chat_id = ? WHERE id = ?");
            $stmt->execute([$chatId, $classId]);
            
            // Добавляем создателя в чат
            $stmt = $conn->prepare("INSERT INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$chatId, $userId]);
            
            // Если указан учитель - добавляем в чат как админа
            if (!empty($data['teacher_id'])) {
                $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'admin')");
                $stmt->execute([$chatId, (int)$data['teacher_id']]);
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'class_id' => $classId, 'chat_id' => $chatId]);
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Create class error: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        $classId = (int)($data['class_id'] ?? 0);
        
        if (!$classId) {
            echo json_encode(['error' => 'Class ID required']);
            exit;
        }
        
        try {
            // Обновляем основные данные класса
            $stmt = $conn->prepare("
                UPDATE classes 
                SET name = ?, grade = ?, letter = ?, teacher_id = ?, room_number = ?, description = ?
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $data['name'] ?: ($data['grade'] . $data['letter']),
                (int)$data['grade'],
                trim($data['letter']),
                !empty($data['teacher_id']) ? (int)$data['teacher_id'] : null,
                $data['room_number'] ?? null,
                $data['description'] ?? null,
                $classId,
                $schoolId
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Update class error: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $classId = (int)($data['id'] ?? 0);
        
        if (!$classId) {
            echo json_encode(['error' => 'Class ID required']);
            exit;
        }
        
        try {
            $conn->beginTransaction();
            
            // Получаем chat_id
            $stmt = $conn->prepare("SELECT chat_id FROM classes WHERE id = ? AND school_id = ?");
            $stmt->execute([$classId, $schoolId]);
            $class = $stmt->fetch();
            
            // Отвязываем учеников
            $conn->prepare("UPDATE users SET class_id = NULL WHERE class_id = ?")->execute([$classId]);
            
            // Удаляем чат если есть
            if ($class && $class['chat_id']) {
                $chatId = $class['chat_id'];
                $conn->prepare("DELETE FROM message_read_status WHERE message_id IN (SELECT id FROM messages WHERE chat_id = ?)")->execute([$chatId]);
                $conn->prepare("DELETE FROM messages WHERE chat_id = ?")->execute([$chatId]);
                $conn->prepare("DELETE FROM chat_participants WHERE chat_id = ?")->execute([$chatId]);
                $conn->prepare("DELETE FROM chats WHERE id = ?")->execute([$chatId]);
            }
            
            // Удаляем класс
            $conn->prepare("DELETE FROM classes WHERE id = ?")->execute([$classId]);
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>