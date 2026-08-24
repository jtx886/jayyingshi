<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$id = intval($_POST['id'] ?? 0);
$db = Database::getInstance();
$uid = $_SESSION['user_id'];

$db->delete('favorites', 'id = ? AND user_id = ?', array($id, $uid));
jsonResponse(array('success' => true, 'message' => '已删除'));
?>
