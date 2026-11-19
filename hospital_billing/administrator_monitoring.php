<?php
/* ============================================================
   administrator_monitoring.php
   Administrator Monitoring & Record Management
   ============================================================ */
date_default_timezone_set('Asia/Manila');
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
try { $pdo->exec("SET time_zone = '+08:00'"); } catch (PDOException $e) {}

$user_id   = $_SESSION['user_id']  ?? 0;
$user_name = $_SESSION['username'] ?? 'Admin';

/* -------- Fetch user & enforce admin role -------- */
$full_name = $user_name; $user_role = 'Admin'; $profile_picture_url = '';
try {
    $stmt = $pdo->prepare("SELECT full_name, role, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $full_name = $u['full_name'] ?? $user_name;
        $user_role = $u['role']      ?? 'Admin';
        if (!empty($u['profile_picture']) && file_exists(__DIR__.'/uploads/profiles/'.$u['profile_picture'])) {
            $profile_picture_url = 'uploads/profiles/'.$u['profile_picture'];
        }
    }
} catch (PDOException $e) {}

if (strtolower($user_role) !== 'admin') {
    header('Location: dashboard.php'); exit;
}

/* -------- Auto-create monitoring tables (safety) -------- */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_trail (
        id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, admin_name VARCHAR(150),
        action_type VARCHAR(50) NOT NULL, module VARCHAR(50) NOT NULL, record_id INT NULL,
        reason TEXT NULL, details TEXT NULL, ip_address VARCHAR(45) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action_type), INDEX idx_module (module), INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS archived_records (
        id INT AUTO_INCREMENT PRIMARY KEY, module VARCHAR(50) NOT NULL, original_id INT NOT NULL,
        record_snapshot LONGTEXT NOT NULL, archived_by INT NOT NULL, archived_by_name VARCHAR(150),
        reason TEXT, archived_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        restored_by INT NULL, restored_by_name VARCHAR(150), restored_at DATETIME NULL,
        INDEX idx_module (module), INDEX idx_original (original_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS voided_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY, billing_id INT NOT NULL, voided_by INT NOT NULL,
        voided_by_name VARCHAR(150), reason TEXT NOT NULL, original_snapshot LONGTEXT,
        voided_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_billing (billing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

foreach ([
    "ALTER TABLE billing_records ADD COLUMN is_archived TINYINT(1) DEFAULT 0",
    "ALTER TABLE billing_records ADD COLUMN is_voided TINYINT(1) DEFAULT 0",
    "ALTER TABLE billing_records ADD COLUMN void_reason TEXT NULL",
    "ALTER TABLE billing_records ADD COLUMN voided_by INT NULL",
    "ALTER TABLE billing_records ADD COLUMN voided_at DATETIME NULL",
    "ALTER TABLE users ADD COLUMN is_archived TINYINT(1) DEFAULT 0",
] as $sql) { try { $pdo->exec($sql); } catch (PDOException $e) {} }

/* -------- Helper: log audit entry (still used by actions) -------- */
function logAudit(PDO $pdo, int $adminId, string $adminName, string $action, string $module, $recordId, ?string $reason = null, ?string $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo->prepare("INSERT INTO audit_trail (admin_id, admin_name, action_type, module, record_id, reason, details, ip_address)
                       VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$adminId, $adminName, $action, $module, $recordId, $reason, $details, $ip]);
    } catch (PDOException $e) {}
}

logAudit($pdo, (int)$user_id, $full_name, 'VIEW', 'MONITORING', null, 'Viewed Administrator Monitoring page');

/* -------- Summary stats -------- */
$stats = ['total_patients'=>0,'total_billing'=>0,'active_users'=>0,'archived_count'=>0,'voided_count'=>0];

try { $stats['total_patients'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role)='patient' AND COALESCE(is_archived,0)=0")->fetchColumn(); }
catch (PDOException $e) { try { $stats['total_patients'] = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(); } catch (PDOException $e2) {} }

try { $stats['total_billing']     = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE COALESCE(is_archived,0)=0 AND COALESCE(is_voided,0)=0")->fetchColumn(); } catch (PDOException $e) {}
try { $stats['active_users']      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(is_archived,0)=0")->fetchColumn(); } catch (PDOException $e) {}
try { $stats['archived_count']    = (int)$pdo->query("SELECT COUNT(*) FROM archived_records WHERE restored_at IS NULL")->fetchColumn(); } catch (PDOException $e) {}
try { $stats['voided_count']      = (int)$pdo->query("SELECT COUNT(*) FROM voided_transactions")->fetchColumn(); } catch (PDOException $e) {}

/* -------- Fetch PATIENTS -------- */
$patients = []; $patient_cols = [];
try {
    $patients = $pdo->query("SELECT * FROM patients ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($patients)) { $patient_cols = array_keys($patients[0]); }
} catch (PDOException $e) {
    try {
        $patients = $pdo->query("SELECT * FROM users WHERE LOWER(role)='patient' ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($patients)) { $patient_cols = array_keys($patients[0]); }
    } catch (PDOException $e2) {}
}
$archived_patient_ids = [];
try {
    $r = $pdo->query("SELECT original_id FROM archived_records WHERE module='PATIENT' AND restored_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $archived_patient_ids = array_flip($r);
} catch (PDOException $e) {}

/* -------- Fetch BILLING RECORDS -------- */
$billing = [];
try { $billing = $pdo->query("SELECT * FROM billing_records ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrator Monitoring - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

/* ===== BODY: soft teal mesh gradient background (sama seperti login.php) ===== */
body {
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex; min-height:100vh;
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

/* ---------- SIDEBAR (teal theme, semi-transparent) ---------- */
.sidebar { width:270px; background:rgba(255,255,255,.88); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); display:flex; flex-direction:column; padding:25px 0 20px; position:fixed; top:0; left:0; height:100vh; z-index:200; transition:transform .3s; overflow-y:auto; border-right:1px solid rgba(226,232,240,.8); }

/* Exact Sidebar Brand Match */
.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:#0f766e;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}

.sidebar-divider { height:1px; background:#f1f5f9; margin:0 20px 25px; }
.sidebar-menu { list-style:none; padding:0 14px; flex:1; }
.sidebar-menu li { margin-bottom:4px; }
.sidebar-menu li a { display:flex; align-items:center; gap:14px; padding:12px 15px; color:#475569; text-decoration:none; border-radius:10px; font-size:13.5px; font-weight:500; transition:all .25s; }
.sidebar-menu li a i { width:20px; font-size:16px; text-align:center; color:#94a3b8; }
.sidebar-menu li a:hover { background:#f0fdfa; color:#0f766e; }
.sidebar-menu li a:hover i { color:#0f766e; }
.sidebar-menu li a.active { background:#0f766e; color:#fff; box-shadow:0 4px 12px rgba(15,118,110,.2); }
.sidebar-menu li a.active i { color:#fff; }
.sidebar-footer { padding:15px 14px 0; border-top:1px solid #f1f5f9; margin-top:15px; }
.sidebar-footer a { display:flex; align-items:center; gap:14px; padding:12px 15px; color:#64748b; text-decoration:none; border-radius:10px; font-size:13.5px; font-weight:500; transition:all .25s; }
.sidebar-footer a:hover { background:#fef2f2; color:#dc2626; }
.burger-btn { display:none; position:fixed; top:15px; left:15px; z-index:300; width:44px; height:44px; background:#0f766e; color:#fff; border:none; border-radius:10px; font-size:18px; cursor:pointer; align-items:center; justify-content:center; }
.burger-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:150; }
.burger-overlay.active { display:block; }

/* ---------- MAIN ---------- */
.main-content { margin-left:270px; flex:1; padding:25px 35px 40px; min-height:100vh; position:relative; z-index:1; }
.top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:15px; }
.top-bar h1 { font-size:26px; font-weight:700; color:#0f172a; }
.top-bar p { font-size:13.5px; color:#64748b; margin-top:4px; }
.top-bar-actions { display:flex; align-items:center; gap:15px; }

.user-chip { display:flex; align-items:center; gap:10px; padding:6px 16px 6px 6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:30px; transition:all .25s; }
.user-chip:hover { box-shadow:0 4px 12px rgba(15,118,110,.15); transform:translateY(-1px); }
.user-chip .avatar, .user-chip .avatar-img { width:38px; height:38px; border-radius:50%; background:#0f766e; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; object-fit:cover; border:2px solid #ccfbf1; flex-shrink:0; }
.user-chip .info { min-width:0; }
.user-chip .info .name { font-size:13.5px; font-weight:700; color:#0f172a; line-height:1.2; white-space:nowrap; }
.user-chip .info .role { font-size:10.5px; color:#0f766e; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; white-space:nowrap; }

/* ---------- SUMMARY CARDS ---------- */
.summary-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:14px; margin-bottom:22px; }
.summary-card {
    background:#fff;
    padding:16px 18px;
    border-radius:12px;
    border:1px solid #e2e8f0;
    border-left:4px solid #0f766e;
    transition:all .2s;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    min-height:78px;
}
.summary-card:hover { transform:translateY(-2px); box-shadow:0 6px 14px rgba(15,118,110,.08); }
.summary-card.blue   { border-left-color:#0ea5e9; }
.summary-card.orange { border-left-color:#f59e0b; }
.summary-card.green  { border-left-color:#10b981; }
.summary-card.red    { border-left-color:#dc2626; }
.summary-card.purple { border-left-color:#8b5cf6; }
.summary-card .label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.5px; line-height:1.25; min-height:14px; }
.summary-card .value { font-size:24px; font-weight:800; color:#0f172a; line-height:1.1; margin-top:6px; }

/* ---------- TABS ---------- */
.tabs-bar { display:flex; gap:6px; background:#fff; padding:6px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px; overflow-x:auto; }
.tabs-bar::-webkit-scrollbar { height:5px; }
.tabs-bar::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:3px; }
.tab-btn { flex:1; padding:11px 16px; background:transparent; border:none; border-radius:8px; font-size:13.5px; font-weight:600; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; white-space:nowrap; transition:all .2s; }
.tab-btn:hover { background:#f0fdfa; color:#0f766e; }
.tab-btn.active { background:#0f766e; color:#fff; box-shadow:0 4px 10px rgba(15,118,110,.2); }
.tab-panel { display:none; }
.tab-panel.active { display:block; }

/* ---------- CARD / TABLE ---------- */
.card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:20px 24px; margin-bottom:20px; }
.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px; }
.card-header h3 { font-size:15px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:10px; }
.card-header h3 i { color:#0f766e; }
.card-header .card-tools { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-box { position:relative; }
.search-box input { padding:9px 14px 9px 36px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; width:240px; outline:none; transition:all .2s; }
.search-box input:focus { border-color:#14b8a6; box-shadow:0 0 0 3px rgba(20,184,166,.12); }
.search-box i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px; pointer-events:none; }
.filter-select { padding:9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; background:#fff; color:#334155; cursor:pointer; }

.table-wrap { overflow-x:auto; border-radius:10px; border:1px solid #f1f5f9; }
.table-wrap::-webkit-scrollbar { height:8px; }
.table-wrap::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
table { width:100%; border-collapse:collapse; font-size:13px; }
thead { background:#f8fafc; }
thead th { text-align:left; padding:12px 14px; font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e2e8f0; white-space:nowrap; vertical-align:middle; }
tbody td { padding:12px 14px; border-bottom:1px solid #f8fafc; color:#334155; vertical-align:middle; line-height:1.4; }
tbody tr:hover { background:#f8fafc; }
tbody tr:last-child td { border-bottom:none; }
tbody td .badge { vertical-align:middle; }

/* ===== SERVICE DETAILS STYLING (Matching Transaction History) ===== */
.service-cell { max-width: 350px; }
.svc-title { font-size: 12.5px; font-weight: 700; color: #0f172a; }
.service-cell .cat { font-size: 10.5px; color: #64748b; margin-top: 3px; display: inline-block; }
.service-cell .explanation {
    font-size: 11.5px; color: #475569; margin-top: 6px; padding: 6px 10px;
    background: #f0fdfa; border-left: 2px solid #14b8a6; border-radius: 0 4px 4px 0;
    line-height: 1.4; 
}
.service-cell .explanation strong { color: #0f766e; }

.badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; line-height:1.2; }
.badge-green  { background:#dcfce7; color:#15803d; }
.badge-yellow { background:#fef9c3; color:#a16207; }
.badge-red    { background:#fee2e2; color:#b91c1c; }
.badge-blue   { background:#dbeafe; color:#1e40af; }
.badge-gray   { background:#f1f5f9; color:#475569; }
.badge-purple { background:#ede9fe; color:#6d28d9; }

.btn { padding:7px 12px; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; justify-content:center; gap:6px; line-height:1.2; white-space:nowrap; }
.btn:hover { transform:translateY(-1px); }
.btn-view    { background:#f0fdfa; color:#0f766e; }
.btn-view:hover { background:#ccfbf1; }
.btn-archive { background:#fef3c7; color:#92400e; }
.btn-archive:hover { background:#fde68a; }
.btn-void    { background:#fee2e2; color:#b91c1c; }
.btn-void:hover { background:#fecaca; }
.btn-restore { background:#dcfce7; color:#15803d; }
.btn-restore:hover { background:#bbf7d0; }
.btn-row { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }

/* ---------- MODAL ---------- */
.modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); backdrop-filter:blur(4px); z-index:9998; display:none; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.show { display:flex; }
.modal { background:#fff; border-radius:16px; width:100%; max-width:560px; box-shadow:0 25px 70px rgba(15,23,42,.3); overflow:hidden; animation:modalIn .25s ease; }
.modal.lg { max-width:820px; }
@keyframes modalIn { from { opacity:0; transform:translateY(20px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.modal-header { padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:#f0fdfa; gap:12px; }
.modal-header h3 { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:10px; }
.modal-header h3 i { color:#0f766e; }
.modal-close { width:34px; height:34px; border-radius:10px; border:none; background:#f1f5f9; color:#475569; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.modal-close:hover { background:#fee2e2; color:#dc2626; }
.modal-body { padding:22px 24px; max-height:65vh; overflow-y:auto; }
.modal-footer { padding:14px 24px; border-top:1px solid #f1f5f9; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; }

.form-label { font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px; display:block; }
.form-textarea { width:100%; padding:11px 14px; border:1px solid #e2e8f0; border-radius:10px; font-family:inherit; font-size:13px; resize:vertical; min-height:100px; outline:none; display:block; }
.form-textarea:focus { border-color:#14b8a6; box-shadow:0 0 0 3px rgba(20,184,166,.12); }
.form-hint { font-size:11.5px; color:#94a3b8; margin-top:6px; }

.alert-warning { background:#fffbeb; color:#92400e; padding:14px 18px; border-radius:10px; border-left:4px solid #f59e0b; font-size:13px; display:flex; align-items:flex-start; gap:10px; margin-bottom:16px; line-height:1.5; }
.alert-warning > i { flex-shrink:0; padding-top:2px; }

.detail-row { display:grid; grid-template-columns:140px 1fr; gap:10px; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:13px; align-items:start; }
.detail-row:last-child { border-bottom:none; }
.detail-row .dt { color:#64748b; font-weight:600; word-break:break-word; }
.detail-row .dd { color:#0f172a; word-break:break-word; overflow-wrap:anywhere; }

.btn-primary { background:#0f766e; color:#fff; padding:10px 20px; }
.btn-primary:hover { background:#115e59; }
.btn-secondary { background:#f1f5f9; color:#475569; padding:10px 20px; }
.btn-secondary:hover { background:#e2e8f0; }
.btn-danger { background:#dc2626; color:#fff; padding:10px 20px; }
.btn-danger:hover { background:#b91c1c; }
.btn-warn { background:#f59e0b; color:#fff; padding:10px 20px; }
.btn-warn:hover { background:#d97706; }

/* Toast */
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:380px;width:calc(100% - 48px);}
.toast{background:#fff;border-radius:12px;box-shadow:0 12px 40px rgba(15,23,42,.18);padding:14px 18px;border-left:4px solid var(--toast-accent,#14b8a6);animation:slideIn .3s ease;}
.toast.success{--toast-accent:#10b981}.toast.error{--toast-accent:#dc2626}.toast.warn{--toast-accent:#f59e0b}
@keyframes slideIn{from{opacity:0;transform:translateX(120%)}to{opacity:1;transform:translateX(0)}}
.toast .title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.toast .msg{font-size:12.5px;color:#475569;line-height:1.5}

.empty-row { text-align:center; padding:40px 20px; color:#94a3b8; font-size:13px; }
.empty-row i { font-size:36px; margin-bottom:10px; display:block; color:#cbd5e1; }

@media (max-width: 1200px) { .summary-grid { grid-template-columns:repeat(3, 1fr); } }
@media (max-width: 768px) {
  .sidebar { transform:translateX(-100%); width:260px; }
  .sidebar.open { transform:translateX(0); }
  .burger-btn { display:flex; }
  .main-content { margin-left:0; padding:70px 18px 30px; }
  .summary-grid { grid-template-columns:repeat(2, 1fr); }
  .top-bar { flex-direction:column; align-items:flex-start; }
  .user-chip { padding:6px; }
  .user-chip .info { display:none; }
  .search-box input { width:100%; }
  .card-tools { width:100%; }
  .card-tools > * { flex:1; }
  .detail-row { grid-template-columns:1fr; gap:4px; }
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
        <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <li><a href="administrator_monitoring.php" class="active"><i class="fas fa-shield-halved"></i> <span>Monitoring</span></a></li>
        <li><a href="explanations.php"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
    </ul>
    <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></div>
</aside>

<main class="main-content">
    <div class="top-bar">
        <div>
            <h1>Administrator Monitoring</h1>
            <p>Monitor patients and billing transactions across the system</p>
        </div>
        <div class="top-bar-actions">
            <div class="user-chip">
                <?php if ($profile_picture_url): ?>
                    <img src="<?= htmlspecialchars($profile_picture_url) ?>?v=<?= time() ?>" alt="" class="avatar-img">
                <?php else: ?>
                    <div class="avatar"><?= strtoupper(substr($full_name,0,2)) ?></div>
                <?php endif; ?>
                <div class="info">
                    <div class="name"><?= htmlspecialchars($full_name) ?></div>
                    <div class="role"><?= htmlspecialchars($user_role) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="summary-grid">
        <div class="summary-card"><div class="label">Patients</div><div class="value"><?= number_format($stats['total_patients']) ?></div></div>
        <div class="summary-card blue"><div class="label">Billing Records</div><div class="value"><?= number_format($stats['total_billing']) ?></div></div>
        <div class="summary-card green"><div class="label">Active Users</div><div class="value"><?= number_format($stats['active_users']) ?></div></div>
        <div class="summary-card purple"><div class="label">Archived</div><div class="value"><?= number_format($stats['archived_count']) ?></div></div>
        <div class="summary-card red"><div class="label">Voided</div><div class="value"><?= number_format($stats['voided_count']) ?></div></div>
    </div>

    <!-- TABS -->
    <div class="tabs-bar">
        <button class="tab-btn active" data-tab="patients"><i class="fas fa-user-injured"></i> Patient Records</button>
        <button class="tab-btn" data-tab="billing"><i class="fas fa-file-invoice-dollar"></i> Billing Records</button>
    </div>

    <!-- ================= PATIENT RECORDS ================= -->
    <div class="tab-panel active" id="tab-patients">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-injured"></i> Registered Patients (<?= count($patients) ?>)</h3>
                <div class="card-tools">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="patientSearch" placeholder="Search patients..." onkeyup="filterTable('patientTable', this.value)">
                    </div>
                </div>
            </div>
            <div class="table-wrap">
                <table id="patientTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email / Username</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="7" class="empty-row"><i class="fas fa-inbox"></i>No patients found.</td></tr>
                    <?php else: foreach ($patients as $p):
                        $pid     = $p['id'] ?? '?';
                        $pname   = $p['full_name'] ?? ($p['name'] ?? 'N/A');
                        $prole   = $p['role'] ?? 'Patient';
                        $pemail  = $p['email'] ?? ($p['username'] ?? '—');
                        $pdate   = $p['created_at'] ?? ($p['date_registered'] ?? '—');
                        $isArch  = isset($archived_patient_ids[$pid]);
                    ?>
                        <tr>
                            <td>#<?= htmlspecialchars($pid) ?></td>
                            <td><strong><?= htmlspecialchars($pname) ?></strong></td>
                            <td><span class="badge badge-blue"><?= htmlspecialchars($prole) ?></span></td>
                            <td><?= htmlspecialchars($pemail) ?></td>
                            <td><?= $pdate !== '—' ? date('M d, Y', strtotime($pdate)) : '—' ?></td>
                            <td>
                                <?php if ($isArch): ?>
                                    <span class="badge badge-gray">Archived</span>
                                <?php else: ?>
                                    <span class="badge badge-green">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-row">
                                    <button class="btn btn-view" onclick="viewPatientHistory(<?= (int)$pid ?>, '<?= htmlspecialchars(addslashes($pname)) ?>')">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                    <?php if (!$isArch): ?>
                                    <button class="btn btn-archive" onclick="openArchiveModal('PATIENT', <?= (int)$pid ?>, '<?= htmlspecialchars(addslashes($pname)) ?>')">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-restore" onclick="openRestoreModal('PATIENT', <?= (int)$pid ?>, '<?= htmlspecialchars(addslashes($pname)) ?>')">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================= BILLING RECORDS ================= -->
    <div class="tab-panel" id="tab-billing">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Billing Transactions (<?= count($billing) ?>)</h3>
                <div class="card-tools">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="billingSearch" placeholder="Search billing..." onkeyup="filterTable('billingTable', this.value)">
                    </div>
                    <select class="filter-select" id="billingStatusFilter" onchange="filterByStatus('billingTable', this.value, 5)">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Paid">Paid</option>
                        <option value="Voided">Voided</option>
                    </select>
                </div>
            </div>
            <div class="table-wrap">
                <table id="billingTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <!-- MERGED COLUMN HEADER -->
                            <th>Service Details</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($billing)): ?>
                        <tr><td colspan="7" class="empty-row"><i class="fas fa-inbox"></i>No billing records found.</td></tr>
                    <?php else: foreach ($billing as $b):
                        $bid    = $b['id'] ?? '?';
                        $bpat   = $b['patient_name'] ?? ($b['patient_id'] ?? '—');
                        $bcat   = $b['category'] ?? '—';
                        $btot   = (float)($b['total_amount'] ?? 0);
                        $bdate  = $b['date'] ?? ($b['created_at'] ?? '—');
                        $bstat  = $b['status'] ?? 'Pending';
                        $isVoid = !empty($b['is_voided']);
                        $isArch = !empty($b['is_archived']);
                        
                        // Fetch service description and explanation
                        $bdesc  = $b['service_description'] ?? '—';
                        $bexp   = $b['patient_explanation'] ?? '';
                    ?>
                        <tr>
                            <td>#<?= htmlspecialchars($bid) ?></td>
                            <td><?= htmlspecialchars($bpat) ?></td>
                            
                            <!-- MERGED SERVICE DETAILS COLUMN -->
                            <td>
                                <div class="service-cell">
                                    <div class="svc-title"><?= htmlspecialchars($bdesc) ?></div>
                                    <div class="cat"><?= htmlspecialchars($bcat) ?></div>
                                    
                                    <?php if (!empty($bexp)): ?>
                                        <div class="explanation">
                                            <strong>Patient Understanding:</strong><br>
                                            <?= htmlspecialchars($bexp) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>₱ <?= number_format($btot, 2) ?></td>
                            <td><?= $bdate !== '—' ? date('M d, Y', strtotime($bdate)) : '—' ?></td>
                            <td>
                                <?php
                                $badge = 'badge-gray';
                                if ($isVoid) $badge = 'badge-red';
                                elseif ($bstat === 'Paid') $badge = 'badge-green';
                                elseif ($bstat === 'Processing') $badge = 'badge-blue';
                                elseif ($bstat === 'Pending') $badge = 'badge-yellow';
                                ?>
                                <span class="badge <?= $badge ?>"><?= $isVoid ? 'Voided' : htmlspecialchars($bstat) ?></span>
                            </td>
                            <td>
                                <div class="btn-row">
                                    <button class="btn btn-view" onclick="viewBillingHistory(<?= (int)$bid ?>)">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                    <?php if (!$isVoid && !$isArch): ?>
                                        <button class="btn btn-void" onclick="openVoidModal(<?= (int)$bid ?>)">
                                            <i class="fas fa-ban"></i> Void
                                        </button>
                                        <button class="btn btn-archive" onclick="openArchiveModal('BILLING', <?= (int)$bid ?>, 'Billing #<?= (int)$bid ?>')">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    <?php elseif ($isArch && !$isVoid): ?>
                                        <button class="btn btn-restore" onclick="openRestoreModal('BILLING', <?= (int)$bid ?>, 'Billing #<?= (int)$bid ?>')">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- ============ REUSABLE MODALS ============ -->
<div class="modal-overlay" id="actionModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="actionModalTitle"><i class="fas fa-exclamation-triangle"></i> Confirm Action</h3>
            <button class="modal-close" onclick="closeModal('actionModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="alert-warning" id="actionModalWarning">
                <i class="fas fa-info-circle"></i>
                <div id="actionModalWarningText">This action will be recorded in the Audit Trail.</div>
            </div>
            <label class="form-label">Reason <span style="color:#dc2626">*</span></label>
            <textarea class="form-textarea" id="actionReason" placeholder="Explain why you are performing this action..."></textarea>
            <div class="form-hint">This reason is required and will be stored in the audit trail.</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('actionModal')">Cancel</button>
            <button class="btn btn-danger" id="actionConfirmBtn" onclick="submitAction()">Confirm</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="historyModal">
    <div class="modal lg">
        <div class="modal-header">
            <h3><i class="fas fa-history"></i> Record History</h3>
            <button class="modal-close" onclick="closeModal('historyModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="historyModalBody">
            <p style="color:#64748b">Loading...</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('historyModal')">Close</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
/* ---------- Sidebar ---------- */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}

/* ---------- Tabs ---------- */
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + target).classList.add('active');
    });
});

/* ---------- Search filter ---------- */
function filterTable(tableId, query) {
    const q = query.toLowerCase();
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(tr => {
        if (tr.querySelector('.empty-row')) return;
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function filterByStatus(tableId, val, colIdx) {
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(tr => {
        if (tr.querySelector('.empty-row')) return;
        const cell = tr.children[colIdx];
        if (!cell) return;
        tr.style.display = (!val || cell.textContent.trim().toLowerCase().includes(val.toLowerCase())) ? '' : 'none';
    });
}

/* ---------- Modal controls ---------- */
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
});

/* ---------- Toast ---------- */
function showToast(title, message, type) {
    type = type || 'success';
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<div class="title">${title}</div><div class="msg">${message}</div>`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(120%)'; setTimeout(() => t.remove(), 300); }, 4500);
}

/* ---------- Action modal (archive / void / restore) ---------- */
let pendingAction = null;
function openArchiveModal(module, id, label) {
    pendingAction = { type: 'archive', module, id };
    document.getElementById('actionModalTitle').innerHTML = '<i class="fas fa-archive"></i> Archive ' + label;
    document.getElementById('actionModalWarningText').innerHTML =
        'Archiving removes this record from active views but <strong>does not permanently delete it</strong>. You can restore it at any time.';
    document.getElementById('actionConfirmBtn').className = 'btn btn-warn';
    document.getElementById('actionConfirmBtn').textContent = 'Archive Record';
    document.getElementById('actionReason').value = '';
    document.getElementById('actionModal').classList.add('show');
}
function openRestoreModal(module, id, label) {
    pendingAction = { type: 'restore', module, id };
    document.getElementById('actionModalTitle').innerHTML = '<i class="fas fa-undo"></i> Restore ' + label;
    document.getElementById('actionModalWarningText').innerHTML =
        'This will restore the record to active views. The action will be logged in the audit trail.';
    document.getElementById('actionConfirmBtn').className = 'btn btn-primary';
    document.getElementById('actionConfirmBtn').textContent = 'Restore Record';
    document.getElementById('actionReason').value = '';
    document.getElementById('actionModal').classList.add('show');
}
function openVoidModal(billingId) {
    pendingAction = { type: 'void', module: 'BILLING', id: billingId };
    document.getElementById('actionModalTitle').innerHTML = '<i class="fas fa-ban"></i> Void Billing #' + billingId;
    document.getElementById('actionModalWarningText').innerHTML =
        'Voiding marks the transaction as invalid. <strong>The original billing history is preserved</strong> and this action will be recorded. A reason is required.';
    document.getElementById('actionConfirmBtn').className = 'btn btn-danger';
    document.getElementById('actionConfirmBtn').textContent = 'Void Transaction';
    document.getElementById('actionReason').value = '';
    document.getElementById('actionModal').classList.add('show');
}

async function submitAction() {
    if (!pendingAction) return;
    const reason = document.getElementById('actionReason').value.trim();
    if (!reason) { showToast('Reason Required', 'Please provide a reason for this action.', 'error'); return; }

    const btn = document.getElementById('actionConfirmBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    try {
        const res = await fetch('monitoring_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: pendingAction.type,
                module: pendingAction.module,
                id: pendingAction.id,
                reason: reason
            })
        });
        const data = await res.json();
        if (data.success) {
            closeModal('actionModal');
            showToast('Success', data.message || 'Action completed.', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('Error', data.message || 'Action failed.', 'error');
            btn.disabled = false;
            btn.textContent = 'Try Again';
        }
    } catch (err) {
        showToast('Error', 'Network error: ' + err.message, 'error');
        btn.disabled = false; btn.textContent = 'Try Again';
    }
}

/* ---------- View histories ---------- */
async function viewPatientHistory(id, name) {
    document.getElementById('historyModalBody').innerHTML = '<p style="color:#64748b">Loading...</p>';
    document.getElementById('historyModal').classList.add('show');
    try {
        const res = await fetch('monitoring_actions.php?history=patient&id=' + id);
        const data = await res.json();
        let html = `<h4 style="margin-bottom:12px;color:#0f172a;">${escapeHtml(name)} (Patient #${id})</h4>`;
        if (data.registration) {
            html += `<div class="detail-row"><div class="dt">Registered</div><div class="dd">${escapeHtml(data.registration)}</div></div>`;
        }
        if (data.billing_count !== undefined) {
            html += `<div class="detail-row"><div class="dt">Billing Records</div><div class="dd">${data.billing_count}</div></div>`;
        }
        if (data.timeline && data.timeline.length) {
            html += `<h5 style="margin:16px 0 8px;color:#0f172a;">Activity Timeline</h5>`;
            data.timeline.forEach(item => {
                html += `<div class="detail-row"><div class="dt">${escapeHtml(item.date)}</div><div class="dd"><span class="badge badge-blue">${escapeHtml(item.action)}</span> ${escapeHtml(item.detail || '')}</div></div>`;
            });
        } else {
            html += `<p style="color:#94a3b8;margin-top:12px;">No timeline data.</p>`;
        }
        document.getElementById('historyModalBody').innerHTML = html;
    } catch (err) {
        document.getElementById('historyModalBody').innerHTML = '<p style="color:#dc2626">Failed to load history.</p>';
    }
}
async function viewBillingHistory(id) {
    document.getElementById('historyModalBody').innerHTML = '<p style="color:#64748b">Loading...</p>';
    document.getElementById('historyModal').classList.add('show');
    try {
        const res = await fetch('monitoring_actions.php?history=billing&id=' + id);
        const data = await res.json();
        let html = `<h4 style="margin-bottom:12px;color:#0f172a;">Billing Record #${id}</h4>`;
        if (data.record) {
            Object.keys(data.record).forEach(k => {
                if (data.record[k] !== null && data.record[k] !== '') {
                    html += `<div class="detail-row"><div class="dt">${escapeHtml(k)}</div><div class="dd">${escapeHtml(String(data.record[k]))}</div></div>`;
                }
            });
        }
        if (data.voided) {
            html += `<h5 style="margin:16px 0 8px;color:#b91c1c;">Voided</h5>`;
            data.voided.forEach(v => {
                html += `<div class="detail-row"><div class="dt">${escapeHtml(v.voided_at)}</div><div class="dd">By ${escapeHtml(v.voided_by_name)} — ${escapeHtml(v.reason)}</div></div>`;
            });
        }
        document.getElementById('historyModalBody').innerHTML = html;
    } catch (err) {
        document.getElementById('historyModalBody').innerHTML = '<p style="color:#dc2626">Failed to load history.</p>';
    }
}

function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
</script>
</body>
</html>