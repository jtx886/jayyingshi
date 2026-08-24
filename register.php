<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email.php';

if (Auth::isLoggedIn()) {
    redirect('index.php');
}

$pageTitle = '注册';
include __DIR__ . '/header.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $code = trim($_POST['code'] ?? '');

    $db = Database::getInstance();
    if (!$username || !$email || !$password || !$confirmPassword || !$code) {
        $error = '请填写完整信息';
    } elseif (mb_strlen($username) < 2 || mb_strlen($username) > 20) {
        $error = '用户名长度需在2-20个字符之间';
    } elseif (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
        $error = '邮箱格式不正确';
    } elseif (strlen($password) < 6) {
        $error = '密码至少需要6位';
    } elseif ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } else {
        // 检查用户名
        $exist = $db->fetchOne("SELECT id FROM users WHERE username = ?", array($username));
        if ($exist) { $error = '该用户名已被注册'; }
        else {
            // 检查邮箱
            $exist = $db->fetchOne("SELECT id FROM users WHERE email = ?", array($email));
            if ($exist) { $error = '该邮箱已被注册'; }
            else {
                // 验证验证码
                if (!Email::verifyCode($email, $code, 'register')) {
                    $error = '邮箱验证码错误或已过期';
                } else {
                    // 创建用户
                    $hash = Auth::generatePassword($password);
                    $uid = $db->insert('users', array(
                        'username' => $username,
                        'email' => $email,
                        'password' => $hash
                    ));
                    // 发送欢迎邮件
                    $title = '欢迎加入Jay影视';
                    $contentHtml = Email::getEmailTemplate($title, '
                        <div class="title">🎉 注册成功，欢迎加入Jay影视！</div>
                        <p style="margin: 10px 0;">亲爱的 <strong>' . htmlspecialchars($username) . '</strong>，您的账号已注册成功！</p>
                        <div class="info-box">现在您可以登录账号，观看海量高清影视内容，并享受收藏、观看历史等专属功能。</div>
                        <p style="margin-top:24px;text-align:center;"><a href="' . SITE_URL . '/login.php" class="btn">立即登录</a></p>
                    ');
                    @Email::send($email, $title, $contentHtml, 'welcome', $uid);
                    $_SESSION['registered'] = true;
                    redirect('login.php');
                }
            }
        }
    }
}
?>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 520px;">
        <div class="auth-logo">
            <div class="logo" style="display:inline-flex;">
                <span class="logo-icon"></span>
                <span class="logo-text">Jay影视</span>
            </div>
        </div>
        <h1 class="auth-title">创建新账号</h1>
        <p class="auth-subtitle">加入Jay影视，畅享海量高清影视内容</p>

        <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" name="username" class="form-input" placeholder="2-20个字符" value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">邮箱</label>
                    <input type="email" id="regEmail" name="email" class="form-input" placeholder="your@email.com" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <input type="password" name="password" class="form-input" placeholder="至少6位" required>
                </div>
                <div class="form-group">
                    <label class="form-label">确认密码</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="再次输入密码" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">邮箱验证码</label>
                <div class="input-suffix">
                    <input type="text" name="code" class="form-input" maxlength="6" placeholder="请输入6位验证码" required>
                    <button type="button" class="suffix-btn send-code-btn" data-email="#regEmail" data-type="register">获取验证码</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block form-submit">注册账号</button>
        </form>

        <div class="auth-footer">
            已有账号？<a href="login.php">立即登录</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
