<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$statCards = [
    ['label' => '总用户数', 'value' => '2,847', 'icon' => '👥', 'color' => '#05d4c7', 'change': '+12.5%'],
    ['label' => '活跃用户', 'value' => '1,245', 'icon' => '⚡', 'color' => '#f59e0b', 'change': '+8.3%'],
    ['label' => '封禁用户', 'value' => '32', 'icon' => '🚫', 'color' => '#ef4444', 'change': '+2'],
    ['label' => '总收藏', 'value' => '18,432', 'icon' => '⭐', 'color' => '#8b5cf6', 'change': '+15.7%'],
    ['label' => '总观看历史', 'value' => '45,821', 'icon' => '🎬', 'color' => '#06b6d4', 'change': '+22.1%'],
    ['label' => '总反馈', 'value' => '186', 'icon' => '💬', 'color' => '#ec4899', 'change': '+5'],
];
$dailyRegistrations = [
    ['day' => '周一', 'count' => 23],
    ['day' => '周二', 'count' => 45],
    ['day' => '周三', 'count' => 38],
    ['day' => '周四', 'count' => 62],
    ['day' => '周五', 'count' => 89],
    ['day' => '周六', 'count' => 127],
    ['day' => '周日', 'count' => 98],
];
$maxRegistration = max(array_column($dailyRegistrations, 'count'));
$latestUsers = [
    ['name' => '张小明', 'email' => 'zhangxm@example.com', 'time' => '2分钟前', 'avatar' => '张'],
    ['name' => '李婷婷', 'email' => 'litt@example.com', 'time' => '15分钟前', 'avatar' => '李'],
    ['name' => '王大壮', 'email' => 'wangdz@example.com', 'time' => '32分钟前', 'avatar' => '王'],
    ['name' => '赵雪琪', 'email' => 'zhaoxq@example.com', 'time' => '1小时前', 'avatar' => '赵'],
    ['name' => '陈思远', 'email' => 'chensy@example.com', 'time' => '2小时前', 'avatar' => '陈'],
];
$latestFeedbacks = [
    ['user' => '用户A', 'content' => '播放源加载速度有点慢，希望能优化一下', 'time' => '5分钟前'],
    ['user' => '用户B', 'content' => '电影资源很全，界面也很美观，点赞！', 'time' => '20分钟前'],
    ['user' => '用户C', 'content' => '建议增加夜间模式自动切换功能', 'time' => '45分钟前'],
    ['user' => '用户D', 'content' => '部分老电影画质不太清楚，能否提供高清版本', 'time' => '1小时前'],
];
$topWatched = [
    ['rank' => 1, 'title' => '流浪地球3', 'count' => '12,458次', 'poster' => '🌍'],
    ['rank' => 2, 'title' => '满江红', 'count' => '9,832次', 'poster' => '🎭'],
    ['rank' => 3, 'title' => '消失的她', 'count' => '8,671次', 'poster' => '🎬'],
    ['rank' => 4, 'title' => '长安三万里', 'count' => '7,245次', 'poster' => '🏯'],
    ['rank' => 5, 'title' => '孤注一掷', 'count' => '6,832次', 'poster' => '🎲'],
];
$topFavorites = [
    ['rank' => 1, 'title' => '无间道', 'count' => '3,892次', 'poster' => '🕵️'],
    ['rank' => 2, 'title' => '霸王别姬', 'count' => '3,456次', 'poster' => '🎭'],
    ['rank' => 3, 'title' => '泰坦尼克号', 'count' => '2,987次', 'poster' => '🚢'],
    ['rank' => 4, 'title' => '千与千寻', 'count' => '2,654次', 'poster' => '🐉'],
    ['rank' => 5, 'title' => '盗梦空间', 'count' => '2,341次', 'poster' => '🌀'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仪表盘 - Jay影视后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #1a1f2e;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 240px;
            background: #252d3d;
            padding: 24px 0;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease;
            z-index: 100;
        }
        .sidebar-header {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 16px;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #05d4c7 0%, #03a89e 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
            font-size: 18px;
        }
        .logo-text {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
        }
        .logo-sub {
            color: #8b95a7;
            font-size: 11px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #8b95a7;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
        }
        .nav-item.active {
            background: rgba(5, 212, 199, 0.1);
            color: #05d4c7;
            border-left-color: #05d4c7;
        }
        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        .main-content {
            margin-left: 240px;
            padding: 24px 32px;
            min-height: 100vh;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
        }
        .page-subtitle {
            color: #8b95a7;
            font-size: 14px;
            margin-top: 4px;
        }
        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #05d4c7 0%, #03a89e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
        }
        .admin-name {
            color: #fff;
            font-size: 14px;
        }
        .admin-role {
            color: #8b95a7;
            font-size: 12px;
        }
        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        .mobile-menu-btn {
            display: none;
            background: #252d3d;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #252d3d;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .stat-change {
            font-size: 12px;
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }
        .stat-label {
            color: #8b95a7;
            font-size: 13px;
            margin-top: 4px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .card {
            background: #252d3d;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 24px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        .card-link {
            color: #05d4c7;
            text-decoration: none;
            font-size: 13px;
        }
        .chart-container {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            height: 180px;
            padding: 20px 0;
        }
        .chart-bar-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .chart-bar {
            width: 100%;
            max-width: 40px;
            background: linear-gradient(180deg, #05d4c7 0%, #03a89e 100%);
            border-radius: 6px 6px 0 0;
            transition: height 0.5s ease;
            min-height: 8px;
        }
        .chart-bar:hover {
            background: linear-gradient(180deg, #06e5d7 0%, #05d4c7 100%);
        }
        .chart-label {
            color: #8b95a7;
            font-size: 12px;
        }
        .list-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #353f52 0%, #252d3d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8b95a7;
            font-weight: 600;
            font-size: 16px;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
        }
        .user-meta {
            color: #8b95a7;
            font-size: 12px;
            margin-top: 2px;
        }
        .rank-badge {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        .rank-1 { background: #f59e0b; color: #fff; }
        .rank-2 { background: #8b95a7; color: #fff; }
        .rank-3 { background: #d97706; color: #fff; }
        .rank-other { background: #353f52; color: #8b95a7; }
        .movie-poster {
            width: 36px;
            height: 50px;
            background: #353f52;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .movie-info {
            flex: 1;
        }
        .movie-title {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
        }
        .movie-count {
            color: #8b95a7;
            font-size: 12px;
            margin-top: 2px;
        }
        .feedback-content {
            flex: 1;
        }
        .feedback-user {
            color: #05d4c7;
            font-size: 13px;
            font-weight: 500;
        }
        .feedback-text {
            color: #8b95a7;
            font-size: 13px;
            margin-top: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .feedback-time {
            color: #5a6478;
            font-size: 12px;
        }
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            .mobile-menu-btn {
                display: block;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .top-bar {
                flex-wrap: wrap;
                gap: 16px;
            }
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }
        .sidebar-overlay.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">J</div>
                <div>
                    <div class="logo-text">Jay影视</div>
                    <div class="logo-sub">管理系统</div>
                </div>
            </div>
        </div>
        <nav>
            <a href="index.php" class="nav-item active">
                <span class="nav-icon">📊</span>
                <span>仪表盘</span>
            </a>
            <a href="users.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span>用户管理</span>
            </a>
            <a href="sources.php" class="nav-item">
                <span class="nav-icon">🎬</span>
                <span>播放源管理</span>
            </a>
            <a href="announcements.php" class="nav-item">
                <span class="nav-icon">📢</span>
                <span>公告管理</span>
            </a>
            <a href="feedback.php" class="nav-item">
                <span class="nav-icon">💬</span>
                <span>反馈管理</span>
            </a>
            <a href="email.php" class="nav-item">
                <span class="nav-icon">📧</span>
                <span>邮件通知</span>
            </a>
            <a href="theme.php" class="nav-item">
                <span class="nav-icon">🎨</span>
                <span>主题设置</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title">仪表盘</h1>
                <p class="page-subtitle">欢迎回来，<?php echo $_SESSION['admin_username']; ?>！</p>
            </div>
            <div class="top-bar-actions">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
                <div class="admin-info">
                    <div class="admin-avatar">A</div>
                    <div>
                        <div class="admin-name"><?php echo $_SESSION['admin_username']; ?></div>
                        <div class="admin-role">超级管理员</div>
                    </div>
                </div>
                <a href="?logout=1" class="logout-btn">退出</a>
            </div>
        </div>

        <div class="stats-grid">
            <?php foreach ($statCards as $card): ?>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon" style="background: <?php echo $card['color']; ?>20; color: <?php echo $card['color']; ?>;">
                        <?php echo $card['icon']; ?>
                    </div>
                    <div class="stat-change"><?php echo $card['change']; ?></div>
                </div>
                <div class="stat-value"><?php echo $card['value']; ?></div>
                <div class="stat-label"><?php echo $card['label']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="content-grid">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">每日注册统计</h3>
                        <span style="color:#8b95a7; font-size:13px;">本周</span>
                    </div>
                    <div class="chart-container">
                        <?php foreach ($dailyRegistrations as $data): ?>
                        <div class="chart-bar-wrapper">
                            <div style="font-size:11px; color:#05d4c7; margin-bottom:4px;"><?php echo $data['count']; ?></div>
                            <div class="chart-bar" style="height: <?php echo ($data['count'] / $maxRegistration) * 120; ?>px;"></div>
                            <div class="chart-label"><?php echo $data['day']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">最新反馈</h3>
                        <a href="feedback.php" class="card-link">查看全部</a>
                    </div>
                    <?php foreach ($latestFeedbacks as $fb): ?>
                    <div class="list-item">
                        <div class="feedback-content">
                            <div class="feedback-user"><?php echo $fb['user']; ?></div>
                            <div class="feedback-text"><?php echo htmlspecialchars($fb['content']); ?></div>
                        </div>
                        <div class="feedback-time"><?php echo $fb['time']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">最新注册用户</h3>
                        <a href="users.php" class="card-link">查看全部</a>
                    </div>
                    <?php foreach ($latestUsers as $user): ?>
                    <div class="list-item">
                        <div class="user-avatar"><?php echo $user['avatar']; ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $user['name']; ?></div>
                            <div class="user-meta"><?php echo $user['email']; ?></div>
                        </div>
                        <div class="feedback-time"><?php echo $user['time']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">观看历史 TOP 5</h3>
                    </div>
                    <?php foreach ($topWatched as $item): ?>
                    <div class="list-item">
                        <div class="rank-badge <?php echo $item['rank'] <= 3 ? 'rank-' . $item['rank'] : 'rank-other'; ?>">
                            <?php echo $item['rank']; ?>
                        </div>
                        <div class="movie-poster"><?php echo $item['poster']; ?></div>
                        <div class="movie-info">
                            <div class="movie-title"><?php echo $item['title']; ?></div>
                            <div class="movie-count"><?php echo $item['count']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">收藏 TOP 5</h3>
                    </div>
                    <?php foreach ($topFavorites as $item): ?>
                    <div class="list-item">
                        <div class="rank-badge <?php echo $item['rank'] <= 3 ? 'rank-' . $item['rank'] : 'rank-other'; ?>">
                            <?php echo $item['rank']; ?>
                        </div>
                        <div class="movie-poster"><?php echo $item['poster']; ?></div>
                        <div class="movie-info">
                            <div class="movie-title"><?php echo $item['title']; ?></div>
                            <div class="movie-count"><?php echo $item['count']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);
    </script>
</body>
</html>
