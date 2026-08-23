<?php
/**
 * Jay影视 - 用户注册页面
 */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
    exit;
}

if (empty($_SESSION[SESSION_PREFIX . 'csrf_token'])) {
    $_SESSION[SESSION_PREFIX . 'csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrfToken = $_SESSION[SESSION_PREFIX . 'csrf_token'];

$error = '';
$success = '';
$oldEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$oldUsername = isset($_POST['username']) ? trim($_POST['username']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {

    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $error = '安全验证失败，请刷新页面重试';
        goto renderPage;
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $code = isset($_POST['verification_code']) ? trim($_POST['verification_code']) : '';

    $oldEmail = $email;
    $oldUsername = $username;

    if (empty($email)) {
        $error = '请输入邮箱地址';
        goto renderPage;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
        goto renderPage;
    }
    if (empty($username)) {
        $error = '请输入用户名';
        goto renderPage;
    }
    if (!preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9]{4,20}$/u', $username)) {
        $error = '用户名需4-20字符，仅支持字母、数字或中文';
        goto renderPage;
    }
    if (empty($password)) {
        $error = '请输入密码';
        goto renderPage;
    }
    if (strlen($password) < 6 || strlen($password) > 20) {
        $error = '密码需6-20字符';
        goto renderPage;
    }
    if ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
        goto renderPage;
    }
    if (empty($code)) {
        $error = '请输入邮箱验证码';
        goto renderPage;
    }

    try {
        $pdo = getDB();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute(array($email));
        if ($stmt->fetch()) {
            $error = '该邮箱已被注册';
            goto renderPage;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute(array($username));
        if ($stmt->fetch()) {
            $error = '该用户名已被使用';
            goto renderPage;
        }

        $verifyResult = verifyCodeCompat($pdo, $email, $code, 'register');
        if (!$verifyResult) {
            $error = '验证码错误或已过期';
            goto renderPage;
        }

        $now = time();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, avatar, is_admin, is_banned, ban_start_time, ban_end_time, created_at, updated_at) VALUES (?, ?, ?, ?, 0, 0, 0, 0, ?, ?)");
        $stmt->execute(array($email, $username, $hashedPassword, '', $now, $now));
        $userId = intval($pdo->lastInsertId());

        sendWelcomeEmail($email, $username);

        $_SESSION[SESSION_PREFIX . 'user_id'] = $userId;

        $success = '注册成功！正在跳转...';
        $oldEmail = '';
        $oldUsername = '';

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>注册成功</title>';
        echo '<meta http-equiv="refresh" content="2;url=' . e(SITE_URL . '/index.php') . '"></head><body>';
        echo '<script type="text/javascript">window.location.href="' . e(SITE_URL . '/index.php') . '";</script>';
        echo '</body></html>';
        exit;

    } catch (Exception $e) {
        $error = '注册失败：' . $e->getMessage();
    }
}

renderPage:

if (!function_exists('verifyCodeCompat')) {
function verifyCodeCompat($pdo, $email, $code, $type)
{
    $email = trim($email);
    $code = trim($code);
    if (empty($email) || empty($code)) {
        return false;
    }
    try {
        $stmt = $pdo->query("PRAGMA table_info(verification_codes)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $expireField = 'expire_time';
        foreach ($columns as $col) {
            if (strtolower($col['name']) === 'expire_at') {
                $expireField = 'expire_at';
                break;
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE email = ? AND type = ? AND used = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute(array($email, $type));
        $record = $stmt->fetch();
        if (!$record) {
            return false;
        }
        $expireVal = isset($record[$expireField]) ? intval($record[$expireField]) : 0;
        if ($expireVal < time()) {
            return false;
        }
        if (strval($record['code']) !== strval($code)) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE verification_codes SET used = 1 WHERE id = ?");
        $stmt->execute(array($record['id']));
        return true;
    } catch (Exception $e) {
        return false;
    }
}
}

if (!function_exists('sendWelcomeEmail')) {
function sendWelcomeEmail($email, $username)
{
    $siteName = SITE_NAME;
    $siteUrl = SITE_URL;
    $year = date('Y');

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>欢迎加入 {$siteName}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #eef7ff;
        padding: 20px;
        color: #333;
    }
    .email-wrapper {
        max-width: 600px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(1, 180, 228, 0.15);
    }
    .email-header {
        background: linear-gradient(135deg, #667eea 0%, #01b4e4 100%);
        padding: 40px 30px 35px;
        text-align: center;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .email-header::before {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        top: -80px;
        left: -60px;
    }
    .email-header::after {
        content: '';
        position: absolute;
        width: 140px;
        height: 140px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
        bottom: -60px;
        right: -30px;
    }
    .logo-text {
        position: relative;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 2px;
    }
    .email-header p {
        position: relative;
        font-size: 14px;
        opacity: 0.95;
    }
    .welcome-badge {
        display: inline-block;
        background: rgba(255,255,255,0.25);
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        margin-top: 14px;
        position: relative;
        backdrop-filter: blur(4px);
    }
    .email-body {
        padding: 35px 32px;
    }
    .greeting-box {
        background: linear-gradient(135deg, #f5faff 0%, #eef8ff 100%);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        border-left: 4px solid #01b4e4;
    }
    .greeting-text {
        font-size: 17px;
        color: #333;
        line-height: 1.7;
    }
    .greeting-text .username {
        color: #01b4e4;
        font-weight: 600;
    }
    .features-title {
        font-size: 16px;
        color: #333;
        margin: 25px 0 15px;
        font-weight: 600;
        padding-left: 10px;
        border-left: 3px solid #667eea;
    }
    .features-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 25px;
    }
    .feature-item {
        flex: 1 1 45%;
        min-width: 240px;
        background: #fafbfc;
        border-radius: 10px;
        padding: 16px 18px;
        border: 1px solid #eef1f4;
        transition: all 0.2s;
    }
    .feature-item:hover {
        border-color: #01b4e4;
        box-shadow: 0 4px 12px rgba(1, 180, 228, 0.1);
    }
    .feature-icon {
        font-size: 22px;
        margin-bottom: 8px;
    }
    .feature-name {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    .feature-desc {
        font-size: 12px;
        color: #888;
        line-height: 1.5;
    }
    .cta-box {
        text-align: center;
        padding: 28px 20px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f4ff 100%);
        border-radius: 12px;
        margin: 25px 0;
    }
    .cta-title {
        font-size: 17px;
        color: #333;
        margin-bottom: 16px;
        font-weight: 600;
    }
    .btn-cta {
        display: inline-block;
        padding: 13px 45px;
        background: linear-gradient(135deg, #667eea 0%, #01b4e4 100%);
        color: #ffffff !important;
        text-decoration: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        box-shadow: 0 6px 20px rgba(1, 180, 228, 0.4);
        transition: all 0.2s;
    }
    .btn-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(1, 180, 228, 0.5);
    }
    .info-box {
        background: #fffdf5;
        border-left: 4px solid #ffc107;
        border-radius: 6px;
        padding: 16px 18px;
        margin-top: 22px;
    }
    .info-title {
        font-size: 14px;
        color: #f57c00;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .info-text {
        font-size: 13px;
        color: #795548;
        line-height: 1.8;
    }
    .user-card {
        background: #f8faff;
        border-radius: 10px;
        padding: 18px 20px;
        margin: 18px 0;
        display: flex;
        align-items: center;
        border: 1px solid #e0e7ff;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #01b4e4);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
        margin-right: 15px;
        flex-shrink: 0;
    }
    .user-info .label {
        font-size: 12px;
        color: #999;
    }
    .user-info .value {
        font-size: 15px;
        color: #333;
        font-weight: 500;
        margin-top: 2px;
    }
    .email-footer {
        background: #fafbfc;
        padding: 22px 30px;
        text-align: center;
        border-top: 1px solid #eef1f4;
    }
    .email-footer p {
        font-size: 12px;
        color: #999;
        line-height: 1.8;
    }
    .email-footer a {
        color: #01b4e4;
        text-decoration: none;
    }
</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <div class="logo-text">🎬 {$siteName}</div>
            <p>您的专属高清影视平台</p>
            <div class="welcome-badge">✨ 欢迎加入我们 ✨</div>
        </div>
        <div class="email-body">
            <div class="greeting-box">
                <div class="greeting-text">
                    亲爱的 <span class="username">{$username}</span>：<br><br>
                    恭喜您成功注册 {$siteName} 账号！感谢您选择我们的平台，我们将为您提供海量高清影视资源和极致的观影体验。
                </div>
            </div>

            <div class="user-card">
                <div class="user-avatar">{$username}</div>
                <div class="user-info">
                    <div class="label">您的账号信息</div>
                    <div class="value">用户名：{$username}　|　邮箱：{$email}</div>
                </div>
            </div>

            <div class="features-title">🌟 您可以享受以下特权</div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🎥</div>
                    <div class="feature-name">海量影视库</div>
                    <div class="feature-desc">数万部电影、电视剧、动漫、综艺</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔥</div>
                    <div class="feature-name">极速更新</div>
                    <div class="feature-desc">第一时间获取最热最新的影视资源</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⭐</div>
                    <div class="feature-name">高清播放</div>
                    <div class="feature-desc">支持1080P超清画质，流畅不卡顿</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📚</div>
                    <div class="feature-name">收藏记录</div>
                    <div class="feature-desc">跨设备同步观影记录和收藏夹</div>
                </div>
            </div>

            <div class="cta-box">
                <div class="cta-title">立即开始您的观影之旅</div>
                <a href="{$siteUrl}/index.php" class="btn-cta">访问 {$siteName}</a>
            </div>

            <div class="info-box">
                <div class="info-title">🔒 账号安全提醒</div>
                <div class="info-text">
                    1. 请妥善保管您的账号密码，不要与他人共用；<br>
                    2. 请勿将验证码发送给任何人，包括平台工作人员；<br>
                    3. 如遇账号问题，请及时通过站内反馈功能联系我们。
                </div>
            </div>
        </div>
        <div class="email-footer">
            <p>此邮件由 <a href="{$siteUrl}">{$siteName}</a> 自动发送，请勿直接回复</p>
            <p>&copy; {$year} {$siteName}. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;

    @sendEmail($email, '【' . SITE_NAME . '】欢迎加入，注册成功！', $body);
}
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>注册 - <?php echo e(SITE_NAME); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #01b4e4 50%, #0083b0 100%);
    position: relative;
    overflow-x: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 15px;
}
body::before {
    content: '';
    position: absolute;
    top: -200px;
    left: -200px;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
body::after {
    content: '';
    position: absolute;
    bottom: -300px;
    right: -200px;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.bg-pattern {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.08) 1px, transparent 1px),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.08) 1px, transparent 1px),
        radial-gradient(circle at 50% 90%, rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 50px 50px, 80px 80px, 100px 100px;
    pointer-events: none;
}
.register-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0, 30, 60, 0.25), 0 10px 30px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: cardIn 0.5s ease-out;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(30px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.card-header {
    padding: 36px 36px 28px;
    text-align: center;
    background: linear-gradient(135deg, #f8fbff 0%, #eef7ff 100%);
    border-bottom: 1px solid #eef1f6;
}
.logo {
    font-size: 38px;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #01b4e4 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 6px;
    letter-spacing: 2px;
}
.logo-sub {
    font-size: 14px;
    color: #888;
    margin-bottom: 14px;
}
.card-title {
    font-size: 22px;
    color: #222;
    font-weight: 700;
    margin-top: 10px;
}
.card-title::before {
    content: '✨ ';
}
.card-title::after {
    content: ' ✨';
}
.card-body {
    padding: 28px 36px 30px;
}
.alert {
    border-radius: 10px;
    padding: 13px 16px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    line-height: 1.5;
}
.alert-error {
    background: #fff5f5;
    border: 1px solid #fecaca;
    color: #c53030;
}
.alert-success {
    background: #f0fff4;
    border: 1px solid #9ae6b4;
    color: #22543d;
}
.alert-icon {
    margin-right: 10px;
    flex-shrink: 0;
    font-size: 16px;
    margin-top: 1px;
}
.form-group {
    margin-bottom: 17px;
}
.input-label {
    display: block;
    font-size: 13px;
    color: #555;
    margin-bottom: 7px;
    font-weight: 500;
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
    pointer-events: none;
    z-index: 2;
}
.form-input {
    width: 100%;
    height: 44px;
    padding: 0 14px 0 42px;
    border: 1.5px solid #e1e5eb;
    border-radius: 10px;
    font-size: 14px;
    color: #333;
    background: #fafbfc;
    transition: all 0.2s;
    outline: none;
    font-family: inherit;
}
.form-input::placeholder {
    color: #b0b7c0;
}
.form-input:focus {
    border-color: #01b4e4;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(1, 180, 228, 0.12);
}
.code-group {
    display: flex;
    gap: 10px;
}
.code-group .form-input {
    flex: 1;
}
.btn-code {
    height: 44px;
    padding: 0 16px;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: linear-gradient(135deg, #667eea 0%, #01b4e4 100%);
    color: #ffffff;
    white-space: nowrap;
    transition: all 0.2s;
    box-shadow: 0 3px 10px rgba(1, 180, 228, 0.25);
    font-family: inherit;
    flex-shrink: 0;
}
.btn-code:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(1, 180, 228, 0.35);
}
.btn-code:active:not(:disabled) {
    transform: translateY(0);
}
.btn-code:disabled {
    background: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
}
.btn-submit {
    width: 100%;
    height: 48px;
    margin-top: 8px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    background: linear-gradient(135deg, #667eea 0%, #01b4e4 100%);
    color: #ffffff;
    transition: all 0.25s;
    box-shadow: 0 8px 25px rgba(1, 180, 228, 0.35);
    letter-spacing: 1px;
    font-family: inherit;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(1, 180, 228, 0.45);
}
.btn-submit:active {
    transform: translateY(0);
    box-shadow: 0 5px 15px rgba(1, 180, 228, 0.3);
}
.btn-submit:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.card-footer {
    padding: 0 36px 32px;
    text-align: center;
    font-size: 14px;
    color: #666;
}
.card-footer a {
    color: #01b4e4;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}
.card-footer a:hover {
    color: #0083b0;
    text-decoration: underline;
}
.hint {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
    padding-left: 2px;
    line-height: 1.5;
}
@media (max-width: 520px) {
    body { padding: 10px 0; background: linear-gradient(135deg, #667eea 0%, #01b4e4 100%); }
    .register-card {
        max-width: 100%;
        border-radius: 16px;
        box-shadow: 0 15px 40px rgba(0, 30, 60, 0.2);
    }
    .card-header { padding: 28px 22px 22px; }
    .card-body { padding: 22px 22px 24px; }
    .card-footer { padding: 0 22px 26px; }
    .logo { font-size: 32px; }
    .card-title { font-size: 19px; }
    .btn-code { padding: 0 12px; font-size: 12px; }
    .code-group { gap: 8px; }
}
</style>
</head>
<body>
<div class="bg-pattern"></div>
<div class="register-card">
    <div class="card-header">
        <div class="logo">🎬 <?php echo e(SITE_NAME); ?></div>
        <div class="logo-sub">您的专属高清影视平台</div>
        <div class="card-title">创建新账号</div>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <span class="alert-icon">⚠️</span>
            <span><?php echo e($error); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <span><?php echo e($success); ?></span>
        </div>
        <?php endif; ?>

        <form id="registerForm" method="post" action="" novalidate>
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label class="input-label" for="email">邮箱地址</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <input type="email" id="email" name="email" class="form-input" placeholder="请输入您的邮箱地址" value="<?php echo e($oldEmail); ?>" autocomplete="email" required>
                </div>
                <div class="hint">用于登录和接收验证码，不会公开</div>
            </div>

            <div class="form-group">
                <label class="input-label" for="username">用户名</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text" id="username" name="username" class="form-input" placeholder="4-20字符，字母/数字/中文" value="<?php echo e($oldUsername); ?>" autocomplete="username" required>
                </div>
                <div class="hint">4-20个字符，支持字母、数字或中文</div>
            </div>

            <div class="form-group">
                <label class="input-label" for="password">密码</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" id="password" name="password" class="form-input" placeholder="6-20字符，建议字母数字组合" autocomplete="new-password" required>
                </div>
                <div class="hint">长度6-20个字符，请牢记您的密码</div>
            </div>

            <div class="form-group">
                <label class="input-label" for="confirm_password">确认密码</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4"/>
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="请再次输入密码" autocomplete="new-password" required>
                </div>
            </div>

            <div class="form-group">
                <label class="input-label" for="verification_code">邮箱验证码</label>
                <div class="code-group">
                    <div class="input-wrap" style="flex:1;">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/>
                        </svg>
                        <input type="text" id="verification_code" name="verification_code" class="form-input" placeholder="请输入6位验证码" maxlength="6" autocomplete="off" required>
                    </div>
                    <button type="button" id="btnSendCode" class="btn-code">获取验证码</button>
                </div>
                <div class="hint">点击"获取验证码"，查看邮件并输入6位数字</div>
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">立即注册</button>
        </form>
    </div>
    <div class="card-footer">
        已有账号？<a href="<?php echo e(SITE_URL . '/login.php'); ?>">立即登录</a>
    </div>
</div>

<script>
(function() {
    var csrfToken = <?php echo json_encode($csrfToken); ?>;
    var btnSendCode = document.getElementById('btnSendCode');
    var emailInput = document.getElementById('email');
    var form = document.getElementById('registerForm');
    var btnSubmit = document.getElementById('btnSubmit');
    var timer = null;

    function showAlert(msg, type) {
        type = type || 'error';
        var oldAlerts = document.querySelectorAll('.alert');
        oldAlerts.forEach(function(el) { el.remove(); });
        var div = document.createElement('div');
        div.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
        div.innerHTML = '<span class="alert-icon">' + (type === 'success' ? '✅' : '⚠️') + '</span><span>' + msg + '</span>';
        var target = document.querySelector('.card-body');
        target.insertBefore(div, target.firstChild);
    }

    function startCountdown() {
        var seconds = 60;
        btnSendCode.disabled = true;
        var originalText = '获取验证码';
        btnSendCode.textContent = seconds + 's 后重试';
        if (timer) clearInterval(timer);
        timer = setInterval(function() {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                timer = null;
                btnSendCode.disabled = false;
                btnSendCode.textContent = originalText;
            } else {
                btnSendCode.textContent = seconds + 's 后重试';
            }
        }, 1000);
    }

    btnSendCode.addEventListener('click', function() {
        if (btnSendCode.disabled) return;
        var email = emailInput.value.trim();
        if (!email) {
            showAlert('请先输入邮箱地址');
            emailInput.focus();
            return;
        }
        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(email)) {
            showAlert('邮箱格式不正确');
            emailInput.focus();
            return;
        }

        btnSendCode.disabled = true;
        var origText = btnSendCode.textContent;
        btnSendCode.textContent = '发送中...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', <?php echo json_encode(SITE_URL . '/ajax/send_code.php'); ?>, true);
        xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            var data;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (e) {
                data = { success: false, message: '服务器响应错误' };
            }
            if (data && data.success) {
                showAlert(data.message || '验证码发送成功', 'success');
                startCountdown();
            } else {
                btnSendCode.disabled = false;
                btnSendCode.textContent = origText;
                showAlert(data.message || '发送失败');
            }
        };
        xhr.onerror = function() {
            btnSendCode.disabled = false;
            btnSendCode.textContent = origText;
            showAlert('网络错误，请稍后重试');
        };
        var payload = JSON.stringify({ email: email, type: 'register', csrf_token: csrfToken });
        xhr.send(payload);
    });

    form.addEventListener('submit', function(e) {
        var email = document.getElementById('email').value.trim();
        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        var code = document.getElementById('verification_code').value.trim();

        if (!email) { showAlert('请输入邮箱地址'); e.preventDefault(); return false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showAlert('邮箱格式不正确'); e.preventDefault(); return false; }
        if (!username) { showAlert('请输入用户名'); e.preventDefault(); return false; }
        var userRe = /^[\u4e00-\u9fa5A-Za-z0-9]{4,20}$/;
        if (!userRe.test(username)) { showAlert('用户名需4-20字符，仅支持字母、数字或中文'); e.preventDefault(); return false; }
        if (!password) { showAlert('请输入密码'); e.preventDefault(); return false; }
        if (password.length < 6 || password.length > 20) { showAlert('密码需6-20字符'); e.preventDefault(); return false; }
        if (password !== confirmPassword) { showAlert('两次输入的密码不一致'); e.preventDefault(); return false; }
        if (!code) { showAlert('请输入邮箱验证码'); e.preventDefault(); return false; }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="loading"></span>注册中...';
        return true;
    });
})();
</script>
</body>
</html>
