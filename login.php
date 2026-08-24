<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email.php';

$pageTitle = '登录';
include __DIR__ . '/header.php';

$error = '';
$message = '';
$redirect = $_SESSION['redirect_after_login'] ?? '';
$loginMsg = $_SESSION['login_message'] ?? '';
unset($_SESSION['login_message']);

if (isset($_SESSION['registered'])) {
    $message = '注册成功！请登录';
    unset($_SESSION['registered']);
}

if (isset($_GET['banned']) && $_GET['banned'] == 1) {
    $error = '您的账号已被封禁，请联系管理员';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account = trim($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$account || !$password) {
        $error = '请填写完整信息';
    } else {
        $db = Database::getInstance();
        // 支持用户名或邮箱登录
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1", array($account, $account));
        if (!$user) {
            $error = '账号不存在';
        } else {
            $passOk = false;
            // 兼容MD5旧密码
            if (strlen($user['password']) == 32 && $user['password'] == md5($password)) {
                $passOk = true;
                // 升级为password_hash
                $newHash = Auth::generatePassword($password);
                $db->update('users', array('password' => $newHash), 'id = ?', array($user['id']));
                $user['password'] = $newHash;
            } else if (Auth::verifyPassword($password, $user['password'])) {
                $passOk = true;
            }
            if (!$passOk) {
                $error = '密码错误';
            } else {
                // 检查封禁并自动解封
                if ($user['status'] == 0) {
                    $endTime = $user['ban_end_time'];
                    if ($endTime && strtotime($endTime) < time()) {
                        $db->update('users', array('status' => 1, 'ban_time' => null, 'ban_end_time' => null, 'ban_reason' => null), 'id = ?', array($user['id']));
                        $user['status'] = 1;
                    } else {
                        $banMsg = '账号已被封禁';
                        if (!empty($user['ban_reason'])) $banMsg .= '，原因：' . $user['ban_reason'];
                        if (!empty($endTime)) $banMsg .= '，解封时间：' . $endTime;
                        $error = $banMsg;
                        $user = null;
                    }
                }
                if ($user) {
                    Auth::login($user);
                    $url = $redirect ?: 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $url);
                    exit();
                }
            }
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo" style="display:inline-flex;">
                <span class="logo-icon"></span>
                <span class="logo-text">Jay影视</span>
            </div>
        </div>
        <h1 class="auth-title">欢迎回来</h1>
        <p class="auth-subtitle">登录账号继续享受精彩视频</p>

        <?php if ($loginMsg): ?>
            <div class="auth-alert auth-alert-info">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span><?php echo e($loginMsg); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="auth-alert auth-alert-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?php echo e($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">用户名 / 邮箱</label>
                <input type="text" name="account" class="form-input" placeholder="请输入用户名或邮箱" value="<?php echo e($_POST['account'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block form-submit">登录</button>
        </form>

        <div class="auth-footer">
            还没有账号？<a href="register.php">立即注册</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
