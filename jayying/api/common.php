<?php
// 公共函数库

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

// 初始化数据库
$db = Database::getInstance();
$db->initTables();

// 加载主题设置到常量
$theme = $db->fetch("SELECT * FROM theme_settings LIMIT 1");
if ($theme) {
    define('CUSTOM_PRIMARY', $theme['primary_color']);
    define('CUSTOM_SECONDARY', $theme['secondary_color']);
    define('CUSTOM_ACCENT', $theme['accent_color']);
    define('CUSTOM_BG', $theme['bg_color']);
    define('CUSTOM_CARD', $theme['card_color']);
    define('CUSTOM_TEXT', $theme['text_color']);
    define('CUSTOM_TEXT_SECONDARY', $theme['text_secondary']);
} else {
    define('CUSTOM_PRIMARY', THEME_PRIMARY);
    define('CUSTOM_SECONDARY', THEME_SECONDARY);
    define('CUSTOM_ACCENT', THEME_ACCENT);
    define('CUSTOM_BG', THEME_BG);
    define('CUSTOM_CARD', THEME_CARD);
    define('CUSTOM_TEXT', THEME_TEXT);
    define('CUSTOM_TEXT_SECONDARY', THEME_TEXT_SECONDARY);
}

// JSON响应函数
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取当前用户
function getCurrentUser() {
    global $db;
    if (isset($_SESSION['user_id'])) {
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
        if ($user && $user['status'] == 1) {
            // 检查封禁时间
            if (!empty($user['ban_until']) && strtotime($user['ban_until']) < time()) {
                // 封禁过期，自动解封
                $db->update('users', ['status' => 1, 'ban_until' => '', 'ban_reason' => ''], 'id = ?', [$user['id']]);
                return $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);
            }
            return $user;
        }
        // 用户被封禁
        if ($user && $user['status'] == 0) {
            if (!empty($user['ban_until']) && strtotime($user['ban_until']) > time()) {
                return $user; // 仍然是封禁状态
            }
        }
    }
    return null;
}

// 检查是否管理员
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// 需要登录
function requireLogin() {
    $user = getCurrentUser();
    if (!$user) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(['code' => 401, 'message' => '需要登录才可以观看哦,如没有账号请注册!']);
        }
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    return $user;
}

// 需要管理员权限
function requireAdmin() {
    if (!isAdmin()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(['code' => 403, 'message' => '无权限访问']);
        }
        header('Location: /admin/login.php');
        exit;
    }
}

// 生成随机字符串
function randomString($length = 32) {
    return bin2hex(random_bytes($length));
}

// 生成验证码
function generateCode() {
    return strval(mt_rand(100000, 999999));
}

// 密码哈希
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

// 验证密码
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// TMDB API请求
function tmdbRequest($endpoint, $params = []) {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = 'zh-CN';
    $url = TMDB_API_URL . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Jay影视/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURL_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    return null;
}

// 获取图片URL
function getImageUrl($path, $size = 'w500') {
    if (empty($path)) {
        return '';
    }
    return TMDB_IMAGE_URL . '/' . $size . $path;
}

// 获取当前播放源URL
function getCurrentSourceUrl() {
    global $db;
    $source = $db->fetch("SELECT url FROM sources WHERE is_default = 1 AND status = 1");
    if ($source) {
        return $source['url'];
    }
    return DEFAULT_SOURCE_URL;
}

// 格式化日期
function formatDate($date) {
    if (empty($date)) {
        return '';
    }
    return date('Y-m-d', strtotime($date));
}

// 格式化文件大小
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// 清理过期验证码
function cleanExpiredCodes() {
    global $db;
    $db->query("DELETE FROM verification_codes WHERE expires_at < datetime('now', 'localtime')");
}

// 检查是否已点赞反馈
function isFeedbackLiked($feedbackId, $userId) {
    global $db;
    $like = $db->fetch(
        "SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?",
        [$feedbackId, $userId]
    );
    return $like ? true : false;
}

// 获取公告（弹窗显示）
function getActiveAnnouncement() {
    global $db;
    return $db->fetch("SELECT * FROM announcements WHERE status = 1 ORDER BY id DESC LIMIT 1");
}

// 检查用户是否已关闭公告
function isAnnouncementDismissed($announcementId, $userId) {
    global $db;
    if (!$userId) {
        return false;
    }
    $view = $db->fetch(
        "SELECT id FROM announcement_views WHERE announcement_id = ? AND user_id = ? AND dismissed = 1",
        [$announcementId, $userId]
    );
    return $view ? true : false;
}

// 获取用户头像
function getUserAvatar($user) {
    if (!empty($user['avatar'])) {
        return $user['avatar'];
    }
    // 默认头像（使用用户名首字母生成）
    $char = mb_substr($user['username'], 0, 1, 'UTF-8');
    return '<div class="default-avatar">' . htmlspecialchars($char) . '</div>';
}

// 安全输出
function safeOutput($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 生成CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = randomString(32);
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
