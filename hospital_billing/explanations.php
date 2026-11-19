<?php
/* Dev-only diagnostics — comment these out in production */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$success = '';
$error   = '';

$user_id   = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'User';
$full_name = $user_name;
$user_role = 'User';
$profile_picture_url = '';

try {
    $s = $pdo->prepare("SELECT full_name, role, profile_picture FROM users WHERE id = ?");
    $s->execute([$user_id]);
    $u = $s->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $full_name = $u['full_name'] ?: $user_name;
        $user_role = $u['role'] ?? 'User';
        if (!empty($u['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $u['profile_picture'])) {
            $profile_picture_url = 'uploads/profiles/' . $u['profile_picture'];
        }
    }
} catch (PDOException $e) {}

if (strtolower(trim($user_role)) !== 'admin') {
    header('Location: login.php?msg=access_denied'); exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* NOTIFICATIONS */
$unread_count = 0;
$notifications = [];
try { $unread_count = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn(); } catch (PDOException $e) {}
try { $notifications = $pdo->query("SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago FROM notifications ORDER BY is_read ASC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
if (empty($notifications)) {
    $notifications = [[
        'id' => 0, 'icon' => 'fa-info-circle', 'color' => '#14b8a6',
        'message' => 'Welcome to the Patient-Centered Hospital Billing System.',
        'is_read' => 0, 'created_at' => date('Y-m-d H:i:s'), 'seconds_ago' => 0
    ]];
    $unread_count = 1;
}

function timeAgo($dt, $sec = null) {
    $s = $sec !== null ? (int)$sec : (time() - strtotime($dt));
    if ($s < 0) $s = 0;
    if ($s < 60)    return 'Just now';
    if ($s < 3600)  return floor($s / 60) . ' min ago';
    if ($s < 86400) return floor($s / 3600) . ' hr ago';
    $ts = strtotime($dt);
    $td = date('Y-m-d');
    $yd = date('Y-m-d', strtotime('-1 day'));
    $ds = date('Y-m-d', $ts);
    if ($ds === $td) return 'Today, ' . date('g:i A', $ts);
    if ($ds === $yd) return 'Yesterday, ' . date('g:i A', $ts);
    return date('M d, Y, g:i A', $ts);
}

function notifTitle($m) {
    $msg = strtolower($m ?? '');
    if (strpos($msg, 'welcome') !== false) return 'System Welcome';
    if (strpos($msg, 'new billing') !== false || strpos($msg, 'record added') !== false) return 'New Billing Record';
    if (strpos($msg, 'updated') !== false) return 'Record Updated';
    if (strpos($msg, 'deleted') !== false) return 'Record Deleted';
    if (strpos($msg, 'user') !== false)    return 'User Activity';
    if (strpos($msg, 'alert') !== false)   return 'System Alert';
    return 'Notification';
}

/* ============================================================
   TUGMA HELPERS — keep semantic_dictionary ↔ services in sync
   ============================================================ */

function push_explanation_to_services(PDO $pdo, string $term, string $explanation): int {
    try {
        $stmt = $pdo->prepare("UPDATE services SET patient_explanation = ? WHERE service_name = ? AND is_active = 1");
        $stmt->execute([$explanation, $term]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

function pull_all_from_services(PDO $pdo): array {
    $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
    try {
        $rows = $pdo->query("
            SELECT s.service_name, s.patient_explanation, c.category_name AS category_name
            FROM services s
            JOIN billing_categories c ON c.id = s.category_id
            WHERE s.is_active = 1
              AND s.patient_explanation IS NOT NULL
              AND s.patient_explanation <> ''
            ORDER BY c.category_name, s.service_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $rows = $pdo->query("
                SELECT s.service_name, s.patient_explanation, '' AS category_name
                FROM services s
                WHERE s.is_active = 1 AND s.patient_explanation IS NOT NULL AND s.patient_explanation <> ''
                ORDER BY s.service_name
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            return $stats;
        }
    }
    foreach ($rows as $r) {
        $term  = trim($r['service_name']);
        $expl  = trim($r['patient_explanation']);
        $cat   = trim($r['category_name'] ?? '');
        if ($term === '' || $expl === '') { $stats['skipped']++; continue; }
        try {
            $find = $pdo->prepare("SELECT id, patient_explanation FROM semantic_dictionary WHERE technical_term = ? LIMIT 1");
            $find->execute([$term]);
            $e = $find->fetch(PDO::FETCH_ASSOC);
            if (!$e) {
                $pdo->prepare("INSERT INTO semantic_dictionary (technical_term, patient_explanation, category, synonym_terms) VALUES (?,?,?,NULL)")
                    ->execute([$term, $expl, $cat]);
                $stats['inserted']++;
            } elseif (trim($e['patient_explanation']) !== $expl) {
                $pdo->prepare("UPDATE semantic_dictionary SET patient_explanation = ?, category = ? WHERE id = ?")
                    ->execute([$expl, $cat, $e['id']]);
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        } catch (PDOException $e) {
            $stats['errors']++;
        }
    }
    return $stats;
}

function pull_one_from_service(PDO $pdo, int $dict_id): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT d.technical_term, s.patient_explanation AS svc_expl, c.category_name AS category_name
            FROM semantic_dictionary d
            JOIN services s ON s.service_name = d.technical_term AND s.is_active = 1
            LEFT JOIN billing_categories c ON c.id = s.category_id
            WHERE d.id = ?
            LIMIT 1
        ");
        $stmt->execute([$dict_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) return false;
        $pdo->prepare("UPDATE semantic_dictionary SET patient_explanation = ?, category = ? WHERE id = ?")
            ->execute([$r['svc_expl'], $r['category_name'], $dict_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/* ============================================================
   ACTIONS
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_from_services'])) {
    if (($_POST['csrf_token'] ?? '') !== $csrf) {
        $error = 'Invalid request token. Please try again.';
    } else {
        $stats = pull_all_from_services($pdo);
        $msgParts = [];
        if ($stats['inserted'] > 0) $msgParts[] = $stats['inserted'] . ' added';
        if ($stats['updated']  > 0) $msgParts[] = $stats['updated']  . ' updated';
        if ($stats['skipped']  > 0) $msgParts[] = $stats['skipped']  . ' already in sync';
        if ($stats['errors']   > 0) $msgParts[] = $stats['errors']   . ' error(s)';
        $success = 'Synced from Services — ' . (count($msgParts) ? implode(', ', $msgParts) : 'nothing to sync');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pull_row'])) {
    $rid = (int)$_POST['pull_row'];
    if (($_POST['csrf_token'] ?? '') !== $csrf) {
        $error = 'Invalid request token.';
    } elseif ($rid <= 0) {
        $error = 'Invalid row.';
    } elseif (pull_one_from_service($pdo, $rid)) {
        header("Location: explanations.php?msg=pulled"); exit;
    } else {
        $error = 'Could not pull — no matching active service found.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)$_POST['delete_id'];
    if (($_POST['csrf_token'] ?? '') !== $csrf) {
        $error = 'Invalid request token. Please try again.';
    } elseif ($del_id <= 0) {
        $error = 'Invalid record.';
    } else {
        try {
            $pdo->prepare("DELETE FROM semantic_dictionary WHERE id = ?")->execute([$del_id]);
            header("Location: explanations.php?msg=deleted"); exit;
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_explanation'])) {
    if (($_POST['csrf_token'] ?? '') !== $csrf) {
        $error = 'Invalid request token. Please try again.';
    } else {
        $id  = $_POST['id'] ?? null;
        $mt  = trim($_POST['technical_term'] ?? '');
        $pe  = trim($_POST['patient_explanation'] ?? '');
        $cat = trim($_POST['category'] ?? '');
        $st  = trim($_POST['synonym_terms'] ?? '');

        if ($mt === '') {
            $error = 'Service Description (Technical Term) is required.';
        } elseif ($pe === '') {
            $error = 'Patient Understanding is required.';
        } else {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE semantic_dictionary SET technical_term=?, patient_explanation=?, category=?, synonym_terms=? WHERE id=?");
                    $stmt->execute([$mt, $pe, $cat, $st, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO semantic_dictionary (technical_term, patient_explanation, category, synonym_terms) VALUES (?,?,?,?)");
                    $stmt->execute([$mt, $pe, $cat, $st]);
                }
                $pushed = push_explanation_to_services($pdo, $mt, $pe);
                header("Location: explanations.php?msg=saved" . ($pushed ? "&pushed=1" : "")); exit;
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved')   $success = "Explanation saved!" . (!empty($_GET['pushed']) ? " Synced to matching service." : "");
    if ($_GET['msg'] === 'deleted') $success = "Explanation deleted!";
    if ($_GET['msg'] === 'pulled')  $success = "Row updated to match its service.";
}

/* ============================================================
   FETCH + SYNC STATUS
   ============================================================ */

$explanations = [];
try {
    $explanations = $pdo->query("
        SELECT d.*,
               s.id AS linked_service_id,
               s.patient_explanation AS linked_service_explanation,
               CASE
                 WHEN s.id IS NULL THEN 'custom'
                 WHEN d.patient_explanation = s.patient_explanation THEN 'synced'
                 ELSE 'mismatch'
               END AS sync_status
        FROM semantic_dictionary d
        LEFT JOIN services s
          ON s.service_name = d.technical_term AND s.is_active = 1
        ORDER BY d.id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $explanations = $pdo->query("SELECT *, 'custom' AS sync_status FROM semantic_dictionary ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $explanations = [];
        $error = "Table not found.";
    }
}

$stat_dict_total = count($explanations);
$stat_synced     = 0;
$stat_mismatch   = 0;
$stat_custom     = 0;
foreach ($explanations as $e) {
    if ($e['sync_status'] === 'synced')        $stat_synced++;
    elseif ($e['sync_status'] === 'mismatch')  $stat_mismatch++;
    else                                       $stat_custom++;
}
$stat_services_with_expl = 0;
$stat_missing_in_dict    = 0;
try {
    $stat_services_with_expl = (int)$pdo->query("
        SELECT COUNT(*) FROM services
        WHERE is_active = 1 AND patient_explanation IS NOT NULL AND patient_explanation <> ''
    ")->fetchColumn();
    $stat_missing_in_dict = (int)$pdo->query("
        SELECT COUNT(*) FROM services s
        LEFT JOIN semantic_dictionary d ON d.technical_term = s.service_name
        WHERE s.is_active = 1
          AND s.patient_explanation IS NOT NULL
          AND s.patient_explanation <> ''
          AND d.id IS NULL
    ")->fetchColumn();
} catch (PDOException $e) {}

$edit_record = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM semantic_dictionary WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$category_options = [];
try {
    $c = $pdo->query("SELECT category_name FROM billing_categories ORDER BY category_name")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($c)) $category_options = $c;
} catch (PDOException $e) {}
if (empty($category_options)) {
    $category_options = [
        'Laboratory Services','Diagnostic Services','Consultation Services',
        'Pharmacy / Medication','Medical Supplies','Room Charges',
        'Therapy Services','Emergency Services','Surgical Services',
        'Professional Fees'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Semantic Explanations - Hospital Billing System</title>
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

/* ===== SYNC STATUS CARD ===== */
.sync-card{background:linear-gradient(135deg,#f0fdfa,#fff);border:1px solid #ccfbf1;border-radius:14px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 2px 8px rgba(15,118,110,.06)}
.sync-card.has-mismatch{background:linear-gradient(135deg,#fffbeb,#fff);border-color:#fde68a;box-shadow:0 2px 8px rgba(245,158,11,.08)}
.sync-card .sync-icon{width:42px;height:42px;border-radius:11px;background:#0f766e;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;box-shadow:0 4px 10px rgba(15,118,110,.25)}
.sync-card.has-mismatch .sync-icon{background:#f59e0b;box-shadow:0 4px 10px rgba(245,158,11,.25)}
.sync-card .sync-info{flex:1;min-width:200px}
.sync-card .sync-title{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.sync-card .sync-stats{display:flex;flex-wrap:wrap;gap:14px;font-size:12.5px;color:#475569}
.sync-card .sync-stats .s-item{display:inline-flex;align-items:center;gap:5px}
.sync-card .sync-stats .s-item strong{color:#0f172a;font-weight:800}
.sync-card .sync-stats .s-synced{color:#10b981}
.sync-card .sync-stats .s-synced strong{color:#10b981}
.sync-card .sync-stats .s-mismatch{color:#d97706}
.sync-card .sync-stats .s-mismatch strong{color:#d97706}
.sync-card .sync-actions{display:flex;gap:8px;flex-wrap:wrap}
.btn-sync{padding:10px 18px;background:#0f766e;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;box-shadow:0 4px 10px rgba(15,118,110,.2);white-space:nowrap}
.btn-sync:hover{background:#115e59;transform:translateY(-1px)}
.btn-sync.warning{background:#f59e0b;box-shadow:0 4px 10px rgba(245,158,11,.2)}
.btn-sync.warning:hover{background:#d97706}

.action-bar{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:20px;flex-wrap:wrap}
.btn-add{padding:12px 24px;background:#0f766e;color:white;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 15px rgba(15,118,110,.3);transition:all .25s}
.btn-add:hover{background:#115e59;transform:translateY(-2px)}
.search-wrapper{position:relative;min-width:260px}
.search-wrapper input{width:100%;padding:12px 20px 12px 44px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;background:#fff}
.search-wrapper input:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12)}
.search-wrapper .search-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px}
.table-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.04);overflow:hidden;border:1px solid #e2e8f0}
.table-wrapper{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:14px}
table thead th{text-align:left;padding:16px 20px;border-bottom:1px solid #f0f0f0;color:#64748b;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc}
table tbody td{padding:16px 20px;border-bottom:1px solid #f5f5f5;color:#334155;vertical-align:middle;font-size:13.5px}
table tbody tr:hover{background:#f0fdfa}
table tbody tr:last-child td{border-bottom:none}
.text-muted{color:#94a3b8;text-align:center;padding:60px 0;font-size:14px}
.category-badge{display:inline-block;padding:4px 14px;border-radius:30px;font-size:12px;font-weight:500;background:#ccfbf1;color:#0f766e}
.sync-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.sync-badge.synced{background:#d1fae5;color:#065f46}
.sync-badge.mismatch{background:#fef3c7;color:#92400e}
.sync-badge.custom{background:#f1f5f9;color:#64748b}
.sync-badge i{font-size:9px}
.row-pull-btn{background:none;border:1.5px solid #f59e0b;color:#d97706;padding:3px 9px;border-radius:6px;font-size:10.5px;font-weight:700;cursor:pointer;transition:all .2s;margin-left:6px;display:inline-flex;align-items:center;gap:4px;white-space:nowrap}
.row-pull-btn:hover{background:#f59e0b;color:#fff}

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
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}
#formModal .modal-header h3 i { color: #0f766e; }
#deleteModal .modal-header h3 i { color: #dc3545; }

.modal-close{background:none;border:none;font-size:28px;color:#94a3b8;cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .25s}
.modal-close:hover{color:#dc2626;background:#fef2f2;transform:rotate(90deg)}

.modal p{font-size:15px;color:#555;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-weight:600;color:#334155;font-size:13px;margin-bottom:5px}
.form-group label .required{color:#dc3545}
.form-group input,.form-group textarea{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#f8fafc;font-family:inherit}
.form-group input:focus,.form-group textarea:focus{border-color:#14b8a6;outline:none;box-shadow:0 0 0 4px rgba(20,184,166,.12);background:#fff}
.form-group textarea{resize:vertical;min-height:80px}
.form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
.form-group .hint.warn{color:#d97706;font-weight:600}
.form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:20px;padding-top:15px;border-top:1px solid #e9ecef}
.btn{padding:10px 20px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn-success{background:#0f766e;color:white}.btn-success:hover{background:#115e59}
.btn-secondary{background:#6c757d;color:white}.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}.btn-danger:hover{background:#c82333}

.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:380px;width:calc(100% - 48px);pointer-events:none}
.toast{background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(15,23,42,.18);border:1px solid #e2e8f0;padding:16px 18px;display:flex;gap:12px;align-items:flex-start;pointer-events:auto;animation:toastIn .4s cubic-bezier(.2,.9,.3,1.1);position:relative;overflow:hidden}
.toast::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--toast-accent,#14b8a6)}
.toast.closing{animation:toastOut .3s ease forwards}
.toast .toast-body{flex:1;min-width:0}
.toast .toast-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.toast .toast-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:6px;word-wrap:break-word}
.toast .toast-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.toast .toast-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:4px;border-radius:6px;transition:all .2s;flex-shrink:0}
.toast .toast-close:hover{color:#dc2626;background:#fef2f2}
@keyframes toastIn{from{opacity:0;transform:translateX(120%)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(120%)}}

.notif-fab{position:fixed;bottom:28px;right:28px;z-index:900;width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 10px 28px rgba(15,118,110,.4);transition:all .25s ease}
.notif-fab:hover{transform:scale(1.08) translateY(-2px);box-shadow:0 14px 34px rgba(15,118,110,.5)}
.notif-fab .fab-badge{position:absolute;top:-3px;right:-3px;background:#dc2626;color:#fff;font-size:11px;font-weight:800;min-width:22px;height:22px;padding:0 5px;border-radius:12px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.2)}

.notif-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:9998;display:none;align-items:center;justify-content:center;padding:20px;animation:fadeIn .2s ease}
.notif-modal-overlay.show{display:flex}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.notif-modal{background:#fff;border-radius:18px;width:100%;max-width:520px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 25px 70px rgba(15,23,42,.3);overflow:hidden;animation:modalIn .3s cubic-bezier(.2,.9,.3,1.1)}
.notif-modal-header{padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f0fdfa,#fff)}
.notif-modal-header h3{font-size:16px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px}
.notif-modal-header h3 i{color:#0f766e;font-size:16px}
.notif-modal-header .modal-sub{font-size:12px;color:#64748b;margin-top:3px;margin-left:26px}
.notif-modal-close{width:36px;height:36px;border-radius:10px;border:none;background:#f1f5f9;color:#475569;cursor:pointer;font-size:15px;transition:all .2s;display:flex;align-items:center;justify-content:center}
.notif-modal-close:hover{background:#fee2e2;color:#dc2626}
.notif-modal-body{overflow-y:auto;padding:8px 0;flex:1}
.notif-modal-body::-webkit-scrollbar{width:8px}
.notif-modal-body::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
.notif-item-modal{display:flex;gap:14px;padding:16px 24px;border-bottom:1px solid #f8fafc;transition:background .15s;cursor:pointer;border-left:3px solid transparent}
.notif-item-modal:last-child{border-bottom:none}
.notif-item-modal:hover{background:#f8fafc}
.notif-item-modal.unread{background:#f0fdfa;border-left-color:var(--notif-accent,#14b8a6)}
.notif-item-modal.unread:hover{background:#ccfbf1}
.notif-item-modal .notif-content{flex:1;min-width:0}
.notif-item-modal .notif-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.notif-item-modal .notif-unread-dot{width:8px;height:8px;border-radius:50%;background:#14b8a6;display:inline-block;flex-shrink:0}
.notif-item-modal .notif-message{font-size:12.5px;color:#475569;line-height:1.5;margin-bottom:5px;word-wrap:break-word}
.notif-item-modal .notif-time{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
.notif-modal-footer{padding:14px 24px;border-top:1px solid #f1f5f9;text-align:center;background:#f8fafc}
.notif-modal-footer a{color:#0f766e;text-decoration:none;font-size:12.5px;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.notif-modal-footer a:hover{text-decoration:underline}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);width:260px}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main-content{margin-left:0;padding:70px 18px 30px 18px}
  .top-bar,.action-bar{flex-direction:column;align-items:stretch}
  .user-chip .info{display:none}
  .user-chip{padding:6px}
  .search-wrapper{width:100%;min-width:unset}
  .modal{padding:20px;margin:10px}
  .toast-container{top:16px;right:16px;left:16px;width:auto;max-width:none}
  .notif-modal{max-height:90vh}
  .notif-fab{bottom:20px;right:20px;width:52px;height:52px;font-size:20px}
  .icon-btn{padding:6px 10px;font-size:12px}
  .sync-card{flex-direction:column;align-items:stretch;text-align:center}
  .sync-card .sync-info{text-align:center}
  .sync-card .sync-stats{justify-content:center}
  .sync-card .sync-actions{justify-content:center}
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
        <li><a href="explanations.php" class="active"><i class="fas fa-comment-dots"></i> <span>Semantic Explanations</span></a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
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
        <h1>Semantic Explanations</h1>
        <div class="page-sub">Manage the dictionary of technical terms and their patient-friendly explanations.</div>
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

<!-- ===== SYNC STATUS CARD ===== -->
<div class="sync-card <?= ($stat_mismatch > 0 || $stat_missing_in_dict > 0) ? 'has-mismatch' : '' ?>">
    <div class="sync-icon">
        <i class="fas <?= ($stat_mismatch > 0 || $stat_missing_in_dict > 0) ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
    </div>
    <div class="sync-info">
        <div class="sync-title">
            <?= ($stat_mismatch > 0 || $stat_missing_in_dict > 0) ? 'Some explanations are out of sync' : 'All explanations are in sync' ?>
        </div>
        <div class="sync-stats">
            <span class="s-item"><i class="fas fa-book"></i> Dictionary: <strong><?= number_format($stat_dict_total) ?></strong></span>
            <span class="s-item"><i class="fas fa-stethoscope"></i> Services with explanations: <strong><?= number_format($stat_services_with_expl) ?></strong></span>
            <span class="s-item s-synced"><i class="fas fa-circle-check"></i> In sync: <strong><?= number_format($stat_synced) ?></strong></span>
            <?php if ($stat_mismatch > 0): ?>
                <span class="s-item s-mismatch"><i class="fas fa-triangle-exclamation"></i> Mismatch: <strong><?= number_format($stat_mismatch) ?></strong></span>
            <?php endif; ?>
            <?php if ($stat_missing_in_dict > 0): ?>
                <span class="s-item s-mismatch"><i class="fas fa-circle-plus"></i> Missing: <strong><?= number_format($stat_missing_in_dict) ?></strong></span>
            <?php endif; ?>
            <span class="s-item"><i class="fas fa-pencil"></i> Custom only: <strong><?= number_format($stat_custom) ?></strong></span>
        </div>
    </div>
    <div class="sync-actions">
        <form method="post" style="display:inline" onsubmit="return confirm('Pull all service explanations into the dictionary?\n\n• New services will be added as dictionary entries\n• Mismatched explanations will be overwritten with the service wording\n\nContinue?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <button type="submit" name="sync_from_services" class="btn-sync <?= ($stat_mismatch > 0 || $stat_missing_in_dict > 0) ? 'warning' : '' ?>">
                <i class="fas fa-arrows-rotate"></i> Sync from Services
            </button>
        </form>
    </div>
</div>

<div class="action-bar">
    <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Explanation</button>
    <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Search terms..." onkeyup="searchTable()">
    </div>
</div>

<div class="table-card">
    <div class="table-wrapper">
        <table id="explanationsTable">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Service Description</th>
                    <th>Patient Understanding</th>
                    <th style="text-align:center;">Sync</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($explanations)): ?><tr><td colspan="5" class="text-muted">No explanations found.</td></tr>
            <?php else: foreach ($explanations as $exp):
                $sync_status = $exp['sync_status'] ?? 'custom';
                $row_id = (int)$exp['id'];
            ?>
                <tr>
                    <td><span class="category-badge"><?= htmlspecialchars($exp['category'] ?? 'General') ?></span></td>
                    <td><strong><?= htmlspecialchars($exp['technical_term']) ?></strong></td>
                    <td><?= htmlspecialchars($exp['patient_explanation']) ?></td>
                    <td style="text-align:center;">
                        <?php if ($sync_status === 'synced'): ?>
                            <span class="sync-badge synced" title="Matches its service record"><i class="fas fa-link"></i> Synced</span>
                        <?php elseif ($sync_status === 'mismatch'): ?>
                            <span class="sync-badge mismatch" title="Differs from the service record"><i class="fas fa-triangle-exclamation"></i> Mismatch</span>
                            <form method="post" style="display:inline" onsubmit="return confirm('Pull the service\'s current explanation into this row? Your edits here will be overwritten.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="pull_row" value="<?= $row_id ?>">
                                <button type="submit" class="row-pull-btn" title="Pull from service"><i class="fas fa-arrow-down"></i> Pull</button>
                            </form>
                        <?php else: ?>
                            <span class="sync-badge custom" title="No matching service — dictionary-only entry"><i class="fas fa-circle"></i> Custom</span>
                        <?php endif; ?>
                    </td>
                    <td><div class="action-icons">
                        <a href="?edit=<?= $row_id ?>" class="icon-btn edit-icon"><i class="fas fa-pen"></i> Edit</a>
                        <a href="#" onclick="confirmDelete(event, <?= $row_id ?>)" class="icon-btn delete-icon"><i class="fas fa-trash"></i> Delete</a>
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
        <div class="modal-header"><h3><i class="fas fa-comment-dots"></i> <span id="modalTitle">Add New Explanation</span></h3><button class="modal-close" onclick="closeModal('formModal')">&times;</button></div>
        <form method="post" id="explanationForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="recordId">
            <div class="form-group"><label>Service Description (Technical Term) <span class="required">*</span></label><input type="text" id="technical_term" name="technical_term" required oninput="checkLinkStatus()"><div class="hint" id="linkHint">Type a service name (e.g. <strong>CBC</strong>) to sync with the service catalog.</div></div>
            <div class="form-group"><label>Patient Understanding <span class="required">*</span></label><textarea id="patient_explanation" name="patient_explanation" rows="3" required></textarea></div>
            <div class="form-group"><label>Category</label><input type="text" id="category" name="category" list="categoryList"><datalist id="categoryList"><?php foreach ($category_options as $cat): ?><option value="<?= htmlspecialchars($cat) ?>"><?php endforeach; ?></datalist></div>
            <div class="form-group"><label>Synonym / Abbreviation Terms</label><input type="text" id="synonym_terms" name="synonym_terms"><div class="hint">Comma-separated</div></div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('formModal')">Cancel</button><button type="submit" name="save_explanation" class="btn btn-success"><i class="fas fa-save"></i> Save</button></div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header"><h3><i class="fas fa-trash"></i> Confirm Delete</h3><button class="modal-close" onclick="closeModal('deleteModal')">&times;</button></div>
        <p>Are you sure you want to permanently delete this explanation?</p>
        <form method="post" id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="delete_id" id="deleteId" value="">
            <div class="form-actions" style="justify-content:center;border-top:none;padding-top:0">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </form>
    </div>
</div>

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
            <div>
                <h3><i class="fas fa-bell"></i> Notifications</h3>
                <div class="modal-sub"><?= count($notifications) ?> recent · <?= $unread_count ?> unread</div>
            </div>
            <button class="notif-modal-close" onclick="closeNotifModal()" title="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="notif-modal-body">
            <?php foreach ($notifications as $notif): $isUnread = empty($notif['is_read']); ?>
                <div class="notif-item-modal <?= $isUnread ? 'unread' : '' ?>"
                     data-id="<?= (int)$notif['id'] ?>"
                     data-color="<?= htmlspecialchars($notif['color'] ?: '#14b8a6') ?>"
                     style="--notif-accent: <?= htmlspecialchars($notif['color'] ?: '#14b8a6') ?>;"
                     <?php if ($isUnread && (int)$notif['id'] > 0): ?>onclick="markAsRead(event, this)"<?php endif; ?>>
                    <div class="notif-content">
                        <div class="notif-title">
                            <?php if ($isUnread): ?><span class="notif-unread-dot"></span><?php endif; ?>
                            <?= htmlspecialchars(notifTitle($notif['message'])) ?>
                        </div>
                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                        <div class="notif-time"><i class="fas fa-clock"></i> <?= timeAgo($notif['created_at'], $notif['seconds_ago'] ?? null) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="notif-modal-footer">
            <a href="notifications.php">View all notifications <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<script>
/* Service names available for client-side link hint */
const SERVICE_NAMES = <?= json_encode(
    array_values(
        array_filter(
            array_map(fn($r) => $r['technical_term'], $explanations),
            fn($x) => $x !== ''
        )
    )
) ?>;

function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}
function toggleSettingsSubmenu(e){if(e)e.preventDefault();document.getElementById('settingsMenuItem').classList.toggle('open');}
document.addEventListener('DOMContentLoaded',function(){const p=window.location.pathname.toLowerCase();if(p.includes('profile.php')||p.includes('change_password.php'))document.getElementById('settingsMenuItem').classList.add('open');});

function openNotifModal(){
    document.getElementById('notifModalOverlay').classList.add('show');
    document.body.style.overflow='hidden';
}
function closeNotifModal(){
    document.getElementById('notifModalOverlay').classList.remove('show');
    document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeNotifModal();});

function showToast(title, message, color, duration){
    duration = duration || 6000;
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.setProperty('--toast-accent', color || '#14b8a6');
    toast.innerHTML = `
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

function markAsRead(event, el){
    event.stopPropagation();
    if (!el.classList.contains('unread')) return;
    const id = parseInt(el.dataset.id, 10);
    if (!id) return;
    el.classList.remove('unread');
    const dot = el.querySelector('.notif-unread-dot');
    if (dot) dot.remove();
    el.removeAttribute('onclick');
    const badge = document.getElementById('fabBadge');
    if (badge) {
        let c = parseInt(badge.textContent, 10) || 0;
        const n = Math.max(0, c - 1);
        if (n === 0) badge.remove();
        else badge.textContent = n > 9 ? '9+' : n;
    }
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    }).catch(() => {});
}

(function autoToast(){
    const first = document.querySelector('.notif-item-modal.unread');
    if (!first) return;
    const titleEl = first.querySelector('.notif-title');
    const msgEl   = first.querySelector('.notif-message');
    if (!msgEl) return;
    const color = first.dataset.color || '#14b8a6';
    const t  = titleEl ? titleEl.textContent.replace(/\s+/g,' ').trim() : 'Notification';
    const m  = msgEl.textContent.trim();
    setTimeout(() => showToast(t, m, color, 6000), 500);
})();

function searchTable(){
    const f = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.getElementById('explanationsTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < cells.length; j++) {
            if ((cells[j].textContent || cells[j].innerText).toLowerCase().indexOf(f) > -1) { found = true; break; }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}

/* Live link-hint while typing a technical term */
function checkLinkStatus() {
    const term = document.getElementById('technical_term').value.trim();
    const hint = document.getElementById('linkHint');
    if (!hint) return;
    if (term === '') {
        hint.textContent = 'Type a service name (e.g. CBC) to sync with the service catalog.';
        hint.classList.remove('warn');
        return;
    }
    const hasService = SERVICE_NAMES.some(n => n.toLowerCase() === term.toLowerCase());
    if (hasService) {
        hint.innerHTML = '<i class="fas fa-link"></i> Linked to service "' + term + '" — saving will update the service catalog too.';
        hint.classList.add('warn');
    } else {
        hint.innerHTML = 'No matching service found. This will be a dictionary-only entry.';
        hint.classList.remove('warn');
    }
}

function openAddModal(){
    document.getElementById('modalTitle').textContent = 'Add New Explanation';
    document.getElementById('explanationForm').reset();
    document.getElementById('recordId').value = '';
    document.getElementById('category').value = '';
    document.getElementById('linkHint').textContent = 'Type a service name (e.g. CBC) to sync with the service catalog.';
    document.getElementById('linkHint').classList.remove('warn');
    document.getElementById('formModal').classList.add('active');
}
function closeModal(id){
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', function(e){ if (e.target === this) this.classList.remove('active'); }));

function openEditModal(){
    <?php if ($edit_record): ?>
    document.getElementById('modalTitle').textContent = 'Edit Explanation';
    document.getElementById('recordId').value = <?= json_encode($edit_record['id']) ?>;
    document.getElementById('technical_term').value = <?= json_encode($edit_record['technical_term']) ?>;
    document.getElementById('patient_explanation').value = <?= json_encode($edit_record['patient_explanation']) ?>;
    document.getElementById('category').value = <?= json_encode($edit_record['category'] ?? '') ?>;
    document.getElementById('synonym_terms').value = <?= json_encode($edit_record['synonym_terms'] ?? '') ?>;
    document.getElementById('formModal').classList.add('active');
    checkLinkStatus();
    if (window.history && window.history.replaceState) {
        window.history.replaceState({}, document.title, 'explanations.php');
    }
    <?php endif; ?>
}
document.addEventListener('DOMContentLoaded', openEditModal);

let deleteId = null;
function confirmDelete(event, id){
    event.preventDefault();
    deleteId = id;
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').classList.add('active');
}
</script>
</body>
</html>