<?php
require_once __DIR__ . '/includes/tmdb.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = '首页';
include __DIR__ . '/header.php';

try {
    $trending = TMDB::getTrending('all', 'day');
    $nowPlaying = TMDB::getNowPlaying();
    $popMovies = TMDB::getPopularMovies();
    $popTv = TMDB::getPopularTv();
    $popAnime = TMDB::getPopularAnime();
    $popVariety = TMDB::getPopularVariety();
} catch (Exception $e) {
    $trending = $nowPlaying = $popMovies = $popTv = $popAnime = $popVariety = null;
}

$db = Database::getInstance();
// 获取最新公告
$latestAnn = null;
if (Auth::isLoggedIn()) {
    $latestAnn = $db->fetchOne("
        SELECT a.* FROM announcements a 
        LEFT JOIN announcement_dismissed ad ON a.id = ad.announcement_id AND ad.user_id = ?
        WHERE ad.id IS NULL 
        ORDER BY a.id DESC LIMIT 1
    ", array($_SESSION['user_id']));
} else {
    if (empty($_SESSION['guest_seen_ann'])) {
        $latestAnn = $db->fetchOne("SELECT * FROM announcements ORDER BY id DESC LIMIT 1");
    }
}

function renderMediaCard($m, $mediaType = null) {
    $type = $mediaType ?: ($m['media_type'] ?? 'movie');
    $id = $m['id'];
    $title = $m['title'] ?? $m['name'] ?? '';
    $poster = TMDB::getImageUrl($m['poster_path'] ?? '', 'w342');
    $rating = $m['vote_average'] ?? 0;
    $year = '';
    $dateStr = $m['release_date'] ?? ($m['first_air_date'] ?? '');
    if ($dateStr) $year = substr($dateStr, 0, 4);
    $typeLabel = array('movie' => '电影', 'tv' => '剧集', 'anime' => '动漫', 'variety' => '综艺');
    $itemType = '';
    if (!empty($m['media_type'])) $itemType = $typeLabel[$m['media_type']] ?? '';
    else if ($type == 'anime') $itemType = '动漫';
    else if ($type == 'variety') $itemType = '综艺';
    else $itemType = $typeLabel[$type] ?? '';
    $ep = '';
    if (!empty($m['number_of_episodes'])) $ep = '· ' . $m['number_of_episodes'] . '集全';
    if (!empty($m['status']) && $m['status'] !== 'Ended' && !empty($m['last_episode_to_air']['episode_number'])) {
        $ep = '· 更新至' . $m['last_episode_to_air']['episode_number'] . '集';
    }
    if (isset($m['total_episodes'])) $ep = '· 更新至' . $m['total_episodes'] . '集';
    ?>
    <div class="media-card" onclick="window.location='detail.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>'">
        <div class="media-poster">
            <?php if ($poster): ?>
                <img src="<?php echo e($poster); ?>" alt="<?php echo e($title); ?>" loading="lazy">
            <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#37374d,#252542);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:14px;">无海报</div>
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
                <?php if ($itemType): ?><span><?php echo e($itemType); ?></span><?php endif; ?>
                <?php if ($ep): ?><span class="media-meta-dot"></span><span><?php echo e($ep); ?></span><?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function renderSection($title, $icon, $items, $mediaType = null, $moreUrl = '') {
    if (!$items || empty($items['results'])) return;
    $results = array_slice($items['results'], 0, 12);
    ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon"><?php echo $icon; ?></span>
                <?php echo e($title); ?>
            </h2>
            <?php if ($moreUrl): ?>
                <a href="<?php echo e($moreUrl); ?>" class="section-more">
                    查看更多
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            <?php endif; ?>
        </div>
        <div class="media-grid">
            <?php foreach ($results as $m) renderMediaCard($m, $mediaType); ?>
        </div>
    </section>
    <?php
}

// 渲染 Hero
function renderHero($trending, $nowPlaying) {
    $items = array();
    if (!empty($trending['results'])) $items = array_merge($items, array_slice($trending['results'], 0, 4));
    if (!empty($nowPlaying['results'])) $items = array_merge($items, array_slice($nowPlaying['results'], 0, 3));
    $items = array_values(array_filter($items, function($i) { return !empty($i['backdrop_path']); }));
    if (count($items) > 5) $items = array_slice($items, 0, 5);
    if (!$items) return;
    ?>
    <div class="hero">
        <?php foreach ($items as $idx => $m): 
            $type = $m['media_type'] ?? 'movie';
            $title = $m['title'] ?? $m['name'] ?? '';
            $rating = $m['vote_average'] ?? 0;
            $year = '';
            $dateStr = $m['release_date'] ?? ($m['first_air_date'] ?? '');
            if ($dateStr) $year = substr($dateStr, 0, 4);
            $genres = array('电影','剧集','动漫');
            $typeIdx = $type === 'tv' ? 1 : ($type === 'anime' ? 2 : 0);
            $genre = $genres[$typeIdx];
            ?>
            <div class="hero-slide <?php echo $idx === 0 ? 'active' : ''; ?>">
                <div class="hero-bg" style="background-image:url('<?php echo e(TMDB::getImageUrl($m['backdrop_path'], 'original')); ?>');"></div>
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <span class="hero-tag">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        正在热映
                    </span>
                    <h1 class="hero-title"><?php echo e($title); ?></h1>
                    <div class="hero-meta">
                        <span class="hero-rating">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo number_format($rating, 1); ?>
                        </span>
                        <?php if ($year): ?><span><?php echo e($year); ?></span><?php endif; ?>
                        <span><?php echo e($genre); ?></span>
                        <?php if (!empty($m['original_language'])): ?>
                            <span>原声: <?php echo e(strtoupper($m['original_language'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="hero-desc"><?php echo e($m['overview'] ?? '暂无简介'); ?></p>
                    <div class="hero-buttons">
                        <a href="detail.php?id=<?php echo $m['id']; ?>&type=<?php echo $type; ?>" class="btn btn-primary btn-lg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            立即播放
                        </a>
                        <a href="detail.php?id=<?php echo $m['id']; ?>&type=<?php echo $type; ?>#fav" class="btn btn-outline btn-lg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            收藏
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="hero-dots">
            <?php for ($i = 0; $i < count($items); $i++): ?>
                <span class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <?php
}
?>

<div class="container">
    <?php renderHero($trending, $nowPlaying); ?>

    <!-- 热门推荐 -->
    <?php
    $fireIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
    $movieIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>';
    $tvIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>';
    $animeIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a9.993 9.993 0 0 0-9.95 9h11.644L12 5.5 8.306 11H2.05A9.994 9.994 0 0 0 12 22a9.99 9.99 0 0 0 9.95-9h-11.644L12 18.5 15.694 13H21.95A9.994 9.994 0 0 0 12 2Z"/></svg>';
    $varietyIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3"/></svg>';
    
    renderSection('热门推荐', $fireIcon, $trending, null, 'category.php?type=all');
    ?>

    <!-- 电影 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon"><?php echo $movieIcon; ?></span>
                电影
            </h2>
            <a href="category.php?type=movie" class="section-more">查看更多
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
        <div class="filter-tabs">
            <span class="filter-tab active">全部</span>
            <span class="filter-tab">动作</span>
            <span class="filter-tab">喜剧</span>
            <span class="filter-tab">爱情</span>
            <span class="filter-tab">科幻</span>
            <span class="filter-tab">悬疑</span>
            <span class="filter-tab">剧情</span>
        </div>
        <div class="media-grid">
            <?php
            if (!empty($popMovies['results'])) {
                foreach (array_slice($popMovies['results'], 0, 6) as $m) renderMediaCard($m, 'movie');
            }
            ?>
        </div>
    </section>

    <?php
    renderSection('热门电视剧', $tvIcon, $popTv, 'tv', 'category.php?type=tv');
    renderSection('热门动漫', $animeIcon, $popAnime, 'anime', 'category.php?type=anime');
    renderSection('热门综艺', $varietyIcon, $popVariety, 'variety', 'category.php?type=variety');
    ?>
</div>

<?php
// 公告弹窗（仅首页显示）
if ($latestAnn):
    $guestDismiss = !Auth::isLoggedIn();
?>
<div class="modal-overlay announcement-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <?php echo e($latestAnn['title']); ?>
            </h3>
            <button class="modal-close" aria-label="关闭">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <?php echo nl2br(e($latestAnn['content'])); ?>
        </div>
        <div class="modal-footer">
            <label class="checkbox-wrap">
                <input type="checkbox" class="ann-dismiss" data-id="<?php echo $latestAnn['id']; ?>">
                <span class="checkbox-custom"></span>
                不再提示此公告
            </label>
            <button class="btn btn-primary ann-ok">我知道了</button>
        </div>
    </div>
</div>
<?php
    if ($guestDismiss) $_SESSION['guest_seen_ann'] = true;
endif;

include __DIR__ . '/footer.php';
?>
