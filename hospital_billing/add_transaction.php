<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'classify_lib.php';
require_once 'service_catalog.php'; // Added for the dropdowns & explanations

$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$r = strtolower(trim($user_role));

$is_frontdesk = strpos($r,'reception') !== false || strpos($r,'frontdesk') !== false || strpos($r,'front desk') !== false || strpos($r,'front-desk') !== false || strpos($r,'front office') !== false || in_array($r, ['receptionist','front desk receptionist','front desk reception','reception'], true);
$is_billing   = in_array($r, ['billing','cashier'], true) || strpos($r,'billing') !== false || strpos($r,'cashier') !== false;
if (!$is_frontdesk && !$is_billing) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_brand = $is_billing ? 'Billing Staff' : 'Front Desk';
$sidebar_sub   = $is_billing ? 'BILLING PORTAL' : 'RECEPTIONIST';
$accent        = '#0f766e';
$accent_light  = '#14b8a6';
$dash_url      = $is_billing ? 'billing_dashboard.php' : 'frontdesk_dashboard.php';

/* ================= CATALOG DATA ================= */
$categories   = get_service_categories($pdo);
$catalog_json = catalog_json($pdo);

/* ===== GET PATIENT ===== */
$patient_id = (int)($_GET['patient_id'] ?? $_POST['patient_db_id'] ?? 0);
$patient = null;
if ($patient_id > 0) {
    try {
        $s = $pdo->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
        $s->execute([$patient_id]);
        $patient = $s->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
if (!$patient) { header('Location: patients.php'); exit; }

$success = ''; $error = '';

/* ===== SAVE TRANSACTIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_transactions'])) {
    $service_ids = $_POST['service_id'] ?? [];
    $amounts     = $_POST['amount'] ?? [];
    $status      = 'Pending';
    $bill_date   = $_POST['bill_date'] ?? date('Y-m-d');
    $notes       = trim($_POST['notes'] ?? '');

    $items = [];
    for ($i = 0; $i < count($service_ids); $i++) {
        $svc_id = (int)($service_ids[$i] ?? 0);
        $amt    = (float)($amounts[$i] ?? 0);
        $qty    = 1; // Quantity removed from UI, hardcoded to 1
        
        if ($svc_id <= 0 || $amt <= 0) continue;
        
        // Fetch the service details to ensure accurate data
        $srow = get_service_by_id($pdo, $svc_id);
        if ($srow) {
            $cstmt = $pdo->prepare("SELECT name FROM service_categories WHERE id = ?");
            $cstmt->execute([$srow['category_id']]);
            $cat_name = $cstmt->fetchColumn() ?: 'Other';

            $items[] = [
                'service_id' => $svc_id,
                'service'    => $srow['service_name'], 
                'category'   => $cat_name, 
                'amount'     => $amt, 
                'qty'        => $qty, 
                'total'      => $amt * $qty,
                'patient_explanation' => $srow['patient_explanation'] ?? ''
            ];
        }
    }

    if (empty($items)) {
        $error = 'Please add at least one valid service.';
    } else {
        try {
            $pdo->beginTransaction();

            $insert = $pdo->prepare("
                INSERT INTO billing_records
                    (patient_db_id, patient_id, patient_name, service_id, service_description, category,
                     predicted_category, matched_rule, matched_rule_id, matched_keyword,
                     patient_explanation, processing_time_ms,
                     quantity, amount, total_amount, status, date, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $grand = 0;
            $first = true;
            foreach ($items as $it) {
                $rowNote = '';
                if ($first && $notes !== '') { $rowNote = $notes; $first = false; }

                $cls = classify_service($pdo, $it['service']);
                
                // Use the catalog explanation if available, otherwise fallback to AI prediction
                $explanation = !empty($it['patient_explanation']) ? $it['patient_explanation'] : $cls['patient_explanation'];

                $insert->execute([
                    $patient['id'],
                    $patient['patient_code'] ?? '',
                    $patient['full_name'],
                    $it['service_id'],
                    $it['service'], $it['category'],
                    $cls['predicted_category'], $cls['matched_rule'], $cls['matched_rule_id'],
                    $cls['matched_keyword'], $explanation, $cls['processing_ms'],
                    $it['qty'], $it['amount'], $it['total'],
                    $status, $bill_date, $rowNote
                ]);
                $grand += $it['total'];
            }

            try {
                $msg = 'Added ' . count($items) . ' transaction' . (count($items)>1?'s':'')
                     . ' for ' . $patient['full_name'] . ' — ₱' . number_format($grand, 2);
                $pdo->prepare("INSERT INTO notifications (icon, color, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())")
                    ->execute(['fa-file-circle-plus', $accent_light, $msg]);
            } catch (PDOException $e) {}

            $pdo->commit();
            $_SESSION['flash_patient'] = 'Added ' . count($items) . ' transaction' . (count($items)>1?'s':'') . ' for ' . $patient['full_name'] . '.';
            header('Location: patients.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Failed to save: ' . $e->getMessage();
        }
    }
}

$initials = strtoupper(substr($full_name, 0, 2));

/* Notifications */
$unread_count = 0; $notifications = [];
try { $unread_count = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn(); } catch (PDOException $e) {}
try { $notifications = $pdo->query("SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago FROM notifications ORDER BY is_read ASC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
if (empty($notifications)) { $notifications = [['id'=>0,'icon'=>'fa-info-circle','color'=>$accent_light,'message'=>'Welcome to the Patient-Centered Hospital Billing System.','is_read'=>0,'created_at'=>date('Y-m-d H:i:s'),'seconds_ago'=>0]]; $unread_count = 1; }
function timeAgo($dt,$sec=null){$s=$sec!==null?(int)$sec:(time()-strtotime($dt));if($s<0)$s=0;if($s<60)return 'Just now';if($s<3600)return floor($s/60).' min ago';if($s<86400)return floor($s/3600).' hr ago';$ts=strtotime($dt);$td=date('Y-m-d');$yd=date('Y-m-d',strtotime('-1 day'));$ds=date('Y-m-d',$ts);if($ds===$td)return 'Today, '.date('g:i A',$ts);if($ds===$yd)return 'Yesterday, '.date('g:i A',$ts);return date('M d, Y, g:i A',$ts);}
function notifTitle($m){$msg=strtolower($m??'');if(strpos($msg,'welcome')!==false)return 'System Welcome';if(strpos($msg,'marked as')!==false)return 'Payment Update';if(strpos($msg,'new billing')!==false||strpos($msg,'record added')!==false)return 'New Billing Record';if(strpos($msg,'updated')!==false)return 'Record Updated';if(strpos($msg,'deleted')!==false)return 'Record Deleted';if(strpos($msg,'added')!==false)return 'New Transaction';return 'Notification';}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Transaction · <?= htmlspecialchars($patient['full_name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#ffffff;display:flex;min-height:100vh;color:#1e293b}

.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}

/* Exact Sidebar Brand Match */
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
.sidebar-menu li a:hover{background:#f8fafc;color:<?= $accent ?>}
.sidebar-menu li a:hover i{color:<?= $accent ?>}
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.sidebar-footer a:hover i{color:#dc2626}
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

.main{margin-left:270px;flex:1;padding:0 0 40px 0;min-height:100vh}
.top-bar{background:#fff;padding:18px 32px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:25px}
.top-bar h1{font-size:22px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:12px}
.top-bar h1 .h-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 6px 16px rgba(0,0,0,.15)}
.top-bar .sub{font-size:12.5px;color:#64748b;margin-top:4px;margin-left:52px}
.top-bar-actions{display:flex;align-items:center;gap:15px}

.notification-wrapper{position:relative;cursor:pointer}
.notification-wrapper .bell-icon{font-size:18px;color:#1e293b;background:#fff;padding:11px;border-radius:50%;border:1px solid #e2e8f0;transition:all .25s}
.notification-wrapper:hover .bell-icon{color:<?= $accent ?>;border-color:<?= $accent_light ?>;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.notification-wrapper .badge{position:absolute;top:-3px;right:-3px;background:#dc2626;color:#fff;font-size:10px;font-weight:700;min-width:20px;height:20px;padding:0 5px;border-radius:10px;display:flex;align-items:center;justify-content:center;border:2px solid #fff}
.notification-dropdown{position:absolute;top:52px;right:0;width:380px;background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 12px 40px rgba(0,0,0,.12);display:none;z-index:500;max-height:480px;overflow-y:auto}
.notification-dropdown.show{display:block}
.notification-dropdown .dropdown-header{padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff}
.notification-dropdown .dropdown-header .title{font-weight:700;font-size:14px;color:#0f172a}
.notification-item{display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid #f8fafc;cursor:pointer}
.notification-item:hover{background:#f8fafc}
.notification-item.unread{background:#f0fdfa}
.notification-item .notif-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:14px}
.notification-item .notif-title{font-size:12.5px;font-weight:700;color:#0f172a;margin-bottom:3px}
.notification-item .notif-message{font-size:12px;color:#64748b;margin-bottom:4px}
.notification-item .notif-time{font-size:11px;color:#94a3b8}
.notification-dropdown .dropdown-footer{padding:12px 20px;text-align:center;border-top:1px solid #f1f5f9;background:#f8fafc}
.notification-dropdown .dropdown-footer a{color:<?= $accent ?>;text-decoration:none;font-size:12.5px;font-weight:600}

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:600;text-transform:uppercase;margin-top:2px}

.content{padding:0 32px}

.alert{padding:14px 18px;border-radius:11px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:500}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}

.patient-card{background:linear-gradient(135deg,#f0fdfa,#e0f7f4);border:1.5px solid #99f6e4;border-radius:14px;padding:20px 24px;margin-bottom:22px;display:flex;align-items:center;gap:18px}
.patient-card .pc-avatar{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;flex-shrink:0;box-shadow:0 6px 16px rgba(20,184,166,.25)}
.patient-card .pc-info{flex:1}
.patient-card .pc-name{font-size:18px;font-weight:800;color:#0f172a;margin-bottom:4px}
.patient-card .pc-meta{font-size:12.5px;color:#64748b;display:flex;gap:14px;flex-wrap:wrap}
.patient-card .pc-meta span{display:inline-flex;align-items:center;gap:5px}
.patient-card .pc-meta i{color:<?= $accent ?>;font-size:11px}

.card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:22px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.card-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.card-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.card-header h3 i{color:<?= $accent ?>;font-size:15px}
.card-body{padding:22px 24px}

.items-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden}
/* Adjusted Grid: Removed Qty column */
.items-head,.items-row{display:grid;grid-template-columns:2fr 3fr 1.2fr 1.2fr 44px;gap:10px;padding:12px 16px;align-items:start;}
.items-head{background:#f8fafc;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #f1f5f9;align-items:center;}
.items-row{border-bottom:1px solid #f1f5f9;}
.items-row:last-child{border-bottom:none;}
.items-row input,.items-row select{width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;background:#fff;color:#1e293b;}
.items-row input:focus,.items-row select:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 3px rgba(20,184,166,.1)}
.items-row .row-total{font-weight:700;color:<?= $accent ?>;text-align:right;font-size:13.5px;padding-right:6px; padding-top:10px;}
.col-svc{display:flex;flex-direction:column;gap:4px;}
.pt-explanation{display:none;font-size:11px;color:#0f766e;background:#f0fdfa;padding:6px 10px;border-radius:6px;border-left:3px solid #14b8a6;font-style:italic;line-height:1.4;margin-top:2px;}

.btn-remove{width:34px;height:34px;border-radius:9px;border:1.5px solid #fecaca;background:#fff;color:#dc2626;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .2s;margin-top:4px;}
.btn-remove:hover{background:#fef2f2}
.btn-remove:disabled{opacity:.4;cursor:not-allowed}

.btn-add-item{margin-top:12px;padding:11px 18px;border-radius:10px;border:1.5px dashed #5eead4;background:#f0fdfa;color:#0f766e;font-weight:700;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn-add-item:hover{background:#ccfbf1;border-style:solid}

.summary-bar{margin-top:20px;background:linear-gradient(135deg,<?= $accent ?>,#<?= ltrim($accent_light,'#') ?>);color:#fff;border-radius:12px;padding:18px 24px;display:flex;justify-content:space-between;align-items:center}
.summary-bar .sb-label{font-size:13px;opacity:.9;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
.summary-bar .sb-total{font-size:26px;font-weight:800;margin-top:4px}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column}
.form-group label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px}
.form-group input,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13.5px;font-family:inherit;background:#fff;color:#1e293b}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}
.form-group.full{grid-column:1 / -1}
.form-group .hint{font-size:11.5px;color:#94a3b8;margin-top:6px}

.form-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9}
.btn{padding:11px 22px;border-radius:10px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.15)}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-light{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-light:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}

@media(max-width:900px){.form-grid{grid-template-columns:1fr}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}.main{margin-left:0}
  .top-bar,.content{padding-left:18px;padding-right:18px}
  .user-chip .info{display:none}.user-chip{padding:5px}
  .items-head{display:none}
  .items-row{grid-template-columns:1fr;gap:8px}
  .btn{width:100%;justify-content:center}
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
                <div class="brand-sub"><?= $sidebar_sub ?></div>
            </div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="<?= $dash_url ?>"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <?php if ($is_frontdesk): ?>
            <li><a href="patients.php" class="active"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
            <li><a href="add_patient.php"><i class="fas fa-user-plus"></i> <span>Register & Bill</span></a></li>
        <?php endif; ?>
        <?php if ($is_billing): ?>
            <li><a href="patients.php" class="active"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <?php endif; ?>
        <li><a href="billing_records.php"><i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div>
        <h1><span class="h-icon"><i class="fas fa-plus-circle"></i></span> Add Transaction</h1>
        <div class="sub">Add new services/charges to an existing patient.</div>
    </div>
    <div class="top-bar-actions">
        <div class="notification-wrapper" onclick="event.stopPropagation(); toggleDropdown()">
            <i class="fas fa-bell bell-icon"></i>
            <?php if ($unread_count > 0): ?><span class="badge" id="notifBadge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
            <div class="notification-dropdown" id="notificationDropdown" onclick="event.stopPropagation();">
                <div class="dropdown-header">
                    <span class="title">Notifications</span>
                    <?php if ($unread_count > 0): ?><a href="notifications.php?mark_all=1" style="font-size:12px;color:<?= $accent ?>;text-decoration:none;font-weight:600;">Mark all read</a><?php endif; ?>
                </div>
                <?php foreach ($notifications as $notif): $isUnread = empty($notif['is_read']); ?>
                    <div class="notification-item <?= $isUnread ? 'unread' : '' ?>" data-id="<?= (int)$notif['id'] ?>" <?php if ($isUnread && (int)$notif['id'] > 0): ?>onclick="markAsRead(event, this)"<?php endif; ?>>
                        <div class="notif-icon" style="background: <?= htmlspecialchars($notif['color'] ?: $accent_light) ?>;"><i class="fas <?= htmlspecialchars($notif['icon'] ?: 'fa-bell') ?>"></i></div>
                        <div style="flex:1;">
                            <div class="notif-title"><?= htmlspecialchars(notifTitle($notif['message'])) ?></div>
                            <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                            <div class="notif-time"><i class="fas fa-clock"></i> <?= timeAgo($notif['created_at'], $notif['seconds_ago'] ?? null) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="dropdown-footer"><a href="notifications.php">View all notifications</a></div>
            </div>
        </div>
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

<div class="patient-card">
    <div class="pc-avatar"><?= strtoupper(substr($patient['full_name'], 0, 2)) ?></div>
    <div class="pc-info">
        <div class="pc-name"><?= htmlspecialchars($patient['full_name']) ?></div>
        <div class="pc-meta">
            <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars($patient['patient_code'] ?? '—') ?></span>
            <span><i class="fas fa-user-tag"></i> <?= htmlspecialchars($patient['patient_type'] ?? 'Outpatient') ?></span>
            <?php if (!empty($patient['contact_number'])): ?>
                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($patient['contact_number']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="POST" autocomplete="off">
    <input type="hidden" name="patient_db_id" value="<?= (int)$patient['id'] ?>">

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> New Services / Charges</h3>
        </div>
        <div class="card-body">

            <div class="items-card">
                <div class="items-head">
                    <div>Category</div>
                    <div>Service Description</div>
                    <div>Unit Price (₱)</div>
                    <div style="text-align:right;padding-right:10px;">Subtotal</div>
                    <div></div>
                </div>
                <div id="itemsBody"></div>
            </div>

            <button type="button" class="btn-add-item" onclick="addItem()">
                <i class="fas fa-plus"></i> Add Another Service
            </button>

            <div class="summary-bar">
                <div>
                    <div class="sb-label">Total New Charges</div>
                    <div class="sb-total" id="sumTotal">₱ 0.00</div>
                </div>
                <i class="fas fa-money-bill-wave" style="font-size:36px;opacity:.25;"></i>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Additional Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label for="bill_date">Billing Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" id="bill_date" name="bill_date" required value="<?= htmlspecialchars($_POST['bill_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label>Payment Status</label>
                    <input type="text" value="Pending (default)" readonly style="background:#f0fdfa;color:#0f766e;font-weight:700;">
                    <div class="hint">Payment processed by Billing Staff.</div>
                </div>
                <div class="form-group full">
                    <label for="notes">Notes (optional)</label>
                    <input type="text" id="notes" name="notes" placeholder="e.g. Follow-up visit, additional lab test…" value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>">
                </div>
            </div>

            <div class="form-actions">
                <a href="patients.php" class="btn btn-light"><i class="fas fa-xmark"></i> Cancel</a>
                <button type="submit" name="save_transactions" class="btn btn-primary">
                    <i class="fas fa-floppy-disk"></i> Save Transactions
                </button>
            </div>
        </div>
    </div>

</form>

</div>
</main>

<script>
const CATALOG = <?= $catalog_json ?>;
const CATEGORIES = <?= json_encode($categories) ?>;

let rowCounter = 0;

function addItem() {
    rowCounter++;
    const id = 'row_' + rowCounter;

    let catOpts = '<option value="">— Select Category —</option>';
    CATEGORIES.forEach(c => {
        catOpts += `<option value="${c.id}">${c.name}</option>`;
    });

    const row = document.createElement('div');
    row.className = 'items-row';
    row.id = id;

    row.innerHTML = `
        <div class="col-cat">
            <select class="cat-select" onchange="updateServiceOptions(this, '${id}')" required>
                ${catOpts}
            </select>
        </div>
        <div class="col-svc">
            <select name="service_id[]" class="svc-select" onchange="updateServiceDetails(this)" required>
                <option value="">— Select Category First —</option>
            </select>
            <div class="pt-explanation"></div>
        </div>
        <div class="col-amt">
            <input type="number" name="amount[]" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()" required>
        </div>
        <div class="col-tot">
            <div class="row-total">₱ 0.00</div>
        </div>
        <div class="col-del">
            <button type="button" class="btn-remove" onclick="removeItem('${id}')"><i class="fas fa-xmark"></i></button>
        </div>
    `;
    
    document.getElementById('itemsBody').appendChild(row);
    updateTotals();
    refreshRemoveButtons();
}

function updateServiceOptions(catSelect, rowId) {
    const catId = catSelect.value;
    const row = document.getElementById(rowId);
    const svcSelect = row.querySelector('.svc-select');
    const expDiv = row.querySelector('.pt-explanation');
    const amtInput = row.querySelector('input[name="amount[]"]');

    // Reset service & explanation
    svcSelect.innerHTML = '<option value="">— Select Service —</option>';
    expDiv.style.display = 'none';
    expDiv.textContent = '';
    amtInput.value = '';

    if (!catId) {
        svcSelect.innerHTML = '<option value="">— Select Category First —</option>';
        updateTotals();
        return;
    }

    const services = (CATALOG.services || []).filter(s => String(s.category_id) === String(catId));
    
    if (services.length === 0) {
        svcSelect.innerHTML = '<option value="">— No services available —</option>';
    } else {
        services.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.service_name;
            opt.dataset.explanation = s.patient_explanation || '';
            opt.dataset.defaultPrice = (s.default_price !== null && s.default_price !== undefined) ? s.default_price : '';
            opt.dataset.hasDefault = String(s.has_default_price) === '1' ? '1' : '0';
            svcSelect.appendChild(opt);
        });
    }
    updateTotals();
}

function updateServiceDetails(svcSelect) {
    const opt = svcSelect.options[svcSelect.selectedIndex];
    const row = svcSelect.closest('.items-row');
    const expDiv = row.querySelector('.pt-explanation');
    const amtInput = row.querySelector('input[name="amount[]"]');

    if (!opt || !svcSelect.value) {
        expDiv.style.display = 'none';
        expDiv.textContent = '';
        amtInput.value = '';
        updateTotals();
        return;
    }

    // Show Patient Explanation
    if (opt.dataset.explanation) {
        expDiv.innerHTML = '<i class="fas fa-lightbulb"></i> ' + opt.dataset.explanation;
        expDiv.style.display = 'block';
    } else {
        expDiv.style.display = 'none';
    }

    // Auto-fill Default Price if available
    if (opt.dataset.hasDefault === '1' && opt.dataset.defaultPrice !== '') {
        amtInput.value = parseFloat(opt.dataset.defaultPrice).toFixed(2);
    } else {
        amtInput.value = '';
    }
    updateTotals();
}

function removeItem(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.remove();
    updateTotals();
    refreshRemoveButtons();
}

function refreshRemoveButtons() {
    const rows = document.querySelectorAll('#itemsBody .items-row');
    rows.forEach(r => {
        const btn = r.querySelector('.btn-remove');
        if (btn) btn.disabled = rows.length === 1;
    });
}

function updateTotals() {
    const rows = document.querySelectorAll('#itemsBody .items-row');
    let total = 0;
    rows.forEach(r => {
        const a = parseFloat(r.querySelector('input[name="amount[]"]').value) || 0;
        const t = a; // Quantity is fixed to 1
        total += t;
        r.querySelector('.row-total').textContent = '₱ ' + t.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    });
    document.getElementById('sumTotal').textContent = '₱ ' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

document.addEventListener('DOMContentLoaded', function(){
    addItem(); // Add first row automatically
});

function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}
function toggleDropdown(){var dd=document.getElementById('notificationDropdown');if(dd)dd.classList.toggle('show');}
document.addEventListener('click',function(e){if(!e.target.closest('.notification-wrapper')){var dd=document.getElementById('notificationDropdown');if(dd)dd.classList.remove('show');}});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){var dd=document.getElementById('notificationDropdown');if(dd)dd.classList.remove('show');}});
function markAsRead(event,el){event.stopPropagation();if(!el.classList.contains('unread'))return;var id=parseInt(el.dataset.id,10);if(!id)return;el.classList.remove('unread');el.removeAttribute('onclick');var badge=document.getElementById('notifBadge');if(badge){var c=parseInt(badge.textContent,10)||0;var n=Math.max(0,c-1);if(n===0)badge.remove();else badge.textContent=n>9?'9+':n;}fetch('mark_notification_read.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}).catch(function(){});}
</script>
</body>
</html>