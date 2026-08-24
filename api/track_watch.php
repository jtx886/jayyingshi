<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tmdb.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(array('success' => false, 'message' => 'Method Error'));

$mediaId = intval($_POST['media_id'] ?? 0);
$mediaType = $_POST['media_type'] ?? '';
$episode = intval($_POST['episode'] ?? 0);
$seconds = intval($_POST['seconds'] ?? 0);

if (!$mediaId || !$mediaType) jsonResponse(array('success' => false));

$db = Database::getInstance();
$uid = $_SESSION['user_id'];

// 获取标题和海报（从收藏或历史）
$meta = $db->fetchOne("SELECT media_title, media_poster FROM watch_history WHERE user_id = ? AND media_id = ? AND media_type = ? ORDER BY id DESC LIMIT 1", array($uid, $mediaId, $mediaType));
if (!$meta) {
    $meta = $db->fetchOne("SELECT media_title, media_poster FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ? LIMIT 1", array($uid, $mediaId, $mediaType));
}
$title = $meta['media_title'] ?? '未知';
$poster = $meta['media_poster'] ?? '';

if (empty($title) || empty($poster)) {
    // 通过API拉标题/海报
    if ($mediaType === 'movie') {
        $d = @TMDB::getMovieDetail($mediaId);
        if ($d) {
            $title = $d['title'] ?? $title;
            $poster = TMDB::getImageUrl($d['poster_path'] ?? '', 'w342');
        }
    } else {
        $d = @TMDB::getTvDetail($mediaId);
        if ($d) {
            $title = $d['name'] ?? $title;
            $poster = TMDB::getImageUrl($d['poster_path'] ?? '', 'w342');
        }
    }
}

$exist = $db->fetchOne("SELECT id FROM watch_history WHERE user_id = ? AND media_id = ? AND media_type = ? AND episode = ?", array($uid, $mediaId, $mediaType, $episode));
$now = date('Y-m-d H:i:s');
if ($exist) {
    $db->update('watch_history', array('watch_seconds' => $seconds, 'last_watch_time' => $now, 'media_title' => $title, 'media_poster' => $poster), 'id = ?', array($exist['id']));
} else {
    $db->insert('watch_history', array(
        'user_id' => $uid,
        'media_id' => $mediaId,
        'media_type' => $mediaType,
        'episode' => $episode,
        'watch_seconds' => $seconds,
        'media_title' => $title,
        'media_poster' => $poster,
        'last_watch_time' => $now
    ));
}
jsonResponse(array('success' => true));
?>
