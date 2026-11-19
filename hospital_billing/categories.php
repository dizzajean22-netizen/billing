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

/* DELETE with guard */
if(isset($_GET['delete'])&&is_numeric($_GET['delete'])){
    $del_id = (int)$_GET['delete'];
    try{
        $c = $pdo->prepare("SELECT category_name FROM billing_categories WHERE id=?");
        $c->execute([$del_id]);
        $cat_name = $c->fetchColumn();
        if ($cat_name === false) { $error = "Category not found."; }
        else {
            $c1 = $pdo->prepare("SELECT COUNT(*) FROM billing_records WHERE category = ?");
            $c1->execute([$cat_name]); $used_bills = (int)$c1->fetchColumn();
            $c2 = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?");
            $c2->execute([$del_id]); $used_services = (int)$c2->fetchColumn();
            if ($used_bills > 0 || $used_services > 0) {
                $error = "Cannot delete this category. Used in {$used_bills} billing record(s) and {$used_services} service(s).";
            } else {
                $pdo->prepare("DELETE FROM billing_categories WHERE id=?")->execute([$del_id]);
                header("Location: categories.php?msg=deleted"); exit;
            }
        }
    } catch(PDOException $e){ $error="Error: ".$e->getMessage(); }
}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_category'])){
    $id=$_POST['id']??null;$cn=trim($_POST['category_name']??'');$cc=trim($_POST['category_code']??'');$d=trim($_POST['description']??'');$co=trim($_POST['color']??'#0f766e');
    if($cn==='')$error="Category Name is required.";elseif($cc==='')$error="Category Code is required.";
    else{try{if($id){$stmt=$pdo->prepare("UPDATE billing_categories SET category_name=?,category_code=?,description=?,color=? WHERE id=?");$stmt->execute([$cn,$cc,$d,$co,$id]);}else{$stmt=$pdo->prepare("INSERT INTO billing_categories (category_name,category_code,description,color) VALUES (?,?,?,?)");$stmt->execute([$cn,$cc,$d,$co]);}header("Location: categories.php?msg=saved");exit;}catch(PDOException $e){$error="Error: ".$e->getMessage();}}
}
if(isset($_GET['msg'])){if($_GET['msg']=='saved')$success="Category saved!";if($_GET['msg']=='deleted')$success="Category deleted!";}

