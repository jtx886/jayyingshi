<?php
// 管理员API

require_once __DIR__ . '/common.php';

// 管理员登录
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_login_time'] = time();
        jsonResponse(['code' => 200, 'message' => '登录成功']);
    } else {
        jsonResponse(['code' => 400, 'message' => '账号或密码错误']);
    }
}

// 管理员登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['code' => 200, 'message' => '已退出']);
}

// 检查管理员状态
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    jsonResponse(['code' => 200, 'is_admin' => isAdmin()]);
}

requireAdmin();

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'dashboard':
        getDashboard();
        break;
    case 'users':
        handleUsers();
        break;
    case 'user_detail':
        getUserDetail();
        break;
    case 'sources':
        handleSources();
        break;
    case 'announcements':
        handleAnnouncements();
        break;
    case 'themes':
        handleThemes();
        break;
    case 'send_email':
        handleSendEmail();
        break;
    case 'feedback_list':
        getFeedbackAdminList();
        break;
    case 'delete_feedback':
        deleteFeedback();
        break;
    default:
        jsonResponse(['code' => 400, 'message' => '无效的请求']);
}

// 仪表盘数据
function getDashboard() {
    global $db;
    
    // 统计数据
    $totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users");
    $activeUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 1");
    $bannedUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 0");
    $totalFavorites = $db->fetch("SELECT COUNT(*) as count FROM favorites");
    $totalHistory = $db->fetch("SELECT COUNT(*) as count FROM watch_history");
    $totalFeedback = $db->fetch("SELECT COUNT(*) as count FROM feedback WHERE status = 1");
    
    // 最新注册用户
    $recentUsers = $db->fetchAll(
        "SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5"
    );
    
    // 最新反馈
    $recentFeedback = $db->fetchAll(
        "SELECT f.id, f.content, f.created_at, u.username 
         FROM feedback f 
         JOIN users u ON f.user_id = u.id 
         WHERE f.status = 1 
         ORDER BY f.created_at DESC LIMIT 5"
    );
    
    // 观看历史TOP
    $topHistory = $db->fetchAll(
        "SELECT w.*, u.username 
         FROM watch_history w 
         JOIN users u ON w.user_id = u.id 
         ORDER BY w.updated_at DESC LIMIT 5"
    );
    
    // 收藏TOP
    $topFavorites = $db->fetchAll(
        "SELECT f.*, u.username 
         FROM favorites f 
         JOIN users u ON f.user_id = u.id 
         ORDER BY f.created_at DESC LIMIT 5"
    );
    
    // 每日注册统计（最近7天）
    $dailyStats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $stats = $db->fetch(
            "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = ?",
            [$date]
        );
        $dailyStats[] = [
            'date' => $date,
            'count' => intval($stats['count'])
        ];
    }
    
    jsonResponse([
        'code' => 200,
        'data' => [
            'total_users' => $totalUsers['count'],
            'active_users' => $activeUsers['count'],
            'banned_users' => $bannedUsers['count'],
            'total_favorites' => $totalFavorites['count'],
            'total_history' => $totalHistory['count'],
            'total_feedback' => $totalFeedback['count'],
            'recent_users' => $recentUsers,
            'recent_feedback' => $recentFeedback,
            'top_history' => $topHistory,
            'top_favorites' => $topFavorites,
            'daily_stats' => $dailyStats
        ]
    ]);
}

