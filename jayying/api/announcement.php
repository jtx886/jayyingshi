<?php
// 公告API

require_once __DIR__ . '/common.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'get_active':
        getActiveAnnouncement();
        break;
    case 'dismiss':
        dismissAnnouncement();
        break;
    default:
        jsonResponse(['code' => 400, 'message' => '无效的请求']);
}

// 获取当前活动公告
function getActiveAnnouncement() {
    global $db;
    
    $announcement = $db->fetch("SELECT * FROM announcements WHERE status = 1 ORDER BY id DESC LIMIT 1");
    
    if (!$announcement) {
        jsonResponse(['code' => 200, 'data' => null]);
    }
    
    // 检查用户是否已关闭此公告
    $dismissed = false;
    if (isset($_SESSION['user_id'])) {
        $view = $db->fetch(
            "SELECT id FROM announcement_views WHERE announcement_id = ? AND user_id = ? AND dismissed = 1",
            [$announcement['id'], $_SESSION['user_id']]
        );
        $dismissed = $view ? true : false;
    }
    
    // 检查cookie
    if (!$dismissed && isset($_COOKIE['announcement_dismissed'])) {
        $dismissed = intval($_COOKIE['announcement_dismissed']) >= $announcement['id'];
    }
    
    $announcement['dismissed'] = $dismissed;
    jsonResponse(['code' => 200, 'data' => $announcement]);
}

// 关闭公告（不再提示）
function dismissAnnouncement() {
    global $db;
    
    $announcementId = isset($_POST['announcement_id']) ? intval($_POST['announcement_id']) : 0;
    $remember = isset($_POST['remember']) ? filter_var($_POST['remember'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (!$announcementId) {
        jsonResponse(['code' => 400, 'message' => '参数错误']);
    }
    
    // 记录到数据库
    if (isset($_SESSION['user_id'])) {
        $existing = $db->fetch(
            "SELECT id FROM announcement_views WHERE announcement_id = ? AND user_id = ?",
            [$announcementId, $_SESSION['user_id']]
        );
        
        if ($existing) {
            $db->update('announcement_views', ['dismissed' => 1], 'id = ?', [$existing['id']]);
        } else {
            $db->insert('announcement_views', [
                'user_id' => $_SESSION['user_id'],
                'announcement_id' => $announcementId,
                'dismissed' => 1
            ]);
        }
    }
    
    // 设置cookie（用于未登录用户或持久化）
    if ($remember) {
        setcookie('announcement_dismissed', $announcementId, time() + (365 * 24 * 60 * 60), '/');
    }
    
    jsonResponse(['code' => 200, 'message' => '已关闭']);
}
