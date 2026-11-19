<?php
/* ============================================================
   print_receipt.php
   Printable official receipt for a billing record.

   URL forms:
     print_receipt.php?id=X             → single record
     print_receipt.php?id=X&auto=1      → single + auto print
     print_receipt.php?ids[]=X&ids[]=Y  → explicit batch

   SMART GROUPING:
     If a single ?id= is provided, the script looks at the record's
     remarks for the LATEST "[Payment ...]" signature, then finds
     every other bill that shares that signature and prints them
     all together. Uses the LAST signature — never the first —
     so reprints grab the correct transaction.

   Permission:
     billing / cashier / reception / front-desk (front-desk is read-only;
     this file never writes to the DB).
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

/* ===== PERMISSION GATE ===== */
$user_role    = strtolower(trim($_SESSION['role'] ?? ''));
$is_billing   = in_array($user_role, ['billing','cashier'], true)
             || strpos($user_role,'billing')  !== false
             || strpos($user_role,'cashier')  !== false;
$is_frontdesk = strpos($user_role,'reception') !== false
             || strpos($user_role,'front')     !== false;

if (!$is_billing && !$is_frontdesk) {
    header('Location: login.php?msg=access_denied');
    exit;
}

$cashier_name = $_SESSION['full_name'] ?? 'Cashier';
$auto_print   = !empty($_GET['auto']);

/* ===== COLLECT RECORD IDS ===== */
$record_ids = [];
$single_id  = (int)($_GET['id'] ?? 0);

if (!empty($_GET['ids']) && is_array($_GET['ids'])) {
    foreach ($_GET['ids'] as $v) {
        $v = (int)$v;
        if ($v > 0) $record_ids[] = $v;
    }
} elseif ($single_id > 0) {
    $record_ids = [$single_id];
}

$record_ids = array_values(array_unique($record_ids));
if (empty($record_ids)) { header('Location: billing_records.php'); exit; }

/* ===== LOAD RECORDS ===== */
$records = [];
try {
    $ph   = implode(',', array_fill(0, count($record_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id IN ($ph) ORDER BY date ASC, id ASC");
    $stmt->execute($record_ids);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $records = []; }
if (empty($records)) { header('Location: billing_records.php'); exit; }

/* ===== SMART GROUPING ===== */
if ($single_id > 0 && empty($_GET['ids'])) {
    $rec = null;
    foreach ($records as $r0) {
        if ((int)$r0['id'] === $single_id) { $rec = $r0; break; }
    }

    if ($rec) {
        $remarks = (string)($rec['remarks'] ?? '');
        if (preg_match_all('/(\[Payment\s+[^\]]+\])/u', $remarks, $m)) {
            $payment_sig = end($m[1]); // LATEST, not first

            $sig_escaped = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $payment_sig);
            $like        = '%' . $sig_escaped . '%';

            try {
                if (!empty($rec['patient_id'])) {
                    $stmt_group = $pdo->prepare(
                        "SELECT id FROM billing_records WHERE patient_id = ? AND date = ? AND remarks LIKE ?"
                    );
                    $stmt_group->execute([$rec['patient_id'], $rec['date'], $like]);
                } else {
                    $stmt_group = $pdo->prepare(
                        "SELECT id FROM billing_records WHERE LOWER(TRIM(patient_name)) = LOWER(TRIM(?)) AND date = ? AND remarks LIKE ?"
                    );
                    $stmt_group->execute([$rec['patient_name'], $rec['date'], $like]);
                }

                $found_ids = $stmt_group->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($found_ids)) {
                    $found_ids = array_map('intval', $found_ids);
                    $ph   = implode(',', array_fill(0, count($found_ids), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id IN ($ph) ORDER BY date ASC, id ASC");
                    $stmt->execute($found_ids);
                    $records    = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $record_ids = $found_ids;
                }
            } catch (PDOException $e) { /* keep single record on failure */ }
        }
    }
}

/* ================================================================
   FETCH PATIENT UNDERSTANDING (EXPLANATIONS)
   ================================================================ */
$unique_services = [];
foreach ($records as $rec) {
    if (!empty($rec['service_description'])) {
        $unique_services[$rec['service_description']] = true;
    }
}
$unique_services = array_keys($unique_services);

$explanations = [];
if (!empty($unique_services)) {
    $placeholders = implode(',', array_fill(0, count($unique_services), '?'));

    // 1. Fetch from `services` table
    try {
        $stmt = $pdo->prepare("SELECT service_name, patient_explanation FROM services WHERE service_name IN ($placeholders)");
        $stmt->execute($unique_services);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!empty($row['patient_explanation'])) {
                $explanations[$row['service_name']] = $row['patient_explanation'];
            }
        }
    } catch (PDOException $e) {}

    // 2. Fallback to `semantic_dictionary` for missing ones
    $missing = array_diff($unique_services, array_keys($explanations));
    if (!empty($missing)) {
        $placeholders_miss = implode(',', array_fill(0, count($missing), '?'));
        try {
            $stmt = $pdo->prepare("SELECT technical_term, patient_explanation FROM semantic_dictionary WHERE technical_term IN ($placeholders_miss)");
            $stmt->execute($missing);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['patient_explanation'])) {
                    $explanations[$row['technical_term']] = $row['patient_explanation'];
                }
            }
        } catch (PDOException $e) {}
    }
}

