<?php
$adminActivePage = 'favorites';
$adminTitle = '用户收藏';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

$uidFilter = intval($_GET['user_id'] ?? 0);
$q = trim($_GET['q'] ?? '');

$whereArr = array();
$params = array();
if ($uidFilter > 0) { $whereArr[] = 'f.user_id = ?'; $params[] = $uidFilter; }
if ($q !== '') { $whereArr[] = 'f.media_title LIKE ?'; $params[] = '%' . $q . '%'; }
$where = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';

$total = $db->fetchOne("SELECT COUNT(*) c FROM favorites f $where", $params)['c'];
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$start = ($page - 1) * $perPage;

$list = $db->fetchAll("
    SELECT f.*, u.username, u.email FROM favorites f 
    LEFT JOIN users u ON u.id = f.user_id 
    $where
    ORDER BY f.id DESC 
    LIMIT $start, $perPage
", $params);

$users = $db->fetchAll("SELECT id, username FROM users ORDER BY username ASC");

showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            用户收藏 (共 <?php echo intval($total); ?> 条)
        </div>
    </div>
    <form method="GET" style="display:flex; gap:10px; margin-bottom: 20px; flex-wrap: wrap;">
        <select name="user_id" class="form-select" style="max-width: 220px;">
            <option value="0">全部用户</option>
            <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $uidFilter == $u['id'] ? 'selected' : ''; ?>><?php echo e($u['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" class="form-input" value="<?php echo e($q); ?>" placeholder="搜索影视标题" style="max-width:260px;">
        <button class="btn btn-primary">筛选</button>
        <?php if ($uidFilter || $q): ?>
            <a href="favorites.php" class="btn btn-outline">重置</a>
        <?php endif; ?>
    </form>

    <?php if (empty($list)): ?>
        <div class="empty-state"><div class="empty-state-title">暂无收藏</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>用户</th><th>影视</th><th>类型</th><th>收藏时间</th></tr></thead>
        <tbody>
            <?php foreach ($list as $f): ?>
            <tr>
                <td>
                    <div>
                        <strong><?php echo e($f['username'] ?? '已删除用户'); ?></strong>
                        <?php if (!empty($f['email'])): ?>
                            <div style="font-size:11px; color:var(--text-muted);"><?php echo e($f['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="max-width:320px;">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <?php if (!empty($f['media_poster'])): ?>
                            <img src="<?php echo e($f['media_poster']); ?>" style="width:40px; height:56px; border-radius:6px; object-fit:cover; flex-shrink:0;">
                        <?php endif; ?>
                        <div style="overflow:hidden;">
                            <div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($f['media_title']); ?></div>
                            <a href="../detail.php?id=<?php echo $f['media_id']; ?>&type=<?php echo e($f['media_type']); ?>" target="_blank" style="font-size:11px; color:var(--theme-light);">查看详情 ›</a>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-info"><?php echo e(array('movie'=>'电影','tv'=>'剧集','anime'=>'动漫','variety'=>'综艺')[$f['media_type']] ?? $f['media_type']); ?></span></td>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(date('Y-m-d H:i', strtotime($f['created_at']))); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $totalPages = ceil($total / $perPage);
    if ($totalPages > 1):
    ?>
    <div class="pagination" style="margin-top: 24px;">
        <a class="page-btn" href="?user_id=<?php echo $uidFilter; ?>&q=<?php echo urlencode($q); ?>&page=<?php echo max(1, $page-1); ?>" <?php if ($page <= 1) echo 'disabled'; ?>>‹</a>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a class="page-btn <?php echo $i == $page ? 'active' : ''; ?>" href="?user_id=<?php echo $uidFilter; ?>&q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a class="page-btn" href="?user_id=<?php echo $uidFilter; ?>&q=<?php echo urlencode($q); ?>&page=<?php echo min($totalPages, $page+1); ?>" <?php if ($page >= $totalPages) echo 'disabled'; ?>>›</a>
    </div>
    <?php endif; endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
