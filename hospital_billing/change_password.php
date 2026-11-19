<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: login.php'); exit; }
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$success = ''; $error = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { header('Location: logout.php'); exit; }
} catch (PDOException $e) { $error = "Error: ".$e->getMessage(); }

$full_name = $user['full_name'] ?? 'User';
$user_role = $user['role'] ?? 'Admin';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stored = $user['password'] ?? '';
    $current_ok = false;
    if ($stored !== '') {
        if (password_verify($current, $stored)) { $current_ok = true; }
        elseif (md5($current) === $stored) { $current_ok = true; }
    }

    if ($current === '') {
        $error = "Current password is required.";
    } elseif (!$current_ok) {
        $error = "Current password is incorrect.";
    } elseif ($new === '') {
        $error = "New password is required.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new === $current) {
        $error = "New password must be different from the current password.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        try {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$new_hash, $user_id]);
            $user['password'] = $new_hash;
            $success = "Password changed successfully!";
        } catch (PDOException $e) {
            $error = "Error: ".$e->getMessage();
        }
    }
}

$initials = '';
$parts = preg_split('/\s+/', trim($user['full_name'] ?? ''));
if (count($parts) >= 2) $initials = strtoupper(substr($parts[0],0,1).substr($parts[count($parts)-1],0,1));
else $initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 2));

