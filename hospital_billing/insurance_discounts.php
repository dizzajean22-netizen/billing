<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: login.php'); exit; }
require_once 'config.php';

$success='';$error='';
$user_id=$_SESSION['user_id']??0;$user_name=$_SESSION['username']??'Admin';$full_name=$user_name;$user_role='Admin';$profile_picture_url='';
try{$s=$pdo->prepare("SELECT full_name,role,profile_picture FROM users WHERE id=?");$s->execute([$user_id]);$u=$s->fetch(PDO::FETCH_ASSOC);if($u){$full_name=$u['full_name']??$user_name;$user_role=$u['role']??'Admin';if(!empty($u['profile_picture'])&&file_exists(__DIR__.'/uploads/profiles/'.$u['profile_picture']))$profile_picture_url='uploads/profiles/'.$u['profile_picture'];}}catch(PDOException $e){}

/* ===== AUTO-MIGRATE: ensure columns exist ===== */
try { $stmt = $pdo->query("SHOW COLUMNS FROM insurance_providers LIKE 'description'"); if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE insurance_providers ADD COLUMN description VARCHAR(255) NULL"); } catch (PDOException $e) {}
try { $stmt = $pdo->query("SHOW COLUMNS FROM insurance_providers LIKE 'payment_type'"); if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE insurance_providers ADD COLUMN payment_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent'"); } catch (PDOException $e) {}
try { $stmt = $pdo->query("SHOW COLUMNS FROM discount_types LIKE 'payment_type'"); if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE discount_types ADD COLUMN payment_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent'"); } catch (PDOException $e) {}

/* ===== DELETE INSURANCE ===== */
if(isset($_GET['delete_insurance'])&&is_numeric($_GET['delete_insurance'])){
    try{$pdo->prepare("DELETE FROM insurance_providers WHERE id=?")->execute([$_GET['delete_insurance']]);header("Location: insurance_discounts.php?msg=deleted_insurance");exit;}
    catch(PDOException $e){$error="Error: ".$e->getMessage();}
}

/* ===== SAVE INSURANCE ===== */
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_insurance'])){
    $id          = $_POST['id'] ?? null;
    $pn          = trim($_POST['provider_name'] ?? '');
    $ptype       = ($_POST['payment_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $cp          = floatval($_POST['coverage_percentage'] ?? 0);
    $ca          = floatval($_POST['coverage_amount'] ?? 0);
    $d           = trim($_POST['description'] ?? '');

    if($pn===''){
        $error="Provider Name is required.";
    } elseif($ptype==='percent' && ($cp<0 || $cp>100)){
        $error="Percentage must be 0-100.";
    } elseif($ptype==='fixed' && $ca<0){
        $error="Fixed amount cannot be negative.";
    } else {
        try{
            if($id){
                $stmt=$pdo->prepare("UPDATE insurance_providers SET provider_name=?, coverage_percentage=?, coverage_amount=?, description=?, payment_type=? WHERE id=?");
                $stmt->execute([$pn,$cp,$ca,$d,$ptype,$id]);
            }else{
                $stmt=$pdo->prepare("INSERT INTO insurance_providers (provider_name,coverage_percentage,coverage_amount,description,payment_type) VALUES (?,?,?,?,?)");
                $stmt->execute([$pn,$cp,$ca,$d,$ptype]);
            }
            header("Location: insurance_discounts.php?msg=saved_insurance");exit;
        }catch(PDOException $e){$error="Error: ".$e->getMessage();}
    }
}

/* ===== DELETE DISCOUNT ===== */
if(isset($_GET['delete_discount'])&&is_numeric($_GET['delete_discount'])){
    try{$pdo->prepare("DELETE FROM discount_types WHERE id=?")->execute([$_GET['delete_discount']]);header("Location: insurance_discounts.php?msg=deleted_discount");exit;}
    catch(PDOException $e){$error="Error: ".$e->getMessage();}
}

/* ===== SAVE DISCOUNT ===== */
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_discount'])){
    $id   = $_POST['id'] ?? null;
    $dn   = trim($_POST['discount_name'] ?? '');
    $dtype= ($_POST['discount_payment_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $dp   = floatval($_POST['discount_percentage'] ?? 0);
    $da   = floatval($_POST['discount_amount'] ?? 0);
    $el   = trim($_POST['eligibility'] ?? '');

    if($dn===''){
        $error="Discount Name is required.";
    } elseif($dtype==='percent' && ($dp<0 || $dp>100)){
        $error="Percentage must be 0-100.";
    } elseif($dtype==='fixed' && $da<0){
        $error="Fixed amount cannot be negative.";
    } else {
        try{
            if($id){
                $stmt=$pdo->prepare("UPDATE discount_types SET discount_name=?, discount_percentage=?, description=?, payment_type=?, discount_amount=? WHERE id=?");
                $stmt->execute([$dn,$dp,$el,$dtype,$da,$id]);
            }else{
                $stmt=$pdo->prepare("INSERT INTO discount_types (discount_name,discount_percentage,description,payment_type,discount_amount) VALUES (?,?,?,?,?)");
                $stmt->execute([$dn,$dp,$el,$dtype,$da]);
            }
            header("Location: insurance_discounts.php?msg=saved_discount");exit;
        }catch(PDOException $e){$error="Error: ".$e->getMessage();}
    }
}

if(isset($_GET['msg'])){
    if($_GET['msg']=='saved_insurance')   $success="Insurance saved!";
    if($_GET['msg']=='deleted_insurance') $success="Insurance deleted!";
    if($_GET['msg']=='saved_discount')    $success="Discount saved!";
    if($_GET['msg']=='deleted_discount')  $success="Discount deleted!";
}

try{$insurance_providers=$pdo->query("SELECT * FROM insurance_providers ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);}catch(PDOException $e){$insurance_providers=[];}
try{$discounts=$pdo->query("SELECT * FROM discount_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);}catch(PDOException $e){$discounts=[];}

$edit_insurance=null;if(isset($_GET['edit_insurance'])&&is_numeric($_GET['edit_insurance'])){try{$stmt=$pdo->prepare("SELECT * FROM insurance_providers WHERE id=?");$stmt->execute([$_GET['edit_insurance']]);$edit_insurance=$stmt->fetch(PDO::FETCH_ASSOC);}catch(PDOException $e){}}
$edit_discount=null;if(isset($_GET['edit_discount'])&&is_numeric($_GET['edit_discount'])){try{$stmt=$pdo->prepare("SELECT * FROM discount_types WHERE id=?");$stmt->execute([$_GET['edit_discount']]);$edit_discount=$stmt->fetch(PDO::FETCH_ASSOC);}catch(PDOException $e){}}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Insurance & Discounts - Hospital Billing System</title>
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

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px;transition:all .25s ease}
.user-chip:hover{box-shadow:0 4px 12px rgba(15,118,110,.15);transform:translateY(-1px)}
.user-chip .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;letter-spacing:.5px}
.user-chip .avatar-img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #ccfbf1;flex-shrink:0}
.user-chip .info .name{font-size:13.5px;font-weight:700;color:#0f172a;line-height:1.2;white-space:nowrap}
.user-chip .info .role{font-size:10.5px;color:#0f766e;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;white-space:nowrap}

.alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:12px}
.alert-success{background:#d1fae5;color:#065f46;border-left:5px solid #10b981}
.alert-danger{background:#f8d7da;color:#721c24;border-left:5px solid #dc3545}
.section-title{font-size:18px;font-weight:700;color:#0f172a;margin:30px 0 18px 0;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.section-title:first-of-type{margin-top:0}
.section-title .icon-circle{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;background:#ccfbf1;color:#0f766e;font-size:16px}
.section-title .count-badge{background:#0f766e;color:white;font-size:12px;padding:3px 12px;border-radius:20px;font-weight:600}
.section-actions{margin-left:auto;display:flex;gap:10px}
.btn-add{padding:12px 24px;background:#0f766e;color:white;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 15px rgba(15,118,110,.3);transition:all .25s}
.btn-add:hover{background:#115e59;transform:translateY(-2px)}
.table-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.04);overflow:hidden;margin-bottom:25px;border:1px solid #e2e8f0}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
table thead th{text-align:left;padding:16px 20px;border-bottom:1px solid #f0f0f0;color:#64748b;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc}
table tbody td{padding:16px 20px;border-bottom:1px solid #f5f5f5;color:#334155;vertical-align:middle;font-size:13.5px}
table tbody tr:hover{background:#f0fdfa}
table tbody tr:last-child td{border-bottom:none}
.text-muted{color:#94a3b8;text-align:center;padding:60px 0;font-size:14px}

/* ===== BADGES ===== */
.percent-badge{display:inline-block;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#ccfbf1;color:#0f766e}
.percent-badge.high{background:#d1fae5;color:#065f46}
.percent-badge.medium{background:#fff3cd;color:#856404}
.percent-badge.low{background:#f8d7da;color:#721c24}
.fixed-badge{display:inline-block;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:#dbeafe;color:#1e40af;font-family:'Courier New',monospace;letter-spacing:.3px}
.case-rate-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:10.5px;font-weight:800;background:#fef3c7;color:#92400e;margin-left:6px;text-transform:uppercase;letter-spacing:.4px;border:1px solid #fcd34d}
.type-label{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px}

.action-icons{display:flex;justify-content:center;gap:8px;flex-wrap:wrap}
.icon-btn{background:transparent;border:none;font-size:12.5px;font-weight:600;cursor:pointer;padding:7px 14px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap;line-height:1}
.icon-btn i{font-size:12px}
.icon-btn.edit-icon{color:#0f766e;background:rgba(15,118,110,.08);border:1px solid rgba(15,118,110,.2)}
.icon-btn.edit-icon:hover{color:#fff;background:#0f766e;border-color:#0f766e;transform:translateY(-1px);box-shadow:0 4px 10px rgba(15,118,110,.25)}
.icon-btn.delete-icon{color:#dc3545;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.icon-btn.delete-icon:hover{color:#fff;background:#dc3545;border-color:#dc3545;transform:translateY(-1px);box-shadow:0 4px 10px rgba(220,53,69,.25)}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:1000;justify-content:center;align-items:center;padding:20px}
.modal-overlay.active{display:flex}
.modal{background:white;border-radius:20px;padding:30px 35px;max-width:580px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.25);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.1)}
@keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
#insuranceModal .modal-header, #discountModal .modal-header, #deleteModal .modal-header{margin:-30px -35px 20px -35px;border-radius:20px 20px 0 0;padding:18px 35px;display:flex;justify-content:space-between;align-items:center;background:#ffffff;border-bottom:1px solid #e2e8f0}
#insuranceModal .modal-header h3, #discountModal .modal-header h3, #deleteModal .modal-header h3{font-size:20px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:10px}
#insuranceModal .modal-header h3 i, #discountModal .modal-header h3 i{color:#0f766e}
#deleteModal .modal-header h3 i{color:#dc3545}
.modal-close{background:none;border:none;font-size:28px;color:#94a3b8;cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .25s}
.modal-close:hover{color:#dc2626;background:#fef2f2;transform:rotate(90deg)}
.modal p{font-size:15px;color:#555;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:600;color:#334155;font-size:13px;margin-bottom:5px}
.form-group label .required{color:#dc3545}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#f8fafc;font-family:inherit}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-group textarea{resize:vertical;min-height:60px}
.form-group .hint{font-size:12px;color:#94a3b8;margin-top:5px;line-height:1.5}
.form-group .hint.warn{color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;margin-top:8px}
.form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:20px;padding-top:15px;border-top:1px solid #e9ecef}
.btn{padding:10px 20px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn-success{background:#0f766e;color:white}.btn-success:hover{background:#115e59}
.btn-secondary{background:#6c757d;color:white}.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}.btn-danger:hover{background:#c82333}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .section-title{flex-direction:column;align-items:flex-start}
  .section-actions{margin-left:0;width:100%}
  .btn-add{width:100%;justify-content:center}
  .top-bar{flex-direction:column;align-items:flex-start}
  .user-chip .info{display:none}
  .user-chip{padding:6px}
  .modal{padding:20px;margin:10px}
  .icon-btn{padding:6px 10px;font-size:12px}
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
        <li><a href="insurance_discounts.php" class="active"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
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
        <h1>Insurance &amp; Discounts</h1>
        <div class="page-sub">Manage insurance providers and discount rules. PhilHealth is a <strong>fixed case rate</strong>, not a percentage.</div>
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

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

<!-- ==================== INSURANCE PROVIDERS ==================== -->
<div class="section-title">
    <span class="icon-circle"><i class="fas fa-shield-alt"></i></span>
    Insurance Providers <span class="count-badge"><?= count($insurance_providers) ?></span>
    <div class="section-actions"><button class="btn-add" onclick="openInsuranceModal()"><i class="fas fa-plus"></i> Add Insurance</button></div>
</div>

<div class="table-card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>ID</th><th>Provider Name</th><th>Payment Type</th><th>Coverage</th><th>Description</th><th style="text-align:center;">Actions</th></tr></thead>
            <tbody>
            <?php if (empty($insurance_providers)): ?><tr><td colspan="6" class="text-muted">No insurance providers found.</td></tr>
            <?php else: foreach ($insurance_providers as $ins):
                $is_fixed       = (($ins['payment_type'] ?? 'percent') === 'fixed');
                $is_philhealth  = stripos($ins['provider_name'] ?? '', 'philhealth') !== false;
            ?>
                <tr>
                    <td><strong><?= $ins['id'] ?></strong></td>
                    <td>
                        <strong><?= htmlspecialchars($ins['provider_name']) ?></strong>
                        <?php if ($is_philhealth): ?><span class="case-rate-badge"><i class="fas fa-shield-heart"></i> Case Rate</span><?php endif; ?>
                    </td>
                    <td>
                        <span class="type-label">
                            <?= $is_fixed ? 'Fixed Amount' : 'Percentage' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($is_fixed): ?>
                            <span class="fixed-badge">₱ <?= number_format((float)($ins['coverage_amount'] ?? 0), 2) ?></span>
                        <?php else: ?>
                            <span class="percent-badge <?= $ins['coverage_percentage'] >= 70 ? 'high' : ($ins['coverage_percentage'] >= 40 ? 'medium' : 'low') ?>"><?= $ins['coverage_percentage'] ?>%</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($ins['description'] ?? '-') ?></td>
                    <td><div class="action-icons">
                        <a href="?edit_insurance=<?= $ins['id'] ?>" class="icon-btn edit-icon"><i class="fas fa-pen"></i> Edit</a>
                        <a href="#" onclick="confirmDeleteInsurance(event, <?= $ins['id'] ?>)" class="icon-btn delete-icon"><i class="fas fa-trash"></i> Delete</a>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== DISCOUNTS ==================== -->
<div class="section-title">
    <span class="icon-circle"><i class="fas fa-percent"></i></span>
    Discounts <span class="count-badge"><?= count($discounts) ?></span>
    <div class="section-actions"><button class="btn-add" onclick="openDiscountModal()"><i class="fas fa-plus"></i> Add Discount</button></div>
</div>

<div class="table-card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>ID</th><th>Discount Name</th><th>Payment Type</th><th>Value</th><th>Eligibility</th><th style="text-align:center;">Actions</th></tr></thead>
            <tbody>
            <?php if (empty($discounts)): ?><tr><td colspan="6" class="text-muted">No discounts found.</td></tr>
            <?php else: foreach ($discounts as $disc):
                $is_fixed = (($disc['payment_type'] ?? 'percent') === 'fixed');
            ?>
                <tr>
                    <td><strong><?= $disc['id'] ?></strong></td>
                    <td><strong><?= htmlspecialchars($disc['discount_name']) ?></strong></td>
                    <td><span class="type-label"><?= $is_fixed ? 'Fixed Amount' : 'Percentage' ?></span></td>
                    <td>
                        <?php if ($is_fixed): ?>
                            <span class="fixed-badge">₱ <?= number_format((float)($disc['discount_amount'] ?? 0), 2) ?></span>
                        <?php else: ?>
                            <span class="percent-badge <?= $disc['discount_percentage'] >= 70 ? 'high' : ($disc['discount_percentage'] >= 40 ? 'medium' : 'low') ?>"><?= $disc['discount_percentage'] ?>%</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($disc['description'] ?? '-') ?></td>
                    <td><div class="action-icons">
                        <a href="?edit_discount=<?= $disc['id'] ?>" class="icon-btn edit-icon"><i class="fas fa-pen"></i> Edit</a>
                        <a href="#" onclick="confirmDeleteDiscount(event, <?= $disc['id'] ?>)" class="icon-btn delete-icon"><i class="fas fa-trash"></i> Delete</a>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>

<!-- ==================== INSURANCE MODAL ==================== -->
<div class="modal-overlay" id="insuranceModal">
    <div class="modal">
        <div class="modal-header"><h3><i class="fas fa-shield-alt"></i> <span id="insuranceModalTitle">Add Insurance Provider</span></h3><button class="modal-close" onclick="closeModal('insuranceModal')">&times;</button></div>
        <form method="post">
            <input type="hidden" name="id" id="insuranceId">
            <div class="form-group">
                <label>Provider Name <span class="required">*</span></label>
                <input type="text" id="provider_name" name="provider_name" required
                       oninput="detectPhilHealth(this.value)">
                <div class="hint" id="philhealthHint" style="display:none;">
                    <strong>Detected PhilHealth.</strong> Set Payment Type to <em>Fixed Amount</em> and enter the case rate (e.g. 5000).
                </div>
            </div>
            <div class="form-group">
                <label>Payment Type <span class="required">*</span></label>
                <select name="payment_type" id="payment_type" onchange="togglePaymentFields('insurance')">
                    <option value="percent">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (₱) — Case Rate</option>
                </select>
            </div>
            <div class="form-group" id="insurance_percent_group">
                <label>Coverage Percentage (%) <span class="required">*</span></label>
                <input type="number" id="coverage_percentage" name="coverage_percentage" step="0.01" min="0" max="100" value="0">
            </div>
            <div class="form-group" id="insurance_fixed_group" style="display:none;">
                <label>Fixed Amount / Case Rate (₱) <span class="required">*</span></label>
                <input type="number" id="coverage_amount" name="coverage_amount" step="0.01" min="0" value="0" placeholder="e.g. 5000">
                <div class="hint">Enter the fixed peso amount — for PhilHealth this is the case rate for the patient's diagnosis.</div>
            </div>
            <div class="form-group"><label>Description</label><textarea id="insurance_description" name="description" rows="2"></textarea></div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('insuranceModal')">Cancel</button><button type="submit" name="save_insurance" class="btn btn-success"><i class="fas fa-save"></i> Save Insurance</button></div>
        </form>
    </div>
</div>

<!-- ==================== DISCOUNT MODAL ==================== -->
<div class="modal-overlay" id="discountModal">
    <div class="modal">
        <div class="modal-header"><h3><i class="fas fa-percent"></i> <span id="discountModalTitle">Add Discount</span></h3><button class="modal-close" onclick="closeModal('discountModal')">&times;</button></div>
        <form method="post">
            <input type="hidden" name="id" id="discountId">
            <div class="form-group"><label>Discount Name <span class="required">*</span></label><input type="text" id="discount_name" name="discount_name" required></div>
            <div class="form-group">
                <label>Payment Type <span class="required">*</span></label>
                <select name="discount_payment_type" id="discount_payment_type" onchange="togglePaymentFields('discount')">
                    <option value="percent">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (₱)</option>
                </select>
            </div>
            <div class="form-group" id="discount_percent_group">
                <label>Discount Percentage (%) <span class="required">*</span></label>
                <input type="number" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100" value="0">
            </div>
            <div class="form-group" id="discount_fixed_group" style="display:none;">
                <label>Fixed Discount Amount (₱) <span class="required">*</span></label>
                <input type="number" id="discount_amount" name="discount_amount" step="0.01" min="0" value="0" placeholder="e.g. 500">
            </div>
            <div class="form-group"><label>Eligibility</label><textarea id="eligibility" name="eligibility" rows="2"></textarea></div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('discountModal')">Cancel</button><button type="submit" name="save_discount" class="btn btn-success"><i class="fas fa-save"></i> Save Discount</button></div>
        </form>
    </div>
</div>

<!-- ==================== DELETE MODAL ==================== -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header"><h3><i class="fas fa-trash"></i> Confirm Delete</h3><button class="modal-close" onclick="closeModal('deleteModal')">&times;</button></div>
        <p id="deleteMessage">Are you sure?</p>
        <div class="form-actions" style="justify-content:center;border-top:none;padding-top:0">
            <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
document.addEventListener('DOMContentLoaded',function(){const p=window.location.pathname.toLowerCase();if(p.includes('profile.php')||p.includes('change_password.php'))document.getElementById('settingsMenuItem').classList.add('open');});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.querySelectorAll('.modal-overlay.active').forEach(o=>o.classList.remove('active'));}});

/* ===== PAYMENT TYPE TOGGLE ===== */
function togglePaymentFields(scope) {
    if (scope === 'insurance') {
        const type   = document.getElementById('payment_type').value;
        const pctGrp = document.getElementById('insurance_percent_group');
        const fixGrp = document.getElementById('insurance_fixed_group');
        if (type === 'fixed') {
            pctGrp.style.display = 'none';
            fixGrp.style.display = 'block';
        } else {
            pctGrp.style.display = 'block';
            fixGrp.style.display = 'none';
        }
    } else if (scope === 'discount') {
        const type   = document.getElementById('discount_payment_type').value;
        const pctGrp = document.getElementById('discount_percent_group');
        const fixGrp = document.getElementById('discount_fixed_group');
        if (type === 'fixed') {
            pctGrp.style.display = 'none';
            fixGrp.style.display = 'block';
        } else {
            pctGrp.style.display = 'block';
            fixGrp.style.display = 'none';
        }
    }
}

/* ===== PHILHEALTH AUTO-DETECT ===== */
function detectPhilHealth(name) {
    const hint = document.getElementById('philhealthHint');
    if (!hint) return;
    if (/philhealth/i.test(name)) {
        hint.style.display = 'block';
        const typeSel = document.getElementById('payment_type');
        if (typeSel && typeSel.value !== 'fixed') {
            typeSel.value = 'fixed';
            togglePaymentFields('insurance');
        }
    } else {
        hint.style.display = 'none';
    }
}

/* ===== DELETE ===== */
let deleteUrl='';
function confirmDeleteInsurance(e,id){e.preventDefault();deleteUrl='?delete_insurance='+id;document.getElementById('deleteMessage').textContent='Delete this insurance provider?';document.getElementById('deleteModal').classList.add('active');}
function confirmDeleteDiscount(e,id){e.preventDefault();deleteUrl='?delete_discount='+id;document.getElementById('deleteMessage').textContent='Delete this discount?';document.getElementById('deleteModal').classList.add('active');}
document.getElementById('confirmDeleteBtn').addEventListener('click',function(){if(deleteUrl)window.location.href=deleteUrl;});

/* ===== OPEN MODALS ===== */
function openInsuranceModal(){
    document.getElementById('insuranceModalTitle').textContent='Add Insurance Provider';
    document.querySelector('#insuranceModal form').reset();
    document.getElementById('insuranceId').value='';
    document.getElementById('payment_type').value='percent';
    document.getElementById('philhealthHint').style.display='none';
    togglePaymentFields('insurance');
    document.getElementById('insuranceModal').classList.add('active');
}
function openDiscountModal(){
    document.getElementById('discountModalTitle').textContent='Add Discount';
    document.querySelector('#discountModal form').reset();
    document.getElementById('discountId').value='';
    document.getElementById('discount_payment_type').value='percent';
    togglePaymentFields('discount');
    document.getElementById('discountModal').classList.add('active');
}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('active');}));

<?php if ($edit_insurance): ?>
document.addEventListener('DOMContentLoaded',function(){
    document.getElementById('insuranceModalTitle').textContent='Edit Insurance Provider';
    document.getElementById('insuranceId').value=<?= json_encode($edit_insurance['id']) ?>;
    document.getElementById('provider_name').value=<?= json_encode($edit_insurance['provider_name']) ?>;
    document.getElementById('coverage_percentage').value=<?= json_encode($edit_insurance['coverage_percentage']) ?>;
    document.getElementById('coverage_amount').value=<?= json_encode($edit_insurance['coverage_amount'] ?? 0) ?>;
    document.getElementById('insurance_description').value=<?= json_encode($edit_insurance['description'] ?? '') ?>;
    document.getElementById('payment_type').value=<?= json_encode($edit_insurance['payment_type'] ?? 'percent') ?>;
    detectPhilHealth(<?= json_encode($edit_insurance['provider_name']) ?>);
    togglePaymentFields('insurance');
    document.getElementById('insuranceModal').classList.add('active');
});
<?php endif; ?>

<?php if ($edit_discount): ?>
document.addEventListener('DOMContentLoaded',function(){
    document.getElementById('discountModalTitle').textContent='Edit Discount';
    document.getElementById('discountId').value=<?= json_encode($edit_discount['id']) ?>;
    document.getElementById('discount_name').value=<?= json_encode($edit_discount['discount_name']) ?>;
    document.getElementById('discount_percentage').value=<?= json_encode($edit_discount['discount_percentage']) ?>;
    document.getElementById('discount_amount').value=<?= json_encode($edit_discount['discount_amount'] ?? 0) ?>;
    document.getElementById('eligibility').value=<?= json_encode($edit_discount['description'] ?? '') ?>;
    document.getElementById('discount_payment_type').value=<?= json_encode($edit_discount['payment_type'] ?? 'percent') ?>;
    togglePaymentFields('discount');
    document.getElementById('discountModal').classList.add('active');
});
<?php endif; ?>
</script>
</body>
</html>