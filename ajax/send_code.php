<?php
/**
 * Jay影视 - 发送邮箱验证码AJAX接口
 */

require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => '请求方式错误'), JSON_UNESCAPED_UNICODE);
    exit;
}

$input = file_get_contents('php://input');
if ($input !== false && !empty($input)) {
    $jsonParams = json_decode($input, true);
    if (is_array($jsonParams)) {
        $_POST = array_merge($_POST, $jsonParams);
    }
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$type = isset($_POST['type']) ? trim($_POST['type']) : 'register';
$csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

if (empty($csrfToken) || empty($_SESSION[SESSION_PREFIX . 'csrf_token']) || $csrfToken !== $_SESSION[SESSION_PREFIX . 'csrf_token']) {
    echo json_encode(array('success' => false, 'message' => 'CSRF验证失败，请刷新页面重试'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($email)) {
    echo json_encode(array('success' => false, 'message' => '请输入邮箱地址'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array('success' => false, 'message' => '邮箱格式不正确'), JSON_UNESCAPED_UNICODE);
    exit;
}

$lastSendKey = SESSION_PREFIX . 'code_send_time_' . md5($email . '_' . $type);
if (!empty($_SESSION[$lastSendKey])) {
    $lastSend = intval($_SESSION[$lastSendKey]);
    if ((time() - $lastSend) < 60) {
        $remain = 60 - (time() - $lastSend);
        echo json_encode(array('success' => false, 'message' => '发送过于频繁，请' . $remain . '秒后再试', 'remain' => $remain), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$allowedTypes = array('register', 'reset', 'login', 'email');
if (!in_array($type, $allowedTypes, true)) {
    echo json_encode(array('success' => false, 'message' => '无效的验证码类型'), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type === 'register') {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute(array($email));
        if ($stmt->fetch()) {
            echo json_encode(array('success' => false, 'message' => '该邮箱已被注册'), JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
    }
}

$result = sendVerificationCode($email, $type);
if ($result) {
    $_SESSION[$lastSendKey] = time();
    echo json_encode(array('success' => true, 'message' => '验证码发送成功，请注意查收邮件'), JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(array('success' => false, 'message' => '验证码发送失败，请稍后重试'), JSON_UNESCAPED_UNICODE);
}
exit;
