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

$schoolId = $_SESSION['user']['school_id'] ?? null;

if (!$schoolId) {
    echo json_encode(['error' => 'No school assigned']);
    exit;
}

$teachers = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE school_id = ? AND role = 'teacher' AND is_active = 1")->execute([$schoolId]) ? $conn->query("SELECT COUNT(*) as count FROM users WHERE school_id = $schoolId AND role = 'teacher' AND is_active = 1")->fetch()['count'] : 0;
$students = $conn->query("SELECT COUNT(*) as count FROM users WHERE school_id = $schoolId AND role = 'student' AND is_active = 1")->fetch()['count'];
$classes = $conn->query("SELECT COUNT(*) as count FROM classes WHERE school_id = $schoolId AND is_active = 1")->fetch()['count'];
$chats = $conn->query("SELECT COUNT(*) as count FROM chats WHERE school_id = $schoolId")->fetch()['count'];

echo json_encode([
    'teachers' => (int)$teachers,
    'students' => (int)$students,
    'classes' => (int)$classes,
    'chats' => (int)$chats
]);
?>