/* ===== PAYMENT METADATA ===== */
$payment_date   = '';
$payment_method = '';
$latest_sig_ts  = 0;
$latest_sig     = '';

foreach ($records as $rec) {
    $rmk = (string)($rec['remarks'] ?? '');
    if (preg_match_all('/(\[Payment\s+([^\]]+)\])/u', $rmk, $pm, PREG_SET_ORDER)) {
        foreach ($pm as $match) {
            $sig_full = $match[1];
            $sig_text = trim($match[2]);
            $ts       = strtotime($sig_text);
            if ($ts !== false && $ts > $latest_sig_ts) {
                $latest_sig_ts = $ts;
                $latest_sig    = $sig_full;
                $payment_date  = $sig_text;
            }
        }
    }
}

if ($latest_sig !== '') {
    foreach ($records as $rec) {
        $rmk = (string)($rec['remarks'] ?? '');
        $pos = strpos($rmk, $latest_sig);
        if ($pos !== false) {
            $tail = substr($rmk, $pos + strlen($latest_sig));
            $tail = explode("\n", $tail, 2)[0];
            if (preg_match('/^\s*([A-Za-z][A-Za-z\s]+?)\s*[—\-]/u', $tail, $mm)) {
                $payment_method = trim($mm[1]);
            }
            break;
        }
    }
    if ($payment_method === '') $payment_method = 'Cash';
}

/* ===== NON-SYSTEM REMARKS ===== */
$extra_remarks = [];
foreach ($records as $rec) {
    foreach (explode("\n", (string)($rec['remarks'] ?? '')) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '[') continue;
        if (!in_array($line, $extra_remarks, true)) $extra_remarks[] = $line;
    }
}

/* ===== TOTALS ===== */
$grand_total   = 0.0;
$grand_paid    = 0.0;
$grand_balance = 0.0;
$patients_seen = [];

foreach ($records as $rec) {
    $t = (float)($rec['total_amount'] ?? 0);
    $p = (float)($rec['amount_paid']  ?? 0);
    $grand_total   += $t;
    $grand_paid    += $p;
    $grand_balance += max(0, $t - $p);

    $key = ($rec['patient_db_id'] ?? 'x') . '|' . strtolower(trim($rec['patient_name'] ?? ''));
    $patients_seen[$key] = $rec['patient_name'] ?? 'Unknown';
}

$is_multi_patient = count($patients_seen) > 1;

/* ===== DEDUCTIONS BREAKDOWN ===== */
$deductions = [];
foreach ($records as $rec) {
    $rmk = (string)($rec['remarks'] ?? '');
    if (preg_match_all('/\[(VAT Exempt 12%|Discount:\s*[^\]]+|Insurance:\s*[^\]]+)\]\s*-\s*₱\s*([0-9\.,]+)/u', $rmk, $dm, PREG_SET_ORDER)) {
        foreach ($dm as $d) {
            $label  = trim($d[1]);
            $amount = (float)str_replace(',', '', $d[2]);
            if (!isset($deductions[$label])) $deductions[$label] = 0.0;
            $deductions[$label] += $amount;
        }
    }
}

