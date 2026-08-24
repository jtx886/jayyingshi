<?php
require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    header('Location: search.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $code = trim($_POST['code'] ?? '');

    $errors = [];

    if (!$username || !$email || !$password || !$confirm_password || !$code) {
        $errors[] = '请填写完整信息';
    }
    if (mb_strlen($username) < 2 || mb_strlen($username) > 20) {
        $errors[] = '用户名长度需在2-20个字符之间';
    }
    if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
        $errors[] = '邮箱格式不正确';
    }
    if (strlen($password) < 6) {
        $errors[] = '密码至少需要6位';
    }
    if ($password !== $confirm_password) {
        $errors[] = '两次输入的密码不一致';
    }

    if (empty($errors)) {
        $exist = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exist) $errors[] = '该用户名已被注册';

        $exist = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exist) $errors[] = '该邮箱已被注册';
    }

    if (empty($errors)) {
        $codeRecord = $db->fetch(
            "SELECT * FROM verification_codes WHERE email = ? AND code = ? AND type = 'register' AND expires_at > datetime('now', 'localtime') ORDER BY id DESC LIMIT 1",
            [$email, $code]
        );

        if (!$codeRecord) {
            $errors[] = '验证码错误或已过期';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hash,
            'avatar' => '',
            'status' => 1,
            'ban_until' => '',
            'ban_reason' => ''
        ]);

        $db->delete('verification_codes', 'id = ?', [$codeRecord['id']]);

        $success = '注册成功！请登录';
    } else {
        $error = implode('；', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>注册 - Jay影视</title>
<style>
:root {
    --primary: #05d4c7;
    --primary-dark: #04b8ad;
    --primary-light: #3de8dc;
    --secondary: #0e1929;
    --accent: #1f80d6;
    --bg: #0b1019;
    --card: #161f2e;
    --card-hover: #1c2738;
    --text: #ffffff;
    --text-secondary: #b3b3b3;
    --text-muted: #6b7a8d;
    --border: rgba(255,255,255,0.08);
    --border-light: rgba(255,255,255,0.15);
    --danger: #ef4444;
    --success: #10b981;
    --warning: #f59e0b;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse at top left, rgba(5,212,199,0.12) 0%, transparent 50%),
        radial-gradient(ellipse at bottom right, rgba(31,128,214,0.1) 0%, transparent 50%),
        radial-gradient(ellipse at top right, rgba(5,212,199,0.05) 0%, transparent 40%);
    pointer-events: none;
    z-index: -1;
}

.auth-card {
    width: 100%;
    max-width: 520px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 36px 34px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.5);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.auth-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
}

.brand {
    text-align: center;
    margin-bottom: 24px;
}

.brand-logo {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 12px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 8px 24px rgba(5,212,199,0.3);
}

.brand-logo::after {
    content: '';
    width: 0; height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-left: 16px solid #fff;
    margin-left: 3px;
}

.brand-name {
    font-size: 22px;
    font-weight: 900;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
}

.form-title {
    font-size: 26px;
    font-weight: 800;
    text-align: center;
    margin-bottom: 6px;
}

.form-subtitle {
    color: var(--text-secondary);
    text-align: center;
    font-size: 14px;
    margin-bottom: 26px;
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: shakeIn 0.35s ease;
}

@keyframes shakeIn {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}

.alert-error {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.35);
    color: #fca5a5;
}

.alert-error::before {
    content: '';
    display: inline-block;
    width: 16px; height: 16px;
    background: var(--danger);
    border-radius: 50%;
    flex-shrink: 0;
    position: relative;
}

.alert-error::after {
    content: '!';
    position: absolute;
    color: #fff;
    font-weight: bold;
    font-size: 12px;
    line-height: 16px;
    text-align: center;
    width: 16px;
    left: 0;
}

.alert-success {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.35);
    color: #6ee7b7;
}

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