// 用户管理
function handleUsers() {
    global $db;
    $subAction = isset($_GET['sub_action']) ? $_GET['sub_action'] : (isset($_POST['sub_action']) ? $_POST['sub_action'] : 'list');
    
    switch ($subAction) {
        case 'list':
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
            $offset = ($page - 1) * $limit;
            
            $where = [];
            $params = [];
            
            if ($search) {
                $where[] = "(username LIKE ? OR email LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            if ($status >= 0) {
                $where[] = "status = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $users = $db->fetchAll(
                "SELECT * FROM users {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}",
                $params
            );
            
            $total = $db->fetch("SELECT COUNT(*) as count FROM users {$whereClause}", $params);
            
            jsonResponse([
                'code' => 200,
                'data' => [
                    'list' => $users,
                    'total' => $total['count'],
                    'page' => $page,
                    'total_pages' => ceil($total['count'] / $limit)
                ]
            ]);
            break;
            
        case 'ban':
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $banDays = isset($_POST['ban_days']) ? intval($_POST['ban_days']) : 0;
            $permanent = isset($_POST['permanent']) ? filter_var($_POST['permanent'], FILTER_VALIDATE_BOOLEAN) : false;
            
            if (!$userId) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                jsonResponse(['code' => 404, 'message' => '用户不存在']);
            }
            
            if ($permanent) {
                $banUntil = '2099-12-31 23:59:59';
            } elseif ($banDays > 0) {
                $banUntil = date('Y-m-d H:i:s', time() + ($banDays * 86400));
            } else {
                jsonResponse(['code' => 400, 'message' => '请设置封禁时间']);
            }
            
            $db->update('users', [
                'status' => 0,
                'ban_until' => $banUntil,
                'ban_reason' => $reason
            ], 'id = ?', [$userId]);
            
            // 发送封禁邮件
            require_once __DIR__ . '/Mailer.php';
            $mailer = new Mailer();
            $mailer->sendBanNotification($user['email'], $user['username'], $reason, $banUntil);
            
            jsonResponse(['code' => 200, 'message' => '封禁成功']);
            break;
            
        case 'unban':
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            if (!$userId) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            $db->update('users', [
                'status' => 1,
                'ban_until' => '',
                'ban_reason' => ''
            ], 'id = ?', [$userId]);
            
            jsonResponse(['code' => 200, 'message' => '已解封']);
            break;
            
        case 'delete':
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            if (!$userId) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            // 删除用户相关数据
            $db->delete('favorites', 'user_id = ?', [$userId]);
            $db->delete('watch_history', 'user_id = ?', [$userId]);
            $db->delete('feedback', 'user_id = ?', [$userId]);
            $db->delete('users', 'id = ?', [$userId]);
            
            jsonResponse(['code' => 200, 'message' => '删除成功']);
            break;
    }
}

// 获取用户详情
function getUserDetail() {
    global $db;
    
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
    
    if (!$userId) {
        jsonResponse(['code' => 400, 'message' => '参数错误']);
    }
    
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        jsonResponse(['code' => 404, 'message' => '用户不存在']);
    }
    
    $data = ['user' => $user];
    
    if ($tab === 'favorites') {
        $data['favorites'] = $db->fetchAll(
            "SELECT * FROM favorites WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    } elseif ($tab === 'history') {
        $data['history'] = $db->fetchAll(
            "SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC",
            [$userId]
        );
    } elseif ($tab === 'feedback') {
        $data['feedback'] = $db->fetchAll(
            "SELECT f.*, f2.content as reply_content FROM feedback f 
             LEFT JOIN feedback_replies f2 ON f.id = f2.feedback_id 
             WHERE f.user_id = ? AND f.status = 1 
             ORDER BY f.created_at DESC",
            [$userId]
        );
    }
    
    jsonResponse(['code' => 200, 'data' => $data]);
}

