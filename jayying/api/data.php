<?php
// 数据API - 电影数据、收藏、观看历史等

require_once __DIR__ . '/common.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'popular_movies':
        getPopularMovies();
        break;
    case 'trending':
        getTrending();
        break;
    case 'movie_detail':
        getMovieDetail();
        break;
    case 'movie_videos':
        getMovieVideos();
        break;
    case 'movie_credits':
        getMovieCredits();
        break;
    case 'movie_similar':
        getMovieSimilar();
        break;
    case 'season_detail':
        getSeasonDetail();
        break;
    case 'search':
        searchMovies();
        break;
    case 'genres':
        getGenres();
        break;
    case 'favorites':
        handleFavorites();
        break;
    case 'watch_history':
        handleWatchHistory();
        break;
    case 'user_profile':
        updateProfile();
        break;
    default:
        jsonResponse(['code' => 400, 'message' => '无效的请求']);
}

// 获取热门电影
function getPopularMovies() {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    
    $endpoint = $type === 'tv' ? '/tv/popular' : '/movie/popular';
    $data = tmdbRequest($endpoint, ['page' => $page]);
    
    if ($data) {
        jsonResponse(['code' => 200, 'data' => $data]);
    }
    jsonResponse(['code' => 500, 'message' => '获取数据失败']);
}

// 获取趋势
function getTrending() {
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $timeWindow = isset($_GET['time_window']) ? $_GET['time_window'] : 'week';
    
    $data = tmdbRequest("/trending/{$type}/{$timeWindow}");
    if ($data) {
        jsonResponse(['code' => 200, 'data' => $data]);
    }
    jsonResponse(['code' => 500, 'message' => '获取数据失败']);
}

// 获取电影详情
function getMovieDetail() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    
    if (!$id) {
        jsonResponse(['code' => 400, 'message' => '无效的ID']);
    }
    
    $endpoint = $type === 'tv' ? "/tv/{$id}" : "/movie/{$id}";
    $data = tmdbRequest($endpoint, ['append_to_response' => 'videos,images,credits']);
    
    if ($data) {
        jsonResponse(['code' => 200, 'data' => $data]);
    }
    jsonResponse(['code' => 500, 'message' => '获取数据失败']);
}

// 获取视频
function getMovieVideos() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    
    $endpoint = $type === 'tv' ? "/tv/{$id}/videos" : "/movie/{$id}/videos";
    $data = tmdbRequest($endpoint);
    jsonResponse(['code' => 200, 'data' => $data]);
}

// 获取演职员
function getMovieCredits() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    
    $endpoint = $type === 'tv' ? "/tv/{$id}/credits" : "/movie/{$id}/credits";
    $data = tmdbRequest($endpoint);
    jsonResponse(['code' => 200, 'data' => $data]);
}

// 获取相似推荐
function getMovieSimilar() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    
    $endpoint = $type === 'tv' ? "/tv/{$id}/similar" : "/movie/{$id}/similar";
    $data = tmdbRequest($endpoint);
    jsonResponse(['code' => 200, 'data' => $data]);
}

// 获取季详情
function getSeasonDetail() {
    $tvId = isset($_GET['tv_id']) ? intval($_GET['tv_id']) : 0;
    $seasonNumber = isset($_GET['season']) ? intval($_GET['season']) : 1;
    
    $data = tmdbRequest("/tv/{$tvId}/season/{$seasonNumber}");
    jsonResponse(['code' => 200, 'data' => $data]);
}

// 搜索
function searchMovies() {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $type = isset($_GET['type']) ? $_GET['type'] : 'multi';
    
    if (empty($query)) {
        jsonResponse(['code' => 400, 'message' => '请输入搜索关键词']);
    }
    
    $endpoint = $type === 'movie' ? '/search/movie' : ($type === 'tv' ? '/search/tv' : '/search/multi');
    $data = tmdbRequest($endpoint, [
        'query' => $query,
        'page' => $page,
        'include_adult' => 'false'
    ]);
    
    if ($data) {
        jsonResponse(['code' => 200, 'data' => $data]);
    }
    jsonResponse(['code' => 500, 'message' => '搜索失败']);
}

// 获取分类
function getGenres() {
    $type = isset($_GET['type']) ? $_GET['type'] : 'movie';
    $endpoint = $type === 'tv' ? '/genre/tv/list' : '/genre/movie/list';
    $data = tmdbRequest($endpoint);
    jsonResponse(['code' => 200, 'data' => $data]);
}