.input-wrap {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.icon-user, .icon-mail, .icon-lock, .icon-eye, .icon-eye-off {
    width: 18px;
    height: 18px;
    position: relative;
    display: inline-block;
}

.icon-user::before {
    content: '';
    position: absolute;
    top: 0; left: 50%;
    width: 8px; height: 8px;
    background: var(--text-muted);
    border-radius: 50%;
    transform: translateX(-50%);
}

.icon-user::after {
    content: '';
    position: absolute;
    bottom: 0; left: 50%;
    width: 14px; height: 7px;
    background: var(--text-muted);
    border-radius: 7px 7px 0 0;
    transform: translateX(-50%);
}

.icon-lock::before {
    content: '';
    position: absolute;
    top: 2px; left: 50%;
    width: 10px; height: 6px;
    border: 2px solid var(--text-muted);
    border-bottom: none;
    border-radius: 5px 5px 0 0;
    transform: translateX(-50%);
}

.icon-lock::after {
    content: '';
    position: absolute;
    bottom: 0; left: 50%;
    width: 16px; height: 10px;
    background: var(--text-muted);
    border-radius: 3px;
    transform: translateX(-50%);
}

.icon-mail::before {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 18px; height: 12px;
    border: 2px solid var(--text-muted);
    border-radius: 2px;
    transform: translate(-50%, -50%);
}

.icon-eye {
    cursor: pointer;
}

.icon-eye::before {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 18px; height: 12px;
    border: 2px solid var(--text-muted);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

.icon-eye::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 5px; height: 5px;
    background: var(--text-muted);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

.input-wrap input {
    width: 100%;
    padding: 13px 44px 13px 44px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    font-size: 14px;
    transition: all 0.25s;
}

.input-wrap input::placeholder {
    color: var(--text-muted);
}

.input-wrap input:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(5,212,199,0.06);
    box-shadow: 0 0 0 3px rgba(5,212,199,0.15);
}

.input-action {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.input-suffix-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    padding: 8px 14px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.2s;
}

.input-suffix-btn:hover:not(:disabled) {
    opacity: 0.85;
}

.input-suffix-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.input-suffix-input {
    padding-right: 130px !important;
}

.form-row {
    display: flex;
    gap: 12px;
}

.form-row .form-group {
    flex: 1;
}

.password-strength {
    margin-top: 8px;
    display: none;
}

.strength-bar {
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 6px;
}

.strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 2px;
    transition: all 0.3s;
}

.strength-text {
    font-size: 12px;
    color: var(--text-muted);
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 8px 24px rgba(5,212,199,0.35);
    letter-spacing: 0.5px;
    margin-top: 8px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(5,212,199,0.5);
}

.btn-submit:active {
    transform: translateY(0);
}

.auth-footer {
    text-align: center;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
    color: var(--text-secondary);
    font-size: 14px;
}

