<?php
require_once __DIR__ . '/includes/auth.php';
Auth::requireLogin();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '个人中心';
$user = Auth::getCurrentUser();
include __DIR__ . '/header.php';

$activeTab = $_GET['tab'] ?? 'profile';
$db = Database::getInstance();

// 统计数据
$favCount = $db->fetchOne("SELECT COUNT(*) as c FROM favorites WHERE user_id = ?", array($user['id']))['c'];
$historyCount = $db->fetchOne("SELECT COUNT(*) as c FROM watch_history WHERE user_id = ?", array($user['id']))['c'];
$totalSeconds = $db->fetchOne("SELECT COALESCE(SUM(watch_seconds),0) as t FROM watch_history WHERE user_id = ?", array($user['id']))['t'];

// 收藏列表
$favorites = $db->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC LIMIT 100", array($user['id']));
// 观看历史
$history = $db->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY last_watch_time DESC LIMIT 100", array($user['id']));
?>

<div class="container" style="padding-top: 10px;">
    <div class="profile-header">
        <div class="profile-avatar-wrap">
            <img src="<?php echo e(getUserAvatar($user)); ?>" class="profile-avatar" id="profileAvatar">
            <label class="avatar-upload-label" for="avatarInput" title="更换头像">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </label>
            <input type="file" id="avatarInput" accept="image/*" style="display:none;">
        </div>
        <div class="profile-info">
            <h2>
                <?php echo e($user['username']); ?>
                <?php if (!empty($user['is_admin'])): ?>
                    <span class="admin-badge" style="font-size: 12px; padding: 3px 10px;">开发者</span>
                <?php endif; ?>
            </h2>
            <p>📧 <?php echo e($user['email']); ?></p>
            <p style="margin-top:6px;">📅 加入于 <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?></p>
            <?php if ($user['status'] == 0): ?>
                <p style="color:#fca5a5; font-weight:600; margin-top:6px;">⚠️ 当前账号已被封禁，解封时间：<?php echo e($user['ban_end_time'] ?? '永久'); ?></p>
            <?php endif; ?>
        </div>
        <div class="profile-stats">
            <div class="profile-stat">
                <div class="profile-stat-num"><?php echo intval($favCount); ?></div>
                <div class="profile-stat-label">收藏</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-num"><?php echo intval($historyCount); ?></div>
                <div class="profile-stat-label">观看</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-num" style="font-size: 24px;"><?php echo formatDuration(intval($totalSeconds)); ?></div>
                <div class="profile-stat-label">总时长</div>
            </div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab-btn <?php echo $activeTab == 'profile' ? 'active' : ''; ?>" data-tab="tab-profile">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:6px; vertical-align: -2px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            个人资料
        </button>
        <button class="tab-btn <?php echo $activeTab == 'favorites' ? 'active' : ''; ?>" data-tab="tab-favorites">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; margin-right:6px; vertical-align: -2px;"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            我的收藏 (<?php echo intval($favCount); ?>)
        </button>
        <button class="tab-btn <?php echo $activeTab == 'history' ? 'active' : ''; ?>" data-tab="tab-history">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:6px; vertical-align: -2px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
            观看历史 (<?php echo intval($historyCount); ?>)
        </button>
    </div>

    <!-- 个人资料 -->
    <div class="tab-pane <?php echo $activeTab == 'profile' ? 'active' : ''; ?>" id="tab-profile">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    个人信息
                </div>
            </div>
            <form method="POST" action="api/update_profile.php" style="max-width: 600px;">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-input" value="<?php echo e($user['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">邮箱</label>
                    <input type="email" class="form-input" value="<?php echo e($user['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">新密码（留空则不修改）</label>
                    <input type="password" name="password" class="form-input" placeholder="至少6位">
                </div>
                <div class="form-group">
                    <label class="form-label">确认新密码</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="再次输入新密码">
                </div>
                <button type="submit" class="btn btn-primary">保存修改</button>
            </form>
        </div>
    </div>

    <!-- 收藏 -->
    <div class="tab-pane <?php echo $activeTab == 'favorites' ? 'active' : ''; ?>" id="tab-favorites">
        <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                </div>
                <div class="empty-state-title">还没有收藏任何影视</div>
                <div class="empty-state-desc">去首页逛逛看到喜欢的收藏起来吧~</div>
                <a href="index.php" class="btn btn-primary">去逛逛</a>
            </div>
        <?php else: ?>
            <?php foreach ($favorites as $fav): ?>
                <div class="media-list-item">
                    <div class="media-list-poster" onclick="window.location='detail.php?id=<?php echo $fav['media_id']; ?>&type=<?php echo e($fav['media_type']); ?>'">
                        <?php if (!empty($fav['media_poster'])): ?>
                            <img src="<?php echo e($fav['media_poster']); ?>" alt="<?php echo e($fav['media_title']); ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);">无图</div>
                        <?php endif; ?>
                    </div>
                    <div class="media-list-info">
                        <div class="media-list-title" onclick="window.location='detail.php?id=<?php echo $fav['media_id']; ?>&type=<?php echo e($fav['media_type']); ?>'"><?php echo e($fav['media_title']); ?></div>
                        <div class="media-list-meta">
                            类型：<?php echo e(array('movie'=>'电影','tv'=>'电视剧','anime'=>'动漫','variety'=>'综艺')[$fav['media_type']] ?? '影视'); ?>
                            <span style="margin-left:16px;">收藏于：<?php echo e(date('Y-m-d H:i', strtotime($fav['created_at']))); ?></span>
                        </div>
                        <a href="player.php?id=<?php echo $fav['media_id']; ?>&type=<?php echo e($fav['media_type']); ?>" class="btn btn-primary btn-sm" style="margin-top: 8px;">立即播放</a>
                    </div>
                    <div class="media-list-actions">
                        <button class="icon-btn" data-delete="favorite" data-id="<?php echo $fav['id']; ?>" title="删除收藏">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 观看历史 -->
    <div class="tab-pane <?php echo $activeTab == 'history' ? 'active' : ''; ?>" id="tab-history">
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </div>
                <div class="empty-state-title">还没有观看记录</div>
                <div class="empty-state-desc">去挑一部精彩的影片来看看吧~</div>
                <a href="index.php" class="btn btn-primary">去看看</a>
            </div>
        <?php else: ?>
            <?php foreach ($history as $h):
                $progress = 0;
                $epLabel = '';
                if ($h['media_type'] !== 'movie') {
                    $epLabel = ' 第' . $h['episode'] . '集';
                }
            ?>
                <div class="media-list-item">
                    <div class="media-list-poster" onclick="window.location='detail.php?id=<?php echo $h['media_id']; ?>&type=<?php echo e($h['media_type']); ?>'">
                        <?php if (!empty($h['media_poster'])): ?>
                            <img src="<?php echo e($h['media_poster']); ?>" alt="<?php echo e($h['media_title']); ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);">无图</div>
                        <?php endif; ?>
                    </div>
                    <div class="media-list-info">
                        <div class="media-list-title" onclick="window.location='player.php?id=<?php echo $h['media_id']; ?>&type=<?php echo e($h['media_type']); ?>&episode=<?php echo $h['episode']; ?>'"><?php echo e($h['media_title']); ?><?php echo e($epLabel); ?></div>
                        <div class="media-list-meta">
                            <?php echo e(array('movie'=>'电影','tv'=>'电视剧','anime'=>'动漫','variety'=>'综艺')[$h['media_type']] ?? '影视'); ?>
                            <span style="margin-left:16px;">已观看：<?php echo formatDuration($h['watch_seconds']); ?></span>
                            <span style="margin-left:16px;">上次：<?php echo e(formatTimeAgo($h['last_watch_time'])); ?></span>
                        </div>
                        <div class="watch-progress">
                            <div class="watch-progress-bar" style="width: <?php echo min(100, $progress ?: 5); ?>%;"></div>
                        </div>
                        <a href="player.php?id=<?php echo $h['media_id']; ?>&type=<?php echo e($h['media_type']); ?>&episode=<?php echo $h['episode']; ?>" class="btn btn-primary btn-sm" style="margin-top: 4px;">继续观看</a>
                    </div>
                    <div class="media-list-actions">
                        <button class="icon-btn" data-delete="history" data-id="<?php echo $h['id']; ?>" title="删除历史">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