/* ===== HEADER FIELDS ===== */
$receipt_no = 'OR-' . date('Ymd') . '-' . str_pad((string)min($record_ids), 5, '0', STR_PAD_LEFT);
$first_rec  = $records[0];

$patient_display = $is_multi_patient
    ? 'Multiple Patients (' . count($patients_seen) . ')'
    : ($first_rec['patient_name'] ?? 'Unknown Patient');
$patient_code    = $is_multi_patient ? '—' : ($first_rec['patient_id'] ?? '—');
$bill_count      = count($records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt <?= htmlspecialchars($receipt_no) ?> · Hospital Billing</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }

    :root {
        --accent:#0f766e;
        --accent-light:#14b8a6;
        --ink:#0f172a;
        --muted:#64748b;
        --line:#e2e8f0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background:#e5e7eb;
        color: var(--ink);
        min-height:100vh;
        padding: 24px 16px 40px;
        display:flex;
        flex-direction:column;
        align-items:center;
    }

    /* ===== Screen-only toolbar (Moved to bottom) ===== */
    .toolbar {
        display:flex;
        gap:10px;
        margin-top: 24px; /* Added margin-top */
        margin-bottom: 0; /* Removed margin-bottom */
        align-items:center;
        flex-wrap:wrap;
        justify-content:center;
    }
    .toolbar a, .toolbar button {
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:11px 22px;
        border-radius:10px;
        font-size:13.5px;
        font-weight:700;
        font-family:inherit;
        text-decoration:none;
        cursor:pointer;
        border:none;
        transition:all .2s;
    }
    .btn-primary {
        background: linear-gradient(135deg, var(--accent-light), var(--accent));
        color:#fff;
        box-shadow: 0 4px 12px rgba(15,118,110,.3);
    }
    .btn-primary:hover { filter:brightness(1.08); transform:translateY(-1px); }
    .btn-light {
        background:#fff;
        color:#475569;
        border:1.5px solid var(--line);
    }
    .btn-light:hover { background:#f8fafc; border-color:#cbd5e1; }

    /* ===== Receipt paper ===== */
    .receipt {
        background:#fff;
        width:100%;
        max-width:820px;
        border-radius:14px;
        box-shadow: 0 10px 40px rgba(15,23,42,.12);
        overflow:hidden;
    }

    .receipt-head {
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
        color:#fff;
        padding:26px 34px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
        flex-wrap:wrap;
    }
    .receipt-head .brand { display:flex; align-items:center; gap:14px; }
    .receipt-head .brand-icon {
        width:52px; height:52px;
        background:rgba(255,255,255,.18);
        border:1px solid rgba(255,255,255,.3);
        border-radius:12px;
        display:flex; align-items:center; justify-content:center;
        font-size:22px;
    }
    .receipt-head .brand h1 {
        font-size:20px; font-weight:800; letter-spacing:.3px; line-height:1.2;
    }
    .receipt-head .brand .sub {
        font-size:11.5px; opacity:.9; letter-spacing:.6px;
        text-transform:uppercase; margin-top:2px;
    }
    .receipt-head .meta { text-align:right; font-size:12px; line-height:1.6; }
    .receipt-head .meta .title {
        font-size:14px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase;
        opacity:.95;
    }
    .receipt-head .meta .rcpt-no {
        font-family:'Courier New', monospace;
        font-size:13px; font-weight:800; margin-top:4px;
    }
    .receipt-head .meta .stamp {
        font-size:10.5px; opacity:.85; margin-top:4px; letter-spacing:.3px;
    }

    .receipt-body { padding: 28px 34px; }

    /* Info grid */
    .info-grid {
        display:grid;
        grid-template-columns: 1.4fr 1fr;
        gap:18px;
        padding-bottom:22px;
        border-bottom: 1.5px dashed var(--line);
        margin-bottom:22px;
    }
    .info-block .lbl {
        font-size:10.5px; font-weight:700; letter-spacing:.6px;
        text-transform:uppercase; color: var(--muted); margin-bottom:5px;
    }
    .info-block .val {
        font-size:15px; font-weight:700; color: var(--ink); line-height:1.3;
    }
    .info-block .val.small {
        font-size:12.5px; font-weight:600; color:#475569; margin-top:2px;
    }

    /* Line items table */
    .items-table {
        width:100%; border-collapse:collapse; font-size:12.5px; margin-bottom:20px;
    }
    .items-table thead th {
        background:#f8fafc; color: var(--muted); text-align:left;
        padding:10px 12px; font-size:10px; font-weight:700;
        letter-spacing:.6px; text-transform:uppercase;
        border-top:1px solid var(--line);
        border-bottom:1px solid var(--line);
    }
    .items-table thead th.num { text-align:right; }
    .items-table tbody td {
        padding:11px 12px; border-bottom:1px solid #f1f5f9;
        color:#334155; vertical-align:top;
    }
    .items-table tbody td.num {
        text-align:right; font-variant-numeric: tabular-nums;
        font-weight:700; color: var(--ink); white-space:nowrap;
        vertical-align: middle;
    }
    .items-table tbody tr:last-child td { border-bottom:1px solid var(--line); }
    .items-table tbody td .inv {
        font-family:'Courier New', monospace; font-weight:700;
        color: var(--accent); font-size:11.5px;
    }
    .items-table tbody td .svc { font-weight:700; color: var(--ink); font-size:13px; }
    .items-table tbody td .cat { font-size:10.5px; color: var(--muted); margin-top:2px; }
    .items-table tbody td .patient-name { font-weight:700; color: var(--ink); }
    .items-table tbody td .patient-code { font-size:10.5px; color: var(--muted); margin-top:2px; }

    /* UPDATED: Patient Understanding Styling */
    .svc-exp {
        font-size: 11px; color: #475569; margin-top: 8px;
        padding: 6px 10px; background: #f0fdfa;
        border-left: 2px solid var(--accent-light);
        border-radius: 0 4px 4px 0;
        line-height: 1.5;
    }
    .svc-exp strong { color: var(--accent); }

    /* Totals */
    .totals {
        display:grid;
        grid-template-columns: 1fr 340px;
        gap:20px;
        margin-bottom:18px;
    }
    .totals .left-note {
        font-size:11.5px; color: var(--muted); line-height:1.6;
        padding:12px 14px;
        background:#f8fafc;
        border-left:3px solid var(--accent-light);
        border-radius:8px;
        align-self:start;
    }
    .totals .left-note strong { color: var(--ink); }
    .totals .right { display:flex; flex-direction:column; gap:6px; }
    .totals .row {
        display:flex; justify-content:space-between;
        padding:7px 12px; font-size:12.5px; color:#475569;
    }
    .totals .row.subhead {
        padding-top:12px; padding-bottom:4px;
        font-size:10px; font-weight:800; letter-spacing:.6px;
        text-transform:uppercase; color:#92400e;
    }
    .totals .row.deduction { color:#b45309; font-size:12px; padding:4px 12px 4px 24px; }
    .totals .row.deduction span:last-child { font-weight:600; }
    .totals .row.total-billed {
        background:#f1f5f9; border-radius:8px;
        padding:10px 14px; font-weight:800; font-size:13.5px;
        color: var(--ink); margin-top:4px;
    }
    .totals .row.paid {
        color:#065f46; font-weight:800; font-size:13.5px;
        padding: 10px 14px; background:#ecfdf5; border-radius:8px; margin-top:6px;
    }
    .totals .row.balance {
        color:#991b1b; font-weight:800; font-size:13.5px;
        padding: 10px 14px; background:#fef2f2; border-radius:8px;
    }
    .totals .row.balance.zero { color:#065f46; background:#ecfdf5; }

    /* Payment meta strip */
    .pay-meta {
        display:flex; gap:24px; flex-wrap:wrap;
        padding:14px 18px;
        background:#f8fafc;
        border:1px solid var(--line);
        border-radius:10px;
        margin-bottom:24px;
        font-size:12px;
    }
    .pay-meta .item { display:flex; flex-direction:column; gap:3px; }
    .pay-meta .item .lbl {
        font-size:10px; font-weight:700; letter-spacing:.6px;
        text-transform:uppercase; color: var(--muted);
    }
    .pay-meta .item .val { font-weight:700; color: var(--ink); font-size:12.5px; }

    /* Signatures */
    .signatures {
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap:36px;
        margin-top:26px;
        padding-top:18px;
        border-top:1.5px dashed var(--line);
    }
    .signatures .sig { text-align:center; padding-top:36px; }
    .signatures .sig .name {
        font-size:12.5px; font-weight:700; color: var(--ink);
        margin-bottom:4px;
    }
    .signatures .sig .line {
        border-top:1.5px solid #94a3b8;
        padding-top:8px;
        font-size:11px; font-weight:600; color: var(--muted);
        letter-spacing:.4px; text-transform:uppercase;
    }

    .receipt-footer {
        padding:16px 34px 22px;
        text-align:center;
        font-size:10.5px;
        color: var(--muted);
        border-top:1px solid #f1f5f9;
        background:#fafbfc;
        line-height:1.6;
    }
    .receipt-footer strong { color:#334155; }

    @media (max-width:640px) {
        .receipt-head { flex-direction:column; align-items:flex-start; text-align:left; }
        .receipt-head .meta { text-align:left; }
        .receipt-body { padding: 22px 20px; }
        .info-grid { grid-template-columns:1fr; gap:12px; }
        .totals { grid-template-columns:1fr; }
        .signatures { grid-template-columns:1fr; gap:24px; }
        .items-table thead th, .items-table tbody td { padding:8px 8px; font-size:11.5px; }
    }

    @media print {
        @page { size: A4; margin: 12mm; }
        body {
            background:#fff;
            padding:0;
            display:block;
        }
        .toolbar { display:none !important; }
        .receipt {
            max-width:none;
            width:100%;
            box-shadow:none;
            border-radius:0;
            border:none;
        }
        .receipt-head { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .items-table thead th,
        .totals .row.total-billed,
        .totals .row.paid,
        .totals .row.balance,
        .svc-exp {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .items-table tbody tr { page-break-inside: avoid; }
        .signatures { page-break-inside: avoid; }
        .receipt { page-break-after: avoid; }
    }
</style>
</head>
<body>

<!-- ===== Receipt ===== -->
<div class="receipt">

    <!-- Header -->
    <div class="receipt-head">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-heart-pulse"></i></div>
            <div>
                <h1>Hospital Billing System</h1>
                <div class="sub">Official Payment Receipt</div>
            </div>
        </div>
        <div class="meta">
            <div class="title">Official Receipt</div>
            <div class="rcpt-no"><?= htmlspecialchars($receipt_no) ?></div>
            <div class="stamp">Printed <?= date('M d, Y g:i A') ?></div>
        </div>
    </div>

    <!-- Body -->
    <div class="receipt-body">

        <!-- Info grid -->
        <div class="info-grid">
            <div class="info-block">
                <div class="lbl">Received From</div>
                <div class="val"><?= htmlspecialchars($patient_display) ?></div>
                <?php if (!$is_multi_patient): ?>
                    <div class="val small">Patient Code: <?= htmlspecialchars($patient_code ?: '—') ?></div>
                <?php else: ?>
                    <div class="val small">
                        <?= count($patients_seen) ?> patients · <?= $bill_count ?> bill(s)
                    </div>
                <?php endif; ?>
            </div>
            <div class="info-block">
                <div class="lbl">Payment Date</div>
                <div class="val">
                    <?= htmlspecialchars($payment_date ?: date('M d, Y g:i A')) ?>
                </div>
                <div class="val small"><?= $bill_count ?> service bill(s)</div>
            </div>
        </div>

        <!-- Line items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:90px;">Invoice</th>
                    <th style="width:100px;">Date</th>
                    <?php if ($is_multi_patient): ?>
                        <th style="width:180px;">Patient</th>
                    <?php endif; ?>
                    <th>Service Details</th>
                    <th class="num" style="width:130px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $rec):
                    $t = (float)($rec['total_amount'] ?? 0);
                    $svc_name = $rec['service_description'] ?? '';
                    $svc_exp  = $explanations[$svc_name] ?? '';
                ?>
                <tr>
                    <td><span class="inv">#<?= str_pad((string)$rec['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                    <td><?= date('M d, Y', strtotime($rec['date'])) ?></td>
                    <?php if ($is_multi_patient): ?>
                    <td>
                        <div class="patient-name"><?= htmlspecialchars($rec['patient_name'] ?? 'Unknown') ?></div>
                        <?php if (!empty($rec['patient_id'])): ?>
                            <div class="patient-code"><?= htmlspecialchars($rec['patient_id']) ?></div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <div class="svc"><?= htmlspecialchars($svc_name ?: '—') ?></div>
                        <div class="cat"><?= htmlspecialchars($rec['category'] ?? '—') ?></div>
                        
                        <?php if (!empty($svc_exp)): ?>
                            <div class="svc-exp">
                                <strong>Patient Understanding:</strong><br>
                                <?= htmlspecialchars($svc_exp) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="num">₱ <?= number_format($t, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="left-note">
                <?php if ($grand_balance > 0): ?>
                    <strong>Partial payment.</strong>
                    Outstanding balance across these bills is
                    <strong>₱ <?= number_format($grand_balance, 2) ?></strong>.
                <?php else: ?>
                    <strong>Paid in full.</strong> These bills have been
                    fully settled. Thank you.
                <?php endif; ?>
            </div>
            <div class="right">
                <div class="row total-billed">
                    <span>Total Billed</span>
                    <span>₱ <?= number_format($grand_total, 2) ?></span>
                </div>

                <?php if (!empty($deductions)): ?>
                    <div class="row subhead">
                        <span colspan="2">Deductions (already applied)</span>
                    </div>
                    <?php foreach ($deductions as $label => $amt): ?>
                        <div class="row deduction">
                            <span>– <?= htmlspecialchars($label) ?></span>
                            <span>– ₱ <?= number_format($amt, 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="row paid">
                    <span>Amount Paid</span>
                    <span>₱ <?= number_format($grand_paid, 2) ?></span>
                </div>
                <div class="row balance <?= $grand_balance <= 0 ? 'zero' : '' ?>">
                    <span><?= $grand_balance <= 0 ? 'Balance (Settled)' : 'Balance Due' ?></span>
                    <span>₱ <?= number_format($grand_balance, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Payment meta -->
        <div class="pay-meta">
            <div class="item">
                <span class="lbl">Payment Method</span>
                <span class="val"><?= htmlspecialchars($payment_method ?: 'Cash') ?></span>
            </div>
            <div class="item">
                <span class="lbl">Printed By</span>
                <span class="val"><?= htmlspecialchars($cashier_name) ?></span>
            </div>
            <div class="item">
                <span class="lbl">Bills Covered</span>
                <span class="val"><?= $bill_count ?></span>
            </div>
            <?php if (!empty($extra_remarks)): ?>
            <div class="item" style="flex:1;min-width:220px;">
                <span class="lbl">Remarks</span>
                <span class="val" style="font-weight:500;font-size:12px;color:#475569;">
                    <?= htmlspecialchars(implode(' · ', array_slice($extra_remarks, 0, 2))) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig">
                <div class="name"><?= htmlspecialchars($cashier_name) ?></div>
                <div class="line">Cashier / Received By</div>
            </div>
            <div class="sig">
                <div class="name"><?= htmlspecialchars($patient_display) ?></div>
                <div class="line">Patient / Payor Signature</div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="receipt-footer">
        <strong>Hospital Billing System</strong> · This is a computer-generated receipt.<br>
        Please keep this for your records. For inquiries, contact the Billing Department.<br>
        Receipt No: <?= htmlspecialchars($receipt_no) ?> · Printed <?= date('M d, Y g:i A') ?>
    </div>

</div>

<!-- ===== Screen-only toolbar (Moved to bottom) ===== -->
<div class="toolbar">
    <button class="btn-primary" type="button" onclick="window.print()">
        <i class="fas fa-print"></i> Print Receipt
    </button>
    <a class="btn-light" href="billing_records.php">
        <i class="fas fa-arrow-left"></i> Back to Records
    </a>
</div>

<script>
    window.addEventListener('DOMContentLoaded', function () {
        <?php if ($auto_print): ?>
        setTimeout(function () { window.print(); }, 350);
        <?php endif; ?>
    });
</script>

</body>
</html>