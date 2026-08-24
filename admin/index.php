<?php
$adminActivePage = 'dashboard';
$adminTitle = '仪表盘';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

$totalUsers = $db->fetchOne("SELECT COUNT(*) c FROM users WHERE is_admin = 0")['c'];
$bannedUsers = $db->fetchOne("SELECT COUNT(*) c FROM users WHERE status = 0")['c'];
$totalFavorites = $db->fetchOne("SELECT COUNT(*) c FROM favorites")['c'];
$totalHistory = $db->fetchOne("SELECT COUNT(*) c FROM watch_history")['c'];
$totalFeedback = $db->fetchOne("SELECT COUNT(*) c FROM feedback")['c'];
$pendingFeedback = $db->fetchOne("SELECT COUNT(*) c FROM feedback WHERE status = 0")['c'];
$totalPlayTime = $db->fetchOne("SELECT COALESCE(SUM(watch_seconds),0) t FROM watch_history")['t'];

$recentUsers = $db->fetchAll("SELECT * FROM users ORDER BY id DESC LIMIT 6");
$recentFeedback = $db->fetchAll("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON u.id=f.user_id ORDER BY f.id DESC LIMIT 5");
$recentHistory = $db->fetchAll("SELECT w.*, u.username FROM watch_history w LEFT JOIN users u ON u.id=w.user_id ORDER BY w.last_watch_time DESC LIMIT 6");
$recentFavorites = $db->fetchAll("SELECT f.*, u.username FROM favorites f LEFT JOIN users u ON u.id=f.user_id ORDER BY f.id DESC LIMIT 6");

showAlert();
?>

