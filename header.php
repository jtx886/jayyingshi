<?php
require_once dirname(__FILE__) . '/includes/functions.php';
require_once dirname(__FILE__) . '/includes/settings.php';
$themeColor = getThemeColor();
$currentUser = null;
if (Auth::isLoggedIn()) {
    $currentUser = Auth::getCurrentUser();
}
$currentPage = basename($_SERVER['PHP_SELF']);
$siteName = SiteSetting::get('site_name', 'Jay影视');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="<?php echo e($themeColor); ?>">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?><?php echo e($siteName); ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    :root {
        --theme-color: <?php echo e($themeColor); ?>;
    }
</style>
</head>
<body data-theme="<?php echo e($themeColor); ?>">

<nav class="navbar">
    <div class="container">
        <div class="navbar-inner">
            <a href="index.php" class="logo">
                <span class="logo-icon"></span>
                <span class="logo-text">Jay影视</span>
            </a>

            <ul class="nav-menu">
                <li><a href="index.php" class="<?php echo in_array($currentPage, ['index.php']) ? 'active' : ''; ?>">首页</a></li>
                <li><a href="category.php?type=movie" class="<?php echo ($currentPage == 'category.php' && ($_GET['type'] ?? '') == 'movie') ? 'active' : ''; ?>">电影</a></li>
                <li><a href="category.php?type=tv" class="<?php echo ($currentPage == 'category.php' && ($_GET['type'] ?? '') == 'tv') ? 'active' : ''; ?>">电视剧</a></li>
                <li><a href="category.php?type=anime" class="<?php echo ($currentPage == 'category.php' && ($_GET['type'] ?? '') == 'anime') ? 'active' : ''; ?>">动漫</a></li>
                <li><a href="category.php?type=variety" class="<?php echo ($currentPage == 'category.php' && ($_GET['type'] ?? '') == 'variety') ? 'active' : ''; ?>">综艺</a></li>
                <li><a href="feedback.php" class="<?php echo $currentPage == 'feedback.php' ? 'active' : ''; ?>">反馈</a></li>
            </ul>

            <form id="searchForm" class="search-box">
                <input type="text" name="q" class="search-input" placeholder="搜索电影、电视剧、动漫...">
                <button type="submit" class="search-btn" aria-label="搜索">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </form>

            <div class="nav-right">
                <?php if ($currentUser): ?>
                    <div class="user-menu">
                        <img src="<?php echo e(getUserAvatar($currentUser)); ?>" class="user-avatar" alt="">
                        <div class="dropdown-menu">
                            <a href="profile.php" style="color:var(--text-primary); font-weight:700;">
                                <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span><?php echo e($currentUser['username']); ?></span>
                                    <?php if (!empty($currentUser['is_admin'])): ?>
                                        <span class="admin-badge">开发者</span>
                                    <?php endif; ?>
                                </span>
                            </a>
                            <a href="profile.php?tab=favorites">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                我的收藏
                            </a>
                            <a href="profile.php?tab=history">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                                观看历史
                            </a>
                            <a href="profile.php">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                个人中心
                            </a>
                            <?php if (!empty($currentUser['is_admin'])): ?>
                                <a href="admin/" style="color:#fca5a5;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V6z"/></svg>
                                    管理后台
                                </a>
                            <?php endif; ?>
                            <form method="POST" action="api/logout.php" style="margin:0;">
                                <button type="submit" style="color:#fca5a5;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    退出登录
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">登录</a>
                    <a href="register.php" class="btn btn-primary btn-sm">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
