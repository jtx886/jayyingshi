<?php
$adminActivePage = 'users';
$adminTitle = '用户管理';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

// 处理操作
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'ban') {
        $uid = intval($_POST['user_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $duration = intval($_POST['duration'] ?? 0); // 0=永久
        if ($uid == $_SESSION['user_id']) {
            redirect('users.php?msg=' . urlencode('不能封禁自己') . '&t=error');
        }
        $banEnd = null;
        if ($duration > 0) {
            $banEnd = date('Y-m-d H:i:s', time() + $duration * 3600);
        }
        $now = date('Y-m-d H:i:s');
        $db->update('users', array('status' => 0, 'ban_time' => $now, 'ban_end_time' => $banEnd, 'ban_reason' => $reason), 'id = ?', array($uid));
        // 发送邮件
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", array($uid));
        if ($user) {
            $endStr = $banEnd ? $banEnd : '永久';
            $html = Email::getEmailTemplate('账号封禁通知', '
                <div class="title">😔 您的账号被封禁</div>
                <p>亲爱的 <strong>' . htmlspecialchars($user['username']) . '</strong>，很抱歉通知您，您的账号因违规行为被封禁处理。</p>
                <div class="info-box">
                    <div style="margin-bottom:8px;"><strong>封禁时间：</strong>' . $now . '</div>
                    <div style="margin-bottom:8px;"><strong>解封时间：</strong>' . $endStr . '</div>
                    <div><strong>封禁原因：</strong>' . ($reason ?: '违反平台规则') . '</div>
                </div>
                <p style="margin-top:16px;">如有异议请通过反馈页面联系我们的管理员，感谢您的理解与配合。</p>
            ');
            @Email::send($user['email'], '【Jay影视】账号封禁通知', $html, 'ban_user', $uid);
        }
        redirect('users.php?msg=' . urlencode('已封禁该用户并发送邮件通知') . '&t=success');
    } elseif ($action === 'unban') {
        $uid = intval($_POST['user_id'] ?? 0);
        $db->update('users', array('status' => 1, 'ban_time' => null, 'ban_end_time' => null, 'ban_reason' => null), 'id = ?', array($uid));
        redirect('users.php?msg=' . urlencode('已解封该用户') . '&t=success');
    } elseif ($action === 'delete') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid == $_SESSION['user_id']) redirect('users.php?msg=' . urlencode('不能删除自己') . '&t=error');
        $db->delete('users', 'id = ? AND is_admin = 0', array($uid));
        redirect('users.php?msg=' . urlencode('用户已删除') . '&t=success');
    }
}

