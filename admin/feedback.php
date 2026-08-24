<?php
$adminActivePage = 'feedback';
$adminTitle = '反馈管理';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'reply') {
        $fid = intval($_POST['feedback_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        if ($fid && $content) {
            $db->insert('feedback_replies', array(
                'feedback_id' => $fid,
                'user_id' => $_SESSION['user_id'],
                'content' => $content,
                'is_admin' => 1
            ));
            $db->update('feedback', array('status' => 1), 'id = ?', array($fid));
            redirect('feedback.php?msg=' . urlencode('回复成功') . '&t=success');
        }
    } elseif ($action === 'delete') {
        $fid = intval($_POST['id'] ?? 0);
        $db->delete('feedback', 'id = ?', array($fid));
        redirect('feedback.php?msg=' . urlencode('反馈已删除') . '&t=success');
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$whereArr = array();
$params = array();
if ($statusFilter === 'pending') { $whereArr[] = 'f.status = 0'; }
elseif ($statusFilter === 'done') { $whereArr[] = 'f.status = 1'; }
$where = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';

$total = $db->fetchOne("SELECT COUNT(*) c FROM feedback f $where", $params)['c'];
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$start = ($page - 1) * $perPage;

$list = $db->fetchAll("
    SELECT f.*, u.username, u.avatar, u.is_admin as poster_admin 
    FROM feedback f 
    LEFT JOIN users u ON u.id = f.user_id 
    $where
    ORDER BY f.id DESC 
    LIMIT $start, $perPage
", $params);

showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            反馈管理 (共 <?php echo intval($total); ?> 条)
        </div>
    </div>
    <form method="GET" style="display:flex; gap:10px; margin-bottom: 20px;">
        <select name="status" class="form-select" style="max-width: 200px;">
            <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>全部</option>
            <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>待处理</option>
            <option value="done" <?php echo $statusFilter == 'done' ? 'selected' : ''; ?>>已处理</option>
        </select>
        <button class="btn btn-primary">筛选</button>
    </form>

    <?php if (empty($list)): ?>
        <div class="empty-state"><div class="empty-state-title">暂无反馈</div></div>
    <?php else: ?>
        <?php foreach ($list as $f):
            $replies = $db->fetchAll("
                SELECT r.*, u.username, u.is_admin 
                FROM feedback_replies r 
                LEFT JOIN users u ON u.id = r.user_id 
                WHERE r.feedback_id = ? 
                ORDER BY 
                    CASE WHEN r.user_id = ? THEN 1
                         WHEN u.is_admin = 1 THEN 2 
                         ELSE 3 END, 
                    r.created_at ASC
            ", array($f['id'], $f['user_id']));
            $replyCount = count($replies);
            $likeCount = $db->fetchOne("SELECT COUNT(*) c FROM feedback_likes WHERE feedback_id = ?", array($f['id']))['c'];
        ?>
        <div class="feedback-card" style="margin-bottom: 20px;">
            <div class="feedback-head">
                <img src="<?php echo e(getUserAvatar(array('username' => $f['username'], 'avatar' => $f['avatar']))); ?>" class="feedback-avatar">
                <div class="feedback-content">
                    <div class="feedback-user-line">
                        <div class="feedback-user">
                            <?php echo e($f['username']); ?>
                            <?php if (!empty($f['poster_admin'])): ?><span class="admin-badge">开发者</span><?php endif; ?>
                        </div>
                        <?php if ($f['status'] == 1): ?>
                            <span class="badge badge-success">已处理</span>
                        <?php else: ?>
                            <span class="badge badge-warning">待处理</span>
                        <?php endif; ?>
                        <span class="feedback-time">#<?php echo $f['id']; ?> · <?php echo e(date('Y-m-d H:i', strtotime($f['created_at']))); ?> · 👍 <?php echo intval($likeCount); ?></span>
                    </div>
                    <div class="feedback-title"><?php echo e($f['title']); ?></div>
                    <div class="feedback-body"><?php echo nl2br(e($f['content'])); ?></div>
                    <div style="margin-top: 12px;">
                        <button class="btn btn-outline btn-sm" onclick="toggleReplyBox(<?php echo $f['id']; ?>)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            回复 (<?php echo intval($replyCount); ?>)
                        </button>
                        <button class="btn btn-danger btn-sm" style="margin-left: 6px;" onclick="delFb(<?php echo $f['id']; ?>)">删除</button>
                    </div>
                </div>
            </div>
            <div class="replies-section replies-collapsed" style="padding-top: 0;">
                <?php foreach ($replies as $r): ?>
                    <div class="reply-card <?php echo !empty($r['is_admin']) ? 'admin-reply' : ''; ?>">
                        <img src="<?php echo e(getUserAvatar(array('username' => $r['username'], 'avatar' => null))); ?>" class="reply-avatar">
                        <div class="reply-body">
                            <div class="reply-head">
                                <span class="reply-username">
                                    <?php echo e($r['username']); ?>
                                    <?php if (!empty($r['is_admin'])): ?><span class="admin-badge">开发者</span><?php endif; ?>
                                </span>
                                <span class="feedback-time"><?php echo e(date('Y-m-d H:i', strtotime($r['created_at']))); ?></span>
                            </div>
                            <div class="reply-text"><?php echo nl2br(e($r['content'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button class="expand-replies-btn" data-total="<?php echo intval($replyCount); ?>">展开全部</button>

                <div id="replyBox-<?php echo $f['id']; ?>" style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-color); display: none;">
                    <form method="POST">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="feedback_id" value="<?php echo $f['id']; ?>">
                        <div class="form-group">
                            <label class="form-label">管理员回复</label>
                            <textarea name="content" class="form-textarea" placeholder="输入回复内容，提交后将标记为已处理并向用户展示（开发者回复将被高亮显示）" style="min-height: 80px;" required></textarea>
                        </div>
                        <button class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                            提交回复
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php
        $totalPages = ceil($total / $perPage);
        if ($totalPages > 1):
        ?>
        <div class="pagination" style="margin-top: 24px;">
            <a class="page-btn" href="?status=<?php echo e($statusFilter); ?>&page=<?php echo max(1, $page-1); ?>" <?php if ($page <= 1) echo 'disabled'; ?>>‹</a>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a class="page-btn <?php echo $i == $page ? 'active' : ''; ?>" href="?status=<?php echo e($statusFilter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <a class="page-btn" href="?status=<?php echo e($statusFilter); ?>&page=<?php echo min($totalPages, $page+1); ?>" <?php if ($page >= $totalPages) echo 'disabled'; ?>>›</a>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
    function toggleReplyBox(id) {
        var el = document.getElementById('replyBox-' + id);
        el.style.display = el.style.display === 'none' ? '' : 'none';
        if (el.style.display !== 'none') el.querySelector('textarea').focus();
    }
    function delFb(id) {
        confirmDialog('确认删除该反馈？所有回复也将被一起删除').then(r=>{
            if (!r) return;
            var f = document.createElement('form'); f.method='POST';
            f.innerHTML = '<input name="action" value="delete"><input name="id" value="'+id+'">';
            document.body.appendChild(f); f.submit();
        });
    }
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