// 播放源管理
function handleSources() {
    global $db;
    $subAction = isset($_GET['sub_action']) ? $_GET['sub_action'] : (isset($_POST['sub_action']) ? $_POST['sub_action'] : 'list');
    
    switch ($subAction) {
        case 'list':
            $sources = $db->fetchAll("SELECT * FROM sources ORDER BY is_default DESC, id ASC");
            jsonResponse(['code' => 200, 'data' => $sources]);
            break;
            
        case 'add':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $url = isset($_POST['url']) ? trim($_POST['url']) : '';
            
            if (!$name || !$url) {
                jsonResponse(['code' => 400, 'message' => '名称和URL不能为空']);
            }
            
            $id = $db->insert('sources', [
                'name' => $name,
                'url' => $url,
                'is_default' => 0,
                'status' => 1
            ]);
            
            jsonResponse(['code' => 200, 'message' => '添加成功', 'id' => $id]);
            break;
            
        case 'update':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $url = isset($_POST['url']) ? trim($_POST['url']) : '';
            $status = isset($_POST['status']) ? intval($_POST['status']) : null;
            
            if (!$id) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            $data = [];
            if ($name) $data['name'] = $name;
            if ($url) $data['url'] = $url;
            if ($status !== null) $data['status'] = $status;
            
            $db->update('sources', $data, 'id = ?', [$id]);
            jsonResponse(['code' => 200, 'message' => '更新成功']);
            break;
            
        case 'delete':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if (!$id) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            $source = $db->fetch("SELECT is_default FROM sources WHERE id = ?", [$id]);
            if ($source && $source['is_default']) {
                jsonResponse(['code' => 400, 'message' => '默认播放源不能删除']);
            }
            
            $db->delete('sources', 'id = ?', [$id]);
            jsonResponse(['code' => 200, 'message' => '删除成功']);
            break;
            
        case 'set_default':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if (!$id) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            // 取消其他默认
            $db->query("UPDATE sources SET is_default = 0");
            $db->update('sources', ['is_default' => 1], 'id = ?', [$id]);
            
            jsonResponse(['code' => 200, 'message' => '已设为默认']);
            break;
    }
}

// 公告管理
function handleAnnouncements() {
    global $db;
    $subAction = isset($_GET['sub_action']) ? $_GET['sub_action'] : (isset($_POST['sub_action']) ? $_POST['sub_action'] : 'list');
    
    switch ($subAction) {
        case 'list':
            $announcements = $db->fetchAll("SELECT * FROM announcements ORDER BY id DESC");
            jsonResponse(['code' => 200, 'data' => $announcements]);
            break;
            
        case 'add':
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            
            if (!$title || !$content) {
                jsonResponse(['code' => 400, 'message' => '标题和内容不能为空']);
            }
            
            $id = $db->insert('announcements', [
                'title' => $title,
                'content' => $content,
                'status' => 1
            ]);
            
            jsonResponse(['code' => 200, 'message' => '发布成功', 'id' => $id]);
            break;
            
        case 'update':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            
            if (!$id) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            $data = [];
            if ($title) $data['title'] = $title;
            if ($content) $data['content'] = $content;
            
            $db->update('announcements', $data, 'id = ?', [$id]);
            jsonResponse(['code' => 200, 'message' => '更新成功']);
            break;
            
        case 'toggle_status':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : null;
            
            if (!$id) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            if ($status === null) {
                $ann = $db->fetch("SELECT status FROM announcements WHERE id = ?", [$id]);
                $status = $ann['status'] ? 0 : 1;
            }
            
            $db->update('announcements', ['status' => $status], 'id = ?', [$id]);
            jsonResponse(['code' => 200, 'message' => '状态已更新']);
            break;
            
        case 'delete':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if (!$id) {
                jsonResponse(['code' => 400, 'message' => '参数错误']);
            }
            
            $db->delete('announcements', 'id = ?', [$id]);
            $db->delete('announcement_views', 'announcement_id = ?', [$id]);
            jsonResponse(['code' => 200, 'message' => '删除成功']);
            break;
    }
}

