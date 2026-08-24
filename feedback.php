<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '意见反馈';
$user = null;
if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
}
include __DIR__ . '/header.php';

$db = Database::getInstance();
// 获取所有反馈及点赞回复数
$feedbacks = $db->fetchAll("
    SELECT f.*, u.username, u.avatar, u.is_admin as poster_is_admin,
           (SELECT COUNT(*) FROM feedback_likes fl WHERE fl.feedback_id = f.id) as like_count,
           (SELECT COUNT(*) FROM feedback_replies fr WHERE fr.feedback_id = f.id) as reply_count
    FROM feedback f 
    LEFT JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
    LIMIT 100
");

// 用户对哪些点赞过
$likedIds = array();
if ($user) {
    $liked = $db->fetchAll("SELECT feedback_id FROM feedback_likes WHERE user_id = ?", array($user['id']));
    foreach ($liked as $l) $likedIds[] = $l['feedback_id'];
}
?>
<div class="container" style="padding-top: 24px;">
    <div class="section-header" style="margin-bottom: 22px;">
        <h1 class="section-title" style="font-size:28px;">
            <span class="section-title-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            意见反馈
        </h1>
    </div>

    <div class="feedback-form-card">
        <div class="admin-card-title" style="font-size: 18px; font-weight:800; margin-bottom: 18px; display:flex; align-items:center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            发布反馈
        </div>
        <?php if (!$user): ?>
            <div class="auth-alert auth-alert-info">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>请先<a href="login.php" style="color:inherit;font-weight:700;text-decoration:underline;">登录</a>后发布反馈哦~</span>
            </div>
        <?php else: ?>
        <form id="feedbackForm">
            <div class="form-group">
                <label class="form-label">反馈标题</label>
                <input type="text" name="title" class="form-input" placeholder="简要描述您遇到的问题或建议">
            </div>
            <div class="form-group">
                <label class="form-label">详细内容</label>
                <textarea name="content" class="form-textarea" placeholder="请详细描述您的问题或建议，我们会认真对待每一条反馈~"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                提交反馈
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="admin-card-header" style="padding: 0 4px;">
        <div class="admin-card-title" style="font-size:18px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
            所有反馈 (<?php echo count($feedbacks); ?>)
        </div>
    </div>

    <div class="feedback-list" style="margin-top: 16px;">
        <?php if (empty($feedbacks)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="empty-state-title">还没有任何反馈</div>
                <div class="empty-state-desc">成为第一个提建议的人吧~</div>
            </div>
        <?php else: ?>
            <?php foreach ($feedbacks as $f):
                $fbUser = array('username' => $f['username'], 'avatar' => $f['avatar']);
                // 回复列表：管理员在用户回复之上、反馈者之下
                $replies = $db->fetchAll("
                    SELECT r.*, u.username, u.avatar, u.is_admin 
                    FROM feedback_replies r 
                    LEFT JOIN users u ON u.id = r.user_id 
                    WHERE r.feedback_id = ? 
                    ORDER BY 
                        CASE WHEN r.user_id = ? THEN 1           -- 反馈者排第一
                             WHEN u.is_admin = 1 THEN 2          -- 然后管理员
                             ELSE 3 END,                          -- 然后其他用户
                        r.created_at ASC
                ", array($f['id'], $f['user_id']));
            ?>
            <div class="feedback-card">
                <div class="feedback-head">
                    <img src="<?php echo e(getUserAvatar($fbUser)); ?>" class="feedback-avatar" alt="">
                    <div class="feedback-content">
                        <div class="feedback-user-line">
                            <div class="feedback-user">
                                <?php echo e($f['username']); ?>
                                <?php if (!empty($f['poster_is_admin'])): ?>
                                    <span class="admin-badge">开发者</span>
                                <?php endif; ?>
                                <?php if ($f['status'] == 1): ?>
                                    <span class="badge badge-success" style="font-size: 11px; padding: 2px 8px;">已处理</span>
                                <?php endif; ?>
                            </div>
                            <span class="feedback-time"><?php echo e(formatTimeAgo($f['created_at'])); ?></span>
                        </div>
                        <div class="feedback-title"><?php echo e($f['title']); ?></div>
                        <div class="feedback-body"><?php echo nl2br(e($f['content'])); ?></div>
                    </div>
                </div>

                <div class="replies-section replies-collapsed">
                    <?php foreach ($replies as $r):
                        $rUser = array('username' => $r['username'], 'avatar' => $r['avatar']);
                    ?>
                        <div class="reply-card <?php echo !empty($r['is_admin']) ? 'admin-reply' : ''; ?>">
                            <img src="<?php echo e(getUserAvatar($rUser)); ?>" class="reply-avatar" alt="">
                            <div class="reply-body">
                                <div class="reply-head">
                                    <span class="reply-username">
                                        <?php echo e($r['username']); ?>
                                        <?php if (!empty($r['is_admin'])): ?>
                                            <span class="admin-badge">开发者</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="feedback-time"><?php echo e(formatTimeAgo($r['created_at'])); ?></span>
                                </div>
                                <div class="reply-text"><?php echo nl2br(e($r['content'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button class="expand-replies-btn" data-total="<?php echo count($replies); ?>" style="display:none;">展开全部回复</button>

                    <?php if ($user): ?>
                    <div class="reply-input-wrap">
                        <input type="text" class="reply-input" placeholder="写下你的回复...">
                        <button class="btn btn-primary reply-submit-btn" data-fid="<?php echo $f['id']; ?>">回复</button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="feedback-actions">
                    <button class="feedback-action <?php echo in_array($f['id'], $likedIds) ? 'liked' : ''; ?>" data-like="<?php echo $f['id']; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="<?php echo in_array($f['id'], $likedIds) ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <?php if (in_array($f['id'], $likedIds)): ?>
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            <?php else: ?>
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            <?php endif; ?>
                        </svg>
                        <span class="like-count"><?php echo intval($f['like_count']); ?></span>
                    </button>
                    <div class="feedback-action" style="cursor:default;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <?php echo intval($f['reply_count']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
