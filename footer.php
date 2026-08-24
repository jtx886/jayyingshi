<?php
// 底部导航（移动端）
?>
<div class="mobile-nav">
    <div class="mobile-nav-inner">
        <a href="index.php" class="mobile-nav-item <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
            <span class="mobile-nav-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </span>
            首页
        </a>
        <a href="search.php" class="mobile-nav-item <?php echo $currentPage == 'search.php' ? 'active' : ''; ?>">
            <span class="mobile-nav-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            搜索
        </a>
        <?php if ($currentUser): ?>
            <a href="profile.php" class="mobile-nav-item <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
                <span class="mobile-nav-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                我的
            </a>
        <?php else: ?>
            <a href="login.php" class="mobile-nav-item">
                <span class="mobile-nav-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                我的
            </a>
        <?php endif; ?>
    </div>
</div>

<footer style="padding: 40px 20px; text-align:center; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-color); margin-top: 40px;">
    <div style="margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;">
        <span class="logo-icon" style="width:30px;height:30px;"></span>
        <span class="logo-text" style="font-size:20px;font-weight:900;">Jay影视</span>
    </div>
    <p style="margin-bottom: 6px;">© <?php echo date('Y'); ?> Jay影视 - 发现世界美好影像</p>
    <p>本站内容来源于互联网公开分享接口，仅供学习交流使用，版权归原作者所有</p>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