// 主题设置
function handleThemes() {
    global $db;
    $subAction = isset($_GET['sub_action']) ? $_GET['sub_action'] : (isset($_POST['sub_action']) ? $_POST['sub_action'] : 'get');
    
    switch ($subAction) {
        case 'get':
            $theme = $db->fetch("SELECT * FROM theme_settings LIMIT 1");
            jsonResponse(['code' => 200, 'data' => $theme]);
            break;
            
        case 'update':
            $colors = [
                'primary_color' => isset($_POST['primary_color']) ? $_POST['primary_color'] : THEME_PRIMARY,
                'secondary_color' => isset($_POST['secondary_color']) ? $_POST['secondary_color'] : THEME_SECONDARY,
                'accent_color' => isset($_POST['accent_color']) ? $_POST['accent_color'] : THEME_ACCENT,
                'bg_color' => isset($_POST['bg_color']) ? $_POST['bg_color'] : THEME_BG,
                'card_color' => isset($_POST['card_color']) ? $_POST['card_color'] : THEME_CARD,
                'text_color' => isset($_POST['text_color']) ? $_POST['text_color'] : THEME_TEXT,
                'text_secondary' => isset($_POST['text_secondary']) ? $_POST['text_secondary'] : THEME_TEXT_SECONDARY
            ];
            
            $theme = $db->fetch("SELECT id FROM theme_settings LIMIT 1");
            if ($theme) {
                $db->update('theme_settings', $colors, 'id = ?', [$theme['id']]);
            } else {
                $db->insert('theme_settings', $colors);
            }
            
            jsonResponse(['code' => 200, 'message' => '主题已更新']);
            break;
            
        case 'reset':
            $defaults = [
                'primary_color' => THEME_PRIMARY,
                'secondary_color' => THEME_SECONDARY,
                'accent_color' => THEME_ACCENT,
                'bg_color' => THEME_BG,
                'card_color' => THEME_CARD,
                'text_color' => THEME_TEXT,
                'text_secondary' => THEME_TEXT_SECONDARY
            ];
            
            $theme = $db->fetch("SELECT id FROM theme_settings LIMIT 1");
            if ($theme) {
                $db->update('theme_settings', $defaults, 'id = ?', [$theme['id']]);
            }
            
            jsonResponse(['code' => 200, 'message' => '已重置为默认主题']);
            break;
    }
}

// 发送邮件
function handleSendEmail() {
    global $db;
    
    $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $sendAll = isset($_POST['send_all']) ? filter_var($_POST['send_all'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (!$subject || !$content) {
        jsonResponse(['code' => 400, 'message' => '主题和内容不能为空']);
    }
    
    require_once __DIR__ . '/Mailer.php';
    $mailer = new Mailer();
    
    $users = [];
    if ($sendAll) {
        $users = $db->fetchAll("SELECT email, username FROM users WHERE status = 1");
    } else if (!empty($userIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
        $users = $db->fetchAll(
            "SELECT email, username FROM users WHERE id IN ({$inPlaceholders}) AND status = 1",
            $userIds
        );
    }
    
    $success = 0;
    $fail = 0;
    
    foreach ($users as $user) {
        $sent = $mailer->sendCustomNotification($user['email'], $user['username'], $subject, $content);
        if ($sent) {
            $success++;
        } else {
            $fail++;
        }
    }
    
    jsonResponse([
        'code' => 200,
        'message' => "发送完成",
        'data' => [
            'success' => $success,
            'fail' => $fail,
            'total' => count($users)
        ]
    ]);
}

// 反馈列表（管理员）
function getFeedbackAdminList() {
    global $db;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    
    $list = $db->fetchAll(
        "SELECT f.*, u.username, u.email FROM feedback f 
         JOIN users u ON f.user_id = u.id 
         ORDER BY f.id DESC 
         LIMIT {$limit} OFFSET {$offset}"
    );
    
    $total = $db->fetch("SELECT COUNT(*) as count FROM feedback");
    
    jsonResponse([
        'code' => 200,
        'data' => [
            'list' => $list,
            'total' => $total['count'],
            'page' => $page,
            'total_pages' => ceil($total['count'] / $limit)
        ]
    ]);
}

// 删除反馈
function deleteFeedback() {
    global $db;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (!$id) {
        jsonResponse(['code' => 400, 'message' => '参数错误']);
    }
    
    $db->delete('feedback', 'id = ?', [$id]);
    $db->delete('feedback_replies', 'feedback_id = ?', [$id]);
    $db->delete('feedback_likes', 'feedback_id = ?', [$id]);
    
    jsonResponse(['code' => 200, 'message' => '删除成功']);
}
