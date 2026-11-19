<?php
session_start();

function role_is_billing($role) {
    $r = strtolower(trim($role));
    return in_array($r, ['billing', 'cashier'], true)
        || strpos($r, 'billing') !== false || strpos($r, 'cashier') !== false;
}
function role_is_frontdesk($role) {
    $r = strtolower(trim($role));
    return in_array($r, ['front desk receptionist','receptionist','frontdesk','front desk','front desk reception','front office','reception'], true)
        || strpos($r, 'reception') !== false || strpos($r, 'frontdesk') !== false
        || strpos($r, 'front desk') !== false || strpos($r, 'front-desk') !== false
        || strpos($r, 'front office') !== false;
}
function role_is_admin($role) {
    $r = strtolower(trim($role));
    return in_array($r, ['admin','administrator','super admin','superadmin'], true)
        || strpos($r, 'admin') !== false;
}

function dashboard_candidates_for_role($role) {
    if (role_is_billing($role))   return ['billing_dashboard.php','billing.php','cashier_dashboard.php'];
    if (role_is_frontdesk($role)) return ['frontdesk_dashboard.php','frontdesk.php','receptionist_dashboard.php'];
    if (role_is_admin($role))     return ['dashboard.php','admin_dashboard.php','admin.php','administrator_dashboard.php'];
    return [];
}

function go_to_dashboard($role) {
    foreach (dashboard_candidates_for_role($role) as $file) {
        if (is_file(__DIR__ . DIRECTORY_SEPARATOR . $file)) {
            header('Location: ' . $file);
            exit;
        }
    }
    $candidates = dashboard_candidates_for_role($role);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Dashboard missing</title></head><body style="font-family:Segoe UI,Arial;padding:40px;line-height:1.7">';
    echo '<h2 style="color:#b91c1c">Login OK — but no dashboard file found</h2>';
    echo '<p>Logged in as <strong>' . htmlspecialchars($role) . '</strong>. Folder: <code>' . htmlspecialchars(__DIR__) . '</code></p>';
    echo '<p>Create <strong>one</strong> of these:</p><ul>';
    foreach ($candidates as $f) echo '<li><code>' . htmlspecialchars($f) . '</code></li>';
    echo '</ul><p><a href="logout.php">Logout</a></p></body></html>';
    exit;
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $role = $_SESSION['role'] ?? '';
    if (role_is_billing($role) || role_is_frontdesk($role) || role_is_admin($role)) {
        go_to_dashboard($role);
    } else {
        session_destroy(); header('Location: login.php'); exit;
    }
}

require_once __DIR__ . '/config.php';

$error = '';
$debug_info = [];
$block_caps_lock = true;
define('MAX_ATTEMPTS', 5);
define('LOCKOUT_SECONDS', 900);

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['lockout_until']))  $_SESSION['lockout_until']  = 0;

if (isset($_GET['msg']) && $_GET['msg'] === 'access_denied') {
    $error = 'Access denied. That page is restricted to a different role.';
}

$is_locked_out = ($_SESSION['lockout_until'] > time());
if ($is_locked_out) {
    $remaining = $_SESSION['lockout_until'] - time();
    $error = 'Too many failed attempts. Please try again in ' . ceil($remaining / 60) . ' minute(s).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked_out) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Invalid username or password.';
                $_SESSION['login_attempts']++;
                $debug_info[] = "No user found: " . $username;
            } else {
                $user_status = $user['status'] ?? 'Active';
                if ($user_status !== 'Active' && $user_status !== '' && $user_status !== null) {
                    $error = 'Your account is inactive. Please contact the administrator.';
                } else {
                    $stored = (string)($user['password'] ?? '');
                    $is_md5 = (strlen($stored) === 32 && ctype_xdigit($stored));
                    $valid  = $is_md5 ? hash_equals($stored, md5($password)) : password_verify($password, $stored);

                    if (!$valid) {
                        $error = 'Invalid username or password.';
                        $_SESSION['login_attempts']++;
                        $debug_info[] = "Password mismatch: " . $username;
                    } else {
                        $role = trim((string)($user['role'] ?? ''));
                        if ($role === '') {
                            $error = 'Your account has no assigned role. Contact the administrator.';
                        } else {
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['lockout_until']  = 0;
                            session_regenerate_id(true);

                            $_SESSION['loggedin']      = true;
                            $_SESSION['user_id']       = (int)$user['id'];
                            $_SESSION['username']      = $user['username'];
                            $_SESSION['role']          = $role;
                            $_SESSION['full_name']     = $user['full_name'] ?? $user['username'];
                            $_SESSION['last_activity'] = time();

                            $debug_info[] = "Role from DB: " . $role;

                            if (role_is_billing($role)) {
                                $_SESSION['billing_loggedin'] = true;
                                $_SESSION['billing_id']       = (int)$user['id'];
                                $_SESSION['billing_name']     = $user['full_name'] ?? $user['username'];
                                $_SESSION['billing_username'] = $user['username'];
                                go_to_dashboard($role);
                            } elseif (role_is_frontdesk($role)) {
                                $_SESSION['frontdesk_loggedin'] = true;
                                $_SESSION['frontdesk_id']       = (int)$user['id'];
                                $_SESSION['frontdesk_name']     = $user['full_name'] ?? $user['username'];
                                $_SESSION['frontdesk_username'] = $user['username'];
                                go_to_dashboard($role);
                            } elseif (role_is_admin($role)) {
                                $_SESSION['admin_loggedin'] = true;
                                $_SESSION['admin_id']       = (int)$user['id'];
                                $_SESSION['admin_name']     = $user['full_name'] ?? $user['username'];
                                $_SESSION['admin_username'] = $user['username'];
                                go_to_dashboard($role);
                            } else {
                                session_destroy(); header('Location: login.php?msg=access_denied'); exit;
                            }
                        }
                    }
                }
            }
            if (($_SESSION['login_attempts'] ?? 0) >= MAX_ATTEMPTS) {
                $_SESSION['lockout_until'] = time() + LOCKOUT_SECONDS;
                $error = 'Too many failed attempts. Account temporarily locked for ' . (LOCKOUT_SECONDS / 60) . ' minutes.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again.';
            $debug_info[] = $e->getMessage();
        }
    }
}

