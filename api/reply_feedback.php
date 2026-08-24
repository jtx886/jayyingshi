<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$fid = intval($_POST['feedback_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
if (!$fid || !$content) jsonResponse(array('success' => false, 'message' => '内容不能为空'));
if (mb_strlen($content) > 500) jsonResponse(array('success' => false, 'message' => '回复内容过长'));

$db = Database::getInstance();
$db->insert('feedback_replies', array(
    'feedback_id' => $fid,
    'user_id' => $_SESSION['user_id'],
    'content' => $content,
    'is_admin' => Auth::isAdmin() ? 1 : 0
));
jsonResponse(array('success' => true, 'message' => '回复成功'));
?>
