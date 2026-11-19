<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: login.php'); exit; }
require_once 'config.php';

$user_id=$_SESSION['user_id']??0;$user_name=$_SESSION['username']??'Admin';$full_name=$user_name;$user_role='Admin';$profile_picture_url='';
try{$s=$pdo->prepare("SELECT full_name,role,profile_picture FROM users WHERE id=?");$s->execute([$user_id]);$u=$s->fetch(PDO::FETCH_ASSOC);if($u){$full_name=$u['full_name']??$user_name;$user_role=$u['role']??'Admin';if(!empty($u['profile_picture'])&&file_exists(__DIR__.'/uploads/profiles/'.$u['profile_picture']))$profile_picture_url='uploads/profiles/'.$u['profile_picture'];}}catch(PDOException $e){}

try { $total_bills = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status IN ('Pending','Processing','Paid')")->fetchColumn(); } catch(PDOException $e){ $total_bills = 0; }
try { $correct_classifications = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE predicted_category IS NOT NULL AND actual_category IS NOT NULL AND predicted_category=actual_category")->fetchColumn(); } catch(PDOException $e){ $correct_classifications = 0; }
try { $total_evaluated = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE predicted_category IS NOT NULL AND actual_category IS NOT NULL")->fetchColumn(); } catch(PDOException $e){ $total_evaluated = 0; }
$accuracy_percent = $total_evaluated > 0 ? round(($correct_classifications / $total_evaluated) * 100, 1) : 0;

$consistency_percent = 0;
try {
    $td = (int)$pdo->query("SELECT COUNT(DISTINCT service_description) FROM billing_records WHERE predicted_category IS NOT NULL")->fetchColumn();
    $inc = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT service_description FROM billing_records WHERE predicted_category IS NOT NULL GROUP BY service_description HAVING COUNT(DISTINCT predicted_category) > 1) AS t")->fetchColumn();
    if ($td > 0) $consistency_percent = round((($td - $inc) / $td) * 100, 1);
} catch(PDOException $e){}

try {
    $stmt = $pdo->query("SELECT COALESCE(SUM(charge_amount),0),COALESCE(SUM(insurance_coverage),0),COALESCE(SUM(discount),0),COALESCE(SUM(total_amount),0) FROM billing_records WHERE status IN ('Pending','Processing','Paid')");
    $bs = $stmt->fetch(PDO::FETCH_NUM);
    $total_charges = $bs[0] ?? 0; $total_insurance = $bs[1] ?? 0; $total_discount = $bs[2] ?? 0; $total_payable = $bs[3] ?? 0;
} catch(PDOException $e){ $total_charges = $total_insurance = $total_discount = $total_payable = 0; }

try { $avg_processing_ms = round((float)$pdo->query("SELECT COALESCE(AVG(processing_time_ms),0) FROM billing_records WHERE processing_time_ms IS NOT NULL AND status IN ('Pending','Processing','Paid')")->fetchColumn(), 1); } catch(PDOException $e){ $avg_processing_ms = 0; }

function getMetric($pdo,$n,$d=0){try{$s=$pdo->prepare("SELECT AVG(score) FROM survey_results WHERE metric=?");$s->execute([$n]);$v=$s->fetchColumn();return ($v!==false&&$v!==null)?round($v,2):$d;}catch(PDOException $e){return $d;}}
$readability_score = getMetric($pdo,'readability',0);
$transparency_score = getMetric($pdo,'transparency',0);
$understanding_score = getMetric($pdo,'understanding',0);
$sample_warning = ($total_bills < 100);
$targets = ['accuracy'=>90,'consistency'=>90,'readability'=>4.5,'transparency'=>90,'understanding'=>85];
$read_percent = ($readability_score / 5) * 100;
function checkTarget($a,$t){return $a >= $t;}
$acc_pass = checkTarget($accuracy_percent,$targets['accuracy']);
$cons_pass = checkTarget($consistency_percent,$targets['consistency']);
$read_pass = checkTarget($readability_score,$targets['readability']);
$trans_pass = checkTarget($transparency_score,$targets['transparency']);
$under_pass = checkTarget($understanding_score,$targets['understanding']);
function getRating($score,$type='percent'){
    if($type=='percent'){if($score>=90)return ['Excellent','#28a745'];if($score>=75)return ['Very Good','#e8a020'];if($score>=60)return ['Good','#ffc107'];if($score>=40)return ['Fair','#fd7e14'];return ['Poor','#dc3545'];}
    if($score>=4.5)return ['Excellent','#28a745'];if($score>=3.5)return ['Very Good','#e8a020'];if($score>=2.5)return ['Good','#ffc107'];return ['Needs Improvement','#dc3545'];
}
$am = [];
if ($accuracy_percent > 0) $am[] = $accuracy_percent;
if ($consistency_percent > 0) $am[] = $consistency_percent;
if ($readability_score > 0) $am[] = $read_percent;
if ($transparency_score > 0) $am[] = $transparency_score;
if ($understanding_score > 0) $am[] = $understanding_score;
$avg = count($am) > 0 ? round(array_sum($am) / count($am), 1) : 0;
$overall_rating = getRating($avg,'percent');
$all_pass = $acc_pass && $cons_pass && $read_pass && $trans_pass && $under_pass;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ===== BODY: soft teal mesh gradient background (sama seperti login.php) ===== */
body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex;min-height:100vh;
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

/* ============================================ */
/* =====  SIDEBAR (TEAL THEME, semi-transparent)  =============== */
/* ============================================ */
.sidebar{width:270px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .3s ease;overflow-y:auto;border-right:1px solid rgba(226,232,240,.8)}

/* Exact Sidebar Brand Match */
.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:#0f766e;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}

.sidebar-divider{height:1px;background:#f1f5f9;margin:0 20px 25px 20px}
.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f0fdfa;color:#0f766e}
.sidebar-menu li a:hover i{color:#0f766e}
.sidebar-menu li a.active{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-menu li.has-submenu>a .submenu-arrow{margin-left:auto;font-size:11px;width:auto;transition:transform .3s;color:#94a3b8}
.sidebar-menu li.has-submenu.open>a .submenu-arrow{transform:rotate(180deg)}
.sidebar-menu li.has-submenu.open>a{background:#f0fdfa;color:#0f766e}
.sidebar-menu li.has-submenu.open>a i{color:#0f766e}
.submenu{list-style:none;max-height:0;overflow:hidden;transition:max-height .35s;padding:0;margin:4px 0}
.sidebar-menu li.has-submenu.open .submenu{max-height:220px}
.submenu li{margin-bottom:2px}
.submenu li a{padding:10px 16px 10px 52px;font-size:13px;color:#64748b;background:#f8fafc;border-radius:8px;display:flex;align-items:center;gap:10px}
.submenu li a i{width:16px;font-size:13px}
.submenu li a:hover{background:#f0fdfa;color:#0f766e}
.submenu li a.active{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
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

/* USER CHIP */
.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px;transition:all .25s ease}
.user-chip:hover{box-shadow:0 4px 12px rgba(15,118,110,.15);transform:translateY(-1px)}
.user-chip .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;letter-spacing:.5px}
.user-chip .avatar-img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #ccfbf1;flex-shrink:0}
.user-chip .info .name{font-size:13.5px;font-weight:700;color:#0f172a;line-height:1.2;white-space:nowrap}
.user-chip .info .role{font-size:10.5px;color:#0f766e;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;white-space:nowrap}

.sample-warning{background:#fffbeb;color:#92400e;padding:14px 20px;border-radius:10px;margin-bottom:20px;border-left:4px solid #f59e0b;display:flex;align-items:center;gap:12px;font-size:13px}
.tabs-container{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:6px;margin-bottom:22px;display:flex;gap:4px;overflow-x:auto;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.tab-btn{padding:11px 20px;border:none;background:transparent;border-radius:8px;font-size:13.5px;font-weight:600;color:#64748b;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:8px;font-family:inherit;transition:all .2s}
.tab-btn:hover{background:#f0fdfa;color:#0f766e}
.tab-btn.active{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.35)}
.tab-content{display:none}.tab-content.active{display:block}
.section-header{font-size:15px;font-weight:700;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:10px}
.section-header i{color:#0f766e;font-size:16px}
.kpi-sections{display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-bottom:22px}
.kpi-block{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.kpi-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.kpi-card{background:#f0fdfa;border:1px solid #ccfbf1;border-radius:10px;padding:16px 18px;transition:all .25s}
.kpi-card:hover{background:#fff;border-color:#0f766e;transform:translateY(-2px)}
.kpi-label{font-size:12px;font-weight:600;color:#64748b;margin-bottom:8px;display:block}
.kpi-value{font-size:26px;font-weight:700;color:#0f172a;line-height:1.1;margin-bottom:6px}
.kpi-target{font-size:11.5px;color:#64748b;font-weight:500;display:flex;align-items:center;gap:5px}
.kpi-card.pass .kpi-target{color:#1e7e34}
.kpi-card.fail .kpi-target{color:#b91c1c}
.overall-card-side{background:linear-gradient(135deg,#f0fdfa,#ccfbf1);border:1.5px solid #99f6e4;border-radius:12px;padding:32px 28px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.04);margin-bottom:22px}
.overall-card-side .overall-icon{width:75px;height:75px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:34px;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:18px}
.overall-card-side .overall-icon.pass{color:#10b981}.overall-card-side .overall-icon.fail{color:#dc3545}
.overall-card-side .overall-title{font-size:13.5px;font-weight:600;color:#475569;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px}
.overall-card-side .overall-verdict{font-size:32px;font-weight:800;line-height:1.1}
.overall-card-side .overall-verdict.pass{color:#0f766e}.overall-card-side .overall-verdict.fail{color:#dc3545}
.table-card{background:#fff;padding:22px 25px;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.04);margin-bottom:22px;border:1px solid #e2e8f0}
.table-card h3{font-size:15px;font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:10px}
.table-card h3 i{color:#0f766e}
.table-card .subtitle{font-size:12.5px;color:#64748b;margin-bottom:18px}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
table thead th{text-align:left;padding:12px 14px;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #eef2f7;background:#f8fafc}
table tbody td{padding:14px;border-bottom:1px solid #f5f6f8;color:#334155;font-size:13px}
table tbody tr:last-child td{border-bottom:none}
table tbody tr:hover{background:#f0fdfa}
.comparative-label{font-weight:600;color:#0f172a}
.badge-success{background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;font-weight:600}
.badge-warning{background:#f8d7da;color:#721c24;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;font-weight:600}
.billing-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:22px}
.billing-card{background:#fff;border-radius:12px;padding:20px 22px;box-shadow:0 1px 4px rgba(0,0,0,.04);border-left:4px solid #0f766e;border:1px solid #e2e8f0;border-left-width:4px}
.billing-card:nth-child(2){border-left-color:#14b8a6}.billing-card:nth-child(3){border-left-color:#2dd4bf}.billing-card:nth-child(4){border-left-color:#5eead4}.billing-card:nth-child(5){border-left-color:#99f6e4}
.billing-card .bc-label{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.billing-card .bc-value{font-size:22px;font-weight:700;color:#0f172a}
.understanding-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:22px}
.understanding-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,.04);text-align:center;border:1px solid #e2e8f0}
.understanding-card .uc-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin:0 auto 14px auto}
.understanding-card .uc-label{font-size:13px;font-weight:600;color:#64748b;margin-bottom:8px}
.understanding-card .uc-value{font-size:28px;font-weight:800;color:#0f172a;margin-bottom:10px}
.understanding-card .uc-desc{font-size:12.5px;color:#64748b;line-height:1.6}

/* BURGER */
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:1200px){.kpi-sections{grid-template-columns:1fr}.billing-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:992px){.understanding-grid{grid-template-columns:1fr}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .top-bar{flex-direction:column;align-items:flex-start}
  .kpi-grid-2{grid-template-columns:1fr}
  .billing-grid{grid-template-columns:1fr}
  .user-chip .info{display:none}
  .user-chip{padding:6px}
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
                <div class="brand-sub">ADMINISTRATOR</div>
            </div>
        </div>
    </div>
    <div class="sidebar-divider"></div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <li><a href="administrator_monitoring.php"><i class="fas fa-shield-halved"></i> <span>Monitoring</span></a></li>
        <li><a href="explanations.php"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
        <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li class="has-submenu" id="settingsMenuItem">
            <a href="javascript:void(0)" onclick="toggleSettingsSubmenu(event)"><i class="fas fa-cog"></i><span>Settings</span><i class="fas fa-chevron-down submenu-arrow"></i></a>
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
        <h1>Reports</h1>
        <div class="page-sub">System performance, billing analysis, and algorithm evaluation.</div>
    </div>
    <div class="top-bar-actions">
        <div class="user-chip">
            <?php if (!empty($profile_picture_url)): ?>
                <img src="<?= htmlspecialchars($profile_picture_url) ?>?v=<?= time() ?>" alt="Profile" class="avatar-img">
            <?php else: ?>
                <div class="avatar"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
            <?php endif; ?>
            <div class="info">
                <div class="name"><?= htmlspecialchars($full_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($sample_warning): ?>
    <div class="sample-warning"><i class="fas fa-exclamation-triangle"></i><div><strong>Small Sample Size:</strong> Only <?= $total_bills ?> billing records available.</div></div>
<?php endif; ?>

<div class="tabs-container">
    <button class="tab-btn active" data-tab="performance"><i class="fas fa-chart-line"></i> Performance Metrics</button>
    <button class="tab-btn" data-tab="billing"><i class="fas fa-file-invoice-dollar"></i> Billing Summary</button>
    <button class="tab-btn" data-tab="proposed"><i class="fas fa-microchip"></i> Proposed System</button>
    <button class="tab-btn" data-tab="understanding"><i class="fas fa-user-md"></i> Patient Understanding</button>
</div>

<div class="tab-content active" id="tab-performance">
    <div class="kpi-sections">
        <div class="kpi-block">
            <div class="section-header"><i class="fas fa-microchip"></i> Algorithm Performance</div>
            <div class="kpi-grid-2">
                <div class="kpi-card <?= $acc_pass ? 'pass' : 'fail' ?>"><span class="kpi-label">Accuracy</span><div class="kpi-value"><?= $accuracy_percent ?>%</div><div class="kpi-target"><i class="fas <?= $acc_pass ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> Target: ≥ <?= $targets['accuracy'] ?>%</div></div>
                <div class="kpi-card <?= $cons_pass ? 'pass' : 'fail' ?>"><span class="kpi-label">Consistency</span><div class="kpi-value"><?= $consistency_percent ?>%</div><div class="kpi-target"><i class="fas <?= $cons_pass ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> Target: ≥ <?= $targets['consistency'] ?>%</div></div>
                <div class="kpi-card <?= $read_pass ? 'pass' : 'fail' ?>"><span class="kpi-label">Readability</span><div class="kpi-value"><?= $readability_score ?>/5.0</div><div class="kpi-target"><i class="fas <?= $read_pass ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> Target: ≥ <?= $targets['readability'] ?>/5.0</div></div>
                <div class="kpi-card"><span class="kpi-label">Avg Processing Time</span><div class="kpi-value"><?= $avg_processing_ms ?> ms</div><div class="kpi-target"><i class="fas fa-clock" style="color:#0f766e;"></i> Lower is better</div></div>
            </div>
        </div>
        <div class="kpi-block">
            <div class="section-header"><i class="fas fa-heart-pulse"></i> Patient-Centered Evaluation</div>
            <div class="kpi-grid-2">
                <div class="kpi-card <?= $trans_pass ? 'pass' : 'fail' ?>"><span class="kpi-label">Transparency</span><div class="kpi-value"><?= $transparency_score ?>%</div><div class="kpi-target"><i class="fas <?= $trans_pass ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> Target: ≥ <?= $targets['transparency'] ?>%</div></div>
                <div class="kpi-card <?= $under_pass ? 'pass' : 'fail' ?>"><span class="kpi-label">Patient Understanding</span><div class="kpi-value"><?= $understanding_score ?>%</div><div class="kpi-target"><i class="fas <?= $under_pass ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> Target: ≥ <?= $targets['understanding'] ?>%</div></div>
            </div>
        </div>
    </div>
    <div class="overall-card-side">
        <div class="overall-icon <?= $all_pass ? 'pass' : 'fail' ?>"><i class="fas <?= $all_pass ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i></div>
        <div class="overall-title">Overall System Performance</div>
        <div class="overall-verdict <?= $all_pass ? 'pass' : 'fail' ?>"><?= $overall_rating[0] ?></div>
    </div>
</div>

<div class="tab-content" id="tab-billing">
    <div class="billing-grid">
        <div class="billing-card"><div class="bc-label">Total Records</div><div class="bc-value"><?= number_format($total_bills) ?></div></div>
        <div class="billing-card"><div class="bc-label">Total Charges</div><div class="bc-value">₱ <?= number_format($total_charges, 2) ?></div></div>
        <div class="billing-card"><div class="bc-label">Insurance Deductions</div><div class="bc-value">₱ <?= number_format($total_insurance, 2) ?></div></div>
        <div class="billing-card"><div class="bc-label">Patient Responsibility</div><div class="bc-value">₱ <?= number_format($total_payable, 2) ?></div></div>
        <div class="billing-card"><div class="bc-label">Avg Processing</div><div class="bc-value"><?= $avg_processing_ms ?> ms</div></div>
    </div>
    <div class="table-card">
        <h3><i class="fas fa-chart-pie"></i> Billing Breakdown</h3>
        <div class="subtitle">Summary of billing amounts and deductions</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Category</th><th>Amount</th><th>Percentage</th></tr></thead>
                <tbody>
                    <tr><td class="comparative-label">Original Charges</td><td><strong>₱ <?= number_format($total_charges, 2) ?></strong></td><td>100%</td></tr>
                    <tr><td class="comparative-label">Insurance Coverage</td><td>₱ <?= number_format($total_insurance, 2) ?></td><td><?= $total_charges > 0 ? number_format(($total_insurance / $total_charges) * 100, 1) : 0 ?>%</td></tr>
                    <tr><td class="comparative-label">Discounts Applied</td><td>₱ <?= number_format($total_discount, 2) ?></td><td><?= $total_charges > 0 ? number_format(($total_discount / $total_charges) * 100, 1) : 0 ?>%</td></tr>
                    <tr><td class="comparative-label">Final Payable</td><td><strong>₱ <?= number_format($total_payable, 2) ?></strong></td><td><?= $total_charges > 0 ? number_format(($total_payable / $total_charges) * 100, 1) : 0 ?>%</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-content" id="tab-proposed">
    <div class="table-card">
        <h3><i class="fas fa-microchip"></i> Proposed System Performance</h3>
        <div class="subtitle">Rule-Based Decision and Semantic Analysis Algorithm Evaluation</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Metric</th><th>Score</th><th>Target</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td class="comparative-label">Accuracy</td><td><strong><?= $accuracy_percent ?>%</strong></td><td>≥ <?= $targets['accuracy'] ?>%</td><td><?= $acc_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Consistency</td><td><strong><?= $consistency_percent ?>%</strong></td><td>≥ <?= $targets['consistency'] ?>%</td><td><?= $cons_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Readability</td><td><strong><?= $readability_score ?>/5.0</strong></td><td>≥ <?= $targets['readability'] ?>/5.0</td><td><?= $read_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Transparency</td><td><strong><?= $transparency_score ?>%</strong></td><td>≥ <?= $targets['transparency'] ?>%</td><td><?= $trans_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Patient Understanding</td><td><strong><?= $understanding_score ?>%</strong></td><td>≥ <?= $targets['understanding'] ?>%</td><td><?= $under_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Avg Processing Time</td><td><strong><?= $avg_processing_ms ?> ms</strong></td><td>Lower is better</td><td><span class="badge-success"><i class="fas fa-check-circle"></i> Measured</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-content" id="tab-understanding">
    <div class="understanding-grid">
        <div class="understanding-card"><div class="uc-icon" style="background:#0f766e;"><i class="fas fa-book"></i></div><div class="uc-label">Readability Score</div><div class="uc-value"><?= $readability_score ?>/5.0</div><div class="uc-desc">Measures how easily patients can read and understand billing information.</div></div>
        <div class="understanding-card"><div class="uc-icon" style="background:#14b8a6;"><i class="fas fa-eye"></i></div><div class="uc-label">Transparency Score</div><div class="uc-value"><?= $transparency_score ?>%</div><div class="uc-desc">Indicates how clearly billing processes and charges are explained to patients.</div></div>
        <div class="understanding-card"><div class="uc-icon" style="background:#2dd4bf;"><i class="fas fa-user-md"></i></div><div class="uc-label">Patient Understanding</div><div class="uc-value"><?= $understanding_score ?>%</div><div class="uc-desc">Overall comprehension rate of patients regarding their hospital bills.</div></div>
    </div>
    <div class="table-card">
        <h3><i class="fas fa-clipboard-check"></i> Patient-Centered Metrics</h3>
        <div class="subtitle">Evaluation of patient-facing communication quality</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Metric</th><th>Score</th><th>Target</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td class="comparative-label">Readability</td><td><?= $readability_score ?>/5.0</td><td>≥ <?= $targets['readability'] ?>/5.0</td><td><?= $read_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Transparency</td><td><?= $transparency_score ?>%</td><td>≥ <?= $targets['transparency'] ?>%</td><td><?= $trans_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                    <tr><td class="comparative-label">Patient Understanding</td><td><?= $understanding_score ?>%</td><td>≥ <?= $targets['understanding'] ?>%</td><td><?= $under_pass ? '<span class="badge-success"><i class="fas fa-check-circle"></i> Passed</span>' : '<span class="badge-warning"><i class="fas fa-exclamation-circle"></i> Failed</span>' ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
document.addEventListener('DOMContentLoaded',function(){const p=window.location.pathname.toLowerCase();if(p.includes('profile.php')||p.includes('change_password.php'))document.getElementById('settingsMenuItem').classList.add('open');});

document.addEventListener('DOMContentLoaded',function(){
    const tabs=document.querySelectorAll('.tab-btn'),contents=document.querySelectorAll('.tab-content');
    tabs.forEach(b=>b.addEventListener('click',function(){const t=this.getAttribute('data-tab');tabs.forEach(x=>x.classList.remove('active'));contents.forEach(x=>x.classList.remove('active'));this.classList.add('active');document.getElementById('tab-'+t).classList.add('active');}));
});
</script>
</body>
</html>