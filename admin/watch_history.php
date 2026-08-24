<?php
$adminActivePage = 'history';
$adminTitle = '观看历史';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

$uidFilter = intval($_GET['user_id'] ?? 0);
$q = trim($_GET['q'] ?? '');

$whereArr = array();
$params = array();
if ($uidFilter > 0) { $whereArr[] = 'w.user_id = ?'; $params[] = $uidFilter; }
if ($q !== '') { $whereArr[] = 'w.media_title LIKE ?'; $params[] = '%' . $q . '%'; }
$where = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';

$total = $db->fetchOne("SELECT COUNT(*) c FROM watch_history w $where", $params)['c'];
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$start = ($page - 1) * $perPage;

$list = $db->fetchAll("
    SELECT w.*, u.username, u.email FROM watch_history w 
    LEFT JOIN users u ON u.id = w.user_id 
    $where
    ORDER BY w.last_watch_time DESC 
    LIMIT $start, $perPage
", $params);

$totalSeconds = $db->fetchOne("SELECT COALESCE(SUM(w.watch_seconds),0) t FROM watch_history w $where", $params)['t'];

// 用户筛选列表
$users = $db->fetchAll("SELECT id, username FROM users ORDER BY username ASC");

showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
            观看历史 (共 <?php echo intval($total); ?> 条记录 · 累计观看 <strong style="color:var(--theme-light);"><?php echo formatDuration(intval($totalSeconds)); ?></strong>)
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
            <a href="watch_history.php" class="btn btn-outline">重置</a>
        <?php endif; ?>
    </form>

    <?php if (empty($list)): ?>
        <div class="empty-state"><div class="empty-state-title">暂无记录</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>用户</th><th>影视</th><th>类型</th><th>集</th><th>时长</th><th>上次观看</th></tr></thead>
        <tbody>
            <?php foreach ($list as $h): ?>
            <tr>
                <td>
                    <div>
                        <strong><?php echo e($h['username'] ?? '已删除用户'); ?></strong>
                        <?php if (!empty($h['email'])): ?>
                            <div style="font-size:11px; color:var(--text-muted);"><?php echo e($h['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="max-width:260px;">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <?php if (!empty($h['media_poster'])): ?>
                            <img src="<?php echo e($h['media_poster']); ?>" style="width:40px; height:56px; border-radius:6px; object-fit:cover; flex-shrink:0;">
                        <?php endif; ?>
                        <div style="overflow:hidden;">
                            <div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($h['media_title']); ?></div>
                            <a href="../detail.php?id=<?php echo $h['media_id']; ?>&type=<?php echo e($h['media_type']); ?>" target="_blank" style="font-size:11px; color:var(--theme-light);">查看详情 ›</a>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-info"><?php echo e(array('movie'=>'电影','tv'=>'剧集','anime'=>'动漫','variety'=>'综艺')[$h['media_type']] ?? $h['media_type']); ?></span></td>
                <td><?php echo $h['media_type'] === 'movie' ? '电影' : '第' . $h['episode'] . '集'; ?></td>
                <td><?php echo formatDuration($h['watch_seconds']); ?></td>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(formatTimeAgo($h['last_watch_time'])); ?></td>
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
