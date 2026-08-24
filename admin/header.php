<?php
require_once __DIR__ . '/../includes/auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../includes/settings.php';
$themeColor = getThemeColor();
$adminUser = Auth::getCurrentUser();
$activePage = $adminActivePage ?? 'dashboard';
$siteName = SiteSetting::get('site_name', 'Jay影视');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($adminTitle) ? e($adminTitle) . ' - ' : ''; ?>管理后台 - <?php echo e($siteName); ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    :root { --theme-color: <?php echo e($themeColor); ?>; }
    body { background: var(--bg-primary); }
</style>
</head>
<body data-theme="<?php echo e($themeColor); ?>">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <div class="admin-brand-icon">杰</div>
            <div>
                <div style="font-weight:900; font-size:17px; letter-spacing:-0.5px;">Jay影视</div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">管理控制台</div>
            </div>
        </div>

        <nav class="admin-nav">
            <a href="index.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                </span>
                仪表盘
            </a>
            <a href="users.php" class="<?php echo $activePage === 'users' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                用户管理
            </a>
            <a href="sources.php" class="<?php echo $activePage === 'sources' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                播放源管理
            </a>
            <a href="watch_history.php" class="<?php echo $activePage === 'history' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </span>
                观看历史
            </a>
            <a href="favorites.php" class="<?php echo $activePage === 'favorites' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                </span>
                用户收藏
            </a>
            <a href="feedback.php" class="<?php echo $activePage === 'feedback' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </span>
                反馈管理
            </a>
            <a href="announcements.php" class="<?php echo $activePage === 'announcements' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                公告管理
            </a>
            <a href="emails.php" class="<?php echo $activePage === 'emails' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                邮件通知
            </a>
            <a href="theme.php" class="<?php echo $activePage === 'theme' ? 'active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                </span>
                主题设置
            </a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1 class="admin-title"><?php echo e($adminTitle ?? '仪表盘'); ?></h1>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap: wrap;">
                <a href="../index.php" class="btn btn-outline btn-sm" target="_blank">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="8" height="6" x="8" y="2" rx="1" ry="1"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                    查看站点
                </a>
                <div class="user-menu">
                    <div style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <img src="<?php echo e(getUserAvatar($adminUser)); ?>" class="user-avatar" alt="">
                        <div style="line-height: 1.1;">
                            <div style="font-weight:700; font-size:14px; display:flex; align-items:center; gap:6px;">
                                <?php echo e($adminUser['username']); ?>
                                <span class="admin-badge">开发者</span>
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:3px;">超级管理员</div>
                        </div>
                    </div>
                    <div class="dropdown-menu">
                        <a href="../profile.php">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            个人中心
                        </a>
                        <form method="POST" action="../api/logout.php" style="margin:0;">
                            <button type="submit" style="color:#fca5a5;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                退出登录
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<?php
function showAlert() {
    if (!empty($_GET['msg'])) {
        $type = $_GET['t'] ?? 'info';
        $classMap = array('success' => 'auth-alert-success', 'error' => 'auth-alert-error', 'warning' => 'auth-alert-error', 'info' => 'auth-alert-info');
        $cls = $classMap[$type] ?? 'auth-alert-info';
        echo '<div class="auth-alert ' . $cls . '" style="margin-bottom:24px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>' . htmlspecialchars($_GET['msg']) . '</span>
        </div>';
    }
}
?>