try{$categories=$pdo->query("SELECT * FROM billing_categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);}catch(PDOException $e){$categories=[];}

$total_categories = count($categories);

$edit_record=null;if(isset($_GET['edit'])&&is_numeric($_GET['edit'])){try{$stmt=$pdo->prepare("SELECT * FROM billing_categories WHERE id=?");$stmt->execute([$_GET['edit']]);$edit_record=$stmt->fetch(PDO::FETCH_ASSOC);}catch(PDOException $e){}}

if (!function_exists('cat_icon')) {
    function cat_icon($code_or_name) {
        $k = strtoupper(trim($code_or_name));
        $map = ['LAB'=>'fa-flask','DIAG'=>'fa-x-ray','PHARM'=>'fa-pills','CONS'=>'fa-user-doctor','ROOM'=>'fa-bed','SURG'=>'fa-syringe','MISC'=>'fa-ellipsis'];
        if (isset($map[$k])) return $map[$k];
        $n = strtolower($code_or_name);
        if (strpos($n,'lab')!==false) return 'fa-flask';
        if (strpos($n,'diag')!==false || strpos($n,'imag')!==false) return 'fa-x-ray';
        if (strpos($n,'pharm')!==false || strpos($n,'medic')!==false) return 'fa-pills';
        if (strpos($n,'cons')!==false) return 'fa-user-doctor';
        if (strpos($n,'room')!==false) return 'fa-bed';
        return 'fa-tag';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categories - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ===== BODY: soft teal mesh gradient background (sama seperti login.php) ===== */
body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex;min-height:100vh;color:#0f172a;
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

.sidebar{width:270px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);display:flex;flex-direction:column;padding:25px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid rgba(226,232,240,.8)}

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
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .2s}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f0fdfa;color:#0f766e}
.sidebar-menu li a:hover i{color:#0f766e}
.sidebar-menu li a.active{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-menu li.has-submenu>a .submenu-arrow{margin-left:auto;font-size:11px;width:auto;transition:transform .3s;color:#94a3b8}
.sidebar-menu li.has-submenu.open>a .submenu-arrow{transform:rotate(180deg)}
.submenu{list-style:none;max-height:0;overflow:hidden;transition:max-height .35s;padding:0;margin:4px 0}
.sidebar-menu li.has-submenu.open .submenu{max-height:220px}
.submenu li{margin-bottom:2px}
.submenu li a{padding:10px 16px 10px 52px;font-size:13px;color:#64748b;background:#f8fafc;border-radius:8px;display:flex;align-items:center;gap:10px}
.submenu li a i{width:16px;font-size:13px}
.submenu li a:hover{background:#f0fdfa;color:#0f766e}
.sidebar-footer{padding:15px 14px 0 14px;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}

.main-content{margin-left:270px;flex:1;padding:30px 40px 60px 40px;min-height:100vh;position:relative;z-index:1}
.top-bar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;gap:16px;flex-wrap:wrap}
.top-bar h1{font-size:22px;font-weight:700;color:#0f172a}
.top-bar p{font-size:13.5px;color:#64748b;margin-top:4px}
.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#fff;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0}
.user-chip .avatar-img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #ccfbf1}
.user-chip .info .name{font-size:13px;font-weight:700;color:#0f172a}
.user-chip .info .role{font-size:10.5px;color:#0f766e;font-weight:700;text-transform:uppercase;margin-top:2px}

.page-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px 32px 32px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.page-head h2{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:4px}
.page-head p{font-size:13px;color:#64748b}
.btn-dark{background:#0f172a;color:#fff;border:none;padding:11px 20px;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;font-family:inherit;box-shadow:0 4px 12px rgba(15,23,42,.15)}
.btn-dark:hover{background:#1e293b;transform:translateY(-1px)}

.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding:18px 0 22px;border-bottom:1px solid #f1f5f9;margin-bottom:22px}
.stat-item .stat-num{font-size:26px;font-weight:800;color:#0f172a;line-height:1.1}
.stat-item .stat-lbl{font-size:12px;color:#64748b;font-weight:500;margin-top:4px}

.filter-row{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.search-wrap{position:relative;flex:1;min-width:240px}
.search-wrap i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px}
.search-wrap input{width:100%;padding:12px 16px 12px 44px;border:1px solid #e2e8f0;border-radius:11px;font-size:13.5px;font-family:inherit;background:#fff;transition:all .2s}
.search-wrap input:focus{outline:none;border-color:#14b8a6;box-shadow:0 0 0 4px rgba(20,184,166,.1)}

.table-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13.5px}
table thead th{text-align:left;padding:14px 20px;border-bottom:1px solid #f1f5f9;color:#64748b;font-weight:700;font-size:10.5px;text-transform:uppercase;letter-spacing:.5px;background:#fafbfc;white-space:nowrap}
table tbody td{padding:14px 20px;border-bottom:1px solid #f8fafc;color:#334155;vertical-align:middle}
table tbody tr:hover{background:#fafbfc}
table tbody tr:last-child td{border-bottom:none}
.text-muted{color:#94a3b8;text-align:center;padding:60px 0;font-size:14px}

.cat-cell{display:flex;align-items:center;gap:12px}
.cat-cell-icon{width:40px;height:40px;border-radius:10px;background:#f0fdfa;color:#0f766e;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;border:1px solid #ccfbf1}
.cat-cell-name{font-weight:700;color:#0f172a;font-size:13.5px}
.cat-cell-desc{font-size:11.5px;color:#94a3b8;margin-top:2px}

.code-badge{background:#f1f5f9;color:#475569;font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:6px;letter-spacing:.5px;font-family:'Courier New',monospace;display:inline-block}
.color-display{display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;font-family:'Courier New',monospace}
.color-dot{display:inline-block;width:18px;height:18px;border-radius:50%;border:2px solid #e2e8f0;flex-shrink:0}

.action-icons{display:flex;justify-content:center;gap:8px;flex-wrap:wrap}
.icon-btn{background:transparent;border:none;font-size:12.5px;font-weight:700;cursor:pointer;padding:7px 14px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap;line-height:1}
.icon-btn i{font-size:12px}
.icon-btn.edit-icon{color:#0f766e;background:rgba(15,118,110,.08);border:1px solid rgba(15,118,110,.2)}
.icon-btn.edit-icon:hover{color:#fff;background:#0f766e;border-color:#0f766e;transform:translateY(-1px);box-shadow:0 4px 10px rgba(15,118,110,.25)}
.icon-btn.delete-icon{color:#dc3545;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.icon-btn.delete-icon:hover{color:#fff;background:#dc3545;border-color:#dc3545;transform:translateY(-1px);box-shadow:0 4px 10px rgba(220,53,69,.25)}
.icon-btn.delete-icon.disabled{opacity:.4;cursor:not-allowed;pointer-events:none}

.empty-box{padding:60px 20px;text-align:center;color:#94a3b8}
.empty-box i{font-size:42px;color:#cbd5e1;margin-bottom:12px;display:block}
.empty-box strong{color:#475569;display:block;font-size:15px;margin-bottom:6px}
.empty-box a{color:#0f766e;font-weight:700;text-decoration:none;font-size:13px}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:1000;justify-content:center;align-items:center;padding:20px}
.modal-overlay.active{display:flex}
.modal{background:#fff;border-radius:20px;padding:30px 35px;max-width:580px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.25);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.1)}
@keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}

#formModal .modal-header, #deleteModal .modal-header {
    margin: -30px -35px 20px -35px;
    border-radius: 20px 20px 0 0;
    padding: 18px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}
#formModal .modal-header h3, #deleteModal .modal-header h3 {
    font-size: 19px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}
#formModal .modal-header h3 i {
    color: #0f766e;
}
#deleteModal .modal-header h3 i {
    color: #dc3545;
}

.modal-close{background:none;border:none;font-size:26px;color:#94a3b8;cursor:pointer;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .25s}
.modal-close:hover{color:#dc2626;background:#fef2f2;transform:rotate(90deg)}

.modal p{font-size:14.5px;color:#555;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:700;color:#334155;font-size:13px;margin-bottom:5px}
.form-group label .required{color:#dc3545}
.form-group input,.form-group textarea{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#f8fafc;font-family:inherit}
.form-group input:focus,.form-group textarea:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-group textarea{resize:vertical;min-height:60px}
.form-group input[type=color]{padding:4px;height:50px;cursor:pointer}
.color-preview-wrapper{display:flex;align-items:center;gap:12px;margin-top:6px;padding:8px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0}
.color-preview-wrapper .color-hex{font-size:14px;color:#334155;font-weight:600;font-family:'Courier New',monospace}
.color-preview-wrapper .color-dot{width:28px;height:28px;border-radius:50%;border:3px solid #e2e8f0}
.form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:20px;padding-top:15px;border-top:1px solid #e9ecef}
.btn{padding:10px 20px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-family:inherit}
.btn-success{background:#0f766e;color:#fff}.btn-success:hover{background:#115e59}
.btn-secondary{background:#6c757d;color:#fff}.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:#fff}.btn-danger:hover{background:#c82333}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:380px;width:calc(100% - 48px);pointer-events:none}
.toast{background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(15,23,42,.18);border:1px solid #e2e8f0;padding:16px 18px;display:flex;gap:14px;align-items:flex-start;pointer-events:auto;animation:toastIn .4s cubic-bezier(.2,.9,.3,1.1);position:relative;overflow:hidden}
.toast::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--toast-accent,#14b8a6)}
.toast.closing{animation:toastOut .3s ease forwards}
.toast .toast-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:16px;background:var(--toast-accent,#14b8a6)}
.toast .toast-body{flex:1;min-width:0}
.toast .toast-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.toast .toast-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:6px}
.toast .toast-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.toast .toast-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:4px;border-radius:6px}
.toast .toast-close:hover{color:#dc2626;background:#fef2f2}
@keyframes toastIn{from{opacity:0;transform:translateX(120%)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(120%)}}

@media(max-width:900px){.stats-row{grid-template-columns:repeat(3,1fr);gap:12px}.stat-item .stat-num{font-size:22px}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 40px}
  .page-card{padding:22px 18px}
  .top-bar,.page-head{flex-direction:column;align-items:stretch}
  .user-chip .info{display:none}.user-chip{padding:6px}
  .modal{padding:22px;margin:10px}
  table{font-size:12.5px;min-width:640px}
  thead th,tbody td{padding:11px 14px}
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
        <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
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
    <div>
        <h1>Categories</h1>
        <p>Manage billing categories used by the Rule-Based Decision Algorithm.</p>
    </div>
    <div class="user-chip">
        <?php if (!empty($profile_picture_url)): ?>
            <img src="<?= htmlspecialchars($profile_picture_url) ?>?v=<?= time() ?>" alt="" class="avatar-img">
        <?php else: ?>
            <div class="avatar"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
        <?php endif; ?>
        <div class="info">
            <div class="name"><?= htmlspecialchars($full_name) ?></div>
            <div class="role"><?= htmlspecialchars($user_role) ?></div>
        </div>
    </div>
</div>

<div class="page-card">

    <div class="page-head">
        <div>
            <h2>Categories</h2>
            <p>Manage billing categories used by the Rule-Based Decision Algorithm.</p>
        </div>
        <button class="btn-dark" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Category</button>
    </div>

    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-num"><?= $total_categories ?></div>
            <div class="stat-lbl">Categories</div>
        </div>
    </div>

    <div class="filter-row">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="catSearch" placeholder="Search categories or codes..." oninput="filterCats(this.value)">
        </div>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
            <table id="catTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Code</th>
                        <th>Color</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4" class="text-muted">No categories found. Add your first category!</td></tr>
                <?php else: foreach ($categories as $cat):
                    $icon = cat_icon($cat['category_code'] ?: $cat['category_name']);
                    $search_key = strtolower(($cat['category_name'] ?? '') . ' ' . ($cat['category_code'] ?? ''));
                ?>
                    <tr data-search="<?= htmlspecialchars($search_key) ?>">
                        <td>
                            <div class="cat-cell">
                                <div class="cat-cell-icon"><i class="fas <?= $icon ?>"></i></div>
                                <div>
                                    <div class="cat-cell-name"><?= htmlspecialchars($cat['category_name']) ?></div>
                                    <?php if (!empty($cat['description'])): ?>
                                        <div class="cat-cell-desc"><?= htmlspecialchars(mb_strimwidth($cat['description'], 0, 60, '…')) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="code-badge"><?= htmlspecialchars($cat['category_code']) ?></span></td>
                        <td>
                            <div class="color-display">
                                <span class="color-dot" style="background:<?= htmlspecialchars($cat['color'] ?? '#0f766e') ?>;"></span>
                                <span><?= htmlspecialchars($cat['color'] ?? '#0f766e') ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="action-icons">
                                <a href="?edit=<?= $cat['id'] ?>" class="icon-btn edit-icon"><i class="fas fa-pen"></i> Edit</a>
                                <a href="#" onclick="confirmDelete(event, <?= $cat['id'] ?>)" class="icon-btn delete-icon"><i class="fas fa-trash"></i> Delete</a>
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

<div class="modal-overlay" id="formModal">
    <div class="modal">
        <div class="modal-header"><h3><i class="fas fa-tag"></i> <span id="modalTitle">Add New Category</span></h3><button class="modal-close" onclick="closeModal('formModal')">&times;</button></div>
        <form method="post" id="categoryForm">
            <input type="hidden" name="id" id="recordId">
            <div class="form-group"><label>Category Name <span class="required">*</span></label><input type="text" id="category_name" name="category_name" required></div>
            <div class="form-group"><label>Category Code <span class="required">*</span></label><input type="text" id="category_code" name="category_code" required></div>
            <div class="form-group"><label>Description</label><textarea id="description" name="description" rows="2"></textarea></div>
            <div class="form-group"><label>Color</label><input type="color" id="color" name="color" value="#0f766e">
                <div class="color-preview-wrapper"><span class="color-dot" id="colorDotPreview" style="background:#0f766e;border-color:#0f766e;"></span><span class="color-hex" id="colorHexDisplay">#0f766e</span></div>
            </div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('formModal')">Cancel</button><button type="submit" name="save_category" class="btn btn-success"><i class="fas fa-save"></i> Save Category</button></div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header"><h3><i class="fas fa-trash"></i> Confirm Delete</h3><button class="modal-close" onclick="closeModal('deleteModal')">&times;</button></div>
        <p>Are you sure you want to permanently delete this category?</p>
        <div class="form-actions" style="justify-content:center;border-top:none;padding-top:0">
            <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
document.addEventListener('DOMContentLoaded',function(){const p=window.location.pathname.toLowerCase();if(p.includes('profile.php')||p.includes('change_password.php'))document.getElementById('settingsMenuItem').classList.add('open');});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.querySelectorAll('.modal-overlay.active').forEach(o=>o.classList.remove('active'));}});

function filterCats(q){
    q = (q||'').trim().toLowerCase();
    document.querySelectorAll('#catTable tbody tr').forEach(row => {
        const hay = row.getAttribute('data-search') || '';
        row.style.display = (q === '' || hay.includes(q)) ? '' : 'none';
    });
}

const categoryColors={'Laboratory':'#28a745','Diagnostic':'#ffc107','Pharmacy':'#007bff','Consultation':'#6f42c1','Surgical':'#dc3545','Room':'#fd7e14','Miscellaneous':'#6c757d','Emergency':'#e74c3c','Therapy':'#1abc9c','Nursing':'#3498db','Medication':'#2ecc71','Supply':'#f39c12'};
document.addEventListener('DOMContentLoaded',function(){
    const ni=document.getElementById('category_name'),ci=document.getElementById('color'),hd=document.getElementById('colorHexDisplay'),dp=document.getElementById('colorDotPreview');
    if(ci)ci.addEventListener('input',function(){hd.textContent=this.value;dp.style.background=this.value;dp.style.borderColor=this.value;});
    if(ni)ni.addEventListener('input',function(){const name=this.value.trim();for(const[k,c]of Object.entries(categoryColors)){if(name.toLowerCase().includes(k.toLowerCase())){ci.value=c;hd.textContent=c;dp.style.background=c;dp.style.borderColor=c;break;}}});
});
function openAddModal(){document.getElementById('modalTitle').textContent='Add New Category';document.getElementById('categoryForm').reset();document.getElementById('recordId').value='';const d='#0f766e';document.getElementById('color').value=d;document.getElementById('colorHexDisplay').textContent=d;document.getElementById('colorDotPreview').style.background=d;document.getElementById('colorDotPreview').style.borderColor=d;document.getElementById('formModal').classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('active');}));
let deleteId=null;
function confirmDelete(e,id){e.preventDefault();deleteId=id;document.getElementById('deleteModal').classList.add('active');}
document.getElementById('confirmDeleteBtn').addEventListener('click',function(){if(deleteId)window.location.href='?delete='+deleteId;});

function showToast(title, message, icon, color, duration){
    duration = duration || 5000;
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.setProperty('--toast-accent', color || '#14b8a6');
    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icon || 'fa-bell'}"></i></div>
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

<?php if ($success): ?>
document.addEventListener('DOMContentLoaded', function(){ setTimeout(() => showToast('Success', <?= json_encode($success) ?>, 'fa-circle-check', '#10b981', 4500), 300); });
<?php endif; ?>
<?php if ($error): ?>
document.addEventListener('DOMContentLoaded', function(){ setTimeout(() => showToast('Error', <?= json_encode(strip_tags($error)) ?>, 'fa-circle-exclamation', '#dc2626', 6000), 300); });
<?php endif; ?>

<?php if ($edit_record): ?>
document.addEventListener('DOMContentLoaded',function(){
    document.getElementById('modalTitle').textContent='Edit Category';
    document.getElementById('recordId').value=<?= json_encode($edit_record['id']) ?>;
    document.getElementById('category_name').value=<?= json_encode($edit_record['category_name']) ?>;
    document.getElementById('category_code').value=<?= json_encode($edit_record['category_code']) ?>;
    document.getElementById('description').value=<?= json_encode($edit_record['description'] ?? '') ?>;
    const color=<?= json_encode($edit_record['color'] ?? '#0f766e') ?>;
    document.getElementById('color').value=color;
    document.getElementById('colorHexDisplay').textContent=color;
    document.getElementById('colorDotPreview').style.background=color;
    document.getElementById('colorDotPreview').style.borderColor=color;
    document.getElementById('formModal').classList.add('active');
});
<?php endif; ?>
</script>
</body>
</html>