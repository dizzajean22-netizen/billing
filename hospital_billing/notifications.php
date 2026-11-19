<?php
/* ============================================================
   notifications.php — full page
   ============================================================ */

/* ---- Debug (turn off in production) ---- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---- Timezone ---- */
date_default_timezone_set('Asia/Manila');

/* ---- Session ---- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---- Login check ---- */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

/* ---- Config ---- */
require_once __DIR__ . '/config.php';

/* ---- Force MySQL timezone ---- */
try { $pdo->exec("SET time_zone = '+08:00'"); } catch (PDOException $e) {}

/* ---- Current user ---- */
$user_id   = $_SESSION['user_id']  ?? 0;
$user_name = $_SESSION['username'] ?? 'Admin';
$full_name = $user_name;
$user_role = 'Admin';

try {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $full_name = $u['full_name'] ?? $user_name;
        $user_role = $u['role']      ?? 'Admin';
    }
} catch (PDOException $e) { /* silent */ }

/* ---- Mark all as read ---- */
if (isset($_GET['mark_all']) && $_GET['mark_all'] === '1') {
    try { $pdo->exec("UPDATE notifications SET is_read = 1"); } catch (PDOException $e) {}
    header("Location: notifications.php");
    exit;
}

/* ---- Fetch notifications ---- */
$notifications = [];
try {
    $notifications = $pdo->query("
        SELECT *,
               TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago
        FROM notifications
        ORDER BY id DESC
        LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
}

/* ---- Title helper ---- */
if (!function_exists('notifTitle')) {
    function notifTitle($n) {
        $msg = strtolower($n['message'] ?? '');
        if (strpos($msg, 'welcome') !== false)       return 'System Welcome';
        if (strpos($msg, 'marked as') !== false)     return 'Payment Update';
        if (strpos($msg, 'new billing record') !== false || strpos($msg, 'record added') !== false) return 'New Billing Record Added';
        if (strpos($msg, 'updated') !== false)       return 'Billing Record Updated';
        if (strpos($msg, 'deleted') !== false)       return 'Billing Record Deleted';
        if (strpos($msg, 'new user') !== false || strpos($msg, 'user added') !== false) return 'New User Added';
        if (strpos($msg, 'user') !== false)          return 'User Activity';
        if (strpos($msg, 'alert') !== false)         return 'System Alert';
        return 'Notification';
    }
}

/* ---- Time helper ---- */
if (!function_exists('prettyTime')) {
    function prettyTime($n) {
        $sec = isset($n['seconds_ago']) ? (int)$n['seconds_ago'] : null;
        if ($sec === null) $sec = time() - strtotime($n['created_at']);
        if ($sec < 0) $sec = 0;
        if ($sec < 60)    return 'Just now';
        if ($sec < 3600)  return floor($sec / 60) . ' min ago';
        if ($sec < 86400) return floor($sec / 3600) . ' hr ago';

        $ts        = strtotime($n['created_at']);
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dateStr   = date('Y-m-d', $ts);

        if ($dateStr === $today)     return 'Today, '     . date('g:i A', $ts);
        if ($dateStr === $yesterday) return 'Yesterday, ' . date('g:i A', $ts);
        return date('M d, Y, g:i A', $ts);
    }
}

/* ---- Unread count ---- */
$unread_count = 0;
foreach ($notifications as $n) {
    if (empty($n['is_read'])) $unread_count++;
}

/* ---- Role detection ---- */
$r = strtolower(trim($user_role));

$is_billing   = in_array($r, ['billing','cashier'], true)
              || strpos($r, 'billing') !== false
              || strpos($r, 'cashier') !== false;

$is_frontdesk = strpos($r, 'reception')    !== false
             || strpos($r, 'frontdesk')    !== false
             || strpos($r, 'front desk')   !== false
             || strpos($r, 'front-desk')   !== false
             || strpos($r, 'front office') !== false;

if ($is_billing) {
    $sidebar_sub   = 'Billing';
    $dash_url      = 'billing_dashboard.php';
} elseif ($is_frontdesk) {
    $sidebar_sub   = 'Receptionist';
    $dash_url      = 'frontdesk_dashboard.php';
} else {
    $sidebar_sub   = 'Administrator';
    $dash_url      = 'dashboard.php';
}

/* ---- Theme ---- */
$accent       = '#0f766e';
$accent_light = '#14b8a6';
$initials     = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - Patient-Centered Hospital Billing System</title>
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

/* SIDEBAR (semi-transparent + blur) */
.sidebar{width:270px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .3s ease;overflow-y:auto;border-right:1px solid rgba(226,232,240,.8)}

/* Exact Sidebar Brand Match */
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

/* SUBMENU STYLES (Settings dropdown) */
.sidebar-menu li.has-submenu > a .submenu-arrow{margin-left:auto;font-size:11px;width:auto;transition:transform .3s;color:#94a3b8}
.sidebar-menu li.has-submenu.open > a .submenu-arrow{transform:rotate(180deg)}
.sidebar-menu li.has-submenu.open > a{background:#f0fdfa;color:<?= $accent ?>}
.sidebar-menu li.has-submenu.open > a i{color:<?= $accent ?>}
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

/* HEADER */
.top-bar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:25px;flex-wrap:wrap;gap:15px}
.top-bar h1{font-size:26px;font-weight:700;color:#0f172a;margin-bottom:4px}
.top-bar p{font-size:13.5px;color:#64748b}
.top-bar p strong{color:<?= $accent ?>}
.top-bar-right{display:flex;align-items:center;gap:10px}

/* BUTTONS */
.btn-back{width:42px;height:42px;border-radius:10px;background:#fff;border:1.5px solid #e2e8f0;color:#64748b;display:inline-flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;transition:all .25s ease;text-decoration:none}
.btn-back:hover{background:<?= $accent ?>;border-color:<?= $accent ?>;color:#fff;transform:translateX(-3px)}

.btn-mark-all{padding:10px 18px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:13.5px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .25s ease;box-shadow:0 4px 12px rgba(15,118,110,.25)}
.btn-mark-all:hover{background:#115e59;transform:translateY(-1px)}

/* CARD */
.card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}

/* NOTIF ROW */
.notif-item{display:flex;align-items:flex-start;gap:16px;padding:20px 24px;border-bottom:1px solid #f1f5f9;transition:background .2s ease;text-decoration:none;color:inherit;position:relative;cursor:pointer}
.notif-item:last-child{border-bottom:none}
.notif-item:hover{background:#f8fafc}
.notif-item.unread{background:#f0fdfa}
.notif-item.unread::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:<?= $accent ?>}
.notif-item.unread:hover{background:#ccfbf1}
.notif-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:17px}
.notif-body{flex:1;min-width:0}
.notif-title{font-size:14.5px;font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.notif-title .dot{width:8px;height:8px;background:<?= $accent ?>;border-radius:50%;display:inline-block;flex-shrink:0}
.notif-message{font-size:13.5px;color:#475569;line-height:1.5;margin-bottom:6px;word-wrap:break-word}
.notif-time{font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:6px;font-weight:500}
.notif-time i{font-size:11px}

/* EMPTY */
.text-center{text-align:center;padding:80px 20px;color:#94a3b8}
.text-center .empty-icon{font-size:56px;color:#cbd5e1;margin-bottom:16px}
.text-center .empty-title{font-size:16px;font-weight:600;color:#0f172a;margin-bottom:6px}
.text-center .empty-desc{font-size:13.5px}

/* BURGER */
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .top-bar{flex-direction:column;align-items:flex-start}
  .top-bar-right{width:100%;justify-content:space-between}
  .notif-item{padding:16px 18px;gap:12px}
  .notif-icon{width:38px;height:38px;font-size:15px}
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
        <li><a href="<?= htmlspecialchars($dash_url) ?>"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
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
        
        <!-- ✅ SETTINGS DROPDOWN -->
        <li class="has-submenu" id="settingsMenuItem">
            <a href="javascript:void(0)" onclick="toggleSettingsSubmenu(event)">
                <i class="fas fa-cog"></i><span>Settings</span>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </a>
            <ul class="submenu">
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> <span>Change Password</span></a></li>
            </ul>
        </li>
    </ul>
    <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></div>
</aside>

<main class="main-content">

    <div class="top-bar">
        <div class="top-bar-left">
            <h1>Notifications</h1>
            <p>
                Stay updated with important billing activities and system alerts.
                <?php if ($unread_count > 0): ?>
                    You have <strong><?= $unread_count ?></strong> unread.
                <?php endif; ?>
            </p>
        </div>
        <div class="top-bar-right">
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all=1" class="btn-mark-all">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($dash_url) ?>" class="btn-back" title="Back to Dashboard">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <div class="card">
        <?php if (empty($notifications)): ?>
            <div class="text-center">
                <div class="empty-icon"><i class="fas fa-bell-slash"></i></div>
                <div class="empty-title">No notifications</div>
                <div class="empty-desc">You are all caught up.</div>
            </div>
        <?php else: foreach ($notifications as $n): ?>
            <?php
                $isUnread = empty($n['is_read']);
                $title    = notifTitle($n);
                $time     = prettyTime($n);
            ?>
            <div class="notif-item <?= $isUnread ? 'unread' : '' ?>"
                 data-id="<?= (int)$n['id'] ?>"
                 <?php if ($isUnread): ?>
                     onclick="markAsRead(this)"
                     title="Click to mark as read"
                 <?php else: ?>
                     title="Already read"
                 <?php endif; ?>>
                <div class="notif-icon" style="background: <?= htmlspecialchars($n['color'] ?: $accent_light) ?>;">
                    <i class="fas <?= htmlspecialchars($n['icon'] ?: 'fa-bell') ?>"></i>
                </div>

                <div class="notif-body">
                    <div class="notif-title">
                        <?php if ($isUnread): ?><span class="dot"></span><?php endif; ?>
                        <?= htmlspecialchars($title) ?>
                    </div>
                    <div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="notif-time"><i class="fas fa-clock"></i> <?= htmlspecialchars($time) ?></div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

</main>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}

// ✅ Settings submenu toggle
function toggleSettingsSubmenu(e){
    if(e) e.preventDefault();
    document.getElementById('settingsMenuItem').classList.toggle('open');
}

/* Mark single notification as read via AJAX */
function markAsRead(el) {
    if (!el.classList.contains('unread')) return;
    const id = parseInt(el.dataset.id, 10);
    if (!id) return;

    el.classList.remove('unread');
    el.removeAttribute('onclick');
    el.setAttribute('title', 'Already read');

    const dot = el.querySelector('.notif-title .dot');
    if (dot) dot.remove();

    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            el.classList.add('unread');
            el.setAttribute('onclick', 'markAsRead(this)');
            el.setAttribute('title', 'Click to mark as read');
        }
    })
    .catch(() => {
        el.classList.add('unread');
        el.setAttribute('onclick', 'markAsRead(this)');
        el.setAttribute('title', 'Click to mark as read');
    });
}
</script>

</body>
</html>