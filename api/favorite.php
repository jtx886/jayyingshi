<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) {
    jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$mediaId = intval($_POST['media_id'] ?? 0);
$mediaType = $_POST['media_type'] ?? '';
$mediaTitle = trim($_POST['media_title'] ?? '');
$mediaPoster = trim($_POST['media_poster'] ?? '');

if (!$mediaId || !$mediaType) jsonResponse(array('success' => false, 'message' => '参数错误'));

$db = Database::getInstance();
$uid = $_SESSION['user_id'];

$exist = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", array($uid, $mediaId, $mediaType));
if ($exist) {
    $db->delete('favorites', 'id = ?', array($exist['id']));
    jsonResponse(array('success' => true, 'added' => false, 'message' => '已取消收藏'));
} else {
    $db->insert('favorites', array(
        'user_id' => $uid,
        'media_id' => $mediaId,
        'media_type' => $mediaType,
        'media_title' => $mediaTitle,
        'media_poster' => $mediaPoster
    ));
    jsonResponse(array('success' => true, 'added' => true, 'message' => '收藏成功'));
}
?>
