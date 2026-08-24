<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/db.php';

$db = Database::getInstance();

date_default_timezone_set('Asia/Shanghai');

function require_login() {
    if (empty($_SESSION['user_id'])) {
        $current_url = $_SERVER['REQUEST_URI'];
        header('Location: login.php?redirect=' . urlencode($current_url));
        exit;
    }
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function current_user() {
    global $db;
    if (!empty($_SESSION['user_id'])) {
        return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    return null;
}

function logout() {
    $_SESSION = [];
    session_destroy();
}

function tmdb_request($endpoint, $params = []) {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = 'zh-CN';
    $url = TMDB_API_URL . $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURL_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    return null;
}

function tmdb_image($path, $size = 'w500') {
    if (empty($path)) return '';
    return TMDB_IMAGE_URL . '/' . $size . $path;
}

function get_play_sources() {
    global $db;
    return $db->fetchAll("SELECT * FROM sources WHERE status = 1 ORDER BY is_default DESC, id ASC");
}

function get_player_parser() {
    return PLAYER_URL;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function send_json($data) {
    json_response($data);
}
