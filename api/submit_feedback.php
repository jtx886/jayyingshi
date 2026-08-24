<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
if (!$title || !$content) jsonResponse(array('success' => false, 'message' => '请填写完整内容'));
if (mb_strlen($title) > 100) jsonResponse(array('success' => false, 'message' => '标题过长'));
if (mb_strlen($content) > 5000) jsonResponse(array('success' => false, 'message' => '内容过长'));

$db = Database::getInstance();
$db->insert('feedback', array(
    'user_id' => $_SESSION['user_id'],
    'title' => $title,
    'content' => $content
));
jsonResponse(array('success' => true, 'message' => '反馈提交成功，感谢您的建议！'));
?>
