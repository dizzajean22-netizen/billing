<?php
date_default_timezone_set('Asia/Manila');
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
require_once 'metrics_helper.php';

try { $pdo->exec("SET time_zone = '+08:00'"); } catch (PDOException $e) {}

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'Admin';
$full_name = $user_name;
$user_role = 'Admin';
$profile_picture_url = '';

try {
    $stmt = $pdo->prepare("SELECT full_name, role, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $full_name = $user['full_name'] ?? $user_name;
        $user_role = $user['role'] ?? 'Admin';
        if (!empty($user['profile_picture'])) {
            $check = __DIR__ . '/uploads/profiles/' . $user['profile_picture'];
            if (file_exists($check)) {
                $profile_picture_url = 'uploads/profiles/' . $user['profile_picture'];
            }
        }
    }
} catch (PDOException $e) {}

try { $total_records = (int) $pdo->query("SELECT COUNT(*) FROM billing_records WHERE status IN ('Pending','Processing','Paid')")->fetchColumn(); } catch (PDOException $e) { $total_records = 0; }
try { $total_charges = (float) $pdo->query("SELECT COALESCE(SUM(charge_amount), 0) FROM billing_records WHERE status IN ('Pending','Processing','Paid')")->fetchColumn(); } catch (PDOException $e) { $total_charges = 0; }
try { $total_insurance_deduction = (float) $pdo->query("SELECT COALESCE(SUM(insurance_coverage), 0) FROM billing_records WHERE status IN ('Pending','Processing','Paid')")->fetchColumn(); } catch (PDOException $e) { $total_insurance_deduction = 0; }
try { $total_revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM billing_records WHERE status IN ('Pending','Processing','Paid')")->fetchColumn(); } catch (PDOException $e) { $total_revenue = 0; }

/* ===== CLASSIFICATION METRICS (Accuracy / Precision / Recall / F1) ===== */
$clf = compute_classification_metrics($pdo);            // strict ground-truth only
// $clf = compute_classification_metrics($pdo, true);   // fallback: use `category` as ground truth if actual_category is empty

$total_evaluated          = $clf['evaluated'];
$correct_classifications  = $clf['correct'];
$accuracy_percent         = round($clf['accuracy']           * 100, 1);
$precision_percent        = round($clf['weighted_precision'] * 100, 1);
$recall_percent           = round($clf['weighted_recall']    * 100, 1);
$f1_percent               = round($clf['weighted_f1']        * 100, 1);
$macro_p_percent          = round($clf['macro_precision']    * 100, 1);
$macro_r_percent          = round($clf['macro_recall']       * 100, 1);
$macro_f1_percent         = round($clf['macro_f1']           * 100, 1);
$micro_p_percent          = round($clf['micro_precision']    * 100, 1);
$micro_r_percent          = round($clf['micro_recall']       * 100, 1);
$micro_f1_percent         = round($clf['micro_f1']           * 100, 1);

function getMetric($pdo, $name, $default = 0) {
    try {
        $stmt = $pdo->prepare("SELECT AVG(score) FROM survey_results WHERE metric = ?");
        $stmt->execute([$name]);
        $v = $stmt->fetchColumn();
        return ($v !== false && $v !== null) ? round($v, 2) : $default;
    } catch (PDOException $e) { return $default; }
}

$readability_score     = getMetric($pdo, 'readability', 0);
$patient_understanding = getMetric($pdo, 'understanding', 0);
$consistency_percent   = getMetric($pdo, 'consistency', 0);
$transparency_score    = getMetric($pdo, 'transparency', 0);

$sample_warning = ($total_records < 100);

$monthly_labels = []; $monthly_charges = []; $monthly_final = [];
try {
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(date, '%b') AS month_label, DATE_FORMAT(date, '%Y-%m') AS month_key,
               SUM(charge_amount) AS total_charges, SUM(total_amount) AS total_final
        FROM billing_records
        WHERE status IN ('Pending','Processing','Paid') AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month_key, month_label ORDER BY month_key ASC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $monthly_labels[]  = $m['month_label'];
        $monthly_charges[] = floatval($m['total_charges']);
        $monthly_final[]   = floatval($m['total_final']);
    }
} catch (PDOException $e) {}
if (empty($monthly_labels)) {
    for ($i = 7; $i >= 0; $i--) {
        $monthly_labels[]  = date('M', strtotime("-$i months"));
        $monthly_charges[] = 0; $monthly_final[] = 0;
    }
}

