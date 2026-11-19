<?php
/* ============================================================
   monitoring_actions.php
   Secure AJAX endpoint for Administrator Monitoring actions
   ============================================================ */
date_default_timezone_set('Asia/Manila');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success'=>false, 'message'=>'Not authenticated.']);
    exit;
}

require_once 'config.php';
try { $pdo->exec("SET time_zone = '+08:00'"); } catch (PDOException $e) {}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['username'] ?? 'Admin';

/* -------- Enforce Admin Role -------- */
$full_name = $user_name; $role = '';
try {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) { $full_name = $u['full_name'] ?? $user_name; $role = strtolower($u['role'] ?? ''); }
} catch (PDOException $e) {}

if ($role !== 'admin') {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized: Admin access required.']);
    exit;
}

/* -------- Helper: log audit -------- */
function logAudit(PDO $pdo, int $aid, string $aname, string $action, string $module, $rid, $reason = null, $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo->prepare("INSERT INTO audit_trail (admin_id, admin_name, action_type, module, record_id, reason, details, ip_address)
                       VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$aid, $aname, $action, $module, $rid, $reason, $details, $ip]);
    } catch (PDOException $e) {}
}

/* ============================================================
   GET: History lookups
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['history'])) {

    if ($_GET['history'] === 'patient') {
        $pid = (int)($_GET['id'] ?? 0);
        $out = ['success' => true];

        try {
            $s = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
            $s->execute([$pid]);
            $reg = $s->fetchColumn();
            $out['registration'] = $reg ? date('M d, Y g:i A', strtotime($reg)) : '—';
        } catch (PDOException $e) { $out['registration'] = '—'; }

        try {
            $s = $pdo->prepare("SELECT COUNT(*) FROM billing_records WHERE patient_id = ? OR patient_name IN (SELECT full_name FROM users WHERE id = ?)");
            $s->execute([$pid, $pid]);
            $out['billing_count'] = (int)$s->fetchColumn();
        } catch (PDOException $e) { $out['billing_count'] = 0; }

        try {
            $s = $pdo->prepare("SELECT created_at, action_type, module, details, reason FROM audit_trail WHERE (module='PATIENT' AND record_id=?) OR (module='BILLING' AND record_id IN (SELECT id FROM billing_records WHERE patient_id=?)) ORDER BY created_at DESC LIMIT 30");
            $s->execute([$pid, $pid]);
            $out['timeline'] = array_map(fn($r) => [
                'date'   => date('M d, Y g:i A', strtotime($r['created_at'])),
                'action' => $r['action_type'] . ' ' . $r['module'],
                'detail' => $r['details'] ?? $r['reason'] ?? ''
            ], $s->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) { $out['timeline'] = []; }

        echo json_encode($out); exit;
    }

    if ($_GET['history'] === 'billing') {
        $bid = (int)($_GET['id'] ?? 0);
        $out = ['success' => true, 'record' => null, 'voided' => []];

        try {
            $s = $pdo->prepare("SELECT * FROM billing_records WHERE id = ?");
            $s->execute([$bid]);
            $out['record'] = $s->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        try {
            $s = $pdo->prepare("SELECT voided_at, voided_by_name, reason FROM voided_transactions WHERE billing_id = ? ORDER BY voided_at DESC");
            $s->execute([$bid]);
            $out['voided'] = array_map(fn($r) => [
                'voided_at'      => date('M d, Y g:i A', strtotime($r['voided_at'])),
                'voided_by_name' => $r['voided_by_name'],
                'reason'         => $r['reason']
            ], $s->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {}

        echo json_encode($out); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown history type.']); exit;
}

/* ============================================================
   POST: Mutating actions
   ============================================================ */
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit; }

$action = $input['action'] ?? '';
$module = strtoupper($input['module'] ?? '');
$id     = (int)($input['id'] ?? 0);
$reason = trim($input['reason'] ?? '');

if (!$action || !$module || $id <= 0) {
    echo json_encode(['success'=>false,'message'=>'Missing required parameters.']); exit;
}
if (!in_array($action, ['archive','restore','void'], true)) {
    echo json_encode(['success'=>false,'message'=>'Invalid action.']); exit;
}
if (!in_array($module, ['PATIENT','BILLING','USER'], true)) {
    echo json_encode(['success'=>false,'message'=>'Invalid module.']); exit;
}
if ($reason === '') {
    echo json_encode(['success'=>false,'message'=>'A reason is required.']); exit;
}
if (strlen($reason) > 2000) {
    echo json_encode(['success'=>false,'message'=>'Reason is too long (max 2000 chars).']); exit;
}