.auth-footer a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    margin-left: 4px;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.back-link {
    position: absolute;
    top: 20px;
    left: 20px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.back-link:hover { color: var(--primary); }

.back-link::before {
    content: '<';
    font-weight: 700;
}

@media (max-width: 520px) {
    .auth-card {
        padding: 28px 22px;
        border-radius: 16px;
    }
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    .form-title { font-size: 22px; }
}
</style>
</head>
<body>

<a href="login.php" class="back-link">返回登录</a>

<div class="auth-card">
    <div class="brand">
        <div class="brand-logo"></div>
        <div class="brand-name">Jay影视</div>
    </div>

    <h1 class="form-title">创建新账号</h1>
    <p class="form-subtitle">加入Jay影视，畅享海量高清影视内容</p>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <span><?php echo e($error); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <span><?php echo e($success); ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" id="registerForm">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <div class="input-wrap">
                    <span class="input-icon"><span class="icon-user"></span></span>
                    <input type="text" name="username" id="regUsername" placeholder="2-20个字符" value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">邮箱</label>
                <div class="input-wrap">
                    <span class="input-icon"><span class="icon-mail"></span></span>
                    <input type="email" name="email" id="regEmail" placeholder="your@email.com" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">密码</label>
                <div class="input-wrap">
                    <span class="input-icon"><span class="icon-lock"></span></span>
                    <input type="password" name="password" id="regPassword" placeholder="至少6位" required>
                    <button type="button" class="input-action" id="togglePwd" aria-label="显示密码">
                        <span class="icon-eye"></span>
                    </button>
                </div>
                <div class="password-strength" id="strengthMeter">
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-text" id="strengthText">密码强度</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">确认密码</label>
                <div class="input-wrap">
                    <span class="input-icon"><span class="icon-lock"></span></span>
                    <input type="password" name="confirm_password" id="regConfirm" placeholder="再次输入密码" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">邮箱验证码</label>
            <div class="input-wrap">
                <input type="text" name="code" id="regCode" maxlength="6" class="input-suffix-input" placeholder="请输入6位验证码" required>
                <button type="button" class="input-suffix-btn" id="sendCodeBtn" data-email="#regEmail">获取验证码</button>
            </div>
        </div>

        <button type="submit" class="btn-submit">注 册 账 号</button>
    </form>

    <div class="auth-footer">
        已有账号？<a href="login.php">立即登录</a>
    </div>
</div>

<script>
var togglePwd = document.getElementById('togglePwd');
var pwdInput = document.getElementById('regPassword');
var eyeIcon = togglePwd.querySelector('.icon-eye');

togglePwd.addEventListener('click', function() {
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
    } else {
        pwdInput.type = 'password';
    }
});

pwdInput.addEventListener('input', function() {
    var val = this.value;
    var meter = document.getElementById('strengthMeter');
    var fill = document.getElementById('strengthFill');
    var text = document.getElementById('strengthText');
    meter.style.display = val ? 'block' : 'none';

    var score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var colors = ['#ef4444', '#f59e0b', '#eab308', '#84cc16', '#10b981'];
    var labels = ['太弱', '较弱', '一般', '良好', '强'];
    var pct = score * 20;

    fill.style.width = pct + '%';
    fill.style.background = colors[Math.min(score, 4)];
    text.textContent = '密码强度: ' + labels[Math.min(score, 4)];
    text.style.color = colors[Math.min(score, 4)];
});

var sendCodeBtn = document.getElementById('sendCodeBtn');
var origText = sendCodeBtn.textContent;

sendCodeBtn.addEventListener('click', function() {
    var emailEl = document.querySelector(sendCodeBtn.getAttribute('data-email'));
    var email = emailEl ? emailEl.value.trim() : '';

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('请输入正确的邮箱地址');
        if (emailEl) emailEl.focus();
        return;
    }

    fetch('api/auth.php?action=send_code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email) + '&type=register'
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (!res || res.code !== 200) {
            alert((res && res.message) || '发送失败，请稍后重试');
            return;
        }
        var count = 60;
        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = count + '秒后重发';
        var timer = setInterval(function() {
            count--;
            if (count <= 0) {
                clearInterval(timer);
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = origText;
            } else {
                sendCodeBtn.textContent = count + '秒后重发';
            }
        }, 1000);
    }).catch(function() {
        alert('发送失败，请稍后重试');
    });
});

document.getElementById('registerForm').addEventListener('submit', function(e) {
    var username = document.getElementById('regUsername').value.trim();
    var email = document.getElementById('regEmail').value.trim();
    var password = document.getElementById('regPassword').value;
    var confirm = document.getElementById('regConfirm').value;
    var code = document.getElementById('regCode').value.trim();

    if (!username || !email || !password || !confirm || !code) {
        e.preventDefault();
        alert('请填写所有必填字段');
        return;
    }
    if (password !== confirm) {
        e.preventDefault();
        alert('两次输入的密码不一致');
        return;
    }
});
</script>

</body>
</html>
