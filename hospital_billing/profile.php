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

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $full_name_built = trim($first_name.' '.($middle_name!==''?$middle_name.' ':'').$last_name);

    $contact_number = preg_replace('/\D/', '', $contact_number);
    if ($contact_number !== '' && strlen($contact_number) > 11) {
        $contact_number = substr($contact_number, 0, 11);
    }

    if ($first_name==='') $error = "First name is required.";
    elseif ($last_name==='') $error = "Last name is required.";
    elseif ($username==='') $error = "Username is required.";
    elseif ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) $error = "Invalid email format.";
    elseif ($contact_number !== '' && strlen($contact_number) !== 11) $error = "Contact number must be exactly 11 digits.";
    else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND id!=? LIMIT 1");
            $stmt->execute([$username,$user_id]);
            if ($stmt->fetch()) $error = "Username is already taken.";
            else {
                $stmt = $pdo->prepare("UPDATE users SET first_name=?,middle_name=?,last_name=?,full_name=?,username=?,email=?,contact_number=?,position=? WHERE id=?");
                $stmt->execute([$first_name,$middle_name,$last_name,$full_name_built,$username,$email,$contact_number,$position,$user_id]);
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name_built;
                $success = "Profile updated successfully!";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $full_name = $user['full_name'];
            }
        } catch (PDOException $e) { $error = "Error: ".$e->getMessage(); }
    }
}

if (empty($user['first_name']) && empty($user['last_name']) && !empty($user['full_name'])) {
    $parts = preg_split('/\s+/', trim($user['full_name']));
    if (count($parts)>=3) { $user['first_name']=$parts[0]; $user['middle_name']=$parts[1]; $user['last_name']=implode(' ',array_slice($parts,2)); }
    elseif (count($parts)===2) { $user['first_name']=$parts[0]; $user['last_name']=$parts[1]; }
    else { $user['first_name']=$user['full_name']; }
}

$initials = '';
$parts = preg_split('/\s+/', trim($user['full_name'] ?? ''));
if (count($parts)>=2) $initials = strtoupper(substr($parts[0],0,1).substr($parts[count($parts)-1],0,1));
else $initials = strtoupper(substr($user['full_name'] ?? 'U',0,2));

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
<title>My Profile - Hospital Billing System</title>
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
.card-header{background:linear-gradient(135deg,<?= $accent ?> 0%,<?= $accent_light ?> 100%);padding:35px 40px;position:relative;overflow:hidden;display:flex;align-items:center;border-radius:16px 16px 0 0}
.profile-header{display:flex;align-items:center;gap:24px;position:relative;z-index:1}
.profile-avatar{width:100px;height:100px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:700;color:<?= $accent_light ?>;flex-shrink:0;border:4px solid rgba(255,255,255,.5);box-shadow:0 8px 24px rgba(0,0,0,.2);letter-spacing:1px}
.profile-info{flex:1;min-width:0}
.profile-info h2{font-size:26px;color:#fff;margin-bottom:8px;font-weight:700;line-height:1.2}
.info-row{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.9);font-size:13.5px;margin-top:5px}
.info-row i{font-size:12px;width:16px;text-align:center;opacity:.9}
.card-body{padding:35px 40px 40px 40px}
.section-heading{font-size:15px;font-weight:700;color:#0f172a;margin:0 0 22px 0;padding-bottom:12px;border-bottom:1.5px solid #f0fdfa}
.section-heading:not(:first-child){margin-top:30px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px 24px}
.form-group{display:flex;flex-direction:column}
.form-group.full{grid-column:1 / -1}
.form-group label{font-weight:600;color:#334155;font-size:13px;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between}
.form-group label .required{color:#dc3545;margin-left:3px}
.form-group label .optional{color:#94a3b8;font-weight:400;font-size:11.5px}
.form-group input{width:100%;padding:13px 16px;border:1.5px solid #e2e8f0;border-radius:30px;font-size:14px;background:#f8fafc;font-family:inherit;color:#0f172a}
.form-group input::placeholder{color:#cbd5e1}
.form-group input:focus{border-color:<?= $accent_light ?>;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-group .hint{font-size:11.5px;color:#94a3b8;margin-top:6px;padding-left:6px}
.form-actions{display:flex;gap:12px;margin-top:30px;padding-top:25px;border-top:1px solid #f0fdfa;flex-wrap:wrap}
.btn{padding:13px 26px;border:none;border-radius:30px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:9px;font-family:inherit;transition:all .2s}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 14px rgba(15,118,110,.30)}
.btn-primary:hover{background:#115e59;transform:translateY(-2px)}
.btn-password{background:#f59e0b;color:#fff;box-shadow:0 4px 14px rgba(245,158,11,.30)}
.btn-password:hover{background:#d97706;transform:translateY(-2px)}
.btn-cancel{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-cancel:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:900px){.form-grid{grid-template-columns:1fr;gap:18px}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .card-header{padding:25px}
  .card-body{padding:25px}
  .profile-header{flex-direction:column;text-align:center;gap:16px}
  .profile-avatar{width:90px;height:90px;font-size:30px}
  .profile-info h2{font-size:22px}
  .info-row{justify-content:center}
  .form-actions{flex-direction:column-reverse}
  .form-actions .btn{width:100%;justify-content:center}
  .top-bar{flex-direction:column;align-items:flex-start}
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
                <li><a href="profile.php" class="active"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> <span>Change Password</span></a></li>
            </ul>
        </li>
    </ul>
    <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></div>
</aside>

<main class="main-content">

<div class="top-bar">
    <div class="top-bar-left">
        <h1>My Profile</h1>
        <div class="page-sub">Update your personal information.</div>
    </div>
    <div class="top-bar-actions">
        <div class="user-chip">
            <div class="avatar"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
            <div class="info">
                <div class="name"><?= htmlspecialchars($full_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="profile-header">
            <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                <div class="info-row"><i class="fas fa-at"></i> <span><?= htmlspecialchars($user['username']) ?></span></div>
                <div class="info-row"><i class="fas fa-user-tag"></i> <span><?= htmlspecialchars($user_role) ?></span></div>
                <?php if (!empty($user['position'])): ?><div class="info-row"><i class="fas fa-briefcase"></i> <span><?= htmlspecialchars($user['position']) ?></span></div><?php endif; ?>
                <?php if (!empty($user['email'])): ?><div class="info-row"><i class="fas fa-envelope"></i> <span><?= htmlspecialchars($user['email']) ?></span></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="section-heading">Profile Information</div>
            <div class="form-grid">
                <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" placeholder="Enter first name" required></div>
                <div class="form-group"><label>Middle Name <span class="optional">Optional</span></label><input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>" placeholder="Enter middle name (optional)"></div>
                <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" placeholder="Enter last name" required></div>
                <div class="form-group"><label>Username <span class="required">*</span></label><input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="Enter username" required></div>
                <div class="form-group full"><label>Email Address <span class="optional">Optional</span></label><input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Enter email address"></div>
            </div>
            <div class="section-heading">Work & Contact</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Contact Number <span class="optional">Optional</span></label>
                    <input type="text"
                           name="contact_number"
                           value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
                           placeholder="e.g. 09171234567"
                           maxlength="11"
                           inputmode="numeric"
                           pattern="[0-9]{11}"
                           oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,11);">
                </div>
                <div class="form-group"><label>Position / Department <span class="optional">Optional</span></label><input type="text" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>" placeholder="Enter position"></div>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="change_password.php" class="btn btn-password"><i class="fas fa-key"></i> Change Password</a>
                <a href="<?= $dash_url ?>" class="btn btn-cancel"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
</script>
</body>
</html>