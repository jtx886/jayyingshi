<?php
// 反馈API

require_once __DIR__ . '/common.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'list':
        getFeedbackList();
        break;
    case 'create':
        createFeedback();
        break;
    case 'reply':
        createReply();
        break;
    case 'like':
        toggleLike();
        break;
    case 'get':
        getFeedbackDetail();
        break;
    default:
        jsonResponse(['code' => 400, 'message' => '无效的请求']);
}

// 获取反馈列表
function getFeedbackList() {
    global $db;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // 获取反馈列表
    $list = $db->fetchAll(
        "SELECT f.*, u.username, u.avatar FROM feedback f 
         JOIN users u ON f.user_id = u.id 
         WHERE f.status = 1 
         ORDER BY f.id DESC 
         LIMIT {$limit} OFFSET {$offset}"
    );
    
    // 获取总数
    $total = $db->fetch("SELECT COUNT(*) as count FROM feedback WHERE status = 1");
    
    // 获取每个反馈的回复数和点赞数
    $result = [];
    foreach ($list as $item) {
        $replyCount = $db->fetch("SELECT COUNT(*) as count FROM feedback_replies WHERE feedback_id = ?", [$item['id']]);
        $likeCount = $db->fetch("SELECT COUNT(*) as count FROM feedback_likes WHERE feedback_id = ?", [$item['id']]);
        
        // 获取管理员回复（置顶）
        $adminReply = $db->fetch(
            "SELECT fr.*, u.username FROM feedback_replies fr 
             JOIN users u ON fr.user_id = u.id 
             WHERE fr.feedback_id = ? AND fr.is_admin = 1 
             ORDER BY fr.created_at DESC LIMIT 1",
            [$item['id']]
        );
        
        // 获取普通用户回复
        $userReplies = $db->fetchAll(
            "SELECT fr.*, u.username FROM feedback_replies fr 
             JOIN users u ON fr.user_id = u.id 
             WHERE fr.feedback_id = ? AND fr.is_admin = 0 
             ORDER BY fr.created_at ASC LIMIT 3",
            [$item['id']]
        );
        
        // 获取当前用户点赞状态
        $isLiked = false;
        if (isset($_SESSION['user_id'])) {
            $liked = $db->fetch(
                "SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?",
                [$item['id'], $_SESSION['user_id']]
            );
            $isLiked = $liked ? true : false;
        }
        
        $item['reply_count'] = $replyCount['count'];
        $item['like_count'] = $likeCount['count'];
        $item['is_liked'] = $isLiked;
        $item['admin_reply'] = $adminReply;
        $item['user_replies_preview'] = $userReplies;
        $item['total_replies'] = $replyCount['count'];
        
        $result[] = $item;
    }
    
    jsonResponse([
        'code' => 200,
        'data' => [
            'list' => $result,
            'total' => $total['count'],
            'page' => $page,
            'total_pages' => ceil($total['count'] / $limit)
        ]
    ]);
}

// 创建反馈
function createFeedback() {
    global $db;
    $user = requireLogin();
    
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (empty($content)) {
        jsonResponse(['code' => 400, 'message' => '反馈内容不能为空']);
    }
    
    if (mb_strlen($content) > 2000) {
        jsonResponse(['code' => 400, 'message' => '反馈内容不能超过2000字']);
    }
    
    $id = $db->insert('feedback', [
        'user_id' => $user['id'],
        'content' => $content,
        'status' => 1
    ]);
    
    jsonResponse(['code' => 200, 'message' => '反馈提交成功', 'id' => $id]);
}

// 创建回复
function createReply() {
    global $db;
    $user = requireLogin();
    
    $feedbackId = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $isAdminReply = isAdmin();
    
    if (!$feedbackId) {
        jsonResponse(['code' => 400, 'message' => '参数错误']);
    }
    
    $feedback = $db->fetch("SELECT id FROM feedback WHERE id = ? AND status = 1", [$feedbackId]);
    if (!$feedback) {
        jsonResponse(['code' => 404, 'message' => '反馈不存在']);
    }
    
    if (empty($content)) {
        jsonResponse(['code' => 400, 'message' => '回复内容不能为空']);
    }
    
    $id = $db->insert('feedback_replies', [
        'feedback_id' => $feedbackId,
        'user_id' => $user['id'],
        'content' => $content,
        'is_admin' => $isAdminReply ? 1 : 0
    ]);
    
    jsonResponse(['code' => 200, 'message' => '回复成功', 'id' => $id]);
}

// 切换点赞
function toggleLike() {
    global $db;
    $user = requireLogin();
    
    $feedbackId = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
    
    if (!$feedbackId) {
        jsonResponse(['code' => 400, 'message' => '参数错误']);
    }
    
    $existing = $db->fetch(
        "SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?",
        [$feedbackId, $user['id']]
    );
    
    if ($existing) {
        $db->delete('feedback_likes', 'id = ?', [$existing['id']]);
        jsonResponse(['code' => 200, 'liked' => false, 'message' => '已取消点赞']);
    } else {
        $db->insert('feedback_likes', [
            'feedback_id' => $feedbackId,
            'user_id' => $user['id']
        ]);
        jsonResponse(['code' => 200, 'liked' => true, 'message' => '点赞成功']);
    }
}

// 获取反馈详情
function getFeedbackDetail() {
    global $db;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$id) {
        jsonResponse(['code' => 400, 'message' => '参数错误']);
    }
    
    $feedback = $db->fetch(
        "SELECT f.*, u.username, u.avatar FROM feedback f 
         JOIN users u ON f.user_id = u.id 
         WHERE f.id = ? AND f.status = 1",
        [$id]
    );
    
    if (!$feedback) {
        jsonResponse(['code' => 404, 'message' => '反馈不存在']);
    }
    
    // 获取回复
    $replies = $db->fetchAll(
        "SELECT fr.*, u.username, u.avatar FROM feedback_replies fr 
         JOIN users u ON fr.user_id = u.id 
         WHERE fr.feedback_id = ? 
         ORDER BY CASE WHEN fr.is_admin = 1 THEN 0 ELSE 1 END, fr.created_at ASC",
        [$id]
    );
    
    // 分组管理员回复和普通回复
    $adminReplies = [];
    $userReplies = [];
    
    foreach ($replies as $reply) {
        if ($reply['is_admin']) {
            $adminReplies[] = $reply;
        } else {
            $userReplies[] = $reply;
        }
    }
    
    // 获取点赞数
    $likeCount = $db->fetch("SELECT COUNT(*) as count FROM feedback_likes WHERE feedback_id = ?", [$id]);
    $isLiked = false;
    if (isset($_SESSION['user_id'])) {
        $liked = $db->fetch(
            "SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?",
            [$id, $_SESSION['user_id']]
        );
        $isLiked = $liked ? true : false;
    }
    
    $feedback['like_count'] = $likeCount['count'];
    $feedback['is_liked'] = $isLiked;
    $feedback['admin_replies'] = $adminReplies;
    $feedback['user_replies'] = $userReplies;
    $feedback['total_replies'] = count($replies);
    
    jsonResponse(['code' => 200, 'data' => $feedback]);
}
