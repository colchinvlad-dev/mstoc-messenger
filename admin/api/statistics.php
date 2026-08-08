<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$action = $_GET['action'] ?? 'summary';

if ($action === 'detailed') {
    // Детальная статистика
    $stmt = $conn->query("SELECT * FROM school_statistics ORDER BY date DESC LIMIT 30");
    $stats = $stmt->fetchAll();
    echo json_encode($stats);
    exit;
}

// Сводная статистика
$totalSchools = $conn->query("SELECT COUNT(*) as count FROM schools WHERE is_active = 1")->fetch()['count'];
$totalAdmins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1")->fetch()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch()['count'];
$messagesToday = $conn->query("SELECT COUNT(*) as count FROM messages WHERE DATE(created_at) = CURDATE()")->fetch()['count'];

echo json_encode([
    'total_schools' => (int)$totalSchools,
    'total_admins' => (int)$totalAdmins,
    'total_users' => (int)$totalUsers,
    'messages_today' => (int)$messagesToday
]);
?>