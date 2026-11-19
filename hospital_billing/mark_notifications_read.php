<?php
ob_start();
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

require_once 'config.php';

/* Read JSON body */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$id = isset($data['id']) ? (int)$data['id'] : 0;

if ($id <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    ob_end_flush();
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);

    ob_clean();
    echo json_encode(['success' => true, 'id' => $id]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

ob_end_flush();
exit;