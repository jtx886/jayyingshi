<?php
require_once dirname(__FILE__) . '/includes/functions.php';

if (!function_exists('jay_random_bytes')) {
    function jay_random_bytes($length) {
        $length = intval($length);
        if ($length <= 0) {
            return '';
        }
        if (function_exists('random_bytes')) {
            try {
                return random_bytes($length);
            } catch (Exception $e) {
            }
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $cryptoStrong = false;
            $result = openssl_random_pseudo_bytes($length, $cryptoStrong);
            if ($result !== false && $cryptoStrong) {
                return $result;
            }
        }
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= chr(mt_rand(0, 255));
        }
        return $result;
    }
}

$page_title = '用户登录';
$simple_header = true;
$simple_footer = true;
$siteUrl = SITE_URL;
$siteName = SITE_NAME;
$themeColor = getThemeColor();

if (isLoggedIn()) {
    redirect($siteUrl . '/index.php');
    exit;
}

$errors = array();
$success = '';
$usernameOrEmail = '';

$csrfKey = SESSION_PREFIX . 'csrf_token';
if (empty($_SESSION[$csrfKey])) {
    $_SESSION[$csrfKey] = bin2hex(jay_random_bytes(32));
}
$csrfToken = $_SESSION[$csrfKey];

$attemptsKey = SESSION_PREFIX . 'login_attempts';
$lockKey = SESSION_PREFIX . 'login_lock';
$maxAttempts = 5;
$lockDuration = 900;

$isLocked = false;
$remainingTime = 0;
if (!empty($_SESSION[$lockKey]) && intval($_SESSION[$lockKey]) > time()) {
    $isLocked = true;
    $remainingTime = intval($_SESSION[$lockKey]) - time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isLocked) {
        $errors[] = '登录尝试次数过多，请 ' . ceil($remainingTime / 60) . ' 分钟后再试';
    } else {
        $submittedToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if ($submittedToken !== $csrfToken) {
            $errors[] = '请求无效，请刷新页面重试';
        } else {
            $usernameOrEmail = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($usernameOrEmail)) {
                $errors[] = '请输入用户名或邮箱';
            }
            if (empty($password)) {
                $errors[] = '请输入密码';
            }

            if (empty($errors)) {
                try {
                    $pdo = getDB();
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
                    $stmt->execute(array($usernameOrEmail, $usernameOrEmail));
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        $userId = intval($user['id']);
                        $banned = isset($user['banned']) ? intval($user['banned']) : (isset($user['is_banned']) ? intval($user['is_banned']) : 0);
                        $banExpire = isset($user['ban_expire_at']) ? intval($user['ban_expire_at']) : (isset($user['ban_end_time']) ? intval($user['ban_end_time']) : 0);

                        $isBannedFlag = false;
                        $banReason = '';
                        if ($banned === 1) {
                            if ($banExpire > 0 && time() > $banExpire) {
                                $updateStmt = $pdo->prepare("UPDATE users SET banned = 0, is_banned = 0, ban_expire_at = 0, ban_end_time = 0, ban_reason = '' WHERE id = ?");
                                $updateStmt->execute(array($userId));
                                $isBannedFlag = false;
                            } else {
                                $isBannedFlag = true;
                                $banReason = isset($user['ban_reason']) ? $user['ban_reason'] : '';
                            }
                        }

                        if ($isBannedFlag) {
                            $banMsg = '您的账号已被封禁';
                            if ($banExpire > 0) {
                                $banMsg .= '，解禁时间：' . date('Y-m-d H:i:s', $banExpire);
                            } else {
                                $banMsg .= '（永久封禁）';
                            }
                            if (!empty($banReason)) {
                                $banMsg .= '，原因：' . $banReason;
                            }
                            $errors[] = $banMsg;
                        } else {
                            $_SESSION[SESSION_PREFIX . 'user_id'] = $userId;
                            unset($_SESSION[$attemptsKey]);
                            unset($_SESSION[$lockKey]);
                            $_SESSION[$csrfKey] = bin2hex(jay_random_bytes(32));
                            $csrfToken = $_SESSION[$csrfKey];

                            $redirect = '';
                            if (!empty($_GET['redirect'])) {
                                $redirect = $_GET['redirect'];
                            } elseif (!empty($_SESSION[SESSION_PREFIX . 'login_redirect'])) {
                                $redirect = $_SESSION[SESSION_PREFIX . 'login_redirect'];
                                unset($_SESSION[SESSION_PREFIX . 'login_redirect']);
                            }
                            if (empty($redirect) || !preg_match('/^[\/a-zA-Z0-9_\-\?\=\&\.\%]+$/', $redirect)) {
                                $redirect = 'index.php';
                            }
                            if (strpos($redirect, '://') === false) {
                                if (strpos($redirect, '/') === 0) {
                                    redirect($siteUrl . $redirect);
                                } else {
                                    redirect($siteUrl . '/' . $redirect);
                                }
                            } else {
                                redirect($siteUrl . '/index.php');
                            }
                            exit;
                        }
                    } else {
                        $errors[] = '用户名或密码错误';
                        if (!isset($_SESSION[$attemptsKey])) {
                            $_SESSION[$attemptsKey] = 0;
                        }
                        $_SESSION[$attemptsKey] = intval($_SESSION[$attemptsKey]) + 1;
                        if (intval($_SESSION[$attemptsKey]) >= $maxAttempts) {
                            $_SESSION[$lockKey] = time() + $lockDuration;
                            $isLocked = true;
                            $remainingTime = $lockDuration;
                            $errors[] = '登录失败次数过多，账号已被锁定 ' . ceil($lockDuration / 60) . ' 分钟';
                        } else {
                            $remain = $maxAttempts - intval($_SESSION[$attemptsKey]);
                            if ($remain > 0) {
                                $errors[] = '您还有 ' . $remain . ' 次尝试机会';
                            }
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = '系统繁忙，请稍后再试';
                }
            }
        }
    }
}

