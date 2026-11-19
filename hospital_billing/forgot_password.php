<?php
session_start();

/* =========================================================
   FORGOT PASSWORD — username only, no code, no link
   Matches login.php design + uses config.php $pdo
   ========================================================= */

require_once __DIR__ . '/config.php';

/* ---- Rate limit: max 5 resets per 15 min per session ---- */
define('MAX_RESETS', 5);
define('RESET_WINDOW', 900);

if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = [];
}

/* ---- Helper: is account usable? (mirrors login.php rule) ---- */
function account_active(array $user): bool {
    $s = $user['status'] ?? 'Active';
    return $s === 'Active' || $s === '' || $s === null;
}

/* =========================================================
   AJAX HANDLER
   ========================================================= */
$isAjax = ($_SERVER['REQUEST_METHOD'] === 'POST')
       && (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

if ($isAjax) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $now    = time();

    // prune old attempts
    $_SESSION['reset_attempts'] = array_values(array_filter(
        $_SESSION['reset_attempts'],
        fn($t) => ($now - $t) < RESET_WINDOW
    ));

    try {
        /* ---------- ACTION 1: check username ---------- */
        if ($action === 'check_username') {
            $username = trim($_POST['username'] ?? '');
            if ($username === '') {
                echo json_encode(['error' => 'Username is required.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, username, status FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['exists' => false, 'error' => 'That username does not exist.']);
                exit;
            }
            if (!account_active($user)) {
                echo json_encode(['exists' => false, 'error' => 'Your account is inactive. Please contact the administrator.']);
                exit;
            }

            echo json_encode(['exists' => true, 'username' => $user['username']]);
            exit;
        }

        /* ---------- ACTION 2: reset password ---------- */
        if ($action === 'reset_password') {
            if (count($_SESSION['reset_attempts']) >= MAX_RESETS) {
                echo json_encode(['error' => 'Too many reset attempts. Try again in 15 minutes.']);
                exit;
            }
            $_SESSION['reset_attempts'][] = $now;

            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $password === '') {
                echo json_encode(['error' => 'Missing username or password.']);
                exit;
            }
            if (strlen($password) < 8) {
                echo json_encode(['error' => 'Password must be at least 8 characters.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, username, status FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['error' => 'That username does not exist.']);
                exit;
            }
            if (!account_active($user)) {
                echo json_encode(['error' => 'Your account is inactive. Please contact the administrator.']);
                exit;
            }

            // New hash (login.php already supports password_hash via password_verify)
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, (int)$user['id']]);

            // Clear any login lockout in this session
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_until']  = 0;

            // Audit log
            error_log(
                '[PASSWORD RESET] user=' . $user['username'] .
                ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?') .
                ' time=' . date('c')
            );

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['error' => 'Unknown action.']);
        exit;

    } catch (PDOException $e) {
        error_log('Forgot password DB error: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error. Please try again.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:'Segoe UI',Tahoma,Verdana,sans-serif;
  min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  padding:20px;position:relative;overflow-x:hidden;
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
  border-radius:50%;pointer-events:none;filter:blur(10px);
}
body::after{
  content:'';position:fixed;bottom:-20%;left:-12%;
  width:620px;height:620px;
  background:radial-gradient(circle, rgba(15,118,110,.15) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);
}
.login-container{
  background:#fff;border-radius:24px;
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

.form-group{margin-bottom:18px}
.form-group label{
  display:block;font-weight:600;color:#334155;
  font-size:12.5px;margin-bottom:8px;
  text-transform:uppercase;letter-spacing:.4px;
}
.input-wrapper{position:relative}
.input-wrapper .field-icon{
  position:absolute;left:16px;top:50%;transform:translateY(-50%);
  color:#94a3b8;font-size:15px;pointer-events:none;transition:color .2s;
}
.input-wrapper input{
  width:100%;padding:14px 16px 14px 46px;
  border:1.5px solid #e2e8f0;border-radius:12px;
  font-size:14.5px;background:#f8fafc;color:#0f172a;
  font-family:inherit;transition:all .2s;
}
.input-wrapper input::placeholder{color:#94a3b8}
.input-wrapper input:focus{
  border-color:#14b8a6;outline:none;
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

.btn-login{
  width:100%;padding:15px;
  background:linear-gradient(135deg,#14b8a6,#0f766e);
  color:#fff;border:none;border-radius:12px;
  font-size:15px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:10px;
  box-shadow:0 8px 20px rgba(15,118,110,.32);
  transition:all .25s ease;font-family:inherit;letter-spacing:.3px;
  margin-top:6px;
}
.btn-login:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(15,118,110,.42);
  background:linear-gradient(135deg,#0d9488,#115e59);
}
.btn-login:active{transform:translateY(0)}
.btn-login:disabled{opacity:.75;cursor:not-allowed;transform:none;box-shadow:0 4px 12px rgba(15,118,110,.2)}

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

.rule{font-size:13px;color:#64748b;margin:6px 0 0;padding-left:4px}
.rule.ok{color:#0f766e;font-weight:600}

.link-row{
  text-align:center;margin-top:20px;padding-top:20px;
  border-top:1px solid #f1f5f9;font-size:13px;
}
.link-row a{color:#0f766e;text-decoration:none;font-weight:600}
.link-row a:hover{text-decoration:underline}

.screen{display:none}
.screen.active{display:block}
.success-icon{
  width:72px;height:72px;border-radius:50%;
  background:#f0fdfa;color:#0f766e;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:32px;margin-bottom:16px;
  box-shadow:0 8px 20px rgba(20,184,166,.25);
}
.center{text-align:center}
.center h2{color:#0f172a;font-size:22px;margin-bottom:10px}
.center p{color:#64748b;font-size:14px;margin-bottom:6px}

@media (max-width:520px){
  .login-container{padding:36px 28px 32px;border-radius:20px}
  .login-brand h1{font-size:20px}
  .login-brand .brand-icon{width:56px;height:56px;font-size:22px}
  body::before,body::after{opacity:.6}
}
</style>
</head>
<body>

<div class="login-container">

  <!-- ============ Screen 1 — Enter username ============ -->
  <div class="screen active" id="screen1">
    <div class="login-brand">
      <div class="brand-icon"><i class="fas fa-key"></i></div>
      <h1>Forgot Password</h1>
      <p>Reset your account</p>
    </div>

    <div class="form-group">
      <label for="username">Username</label>
      <div class="input-wrapper">
        <i class="fas fa-user field-icon"></i>
        <input type="text" id="username" placeholder="Enter your username" autofocus autocomplete="username">
      </div>
    </div>

    <div class="alert-error" id="error1" style="display:none;">
      <i class="fas fa-exclamation-circle"></i>
      <div id="error1Text"></div>
    </div>

    <button type="button" class="btn-login" id="btnContinue">
      <i class="fas fa-arrow-right"></i> Continue
    </button>

    <div class="link-row"><a href="login.php">← Back to login</a></div>
  </div>

  <!-- ============ Screen 2 — Set new password ============ -->
  <div class="screen" id="screen2">
    <div class="login-brand">
      <div class="brand-icon"><i class="fas fa-lock"></i></div>
      <h1>New Password</h1>
      <p>For <span id="unameDisplay" style="color:#0f766e;font-weight:700"></span></p>
    </div>

    <div class="form-group">
      <label for="password">New password</label>
      <div class="input-wrapper password-wrapper">
        <i class="fas fa-lock field-icon"></i>
        <input type="password" id="password" placeholder="Enter new password" autocomplete="new-password">
        <button type="button" class="toggle-password" onclick="togglePwd('password','eye1')">
          <i class="fas fa-eye" id="eye1"></i>
        </button>
      </div>
      <p class="rule" id="ruleLen">At least 8 characters</p>
    </div>

    <div class="form-group">
      <label for="password2">Confirm password</label>
      <div class="input-wrapper password-wrapper">
        <i class="fas fa-lock field-icon"></i>
        <input type="password" id="password2" placeholder="Confirm new password" autocomplete="new-password">
        <button type="button" class="toggle-password" onclick="togglePwd('password2','eye2')">
          <i class="fas fa-eye" id="eye2"></i>
        </button>
      </div>
    </div>

    <div class="alert-error" id="error2" style="display:none;">
      <i class="fas fa-exclamation-circle"></i>
      <div id="error2Text"></div>
    </div>

    <button type="button" class="btn-login" id="btnReset">
      <i class="fas fa-check"></i> Reset Password
    </button>

    <div class="link-row"><a href="#" id="backTo1">← Back</a></div>
  </div>

  <!-- ============ Screen 3 — Success ============ -->
  <div class="screen center" id="screen3">
    <div class="success-icon"><i class="fas fa-check"></i></div>
    <h2>Password updated</h2>
    <p>You can now sign in with your new password.</p>
    <div class="link-row"><a href="login.php">→ Go to login</a></div>
  </div>

</div>

<script>
(function () {
  const $ = (id) => document.getElementById(id);
  const screens = { 1: $('screen1'), 2: $('screen2'), 3: $('screen3') };
  const show = (n) => {
    Object.values(screens).forEach(s => s.classList.remove('active'));
    screens[n].classList.add('active');
    const first = screens[n].querySelector('input');
    if (first) first.focus();
  };
  const showError = (which, msg) => {
    const box = $('error' + which);
    const txt = $('error' + which + 'Text');
    if (msg) { txt.textContent = msg; box.style.display = 'flex'; }
    else { box.style.display = 'none'; txt.textContent = ''; }
  };

  let currentUsername = '';

  async function post(fields) {
    const res = await fetch(window.location.href, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams(fields)
    });
    return res.json();
  }

  /* ---- Screen 1: Continue ---- */
  const btnContinue = $('btnContinue');
  btnContinue.addEventListener('click', async () => {
    const username = $('username').value.trim();
    showError(1, '');
    if (!username) { showError(1, 'Please enter your username.'); return; }

    btnContinue.disabled = true;
    const old = btnContinue.innerHTML;
    btnContinue.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking...';

    try {
      const data = await post({ action: 'check_username', username });
      if (!data.exists) { showError(1, data.error || 'That username does not exist.'); return; }

      currentUsername = data.username || username;
      $('unameDisplay').textContent = currentUsername;
      $('password').value = '';
      $('password2').value = '';
      showError(2, '');
      updateRule('');
      show(2);
    } catch (e) {
      showError(1, 'Network error. Please try again.');
    } finally {
      btnContinue.disabled = false;
      btnContinue.innerHTML = old;
    }
  });

  /* ---- Toggle password ---- */
  window.togglePwd = function (inputId, iconId) {
    const p = $(inputId), i = $(iconId);
    if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
    else { p.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
  };

  /* ---- Live password rule ---- */
  const ruleLen = $('ruleLen');
  function updateRule(val) {
    if (val.length >= 8) {
      ruleLen.textContent = '✓ At least 8 characters';
      ruleLen.classList.add('ok');
    } else {
      ruleLen.textContent = 'At least 8 characters';
      ruleLen.classList.remove('ok');
    }
  }
  $('password').addEventListener('input', (e) => updateRule(e.target.value));

  /* ---- Screen 2: Reset ---- */
  const btnReset = $('btnReset');
  btnReset.addEventListener('click', async () => {
    const p1 = $('password').value;
    const p2 = $('password2').value;
    showError(2, '');

    if (p1.length < 8) { showError(2, 'Password must be at least 8 characters.'); return; }
    if (p1 !== p2)     { showError(2, 'Passwords do not match.'); return; }

    btnReset.disabled = true;
    const old = btnReset.innerHTML;
    btnReset.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Updating...';

    try {
      const data = await post({ action: 'reset_password', username: currentUsername, password: p1 });
      if (data.error) { showError(2, data.error); return; }
      show(3);
    } catch (e) {
      showError(2, 'Network error. Please try again.');
    } finally {
      btnReset.disabled = false;
      btnReset.innerHTML = old;
    }
  });

  /* ---- Back ---- */
  $('backTo1').addEventListener('click', (e) => {
    e.preventDefault();
    showError(2, '');
    show(1);
  });

  /* ---- Enter shortcuts ---- */
  $('username').addEventListener('keydown', e => { if (e.key === 'Enter') btnContinue.click(); });
  $('password2').addEventListener('keydown', e => { if (e.key === 'Enter') btnReset.click(); });

  $('username').focus();
})();
</script>
</body>
</html>