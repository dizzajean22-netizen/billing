<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'User';
$full_name = $_SESSION['full_name'] ?? $user_name;
$user_role = $_SESSION['role'] ?? 'User';

$r = strtolower(trim($user_role));
$is_billing = in_array($r, ['billing','cashier'], true)
           || strpos($r,'billing') !== false
           || strpos($r,'cashier') !== false;
if (!$is_billing) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_brand = 'Hospital Billing';
$sidebar_sub   = 'BILLING PORTAL';
$accent        = '#0f766e';
$accent_light  = '#14b8a6';
$dash_url      = 'billing_dashboard.php';

try { $all_bills = (int)$pdo->query("SELECT COUNT(*) FROM billing_records")->fetchColumn(); } catch (PDOException $e) { $all_bills = 0; }
try { $pending_queue = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status = 'Pending'")->fetchColumn(); } catch (PDOException $e) { $pending_queue = 0; }
try { $processing_queue = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status = 'Processing'")->fetchColumn(); } catch (PDOException $e) { $processing_queue = 0; }
try { $paid_today = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status = 'Paid' AND DATE(date) = CURDATE()")->fetchColumn(); } catch (PDOException $e) { $paid_today = 0; }
try { $pending_amount = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM billing_records WHERE status = 'Pending'")->fetchColumn(); } catch (PDOException $e) { $pending_amount = 0; }

$queue = [];
try {
    $queue = $pdo->query("
        SELECT * FROM billing_records
        WHERE status IN ('Pending','Processing')
        ORDER BY date ASC, id ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $queue = []; }

$recent_paid = [];
try {
    $recent_paid = $pdo->query("
        SELECT * FROM billing_records
        WHERE status = 'Paid'
        ORDER BY id DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $recent_paid = []; }

$initials = strtoupper(substr($full_name, 0, 2));

/* Notifications (used for toast) */
$unread_count = 0; $notifications = [];
try { $unread_count = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn(); } catch (PDOException $e) {}
try { $notifications = $pdo->query("SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago FROM notifications ORDER BY is_read ASC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
if (empty($notifications)) { $notifications = [['id'=>0,'icon'=>'fa-info-circle','color'=>$accent_light,'message'=>'Welcome to the Patient-Centered Hospital Billing System.','is_read'=>0,'created_at'=>date('Y-m-d H:i:s'),'seconds_ago'=>0]]; $unread_count = 1; }
function timeAgo($dt,$sec=null){$s=$sec!==null?(int)$sec:(time()-strtotime($dt));if($s<0)$s=0;if($s<60)return 'Just now';if($s<3600)return floor($s/60).' min ago';if($s<86400)return floor($s/3600).' hr ago';$ts=strtotime($dt);$td=date('Y-m-d');$yd=date('Y-m-d',strtotime('-1 day'));$ds=date('Y-m-d',$ts);if($ds===$td)return 'Today, '.date('g:i A',$ts);if($ds===$yd)return 'Yesterday, '.date('g:i A',$ts);return date('M d, Y, g:i A',$ts);}
function notifTitle($m){$msg=strtolower($m??'');if(strpos($msg,'welcome')!==false)return 'System Welcome';if(strpos($msg,'marked as')!==false)return 'Payment Update';if(strpos($msg,'new billing')!==false||strpos($msg,'record added')!==false)return 'New Billing Record';if(strpos($msg,'updated')!==false)return 'Record Updated';if(strpos($msg,'deleted')!==false)return 'Record Deleted';if(strpos($msg,'user')!==false)return 'User Activity';return 'Notification';}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing Dashboard · Payment Queue</title>
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

/* Decorative soft blobs (subtle & professional) */
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

.sidebar{width:270px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid rgba(226,232,240,.8)}

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

.main{margin-left:270px;flex:1;padding:0 0 40px 0;min-height:100vh;position:relative;z-index:1}
.top-bar{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);padding:18px 32px;border-bottom:1px solid rgba(226,232,240,.8);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:25px}
.top-bar h1{font-size:22px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:12px}
.top-bar h1 .h-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 6px 16px rgba(0,0,0,.15)}
.top-bar .sub{font-size:12.5px;color:#64748b;margin-top:4px;margin-left:52px}
.top-bar-actions{display:flex;align-items:center;gap:15px}

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:600;text-transform:uppercase;margin-top:2px}

.content{padding:0 32px}

.welcome{background:linear-gradient(135deg,<?= $accent ?> 0%,<?= $accent_light ?> 100%);color:#fff;padding:28px 32px;border-radius:16px;margin-bottom:22px;position:relative;overflow:hidden;box-shadow:0 8px 24px rgba(15,118,110,.2)}
.welcome::after{content:'';position:absolute;top:-40%;right:-5%;width:340px;height:340px;background:rgba(255,255,255,.07);border-radius:50%}
.welcome h2{font-size:22px;font-weight:800;margin-bottom:6px;position:relative;z-index:1}
.welcome p{font-size:13.5px;color:rgba(255,255,255,.9);position:relative;z-index:1;max-width:620px;line-height:1.55}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{background:#fff;padding:22px 24px;border-radius:14px;border:1px solid #e2e8f0;transition:all .25s ease;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(15,118,110,.08);border-color:#99f6e4}
.stat-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:<?= $accent_light ?>}
.stat-label{font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.stat-value{font-size:28px;font-weight:800;color:#0f172a;line-height:1.1}
.stat-value.money{font-size:22px}
.stat-trend{font-size:11.5px;color:<?= $accent ?>;font-weight:600;margin-top:8px;display:flex;align-items:center;gap:4px}
.stat-trend i{color:<?= $accent ?>}

.queue-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:22px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.queue-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.queue-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.queue-header h3 i{color:<?= $accent ?>;font-size:15px}
.queue-header .queue-count{background:<?= $accent ?>;color:#fff;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;margin-left:6px}
.queue-header a{font-size:12.5px;font-weight:600;color:<?= $accent ?>;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.queue-header a:hover{text-decoration:underline}

table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:12px 22px;background:#f8fafc;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9}
tbody td{padding:14px 22px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f8fafc}
.patient-name,.amount{font-weight:600;color:#0f172a}
.invoice-num{font-family:'Courier New',monospace;font-weight:700;color:<?= $accent ?>;font-size:12px}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:600}
.status-badge.Paid{background:#d1fae5;color:#065f46}
.status-badge.Pending{background:#fef3c7;color:#92400e}
.status-badge.Processing{background:#dbeafe;color:#1e40af}
.status-badge i{font-size:9px}

.btn-process{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:<?= $accent ?>;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;white-space:nowrap;cursor:pointer}
.btn-process:hover{filter:brightness(1.1);transform:translateY(-1px);box-shadow:0 4px 10px rgba(0,0,0,.2);color:#fff}
.btn-process.paid{background:#10b981}

.empty-queue{text-align:center;padding:50px 25px;color:#94a3b8}
.empty-queue i{font-size:44px;color:#cbd5e1;display:block;margin-bottom:14px}
.empty-queue strong{color:#475569;display:block;margin-bottom:6px;font-size:15px}
.empty-queue .celebrate{font-size:13px;color:<?= $accent ?>;font-weight:600}

/* ============================================ */
/* =====  TOAST NOTIFICATIONS  ================ */
/* ============================================ */
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:380px;width:calc(100% - 48px);pointer-events:none}
.toast{background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(15,23,42,.18);border:1px solid #e2e8f0;padding:16px 18px;display:flex;gap:14px;align-items:flex-start;pointer-events:auto;animation:toastIn .4s cubic-bezier(.2,.9,.3,1.1);position:relative;overflow:hidden}
.toast::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:<?= $accent_light ?>}
.toast.closing{animation:toastOut .3s ease forwards}
.toast .toast-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:16px;background:<?= $accent_light ?>}
.toast .toast-body{flex:1;min-width:0}
.toast .toast-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.toast .toast-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:6px;word-wrap:break-word}
.toast .toast-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.toast .toast-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:4px;border-radius:6px;transition:all .2s;flex-shrink:0}
.toast .toast-close:hover{color:#dc2626;background:#fef2f2}
@keyframes toastIn{from{opacity:0;transform:translateX(120%)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(120%)}}

@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}.main{margin-left:0}
  .top-bar,.content{padding-left:18px;padding-right:18px}
  .stats-grid{grid-template-columns:1fr}
  .user-chip .info{display:none}.user-chip{padding:5px}
  .toast-container{top:16px;right:16px;left:16px;width:auto;max-width:none}
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
                <div class="brand-name"><?= $sidebar_brand ?></div>
                <div class="brand-sub"><?= $sidebar_sub ?></div>
            </div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="billing_dashboard.php" class="active"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
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
        <h1><span class="h-icon"><i class="fas fa-gauge-high"></i></span> Billing Dashboard</h1>
        <div class="sub">Welcome back, <strong><?= htmlspecialchars($full_name) ?></strong>! Process patient payments here.</div>
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

<div class="welcome">
    <h2>Payment Processing Center</h2>
    <p>All bills created by the front desk appear in your <strong>Payment Queue</strong> below. Click "Process" to record the payment.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Pending Queue</div>
        <div class="stat-value"><?= number_format($pending_queue) ?></div>
        <div class="stat-trend"><i class="fas fa-exclamation-circle"></i> Awaiting payment</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Processing</div>
        <div class="stat-value"><?= number_format($processing_queue) ?></div>
        <div class="stat-trend"><i class="fas fa-rotate"></i> Being handled</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Paid Today</div>
        <div class="stat-value"><?= number_format($paid_today) ?></div>
        <div class="stat-trend"><i class="fas fa-check"></i> Completed today</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending Amount</div>
        <div class="stat-value money">₱ <?= number_format($pending_amount, 2) ?></div>
        <div class="stat-trend"><i class="fas fa-peso-sign"></i> Total to collect</div>
    </div>
</div>

<div class="queue-card">
    <div class="queue-header">
        <h3><i class="fas fa-list-check"></i> Payment Queue <span class="queue-count"><?= count($queue) ?></span></h3>
        <a href="billing_records.php?status=Pending">View all pending <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if (empty($queue)): ?>
        <div class="empty-queue">
            <i class="fas fa-circle-check"></i>
            <strong>All caught up!</strong>
            <div class="celebrate">No pending payments in the queue right now.</div>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th style="text-align:right;">Amount</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($queue as $q): ?>
                    <tr>
                        <td><span class="invoice-num">#<?= str_pad($q['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                        <td><?= date('M d, Y', strtotime($q['date'])) ?></td>
                        <td>
                            <div class="patient-name"><?= htmlspecialchars($q['patient_name']) ?></div>
                            <?php if (!empty($q['patient_id'])): ?>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars($q['patient_id']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($q['service_description']) ?></td>
                        <td style="text-align:right;"><span class="amount">₱ <?= number_format($q['total_amount'], 2) ?></span></td>
                        <td style="text-align:center;">
                            <span class="status-badge <?= htmlspecialchars($q['status']) ?>">
                                <i class="fas fa-circle"></i> <?= htmlspecialchars($q['status']) ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="process_payment.php?id=<?= (int)$q['id'] ?>" class="btn-process">
                                <i class="fas fa-money-bill-wave"></i> Process
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($recent_paid)): ?>
<div class="queue-card">
    <div class="queue-header">
        <h3 style="color:#0f172a;"><i class="fas fa-circle-check" style="color:<?= $accent ?>;"></i> Recently Paid</h3>
        <a href="billing_records.php?status=Paid">View all <i class="fas fa-arrow-right"></i></a>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Service</th>
                    <th style="text-align:right;">Amount</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent_paid as $p): ?>
                <tr>
                    <td><span class="invoice-num">#<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                    <td><?= date('M d, Y', strtotime($p['date'])) ?></td>
                    <td><div class="patient-name"><?= htmlspecialchars($p['patient_name']) ?></div></td>
                    <td><?= htmlspecialchars($p['service_description']) ?></td>
                    <td style="text-align:right;"><span class="amount">₱ <?= number_format($p['total_amount'], 2) ?></span></td>
                    <td style="text-align:center;">
                        <span class="status-badge Paid">
                            <i class="fas fa-circle"></i> Paid
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>
</main>

<!-- ===== TOAST CONTAINER ===== -->
<div class="toast-container" id="toastContainer"></div>

<script>
/* ===== SIDEBAR ===== */
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}

/* ===== TOAST ===== */
function showToast(title, message, icon, color, duration){
    duration = duration || 6000;
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
        <div class="toast-icon" style="background:${color || '<?= $accent_light ?>'};">
            <i class="fas ${icon || 'fa-bell'}"></i>
        </div>
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

/* ===== AUTO-SHOW LATEST UNREAD AS TOAST ===== */
(function autoToast(){
    <?php
    $toast_notif = null;
    foreach ($notifications as $n) {
        if (empty($n['is_read'])) { $toast_notif = $n; break; }
    }
    if (!$toast_notif && !empty($notifications)) $toast_notif = $notifications[0];
    ?>
    <?php if ($toast_notif): ?>
    setTimeout(() => showToast(
        <?= json_encode(notifTitle($toast_notif['message'])) ?>,
        <?= json_encode($toast_notif['message']) ?>,
        <?= json_encode($toast_notif['icon'] ?: 'fa-bell') ?>,
        <?= json_encode($toast_notif['color'] ?: $accent_light) ?>,
        6000
    ), 500);
    <?php endif; ?>
})();
</script>
</body>
</html>