// 收藏管理
function handleFavorites() {
    global $db;
    $user = requireLogin();
    
    $subAction = isset($_GET['sub_action']) ? $_GET['sub_action'] : (isset($_POST['sub_action']) ? $_POST['sub_action'] : 'list');
    
    switch ($subAction) {
        case 'list':
            $favorites = $db->fetchAll(
                "SELECT * FROM favorites WHERE user_id = ? ORDER BY created_at DESC",
                [$user['id']]
            );
            jsonResponse(['code' => 200, 'data' => $favorites]);
            break;
            
        case 'add':
            $tmdbId = isset($_POST['tmdb_id']) ? $_POST['tmdb_id'] : '';
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $poster = isset($_POST['poster']) ? $_POST['poster'] : '';
            $mediaType = isset($_POST['media_type']) ? $_POST['media_type'] : 'movie';
            
            if (!$tmdbId || !$title) {
                jsonResponse(['code' => 400, 'message' => '参数不完整']);
            }
            
            $exists = $db->fetch(
                "SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ?",
                [$user['id'], $tmdbId]
            );
            
            if ($exists) {
                jsonResponse(['code' => 400, 'message' => '已在收藏列表中']);
            }
            
            $id = $db->insert('favorites', [
                'user_id' => $user['id'],
                'tmdb_id' => $tmdbId,
                'title' => $title,
                'poster' => $poster,
                'media_type' => $mediaType
            ]);
            
            jsonResponse(['code' => 200, 'message' => '添加成功', 'id' => $id]);
            break;
            
        case 'remove':
            $favoriteId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $tmdbId = isset($_POST['tmdb_id']) ? $_POST['tmdb_id'] : '';
            
            if ($favoriteId) {
                $db->delete('favorites', 'id = ? AND user_id = ?', [$favoriteId, $user['id']]);
            } elseif ($tmdbId) {
                $db->delete('favorites', 'tmdb_id = ? AND user_id = ?', [$tmdbId, $user['id']]);
            }
            
            jsonResponse(['code' => 200, 'message' => '已移除']);
            break;
            
        case 'check':
            $tmdbId = isset($_GET['tmdb_id']) ? $_GET['tmdb_id'] : '';
            if ($tmdbId) {
                $exists = $db->fetch(
                    "SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ?",
                    [$user['id'], $tmdbId]
                );
                jsonResponse(['code' => 200, 'is_favorite' => $exists ? true : false]);
            }
            jsonResponse(['code' => 400, 'message' => '参数不完整']);
            break;
    }
}

// 观看历史管理
function handleWatchHistory() {
    global $db;
    $user = requireLogin();
    
    $subAction = isset($_GET['sub_action']) ? $_GET['sub_action'] : (isset($_POST['sub_action']) ? $_POST['sub_action'] : 'list');
    
    switch ($subAction) {
        case 'list':
            $history = $db->fetchAll(
                "SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC",
                [$user['id']]
            );
            jsonResponse(['code' => 200, 'data' => $history]);
            break;
            
        case 'save':
            $tmdbId = isset($_POST['tmdb_id']) ? $_POST['tmdb_id'] : '';
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $poster = isset($_POST['poster']) ? $_POST['poster'] : '';
            $mediaType = isset($_POST['media_type']) ? $_POST['media_type'] : 'movie';
            $season = isset($_POST['season']) ? intval($_POST['season']) : 0;
            $episode = isset($_POST['episode']) ? intval($_POST['episode']) : 0;
            $progress = isset($_POST['progress']) ? intval($_POST['progress']) : 0;
            
            if (!$tmdbId || !$title) {
                jsonResponse(['code' => 400, 'message' => '参数不完整']);
            }
            
            $exists = $db->fetch(
                "SELECT id FROM watch_history WHERE user_id = ? AND tmdb_id = ?",
                [$user['id'], $tmdbId]
            );
            
            if ($exists) {
                $db->update('watch_history', [
                    'title' => $title,
                    'poster' => $poster,
                    'media_type' => $mediaType,
                    'season' => $season,
                    'episode' => $episode,
                    'progress' => $progress,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$exists['id']]);
            } else {
                $db->insert('watch_history', [
                    'user_id' => $user['id'],
                    'tmdb_id' => $tmdbId,
                    'title' => $title,
                    'poster' => $poster,
                    'media_type' => $mediaType,
                    'season' => $season,
                    'episode' => $episode,
                    'progress' => $progress
                ]);
            }
            
            jsonResponse(['code' => 200, 'message' => '保存成功']);
            break;
            
        case 'remove':
            $historyId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $tmdbId = isset($_POST['tmdb_id']) ? $_POST['tmdb_id'] : '';
            
            if ($historyId) {
                $db->delete('watch_history', 'id = ? AND user_id = ?', [$historyId, $user['id']]);
            } elseif ($tmdbId) {
                $db->delete('watch_history', 'tmdb_id = ? AND user_id = ?', [$tmdbId, $user['id']]);
            }
            
            jsonResponse(['code' => 200, 'message' => '已移除']);
            break;
            
        case 'clear':
            $db->delete('watch_history', 'user_id = ?', [$user['id']]);
            jsonResponse(['code' => 200, 'message' => '已清空历史']);
            break;
    }
}

// 更新用户资料
function updateProfile() {
    global $db;
    $user = requireLogin();
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $avatar = isset($_POST['avatar']) ? trim($_POST['avatar']) : null;
    
    $data = [];
    
    if ($username !== null && $username !== $user['username']) {
        if (strlen($username) < 3 || strlen($username) > 20) {
            jsonResponse(['code' => 400, 'message' => '用户名长度需在3-20位之间']);
        }
        
        $exists = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $user['id']]);
        if ($exists) {
            jsonResponse(['code' => 400, 'message' => '用户名已被占用']);
        }
        $data['username'] = $username;
    }
    
    if ($avatar !== null) {
        $data['avatar'] = $avatar;
    }
    
    if (!empty($data)) {
        $db->update('users', $data, 'id = ?', [$user['id']]);
        $_SESSION['username'] = !empty($data['username']) ? $data['username'] : $user['username'];
    }
    
    $updatedUser = $db->fetch("SELECT id, username, email, avatar FROM users WHERE id = ?", [$user['id']]);
    jsonResponse(['code' => 200, 'data' => $updatedUser]);
}
