<?php
require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    header('Location: search.php');
    exit;
}

$error = '';
$message = '';
$redirect = $_GET['redirect'] ?? ($_SESSION['redirect_after_login'] ?? 'search.php');
$remembered_account = '';

if (isset($_COOKIE['jay_remember'])) {
    $remembered_account = $_COOKIE['jay_remember'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = trim($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? 1 : 0;

    if (!$account || !$password) {
        $error = '请填写完整信息';
    } else {
        $user = $db->fetch("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1", [$account, $account]);
        if (!$user) {
            $error = '账号不存在';
        } elseif ($user['status'] == 0) {
            $error = '您的账号已被封禁，请联系管理员';
        } elseif (!password_verify($password, $user['password'])) {
            $error = '密码错误，请重试';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['avatar'] = $user['avatar'] ?? '';

            if ($remember) {
                setcookie('jay_remember', $user['username'], time() + 86400 * 30, '/');
            } else {
                setcookie('jay_remember', '', time() - 3600, '/');
            }

            $url = $redirect ?: 'search.php';
            header('Location: ' . $url);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 - Jay影视</title>
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
    max-width: 440px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 40px 36px;
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
    margin-bottom: 28px;
}

.brand-logo {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 12px;
    margin: 0 auto 14px;
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
    margin-bottom: 28px;
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
    margin-bottom: 18px;
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

.icon-mail::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 18px; height: 8px;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 5px solid var(--text-muted);
    transform: translate(-50%, -20%);
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
    transition: color 0.2s;
}

.input-action:hover {
    color: var(--text-secondary);
}

.remember-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    font-size: 13px;
}

.checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    color: var(--text-secondary);
}

.checkbox-wrap input { display: none; }

.checkbox-custom {
    width: 18px; height: 18px;
    border: 2px solid var(--border-light);
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.checkbox-wrap input:checked + .checkbox-custom {
    background: var(--primary);
    border-color: var(--primary);
}

.checkbox-wrap input:checked + .checkbox-custom::after {
    content: '';
    width: 4px; height: 8px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg) translate(-1px, -1px);
}

.forgot-link {
    color: var(--primary);
    font-size: 13px;
    text-decoration: none;
    transition: opacity 0.2s;
}

.forgot-link:hover { opacity: 0.8; }

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
    margin-top: 24px;
    padding-top: 20px;
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
    transition: color 0.2s;
}

.back-link:hover { color: var(--primary); }

.back-link::before {
    content: '<';
    font-weight: 700;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 32px 24px;
        border-radius: 16px;
    }
    .form-title { font-size: 22px; }
    .brand-name { font-size: 20px; }
}
</style>
</head>
<body>

<a href="search.php" class="back-link">返回首页</a>

<div class="auth-card">
    <div class="brand">
        <div class="brand-logo"></div>
        <div class="brand-name">Jay影视</div>
    </div>

    <h1 class="form-title">欢迎回来</h1>
    <p class="form-subtitle">登录账号继续享受精彩视频</p>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <span><?php echo e($error); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success">
        <span><?php echo e($message); ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
        <div class="form-group">
            <label class="form-label">用户名 / 邮箱</label>
            <div class="input-wrap">
                <span class="input-icon"><span class="icon-user"></span></span>
                <input type="text" name="account" id="account" placeholder="请输入用户名或邮箱" value="<?php echo e($remembered_account); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">密码</label>
            <div class="input-wrap">
                <span class="input-icon"><span class="icon-lock"></span></span>
                <input type="password" name="password" id="password" placeholder="请输入密码" required>
                <button type="button" class="input-action" id="togglePwd" aria-label="显示密码">
                    <span class="icon-eye"></span>
                </button>
            </div>
        </div>

        <div class="remember-row">
            <label class="checkbox-wrap">
                <input type="checkbox" name="remember" id="remember">
                <span class="checkbox-custom"></span>
                <span>记住我</span>
            </label>
            <a href="register.php" class="forgot-link">忘记密码？</a>
        </div>

        <button type="submit" class="btn-submit">登 录</button>
    </form>

    <div class="auth-footer">
        还没有账号？<a href="register.php">立即注册</a>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    var pwd = document.getElementById('password');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        this.querySelector('.icon-eye').classList.replace('icon-eye', 'icon-eye-off');
        this.querySelector('.icon-eye-off').style.background = '#b3b3b3';
    } else {
        pwd.type = 'password';
        this.querySelector('.icon-eye-off').classList.replace('icon-eye-off', 'icon-eye');
    }
});

document.getElementById('loginForm').addEventListener('submit', function(e) {
    var account = document.getElementById('account').value.trim();
    var password = document.getElementById('password').value;
    if (!account || !password) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
