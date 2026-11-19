<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$success = '';
$error = '';

/* ===== USER ===== */
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'Admin';
$full_name = $user_name; $user_role = 'Admin'; $profile_picture_url = '';
try {
    $stmt = $pdo->prepare("SELECT full_name, role, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $full_name = $u['full_name'] ?? $user_name;
        $user_role = $u['role'] ?? 'Admin';
        if (!empty($u['profile_picture']) && file_exists(__DIR__.'/uploads/profiles/'.$u['profile_picture'])) {
            $profile_picture_url = 'uploads/profiles/'.$u['profile_picture'];
        }
    }
} catch (PDOException $e) {}

/* ===== FETCH CATEGORIES ===== */
$categories = [];
try {
    $categories = $pdo->query("SELECT id, category_name FROM billing_categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

/* ===== DELETE ===== */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM rules WHERE id = ?")->execute([$_GET['delete']]);
        header("Location: rules.php?msg=deleted"); exit;
    } catch (PDOException $e) { $error = "Error deleting rule: " . $e->getMessage(); }
}

/* ===== SAVE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rule'])) {
    $id = $_POST['id'] ?? null;
    $rule_name = trim($_POST['rule_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $keywords = trim($_POST['keywords'] ?? '');
    $priority = (int)($_POST['priority'] ?? 1);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($rule_name === '') { $error = "Rule Name is required."; }
    elseif ($category_id <= 0) { $error = "Please select a Category."; }
    elseif ($keywords === '') { $error = "Keywords are required."; }
    else {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE rules SET rule_name=?, category_id=?, keywords=?, priority=?, is_active=? WHERE id=?");
                $stmt->execute([$rule_name, $category_id, $keywords, $priority, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rules (rule_name, category_id, keywords, priority, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$rule_name, $category_id, $keywords, $priority, $is_active]);
            }
            header("Location: rules.php?msg=saved"); exit;
        } catch (PDOException $e) { $error = "Error saving rule: " . $e->getMessage(); }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'saved')   $success = "Rule saved successfully!";
    if ($_GET['msg'] == 'deleted') $success = "Rule deleted successfully!";
}

/* ===== FETCH RULES ===== */
try {
    $rules = $pdo->query("
        SELECT r.*, c.category_name
        FROM rules r
        JOIN billing_categories c ON r.category_id = c.id
        ORDER BY r.priority DESC, r.id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rules = []; $error = "Error fetching rules."; }

$edit_record = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rules WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rules - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#fff;display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:30px 0 20px 0;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}
.sidebar-brand{padding:0 22px 30px 22px;text-align:center}
.sidebar-brand .brand-name{font-size:15px;font-weight:800;color:#0f172a;line-height:1.2}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase}
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

/* ===== MAIN ===== */
.main-content{margin-left:270px;flex:1;padding:25px 35px 40px 35px;min-height:100vh}
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
table thead th{text-align:left;padding:16px 20px;border-bottom:1px solid #f0f0f0;color:#64748b;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc;white-space:nowrap}
table tbody td{padding:16px 20px;border-bottom:1px solid #f5f5f5;color:#334155;vertical-align:middle;font-size:13.5px}
table tbody tr:hover{background:#f0fdfa}
table tbody tr:last-child td{border-bottom:none}
.text-muted{color:#94a3b8;text-align:center;padding:60px 0;font-size:14px}
.code-badge{background:#ccfbf1;color:#0f766e;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;display:inline-block}
.keywords-cell{font-size:12.5px;color:#475569;font-family:'Courier New',monospace;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;vertical-align:middle}

.status-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
.status-badge.active{background:#d1fae5;color:#065f46}
.status-badge.inactive{background:#f8d7da;color:#721c24}

.action-icons{display:flex;justify-content:center;gap:8px;flex-wrap:wrap}
.icon-btn{background:transparent;border:none;font-size:12.5px;font-weight:600;cursor:pointer;padding:7px 14px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap;line-height:1}
.icon-btn i{font-size:12px}
.icon-btn.edit-icon{color:#0f766e;background:rgba(15,118,110,.08);border:1px solid rgba(15,118,110,.2)}
.icon-btn.edit-icon:hover{color:#fff;background:#0f766e;border-color:#0f766e;transform:translateY(-1px);box-shadow:0 4px 10px rgba(15,118,110,.25)}
.icon-btn.delete-icon{color:#dc3545;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.icon-btn.delete-icon:hover{color:#fff;background:#dc3545;border-color:#dc3545;transform:translateY(-1px);box-shadow:0 4px 10px rgba(220,53,69,.25)}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:1000;justify-content:center;align-items:center;padding:20px}
.modal-overlay.active{display:flex}
.modal{background:white;border-radius:20px;padding:30px 35px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.25);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.1)}
@keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
#formModal .modal-header,#deleteModal .modal-header{margin:-30px -35px 20px -35px;border-radius:20px 20px 0 0;padding:18px 35px;display:flex;justify-content:space-between;align-items:center}
#formModal .modal-header{background:linear-gradient(135deg,#0f766e,#14b8a6)}
#formModal .modal-header h3{color:#fff;font-size:20px;font-weight:700;margin:0}
#deleteModal .modal-header{background:linear-gradient(135deg,#dc3545,#c82333)}
#deleteModal .modal-header h3{color:#fff;font-size:20px;font-weight:700;margin:0}
.modal-close{background:none;border:none;font-size:28px;color:rgba(255,255,255,.85);cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .25s}
.modal-close:hover{color:#fff;background:rgba(255,255,255,.2);transform:rotate(90deg)}
.modal p{font-size:15px;color:#555;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:600;color:#334155;font-size:13px;margin-bottom:5px}
.form-group label .required{color:#dc3545}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#f8fafc;font-family:inherit}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
.form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:20px;padding-top:15px;border-top:1px solid #e9ecef}
.btn{padding:10px 20px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn-success{background:#0f766e;color:white}.btn-success:hover{background:#115e59}
.btn-secondary{background:#6c757d;color:white}.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}.btn-danger:hover{background:#c82333}

/* BURGER */
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .top-bar,.section-header{flex-direction:column;align-items:stretch}
  .user-chip .info{display:none}
  .user-chip{padding:6px}
  .modal{padding:20px;margin:10px}
  .icon-btn{padding:6px 10px;font-size:12px}
}
</style>
</head>
<body>

<button class="burger-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="burger-overlay" id="burgerOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">Admin</div>
        <div class="brand-sub">Administrator</div>
    </div>
    <div class="sidebar-divider"></div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <li><a href="explanations.php"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="rules.php" class="active"><i class="fas fa-gavel"></i> <span>Rules</span></a></li>
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
    <div class="top-bar-left">
        <h1>Rule-Based Management</h1>
        <div class="page-sub">Manage keywords and priorities used by the Rule-Based Decision Algorithm.</div>
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
    <div class="section-title"><i class="fas fa-gavel"></i> Rules <span class="count-badge"><?= count($rules) ?></span></div>
    <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Rule</button>
</div>

<div class="table-card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Rule Name</th>
                    <th>Category</th>
                    <th>Keywords</th>
                    <th>Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rules)): ?>
                <tr><td colspan="6" class="text-muted">No rules found. Add your first rule!</td></tr>
            <?php else: foreach ($rules as $rule): ?>
                <tr>
                    <td><strong><?= $rule['priority'] ?></strong></td>
                    <td><strong><?= htmlspecialchars($rule['rule_name']) ?></strong></td>
                    <td><span class="code-badge"><?= htmlspecialchars($rule['category_name']) ?></span></td>
                    <td><span class="keywords-cell"><?= htmlspecialchars($rule['keywords']) ?></span></td>
                    <td>
                        <span class="status-badge <?= $rule['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $rule['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-icons">
                            <a href="?edit=<?= $rule['id'] ?>" class="icon-btn edit-icon"><i class="fas fa-pen"></i> Edit</a>
                            <a href="#" onclick="confirmDelete(event, <?= $rule['id'] ?>)" class="icon-btn delete-icon"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>

<!-- FORM MODAL -->
<div class="modal-overlay" id="formModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-gavel"></i> <span id="modalTitle">Add New Rule</span></h3>
            <button class="modal-close" onclick="closeModal('formModal')">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="id" id="recordId">
            <div class="form-group">
                <label>Rule Name <span class="required">*</span></label>
                <input type="text" id="rule_name" name="rule_name" placeholder="e.g., CBC Test Rule" required>
            </div>
            <div class="form-group">
                <label>Category <span class="required">*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Keywords <span class="required">*</span></label>
                <input type="text" id="keywords" name="keywords" placeholder="e.g., cbc, complete blood count, hemogram" required>
                <div class="hint">Separate multiple keywords with commas (,)</div>
            </div>
            <div class="form-group">
                <label>Priority (Higher number = Higher priority)</label>
                <input type="number" id="priority" name="priority" value="1" min="1">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked style="width:auto;">
                    Active (Enable this rule)
                </label>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('formModal')">Cancel</button>
                <button type="submit" name="save_rule" class="btn btn-success"><i class="fas fa-save"></i> Save Rule</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h3><i class="fas fa-trash"></i> Confirm Delete</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <p>Are you sure you want to delete this rule?</p>
        <div class="form-actions" style="justify-content:center;border-top:none;padding-top:0">
            <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
document.addEventListener('DOMContentLoaded',function(){const p=window.location.pathname.toLowerCase();if(p.includes('profile.php')||p.includes('change_password.php'))document.getElementById('settingsMenuItem').classList.add('open');});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.querySelectorAll('.modal-overlay.active').forEach(o=>o.classList.remove('active'));}});

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Rule';
    document.querySelector('#formModal form').reset();
    document.getElementById('recordId').value = '';
    document.getElementById('priority').value = 1;
    document.getElementById('is_active').checked = true;
    document.getElementById('formModal').classList.add('active');
}
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
let deleteId = null;
function confirmDelete(e, id) { e.preventDefault(); deleteId = id; document.getElementById('deleteModal').classList.add('active'); }
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteId) window.location.href = '?delete=' + deleteId;
});
<?php if ($edit_record): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalTitle').textContent = 'Edit Rule';
    document.getElementById('recordId').value = <?= json_encode($edit_record['id']) ?>;
    document.getElementById('rule_name').value = <?= json_encode($edit_record['rule_name']) ?>;
    document.getElementById('category_id').value = <?= json_encode($edit_record['category_id']) ?>;
    document.getElementById('keywords').value = <?= json_encode($edit_record['keywords']) ?>;
    document.getElementById('priority').value = <?= json_encode($edit_record['priority']) ?>;
    document.getElementById('is_active').checked = <?= $edit_record['is_active'] ? 'true' : 'false' ?>;
    document.getElementById('formModal').classList.add('active');
});
<?php endif; ?>
</script>
</body>
</html>