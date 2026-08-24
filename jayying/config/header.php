<?php
$base_path = dirname(__DIR__);
$config_path = $base_path . '/config/config.php';
$common_path = $base_path . '/config/common.php';

if (file_exists($config_path)) {
    require_once $config_path;
}
if (file_exists($common_path)) {
    require_once $common_path;
}

$theme_color = '#05d4c7';
$site_name = 'JayYing';
$site_url = isset($site_url) ? $site_url : '';
$user_logged_in = false;
$user_info = null;

if (function_exists('is_user_logged_in')) {
    $user_logged_in = is_user_logged_in();
}
if (function_exists('get_current_user')) {
    $user_info = get_current_user();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . $site_name : $site_name; ?></title>
<style>
:root {
    --primary: <?php echo $theme_color; ?>;
    --primary-hover: #04b8ab;
    --primary-glow: rgba(5, 212, 199, 0.3);
    --bg-dark: #0b1019;
    --bg-card: #161f2e;
    --bg-card-hover: #1c2738;
    --bg-secondary: #111827;
    --border-color: #1e293b;
    --text-primary: #ffffff;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
    --shadow-hover: 0 8px 32px rgba(0, 0, 0, 0.5);
    --radius: 12px;
    --radius-sm: 8px;
    --nav-height: 64px;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
    background: var(--bg-dark);
    color: var(--text-primary);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

a { color: inherit; text-decoration: none; }
img { max-width: 100%; display: block; }
button { cursor: pointer; border: none; background: none; font-family: inherit; color: inherit; }

.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--nav-height);
    background: rgba(11, 16, 25, 0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border-color);
    z-index: 1000;
    transition: background 0.3s;
}

.navbar.scrolled {
    background: rgba(11, 16, 25, 0.98);
}

.nav-container {
    max-width: 1400px;
    height: 100%;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
}

.nav-left {
    display: flex;
    align-items: center;
    gap: 40px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 700;
    color: var(--primary);
    letter-spacing: -0.5px;
}

.logo-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--primary) 0%, #0ea5e9 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px var(--primary-glow);
}

.logo-icon::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2.5px solid #fff;
    border-radius: 4px;
    transform: rotate(45deg);
}

.logo-icon::after {
    content: '';
    position: absolute;
    width: 8px;
    height: 8px;
    background: #fff;
    border-radius: 50%;
}

.logo-text {
    background: linear-gradient(135deg, var(--primary) 0%, #0ea5e9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
}

.nav-link {
    position: relative;
    padding: 8px 16px;
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 500;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
    white-space: nowrap;
}

.nav-link:hover,
.nav-link.active {
    color: var(--text-primary);
    background: rgba(255, 255, 255, 0.05);
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 3px;
    background: var(--primary);
    border-radius: 2px;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box input {
    width: 240px;
    height: 40px;
    padding: 0 16px 0 44px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
    outline: none;
}

.search-box input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-glow);
}

.search-box input::placeholder {
    color: var(--text-muted);
}

.search-box .search-icon {
    position: absolute;
    left: 14px;
    width: 18px;
    height: 18px;
    color: var(--text-muted);
}

.nav-auth {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-login, .btn-register {
    padding: 8px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-login {
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-login:hover {
    color: var(--primary);
    border-color: var(--primary);
}

.btn-register {
    background: var(--primary);
    color: #0b1019;
    font-weight: 600;
}

.btn-register:hover {
    background: var(--primary-hover);
    box-shadow: 0 4px 16px var(--primary-glow);
}

.user-dropdown {
    position: relative;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #0ea5e9);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0b1019;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: transform 0.2s;
}

.user-avatar:hover {
    transform: scale(1.05);
}

.user-menu {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 200px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 8px;
    box-shadow: var(--shadow-hover);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s ease;
}

.user-dropdown:hover .user-menu,
.user-dropdown.open .user-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    color: var(--text-secondary);
    transition: all 0.15s;
}

.user-menu-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
}

.user-menu-divider {
    height: 1px;
    background: var(--border-color);
    margin: 6px 0;
}

.hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    gap: 5px;
    border-radius: var(--radius-sm);
    transition: background 0.2s;
}

.hamburger:hover {
    background: rgba(255, 255, 255, 0.05);
}

.hamburger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--text-primary);
    border-radius: 2px;
    transition: all 0.3s ease;
}

.hamburger.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

.mobile-menu {
    position: fixed;
    top: var(--nav-height);
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--bg-dark);
    z-index: 999;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
    padding: 24px;
}

.mobile-menu.active {
    transform: translateX(0);
}

.mobile-menu-header {
    margin-bottom: 24px;
}

.mobile-search input {
    width: 100%;
    height: 44px;
    padding: 0 16px 0 44px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 15px;
    outline: none;
}

.mobile-search input:focus {
    border-color: var(--primary);
}

.mobile-search {
    position: relative;
}

.mobile-search .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--text-muted);
}

.mobile-nav-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mobile-nav-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-radius: var(--radius-sm);
    font-size: 16px;
    color: var(--text-secondary);
    transition: all 0.2s;
}

.mobile-nav-link:hover,
.mobile-nav-link.active {
    background: var(--bg-card);
    color: var(--text-primary);
}

.mobile-nav-link .arrow {
    width: 16px;
    height: 16px;
    color: var(--text-muted);
}

.mobile-auth {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 12px;
}

.mobile-auth .btn-login,
.mobile-auth .btn-register {
    flex: 1;
    text-align: center;
    padding: 12px;
}

.main-content {
    padding-top: var(--nav-height);
    min-height: 100vh;
}

.announcement-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
    padding: 20px;
}

.announcement-modal.active {
    opacity: 1;
    visibility: visible;
}

