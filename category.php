<?php
require_once __DIR__ . '/includes/tmdb.php';
$type = $_GET['type'] ?? 'movie';
$page = max(1, intval($_GET['page'] ?? 1));

$typeMap = array(
    'movie' => array('label' => '电影', 'method' => 'getPopularMovies', 'params' => array()),
    'tv' => array('label' => '电视剧', 'method' => 'getPopularTv'),
    'anime' => array('label' => '动漫', 'method' => 'getPopularAnime'),
    'variety' => array('label' => '综艺', 'method' => 'getPopularVariety'),
    'all' => array('label' => '全部', 'method' => 'getTrending', 'args' => array('all', 'week')),
);
$typeConfig = $typeMap[$type] ?? $typeMap['movie'];
$pageTitle = $typeConfig['label'];
include __DIR__ . '/header.php';

try {
    $method = $typeConfig['method'];
    if (isset($typeConfig['args'])) {
        $result = TMDB::$method($typeConfig['args'][0], $typeConfig['args'][1]);
    } else {
        $result = TMDB::$method($page);
    }
} catch (Exception $e) {
    $result = null;
}
?>
<div class="container" style="padding-top: 24px;">
    <div class="section-header" style="margin-bottom: 22px;">
        <h1 class="section-title" style="font-size: 28px;">
            <span class="section-title-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18M3 7.5h4M3 12h18M3 16.5h4M17 3v18M17 7.5h4M17 16.5h4"/></svg>
            </span>
            <?php echo e($typeConfig['label']); ?>
        </h1>
    </div>

    <?php if (!empty($typeMap[$type]) && $type !== 'all'): ?>
    <div class="filter-tabs" style="margin-bottom: 24px;">
        <span class="filter-tab active">全部</span>
        <span class="filter-tab">高分</span>
        <span class="filter-tab">最新</span>
        <span class="filter-tab">热门</span>
        <span class="filter-tab">动作</span>
        <span class="filter-tab">喜剧</span>
        <span class="filter-tab">爱情</span>
        <span class="filter-tab">科幻</span>
        <span class="filter-tab">悬疑</span>
        <span class="filter-tab">剧情</span>
    </div>
    <?php endif; ?>

    <?php if ($result && !empty($result['results'])): ?>
        <div class="media-grid">
            <?php foreach ($result['results'] as $m):
                $id = $m['id'];
                $title = $m['title'] ?? $m['name'] ?? '';
                $poster = TMDB::getImageUrl($m['poster_path'] ?? '', 'w342');
                $rating = $m['vote_average'] ?? 0;
                $year = '';
                $dateStr = $m['release_date'] ?? ($m['first_air_date'] ?? '');
                if ($dateStr) $year = substr($dateStr, 0, 4);
                $mediaType = $type;
                if ($type === 'all') $mediaType = $m['media_type'] ?? 'movie';
            ?>
            <div class="media-card" onclick="window.location='detail.php?id=<?php echo $id; ?>&type=<?php echo $mediaType; ?>'">
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
                        <span><?php echo e($typeConfig['label']); ?></span>
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
            <a class="page-btn" href="?type=<?php echo e($type); ?>&page=<?php echo max(1, $page-1); ?>" <?php if ($page <= 1) echo 'disabled'; ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a class="page-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?type=<?php echo e($type); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <a class="page-btn" href="?type=<?php echo e($type); ?>&page=<?php echo min($totalPages, $page+1); ?>" <?php if ($page >= $totalPages) echo 'disabled'; ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="empty-state-title">暂无内容</div>
            <div class="empty-state-desc">稍后再来看看吧~</div>
            <a href="index.php" class="btn btn-primary">返回首页</a>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