// 搜索用户
$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$whereArr = array();
$params = array();
if ($q !== '') {
    $whereArr[] = '(username LIKE ? OR email LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($statusFilter === 'normal') { $whereArr[] = 'status = 1'; }
elseif ($statusFilter === 'banned') { $whereArr[] = 'status = 0'; }

$whereSql = $whereArr ? ('WHERE ' . implode(' AND ', $whereArr)) : '';
$total = $db->fetchOne("SELECT COUNT(*) c FROM users $whereSql", $params)['c'];
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$start = ($page - 1) * $perPage;
$users = $db->fetchAll("SELECT * FROM users $whereSql ORDER BY id DESC LIMIT $start, $perPage", $params);
$totalPages = ceil($total / $perPage);

showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            用户列表 (共 <?php echo intval($total); ?> 位用户)
        </div>
    </div>
    <form method="GET" style="display:flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="text" name="q" class="form-input" value="<?php echo e($q); ?>" placeholder="搜索用户名/邮箱" style="max-width: 260px;">
        <select name="status" class="form-select" style="max-width: 160px;">
            <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>全部状态</option>
            <option value="normal" <?php echo $statusFilter == 'normal' ? 'selected' : ''; ?>>正常用户</option>
            <option value="banned" <?php echo $statusFilter == 'banned' ? 'selected' : ''; ?>>封禁用户</option>
        </select>
        <button class="btn btn-primary">筛选</button>
        <?php if ($q || $statusFilter !== 'all'): ?>
            <a href="users.php" class="btn btn-outline">重置</a>
        <?php endif; ?>
    </form>

    <table class="data-table">
        <thead><tr>
            <th>用户</th><th>邮箱</th><th>注册时间</th><th>状态</th><th>封禁信息</th><th style="text-align:right;">操作</th>
        </tr></thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align:center; padding:50px 0; color:var(--text-muted);">没有符合条件的用户</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap: 10px;">
                        <img src="<?php echo e(getUserAvatar($u)); ?>" style="width:36px;height:36px;border-radius:50%; object-fit:cover;">
                        <div>
                            <div style="display:flex; align-items:center; gap: 6px;">
                                <strong><?php echo e($u['username']); ?></strong>
                                <?php if (!empty($u['is_admin'])): ?><span class="admin-badge">开发者</span><?php endif; ?>
                            </div>
                            <div style="font-size:11px; color:var(--text-muted);">ID: <?php echo $u['id']; ?></div>
                        </div>
                    </div>
                </td>
                <td><?php echo e($u['email']); ?></td>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(date('Y-m-d H:i', strtotime($u['created_at']))); ?></td>
                <td>
                    <?php if ($u['status'] == 1): ?>
                        <span class="badge badge-success">正常</span>
                    <?php else: ?>
                        <span class="badge badge-danger">封禁中</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;">
                    <?php if ($u['status'] == 0): ?>
                        <div>原因：<?php echo e($u['ban_reason'] ?: '无'); ?></div>
                        <div style="color:var(--text-muted); margin-top:3px;">解封：<?php echo e($u['ban_end_time'] ?: '永久'); ?></div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">-</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <div class="table-actions" style="justify-content: flex-end;">
                        <?php if ($u['status'] == 1 && empty($u['is_admin'])): ?>
                            <button class="btn btn-sm btn-outline" onclick="openBan(<?php echo $u['id']; ?>, <?php echo json_encode($u['username']); ?>)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM9 5h6v2H9z"/></svg>
                                封禁
                            </button>
                        <?php elseif ($u['status'] == 0): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="unban">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button class="btn btn-sm btn-success">解封</button>
                            </form>
                        <?php endif; ?>
                        <?php if (empty($u['is_admin'])): ?>
                            <button class="btn btn-sm btn-danger" onclick="delUser(<?php echo $u['id']; ?>, <?php echo json_encode($u['username']); ?>)">
                                删除
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <a class="page-btn" href="?q=<?php echo urlencode($q); ?>&status=<?php echo e($statusFilter); ?>&page=<?php echo max(1, $page-1); ?>" <?php if ($page <= 1) echo 'disabled'; ?>>‹</a>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a class="page-btn <?php echo $i == $page ? 'active' : ''; ?>" href="?q=<?php echo urlencode($q); ?>&status=<?php echo e($statusFilter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a class="page-btn" href="?q=<?php echo urlencode($q); ?>&status=<?php echo e($statusFilter); ?>&page=<?php echo min($totalPages, $page+1); ?>" <?php if ($page >= $totalPages) echo 'disabled'; ?>>›</a>
    </div>
    <?php endif; ?>
</div>

<!-- 封禁弹窗 -->
<div class="modal-overlay" id="banModal" style="display:none;">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header"><h3 id="banTitle">封禁用户</h3>
            <button class="modal-close" onclick="document.getElementById('banModal').style.display='none'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="ban">
                <input type="hidden" name="user_id" id="banUserId">
                <div class="form-group">
                    <label class="form-label">封禁时长</label>
                    <select name="duration" class="form-select">
                        <option value="0">永久封禁</option>
                        <option value="1">1小时</option>
                        <option value="24">1天</option>
                        <option value="168">7天</option>
                        <option value="720">30天</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">封禁原因（可选）</label>
                    <textarea name="reason" class="form-textarea" style="min-height: 80px;" placeholder="用户被封禁的原因，会通过邮件通知该用户"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('banModal').style.display='none'">取消</button>
                <button type="submit" class="btn btn-danger">确认封禁</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openBan(uid, name) {
        document.getElementById('banUserId').value = uid;
        document.getElementById('banTitle').innerHTML = '封禁用户：' + name;
        document.getElementById('banModal').style.display = '';
    }
    function delUser(uid, name) {
        confirmDialog('确认删除用户 "' + name + '"？\n此操作不可撤销，该用户所有数据将被清空。').then(function(r){
            if (!r) return;
            var f = document.createElement('form');
            f.method = 'POST';
            f.innerHTML = '<input name="action" value="delete"><input name="user_id" value="'+uid+'">';
            document.body.appendChild(f); f.submit();
        });
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