.announcement-modal-content {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    max-width: 520px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: var(--shadow-hover);
    transform: scale(0.95);
    transition: transform 0.25s ease;
}

.announcement-modal.active .announcement-modal-content {
    transform: scale(1);
}

.announcement-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.announcement-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.announcement-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: all 0.2s;
}

.announcement-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.announcement-body {
    padding: 24px;
    color: var(--text-secondary);
    font-size: 15px;
    line-height: 1.7;
    white-space: pre-wrap;
}

@media (max-width: 1024px) {
    .nav-menu { display: none; }
    .search-box input { width: 180px; }
}

@media (max-width: 768px) {
    .nav-container { padding: 0 16px; gap: 16px; }
    .search-box { display: none; }
    .btn-login { display: none; }
    .nav-left { gap: 12px; }
    .logo { font-size: 18px; }
    .logo-icon { width: 32px; height: 32px; }
    .hamburger { display: flex; }
}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <a href="<?php echo $site_url; ?>/" class="logo">
                <span class="logo-icon"></span>
                <span class="logo-text"><?php echo $site_name; ?></span>
            </a>
            <ul class="nav-menu">
                <li><a href="<?php echo $site_url; ?>/" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">首页</a></li>
                <li><a href="<?php echo $site_url; ?>/movies" class="nav-link">电影</a></li>
                <li><a href="<?php echo $site_url; ?>/tv" class="nav-link">电视剧</a></li>
                <li><a href="<?php echo $site_url; ?>/variety" class="nav-link">综艺</a></li>
                <li><a href="<?php echo $site_url; ?>/feedback" class="nav-link">反馈</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <div class="search-box">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="navSearch" placeholder="搜索电影、电视剧...">
            </div>
            <div class="nav-auth">
                <?php if ($user_logged_in && $user_info): ?>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-avatar" id="userAvatar"><?php echo strtoupper(mb_substr($user_info['username'], 0, 1)); ?></div>
                    <div class="user-menu">
                        <a href="<?php echo $site_url; ?>/user/profile" class="user-menu-item">个人中心</a>
                        <a href="<?php echo $site_url; ?>/user/favorites" class="user-menu-item">我的收藏</a>
                        <a href="<?php echo $site_url; ?>/user/history" class="user-menu-item">观看历史</a>
                        <div class="user-menu-divider"></div>
                        <a href="<?php echo $site_url; ?>/logout" class="user-menu-item">退出登录</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?php echo $site_url; ?>/login" class="btn-login">登录</a>
                <a href="<?php echo $site_url; ?>/register" class="btn-register">注册</a>
                <?php endif; ?>
            </div>
            <button class="hamburger" id="hamburger" aria-label="菜单">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <div class="mobile-search">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="mobileSearch" placeholder="搜索电影、电视剧...">
        </div>
    </div>
    <ul class="mobile-nav-list">
        <li><a href="<?php echo $site_url; ?>/" class="mobile-nav-link">首页 <span class="arrow">›</span></a></li>
        <li><a href="<?php echo $site_url; ?>/movies" class="mobile-nav-link">电影 <span class="arrow">›</span></a></li>
        <li><a href="<?php echo $site_url; ?>/tv" class="mobile-nav-link">电视剧 <span class="arrow">›</span></a></li>
        <li><a href="<?php echo $site_url; ?>/variety" class="mobile-nav-link">综艺 <span class="arrow">›</span></a></li>
        <li><a href="<?php echo $site_url; ?>/feedback" class="mobile-nav-link">反馈 <span class="arrow">›</span></a></li>
    </ul>
    <?php if (!$user_logged_in): ?>
    <div class="mobile-auth">
        <a href="<?php echo $site_url; ?>/login" class="btn-login">登录</a>
        <a href="<?php echo $site_url; ?>/register" class="btn-register">注册</a>
    </div>
    <?php endif; ?>
</div>

<div class="announcement-modal" id="announcementModal">
    <div class="announcement-modal-content">
        <div class="announcement-header">
            <h3 class="announcement-title">📢 站点公告</h3>
            <button class="announcement-close" id="announcementClose" aria-label="关闭">✕</button>
        </div>
        <div class="announcement-body" id="announcementBody">
            加载中...
        </div>
    </div>
</div>

<script>
(function() {
    var navbar = document.getElementById('navbar');
    var hamburger = document.getElementById('hamburger');
    var mobileMenu = document.getElementById('mobileMenu');

    window.addEventListener('scroll', function() {
        if (window.scrollY > 20) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    hamburger.addEventListener('click', function() {
        hamburger.classList.toggle('active');
        mobileMenu.classList.toggle('active');
    });

    function handleSearch(inputId) {
        var input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && input.value.trim()) {
                window.location.href = '<?php echo $site_url; ?>/search?q=' + encodeURIComponent(input.value.trim());
            }
        });
    }

    handleSearch('navSearch');
    handleSearch('mobileSearch');

    var userAvatar = document.getElementById('userAvatar');
    var userDropdown = document.getElementById('userDropdown');
    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });
    }

    var announcementModal = document.getElementById('announcementModal');
    var announcementClose = document.getElementById('announcementClose');
    var announcementBody = document.getElementById('announcementBody');

    function loadAnnouncement() {
        fetch('/api/announcement.php')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.content) {
                    announcementBody.textContent = data.content;
                    announcementModal.classList.add('active');
                } else {
                    announcementModal.style.display = 'none';
                }
            })
            .catch(function() {
                announcementModal.style.display = 'none';
            });
    }

    announcementClose.addEventListener('click', function() {
        announcementModal.classList.remove('active');
    });

    announcementModal.addEventListener('click', function(e) {
        if (e.target === announcementModal) {
            announcementModal.classList.remove('active');
        }
    });

    loadAnnouncement();
})();
</script>
