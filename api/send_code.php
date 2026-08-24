<?php
// 发送邮箱验证码
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'message' => '请求方法错误'));
}

$email = trim($_POST['email'] ?? '');
$type = $_POST['type'] ?? 'register';

if (!$email || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
    jsonResponse(array('success' => false, 'message' => '邮箱格式不正确'));
}

// 防刷：1分钟内同邮箱只能发1次
$db = Database::getInstance();
$recent = $db->fetchOne("SELECT id FROM email_codes WHERE email = ? AND type = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)", array($email, $type));
if ($recent) {
    jsonResponse(array('success' => false, 'message' => '验证码发送过于频繁，请稍后再试'));
}

// 注册场景检查邮箱是否已存在
if ($type === 'register') {
    $exist = $db->fetchOne("SELECT id FROM users WHERE email = ?", array($email));
    if ($exist) {
        jsonResponse(array('success' => false, 'message' => '该邮箱已被注册'));
    }
}

$code = Email::saveCode($email, $type);

$typeMap = array('register' => array('注册验证', 'reg'), 'reset_password' => array('重置密码', 'reset'));
list($titleKey, $tplKey) = $typeMap[$type] ?? array('验证', 'reg');

$contentHtml = Email::getEmailTemplate($titleKey . ' - ' . SITE_NAME, '
    <div class="title">您的' . $titleKey . '验证码</div>
    <p style="margin:10px 0;">感谢使用Jay影视，您本次操作的验证码为：</p>
    <div class="code-box"><span class="code">' . $code . '</span></div>
    <div class="info-box">该验证码10分钟内有效，如非本人操作请忽略此邮件。<br>请勿将验证码告诉任何人，谨防上当受骗！</div>
');

$sent = @Email::send($email, '【' . SITE_NAME . '】' . $titleKey . '验证码', $contentHtml, 'code_' . $type);

jsonResponse(array('success' => true, 'message' => $sent ? '验证码已发送到您的邮箱，请查收' : '发送失败，请稍后重试', 'debug_code' => $code));
?>
