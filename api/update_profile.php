<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $db = Database::getInstance();
    $uid = $_SESSION['user_id'];
    if ($password !== '' && strlen($password) < 6) {
        $msg = '密码至少6位';
    } elseif ($password !== '' && $password !== $confirm) {
        $msg = '两次密码不一致';
    } else {
        if ($password !== '') {
            $hash = Auth::generatePassword($password);
            $db->update('users', array('password' => $hash), 'id = ?', array($uid));
        }
        $msg = '修改成功';
        $ok = true;
    }
    // 刷新session
    $_SESSION = array();
    header('Location: ../profile.php?msg=' . urlencode($msg ?? ''));
    exit();
}

header('Location: ../profile.php');
?>