$remainingMinutes = 0;
if ($isLocked) {
    $remainingMinutes = ceil($remainingTime / 60);
}

include dirname(__FILE__) . '/includes/header.php';
?>
<style>
    .auth-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
        padding: 40px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .auth-page::before,
    .auth-page::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        opacity: 0.1;
        background: #fff;
    }
    .auth-page::before {
        width: 500px;
        height: 500px;
        top: -200px;
        right: -150px;
    }
    .auth-page::after {
        width: 400px;
        height: 400px;
        bottom: -150px;
        left: -100px;
    }
    .bg-decor {
        position: absolute;
        opacity: 0.08;
        pointer-events: none;
    }
    .bg-decor-1 {
        width: 200px;
        height: 200px;
        border: 3px solid #fff;
        top: 15%;
        left: 10%;
        transform: rotate(45deg);
        border-radius: 12px;
    }
    .bg-decor-2 {
        width: 120px;
        height: 120px;
        border: 3px solid #fff;
        top: 60%;
        right: 15%;
        border-radius: 50%;
    }
    .bg-decor-3 {
        width: 0;
        height: 0;
        border-left: 60px solid transparent;
        border-right: 60px solid transparent;
        border-bottom: 100px solid #fff;
        bottom: 20%;
        left: 20%;
    }
    .auth-card {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 400px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25), 0 10px 30px rgba(0, 0, 0, 0.15);
        padding: 40px 32px 32px;
    }
    .auth-logo {
        text-align: center;
        margin-bottom: 8px;
    }
    .auth-logo-icon {
        display: inline-block;
        font-size: 44px;
        line-height: 1;
        margin-bottom: 4px;
    }
    .auth-logo-text {
        font-size: 28px;
        font-weight: 700;
        background: linear-gradient(135deg, #5b5cff 0%, #7c4dff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: 1px;
    }
    .auth-welcome {
        text-align: center;
        color: #64748b;
        font-size: 14px;
        margin-bottom: 28px;
    }
    .alert-box {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        line-height: 1.6;
        border: 1px solid;
    }
    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
        border-left: 4px solid #ef4444;
    }
    .alert-success {
        background: #f0fdf4;
        color: #166534;
        border-color: #bbf7d0;
        border-left: 4px solid #22c55e;
    }
    .alert-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .alert-box li {
        padding: 2px 0;
    }
    .form-group {
        margin-bottom: 18px;
    }
    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        margin-bottom: 8px;
    }
    .form-input {
        width: 100%;
        padding: 12px 14px;
        font-size: 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        color: #1e293b;
        outline: none;
        transition: all 0.25s ease;
        box-sizing: border-box;
    }
    .form-input:hover {
        border-color: #cbd5e1;
    }
    .form-input:focus {
        border-color: <?php echo $themeColor; ?>;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(91, 92, 255, 0.12);
    }
    .form-input::placeholder {
        color: #94a3b8;
    }
    .form-options {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .forgot-link {
        color: <?php echo $themeColor; ?>;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .forgot-link:hover {
        opacity: 0.8;
        text-decoration: underline;
    }
    .btn-submit {
        width: 100%;
        padding: 13px;
        font-size: 15px;
        font-weight: 600;
        color: #fff;
        background: linear-gradient(135deg, #5b5cff 0%, #7c4dff 100%);
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(91, 92, 255, 0.3);
        letter-spacing: 0.5px;
    }
    .btn-submit:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(91, 92, 255, 0.4);
        background: linear-gradient(135deg, #4a4bff 0%, #6b3ce8 100%);
    }
    .btn-submit:active:not(:disabled) {
        transform: translateY(0);
    }
    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .auth-footer {
        text-align: center;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
        color: #64748b;
        font-size: 14px;
    }
    .auth-footer a {
        color: <?php echo $themeColor; ?>;
        font-weight: 500;
        text-decoration: none;
        margin-left: 4px;
    }
    .auth-footer a:hover {
        text-decoration: underline;
    }
    @media (max-width: 480px) {
        .auth-page {
            padding: 20px 12px;
        }
        .auth-card {
            padding: 32px 20px 24px;
            border-radius: 14px;
        }
        .auth-logo-text {
            font-size: 24px;
        }
        .auth-logo-icon {
            font-size: 38px;
        }
    }
</style>

<div class="auth-page">
    <div class="bg-decor bg-decor-1"></div>
    <div class="bg-decor bg-decor-2"></div>
    <div class="bg-decor bg-decor-3"></div>

    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">🎬</div>
            <div class="auth-logo-text"><?php echo e($siteName); ?></div>
        </div>
        <div class="auth-welcome">欢迎回来，请登录您的账号</div>

        <?php if (!empty($errors)): ?>
            <div class="alert-box alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-box alert-success">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php
            $self = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $pos = strpos($self, '?');
            if ($pos !== false) {
                $self = substr($self, 0, $pos);
            }
            if (!empty($_GET['redirect'])) {
                $self .= '?redirect=' . urlencode($_GET['redirect']);
            }
            echo e($self);
        ?>" autocomplete="on" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label class="form-label" for="username_or_email">用户名 / 邮箱</label>
                <input
                    type="text"
                    id="username_or_email"
                    name="username_or_email"
                    class="form-input"
                    placeholder="请输入用户名或邮箱"
                    value="<?php echo e($usernameOrEmail); ?>"
                    autocomplete="username"
                    <?php echo $isLocked ? 'disabled' : ''; ?>
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">密码</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="请输入密码"
                    autocomplete="current-password"
                    <?php echo $isLocked ? 'disabled' : ''; ?>
                    required
                >
            </div>

            <div class="form-options">
                <a href="forgot.php" class="forgot-link">忘记密码？</a>
            </div>

            <button type="submit" class="btn-submit" <?php echo $isLocked ? 'disabled' : ''; ?>>
                <?php
                    if ($isLocked) {
                        echo '已锁定（' . $remainingMinutes . ' 分钟后解锁）';
                    } else {
                        echo '登 录';
                    }
                ?>
            </button>
        </form>

        <div class="auth-footer">
            还没有账号？<a href="register.php">立即注册</a>
        </div>
    </div>
</div>

<?php include dirname(__FILE__) . '/includes/footer.php'; ?>
