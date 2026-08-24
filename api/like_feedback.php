<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$fid = intval($_POST['feedback_id'] ?? 0);
$uid = $_SESSION['user_id'];
$db = Database::getInstance();

$exist = $db->fetchOne("SELECT id FROM feedback_likes WHERE user_id = ? AND feedback_id = ?", array($uid, $fid));
if ($exist) {
    $db->delete('feedback_likes', 'id = ?', array($exist['id']));
    $liked = false;
} else {
    $db->insert('feedback_likes', array('user_id' => $uid, 'feedback_id' => $fid));
    $liked = true;
}
$count = $db->fetchOne("SELECT COUNT(*) c FROM feedback_likes WHERE feedback_id = ?", array($fid))['c'];
jsonResponse(array('success' => true, 'liked' => $liked, 'count' => intval($count), 'message' => ''));
?>
