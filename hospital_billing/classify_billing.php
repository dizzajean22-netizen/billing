<?php
/**
 * classify_billing.php
 * AJAX endpoint for live preview in input_billing.php
 */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in again.']);
    exit;
}

require_once 'config.php';
require_once 'classify_lib.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload) || !isset($payload['description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$description = trim((string) $payload['description']);

if ($description === '' || mb_strlen($description) < 2) {
    echo json_encode([
        'success'             => true,
        'category_id'         => null,
        'category_name'       => null,
        'rule_name'           => null,
        'matched_rule_id'     => null,
        'matched_keyword'     => null,
        'patient_explanation' => '',
        'processing_ms'       => 0
    ]);
    exit;
}

$cls = classify_service($pdo, $description);

echo json_encode([
    'success'             => true,
    'category_id'         => $cls['category_id'],
    'category_name'       => $cls['category_name'],
    'rule_name'           => $cls['matched_rule'],
    'matched_rule_id'     => $cls['matched_rule_id'],
    'matched_keyword'     => $cls['matched_keyword'],
    'patient_explanation' => $cls['patient_explanation'],
    'processing_ms'       => $cls['processing_ms']
]);
exit;