try {
    $category_data = $pdo->query("SELECT category, COUNT(*) AS count FROM billing_records WHERE status IN ('Pending','Processing','Paid') GROUP BY category ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    $total_category_count = array_sum(array_column($category_data, 'count'));
} catch (PDOException $e) { $category_data = []; $total_category_count = 0; }

$pie_labels = []; $pie_data = [];
$pie_colors = ['#0ea5e9','#14b8a6','#f59e0b','#8b5cf6','#ec4899','#10b981','#f97316','#6366f1'];
foreach ($category_data as $cat) {
    $percent = $total_category_count > 0 ? round(($cat['count'] / $total_category_count) * 100, 1) : 0;
    $pie_labels[] = $cat['category']; $pie_data[] = $percent;
}
if (empty($pie_data)) { $pie_labels = ['No Data']; $pie_data = [100]; $pie_colors = ['#e2e8f0']; }

try { $unread_count = (int) $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn(); } catch (PDOException $e) { $unread_count = 0; }

try {
    $notifications = $pdo->query("
        SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago
        FROM notifications
        ORDER BY is_read ASC, id DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $notifications = []; }

if (empty($notifications)) {
    $notifications = [[
        'id' => 0, 'icon' => 'fa-info-circle', 'color' => '#14b8a6',
        'message' => 'Welcome to the Patient-Centered Hospital Billing System.',
        'link' => '', 'is_read' => 0, 'created_at' => date('Y-m-d H:i:s'), 'seconds_ago' => 0
    ]];
    $unread_count = 1;
}

function timeAgo($datetime, $seconds_ago = null) {
    $sec = $seconds_ago !== null ? (int)$seconds_ago : (time() - strtotime($datetime));
    if ($sec < 0) $sec = 0;
    if ($sec < 60)    return 'Just now';
    if ($sec < 3600)  return floor($sec / 60) . ' min ago';
    if ($sec < 86400) return floor($sec / 3600) . ' hr ago';
    $ts = strtotime($datetime);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $dateStr = date('Y-m-d', $ts);
    if ($dateStr === $today)     return 'Today, ' . date('g:i A', $ts);
    if ($dateStr === $yesterday) return 'Yesterday, ' . date('g:i A', $ts);
    return date('M d, Y, g:i A', $ts);
}

function notifTitle($message) {
    $msg = strtolower($message ?? '');
    if (strpos($msg, 'welcome') !== false) return 'System Welcome';
    if (strpos($msg, 'new billing record') !== false || strpos($msg, 'record added') !== false) return 'New Billing Record Added';
    if (strpos($msg, 'updated') !== false) return 'Billing Record Updated';
    if (strpos($msg, 'deleted') !== false) return 'Billing Record Deleted';
    if (strpos($msg, 'new user') !== false || strpos($msg, 'user added') !== false) return 'New User Added';
    if (strpos($msg, 'user') !== false) return 'User Activity';
    if (strpos($msg, 'alert') !== false) return 'System Alert';
    return 'Notification';
}

function getStatus($score, $type = 'percent') {
    if ($type === 'readability') $score = ($score / 5) * 100;
    if ($score >= 90) return ['label'=>'Excellent', 'color'=>'#15803d', 'bg'=>'#dcfce7'];
    if ($score >= 75) return ['label'=>'Very Good', 'color'=>'#0e7490', 'bg'=>'#cffafe'];
    if ($score >= 60) return ['label'=>'Good', 'color'=>'#a16207', 'bg'=>'#fef9c3'];
    if ($score >= 40) return ['label'=>'Fair', 'color'=>'#c2410c', 'bg'=>'#ffedd5'];
    return ['label'=>'Needs Improvement', 'color'=>'#b91c1c', 'bg'=>'#fee2e2'];
}

$acc_status   = getStatus($accuracy_percent);
$prec_status  = getStatus($precision_percent);
$rec_status   = getStatus($recall_percent);
$f1_status    = getStatus($f1_percent);
$cons_status  = getStatus($consistency_percent);
$read_status  = getStatus($readability_score, 'readability');
$trans_status = getStatus($transparency_score);
$under_status = getStatus($patient_understanding);

$read_percent = ($readability_score / 5) * 100;
$active_metrics = [];
if ($total_evaluated > 0)       $active_metrics[] = $accuracy_percent;
if ($total_evaluated > 0)       $active_metrics[] = $precision_percent;
if ($total_evaluated > 0)       $active_metrics[] = $recall_percent;
if ($total_evaluated > 0)       $active_metrics[] = $f1_percent;
if ($consistency_percent > 0)   $active_metrics[] = $consistency_percent;
if ($readability_score > 0)     $active_metrics[] = $read_percent;
if ($transparency_score > 0)    $active_metrics[] = $transparency_score;
if ($patient_understanding > 0) $active_metrics[] = $patient_understanding;
$avg_score = count($active_metrics) > 0 ? round(array_sum($active_metrics) / count($active_metrics), 1) : 0;
$overall_status = getStatus($avg_score);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

/* ===== BODY: soft teal mesh gradient background (sama seperti login.php) ===== */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  display: flex; min-height: 100vh;
  position: relative;
  overflow-x: hidden;
  background:
    radial-gradient(circle at 15% 20%, rgba(20,184,166,.12) 0%, transparent 45%),
    radial-gradient(circle at 85% 80%, rgba(15,118,110,.14) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20,184,166,.05) 0%, transparent 60%),
    linear-gradient(135deg, #f0fdfa 0%, #e6fbf7 40%, #ecfdf5 100%);
  background-attachment: fixed;
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

.sidebar { width: 270px; background: rgba(255,255,255,.88); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); color: #1e293b; display: flex; flex-direction: column; padding: 25px 0 20px 0; position: fixed; top: 0; left: 0; height: 100vh; z-index: 200; overflow-y: auto; border-right: 1px solid rgba(226,232,240,.8); }
.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:#0f766e;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}
.sidebar-divider { height: 1px; background: #f1f5f9; margin: 0 20px 25px 20px; }
.sidebar-menu { list-style: none; padding: 0 14px; flex: 1; }
.sidebar-menu li { margin-bottom: 4px; }
.sidebar-menu li a { display: flex; align-items: center; gap: 14px; padding: 12px 15px; color: #475569; text-decoration: none; border-radius: 10px; font-size: 13.5px; font-weight: 500; }
.sidebar-menu li a i { width: 20px; font-size: 16px; text-align: center; color: #94a3b8; }
.sidebar-menu li a:hover { background: #f0fdfa; color: #0f766e; }
.sidebar-menu li a:hover i { color: #0f766e; }
.sidebar-menu li a.active { background: #0f766e; color: #fff; box-shadow: 0 4px 12px rgba(15,118,110,.2); }
.sidebar-menu li a.active i { color: #fff; }
.sidebar-menu li.has-submenu > a { position: relative; }
.sidebar-menu li.has-submenu > a .submenu-arrow { margin-left: auto; font-size: 11px; width: auto; transition: transform .3s ease; color: #94a3b8; }
.sidebar-menu li.has-submenu.open > a .submenu-arrow { transform: rotate(180deg); }
.sidebar-menu li.has-submenu.open > a { background: #f0fdfa; color: #0f766e; }
.sidebar-menu li.has-submenu.open > a i { color: #0f766e; }
.submenu { list-style: none; max-height: 0; overflow: hidden; transition: max-height .35s ease; padding: 0; margin: 4px 0; }
.sidebar-menu li.has-submenu.open .submenu { max-height: 220px; }
.submenu li { margin-bottom: 2px; }
.submenu li a { padding: 10px 16px 10px 52px; font-size: 13px; color: #64748b; background: #f8fafc; border-radius: 8px; display: flex; align-items: center; gap: 10px; }
.submenu li a i { width: 16px; font-size: 13px; }
.submenu li a:hover { background: #f0fdfa; color: #0f766e; }
.sidebar-footer { padding: 15px 14px 0 14px; border-top: 1px solid #f1f5f9; margin-top: 15px; }
.sidebar-footer a { display: flex; align-items: center; gap: 14px; padding: 12px 15px; color: #64748b; text-decoration: none; border-radius: 10px; font-size: 13.5px; font-weight: 500; }
.sidebar-footer a i { color: #94a3b8; }
.sidebar-footer a:hover { background: #fef2f2; color: #dc2626; }
.sidebar-footer a:hover i { color: #dc2626; }
.burger-btn { display: none; position: fixed; top: 15px; left: 15px; z-index: 300; width: 44px; height: 44px; background: #0f766e; color: #fff; border: none; border-radius: 10px; font-size: 18px; cursor: pointer; align-items: center; justify-content: center; }
.burger-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 150; }
.burger-overlay.active { display: block; }
.main-content { margin-left: 270px; flex: 1; padding: 25px 35px 40px 35px; min-height: 100vh; position: relative; z-index: 1; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
.top-bar h1 { font-size: 26px; font-weight: 700; color: #0f172a; }
.top-bar p { font-size: 13.5px; color: #64748b; margin-top: 4px; }
.top-bar-actions { display: flex; align-items: center; gap: 15px; }
.user-chip { display: flex; align-items: center; gap: 10px; padding: 6px 16px 6px 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 30px; }
.user-chip .avatar { width: 38px; height: 38px; border-radius: 50%; background: #0f766e; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
.user-chip .avatar-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid #ccfbf1; }
.user-chip .info .name { font-size: 13.5px; font-weight: 700; color: #0f172a; white-space: nowrap; }
.user-chip .info .role { font-size: 10.5px; color: #0f766e; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; white-space: nowrap; }
.welcome-card { background: #0f766e; color: #fff; padding: 30px 35px; border-radius: 16px; margin-bottom: 25px; position: relative; overflow: hidden; }
.welcome-card::after { content: ''; position: absolute; top: -30%; right: -5%; width: 300px; height: 300px; background: rgba(255,255,255,0.06); border-radius: 50%; }
.welcome-card h2 { font-size: 24px; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; }
.welcome-card p { font-size: 14px; color: rgba(255,255,255,0.92); position: relative; z-index: 1; max-width: 700px; line-height: 1.5; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 25px; }
.stat-card { background: #fff; padding: 22px 24px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; border-left: 4px solid #0f766e; }
.stat-card.green  { border-left-color: #10b981; }
.stat-card.orange { border-left-color: #f59e0b; }
.stat-card.blue   { border-left-color: #0ea5e9; }
.stat-label { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
.stat-value { font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1.1; }
.warn-banner { background: #fffbeb; color: #92400e; padding: 14px 20px; border-radius: 12px; margin-bottom: 22px; border-left: 5px solid #f59e0b; font-size: 14px; display: flex; align-items: center; gap: 10px; }
.two-col { display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; margin-bottom: 25px; }
.chart-card { background: #fff; padding: 22px 25px; border-radius: 14px; border: 1px solid #e2e8f0; }
.chart-card h3 { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }
.chart-card h3 i { color: #0f766e; }
.bar-chart-wrapper { position: relative; height: 280px; }
.metrics-section { background: #fff; padding: 22px 25px; border-radius: 14px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
.metrics-section h3 { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.metrics-section h3 i { color: #0f766e; }
.metric-subtitle { font-size: 11.5px; font-weight: 500; color: #94a3b8; margin-left: 8px; letter-spacing: .2px; }
.metrics-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
.metric-card { background: #f0fdfa; border-radius: 12px; padding: 18px 20px; border: 1px solid #ccfbf1; }
.metric-card .metric-name { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 10px; }
.metric-card .metric-score { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1; }
.metric-card .metric-status { font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; display: inline-block; margin-top: 10px; text-transform: uppercase; }
.metrics-subinfo { margin-top: 16px; padding: 14px 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 12.5px; color: #475569; display: flex; flex-wrap: wrap; gap: 18px; }
.metrics-subinfo span strong { color: #0f766e; }
.metrics-empty-note { margin-top: 14px; padding: 12px 18px; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 8px; font-size: 12.5px; color: #92400e; }
.overall-card { background: #0f766e; border-radius: 14px; padding: 24px 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; position: relative; overflow: hidden; }
.overall-card::after { content: ''; position: absolute; top: -30%; right: -5%; width: 260px; height: 260px; background: rgba(255,255,255,0.06); border-radius: 50%; }
.overall-card .overall-label { font-size: 15px; font-weight: 600; color: rgba(255,255,255,0.95); position: relative; z-index: 1; }
.overall-card .overall-score { font-size: 36px; font-weight: 800; color: #fff; position: relative; z-index: 1; }
.overall-card .overall-status { font-size: 14px; font-weight: 700; padding: 8px 22px; border-radius: 30px; background: rgba(255,255,255,0.22); color: #fff; position: relative; z-index: 1; text-transform: uppercase; }
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:380px;width:calc(100% - 48px);pointer-events:none}
.toast{background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(15,23,42,.18);border:1px solid #e2e8f0;padding:16px 18px;display:flex;gap:12px;align-items:flex-start;pointer-events:auto;animation:toastIn .4s;position:relative;overflow:hidden}
.toast::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--toast-accent,#14b8a6)}
.toast.closing{animation:toastOut .3s ease forwards}
.toast .toast-body{flex:1;min-width:0}
.toast .toast-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.toast .toast-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:6px;word-wrap:break-word}
.toast .toast-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.toast .toast-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:4px;border-radius:6px}
@keyframes toastIn{from{opacity:0;transform:translateX(120%)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(120%)}}
.notif-fab{position:fixed;bottom:28px;right:28px;z-index:900;width:58px;height:58px;border-radius:50%;background:#0f766e;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 6px 20px rgba(15,118,110,.3)}
.notif-fab:hover{background:#115e59;transform:scale(1.06)}
.notif-fab .fab-badge{position:absolute;top:-3px;right:-3px;background:#dc2626;color:#fff;font-size:11px;font-weight:800;min-width:22px;height:22px;padding:0 5px;border-radius:12px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff}
.notif-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:9998;display:none;align-items:center;justify-content:center;padding:20px}
.notif-modal-overlay.show{display:flex}
.notif-modal{background:#fff;border-radius:18px;width:100%;max-width:520px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 25px 70px rgba(15,23,42,.3);overflow:hidden}
.notif-modal-header{padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;background:#f0fdfa}
.notif-modal-header h3{font-size:16px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px}
.notif-modal-header h3 i{color:#0f766e;font-size:16px}
.notif-modal-header .modal-sub{font-size:12px;color:#64748b;margin-top:3px;margin-left:26px}
.notif-modal-close{width:36px;height:36px;border-radius:10px;border:none;background:#f1f5f9;color:#475569;cursor:pointer;font-size:15px}
.notif-modal-close:hover{background:#fee2e2;color:#dc2626}
.notif-modal-body{overflow-y:auto;padding:8px 0;flex:1}
.notif-item-modal{display:flex;gap:14px;padding:16px 24px;border-bottom:1px solid #f8fafc;cursor:pointer;border-left:3px solid transparent}
.notif-item-modal:hover{background:#f8fafc}
.notif-item-modal.unread{background:#f0fdfa;border-left-color:var(--notif-accent,#14b8a6)}
.notif-item-modal .notif-content{flex:1;min-width:0}
.notif-item-modal .notif-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.notif-item-modal .notif-unread-dot{width:8px;height:8px;border-radius:50%;background:#14b8a6;display:inline-block}
.notif-item-modal .notif-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:5px;word-wrap:break-word}
.notif-item-modal .notif-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.notif-modal-footer{padding:14px 24px;border-top:1px solid #f1f5f9;text-align:center;background:#f8fafc}
.notif-modal-footer a{color:#0f766e;text-decoration:none;font-size:12.5px;font-weight:700;display:inline-flex;align-items:center;gap:6px}
@media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 992px) { .two-col { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); width: 260px; }
  .sidebar.open { transform: translateX(0); }
  .burger-btn { display: flex; }
  .main-content { margin-left: 0; padding: 70px 18px 30px 18px; }
  .stats-grid, .metrics-grid { grid-template-columns: 1fr 1fr; }
  .top-bar { flex-direction: column; align-items: flex-start; }
  .user-chip .info { display: none; }
  body::before, body::after { opacity: .6; }
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
        <li><a href="<?= basename(__FILE__) ?>" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <li><a href="comparison.php"><i class="fas fa-scale-balanced"></i> <span>Algorithm vs Traditional</span></a></li>
        <li><a href="research_evaluation.php"><i class="fas fa-flask"></i> <span>Research Evaluation</span></a></li>
        <li><a href="administrator_monitoring.php"><i class="fas fa-shield-halved"></i> <span>Monitoring</span></a></li>
        <li><a href="performance_metrics.php"><i class="fas fa-chart-line"></i> <span>Performance Metrics</span></a></li>
        <li><a href="explanations.php"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
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
    <div><h1>Admin Dashboard</h1><p>Welcome back, <?= htmlspecialchars($full_name) ?>!</p></div>
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

<div class="welcome-card">
    <h2>Hello, <?= htmlspecialchars($full_name) ?>!</h2>
    <p>Monitor billing performance, track system metrics, and manage patient-centered billing operations from one place.</p>
</div>

<?php if ($sample_warning): ?>
    <div class="warn-banner"><i class="fas fa-exclamation-triangle"></i>
        <div><strong>Small Sample Size:</strong> Only <?= $total_records ?> records. Expand to at least 100 for reliable results.</div>
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total Records</div><div class="stat-value"><?= number_format($total_records) ?></div></div>
    <div class="stat-card green"><div class="stat-label">Total Charges</div><div class="stat-value" style="font-size:22px;">₱ <?= number_format($total_charges, 2) ?></div></div>
    <div class="stat-card orange"><div class="stat-label">Insurance Deduction</div><div class="stat-value" style="font-size:22px;">₱ <?= number_format($total_insurance_deduction, 2) ?></div></div>
    <div class="stat-card blue"><div class="stat-label">Patient Responsibility</div><div class="stat-value" style="font-size:22px;">₱ <?= number_format($total_revenue, 2) ?></div></div>
</div>

<div class="two-col">
    <div class="chart-card"><h3><i class="fas fa-chart-line"></i> Billing Records Overview</h3><div class="bar-chart-wrapper"><canvas id="barChart"></canvas></div></div>
    <div class="chart-card"><h3><i class="fas fa-chart-pie"></i> Top Billing Categories</h3><div class="bar-chart-wrapper"><canvas id="doughnutChart"></canvas></div></div>
</div>

<!-- ══ Classification Metrics (Rule-Based Algorithm) ══ -->
<div class="metrics-section">
    <h3>
        <i class="fas fa-bullseye"></i> Classification Performance
        <span class="metric-subtitle">Rule-Based Decision Algorithm · <?= number_format($total_evaluated) ?> record(s) evaluated</span>
    </h3>
    <div class="metrics-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="metric-card">
            <div class="metric-name">Accuracy</div>
            <div class="metric-score"><?= $accuracy_percent ?>%</div>
            <span class="metric-status" style="background: <?= $acc_status['bg'] ?>; color: <?= $acc_status['color'] ?>;"><?= $acc_status['label'] ?></span>
        </div>
        <div class="metric-card">
            <div class="metric-name">Precision</div>
            <div class="metric-score"><?= $precision_percent ?>%</div>
            <span class="metric-status" style="background: <?= $prec_status['bg'] ?>; color: <?= $prec_status['color'] ?>;"><?= $prec_status['label'] ?></span>
        </div>
        <div class="metric-card">
            <div class="metric-name">Recall</div>
            <div class="metric-score"><?= $recall_percent ?>%</div>
            <span class="metric-status" style="background: <?= $rec_status['bg'] ?>; color: <?= $rec_status['color'] ?>;"><?= $rec_status['label'] ?></span>
        </div>
        <div class="metric-card">
            <div class="metric-name">F1-Score</div>
            <div class="metric-score"><?= $f1_percent ?>%</div>
            <span class="metric-status" style="background: <?= $f1_status['bg'] ?>; color: <?= $f1_status['color'] ?>;"><?= $f1_status['label'] ?></span>
        </div>
    </div>

    <?php if ($total_evaluated > 0): ?>
    <div class="metrics-subinfo">
        <span><strong>Macro</strong> &nbsp; P: <?= $macro_p_percent ?>% &nbsp; R: <?= $macro_r_percent ?>% &nbsp; F1: <?= $macro_f1_percent ?>%</span>
        <span><strong>Micro</strong> &nbsp; P: <?= $micro_p_percent ?>% &nbsp; R: <?= $micro_r_percent ?>% &nbsp; F1: <?= $micro_f1_percent ?>%</span>
        <span><strong>Correct</strong> <?= $correct_classifications ?> / <?= $total_evaluated ?></span>
    </div>
    <?php else: ?>
    <div class="metrics-empty-note">
        <i class="fas fa-info-circle"></i>
        No records have both <code>predicted_category</code> and <code>actual_category</code> set yet — metrics will appear once ground-truth labels exist.
    </div>
    <?php endif; ?>
</div>

<!-- ══ Patient Experience Metrics (Semantic Analysis) ══ -->
<div class="metrics-section">
    <h3><i class="fas fa-heart-pulse"></i> Patient Experience</h3>
    <div class="metrics-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="metric-card">
            <div class="metric-name">Consistency</div>
            <div class="metric-score"><?= $consistency_percent ?>%</div>
            <span class="metric-status" style="background: <?= $cons_status['bg'] ?>; color: <?= $cons_status['color'] ?>;"><?= $cons_status['label'] ?></span>
        </div>
        <div class="metric-card">
            <div class="metric-name">Readability</div>
            <div class="metric-score"><?= $readability_score ?>/5.0</div>
            <span class="metric-status" style="background: <?= $read_status['bg'] ?>; color: <?= $read_status['color'] ?>;"><?= $read_status['label'] ?></span>
        </div>
        <div class="metric-card">
            <div class="metric-name">Transparency</div>
            <div class="metric-score"><?= $transparency_score ?>%</div>
            <span class="metric-status" style="background: <?= $trans_status['bg'] ?>; color: <?= $trans_status['color'] ?>;"><?= $trans_status['label'] ?></span>
        </div>
        <div class="metric-card">
            <div class="metric-name">Patient Understanding</div>
            <div class="metric-score"><?= $patient_understanding ?>%</div>
            <span class="metric-status" style="background: <?= $under_status['bg'] ?>; color: <?= $under_status['color'] ?>;"><?= $under_status['label'] ?></span>
        </div>
    </div>
</div>

<div class="overall-card">
    <span class="overall-label"><i class="fas fa-award"></i> Overall System Rating</span>
    <span class="overall-score"><?= $avg_score ?>%</span>
    <span class="overall-status"><?= $overall_status['label'] ?></span>
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
            <div><h3><i class="fas fa-bell"></i> Notifications</h3><div class="modal-sub"><?= count($notifications) ?> recent · <?= $unread_count ?> unread</div></div>
            <button class="notif-modal-close" onclick="closeNotifModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="notif-modal-body">
            <?php foreach ($notifications as $notif): $isUnread = empty($notif['is_read']); ?>
                <div class="notif-item-modal <?= $isUnread ? 'unread' : '' ?>" data-id="<?= (int)$notif['id'] ?>" data-color="<?= htmlspecialchars($notif['color'] ?: '#14b8a6') ?>" style="--notif-accent: <?= htmlspecialchars($notif['color'] ?: '#14b8a6') ?>;" <?php if ($isUnread && (int)$notif['id'] > 0): ?>onclick="markAsRead(event, this)"<?php endif; ?>>
                    <div class="notif-content">
                        <div class="notif-title"><?php if ($isUnread): ?><span class="notif-unread-dot"></span><?php endif; ?><?= htmlspecialchars(notifTitle($notif['message'])) ?></div>
                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                        <div class="notif-time"><i class="fas fa-clock"></i> <?= timeAgo($notif['created_at'], $notif['seconds_ago'] ?? null) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="notif-modal-footer"><a href="notifications.php">View all notifications <i class="fas fa-arrow-right"></i></a></div>
    </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
function openNotifModal(){document.getElementById('notifModalOverlay').classList.add('show');document.body.style.overflow='hidden';}
function closeNotifModal(){document.getElementById('notifModalOverlay').classList.remove('show');document.body.style.overflow='';}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeNotifModal();});
function showToast(title,message,color,duration){
    duration=duration||6000;
    const c=document.getElementById('toastContainer');
    const t=document.createElement('div');t.className='toast';t.style.setProperty('--toast-accent',color||'#14b8a6');
    t.innerHTML=`<div class="toast-body"><div class="toast-title">${title}</div><div class="toast-message">${message}</div><div class="toast-time"><i class="fas fa-clock"></i> Just now</div></div><button class="toast-close" onclick="dismissToast(this)"><i class="fas fa-xmark"></i></button>`;
    c.appendChild(t);setTimeout(()=>dismissToast(t.querySelector('.toast-close')),duration);
}
function dismissToast(btn){const t=btn.closest('.toast');if(!t||t.classList.contains('closing'))return;t.classList.add('closing');setTimeout(()=>t.remove(),300);}
function markAsRead(event,el){
    event.stopPropagation();if(!el.classList.contains('unread'))return;
    const id=parseInt(el.dataset.id,10);if(!id)return;
    el.classList.remove('unread');const d=el.querySelector('.notif-unread-dot');if(d)d.remove();el.removeAttribute('onclick');
    const b=document.getElementById('fabBadge');
    if(b){let c=parseInt(b.textContent,10)||0;const n=Math.max(0,c-1);if(n===0)b.remove();else b.textContent=n>9?'9+':n;}
    fetch('mark_notification_read.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}).catch(()=>{});
}
(function autoToast(){const f=document.querySelector('.notif-item-modal.unread');if(!f)return;const t=f.querySelector('.notif-title');const m=f.querySelector('.notif-message');if(!m)return;const c=f.dataset.color||'#14b8a6';const ti=t?t.textContent.replace(/\s+/g,' ').trim():'Notification';const me=m.textContent.trim();setTimeout(()=>showToast(ti,me,c,6000),500);})();
document.addEventListener('DOMContentLoaded',function(){
    const barCtx=document.getElementById('barChart').getContext('2d');
    const g1=barCtx.createLinearGradient(0,0,0,280);g1.addColorStop(0,'#14b8a6');g1.addColorStop(1,'#0f766e');
    const g2=barCtx.createLinearGradient(0,0,0,280);g2.addColorStop(0,'#fbbf24');g2.addColorStop(1,'#f59e0b');
    new Chart(barCtx,{type:'bar',data:{labels:<?= json_encode($monthly_labels) ?>,datasets:[{label:'Total Charges',data:<?= json_encode($monthly_charges) ?>,backgroundColor:g1,borderRadius:8,maxBarThickness:42},{label:'Final Amount',data:<?= json_encode($monthly_final) ?>,backgroundColor:g2,borderRadius:8,maxBarThickness:42}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',align:'end',labels:{usePointStyle:true,pointStyle:'circle',boxWidth:8,padding:16}}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8'}},x:{grid:{display:false},ticks:{color:'#64748b'}}}}});
    const dc=document.getElementById('doughnutChart').getContext('2d');
    const pc=<?= json_encode($pie_colors) ?>;
    new Chart(dc,{type:'doughnut',data:{labels:<?= json_encode($pie_labels) ?>,datasets:[{data:<?= json_encode($pie_data) ?>,backgroundColor:pc,borderColor:'#fff',borderWidth:3,hoverOffset:10}]},options:{responsive:true,maintainAspectRatio:false,cutout:'62%',plugins:{legend:{position:'right',labels:{usePointStyle:true,pointStyle:'circle',boxWidth:10,padding:14}}}}});
});
</script>
</body>
</html>