<div class="stats-grid">
    <div class="stat-card" style="--stat-accent: linear-gradient(135deg, #7c3aed, #2563eb); --stat-bg: rgba(124,58,237,0.18); --stat-color: #a78bfa;">
        <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="stat-label">总注册用户</div>
        <div class="stat-value"><?php echo intval($totalUsers); ?></div>
    </div>
    <div class="stat-card" style="--stat-accent: linear-gradient(135deg, #ef4444, #dc2626); --stat-bg: rgba(239,68,68,0.15); --stat-color: #fca5a5;">
        <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM9 5h6v2H9z"/><circle cx="12" cy="14" r="2"/></svg></div>
        <div class="stat-label">封禁用户</div>
        <div class="stat-value"><?php echo intval($bannedUsers); ?></div>
    </div>
    <div class="stat-card" style="--stat-accent: linear-gradient(135deg, #f472b6, #7c3aed); --stat-bg: rgba(244,114,182,0.15); --stat-color: #f9a8d4;">
        <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></div>
        <div class="stat-label">总收藏数</div>
        <div class="stat-value"><?php echo intval($totalFavorites); ?></div>
    </div>
    <div class="stat-card" style="--stat-accent: linear-gradient(135deg, #10b981, #059669); --stat-bg: rgba(16,185,129,0.15); --stat-color: #6ee7b7;">
        <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg></div>
        <div class="stat-label">观看历史</div>
        <div class="stat-value"><?php echo intval($totalHistory); ?></div>
    </div>
    <div class="stat-card" style="--stat-accent: linear-gradient(135deg, #f59e0b, #ea580c); --stat-bg: rgba(245,158,11,0.15); --stat-color: #fcd34d;">
        <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <div class="stat-label">反馈/待处理</div>
        <div class="stat-value"><?php echo intval($totalFeedback); ?> / <?php echo intval($pendingFeedback); ?></div>
    </div>
    <div class="stat-card" style="--stat-accent: linear-gradient(135deg, #3b82f6, #1e40af); --stat-bg: rgba(59,130,246,0.15); --stat-color: #93c5fd;">
        <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg></div>
        <div class="stat-label">累计观看时长</div>
        <div class="stat-value" style="font-size:24px;"><?php echo formatDuration(intval($totalPlayTime)); ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/><path d="M19 8v6"/></svg>
                最新注册用户
            </div>
            <a href="users.php" class="section-more">查看全部</a>
        </div>
        <?php if (empty($recentUsers)): ?>
            <div class="empty-state" style="padding: 30px 20px;"><div class="empty-state-title" style="font-size:16px;">暂无用户</div></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>用户</th><th>邮箱</th><th>状态</th><th>注册时间</th></tr></thead>
            <tbody>
                <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap: 10px;">
                            <img src="<?php echo e(getUserAvatar($u)); ?>" style="width:34px;height:34px;border-radius:50%; object-fit:cover;">
                            <strong><?php echo e($u['username']); ?></strong>
                            <?php if (!empty($u['is_admin'])): ?><span class="admin-badge">开发者</span><?php endif; ?>
                        </div>
                    </td>
                    <td><?php echo e($u['email']); ?></td>
                    <td>
                        <?php if ($u['status'] == 1): ?>
                            <span class="badge badge-success">正常</span>
                        <?php else: ?>
                            <span class="badge badge-danger">封禁中</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted); font-size:13px;"><?php echo e(date('m-d H:i', strtotime($u['created_at']))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                最新反馈
            </div>
            <a href="feedback.php" class="section-more">查看全部</a>
        </div>
        <?php if (empty($recentFeedback)): ?>
            <div class="empty-state" style="padding: 30px 20px;"><div class="empty-state-title" style="font-size:16px;">暂无反馈</div></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>用户</th><th>标题</th><th>状态</th><th>时间</th></tr></thead>
            <tbody>
                <?php foreach ($recentFeedback as $f): ?>
                <tr>
                    <td><strong><?php echo e($f['username']); ?></strong></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo e($f['title']); ?></td>
                    <td><?php echo $f['status'] == 1 ? '<span class="badge badge-success">已处理</span>' : '<span class="badge badge-warning">待处理</span>'; ?></td>
                    <td style="color:var(--text-muted); font-size:13px;"><?php echo e(formatTimeAgo($f['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
            最新观看历史
        </div>
        <a href="watch_history.php" class="section-more">查看全部</a>
    </div>
    <?php if (empty($recentHistory)): ?>
        <div class="empty-state" style="padding: 30px 20px;"><div class="empty-state-title" style="font-size:16px;">暂无记录</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>用户</th><th>影视</th><th>类型</th><th>集数</th><th>观看时长</th><th>时间</th></tr></thead>
        <tbody>
            <?php foreach ($recentHistory as $h): ?>
            <tr>
                <td><strong><?php echo e($h['username']); ?></strong></td>
                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo e($h['media_title']); ?></td>
                <td><span class="badge badge-info"><?php echo e(array('movie'=>'电影','tv'=>'剧集','anime'=>'动漫','variety'=>'综艺')[$h['media_type']] ?? $h['media_type']); ?></span></td>
                <td><?php echo $h['media_type'] === 'movie' ? '完整' : '第' . $h['episode'] . '集'; ?></td>
                <td><?php echo formatDuration($h['watch_seconds']); ?></td>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(formatTimeAgo($h['last_watch_time'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            最新收藏
        </div>
        <a href="favorites.php" class="section-more">查看全部</a>
    </div>
    <?php if (empty($recentFavorites)): ?>
        <div class="empty-state" style="padding: 30px 20px;"><div class="empty-state-title" style="font-size:16px;">暂无记录</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>用户</th><th>影视</th><th>类型</th><th>收藏时间</th></tr></thead>
        <tbody>
            <?php foreach ($recentFavorites as $f): ?>
            <tr>
                <td><strong><?php echo e($f['username']); ?></strong></td>
                <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo e($f['media_title']); ?></td>
                <td><span class="badge badge-info"><?php echo e(array('movie'=>'电影','tv'=>'剧集','anime'=>'动漫','variety'=>'综艺')[$f['media_type']] ?? $f['media_type']); ?></span></td>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(formatTimeAgo($f['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
