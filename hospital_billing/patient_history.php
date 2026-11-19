<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); exit;
}
require_once 'config.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode([]); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT date, service_description, total_amount, status, notes
        FROM billing_records
        WHERE patient_db_id = ?
        ORDER BY date DESC, id DESC
        LIMIT 10
    ");
    $stmt->execute([$id]);

    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[] = [
            'date'   => date('M d, Y', strtotime($row['date'])),
            'desc'   => $row['service_description'],
            'amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'note'   => $row['notes'] ?? '',
        ];
    }
    echo json_encode($out);
} catch (PDOException $e) {
    echo json_encode([]);
}