$show_debug = isset($_GET['debug']) && $_GET['debug'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ===== BODY: soft teal mesh gradient background ===== */
body{
  font-family:'Segoe UI',Tahoma,Verdana,sans-serif;
  min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  padding:20px;
  position:relative;
  overflow-x:hidden;
  background:
    radial-gradient(circle at 15% 20%, rgba(20,184,166,.12) 0%, transparent 45%),
    radial-gradient(circle at 85% 80%, rgba(15,118,110,.14) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20,184,166,.05) 0%, transparent 60%),
    linear-gradient(135deg, #f0fdfa 0%, #e6fbf7 40%, #ecfdf5 100%);
  background-attachment:fixed;
}

/* Decorative soft blobs (subtle & professional) */
body::before{
  content:'';position:fixed;top:-15%;right:-12%;
  width:520px;height:520px;
  background:radial-gradient(circle, rgba(20,184,166,.18) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);
}
body::after{
  content:'';position:fixed;bottom:-20%;left:-12%;
  width:620px;height:620px;
  background:radial-gradient(circle, rgba(15,118,110,.15) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);
}

/* ===== DEBUG BOX ===== */
.debug-box{position:fixed;top:15px;left:15px;right:15px;background:rgba(0,0,0,.9);color:#22c55e;padding:15px 20px;border-radius:12px;font-family:'Courier New',monospace;font-size:13px;z-index:99999;line-height:1.7;border-left:4px solid #14b8a6}
.debug-box strong{color:#fbbf24}

/* ===== LOGIN CARD ===== */
.login-container{
  background:#ffffff;
  border-radius:24px;
  box-shadow:
    0 20px 60px rgba(15,118,110,.15),
    0 8px 24px rgba(15,23,42,.08),
    0 0 0 1px rgba(20,184,166,.06);
  max-width:460px;width:100%;
  padding:48px 44px 42px;
  position:relative;z-index:1;
  animation:cardIn .6s cubic-bezier(.2,.9,.3,1.05);
  border-top:4px solid #0f766e;
}
@keyframes cardIn{
  from{opacity:0;transform:translateY(24px) scale(.97)}
  to{opacity:1;transform:translateY(0) scale(1)}
}

/* ===== BRAND / HEADER ===== */
.login-brand{text-align:center;margin-bottom:32px}
.login-brand .brand-icon{
  width:64px;height:64px;border-radius:16px;
  background:linear-gradient(135deg,#14b8a6,#0f766e);
  color:#fff;display:inline-flex;align-items:center;justify-content:center;
  font-size:26px;margin-bottom:16px;
  box-shadow:0 10px 24px rgba(15,118,110,.35);
  position:relative;
}
.login-brand .brand-icon::after{
  content:'';position:absolute;inset:-4px;border-radius:20px;
  border:2px solid rgba(20,184,166,.2);
}
.login-brand h1{font-size:24px;font-weight:800;color:#0f172a;letter-spacing:-.3px}
.login-brand p{font-size:12.5px;color:#64748b;margin-top:6px;font-weight:500;letter-spacing:.3px;text-transform:uppercase}

/* ===== FORM ===== */
.form-group{margin-bottom:18px}
.form-group label{
  display:block;font-weight:600;color:#334155;
  font-size:12.5px;margin-bottom:8px;
  text-transform:uppercase;letter-spacing:.4px;
}
.input-wrapper{position:relative}
.input-wrapper .field-icon{
  position:absolute;left:16px;top:50%;transform:translateY(-50%);
  color:#94a3b8;font-size:15px;pointer-events:none;
  transition:color .2s;
}
.input-wrapper input{
  width:100%;
  padding:14px 16px 14px 46px;
  border:1.5px solid #e2e8f0;
  border-radius:12px;
  font-size:14.5px;
  background:#f8fafc;
  color:#0f172a;
  font-family:inherit;
  transition:all .2s;
}
.input-wrapper input::placeholder{color:#94a3b8}
.input-wrapper input:focus{
  border-color:#14b8a6;
  outline:none;
  box-shadow:0 0 0 4px rgba(20,184,166,.15);
  background:#fff;
}
.input-wrapper input:focus ~ .field-icon{color:#0f766e}
.password-wrapper input{padding-right:52px}
.toggle-password{
  position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:transparent;border:none;color:#94a3b8;
  font-size:15px;cursor:pointer;padding:8px 10px;border-radius:8px;
  transition:all .2s;
}
.toggle-password:hover{color:#0d9488;background:#f0fdfa}

/* ===== FORGOT LINK ===== */
.forgot-row{text-align:right;margin-top:-4px;margin-bottom:22px}
.forgot-row a{
  font-size:12.5px;color:#64748b;text-decoration:none;font-weight:600;
  transition:color .2s;
}
.forgot-row a:hover{color:#0f766e;text-decoration:underline}

/* ===== BUTTON ===== */
.btn-login{
  width:100%;
  padding:15px;
  background:linear-gradient(135deg,#14b8a6,#0f766e);
  color:#fff;border:none;border-radius:12px;
  font-size:15px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:10px;
  box-shadow:0 8px 20px rgba(15,118,110,.32);
  transition:all .25s ease;
  font-family:inherit;
  letter-spacing:.3px;
}
.btn-login:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(15,118,110,.42);
  background:linear-gradient(135deg,#0d9488,#115e59);
}
.btn-login:active{transform:translateY(0)}
.btn-login:disabled{opacity:.75;cursor:not-allowed;transform:none;box-shadow:0 4px 12px rgba(15,118,110,.2)}

/* ===== ALERTS ===== */
.alert-error{
  background:#fef2f2;color:#991b1b;
  padding:13px 16px;border-radius:11px;
  font-size:13px;margin-bottom:20px;
  border-left:4px solid #dc2626;
  display:flex;align-items:flex-start;gap:10px;line-height:1.5;
  animation:shake .4s ease;
}
.alert-error i{font-size:15px;margin-top:1px;flex-shrink:0}
@keyframes shake{
  0%,100%{transform:translateX(0)}
  25%{transform:translateX(-4px)}
  75%{transform:translateX(4px)}
}

/* ===== CAPS WARNING ===== */
#capsWarning{margin-top:10px;margin-bottom:0;font-size:12.5px;padding:11px 14px}

/* ===== FOOTER ===== */
.login-footer{
  text-align:center;margin-top:26px;padding-top:20px;
  border-top:1px solid #f1f5f9;
  font-size:11.5px;color:#94a3b8;font-weight:500;
}
.login-footer i{color:#14b8a6;margin-right:4px}

/* ===== RESPONSIVE ===== */
@media (max-width:520px){
  .login-container{padding:36px 28px 32px;border-radius:20px}
  .login-brand h1{font-size:20px}
  .login-brand .brand-icon{width:56px;height:56px;font-size:22px}
  body::before,body::after{opacity:.6}
}
</style>
</head>
<body>

<?php if ($show_debug && !empty($debug_info)): ?>
    <div class="debug-box">
        <strong>DEBUG</strong><br>
        <?php foreach ($debug_info as $info): ?>→ <?= htmlspecialchars($info) ?><br><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="login-container">
    <div class="login-brand">
        <div class="brand-icon"><i class="fas fa-heart-pulse"></i></div>
        <h1>Hospital Billing</h1>
        <p>Sign in to continue</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $show_debug ? '?debug=1' : '' ?>" id="loginForm" autocomplete="off">
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrapper">
                <i class="fas fa-user field-icon"></i>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper password-wrapper">
                <i class="fas fa-lock field-icon"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="toggle-password" onclick="togglePassword()"><i class="fas fa-eye" id="eyeIcon"></i></button>
            </div>
            <div id="capsWarning" class="alert-error" style="display:none;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>Caps Lock is ON. Please turn it off before signing in.</div>
            </div>
        </div>

        <div class="forgot-row"><a href="forgot_password.php">Forgot password?</a></div>

        <button type="submit" class="btn-login" id="loginBtn">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
    </form>

    <div class="login-footer">
    </div>
</div>

<script>
const BLOCK_CAPS_LOCK = <?= $block_caps_lock ? 'true' : 'false' ?>;
let capsLockOn = false;
const pwdInput    = document.getElementById('password');
const capsWarning = document.getElementById('capsWarning');
const loginBtn    = document.getElementById('loginBtn');
function updateCapsLock(e) {
    if (!e.getModifierState) return;
    capsLockOn = e.getModifierState('CapsLock');
    capsWarning.style.display = capsLockOn ? 'flex' : 'none';
    if (BLOCK_CAPS_LOCK) loginBtn.disabled = capsLockOn;
}
['keydown','keyup','keypress'].forEach(evt => pwdInput.addEventListener(evt, updateCapsLock));
pwdInput.addEventListener('focus', updateCapsLock);
function togglePassword() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') { pwd.type='text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else { pwd.type='password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (BLOCK_CAPS_LOCK && capsLockOn) { e.preventDefault(); capsWarning.style.display='flex'; pwdInput.focus(); return false; }
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Signing in...';
});
</script>
</body>
</html>