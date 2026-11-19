<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';

$accent        = '#0f766e';
$accent_light  = '#14b8a6';

$patient_id = (int)($_GET['id'] ?? 0);
if ($patient_id <= 0) { header('Location: patients.php'); exit; }

$success = '';
$error   = '';

// Fetch existing patient data
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) { header('Location: patients.php'); exit; }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Split the full_name into First, Middle, Last for the form
$name_parts = explode(' ', $patient['full_name']);
$def_first  = $name_parts[0] ?? '';
$def_last   = count($name_parts) > 1 ? end($name_parts) : '';
$def_middle = '';
if (count($name_parts) > 2) {
    $def_middle = implode(' ', array_slice($name_parts, 1, -1));
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $dob          = trim($_POST['date_of_birth'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $contact      = trim($_POST['contact_number'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    // Sanitize contact
    $contact = preg_replace('/\D/', '', $contact);
    if ($contact !== '' && strlen($contact) > 11) {
        $contact = substr($contact, 0, 11);
    }

    // Build full name
    $full_name_parts = array_filter([$first_name, $middle_name, $last_name], fn($v) => $v !== '');
    $full_name_in    = implode(' ', $full_name_parts);

    if ($first_name === '') {
        $error = 'First name is required.';
    } elseif ($last_name === '') {
        $error = 'Last name is required.';
    } elseif ($contact !== '' && strlen($contact) !== 11) {
        $error = 'Contact number must be exactly 11 digits.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE patients 
                SET full_name = ?, date_of_birth = ?, gender = ?, contact_number = ?, address = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $full_name_in,
                $dob !== '' ? $dob : null,
                $gender !== '' ? $gender : null,
                $contact !== '' ? $contact : null,
                $address !== '' ? $address : null,
                $notes !== '' ? $notes : null,
                $patient_id
            ]);

            $_SESSION['flash_patient'] = "Patient \"{$full_name_in}\" updated successfully.";
            header('Location: patients.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Failed to update patient: ' . $e->getMessage();
        }
    }
}

$initials = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Patient · <?= htmlspecialchars($patient['full_name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* === BACKGROUND MATCHING OTHER PAGES === */
body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex;min-height:100vh;color:#1e293b;
  position:relative;overflow-x:hidden;
  background:
    radial-gradient(circle at 15% 20%, rgba(20,184,166,.12) 0%, transparent 45%),
    radial-gradient(circle at 85% 80%, rgba(15,118,110,.14) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20,184,166,.05) 0%, transparent 60%),
    linear-gradient(135deg, #f0fdfa 0%, #e6fbf7 40%, #ecfdf5 100%);
  background-attachment:fixed;
  padding:40px 20px;
  justify-content:center;
  align-items:flex-start;
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

/* === CARD STYLE === */
.card{
    background:#fff;border-radius:14px;border:1px solid #e2e8f0;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
    width:100%;max-width:800px;overflow:hidden;
    position:relative;z-index:1; /* Ensures card sits above background effects */
}

.card-header{
    padding:18px 24px;border-bottom:1px solid #f1f5f9;
    display:flex;justify-content:space-between;align-items:center;
    background:#fff;
}
.card-header h2{font-size:16px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px;}
.card-header h2 i{color:<?= $accent ?>;}
.card-body{padding:22px 24px;}

.alert{padding:14px 18px;border-radius:11px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:500;}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}

/* === FORM GRID LAYOUT === */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px 20px}
.form-grid .full{grid-column:1 / -1}
.form-grid .span-3{grid-column:1 / -1;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px 20px}

.form-group{margin-bottom:0}
.form-group label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px}
.form-group label .req{color:#dc2626}
.form-group label .opt{color:#94a3b8;font-weight:400;font-size:11px;text-transform:none;letter-spacing:0;margin-left:3px}

.form-group input,
.form-group select,
.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13.5px;font-family:inherit;background:#fff;color:#1e293b;transition:border-color .2s,box-shadow .2s}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}
.form-group textarea{resize:vertical;min-height:80px}

.form-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9}
.btn{padding:11px 22px;border-radius:10px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.15)}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-light{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-light:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}

@media(max-width:768px){
    .form-grid{grid-template-columns:1fr}
    .form-grid .span-3{grid-template-columns:1fr}
    .form-actions .btn{width:100%;justify-content:center}
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-pen"></i> Edit Patient Information</h2>
        <a href="patients.php" class="btn btn-light" style="padding:8px 12px;font-size:12px;"><i class="fas fa-xmark"></i> Cancel</a>
    </div>
    <div class="card-body">
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">

                <!-- 3-Column Name Layout -->
                <div class="span-3">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="req">*</span></label>
                        <input type="text" id="first_name" name="first_name" required placeholder="e.g. Juan"
                               value="<?= htmlspecialchars($_POST['first_name'] ?? $def_first) ?>">
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name <span class="opt">(optional)</span></label>
                        <input type="text" id="middle_name" name="middle_name" placeholder="e.g. Santos"
                               value="<?= htmlspecialchars($_POST['middle_name'] ?? $def_middle) ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="req">*</span></label>
                        <input type="text" id="last_name" name="last_name" required placeholder="e.g. Dela Cruz"
                               value="<?= htmlspecialchars($_POST['last_name'] ?? $def_last) ?>">
                    </div>
                </div>

                <!-- DOB & Gender -->
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           value="<?= htmlspecialchars($_POST['date_of_birth'] ?? $patient['date_of_birth']) ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">— Select —</option>
                        <?php 
                        $genders = ['Male','Female','Other'];
                        $sel = $_POST['gender'] ?? $patient['gender'];
                        foreach ($genders as $g) {
                            echo '<option value="'.$g.'"'.($sel === $g ? ' selected' : '').'>'.$g.'</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Contact -->
                <div class="form-group full">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" maxlength="11" inputmode="numeric" placeholder="e.g. 09171234567"
                           value="<?= htmlspecialchars($_POST['contact_number'] ?? $patient['contact_number']) ?>"
                           oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,11);">
                </div>

                <!-- Address -->
                <div class="form-group full">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="House #, Street, Barangay, City"
                           value="<?= htmlspecialchars($_POST['address'] ?? $patient['address']) ?>">
                </div>

                <!-- Notes -->
                <div class="form-group full">
                    <label for="notes">Notes (optional)</label>
                    <textarea id="notes" name="notes" placeholder="Allergies, existing conditions, remarks…"><?= htmlspecialchars($_POST['notes'] ?? $patient['notes']) ?></textarea>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>