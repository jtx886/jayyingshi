<?php
// 用户认证API

require_once __DIR__ . '/common.php';

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'send_code':
        handleSendCode();
        break;
    case 'verify_code':
        handleVerifyCode();
        break;
    case 'check_email':
        handleCheckEmail();
        break;
    case 'check_username':
        handleCheckUsername();
        break;
    default:
        jsonResponse(['code' => 400, 'message' => '无效的请求']);
}

// 处理注册
function handleRegister() {
    global $db;

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';

    // 验证CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        jsonResponse(['code' => 400, 'message' => '安全令牌验证失败，请刷新页面重试']);
    }

    // 验证输入
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword) || empty($code)) {
        jsonResponse(['code' => 400, 'message' => '请填写所有必填字段']);
    }

    if (strlen($username) < 3 || strlen($username) > 20) {
        jsonResponse(['code' => 400, 'message' => '用户名长度需在3-20位之间']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['code' => 400, 'message' => '请输入有效的邮箱地址']);
    }

    if (strlen($password) < 6) {
        jsonResponse(['code' => 400, 'message' => '密码长度不能少于6位']);
    }

    if ($password !== $confirmPassword) {
        jsonResponse(['code' => 400, 'message' => '两次输入的密码不一致']);
    }

    // 检查用户名是否已存在
    $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        jsonResponse(['code' => 400, 'message' => '用户名已被注册']);
    }

    // 检查邮箱是否已存在
    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        jsonResponse(['code' => 400, 'message' => '邮箱已被注册']);
    }

    // 验证验证码
    $codeRecord = $db->fetch(
        "SELECT * FROM verification_codes WHERE email = ? AND code = ? AND type = 'register' AND expires_at > datetime('now', 'localtime')",
        [$email, $code]
    );

    if (!$codeRecord) {
        jsonResponse(['code' => 400, 'message' => '验证码无效或已过期']);
    }

    // 创建用户
    $db->insert('users', [
        'username' => $username,
        'email' => $email,
        'password' => hashPassword($password),
        'avatar' => '',
        'status' => 1,
        'ban_until' => '',
        'ban_reason' => ''
    ]);

    // 删除验证码记录
    $db->delete('verification_codes', 'id = ?', [$codeRecord['id']]);

    // 自动登录
    $user = $db->fetch("SELECT id, username, email, avatar FROM users WHERE email = ?", [$email]);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    jsonResponse(['code' => 200, 'message' => '注册成功', 'data' => $user]);
}

// 处理登录
function handleLogin() {
    global $db;

    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($login) || empty($password)) {
        jsonResponse(['code' => 400, 'message' => '请填写登录信息']);
    }

    // 通过用户名或邮箱登录
    $user = $db->fetch(
        "SELECT * FROM users WHERE username = ? OR email = ?",
        [$login, $login]
    );

    if (!$user) {
        jsonResponse(['code' => 400, 'message' => '用户名或密码错误']);
    }

    if (!verifyPassword($password, $user['password'])) {
        jsonResponse(['code' => 400, 'message' => '用户名或密码错误']);
    }

    // 检查封禁状态
    if ($user['status'] == 0) {
        // 检查封禁是否过期
        if (!empty($user['ban_until']) && strtotime($user['ban_until']) < time()) {
            $db->update('users', ['status' => 1, 'ban_until' => '', 'ban_reason' => ''], 'id = ?', [$user['id']]);
            $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);
        } else {
            $banTime = !empty($user['ban_until']) ? $user['ban_until'] : '永久';
            jsonResponse([
                'code' => 403,
                'message' => '您的账号已被封禁',
                'ban_until' => $banTime,
                'ban_reason' => $user['ban_reason']
            ]);
        }
    }

    // 登录成功
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    jsonResponse([
        'code' => 200,
        'message' => '登录成功',
        'data' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'avatar' => $user['avatar']
        ]
    ]);
}

// 处理登出
function handleLogout() {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['code' => 200, 'message' => '已退出登录']);
}

// 发送验证码
function handleSendCode() {
    global $db;

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'register';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['code' => 400, 'message' => '请输入有效的邮箱地址']);
    }

    // 检查发送频率限制（60秒内只能发送一次）
    $lastSent = $db->fetch(
        "SELECT created_at FROM verification_codes WHERE email = ? AND type = ? ORDER BY created_at DESC LIMIT 1",
        [$email, $type]
    );

    if ($lastSent) {
        $diff = time() - strtotime($lastSent['created_at']);
        if ($diff < 60) {
            jsonResponse(['code' => 400, 'message' => '请60秒后再发送验证码']);
        }
    }

    // 生成验证码
    $code = generateCode();
    $expiresAt = date('Y-m-d H:i:s', time() + CODE_EXPIRATION);

    // 保存验证码
    $db->insert('verification_codes', [
        'email' => $email,
        'code' => $code,
        'type' => $type,
        'expires_at' => $expiresAt
    ]);

    // 发送邮件
    require_once __DIR__ . '/Mailer.php';
    $mailer = new Mailer();

    $username = $type === 'reset' ? $email : '用户';
    $sent = $mailer->sendVerificationCode($email, $username, $code);

    if (!$sent) {
        // 记录但不暴露错误
        jsonResponse(['code' => 500, 'message' => '验证码发送失败，请稍后重试']);
    }

    jsonResponse(['code' => 200, 'message' => '验证码已发送，请查收邮件']);
}

// 验证验证码
function handleVerifyCode() {
    global $db;

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'register';

    $record = $db->fetch(
        "SELECT * FROM verification_codes WHERE email = ? AND code = ? AND type = ? AND expires_at > datetime('now', 'localtime')",
        [$email, $code, $type]
    );

    if ($record) {
        jsonResponse(['code' => 200, 'message' => '验证码正确', 'valid' => true]);
    } else {
        jsonResponse(['code' => 400, 'message' => '验证码无效或已过期', 'valid' => false]);
    }
}

// 检查邮箱
function handleCheckEmail() {
    global $db;

    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['code' => 400, 'valid' => false]);
    }

    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    jsonResponse(['code' => 200, 'valid' => $existing ? false : true]);
}

// 检查用户名
function handleCheckUsername() {
    global $db;

    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    if (empty($username)) {
        jsonResponse(['code' => 400, 'valid' => false]);
    }

    $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
    jsonResponse(['code' => 200, 'valid' => $existing ? false : true]);
}
