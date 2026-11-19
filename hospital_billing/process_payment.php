<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$r = strtolower(trim($user_role));

$is_billing = in_array($r, ['billing','cashier'], true)
           || strpos($r,'billing') !== false
           || strpos($r,'cashier') !== false;
if (!$is_billing) { header('Location: login.php?msg=access_denied'); exit; }
$is_cashier_only = (strpos($r,'cashier') !== false) && (strpos($r,'billing') === false);

$success = ''; $error = '';

/* ================================================================
   STATUTORY DISCOUNT CLASSIFIER
   ================================================================ */
function classify_statutory_discount($name) {
    $n = strtolower(trim($name ?? ''));
    if ($n === '') return null;
    if (strpos($n, 'senior') !== false) return 'senior';
    if (preg_match('/\bsc\b/', $n))     return 'senior';
    if (strpos($n, 'pwd') !== false)    return 'pwd';
    if (preg_match('/p\.?\s?w\.?\s?d\.?/', $n)) return 'pwd';
    if (strpos($n, 'person with disab') !== false) return 'pwd';
    if (strpos($n, 'disability') !== false) return 'pwd';
    return null;
}

/* ================================================================
   MANUAL-TYPE DISCOUNT CLASSIFIER (Employee / Indigent)
   These discounts let the cashier pick % or ₱ and type the value.
   ================================================================ */
function classify_manual_discount($name) {
    $n = strtolower(trim($name ?? ''));
    if ($n === '') return null;
    if (strpos($n, 'employee') !== false) return 'employee';
    if (strpos($n, 'empleyado') !== false) return 'employee';
    if (strpos($n, 'indigent') !== false) return 'indigent';
    if (strpos($n, 'indigency') !== false) return 'indigent';
    return null;
}

/* ================================================================
   LOAD DEDUCTION CATALOG FROM ADMIN
   ================================================================ */
$admin_discounts  = [];
$admin_insurances = [];

