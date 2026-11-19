<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'classify_lib.php';
require_once 'service_catalog.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$r = strtolower(trim($user_role));

$is_frontdesk = strpos($r,'reception') !== false || strpos($r,'frontdesk') !== false || strpos($r,'front desk') !== false || strpos($r,'front-desk') !== false || strpos($r,'front office') !== false || in_array($r, ['receptionist','front desk receptionist','front desk reception','reception'], true);
$is_billing   = in_array($r, ['billing','cashier'], true) || strpos($r,'billing') !== false || strpos($r,'cashier') !== false;
if (!$is_frontdesk && !$is_billing) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_brand = $is_billing ? 'Billing Staff' : 'Front Desk';
$sidebar_sub   = $is_billing ? 'Billing Portal' : 'Receptionist';
$accent        = '#0f766e';
$accent_light  = '#14b8a6';
$dash_url      = $is_billing ? 'billing_dashboard.php' : 'frontdesk_dashboard.php';

/* ================= CATALOG DATA ================= */
$categories   = get_service_categories($pdo);
$catalog_json = catalog_json($pdo);

/* ============================================================
   ✅ PREVIEW-TIME ENRICHMENT (case-insensitive, trimmed)
   ============================================================ */
$catalog_arr = null;
try {
    $catalog_arr = json_decode($catalog_json, true);
    if (is_array($catalog_arr) && !empty($catalog_arr['services'])) {

        $svc_map = [];
        try {
            $rows = $pdo->query(
                "SELECT service_name, patient_explanation
                   FROM services
                  WHERE patient_explanation IS NOT NULL
                    AND patient_explanation <> ''"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $fr) {
                $k = strtolower(trim((string)$fr['service_name']));
                $v = trim((string)$fr['patient_explanation']);
                if ($k !== '' && $v !== '') $svc_map[$k] = $v;
            }
        } catch (PDOException $e) { }

        $dict_map = [];
        try {
            $rows = $pdo->query(
                "SELECT technical_term, patient_explanation
                   FROM semantic_dictionary
                  WHERE patient_explanation IS NOT NULL
                    AND patient_explanation <> ''"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $fr) {
                $k = strtolower(trim((string)$fr['technical_term']));
                $v = trim((string)$fr['patient_explanation']);
                if ($k !== '' && $v !== '') $dict_map[$k] = $v;
            }
        } catch (PDOException $e) { }

        if (!empty($svc_map) || !empty($dict_map)) {
            $changed = false;
            foreach ($catalog_arr['services'] as &$svc) {
                $exp = trim((string)($svc['patient_explanation'] ?? ''));
                if ($exp === '') {
                    $k = strtolower(trim((string)($svc['service_name'] ?? '')));
                    if ($k !== '' && isset($svc_map[$k])) {
                        $svc['patient_explanation'] = $svc_map[$k];
                        $changed = true;
                    } elseif ($k !== '' && isset($dict_map[$k])) {
                        $svc['patient_explanation'] = $dict_map[$k];
                        $changed = true;
                    }
                }
            }
            unset($svc);
            if ($changed) {
                $catalog_json = json_encode(
                    $catalog_arr,
                    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
            }
        }
    }
} catch (Throwable $e) { }

/* ================= HANDLE SUBMIT ================= */
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name    = trim($_POST['first_name'] ?? '');
    $middle_name   = trim($_POST['middle_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $dob           = trim($_POST['date_of_birth'] ?? '');
    $gender        = trim($_POST['gender'] ?? '');
    $contact       = trim($_POST['contact_number'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    $category_id   = (int)($_POST['category_id'] ?? 0);
    $service_id    = (int)($_POST['service_id'] ?? 0);
    $amount        = (float)($_POST['amount'] ?? 0);
    $qty           = 1;
    $status        = 'Pending';
    $bill_date     = $_POST['bill_date'] ?? date('Y-m-d');

    $contact = preg_replace('/\D/', '', $contact);
    if ($contact !== '' && strlen($contact) > 11) {
        $contact = substr($contact, 0, 11);
    }

    $full_name_parts = array_filter([$first_name, $middle_name, $last_name], fn($v) => $v !== '');
    $full_name_in    = implode(' ', $full_name_parts);

    $service_row  = $service_id > 0 ? get_service_by_id($pdo, $service_id) : null;
    $category_row = null;
    if ($category_id > 0) {
        $stmt = $pdo->prepare("SELECT id, category_name AS name FROM billing_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if ($first_name === '') {
        $error = 'First name is required.';
    } elseif ($last_name === '') {
        $error = 'Last name is required.';
    } elseif ($contact !== '' && strlen($contact) !== 11) {
        $error = 'Contact number must be exactly 11 digits.';
    } elseif ($service_id > 0 && !$service_row) {
        $error = 'The selected service could not be found.';
    } elseif ($service_id > 0 && !$category_row) {
        $error = 'Please select a valid service category.';
    } elseif ($service_id > 0 && (int)$service_row['category_id'] !== (int)$category_row['id']) {
        $error = 'The selected service does not belong to the selected category.';
    } elseif ($service_id > 0 && $amount <= 0) {
        $error = 'Please enter the actual charge amount for the selected service.';
    } elseif ($service_id === 0 && $category_id > 0 && $amount > 0) {
        $error = 'Please select a service for the chosen category.';
    } else {
        try {
            $pdo->beginTransaction();

            $last = (int)$pdo->query("SELECT COALESCE(MAX(id),0) FROM patients")->fetchColumn();
            $patient_code = 'P' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);

            $pdo->prepare("
                INSERT INTO patients
                    (patient_code, full_name, date_of_birth, gender,
                     contact_number, address, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $patient_code, $full_name_in,
                $dob !== '' ? $dob : null,
                $gender !== '' ? $gender : null,
                $contact !== '' ? $contact : null,
                $address !== '' ? $address : null,
                $notes !== '' ? $notes : null
            ]);

            $patient_id = (int)$pdo->lastInsertId();

            if ($service_row && $category_row && $amount > 0) {
                $total_amount = $amount;
                $service_name = $service_row['service_name'];

                $patient_explanation = trim((string)($_POST['patient_explanation'] ?? ''));

                if ($patient_explanation === '') {
                    $patient_explanation = trim((string)($service_row['patient_explanation'] ?? ''));
                }

                if ($patient_explanation === '' && is_array($catalog_arr) && !empty($catalog_arr['services'])) {
                    foreach ($catalog_arr['services'] as $svc) {
                        if ((int)($svc['id'] ?? 0) === $service_id) {
                            $patient_explanation = trim((string)($svc['patient_explanation'] ?? ''));
                            break;
                        }
                    }
                }

                if ($patient_explanation === '') {
                    try {
                        $stmt = $pdo->prepare(
                            "SELECT patient_explanation
                               FROM semantic_dictionary
                              WHERE LOWER(TRIM(technical_term)) = ?
                              LIMIT 1"
                        );
                        $stmt->execute([strtolower(trim($service_name))]);
                        $patient_explanation = trim((string)($stmt->fetchColumn() ?: ''));
                    } catch (PDOException $e) { }
                }

                $cls = classify_service($pdo, $service_name);

                $pdo->prepare("
                    INSERT INTO billing_records
                        (patient_db_id, patient_id, patient_name, service_id, service_description,
                         category, predicted_category, matched_rule, matched_rule_id,
                         matched_keyword, patient_explanation, processing_time_ms,
                         quantity, amount, total_amount, status, date, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ")->execute([
                    $patient_id,
                    $patient_code,
                    $full_name_in,
                    $service_row['id'],
                    $service_name,
                    $category_row['name'],
                    $cls['predicted_category'],
                    $cls['matched_rule'],
                    $cls['matched_rule_id'],
                    $cls['matched_keyword'],
                    $patient_explanation,
                    $cls['processing_ms'],
                    $qty,
                    $amount,
                    $total_amount,
                    $status,
                    $bill_date,
                    $notes !== '' ? 'Initial charges: '.$notes : null
                ]);

                try {
                    $msg = "New patient + bill: {$full_name_in} — ₱" . number_format($total_amount, 2);
                    $pdo->prepare("INSERT INTO notifications (icon, color, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())")
                        ->execute(['fa-user-plus', $accent_light, $msg]);
                } catch (PDOException $e) {}

            } else {
                try {
                    $pdo->prepare("INSERT INTO notifications (icon, color, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())")
                        ->execute(['fa-user-plus', $accent_light, "New patient registered: {$full_name_in}"]);
                } catch (PDOException $e) {}
            }

            $pdo->commit();

            $_SESSION['flash_patient'] = "Patient {$full_name_in} registered successfully (Code: {$patient_code}).";
            header('Location: patients.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Failed to register patient: ' . $e->getMessage();
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
<title>Register & Bill · Patient</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex;min-height:100vh;color:#1e293b;
  position:relative;overflow-x:hidden;
  background:
    radial-gradient(circle at 15% 20%, rgba(20,184,166,.12) 0%, transparent 45%),
    radial-gradient(circle at 85% 80%, rgba(15,118,110,.14) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20,184,166,.05) 0%, transparent 60%),
    linear-gradient(135deg, #f0fdfa 0%, #e6fbf7 40%, #ecfdf5 100%);
  background-attachment:fixed;
}
body::before{
  content:'';position:fixed;top:-15%;right:-12%;
  width:520px;height:520px;
  background:radial-gradient(circle, rgba(20,184,166,.18) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);z-index:0;
}
body::after{
  content:'';position:fixed;bottom:-20%;left:-12%;
  width:620px;height:620px;
  background:radial-gradient(circle, rgba(15,118,110,.15) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);z-index:0;
}

.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}

.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:<?= $accent ?>;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}

.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f0fdfa;color:<?= $accent ?>}
.sidebar-menu li a:hover i{color:<?= $accent ?>}
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.15)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.sidebar-footer a:hover i{color:#dc2626}
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

.main{margin-left:270px;flex:1;padding:0 0 40px 0;min-height:100vh;position:relative;z-index:1}
.top-bar{background:#fff;padding:18px 32px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:25px}
.top-bar h1{font-size:22px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:12px}
.top-bar h1 .h-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 6px 16px rgba(0,0,0,.15)}
.top-bar .sub{font-size:12.5px;color:#64748b;margin-top:4px;margin-left:52px}
.top-bar-actions{display:flex;align-items:center;gap:15px}

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:600;text-transform:uppercase;margin-top:2px}

.content{padding:0 32px}

.alert{padding:14px 18px;border-radius:11px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:500}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.alert i{font-size:15px}

.card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:22px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.card-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.card-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.card-header h3 i{color:<?= $accent ?>;font-size:15px}
.card-body{padding:22px 24px}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px 20px}
.form-grid .full{grid-column:1 / -1}
.form-grid .span-3{grid-column:1 / -1;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px 20px}
.form-group{margin-bottom:0}
.form-group label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px}
.form-group label .req{color:#dc2626}
.form-group label .opt{color:#94a3b8;font-weight:400;font-size:11px;text-transform:none;letter-spacing:0;margin-left:3px}
.form-group input,
.form-group select,
.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13.5px;font-family:inherit;background:#fff;color:#1e293b;transition:border-color .2s,box-shadow .2s}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}
.form-group textarea{resize:vertical;min-height:80px}
.form-group .hint{font-size:11.5px;color:#94a3b8;margin-top:6px}
.form-group input[readonly],
.form-group textarea[readonly]{background:#f8fafc;color:#475569}

.form-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9}

.btn{padding:11px 22px;border-radius:10px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.15)}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-light{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-light:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}

.total-preview{background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.total-preview .label{font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px}
.total-preview .value{font-size:18px;font-weight:800;color:<?= $accent ?>}

@media(max-width:900px){
  .form-grid{grid-template-columns:1fr}
  .form-grid .span-3{grid-template-columns:1fr}
}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}.main{margin-left:0}
  .top-bar,.content{padding-left:18px;padding-right:18px}
  .user-chip .info{display:none}.user-chip{padding:5px}
  .btn{width:100%;justify-content:center}
  .form-actions .btn{width:100%}
}
@media(max-width:520px){body::before,body::after{opacity:.6}}
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
                <div class="brand-name"><?= $sidebar_brand === 'Front Desk' ? 'Hospital Billing' : $sidebar_brand ?></div>
                <div class="brand-sub"><?= $sidebar_sub ?></div>
            </div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="<?= $dash_url ?>"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <?php if ($is_frontdesk): ?>
            <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
            <li><a href="add_patient.php" class="active"><i class="fas fa-user-plus"></i> <span>Register & Bill</span></a></li>
        <?php endif; ?>
        <?php if ($is_billing): ?>
            <li><a href="input_billing.php"><i class="fas fa-file-circle-plus"></i> <span>Input Billing</span></a></li>
            <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <?php endif; ?>
        <li><a href="billing_records.php"><i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
        <li><a href="transaction_history.php"><i class="fas fa-clock-rotate-left"></i> <span>Transaction History</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div>
        <h1><span class="h-icon"><i class="fas fa-user-plus"></i></span> Register & Bill</h1>
        <div class="sub">Add a new patient and (optionally) their first bill — all in one step.</div>
    </div>
    <div class="top-bar-actions">
        <div class="user-chip">
            <div class="avatar"><?= $initials ?></div>
            <div class="info">
                <div class="name"><?= htmlspecialchars($full_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="content">

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST" id="addPatientForm">

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user"></i> Patient Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="span-3">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="req">*</span></label>
                        <input type="text" id="first_name" name="first_name" required placeholder="e.g. Juan"
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name <span class="opt">(optional)</span></label>
                        <input type="text" id="middle_name" name="middle_name" placeholder="e.g. Santos"
                               value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="req">*</span></label>
                        <input type="text" id="last_name" name="last_name" required placeholder="e.g. Dela Cruz"
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">— Select —</option>
                        <?php
                        $genders = ['Male','Female','Other'];
                        $sel     = $_POST['gender'] ?? '';
                        foreach ($genders as $g) {
                            echo '<option value="'.htmlspecialchars($g).'"'.($sel === $g ? ' selected' : '').'>'.htmlspecialchars($g).'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text"
                           id="contact_number"
                           name="contact_number"
                           placeholder="e.g. 09171234567"
                           maxlength="11"
                           inputmode="numeric"
                           pattern="[0-9]{11}"
                           oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,11);"
                           value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                </div>

                <div class="form-group full">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="House #, Street, Barangay, City"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>

                <div class="form-group full">
                    <label for="notes">Notes (optional)</label>
                    <textarea id="notes" name="notes" placeholder="Allergies, existing conditions, remarks…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-invoice-dollar"></i> Initial Billing (optional)</h3>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"
                                    data-has-default="<?= (int)$c['has_default_price'] ?>"
                                <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service_id">Service Description</label>
                    <select id="service_id" name="service_id"
                            data-preselect="<?= htmlspecialchars($_POST['service_id'] ?? '') ?>">
                        <option value="">— Select category first —</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="patient_explanation">Patient-Friendly Explanation</label>
                    <textarea id="patient_explanation" name="patient_explanation" readonly
                              placeholder="Select a service to automatically load the patient-friendly explanation."><?= htmlspecialchars($_POST['patient_explanation'] ?? '') ?></textarea>
                    <div class="hint">Loaded from the service catalog (with dictionary fallback) and saved with the billing record. It is shown automatically when a service is selected.</div>
                </div>

                <div class="form-group">
                    <label for="bill_date">Billing Date</label>
                    <input type="date" id="bill_date" name="bill_date"
                           value="<?= htmlspecialchars($_POST['bill_date'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="form-group">
                    <label for="amount">Unit Charge (₱)</label>
                    <input type="number" step="0.01" min="0" id="amount" name="amount"
                           placeholder="0.00" oninput="updateTotal()"
                           value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
                    <div class="hint">Laboratory tests auto-fill the default price. Other categories are entered manually.</div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <input type="text" value="Pending (default)" readonly style="background:#f0fdfa;color:#0f766e;font-weight:700;">
                </div>

                <div class="form-group">
                    <label>Total</label>
                    <div class="total-preview">
                        <span class="label">Total</span>
                        <span class="value" id="total_display">₱ 0.00</span>
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <a href="patients.php" class="btn btn-light"><i class="fas fa-xmark"></i> Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-floppy-disk"></i> Save Patient & Bill
                </button>
            </div>
        </div>
    </div>

</form>

</div>
</main>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}

function updateTotal(){
    const amt = parseFloat(document.getElementById('amount').value) || 0;
    document.getElementById('total_display').textContent =
        '₱ ' + amt.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

const SERVICE_CATALOG = <?= $catalog_json ?>;

(function () {
    const categorySel    = document.getElementById('category_id');
    const serviceSel     = document.getElementById('service_id');
    const explanationTa  = document.getElementById('patient_explanation');
    const amountInput    = document.getElementById('amount');

    const PLACEHOLDER_NONE  = 'Select a service to automatically load the patient-friendly explanation.';
    const PLACEHOLDER_EMPTY = 'No patient-friendly explanation on file for this service — you can still save the bill.';

    function servicesForCategory(catId) {
        return (SERVICE_CATALOG.services || []).filter(function (s) {
            return String(s.category_id) === String(catId);
        });
    }

    function fillServiceOptions(catId, preselectId) {
        serviceSel.innerHTML = '';
        if (!catId) {
            serviceSel.innerHTML = '<option value="">— Select category first —</option>';
            return;
        }
        const list = servicesForCategory(catId);
        if (!list.length) {
            serviceSel.innerHTML = '<option value="">— No services registered in this category —</option>';
            return;
        }
        serviceSel.innerHTML = '<option value="">— Select service —</option>';
        list.forEach(function (s) {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.service_name;
            opt.dataset.explanation = s.patient_explanation || '';
            opt.dataset.defaultPrice = (s.default_price !== null && s.default_price !== undefined) ? s.default_price : '';
            opt.dataset.hasDefault = String(s.has_default_price) === '1' ? '1' : '0';
            if (preselectId && String(preselectId) === String(s.id)) {
                opt.selected = true;
            }
            serviceSel.appendChild(opt);
        });
    }

    function applyExplanation(opt) {
        const exp = ((opt && opt.dataset.explanation) || '').trim();
        explanationTa.value = exp;
        explanationTa.placeholder = exp ? '' : PLACEHOLDER_EMPTY;
    }

    function resetServiceAndExplanation() {
        serviceSel.innerHTML = '<option value="">— Select service —</option>';
        explanationTa.value = '';
        explanationTa.placeholder = PLACEHOLDER_NONE;
        amountInput.value = '';
        updateTotal();
    }

    categorySel.addEventListener('change', function () {
        resetServiceAndExplanation();
        const catId = this.value;
        if (!catId) return;
        fillServiceOptions(catId);
        updateTotal();
    });

    serviceSel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !this.value) {
            explanationTa.value = '';
            explanationTa.placeholder = PLACEHOLDER_NONE;
            amountInput.value = '';
            updateTotal();
            return;
        }
        applyExplanation(opt);
        if (opt.dataset.hasDefault === '1' && opt.dataset.defaultPrice !== '') {
            amountInput.value = parseFloat(opt.dataset.defaultPrice).toFixed(2);
        } else {
            amountInput.value = '';
        }
        updateTotal();
    });

    (function initCatalog() {
        const catId = categorySel.value;
        if (!catId) return;
        const preselect = serviceSel.dataset.preselect || '';
        fillServiceOptions(catId, preselect);
        if (preselect) {
            const opt = serviceSel.querySelector('option[value="' + preselect + '"]');
            if (opt) {
                applyExplanation(opt);
                if (opt.dataset.hasDefault === '1' && opt.dataset.defaultPrice !== '') {
                    amountInput.value = parseFloat(opt.dataset.defaultPrice).toFixed(2);
                }
            }
        }
        updateTotal();
    })();
})();
updateTotal();
</script>
</body>
</html>