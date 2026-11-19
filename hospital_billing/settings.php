<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'Admin';
$full_name = $user_name; $user_role = 'Admin';
try {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) { $full_name = $u['full_name'] ?? $user_name; $user_role = $u['role'] ?? 'Admin'; }
} catch (PDOException $e) {}

/* System info */
$php_version    = phpversion();
$mysql_version  = 'N/A';
try { $mysql_version = $pdo->query("SELECT VERSION()")->fetchColumn(); } catch (PDOException $e) {}

try { $total_users = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch (PDOException $e) { $total_users = 0; }
try { $total_records = (int) $pdo->query("SELECT COUNT(*) FROM billing_records")->fetchColumn(); } catch (PDOException $e) { $total_records = 0; }
try { $db_size = $pdo->query("
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2)
    FROM information_schema.tables
    WHERE table_schema = 'hospital_billing'
")->fetchColumn(); } catch (PDOException $e) { $db_size = 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Patient-Centered Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; display: flex; min-height: 100vh; }

/* Exact Sidebar Brand Match */
.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .3s ease;overflow-y:auto;border-right:1px solid #e2e8f0}
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

.main-content{margin-left:270px;flex:1;padding:25px 35px 40px 35px;min-height:100vh}
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

.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px}
.card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.04);padding:25px 28px;border:1px solid #e2e8f0}
.card h3{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:20px;display:flex;align-items:center;gap:10px;padding-bottom:12px;border-bottom:2px solid #f0fdfa}
.card h3 i{color:#0f766e}

.info-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #f5f5f5;font-size:14px}
.info-row:last-child{border-bottom:none}
.info-row .label{color:#64748b;font-weight:500}
.info-row .value{color:#0f172a;font-weight:600;text-align:right}

.action-list{display:flex;flex-direction:column;gap:10px}
.action-btn{display:flex;align-items:center;gap:14px;padding:15px 18px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;color:#334155;transition:all .25s ease}
.action-btn:hover{background:#fff;border-color:#0f766e;transform:translateY(-2px);box-shadow:0 4px 12px rgba(15,118,110,.12)}
.action-btn .icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0}
.action-btn .text strong{display:block;font-size:14px;color:#0f172a;margin-bottom:2px}
.action-btn .text span{font-size:12px;color:#64748b}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media (max-width: 992px) { .grid { grid-template-columns: 1fr; } }
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .top-bar{flex-direction:column;align-items:flex-start}
  .user-chip .info{display:none}
  .user-chip{padding:6px}
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

        <!-- ✅ MONITORING LINK -->
        <li><a href="administrator_monitoring.php"><i class="fas fa-shield-halved"></i> <span>Monitoring</span></a></li>

        <li><a href="explanations.php"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="insurance_discounts.php"><i class="fas fa-shield-alt"></i> <span>Insurance &amp; Discounts</span></a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li class="has-submenu open" id="settingsMenuItem">
            <a href="javascript:void(0)" onclick="toggleSettingsSubmenu(event)"><i class="fas fa-cog"></i><span>Settings</span><i class="fas fa-chevron-down submenu-arrow"></i></a>
            <ul class="submenu">
                <li><a href="settings.php" class="active"><i class="fas fa-sliders-h"></i> <span>System Settings</span></a></li>
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
            <h1>Settings</h1>
            <p>System information and settings.</p>
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

    <div class="grid">
        <div class="card">
            <h3><i class="fas fa-info-circle"></i> System Information</h3>
            <div class="info-row">
                <span class="label">Application Name</span>
                <span class="value">Hospital Billing System</span>
            </div>
            <div class="info-row">
                <span class="label">Version</span>
                <span class="value">1.0.0</span>
            </div>
            <div class="info-row">
                <span class="label">PHP Version</span>
                <span class="value"><?= htmlspecialchars($php_version) ?></span>
            </div>
            <div class="info-row">
                <span class="label">MySQL Version</span>
                <span class="value"><?= htmlspecialchars($mysql_version) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Database Size</span>
                <span class="value"><?= htmlspecialchars($db_size) ?> MB</span>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-database"></i> Database Statistics</h3>
            <div class="info-row">
                <span class="label">Total Users</span>
                <span class="value"><?= number_format($total_users) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Total Billing Records</span>
                <span class="value"><?= number_format($total_records) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Logged in as</span>
                <span class="value"><?= htmlspecialchars($full_name) ?> (<?= htmlspecialchars($user_role) ?>)</span>
            </div>
            <div class="info-row">
                <span class="label">Current Time</span>
                <span class="value"><?= date('F d, Y h:i A') ?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <h3><i class="fas fa-tools"></i> Quick Actions</h3>
        <div class="action-list">
            <a href="profile.php" class="action-btn">
                <div class="icon" style="background:#0f766e;"><i class="fas fa-user-circle"></i></div>
                <div class="text">
                    <strong>My Profile</strong>
                    <span>Update your personal information and picture</span>
                </div>
            </a>
            <a href="change_password.php" class="action-btn">
                <div class="icon" style="background:#14b8a6;"><i class="fas fa-key"></i></div>
                <div class="text">
                    <strong>Change Password</strong>
                    <span>Update your account password</span>
                </div>
            </a>
            <a href="users.php" class="action-btn">
                <div class="icon" style="background:#2dd4bf;"><i class="fas fa-users"></i></div>
                <div class="text">
                    <strong>Manage Users</strong>
                    <span>Add, edit, or remove system users</span>
                </div>
            </a>
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