<?php
require_once dirname(__FILE__) . '/auth.php';
require_once dirname(__FILE__) . '/settings.php';

function getThemeColor() {
    return SiteSetting::get('theme_color', '#7c3aed');
}

function getPlayerParser() {
    return SiteSetting::get('player_parser', 'https://svip.ffzyplay.com/?url=');
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function getCurrentPageUrl() {
    return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function formatTimeAgo($datetime) {
    $now = time();
    $ago = strtotime($datetime);
    $diff = $now - $ago;
    if ($diff < 60) return $diff . '秒前';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $ago);
}

function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    $parts = array();
    if ($hours > 0) $parts[] = $hours . '小时';
    if ($minutes > 0) $parts[] = $minutes . '分';
    if ($secs > 0 && count($parts) == 0) $parts[] = $secs . '秒';
    return implode('', $parts);
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getUserAvatar($user) {
    if (!empty($user['avatar']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['avatar'])) {
        return $user['avatar'];
    }
    // 默认头像：首字母
    $initial = mb_substr($user['username'], 0, 1);
    $colors = array('#7c3aed', '#2563eb', '#db2777', '#ea580c', '#059669', '#0891b2');
    $color = $colors[hexdec(substr(md5($user['username']), 0, 1)) % count($colors)];
    return 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="80" height="80" fill="' . $color . '"/><text x="40" y="40" text-anchor="middle" dy=".35em" fill="white" font-family="Arial" font-size="36" font-weight="bold">' . $initial . '</text></svg>');
}
?>
