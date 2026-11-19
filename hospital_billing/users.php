<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: login.php'); exit; }
require_once 'config.php';

$success=''; $error='';
$user_id=$_SESSION['user_id']??0; $user_name=$_SESSION['username']??'Admin';
$full_name=$user_name; $user_role='Admin'; $profile_picture_url='';
try {
    $s = $pdo->prepare("SELECT full_name, role, profile_picture FROM users WHERE id = ?");
    $s->execute([$user_id]);
    $u = $s->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $full_name = $u['full_name'] ?? $user_name;
        $user_role = $u['role'] ?? 'Admin';
        if (!empty($u['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $u['profile_picture'])) {
            $profile_picture_url = 'uploads/profiles/' . $u['profile_picture'];
        }
    }
} catch (PDOException $e) {}

function buildFullName($f,$m,$l){$p=array_filter([trim($f),trim($m),trim($l)],fn($v)=>$v!=='');return implode(' ',$p);}

/* DELETE */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id === $user_id) { $error = "You cannot delete your own account."; }
    else {
        try { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]); header("Location: users.php?msg=deleted"); exit; }
        catch (PDOException $e) { $error = "Error: ".$e->getMessage(); }
    }
}

/* SAVE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? null;
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $role = $_POST['role'] ?? 'Admin';
    $status = $_POST['status'] ?? 'Active';
    $full_name_built = buildFullName($first_name,$middle_name,$last_name);

    $contact_number = preg_replace('/\D/', '', $contact_number);
    if ($contact_number !== '' && strlen($contact_number) > 11) {
        $contact_number = substr($contact_number, 0, 11);
    }

    if ($first_name==='') $error = "First name is required.";
    elseif ($last_name==='') $error = "Last name is required.";
    elseif ($username==='') $error = "Username is required.";
    elseif ($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email format.";
    elseif ($contact_number !== '' && strlen($contact_number) !== 11) $error = "Contact number must be exactly 11 digits.";
    elseif (!$id && $password==='') $error = "Password is required for new users.";
    else {
        try {
            $checkSql = "SELECT id FROM users WHERE username = ?" . ($id ? " AND id != ?" : "");
            $checkParams = $id ? [$username, $id] : [$username];
            $stmt = $pdo->prepare($checkSql); $stmt->execute($checkParams);
            if ($stmt->fetch()) { $error = "Username is already taken."; }
            else {
                if ($id) {
                    if ($password !== '') {
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?,middle_name=?,last_name=?,full_name=?,username=?,password=?,email=?,contact_number=?,position=?,role=?,status=? WHERE id=?");
                        $stmt->execute([$first_name,$middle_name,$last_name,$full_name_built,$username,password_hash($password, PASSWORD_DEFAULT),$email,$contact_number,$position,$role,$status,$id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET first_name=?,middle_name=?,last_name=?,full_name=?,username=?,email=?,contact_number=?,position=?,role=?,status=? WHERE id=?");
                        $stmt->execute([$first_name,$middle_name,$last_name,$full_name_built,$username,$email,$contact_number,$position,$role,$status,$id]);
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (first_name,middle_name,last_name,full_name,username,password,email,contact_number,position,role,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$first_name,$middle_name,$last_name,$full_name_built,$username,password_hash($password, PASSWORD_DEFAULT),$email,$contact_number,$position,$role,$status]);
                }
                header("Location: users.php?msg=saved"); exit;
            }
        } catch (PDOException $e) { $error = "Error: ".$e->getMessage(); }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg']=='saved') $success = "User saved successfully!";
    if ($_GET['msg']=='deleted') $success = "User deleted successfully!";
}

try { $users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) { $users = []; }
$edit_record = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try { $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$_GET['edit']]); $edit_record = $stmt->fetch(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users - Hospital Billing System</title>
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

/* ===== SIDEBAR ===== */
.sidebar{width:270px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .3s ease;overflow-y:auto;border-right:1px solid rgba(226,232,240,.8)}

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
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:15px}
.section-title{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:600;color:#0f172a}
.section-title i{color:#0f766e;font-size:20px}
.count-badge{background:#0f766e;color:#fff;font-size:12px;font-weight:700;padding:2px 8px;border-radius:50px}
.btn-add{padding:12px 24px;background:#0f766e;color:white;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 15px rgba(15,118,110,.3);transition:all .25s}
.btn-add:hover{background:#115e59;transform:translateY(-2px)}
.table-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.04);overflow:hidden;border:1px solid #e2e8f0}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
table thead th{text-align:left;padding:16px 18px;border-bottom:1px solid #f0f0f0;color:#64748b;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc;white-space:nowrap}
table tbody td{padding:16px 18px;border-bottom:1px solid #f5f5f5;color:#334155;font-size:13.5px;vertical-align:middle}
table tbody tr:hover{background:#f0fdfa}
table tbody tr:last-child td{border-bottom:none}
.text-muted{color:#94a3b8;text-align:center;padding:60px 0;font-size:14px}
.user-cell{display:flex;align-items:center;gap:12px}
.user-cell-avatar{width:38px;height:38px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0;overflow:hidden;border:2px solid #ccfbf1}
.user-cell-avatar.role-admin{background:linear-gradient(135deg,#14b8a6,#0f766e)}
.user-cell-avatar.role-receptionist{background:linear-gradient(135deg,#2dd4bf,#14b8a6)}
.user-cell-avatar.role-billing{background:linear-gradient(135deg,#5eead4,#0d9488)}
.user-cell-avatar img{width:100%;height:100%;object-fit:cover}
.user-cell-name{font-weight:600;color:#0f172a;font-size:13.5px}
.user-cell-username{font-size:12px;color:#64748b;margin-top:2px}
.contact-cell{display:flex;flex-direction:column;gap:3px}
.contact-cell .email{color:#0f766e;font-size:12.5px}
.contact-cell .phone{color:#475569;font-size:12.5px}
.contact-cell .empty{color:#cbd5e1;font-size:12.5px;font-style:italic}
.role-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:600;letter-spacing:.3px}
.role-badge.admin{background:#ccfbf1;color:#0f766e}
.role-badge.receptionist{background:#d1fae5;color:#0d9488}
.role-badge.billing{background:#ccfbf1;color:#115e59}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.status-badge.Active{background:#d1fae5;color:#065f46}
.status-badge.Inactive{background:#f8d7da;color:#721c24}
.status-badge .dot{width:6px;height:6px;border-radius:50%;background:currentColor}

.action-icons{display:flex;justify-content:center;gap:8px;flex-wrap:wrap}
.icon-btn{background:transparent;border:none;font-size:12.5px;font-weight:600;cursor:pointer;padding:7px 14px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap;line-height:1}
.icon-btn i{font-size:12px}
.icon-btn.edit-icon{color:#0f766e;background:rgba(15,118,110,.08);border:1px solid rgba(15,118,110,.2)}
.icon-btn.edit-icon:hover{color:#fff;background:#0f766e;border-color:#0f766e;transform:translateY(-1px);box-shadow:0 4px 10px rgba(15,118,110,.25)}
.icon-btn.delete-icon{color:#dc3545;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.icon-btn.delete-icon:hover{color:#fff;background:#dc3545;border-color:#dc3545;transform:translateY(-1px);box-shadow:0 4px 10px rgba(220,53,69,.25)}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:1000;justify-content:center;align-items:center;padding:20px}
.modal-overlay.active{display:flex}
.modal{background:white;border-radius:20px;padding:30px 35px;max-width:660px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.25);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.1)}
@keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
#formModal .modal-header,#deleteModal .modal-header{margin:-30px -35px 20px -35px;border-radius:20px 20px 0 0;padding:18px 35px;display:flex;justify-content:space-between;align-items:center}
#formModal .modal-header{background:linear-gradient(135deg,#0f766e,#14b8a6)}
#formModal .modal-header h3{color:#fff;font-size:20px;font-weight:700}
#deleteModal .modal-header{background:linear-gradient(135deg,#dc3545,#c82333)}
#deleteModal .modal-header h3{color:#fff;font-size:20px;font-weight:700}
.modal-close{background:none;border:none;font-size:28px;color:rgba(255,255,255,.85);cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .25s}
.modal-close:hover{color:#fff;background:rgba(255,255,255,.2);transform:rotate(90deg)}
.modal p{font-size:15px;color:#555;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:600;color:#334155;font-size:13px;margin-bottom:5px}
.form-group label .required{color:#dc3545}
.form-group label .optional{color:#94a3b8;font-weight:400;font-size:11.5px;margin-left:4px}
.form-group input,.form-group select{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#f8fafc;font-family:inherit}
.form-group input:focus,.form-group select:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.section-divider{font-size:11.5px;font-weight:700;color:#0f766e;text-transform:uppercase;letter-spacing:.8px;padding:10px 0 6px 0;margin-top:4px;border-bottom:2px solid #ccfbf1;margin-bottom:14px}
.form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:20px;padding-top:15px;border-top:1px solid #e9ecef}
.btn{padding:10px 20px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn-success{background:#0f766e;color:white}.btn-success:hover{background:#115e59}
.btn-secondary{background:#6c757d;color:white}.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}.btn-danger:hover{background:#c82333}
.role-hint{margin-top:8px;padding:10px 14px;border-radius:10px;font-size:12px;line-height:1.5;display:flex;align-items:center;gap:8px}
.role-hint.admin{background:#ccfbf1;color:#0f766e}
.role-hint.receptionist{background:#d1fae5;color:#0d9488}
.role-hint.billing{background:#ccfbf1;color:#115e59}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:900px){.form-row,.form-row-3{grid-template-columns:1fr;gap:0}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
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
        <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span>Users</span></a></li>
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
        <h1>Users</h1>
        <div class="page-sub">Manage system users and their access levels.</div>
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
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="section-header">
    <div class="section-title"><i class="fas fa-users"></i> Users <span class="count-badge"><?= count($users) ?></span></div>
    <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add User</button>
</div>

<div class="table-card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>User</th><th>Contact</th><th>Position</th><th>Role</th><th>Status</th><th>Created</th><th style="text-align:center;">Actions</th></tr></thead>
            <tbody>
            <?php if (empty($users)): ?><tr><td colspan="7" class="text-muted">No users found.</td></tr>
            <?php else: foreach ($users as $u):
                $ini = '';
                $parts = preg_split('/\s+/', trim($u['full_name'] ?? ''));
                if (count($parts) >= 2) $ini = strtoupper(substr($parts[0],0,1).substr($parts[count($parts)-1],0,1));
                else $ini = strtoupper(substr($u['full_name'] ?? 'U', 0, 2));
                $role_class = 'admin';
                if ($u['role'] === 'Front Desk Receptionist') $role_class = 'receptionist';
                elseif ($u['role'] === 'Billing') $role_class = 'billing';
                $pic_url = '';
                if (!empty($u['profile_picture']) && file_exists('uploads/profiles/' . $u['profile_picture'])) $pic_url = 'uploads/profiles/' . $u['profile_picture'];
            ?>
                <tr>
                    <td><div class="user-cell">
                        <div class="user-cell-avatar role-<?= $role_class ?>"><?php if ($pic_url): ?><img src="<?= htmlspecialchars($pic_url) ?>?v=<?= time() ?>" alt=""><?php else: ?><?= htmlspecialchars($ini) ?><?php endif; ?></div>
                        <div><div class="user-cell-name"><?= htmlspecialchars($u['full_name']) ?></div><div class="user-cell-username">@<?= htmlspecialchars($u['username']) ?></div></div>
                    </div></td>
                    <td><div class="contact-cell">
                        <?php if (!empty($u['email'])): ?><span class="email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($u['email']) ?></span><?php endif; ?>
                        <?php if (!empty($u['contact_number'])): ?><span class="phone"><i class="fas fa-phone"></i> <?= htmlspecialchars($u['contact_number']) ?></span><?php endif; ?>
                        <?php if (empty($u['email']) && empty($u['contact_number'])): ?><span class="empty">No contact info</span><?php endif; ?>
                    </div></td>
                    <td><?= !empty($u['position']) ? htmlspecialchars($u['position']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
                    <td><span class="role-badge <?= $role_class ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td><span class="status-badge <?= htmlspecialchars($u['status'] ?? 'Active') ?>"><span class="dot"></span><?= htmlspecialchars($u['status'] ?? 'Active') ?></span></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td><div class="action-icons">
                        <a href="?edit=<?= $u['id'] ?>" class="icon-btn edit-icon"><i class="fas fa-pen"></i> Edit</a>
                        <?php if ($u['id'] != $user_id): ?><a href="#" onclick="confirmDelete(event, <?= $u['id'] ?>)" class="icon-btn delete-icon"><i class="fas fa-trash"></i> Delete</a><?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>

<div class="modal-overlay" id="formModal">
    <div class="modal">
        <div class="modal-header"><h3><i class="fas fa-user"></i> <span id="modalTitle">Add User</span></h3><button class="modal-close" onclick="closeModal('formModal')">&times;</button></div>
        <form method="post">
            <input type="hidden" name="id" id="recordId">
            <div class="section-divider"><i class="fas fa-id-card"></i> Personal Information</div>
            <div class="form-row-3">
                <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" id="first_name" name="first_name" required></div>
                <div class="form-group"><label>Middle Name <span class="optional">(optional)</span></label><input type="text" id="middle_name" name="middle_name"></div>
                <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" id="last_name" name="last_name" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Username <span class="required">*</span></label><input type="text" id="username" name="username" required></div>
                <div class="form-group"><label>Password <span id="pwdRequired" class="required">*</span></label><input type="password" id="password" name="password"><div class="hint" id="pwdHint">Required for new users.</div></div>
            </div>
            <div class="section-divider"><i class="fas fa-address-card"></i> Contact Details</div>
            <div class="form-row">
                <div class="form-group"><label>Email Address</label><input type="email" id="email" name="email"></div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text"
                           id="contact_number"
                           name="contact_number"
                           placeholder="e.g. 09171234567"
                           maxlength="11"
                           inputmode="numeric"
                           pattern="[0-9]{11}"
                           oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,11);">
                    <div class="hint">Numbers only — exactly 11 digits (e.g. 09171234567)</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Position / Department</label><input type="text" id="position" name="position"></div>
                <div class="form-group"><label>Status <span class="required">*</span></label>
                    <select id="status" name="status" required><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
            </div>
            <div class="section-divider"><i class="fas fa-user-tag"></i> Access Level</div>
            <div class="form-group">
                <label>Role <span class="required">*</span></label>
                <select id="role" name="role" required onchange="updateRoleHint()">
                    <option value="Admin">Admin</option>
                    <option value="Front Desk Receptionist">Front Desk Receptionist</option>
                    <option value="Billing">Billing</option>
                </select>
                <div class="role-hint admin" id="roleHint"><i class="fas fa-info-circle"></i><span>Admin users have full access.</span></div>
            </div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('formModal')">Cancel</button><button type="submit" name="save_user" class="btn btn-success"><i class="fas fa-save"></i> Save User</button></div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header"><h3><i class="fas fa-trash"></i> Confirm Delete</h3><button class="modal-close" onclick="closeModal('deleteModal')">&times;</button></div>
        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
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

const roleHints={'Admin':{cls:'admin',msg:'Admin users have full access.'},'Front Desk Receptionist':{cls:'receptionist',msg:'Receptionists handle patient registration and visit creation.'},'Billing':{cls:'billing',msg:'Billing staff process patient visits and generate billing records.'}};
function updateRoleHint(){const r=document.getElementById('role').value;const h=document.getElementById('roleHint');if(roleHints[r]){h.className='role-hint '+roleHints[r].cls;h.innerHTML='<i class="fas fa-info-circle"></i><span>'+roleHints[r].msg+'</span>';}}
function openAddModal(){
    document.getElementById('modalTitle').textContent='Add User';
    document.querySelector('#formModal form').reset();
    document.getElementById('recordId').value='';
    document.getElementById('pwdRequired').style.display='inline';
    document.getElementById('pwdHint').textContent='Required for new users.';
    document.getElementById('role').value='Admin';
    document.getElementById('contact_number').value='';
    updateRoleHint();
    document.getElementById('formModal').classList.add('active');
}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('active');}));
let deleteId=null;
function confirmDelete(e,id){e.preventDefault();deleteId=id;document.getElementById('deleteModal').classList.add('active');}
document.getElementById('confirmDeleteBtn').addEventListener('click',function(){if(deleteId)window.location.href='?delete='+deleteId;});
<?php if ($edit_record): ?>
document.addEventListener('DOMContentLoaded',function(){
    document.getElementById('modalTitle').textContent='Edit User';
    document.getElementById('recordId').value=<?= json_encode($edit_record['id']) ?>;
    document.getElementById('first_name').value=<?= json_encode($edit_record['first_name'] ?? '') ?>;
    document.getElementById('middle_name').value=<?= json_encode($edit_record['middle_name'] ?? '') ?>;
    document.getElementById('last_name').value=<?= json_encode($edit_record['last_name'] ?? '') ?>;
    document.getElementById('username').value=<?= json_encode($edit_record['username'] ?? '') ?>;
    document.getElementById('email').value=<?= json_encode($edit_record['email'] ?? '') ?>;
    document.getElementById('contact_number').value=<?= json_encode($edit_record['contact_number'] ?? '') ?>;
    document.getElementById('position').value=<?= json_encode($edit_record['position'] ?? '') ?>;
    document.getElementById('role').value=<?= json_encode($edit_record['role'] ?? 'Admin') ?>;
    document.getElementById('status').value=<?= json_encode($edit_record['status'] ?? 'Active') ?>;
    document.getElementById('pwdRequired').style.display='none';
    document.getElementById('pwdHint').textContent='Leave blank to keep current password.';
    updateRoleHint();
    document.getElementById('formModal').classList.add('active');
});
<?php endif; ?>
</script>
</body>
</html>