$unread_count = 0; $notifications = [];
try { $unread_count = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn(); } catch (PDOException $e) {}
try { $notifications = $pdo->query("SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago FROM notifications ORDER BY is_read ASC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
if (empty($notifications)) {
    $notifications = [['id'=>0,'icon'=>'fa-info-circle','color'=>'#14b8a6','message'=>'Welcome to the Patient-Centered Hospital Billing System.','is_read'=>0,'created_at'=>date('Y-m-d H:i:s'),'seconds_ago'=>0]];
    $unread_count = 1;
}
if (!function_exists('timeAgo')) {
    function timeAgo($dt,$sec=null){$s=$sec!==null?(int)$sec:(time()-strtotime($dt));if($s<0)$s=0;if($s<60)return 'Just now';if($s<3600)return floor($s/60).' min ago';if($s<86400)return floor($s/3600).' hr ago';$ts=strtotime($dt);$td=date('Y-m-d');$yd=date('Y-m-d',strtotime('-1 day'));$ds=date('Y-m-d',$ts);if($ds===$td)return 'Today, '.date('g:i A',$ts);if($ds===$yd)return 'Yesterday, '.date('g:i A',$ts);return date('M d, Y, g:i A',$ts);}
}
if (!function_exists('notifTitle')) {
    function notifTitle($m){$msg=strtolower($m??'');if(strpos($msg,'welcome')!==false)return 'System Welcome';if(strpos($msg,'marked as')!==false)return 'Payment Update';if(strpos($msg,'new billing')!==false||strpos($msg,'record added')!==false)return 'New Billing Record';if(strpos($msg,'updated')!==false)return 'Record Updated';if(strpos($msg,'deleted')!==false)return 'Record Deleted';if(strpos($msg,'user')!==false)return 'User Activity';if(strpos($msg,'alert')!==false)return 'System Alert';return 'Notification';}
}

$r = strtolower(trim($user_role));
$is_billing = in_array($r,['billing','cashier'],true) || strpos($r,'billing')!==false || strpos($r,'cashier')!==false;
$is_frontdesk = strpos($r,'reception')!==false || strpos($r,'frontdesk')!==false || strpos($r,'front desk')!==false || strpos($r,'front-desk')!==false || strpos($r,'front office')!==false;
if ($is_billing) { $sidebar_brand='Billing Staff'; $sidebar_sub='Billing'; $dash_url='billing_dashboard.php'; }
elseif ($is_frontdesk) { $sidebar_brand='Front Desk'; $sidebar_sub='Receptionist'; $dash_url='frontdesk_dashboard.php'; }
else { $sidebar_brand='Admin'; $sidebar_sub='Administrator'; $dash_url='dashboard.php'; }

$accent       = '#0f766e';
$accent_light = '#14b8a6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ===== BODY: soft teal mesh gradient background (sama seperti login.php) ===== */
body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex;min-height:100vh;color:#1e293b;
  position:relative;
  overflow-x:hidden;
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

.sidebar{width:270px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .3s ease;overflow-y:auto;border-right:1px solid rgba(226,232,240,.8)}

.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:<?= $accent ?>;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}

.sidebar-divider{height:1px;background:#f1f5f9;margin:0 20px 25px 20px}
.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f0fdfa;color:<?= $accent ?>}
.sidebar-menu li a:hover i{color:<?= $accent ?>}
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-menu li.has-submenu>a .submenu-arrow{margin-left:auto;font-size:11px;width:auto;transition:transform .3s;color:#94a3b8}
.sidebar-menu li.has-submenu.open>a .submenu-arrow{transform:rotate(180deg)}
.sidebar-menu li.has-submenu.open>a{background:#f0fdfa;color:<?= $accent ?>}
.sidebar-menu li.has-submenu.open>a i{color:<?= $accent ?>}
.submenu{list-style:none;max-height:0;overflow:hidden;transition:max-height .35s;padding:0;margin:4px 0}
.sidebar-menu li.has-submenu.open .submenu{max-height:220px}
.submenu li{margin-bottom:2px}
.submenu li a{padding:10px 16px 10px 52px;font-size:13px;color:#64748b;background:#f8fafc;border-radius:8px;display:flex;align-items:center;gap:10px}
.submenu li a i{width:16px;font-size:13px}
.submenu li a:hover{background:#f0fdfa;color:<?= $accent ?>}
.submenu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.submenu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0 14px;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.sidebar-footer a:hover i{color:#dc2626}

.main-content{margin-left:270px;flex:1;padding:25px 35px 40px 35px;min-height:100vh;position:relative;z-index:1}
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:15px}
.top-bar h1{font-size:26px;font-weight:700;color:#0f172a;margin-bottom:4px}
.top-bar p{font-size:13.5px;color:#64748b}
.top-bar-actions{display:flex;align-items:center;gap:15px}

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px;transition:all .25s ease}
.user-chip:hover{box-shadow:0 4px 12px rgba(15,118,110,.15);transform:translateY(-1px)}
.user-chip .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;letter-spacing:.5px}
.user-chip .info .name{font-size:13.5px;font-weight:700;color:#0f172a;line-height:1.2;white-space:nowrap}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;white-space:nowrap}

/* ✅ FULL WIDTH ALERTS AND CARD */
.alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:12px;width:100%}
.alert-success{background:#d1fae5;color:#065f46;border-left:5px solid #10b981}
.alert-danger{background:#f8d7da;color:#721c24;border-left:5px solid #dc3545}

.card{background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden;width:100%;border:1px solid #e2e8f0}
.card-header{background:linear-gradient(135deg,<?= $accent ?> 0%,<?= $accent_light ?> 100%);padding:32px 40px;color:#fff;display:flex;align-items:center;gap:18px}
.card-header .icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.card-header h2{font-size:22px;font-weight:800;margin-bottom:4px}
.card-header p{font-size:13px;color:rgba(255,255,255,.85)}
.card-body{padding:35px 40px 40px 40px}

.form-group{margin-bottom:20px}
.form-group label{display:block;font-weight:600;color:#334155;font-size:13px;margin-bottom:8px}
.form-group label .required{color:#dc3545;margin-left:3px}
.form-group input{width:100%;padding:13px 46px 13px 16px;border:1.5px solid #e2e8f0;border-radius:30px;font-size:14px;background:#f8fafc;font-family:inherit;color:#0f172a;transition:all .2s}
.form-group input::placeholder{color:#cbd5e1}
.form-group input:focus{border-color:<?= $accent_light ?>;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-hint{font-size:12px;color:#94a3b8;margin-top:8px}

.password-wrapper{position:relative}
.password-wrapper input{padding-right:48px}
.password-wrapper input::-ms-reveal,
.password-wrapper input::-ms-clear{display:none !important}
.password-wrapper input::-webkit-credentials-auto-fill-button,
.password-wrapper input::-webkit-strong-password-auto-fill-button,
.password-wrapper input::-webkit-textfield-decoration-container,
.password-wrapper input::-webkit-password-toggle-button{display:none !important;visibility:hidden !important;pointer-events:none !important;opacity:0 !important;width:0 !important;height:0 !important}
.toggle-password{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:#64748b;font-size:15px;cursor:pointer;padding:8px 10px;border-radius:8px;transition:all .2s;z-index:2;display:flex;align-items:center;justify-content:center}
.toggle-password:hover{color:#0d9488;background:#f0fdfa}

/* Password strength meter */
.strength{display:flex;gap:6px;margin-top:10px}
.strength-bar{flex:1;height:6px;border-radius:3px;background:#e2e8f0;transition:background .25s ease}
.strength-bar.active-weak{background:#dc3545}
.strength-bar.active-fair{background:#f59e0b}
.strength-bar.active-good{background:#0ea5e9}
.strength-bar.active-strong{background:#10b981}
.strength-label{font-size:12px;font-weight:600;margin-top:8px;color:#94a3b8}
.strength-label.weak{color:#dc3545}
.strength-label.fair{color:#f59e0b}
.strength-label.good{color:#0ea5e9}
.strength-label.strong{color:#10b981}

.form-actions{display:flex;gap:12px;margin-top:28px;padding-top:22px;border-top:1px solid #f0fdfa;flex-wrap:wrap}
.btn{padding:13px 26px;border:none;border-radius:30px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:9px;font-family:inherit;transition:all .2s;justify-content:center}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 14px rgba(15,118,110,.30)}
.btn-primary:hover{background:#115e59;transform:translateY(-2px)}
.btn-cancel{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-cancel:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}
.form-actions .btn{flex:1}

/* TOAST */
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:380px;width:calc(100% - 48px);pointer-events:none}
.toast{background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(15,23,42,.18);border:1px solid #e2e8f0;padding:16px 18px;display:flex;gap:12px;align-items:flex-start;pointer-events:auto;animation:toastIn .4s cubic-bezier(.2,.9,.3,1.1);position:relative;overflow:hidden}
.toast::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--toast-accent,#14b8a6)}
.toast.closing{animation:toastOut .3s ease forwards}
.toast .toast-body{flex:1;min-width:0}
.toast .toast-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.toast .toast-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:6px;word-wrap:break-word}
.toast .toast-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.toast .toast-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:4px;border-radius:6px;transition:all .2s;flex-shrink:0}
.toast .toast-close:hover{color:#dc2626;background:#fef2f2}
@keyframes toastIn{from{opacity:0;transform:translateX(120%)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(120%)}}

/* FAB */
.notif-fab{position:fixed;bottom:28px;right:28px;z-index:900;width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 10px 28px rgba(15,118,110,.4);transition:all .25s ease}
.notif-fab:hover{transform:scale(1.08) translateY(-2px);box-shadow:0 14px 34px rgba(15,118,110,.5)}
.notif-fab .fab-badge{position:absolute;top:-3px;right:-3px;background:#dc2626;color:#fff;font-size:11px;font-weight:800;min-width:22px;height:22px;padding:0 5px;border-radius:12px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.2)}

/* NOTIF MODAL */
.notif-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:9998;display:none;align-items:center;justify-content:center;padding:20px;animation:fadeIn .2s ease}
.notif-modal-overlay.show{display:flex}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.notif-modal{background:#fff;border-radius:18px;width:100%;max-width:520px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 25px 70px rgba(15,23,42,.3);overflow:hidden;animation:modalIn .3s cubic-bezier(.2,.9,.3,1.1)}
@keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.notif-modal-header{padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f0fdfa,#fff)}
.notif-modal-header h3{font-size:16px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px}
.notif-modal-header h3 i{color:<?= $accent ?>;font-size:16px}
.notif-modal-header .modal-sub{font-size:12px;color:#64748b;margin-top:3px;margin-left:26px}
.notif-modal-close{width:36px;height:36px;border-radius:10px;border:none;background:#f1f5f9;color:#475569;cursor:pointer;font-size:15px;transition:all .2s;display:flex;align-items:center;justify-content:center}
.notif-modal-close:hover{background:#fee2e2;color:#dc2626}
.notif-modal-body{overflow-y:auto;padding:8px 0;flex:1}
.notif-modal-body::-webkit-scrollbar{width:8px}
.notif-modal-body::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
.notif-item-modal{display:flex;gap:14px;padding:16px 24px;border-bottom:1px solid #f8fafc;transition:background .15s;cursor:pointer;border-left:3px solid transparent}
.notif-item-modal:last-child{border-bottom:none}
.notif-item-modal:hover{background:#f8fafc}
.notif-item-modal.unread{background:#f0fdfa;border-left-color:var(--notif-accent,#14b8a6)}
.notif-item-modal.unread:hover{background:#ccfbf1}
.notif-item-modal .notif-content{flex:1;min-width:0}
.notif-item-modal .notif-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.notif-item-modal .notif-unread-dot{width:8px;height:8px;border-radius:50%;background:#14b8a6;display:inline-block;flex-shrink:0}
.notif-item-modal .notif-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:5px;word-wrap:break-word}
.notif-item-modal .notif-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.notif-modal-footer{padding:14px 24px;border-top:1px solid #f1f5f9;text-align:center;background:#f8fafc}
.notif-modal-footer a{color:<?= $accent ?>;text-decoration:none;font-size:12.5px;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.notif-modal-footer a:hover{text-decoration:underline}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .card-header{padding:24px 22px;gap:14px}
  .card-header .icon{width:48px;height:48px;font-size:18px}
  .card-header h2{font-size:19px}
  .card-body{padding:24px 22px}
  .top-bar{flex-direction:column;align-items:flex-start}
  .user-chip .info{display:none}
  .user-chip{padding:6px}
  .form-actions{flex-direction:column-reverse}
  .form-actions .btn{width:100%}
  .toast-container{top:16px;right:16px;left:16px;width:auto;max-width:none}
  .notif-modal{max-height:90vh}
  .notif-fab{bottom:20px;right:20px;width:52px;height:52px;font-size:20px}
  body::before,body::after{opacity:.6}
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
                <div class="brand-sub"><?= strtoupper($sidebar_sub) ?></div>
            </div>
        </div>
    </div>
    <div class="sidebar-divider"></div>
    <ul class="sidebar-menu">
        <li><a href="<?= $dash_url ?>"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <?php if ($is_frontdesk): ?>
            <li><a href="patients.php"><i class="fas fa-users"></i> <span>Patient Records</span></a></li>
            <li><a href="add_patient.php"><i class="fas fa-user-plus"></i> <span>Register Patient</span></a></li>
            <li><a href="billing_records.php"><i class="fas fa-file-invoice"></i> <span>Billing Records</span></a></li>
        <?php elseif ($is_billing): ?>
            <li><a href="input_billing.php"><i class="fas fa-edit"></i> <span>Input Billing</span></a></li>
            <li><a href="patients.php"><i class="fas fa-users"></i> <span>Patient Records</span></a></li>
            <li><a href="billing_records.php"><i class="fas fa-file-invoice"></i> <span>Billing Records</span></a></li>
        <?php else: ?>
            <li><a href="administrator_monitoring.php"><i class="fas fa-shield-halved"></i> <span>Monitoring</span></a></li>
            <li><a href="explanations.php"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
            <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <?php endif; ?>
        <li class="has-submenu open" id="settingsMenuItem">
            <a href="javascript:void(0)" onclick="toggleSettingsSubmenu(event)"><i class="fas fa-cog"></i><span>Settings</span><i class="fas fa-chevron-down submenu-arrow"></i></a>
            <ul class="submenu">
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                <li><a href="change_password.php" class="active"><i class="fas fa-key"></i> <span>Change Password</span></a></li>
            </ul>
        </li>
    </ul>
    <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></div>
</aside>

<main class="main-content">

<div class="top-bar">
    <div class="top-bar-left">
        <h1>Change Password</h1>
        <div class="page-sub">Update your account password securely.</div>
    </div>
    <div class="top-bar-actions">
        <div class="user-chip">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="info">
                <div class="name"><?= htmlspecialchars($full_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="icon"><i class="fas fa-key"></i></div>
        <div>
            <h2>Password Settings</h2>
            <p>Choose a strong password you'll remember.</p>
        </div>
    </div>
    <div class="card-body">
        <form method="post" autocomplete="off">
            <div class="form-group">
                <label>Current Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="current_password" id="current_password" placeholder="Enter your current password" required autofocus>
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>New Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required minlength="6" oninput="updateStrength()">
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="strength">
                    <div class="strength-bar" id="bar1"></div>
                    <div class="strength-bar" id="bar2"></div>
                    <div class="strength-bar" id="bar3"></div>
                    <div class="strength-bar" id="bar4"></div>
                </div>
                <div class="strength-label" id="strengthLabel">At least 6 characters</div>
            </div>

            <div class="form-group">
                <label>Confirm New Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-hint" id="matchHint"></div>
            </div>

            <div class="form-actions">
                <a href="<?= $dash_url ?>" class="btn btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" name="change_password" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
            </div>
        </form>
    </div>
</div>
</main>

<div class="toast-container" id="toastContainer"></div>

<button class="notif-fab" onclick="openNotifModal()" title="View notifications">
    <i class="fas fa-bell"></i>
    <?php if ($unread_count > 0): ?>
        <span class="fab-badge" id="fabBadge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
    <?php endif; ?>
</button>

<div class="notif-modal-overlay" id="notifModalOverlay" onclick="if(event.target===this)closeNotifModal()">
    <div class="notif-modal" role="dialog" aria-modal="true">
        <div class="notif-modal-header">
            <div>
                <h3><i class="fas fa-bell"></i> Notifications</h3>
                <div class="modal-sub"><?= count($notifications) ?> recent · <?= $unread_count ?> unread</div>
            </div>
            <button class="notif-modal-close" onclick="closeNotifModal()" title="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="notif-modal-body">
            <?php foreach ($notifications as $notif): $isUnread = empty($notif['is_read']); ?>
                <div class="notif-item-modal <?= $isUnread ? 'unread' : '' ?>"
                     data-id="<?= (int)$notif['id'] ?>"
                     data-color="<?= htmlspecialchars($notif['color'] ?: $accent_light) ?>"
                     style="--notif-accent: <?= htmlspecialchars($notif['color'] ?: $accent_light) ?>;"
                     <?php if ($isUnread && (int)$notif['id'] > 0): ?>onclick="markAsRead(event, this)"<?php endif; ?>>
                    <div class="notif-content">
                        <div class="notif-title">
                            <?php if ($isUnread): ?><span class="notif-unread-dot"></span><?php endif; ?>
                            <?= htmlspecialchars(notifTitle($notif['message'])) ?>
                        </div>
                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                        <div class="notif-time"><i class="fas fa-clock"></i> <?= timeAgo($notif['created_at'], $notif['seconds_ago'] ?? null) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="notif-modal-footer">
            <a href="notifications.php">View all notifications <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}

/* ===== SHOW / HIDE PASSWORD ===== */
function togglePassword(fieldId, btn){
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/* ===== PASSWORD STRENGTH ===== */
function updateStrength(){
    const val = document.getElementById('new_password').value;
    const bars = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3'), document.getElementById('bar4')];
    const label = document.getElementById('strengthLabel');

    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    if (score > 4) score = 4;

    bars.forEach(b => b.className = 'strength-bar');
    label.className = 'strength-label';

    if (val.length === 0) {
        label.textContent = 'At least 6 characters';
        return;
    }
    if (score <= 1) {
        bars[0].classList.add('active-weak');
        label.classList.add('weak');
        label.textContent = 'Weak password';
    } else if (score === 2) {
        bars[0].classList.add('active-fair');
        bars[1].classList.add('active-fair');
        label.classList.add('fair');
        label.textContent = 'Fair password';
    } else if (score === 3) {
        bars[0].classList.add('active-good');
        bars[1].classList.add('active-good');
        bars[2].classList.add('active-good');
        label.classList.add('good');
        label.textContent = 'Good password';
    } else {
        bars[0].classList.add('active-strong');
        bars[1].classList.add('active-strong');
        bars[2].classList.add('active-strong');
        bars[3].classList.add('active-strong');
        label.classList.add('strong');
        label.textContent = 'Strong password';
    }
}

/* ===== MATCH HINT ===== */
document.addEventListener('DOMContentLoaded', function(){
    const np = document.getElementById('new_password');
    const cp = document.getElementById('confirm_password');
    const hint = document.getElementById('matchHint');
    if (cp && hint) {
        cp.addEventListener('input', function(){
            const a = np.value, b = cp.value;
            if (b === '') { hint.textContent = ''; return; }
            if (a === b) { hint.style.color = '#10b981'; hint.textContent = '✓ Passwords match'; }
            else { hint.style.color = '#dc3545'; hint.textContent = '✗ Passwords do not match'; }
        });
    }
});

/* ===== NOTIF MODAL ===== */
function openNotifModal(){
    document.getElementById('notifModalOverlay').classList.add('show');
    document.body.style.overflow='hidden';
}
function closeNotifModal(){
    document.getElementById('notifModalOverlay').classList.remove('show');
    document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeNotifModal();});

/* ===== TOAST ===== */
function showToast(title, message, color, duration){
    duration = duration || 6000;
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.setProperty('--toast-accent', color || '#14b8a6');
    toast.innerHTML = `
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
            <div class="toast-time"><i class="fas fa-clock"></i> Just now</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this)"><i class="fas fa-xmark"></i></button>
    `;
    container.appendChild(toast);
    setTimeout(() => dismissToast(toast.querySelector('.toast-close')), duration);
}
function dismissToast(btn){
    const toast = btn.closest('.toast');
    if (!toast || toast.classList.contains('closing')) return;
    toast.classList.add('closing');
    setTimeout(() => toast.remove(), 300);
}

/* ===== MARK AS READ ===== */
function markAsRead(event, el){
    event.stopPropagation();
    if (!el.classList.contains('unread')) return;
    const id = parseInt(el.dataset.id, 10);
    if (!id) return;
    el.classList.remove('unread');
    const dot = el.querySelector('.notif-unread-dot');
    if (dot) dot.remove();
    el.removeAttribute('onclick');
    const badge = document.getElementById('fabBadge');
    if (badge) {
        let c = parseInt(badge.textContent, 10) || 0;
        const n = Math.max(0, c - 1);
        if (n === 0) badge.remove();
        else badge.textContent = n > 9 ? '9+' : n;
    }
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    }).catch(() => {});
}

/* ===== AUTO-TOAST LATEST UNREAD ===== */
(function autoToast(){
    const first = document.querySelector('.notif-item-modal.unread');
    if (!first) return;
    const titleEl = first.querySelector('.notif-title');
    const msgEl   = first.querySelector('.notif-message');
    if (!msgEl) return;
    const color = first.dataset.color || '#14b8a6';
    const t  = titleEl ? titleEl.textContent.replace(/\s+/g,' ').trim() : 'Notification';
    const m  = msgEl.textContent.trim();
    setTimeout(() => showToast(t, m, color, 6000), 500);
})();

/* ===== Auto-open Settings submenu ===== */
document.addEventListener('DOMContentLoaded',function(){
    const p = window.location.pathname.toLowerCase();
    if (p.includes('profile.php') || p.includes('change_password.php')) {
        const item = document.getElementById('settingsMenuItem');
        if (item) item.classList.add('open');
    }
});
</script>
</body>
</html>