try {
    $admin_discounts = $pdo->query("SELECT * FROM discount_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $admin_discounts = []; }

try {
    $admin_insurances = $pdo->query("SELECT * FROM insurance_providers ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $admin_insurances = []; }

/* Discounts: classify statutory + manual + force PhilHealth fixed */
foreach ($admin_discounts as &$d) {
    $is_ph = stripos($d['discount_name'] ?? '', 'philhealth') !== false;
    $d['is_philhealth']   = $is_ph;
    $d['statutory_type']  = classify_statutory_discount($d['discount_name'] ?? '');
    $d['manual_type']     = $is_ph ? null : classify_manual_discount($d['discount_name'] ?? '');
    if ($is_ph) {
        $d['payment_type'] = 'fixed';
        $d['discount_percentage'] = 0;
    }
}
unset($d);

/* Insurances: classify + force PhilHealth fixed + strip Senior/PWD */
foreach ($admin_insurances as &$i) {
    $is_ph = stripos($i['provider_name'] ?? '', 'philhealth') !== false;
    $i['is_philhealth']  = $is_ph;
    $i['statutory_type'] = classify_statutory_discount($i['provider_name'] ?? '');
    $i['payment_type'] = 'fixed';
    $i['coverage_percentage'] = 0;
}
unset($i);

$admin_insurances = array_values(array_filter($admin_insurances, function($i) {
    return empty($i['statutory_type']);
}));

/* ================================================================
   FETCH BILLS (SINGLE + BATCH SUPPORT)
   ================================================================ */
$record_id = (int)($_GET['id'] ?? $_POST['record_id'] ?? 0);
$batch_ids = [];

if (!empty($_GET['batch']) && !empty($_GET['ids']) && is_array($_GET['ids'])) {
    foreach ($_GET['ids'] as $v) {
        $v = (int)$v;
        if ($v > 0) $batch_ids[] = $v;
    }
}
if (!empty($_POST['batch_ids']) && is_array($_POST['batch_ids'])) {
    foreach ($_POST['batch_ids'] as $v) {
        $v = (int)$v;
        if ($v > 0) $batch_ids[] = $v;
    }
}
$batch_ids = array_values(array_unique($batch_ids));
$is_batch  = !empty($batch_ids);

$bills         = [];
$patient_db_id = 0;
$record        = null;

try {
    if ($is_batch) {
        $ph = implode(',', array_fill(0, count($batch_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id IN ($ph) ORDER BY date ASC, id ASC");
        $stmt->execute($batch_ids);
        $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($bills)) { header('Location: billing_records.php'); exit; }
        $record = $bills[0];
        $record_id = (int)$record['id'];
    } else {
        if ($record_id <= 0) { header('Location: billing_records.php'); exit; }
        $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) { header('Location: billing_records.php'); exit; }

        $patient_db_id = (int)($record['patient_db_id'] ?? 0);
        if ($patient_db_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE patient_db_id = ? AND status IN ('Pending','Processing') ORDER BY date ASC, id ASC");
            $stmt->execute([$patient_db_id]);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (empty($bills) && !empty($record['patient_name'])) {
            $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE LOWER(TRIM(patient_name)) = LOWER(TRIM(?)) AND status IN ('Pending','Processing') ORDER BY date ASC, id ASC");
            $stmt->execute([$record['patient_name']]);
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (empty($bills) && $record['status'] !== 'Paid') { $bills = [$record]; }
    }
} catch (PDOException $e) { $bills = []; }
if (!$record) { header('Location: billing_records.php'); exit; }

/* ===== PATIENT AGGREGATION ===== */
$unique_patients = [];
foreach ($bills as $b) {
    $key = ($b['patient_db_id'] ?? 'x') . '|' . strtolower(trim($b['patient_name'] ?? ''));
    $unique_patients[$key] = $b['patient_name'] ?? 'Unknown';
}
$patient_count    = count($unique_patients);
$is_multi_patient = ($patient_count > 1);

/* ===== GRAND TOTALS ===== */
$grand_total = 0; $grand_paid = 0; $grand_balance = 0;
foreach ($bills as $b) {
    $grand_total   += (float)$b['total_amount'];
    $grand_paid    += (float)($b['amount_paid'] ?? 0);
    $grand_balance += max(0, (float)$b['total_amount'] - (float)($b['amount_paid'] ?? 0));
}

$is_locked    = ($grand_balance <= 0);
$is_first_pay = (!$is_batch && $grand_paid <= 0);

$remarks_blob = (string)($record['remarks'] ?? '');
$has_insurance = (strpos($remarks_blob, '[VAT Exempt 12%]') !== false
               || strpos($remarks_blob, '[Discount:') !== false
               || strpos($remarks_blob, '[Insurance:') !== false
               || strpos($remarks_blob, '[Insurance]') !== false);

$allow_deductions = (!$is_batch && !$is_locked && $is_first_pay && !$has_insurance);

/* ================================================================
   SAVE PAYMENT
   ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {

    if ($is_locked) {
        $error = 'All bills are <strong>fully paid</strong>.';
    } else {
        $pay_amount     = (float)($_POST['pay_amount'] ?? 0);
        $payment_method = trim($_POST['payment_method'] ?? 'Cash');
        $remarks        = trim($_POST['remarks'] ?? '');
        $vat_exempt     = isset($_POST['vat_exempt']) && $_POST['vat_exempt'] === '1';

        // --- Guarantee Letter Logic ---
        $gl_upload_path = '';
        if ($payment_method === 'Guarantee Letter') {
            $gl_agency = trim($_POST['gl_agency'] ?? '');
            $gl_ref    = trim($_POST['gl_ref_no'] ?? '');
            $gl_amt    = (float)($_POST['gl_amount'] ?? 0);

            if (isset($_FILES['gl_document']) && $_FILES['gl_document']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/gl_docs/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                $file_ext = strtolower(pathinfo($_FILES['gl_document']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];

                if (in_array($file_ext, $allowed_ext)) {
                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $gl_ref) . '.' . $file_ext;
                    $target_path = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['gl_document']['tmp_name'], $target_path)) {
                        $gl_upload_path = $target_path;
                    }
                }
            }

            $remarks .= ($remarks ? "\n" : '') . "[GL Info] Agency: $gl_agency | Ref: $gl_ref | Approved: ₱" . number_format($gl_amt, 2);
            if ($gl_upload_path) { $remarks .= " | Doc: " . basename($gl_upload_path); }
        }

        $selected_discount_ids  = array_map('intval', $_POST['selected_discounts']  ?? []);
        $selected_insurance_ids = array_map('intval', $_POST['selected_insurance']  ?? []);

        $senior_selected = false;
        $pwd_selected    = false;
        foreach ($admin_discounts as $d) {
            if (!in_array((int)$d['id'], $selected_discount_ids, true)) continue;
            if (($d['statutory_type'] ?? null) === 'senior') $senior_selected = true;
            if (($d['statutory_type'] ?? null) === 'pwd')    $pwd_selected    = true;
        }

        $clean_insurance_ids = [];
        foreach ($selected_insurance_ids as $iid) {
            foreach ($admin_insurances as $ins) {
                if ((int)$ins['id'] === $iid) { $clean_insurance_ids[] = $iid; break; }
            }
        }
        $selected_insurance_ids = $clean_insurance_ids;

        if ($pay_amount <= 0 && $payment_method !== 'Guarantee Letter') {
            $error = 'Please enter a valid amount.';
        } elseif ($senior_selected && $pwd_selected) {
            $error = '⚠️ <strong>Senior Citizen and PWD discounts cannot be applied at the same time.</strong>';
        } else {
            try {
                $pdo->beginTransaction();

                if ($allow_deductions && ($vat_exempt || !empty($selected_discount_ids) || !empty($selected_insurance_ids))) {
                    $original_grand = $grand_total;
                    $vat_total = 0;
                    if ($vat_exempt && $original_grand > 0) {
                        $vat_total = round($original_grand - ($original_grand / 1.12), 2);
                    }
                    $discount_total = 0; $discount_details = [];
                    foreach ($admin_discounts as $d) {
                        if (!in_array((int)$d['id'], $selected_discount_ids, true)) continue;
                        $is_ph = !empty($d['is_philhealth']) || stripos($d['discount_name'] ?? '', 'philhealth') !== false;

                        /* ========== MANUAL TYPE (Employee / Indigent) ========== */
                        if (!empty($d['manual_type'])) {
                            $user_type = $_POST['discount_types'][(int)$d['id']] ?? 'percent';
                            $user_val  = max(0, (float)($_POST['discount_amounts'][(int)$d['id']] ?? 0));
                            if ($user_type === 'percent') {
                                $amt = round($original_grand * ($user_val / 100), 2);
                            } else {
                                $amt = $user_val;
                            }
                        } else {
                            /* ========== EXISTING LOGIC ========== */
                            $ptype = ($is_ph || ($d['payment_type'] ?? 'percent') === 'fixed') ? 'fixed' : 'percent';
                            $amt = ($ptype === 'fixed')
                                 ? max(0, (float)($_POST['discount_amounts'][(int)$d['id']] ?? 0))
                                 : round($original_grand * ((float)($d['discount_percentage'] ?? 0) / 100), 2);
                        }

                        if ($amt > 0) { $discount_total += $amt; $discount_details[] = ['name' => $d['discount_name'], 'amount' => $amt]; }
                    }
                    $insurance_total = 0; $insurance_details = [];
                    foreach ($admin_insurances as $i) {
                        if (!in_array((int)$i['id'], $selected_insurance_ids, true)) continue;
                        $amt = max(0, (float)($_POST['insurance_amounts'][(int)$i['id']] ?? 0));
                        if ($amt > 0) { $insurance_total += $amt; $insurance_details[] = ['name' => $i['provider_name'], 'amount' => $amt]; }
                    }

                    $total_deduction = $vat_total + $discount_total + $insurance_total;
                    if ($total_deduction > $original_grand) $total_deduction = $original_grand;

                    if ($total_deduction > 0 && $original_grand > 0) {
                        $allocated = 0; $last_idx = count($bills) - 1;
                        foreach ($bills as $idx => $b) {
                            $b_orig = (float)$b['total_amount'];
                            $share_total = ($idx === $last_idx) ? round($total_deduction - $allocated, 2) : round($total_deduction * ($b_orig / $original_grand), 2);
                            if ($share_total > $b_orig) $share_total = $b_orig;
                            if ($share_total < 0) $share_total = 0;

                            $ratio = $b_orig / $original_grand;
                            $note = trim($b['remarks'] ?? '');
                            if ($vat_total > 0) $note .= ($note ? "\n" : '') . "[VAT Exempt 12%] -₱" . number_format(round($vat_total * $ratio, 2), 2);
                            foreach ($discount_details as $dd) $note .= ($note ? "\n" : '') . "[Discount: " . $dd['name'] . "] -₱" . number_format(round($dd['amount'] * $ratio, 2), 2);
                            foreach ($insurance_details as $ins) $note .= ($note ? "\n" : '') . "[Insurance: " . $ins['name'] . "] -₱" . number_format(round($ins['amount'] * $ratio, 2), 2);

                            $new_total = max(0, $b_orig - $share_total);
                            $pdo->prepare("UPDATE billing_records SET total_amount = ?, remarks = ? WHERE id = ?")->execute([$new_total, $note, $b['id']]);

                            $bills[$idx]['total_amount'] = $new_total;
                            $bills[$idx]['remarks'] = $note;
                            $allocated += $share_total;
                        }
                        $grand_total = 0; $grand_paid = 0;
                        foreach ($bills as $b) { $grand_total += (float)$b['total_amount']; $grand_paid += (float)($b['amount_paid'] ?? 0); }
                        $grand_balance = max(0, $grand_total - $grand_paid);
                    }
                }

                if ($pay_amount > $grand_balance) $pay_amount = $grand_balance;

                $remaining = $pay_amount; $updated = 0; $fullyPaid = 0;
                foreach ($bills as $idx => $b) {
                    if ($remaining <= 0) break;
                    $b_total = (float)$b['total_amount']; $b_paid = (float)($b['amount_paid'] ?? 0);
                    $b_balance = max(0, $b_total - $b_paid);
                    if ($b_balance <= 0) continue;

                    $apply = min($remaining, $b_balance);
                    $new_paid = $b_paid + $apply;
                    $new_balance = max(0, $b_total - $new_paid);
                    $new_status = ($new_balance <= 0) ? 'Paid' : 'Processing';

                    $note = trim($bills[$idx]['remarks'] ?? $b['remarks'] ?? '');
                    $note .= ($note ? "\n" : '') . "[Payment " . date('M d, Y g:i A') . "] " . ($payment_method ?: 'Cash') . " — ₱" . number_format($apply, 2) . " (Balance: ₱" . number_format($new_balance, 2) . ")";
                    if ($remarks !== '' && $updated === 0) $note .= ($note ? "\n" : '') . $remarks;

                    $pdo->prepare("UPDATE billing_records SET amount_paid = ?, status = ?, remarks = ? WHERE id = ?")->execute([$new_paid, $new_status, $note, $b['id']]);

                    $remaining -= $apply; $updated++;
                    if ($new_status === 'Paid') $fullyPaid++;
                }

                try {
                    if (!empty($record['created_by'])) {
                        $msg = "₱" . number_format($pay_amount, 2) . " received from " . $record['patient_name'] . ($is_batch ? " (batch)" : "") . " via {$payment_method}";
                        $pdo->prepare("INSERT INTO notifications (user_id, icon, color, message, link, is_read) VALUES (?,?,?,?,?,?)")->execute([$record['created_by'], 'fa-money-bill-wave', '#10b981', $msg, 'billing_records.php', 0]);
                    }
                } catch (PDOException $e) {}

                $pdo->commit();

                /* ── Refresh state so the UI matches the new DB state ── */
                if ($is_batch) {
                    $ph = implode(',', array_fill(0, count($batch_ids), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id IN ($ph) ORDER BY date ASC, id ASC");
                    $stmt->execute($batch_ids);
                    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $record = $bills[0] ?? $record;
                } elseif ($patient_db_id > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE patient_db_id = ? AND status IN ('Pending','Processing') ORDER BY date ASC, id ASC");
                    $stmt->execute([$patient_db_id]);
                    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id = ?");
                    $stmt->execute([$record_id]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC) ?: $record;
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM billing_records WHERE id = ?");
                    $stmt->execute([$record_id]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC) ?: $record;
                }

                $grand_total = 0; $grand_paid = 0; $grand_balance = 0;
                foreach ($bills as $b) {
                    $grand_total += (float)$b['total_amount']; $grand_paid += (float)($b['amount_paid'] ?? 0);
                    $grand_balance += max(0, (float)$b['total_amount'] - (float)($b['amount_paid'] ?? 0));
                }

                /* ── Recompute flags used by the template ── */
                $is_locked    = ($grand_balance <= 0);
                $is_first_pay = (!$is_batch && $grand_paid <= 0);
                $remarks_blob = (string)($record['remarks'] ?? '');
                $has_insurance = (strpos($remarks_blob, '[VAT Exempt 12%]') !== false
                               || strpos($remarks_blob, '[Discount:') !== false
                               || strpos($remarks_blob, '[Insurance:') !== false
                               || strpos($remarks_blob, '[Insurance]') !== false);
                $allow_deductions = (!$is_batch && !$is_locked && $is_first_pay && !$has_insurance);

                $success = "Received <strong>₱" . number_format($pay_amount, 2) . "</strong> via {$payment_method}. {$updated} bill(s) updated" . ($fullyPaid > 0 ? ", {$fullyPaid} fully paid" : "") . ".<br>Remaining balance: <strong>₱" . number_format($grand_balance, 2) . "</strong>";

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$initials = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $is_batch ? 'Batch Payment' : 'Process Payment' ?> · <?= $is_cashier_only ? 'Cashier' : 'Billing Staff' ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#ffffff;display:flex;min-height:100vh;color:#1e293b}
.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}
.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:#0f766e;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}
.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f8fafc;color:#0f766e}
.sidebar-menu li a:hover i{color:#0f766e}
.sidebar-menu li a.active{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}
.main{margin-left:270px;flex:1;padding:28px 35px 40px;min-height:100vh}
.top-bar{display:flex;justify-content:flex-end;align-items:center;gap:15px;margin-bottom:22px}
.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:#0f766e;font-weight:600;text-transform:uppercase;margin-top:2px}
.page-head{max-width:1000px;margin:0 auto 22px auto}
.page-head h1{font-size:24px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:12px}
.page-head h1 .h-icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 6px 16px rgba(20,184,166,.35)}
.alert{max-width:1000px;margin:0 auto 20px auto;padding:13px 18px;border-radius:11px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:11px;border:1px solid}
.alert-success{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
.alert-danger{background:#fef2f2;color:#991b1b;border-color:#fecaca}
.alert-info{background:#eff6ff;color:#1e40af;border-color:#bfdbfe}
.alert a{margin-left:auto;color:inherit;font-weight:700;text-decoration:none}
.floating-card{max-width:1000px;margin:0 auto;background:#fff;border-radius:18px;box-shadow:0 10px 40px rgba(15,118,110,.08);border:1px solid #ccfbf1;overflow:hidden}
.floating-card.locked{border:2px solid #a7f3d0}
.section{padding:22px 32px 8px}
.section-title{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.section-title .s-icon{width:38px;height:38px;border-radius:10px;background:#f0fdfa;color:#0f766e;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.section-title .s-text .s-main{font-size:15px;font-weight:700;color:#0f172a}
.section-title .s-text .s-sub{font-size:11.5px;color:#64748b;margin-top:2px}
.info-panel{background:linear-gradient(135deg,#f0fdfa,#e0f7f4);border:1.5px solid #99f6e4;border-radius:14px;padding:20px 24px;margin:0 32px 22px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.info-item .lbl{font-size:11px;color:#5a6b8c;font-weight:600;text-transform:uppercase;letter-spacing:.4px;display:flex;align-items:center;gap:6px;margin-bottom:6px}
.info-item .lbl i{font-size:10px;color:#0f766e}
.info-item .val{font-size:14px;font-weight:600;color:#0f172a}
.amount-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:0 32px 22px}
@media(max-width:1000px){.amount-cards{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.amount-cards{grid-template-columns:1fr}}
.amount-card{border-radius:14px;padding:16px 18px;color:#fff;position:relative;overflow:hidden}
.amount-card.total{background:linear-gradient(135deg,#0f766e,#14b8a6)}
.amount-card.discount{background:linear-gradient(135deg,#f59e0b,#fbbf24)}
.amount-card.paid{background:linear-gradient(135deg,#10b981,#34d399)}
.amount-card.balance{background:linear-gradient(135deg,#dc2626,#f87171)}
.amount-card.balance.zero{background:linear-gradient(135deg,#10b981,#059669)}
.amount-card .lbl{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.92;display:flex;align-items:center;gap:6px}
.amount-card .val{font-size:20px;font-weight:800;margin-top:8px;line-height:1.1}
.amount-card .sub{font-size:10.5px;opacity:.85;margin-top:4px}
.deduction-panel{margin:0 32px 22px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:14px;padding:20px 22px}
.deduction-panel h4{font-size:13px;font-weight:700;color:#92400e;margin-bottom:14px;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:.4px}
.deduction-panel h4 i{color:#f59e0b}
.deduction-group{margin-bottom:14px}
.deduction-group:last-child{margin-bottom:0}
.deduction-group .group-title{font-size:11.5px;font-weight:700;color:#78350f;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.deduction-group .group-title i{font-size:11px}
.deduction-row{display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fff;border:1.5px solid #fde68a;border-radius:10px;margin-bottom:8px;transition:all .2s}
.deduction-row:hover{border-color:#f59e0b}
.deduction-row.active{border-color:#0f766e;background:#f0fdfa}
.deduction-row.philhealth{border-color:#fbbf24;background:#fffbeb}
.deduction-row.philhealth.active{border-color:#0f766e;background:#f0fdfa}
.deduction-row .chk-label{display:flex;align-items:center;gap:10px;cursor:pointer;flex:1;min-width:0}
.deduction-row .chk-label input{width:18px;height:18px;accent-color:#0f766e;cursor:pointer;flex-shrink:0}
.deduction-row .chk-label .name{font-size:13px;font-weight:600;color:#0f172a}
.deduction-row .chk-label .meta{font-size:11.5px;color:#64748b;margin-top:2px;display:block}
.deduction-row .chk-label .meta strong{color:#0f766e}
.deduction-row .fixed-input{width:140px;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;text-align:right;background:#fff}
.deduction-row .fixed-input:focus{outline:none;border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.1)}
/* === NEW: percent/peso type selector for manual discounts (employee/indigent) === */
.deduction-row .type-select{width:72px;padding:8px 6px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;background:#fff;text-align:center;cursor:pointer;flex-shrink:0;font-weight:700;color:#0f766e}
.deduction-row .type-select:focus{outline:none;border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.1)}
.deduction-row .type-select:hover{border-color:#14b8a6}
.deduction-row.exclusive-locked{opacity:.55;background:#f8fafc;border-style:dashed}
.deduction-row.exclusive-locked .chk-label input{cursor:not-allowed}
.deduction-row.statutory{border-left:3px solid #f59e0b}
.manual-badge{font-size:10px;background:#dbeafe;color:#1e40af;padding:2px 7px;border-radius:8px;margin-left:6px;font-weight:800;border:1px solid #93c5fd;letter-spacing:.3px}
.ph-badge{font-size:10px;background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:8px;margin-left:6px;font-weight:800;border:1px solid #fcd34d;letter-spacing:.3px}
.ins-info{background:#fff;border:1.5px solid #fbbf24;border-radius:10px;padding:12px 14px;font-size:12.5px;color:#92400e;font-weight:600;display:flex;align-items:center;gap:8px;margin-top:14px;line-height:1.6}
.bills-wrap{margin:0 32px 22px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden}
.bills-head{padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center}
.bills-head h4{font-size:13px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:.4px}
.bills-head h4 i{color:#0f766e}
.bills-head .count{background:#0f766e;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;margin-left:6px}
.bills-table{width:100%;border-collapse:collapse;font-size:12.5px}
.bills-table thead th{text-align:left;padding:10px 16px;background:#fafbfc;color:#64748b;font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
.bills-table tbody td{padding:12px 16px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
.bills-table tbody tr:last-child td{border-bottom:none}
.bills-table tbody tr:hover{background:#f8fafc}
.bills-table .inv{font-family:'Courier New',monospace;font-weight:700;color:#0f766e;font-size:12px}
.bills-table .svc{font-weight:600;color:#0f172a}
.bills-table .svc small{display:block;font-size:10.5px;color:#94a3b8;font-weight:400;margin-top:2px}
.bills-table .num{text-align:right;font-weight:700}
.bills-table .num.paid{color:#10b981}
.bills-table .num.bal{color:#dc2626}
.bills-table .num.bal.zero{color:#10b981}
.bills-table .patient-cell{font-weight:600;color:#0f172a}
.bills-table .patient-cell small{display:block;font-weight:400;color:#94a3b8;font-size:10.5px;margin-top:2px}
.status-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:12px;font-size:10.5px;font-weight:700}
.status-pill.Paid{background:#d1fae5;color:#065f46}
.status-pill.Pending{background:#fef3c7;color:#92400e}
.status-pill.Processing{background:#fef3c7;color:#92400e}
.status-pill.Partial{background:#fef3c7;color:#92400e}
.status-pill i{font-size:8px}
.fg{margin-bottom:15px}
.fg label{display:flex;align-items:center;gap:6px;font-weight:600;color:#334155;font-size:12.5px;margin-bottom:7px}
.fg label i{color:#0f766e;font-size:11px}
.fg label .req{color:#dc2626}
.fg label .opt{color:#94a3b8;font-weight:400;margin-left:2px;font-size:11.5px}
.fg input,.fg select,.fg textarea{width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:11px;font-size:14px;background:#fff;font-family:inherit;transition:all .2s;color:#1e293b}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12)}
.fg textarea{resize:vertical;min-height:60px}
.fg input[readonly]{background:#f0fdfa;font-weight:800;color:#0f766e}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:700px){.row{grid-template-columns:1fr}}
.gl-container{margin-top:15px;padding:18px;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:12px;}
.gl-container h5{font-size:13px;font-weight:700;color:#0f766e;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.upload-box{border:2px dashed #cbd5e1;padding:24px;text-align:center;border-radius:11px;cursor:pointer;background:#fff;transition:all 0.2s;}
.upload-box:hover{border-color:#14b8a6;background:#f0fdfa;}
.upload-box i{font-size:28px;color:#94a3b8;margin-bottom:10px;}
.upload-box .u-title{font-weight:700;color:#334155;font-size:14px;}
.upload-box .u-sub{font-size:11.5px;color:#64748b;margin-top:4px;}
.actions{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:20px 32px 26px;border-top:1px solid #f1f5f9;flex-wrap:wrap}
.btn{padding:13px 26px;border:none;border-radius:11px;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:9px;transition:all .25s ease;font-family:inherit}
.btn-save{background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;box-shadow:0 4px 15px rgba(20,184,166,.4)}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(20,184,166,.5);color:#fff}
.btn-save:disabled{background:#cbd5e1;box-shadow:none;cursor:not-allowed;transform:none}
.btn-cancel{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-cancel:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}
.lock-banner{background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:2px solid #10b981;border-radius:14px;padding:18px 24px;margin:22px 32px;display:flex;align-items:center;gap:16px}
.lock-banner .lb-icon{width:52px;height:52px;border-radius:12px;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.lock-banner .lb-title{font-size:15px;font-weight:800;color:#065f46;display:flex;align-items:center;gap:8px}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}.main{margin-left:0;padding:70px 18px 30px}
  .section,.actions{padding-left:20px;padding-right:20px}
  .info-panel,.amount-cards,.deduction-panel,.lock-banner,.bills-wrap{margin-left:20px;margin-right:20px}
  .actions{flex-direction:column-reverse}
  .actions .btn{width:100%;justify-content:center}
  .deduction-row{flex-wrap:wrap}
  .deduction-row .fixed-input{width:100%}
  .deduction-row .type-select{width:100%}
  .bills-table{font-size:11px}
  .bills-table thead th,.bills-table tbody td{padding:8px 10px}
}
</style>
</head>
<body>

<button class="burger-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="burger-overlay" id="burgerOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-text">
            <span class="brand-icon-box"><i class="fas fa-heart-pulse"></i></span>
            <div class="brand-text-inner">
                <div class="brand-name">Hospital Billing</div>
                <div class="brand-sub"><?= $is_cashier_only ? 'CASHIER PORTAL' : 'BILLING PORTAL' ?></div>
            </div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="billing_dashboard.php"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <li><a href="billing_records.php" class="active"><i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
        <li><a href="transaction_history.php"><i class="fas fa-clock-rotate-left"></i> <span>Transaction History</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div class="user-chip">
        <div class="avatar"><?= $initials ?></div>
        <div class="info">
            <div class="name"><?= htmlspecialchars($full_name) ?></div>
            <div class="role"><?= htmlspecialchars($user_role) ?></div>
        </div>
    </div>
</div>

<div class="page-head">
    <h1>
        <span class="h-icon"><i class="fas <?= $is_batch ? 'fa-layer-group' : 'fa-money-bill-wave' ?>"></i></span>
        <?php if ($is_locked): ?>
            Payment Complete
        <?php elseif ($is_batch): ?>
            Batch Payment (<?= count($bills) ?> bill<?= count($bills) === 1 ? '' : 's' ?>)
        <?php else: ?>
            Process Payment
        <?php endif; ?>
    </h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" style="flex-wrap:wrap;gap:10px;">
        <i class="fas fa-circle-check"></i>
        <div style="flex:1;min-width:200px;"><?= $success ?></div>
        <?php if (!$is_batch): ?>
        <a href="print_receipt.php?id=<?= $record_id ?>&auto=1" target="_blank"
           style="background:#0f766e;color:#fff;padding:10px 16px;border-radius:9px;text-decoration:none;font-weight:700;font-size:12.5px;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;box-shadow:0 4px 12px rgba(15,118,110,.3);">
            <i class="fas fa-print"></i> Print Receipt
        </a>
        <?php endif; ?>
        <a href="billing_records.php" style="color:inherit;font-weight:700;text-decoration:none;">Back →</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <div><?= $error ?></div></div>
<?php endif; ?>

<?php if ($is_batch && !$is_locked): ?>
    <div class="alert alert-info">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Batch mode:</strong> processing <?= count($bills) ?> selected bill<?= count($bills) === 1 ? '' : 's' ?>
            <?php if ($is_multi_patient): ?>across <strong><?= $patient_count ?> patients</strong><?php endif; ?>.
            Deductions are disabled in batch mode.
        </div>
    </div>
<?php endif; ?>

<form method="POST" autocomplete="off" id="payForm" enctype="multipart/form-data">
<div class="floating-card <?= $is_locked ? 'locked' : '' ?>">

    <?php if ($is_locked): ?>
        <div class="lock-banner">
            <div class="lb-icon"><i class="fas fa-lock"></i></div>
            <div><div class="lb-title"><i class="fas fa-circle-check"></i> All Bills Paid</div></div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">
            <div class="s-icon"><i class="fas fa-user"></i></div>
            <div class="s-text">
                <div class="s-main">
                    <?php if ($is_multi_patient): ?>
                        Multiple Patients (<?= $patient_count ?>)
                    <?php else: ?>
                        <?= htmlspecialchars($record['patient_name']) ?>
                    <?php endif; ?>
                </div>
                <div class="s-sub">
                    <?php if ($is_multi_patient): ?>
                        Batch payment selected bills
                    <?php else: ?>
                        Patient Code: <?= htmlspecialchars($record['patient_id'] ?? '—') ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="info-panel" style="margin:0;">
            <div class="info-grid">
                <?php if ($is_multi_patient): ?>
                    <div class="info-item"><div class="lbl"><i class="fas fa-users"></i> Patients</div><div class="val"><?= $patient_count ?> patient(s)</div></div>
                    <div class="info-item"><div class="lbl"><i class="fas fa-file-invoice"></i> Total Bills</div><div class="val"><?= count($bills) ?> bill(s)</div></div>
                <?php else: ?>
                    <div class="info-item"><div class="lbl"><i class="fas fa-user"></i> Patient</div><div class="val"><?= htmlspecialchars($record['patient_name']) ?></div></div>
                    <div class="info-item"><div class="lbl"><i class="fas fa-hashtag"></i> Code</div><div class="val"><?= htmlspecialchars($record['patient_id'] ?? '—') ?></div></div>
                    <div class="info-item"><div class="lbl"><i class="fas fa-file-invoice"></i> Unpaid Bills</div><div class="val"><?= count($bills) ?> bill(s)</div></div>
                <?php endif; ?>
                <div class="info-item"><div class="lbl"><i class="fas fa-calendar-day"></i> Today</div><div class="val"><?= date('M d, Y') ?></div></div>
            </div>
        </div>
    </div>

    <div class="amount-cards">
        <div class="amount-card total">
            <div class="lbl"><i class="fas fa-file-invoice"></i> Total Amount</div>
            <div class="val" id="cardTotal">₱ <?= number_format($grand_total, 2) ?></div>
            <div class="sub">Original</div>
        </div>
        <div class="amount-card discount" id="cardDiscountWrap" style="<?= !$allow_deductions ? 'display:none;' : '' ?>">
            <div class="lbl"><i class="fas fa-shield-halved"></i> Deductions</div>
            <div class="val" id="cardDiscount">₱ 0.00</div>
            <div class="sub">Total off</div>
        </div>
        <div class="amount-card paid">
            <div class="lbl"><i class="fas fa-circle-check"></i> Paid</div>
            <div class="val">₱ <?= number_format($grand_paid, 2) ?></div>
            <div class="sub">Received</div>
        </div>
        <div class="amount-card balance <?= $grand_balance <= 0 ? 'zero' : '' ?>">
            <div class="lbl"><i class="fas <?= $grand_balance <= 0 ? 'fa-lock' : 'fa-exclamation-circle' ?>"></i> <?= $grand_balance <= 0 ? 'Fully Paid' : 'Balance' ?></div>
            <div class="val" id="cardBalance">₱ <?= number_format($grand_balance, 2) ?></div>
            <div class="sub"><?= $grand_balance <= 0 ? 'Complete!' : 'To collect' ?></div>
        </div>
    </div>

    <?php if ($allow_deductions): ?>
    <div class="deduction-panel">
        <h4><i class="fas fa-calculator"></i> Deductions (from admin settings)</h4>
        <div class="deduction-group">
            <div class="group-title"><i class="fas fa-receipt"></i> Step 1 · VAT Exemption</div>
            <div class="deduction-row" id="row_vat">
                <label class="chk-label">
                    <input type="checkbox" name="vat_exempt" value="1" id="vatExempt" onchange="recalcDeductions()">
                    <div><div class="name">VAT-Exempt (12%)</div></div>
                </label>
            </div>
        </div>

        <?php if (!empty($admin_discounts)): ?>
        <div class="deduction-group">
            <div class="group-title"><i class="fas fa-percent"></i> Step 2 · Discounts</div>
            <?php foreach ($admin_discounts as $d):
                $is_ph     = !empty($d['is_philhealth']);
                $stat      = $d['statutory_type'] ?? null;
                $is_stat   = $stat === 'senior' || $stat === 'pwd';
                $is_manual = !empty($d['manual_type']);
                $dtype     = ($is_ph || ($d['payment_type'] ?? 'percent') === 'fixed') ? 'fixed' : 'percent';
                $initial_type = (($d['payment_type'] ?? 'percent') === 'fixed') ? 'fixed' : 'percent';
            ?>
            <div class="deduction-row <?= $is_ph ? 'philhealth' : '' ?> <?= $is_stat ? 'statutory' : '' ?>"
                 id="row_discount_<?= (int)$d['id'] ?>"
                 data-category="<?= htmlspecialchars($stat ?? 'other') ?>"
                 data-manual="<?= $is_manual ? '1' : '0' ?>">
                <label class="chk-label">
                    <input type="checkbox" class="discount-cb"
                           data-category="<?= htmlspecialchars($stat ?? 'other') ?>"
                           name="selected_discounts[]" value="<?= (int)$d['id'] ?>"
                           id="discount_<?= (int)$d['id'] ?>"
                           onchange="handleDiscountChange(this)">
                    <div>
                        <div class="name">
                            <?= htmlspecialchars($d['discount_name']) ?>
                            <?php if ($is_ph): ?><span class="ph-badge">FIXED</span><?php endif; ?>
                            <?php if ($is_stat): ?><span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:6px;margin-left:6px;font-weight:800;">STATUTORY</span><?php endif; ?>
                            <?php if ($is_manual): ?><span class="manual-badge">MANUAL</span><?php endif; ?>
                        </div>
                        <span class="meta">
                            <?php if ($is_manual): ?>
                                Choose <strong>%</strong> or <strong>₱</strong> and enter value
                            <?php elseif ($dtype === 'fixed'): ?>
                                Fixed peso amount — enter ₱ below
                            <?php else: ?>
                                <strong><?= number_format((float)$d['discount_percentage'], 2) ?>%</strong> off original total
                            <?php endif; ?>
                        </span>
                    </div>
                </label>

                <?php if ($is_manual): ?>
                    <select name="discount_types[<?= (int)$d['id'] ?>]"
                            id="discount_type_<?= (int)$d['id'] ?>"
                            class="type-select"
                            onchange="recalcDeductions()">
                        <option value="percent" <?= $initial_type === 'percent' ? 'selected' : '' ?>>%</option>
                        <option value="fixed"   <?= $initial_type === 'fixed'   ? 'selected' : '' ?>>₱</option>
                    </select>
                    <input type="number" step="0.01" min="0"
                           name="discount_amounts[<?= (int)$d['id'] ?>]"
                           id="discount_amount_<?= (int)$d['id'] ?>"
                           class="fixed-input"
                           placeholder="Enter value"
                           oninput="recalcDeductions()">
                <?php elseif ($dtype === 'fixed'): ?>
                    <input type="number" step="0.01" min="0"
                           name="discount_amounts[<?= (int)$d['id'] ?>]"
                           id="discount_amount_<?= (int)$d['id'] ?>"
                           class="fixed-input"
                           placeholder="₱ 0.00"
                           oninput="recalcDeductions()">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($admin_insurances)): ?>
        <div class="deduction-group">
            <div class="group-title"><i class="fas fa-shield-halved"></i> Step 3 · Insurance Coverage</div>
            <?php foreach ($admin_insurances as $ins):
                $is_ph = !empty($ins['is_philhealth']);
            ?>
            <div class="deduction-row <?= $is_ph ? 'philhealth' : '' ?>" id="row_insurance_<?= (int)$ins['id'] ?>">
                <label class="chk-label">
                    <input type="checkbox" name="selected_insurance[]" value="<?= (int)$ins['id'] ?>" id="insurance_<?= (int)$ins['id'] ?>" onchange="recalcDeductions()">
                    <div>
                        <div class="name">
                            <?= htmlspecialchars($ins['provider_name']) ?>
                            <?php if ($is_ph): ?><span class="ph-badge">CASE RATE</span><?php endif; ?>
                        </div>
                        <span class="meta"><?php if ($is_ph): ?><strong>Fixed case rate</strong> — enter ₱ from PhilHealth table<?php else: ?>Enter <strong>₱ amount</strong> below<?php endif; ?></span>
                    </div>
                </label>
                <input type="number" step="0.01" min="0" name="insurance_amounts[<?= (int)$ins['id'] ?>]" id="insurance_amount_<?= (int)$ins['id'] ?>" class="fixed-input" placeholder="₱ 0.00" oninput="recalcDeductions()">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="ins-info" id="insInfo">
            <i class="fas fa-circle-info"></i>
            <span id="insInfoText">No deductions applied.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="bills-wrap">
        <div class="bills-head">
            <h4><i class="fas fa-list"></i> <?= $is_batch ? 'Selected Bills' : 'Unpaid Bills' ?> <span class="count"><?= count($bills) ?></span></h4>
        </div>

        <?php if (empty($bills)): ?>
            <div style="padding:40px 20px;text-align:center;color:#94a3b8;">
                <i class="fas fa-circle-check" style="font-size:36px;color:#10b981;display:block;margin-bottom:12px;"></i>
                <strong style="color:#475569;">All bills are paid.</strong>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <?php if ($is_multi_patient): ?><th>Patient</th><?php endif; ?>
                            <th>Service</th>
                            <th style="text-align:right;">Total</th>
                            <th style="text-align:right;">Paid</th>
                            <th style="text-align:right;">Balance</th>
                            <th style="text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bills as $b):
                        $b_total = (float)$b['total_amount'];
                        $b_paid  = (float)($b['amount_paid'] ?? 0);
                        $b_bal   = max(0, $b_total - $b_paid);
                        $b_status = ($b_paid > 0 && $b_bal > 0) ? 'Partial' : $b['status'];
                    ?>
                        <tr>
                            <td><span class="inv">#<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                            <td><?= date('M d, Y', strtotime($b['date'])) ?></td>
                            <?php if ($is_multi_patient): ?>
                                <td class="patient-cell"><?= htmlspecialchars($b['patient_name']) ?><?php if (!empty($b['patient_id'])): ?><small><?= htmlspecialchars($b['patient_id']) ?></small><?php endif; ?></td>
                            <?php endif; ?>
                            <td><div class="svc"><?= htmlspecialchars($b['service_description']) ?><small><?= htmlspecialchars($b['category']) ?></small></div></td>
                            <td class="num">₱ <?= number_format($b_total, 2) ?></td>
                            <td class="num paid">₱ <?= number_format($b_paid, 2) ?></td>
                            <td class="num bal <?= $b_bal <= 0 ? 'zero' : '' ?>">₱ <?= number_format($b_bal, 2) ?></td>
                            <td style="text-align:center;"><span class="status-pill <?= $b_status ?>"><i class="fas fa-circle"></i> <?= $b_status ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($is_locked): ?>
        <div class="actions">
            <a class="btn btn-cancel" href="billing_records.php"><i class="fas fa-arrow-left"></i> Back to Records</a>
            <button class="btn btn-save" type="button" disabled><i class="fas fa-lock"></i> Locked</button>
        </div>
    <?php else: ?>
        <div class="section">
            <div class="section-title">
                <div class="s-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                <div class="s-text"><div class="s-main">Receive Payment</div></div>
            </div>
            <div class="fg">
                <label><i class="fas fa-peso-sign"></i> Total Amount Received <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" max="<?= $grand_balance ?>" id="payAmount" name="pay_amount" required value="<?= number_format($grand_balance, 2, '.', '') ?>" oninput="updateCalc()">
            </div>
            <div class="row">
                <div class="fg">
                    <label><i class="fas fa-wallet"></i> Payment Method</label>
                    <select name="payment_method" id="paymentMethod" onchange="togglePaymentMethodFields()">
                        <option value="Cash" selected>Cash</option>
                        <option value="Guarantee Letter">Guarantee Letter</option>
                    </select>
                </div>
                <div class="fg">
                    <label><i class="fas fa-calculator"></i> Remaining After Payment</label>
                    <input type="text" id="newBalancePreview" readonly value="₱ <?= number_format($grand_balance, 2) ?>">
                </div>
            </div>
            <div id="glFields" class="gl-container" style="display:none;">
                <h5><i class="fas fa-file-signature"></i> Guarantee Letter Details</h5>
                <div class="fg">
                    <label>Issuing Agency <span class="req">*</span></label>
                    <select name="gl_agency">
                        <option value="">Select agency (DSWD, PCSO, LGU, etc.)</option>
                        <option value="DSWD">DSWD</option>
                        <option value="PCSO">PCSO (Philippine Charity Sweepstakes Office)</option>
                        <option value="LGU">LGU (City or Municipal Social Welfare Office)</option>
                        <option value="Other">Other authorized government or private institutions</option>
                    </select>
                </div>
                <div class="fg">
                    <label>GL Reference Number</label>
                    <input type="text" name="gl_ref_no" placeholder="Enter reference number">
                </div>
                <div class="fg">
                    <label>Approved Amount <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0" name="gl_amount" placeholder="₱ 10,000.00">
                </div>
                <div class="fg" style="margin-bottom:0;">
                    <label>Upload Guarantee Letter <span class="req">*</span></label>
                    <div class="upload-box" onclick="document.getElementById('gl_document').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="u-title">Upload PDF or Image</div>
                        <div class="u-sub">Attach scanned copy or photo of GL</div>
                        <input type="file" name="gl_document" id="gl_document" accept=".pdf,image/*" style="display: none;" onchange="updateFileName(this)">
                        <div id="fileNameDisplay" style="margin-top: 10px; font-size: 12px; color: #0f766e; font-weight: 700;"></div>
                    </div>
                </div>
            </div>
            <div class="fg" style="margin-top:20px;">
                <label><i class="fas fa-message"></i> Remarks <span class="opt">(optional)</span></label>
                <textarea name="remarks" rows="2" placeholder="e.g. Reference #12345"></textarea>
            </div>
        </div>

        <input type="hidden" name="record_id" value="<?= (int)$record['id'] ?>">
        <?php if ($is_batch): ?>
            <?php foreach ($batch_ids as $bid): ?>
                <input type="hidden" name="batch_ids[]" value="<?= (int)$bid ?>">
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="actions">
            <a class="btn btn-cancel" href="billing_records.php"><i class="fas fa-arrow-left"></i> Back</a>
            <button class="btn btn-save" type="submit" name="save_payment">
                <i class="fas fa-circle-check"></i> Receive Payment
            </button>
        </div>
    <?php endif; ?>
</div>
</form>
</main>

<script>
const ORIGINAL_TOTAL = <?= json_encode($grand_total) ?>;
const GRAND_BALANCE  = <?= json_encode($grand_balance) ?>;
const CAN_APPLY_INS  = <?= $allow_deductions ? 'true' : 'false' ?>;

const DISCOUNTS      = <?= json_encode($admin_discounts, JSON_NUMERIC_CHECK) ?>;
const INSURANCES_ALL = <?= json_encode($admin_insurances, JSON_NUMERIC_CHECK) ?>;
const INSURANCES     = INSURANCES_ALL.filter(i => !i.statutory_type);

const VAT_AMOUNT     = Math.round((ORIGINAL_TOTAL - (ORIGINAL_TOTAL / 1.12)) * 100) / 100;

function isFixedType(item) {
    if (item.is_philhealth === true) return true;
    const name = (item.discount_name || item.provider_name || '').toLowerCase();
    if (name.indexOf('philhealth') !== -1) return true;
    return item.payment_type === 'fixed';
}

/* === Compute the peso amount for a checked discount, honouring manual types === */
function computeDiscountAmount(d) {
    const cb = document.getElementById('discount_' + d.id);
    if (!cb || !cb.checked) return 0;

    // Employee / Indigent (manual type): user picks % or ₱ and types a value
    if (d.manual_type) {
        const typeSel  = document.getElementById('discount_type_' + d.id);
        const valInput = document.getElementById('discount_amount_' + d.id);
        const val  = parseFloat(valInput?.value || 0) || 0;
        const type = typeSel?.value || 'percent';
        return (type === 'percent')
            ? Math.round(ORIGINAL_TOTAL * (val / 100) * 100) / 100
            : val;
    }

    // PhilHealth / other fixed
    if (isFixedType(d)) {
        return parseFloat(document.getElementById('discount_amount_' + d.id)?.value || 0) || 0;
    }

    // Percentage-based
    return Math.round(ORIGINAL_TOTAL * (parseFloat(d.discount_percentage) / 100) * 100) / 100;
}

function handleDiscountChange(el) {
    const cat = el.dataset.category;
    if (el.checked && (cat === 'senior' || cat === 'pwd')) {
        document.querySelectorAll('.discount-cb[data-category="senior"], .discount-cb[data-category="pwd"]').forEach(other => {
            if (other !== el) other.checked = false;
        });
    }
    recalcDeductions();
}

function updateExclusiveLockState() {
    const seniorChecked  = document.querySelector('.discount-cb[data-category="senior"]:checked');
    const pwdChecked     = document.querySelector('.discount-cb[data-category="pwd"]:checked');
    const anyStatChecked = !!(seniorChecked || pwdChecked);

    document.querySelectorAll('.discount-cb[data-category="senior"], .discount-cb[data-category="pwd"]').forEach(cb => {
        const row = cb.closest('.deduction-row');
        if (!row) return;
        const isOther = anyStatChecked && !cb.checked;
        row.classList.toggle('exclusive-locked', isOther);
    });
}

function recalcDeductions() {
    if (!CAN_APPLY_INS) return;

    let totalDeduction = 0;
    const parts = [];

    if (document.getElementById('vatExempt')?.checked) {
        totalDeduction += VAT_AMOUNT;
        parts.push(`VAT -₱${VAT_AMOUNT.toFixed(2)}`);
    }

    DISCOUNTS.forEach(d => {
        const cb = document.getElementById('discount_' + d.id);
        if (!cb || !cb.checked) return;
        const amt = computeDiscountAmount(d);
        totalDeduction += amt;
        if (amt > 0) parts.push(`${d.discount_name} -₱${amt.toFixed(2)}`);
    });

    INSURANCES.forEach(i => {
        const cb = document.getElementById('insurance_' + i.id);
        if (!cb || !cb.checked) return;
        const amt = parseFloat(document.getElementById('insurance_amount_' + i.id)?.value || 0) || 0;
        totalDeduction += amt;
        if (amt > 0) parts.push(`${i.provider_name} -₱${amt.toFixed(2)}`);
    });

    if (totalDeduction > ORIGINAL_TOTAL) totalDeduction = ORIGINAL_TOTAL;
    const coPay = Math.max(0, ORIGINAL_TOTAL - totalDeduction);

    document.querySelectorAll('.deduction-row').forEach(row => {
        const cb = row.querySelector('input[type="checkbox"]');
        if (cb) row.classList.toggle('active', cb.checked);
    });

    updateExclusiveLockState();

    const cdw = document.getElementById('cardDiscountWrap');
    if (cdw) cdw.style.display = (totalDeduction > 0) ? 'block' : 'none';
    const cd = document.getElementById('cardDiscount');
    if (cd) cd.textContent = '₱ ' + totalDeduction.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    const cb2 = document.getElementById('cardBalance');
    if (cb2) cb2.textContent = '₱ ' + coPay.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

    const infoTxt = document.getElementById('insInfoText');
    if (infoTxt) {
        if (parts.length > 0) {
            infoTxt.innerHTML = `<strong>${parts.join(' · ')}</strong><br>Patient co-payment: <strong>₱${coPay.toFixed(2)}</strong>`;
        } else {
            infoTxt.textContent = 'No deductions applied.';
        }
    }

    const payInput = document.getElementById('payAmount');
    if (payInput) {
        payInput.value = coPay.toFixed(2);
        payInput.max   = coPay.toFixed(2);
    }

    updateCalc();
}

function getCurrentBalance() {
    if (!CAN_APPLY_INS) return GRAND_BALANCE;

    let totalDeduction = 0;
    if (document.getElementById('vatExempt')?.checked) totalDeduction += VAT_AMOUNT;

    DISCOUNTS.forEach(d => {
        totalDeduction += computeDiscountAmount(d);
    });

    INSURANCES.forEach(i => {
        const cb = document.getElementById('insurance_' + i.id);
        if (!cb || !cb.checked) return;
        totalDeduction += (parseFloat(document.getElementById('insurance_amount_' + i.id)?.value || 0) || 0);
    });

    if (totalDeduction > ORIGINAL_TOTAL) totalDeduction = ORIGINAL_TOTAL;
    return Math.max(0, ORIGINAL_TOTAL - totalDeduction);
}

function updateCalc() {
    const input = document.getElementById('payAmount');
    const preview = document.getElementById('newBalancePreview');
    if (!input || !preview) return;

    const currentBalance = getCurrentBalance();
    let val = parseFloat(input.value) || 0;
    if (val < 0) val = 0;
    if (val > currentBalance) val = currentBalance;

    const newBal = Math.max(0, currentBalance - val);
    preview.value = '₱ ' + newBal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

    if (newBal <= 0) {
        preview.style.background = '#d1fae5'; preview.style.color = '#065f46';
    } else {
        preview.style.background = '#fffbeb'; preview.style.color = '#92400e';
    }
}

function togglePaymentMethodFields() {
    const method = document.getElementById('paymentMethod').value;
    const glFields = document.getElementById('glFields');
    if (!glFields) return;
    glFields.style.display = (method === 'Guarantee Letter') ? 'block' : 'none';
}

function updateFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.textContent = '📎 ' + input.files[0].name;
    } else {
        display.textContent = '';
    }
}

function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', function(){
    recalcDeductions();
    updateCalc();
});
</script>
</body>
</html>