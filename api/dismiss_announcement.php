<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$annId = intval($_POST['announcement_id'] ?? 0);
if (!$annId) jsonResponse(array('success' => false));

$db = Database::getInstance();
$uid = $_SESSION['user_id'];
$exists = $db->fetchOne("SELECT id FROM announcement_dismissed WHERE announcement_id = ? AND user_id = ?", array($annId, $uid));
if (!$exists) {
    $db->insert('announcement_dismissed', array('announcement_id' => $annId, 'user_id' => $uid));
}
jsonResponse(array('success' => true));
?>