/* ============================================================
   ARCHIVE
   ============================================================ */
if ($action === 'archive') {
    $table = $module === 'BILLING' ? 'billing_records' : ($module === 'PATIENT' ? 'users' : null);
    if (!$table) { echo json_encode(['success'=>false,'message'=>'Cannot archive this module.']); exit; }

    // Fetch record for snapshot
    try {
        $s = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $s->execute([$id]);
        $record = $s->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'Record not found.']); exit;
    }
    if (!$record) { echo json_encode(['success'=>false,'message'=>'Record not found.']); exit; }

    // Prevent archiving already-archived
    if (!empty($record['is_archived'])) {
        echo json_encode(['success'=>false,'message'=>'Record is already archived.']); exit;
    }

    $pdo->beginTransaction();
    try {
        // Snapshot
        $pdo->prepare("INSERT INTO archived_records (module, original_id, record_snapshot, archived_by, archived_by_name, reason)
                       VALUES (?,?,?,?,?,?)")
            ->execute([$module, $id, json_encode($record), $user_id, $full_name, $reason]);

        // Soft mark
        $pdo->prepare("UPDATE $table SET is_archived = 1 WHERE id = ?")->execute([$id]);

        logAudit($pdo, $user_id, $full_name, 'ARCHIVE', $module, $id, $reason,
                 "Archived $module record #$id");

        $pdo->commit();
        echo json_encode(['success'=>true,'message'=>"$module record #$id archived."]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Archive failed: '.$e->getMessage()]);
    }
    exit;
}

/* ============================================================
   RESTORE
   ============================================================ */
if ($action === 'restore') {
    $table = $module === 'BILLING' ? 'billing_records' : ($module === 'PATIENT' ? 'users' : null);
    if (!$table) { echo json_encode(['success'=>false,'message'=>'Cannot restore this module.']); exit; }

    // Find active archive entry
    try {
        $s = $pdo->prepare("SELECT id FROM archived_records WHERE module=? AND original_id=? AND restored_at IS NULL ORDER BY archived_at DESC LIMIT 1");
        $s->execute([$module, $id]);
        $archiveEntryId = $s->fetchColumn();
    } catch (PDOException $e) { $archiveEntryId = false; }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE $table SET is_archived = 0 WHERE id = ?")->execute([$id]);

        if ($archiveEntryId) {
            $pdo->prepare("UPDATE archived_records SET restored_by=?, restored_by_name=?, restored_at=NOW() WHERE id=?")
                ->execute([$user_id, $full_name, $archiveEntryId]);
        }

        logAudit($pdo, $user_id, $full_name, 'RESTORE', $module, $id, $reason,
                 "Restored $module record #$id");

        $pdo->commit();
        echo json_encode(['success'=>true,'message'=>"$module record #$id restored."]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Restore failed: '.$e->getMessage()]);
    }
    exit;
}

/* ============================================================
   VOID BILLING TRANSACTION
   ============================================================ */
if ($action === 'void') {
    if ($module !== 'BILLING') { echo json_encode(['success'=>false,'message'=>'Only billing transactions can be voided.']); exit; }

    try {
        $s = $pdo->prepare("SELECT * FROM billing_records WHERE id = ?");
        $s->execute([$id]);
        $record = $s->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'Billing record not found.']); exit;
    }
    if (!$record) { echo json_encode(['success'=>false,'message'=>'Billing record not found.']); exit; }

    if (!empty($record['is_voided'])) {
        echo json_encode(['success'=>false,'message'=>'Transaction is already voided.']); exit;
    }

    $pdo->beginTransaction();
    try {
        // Store void reason + snapshot in dedicated table (history preservation)
        $pdo->prepare("INSERT INTO voided_transactions (billing_id, voided_by, voided_by_name, reason, original_snapshot)
                       VALUES (?,?,?,?,?)")
            ->execute([$id, $user_id, $full_name, $reason, json_encode($record)]);

        // Mark voided in main table (do NOT delete)
        $pdo->prepare("UPDATE billing_records
                       SET is_voided=1, void_reason=?, voided_by=?, voided_at=NOW()
                       WHERE id=?")
            ->execute([$reason, $user_id, $id]);

        logAudit($pdo, $user_id, $full_name, 'VOID', 'BILLING', $id, $reason,
                 "Voided billing transaction #$id");

        $pdo->commit();
        echo json_encode(['success'=>true,'message'=>"Billing #$id voided. Original history preserved."]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Void failed: '.$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'No action taken.']);