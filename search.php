<?php
require_once __DIR__ . '/includes/tmdb.php';
$q = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$pageTitle = ($q ? ('搜索: ' . $q) : '搜索');
include __DIR__ . '/header.php';

$result = null;
if ($q) {
    try {
        $result = TMDB::search($q, $page);
    } catch (Exception $e) {}
}
?>
<div class="container" style="padding-top: 24px;">
    <div class="admin-card" style="margin-bottom: 28px;">
        <div style="margin-bottom: 14px; font-size: 14px; color: var(--text-secondary);">
            搜索超过 <strong style="color:var(--text-primary);font-size:15px;"><?php echo number_format($result['total_results'] ?? 0); ?></strong> 个结果
            <?php if ($q): ?>，关键词 "<strong style="color:var(--theme-light);"><?php echo e($q); ?></strong>"<?php endif; ?>
        </div>
        <div style="position:relative;">
            <input type="text" id="searchQuick" class="form-input" value="<?php echo e($q); ?>" placeholder="输入关键词搜索..." style="padding-left:46px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
    </div>

    <?php if ($q): ?>
        <?php if ($result && !empty($result['results'])): ?>
            <div class="media-grid">
                <?php foreach ($result['results'] as $m):
                    if (empty($m['poster_path']) && empty($m['backdrop_path'])) continue;
                    $type = $m['media_type'] ?? 'movie';
                    if ($type === 'person') continue;
                    $id = $m['id'];
                    $title = $m['title'] ?? $m['name'] ?? '';
                    $poster = TMDB::getImageUrl($m['poster_path'] ?? '', 'w342');
                    $rating = $m['vote_average'] ?? 0;
                    $year = '';
                    $dateStr = $m['release_date'] ?? ($m['first_air_date'] ?? '');
                    if ($dateStr) $year = substr($dateStr, 0, 4);
                    $typeLabel = array('movie' => '电影', 'tv' => '剧集');
                    $itemType = $typeLabel[$type] ?? '影视';
                ?>
                <div class="media-card" onclick="window.location='detail.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>'">
                    <div class="media-poster">
                        <?php if ($poster): ?>
                            <img src="<?php echo e($poster); ?>" alt="<?php echo e($title); ?>" loading="lazy">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:14px;">无海报</div>
                        <?php endif; ?>
                        <?php if ($rating > 0): ?>
                            <div class="media-rating">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <?php echo number_format($rating, 1); ?>
                            </div>
                        <?php endif; ?>
                        <div class="media-play-overlay"><div class="media-play-btn"></div></div>
                    </div>
                    <div class="media-info">
                        <div class="media-title"><?php echo e($title); ?></div>
                        <div class="media-meta">
                            <?php if ($year): ?><span><?php echo e($year); ?></span><span class="media-meta-dot"></span><?php endif; ?>
                            <span><?php echo e($itemType); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
                $totalPages = min(500, intval($result['total_pages'] ?? 1));
                if ($totalPages > 1):
            ?>
            <div class="pagination">
                <a class="page-btn" href="?q=<?php echo urlencode($q); ?>&page=<?php echo max(1, $page-1); ?>" <?php if ($page <= 1) echo 'disabled'; ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="?q=<?php echo urlencode($q); ?>&page=<?php echo min($totalPages, $page+1); ?>" <?php if ($page >= $totalPages) echo 'disabled'; ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <div class="empty-state-title">没有找到相关结果</div>
                <div class="empty-state-desc">试试换个关键词重新搜索吧~</div>
                <a href="index.php" class="btn btn-primary">返回首页</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <div class="empty-state-title">输入关键词开始搜索</div>
            <div class="empty-state-desc">搜索电影、电视剧、动漫、综艺等</div>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
