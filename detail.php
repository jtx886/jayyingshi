<?php
require_once __DIR__ . '/includes/tmdb.php';
require_once __DIR__ . '/includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'movie';

if (!$id) { redirect('index.php'); }

try {
    if ($type === 'movie') {
        $detail = TMDB::getMovieDetail($id);
    } else {
        $detail = TMDB::getTvDetail($id);
    }
} catch (Exception $e) {
    $detail = null;
}

if (!$detail) {
    $pageTitle = '未找到';
    include __DIR__ . '/header.php';
    echo '<div class="container" style="padding:60px 0;"><div class="empty-state">
        <div class="empty-state-icon"><svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
        <div class="empty-state-title">未找到该影视</div><div class="empty-state-desc">它可能已经被移除了</div>
        <a href="index.php" class="btn btn-primary">返回首页</a></div></div>';
    include __DIR__ . '/footer.php';
    exit;
}

$pageTitle = $detail['title'] ?? ($detail['name'] ?? '详情');
include __DIR__ . '/header.php';

$title = $detail['title'] ?? $detail['name'] ?? '';
$originalTitle = $detail['original_title'] ?? ($detail['original_name'] ?? '');
$poster = TMDB::getImageUrl($detail['poster_path'] ?? '', 'w500');
$backdrop = TMDB::getImageUrl($detail['backdrop_path'] ?? '', 'original');
$rating = $detail['vote_average'] ?? 0;
$voteCount = $detail['vote_count'] ?? 0;
$year = '';
$dateStr = $detail['release_date'] ?? ($detail['first_air_date'] ?? '');
if ($dateStr) $year = substr($dateStr, 0, 4);
$runtime = $detail['runtime'] ?? null;
$runtimeStr = '';
if ($runtime) {
    $h = floor($runtime / 60);
    $m = $runtime % 60;
    $runtimeStr = ($h ? $h . '小时' : '') . ($m ? $m . '分钟' : '');
}
$genres = array();
if (!empty($detail['genres'])) {
    foreach ($detail['genres'] as $g) $genres[] = $g['name'];
}
$overview = $detail['overview'] ?? '暂无简介';

// 导演和演员
$credits = $detail['credits'] ?? array();
$cast = array_slice($credits['cast'] ?? array(), 0, 16);
$directors = array();
$writers = array();
if (!empty($credits['crew'])) {
    foreach ($credits['crew'] as $c) {
        if ($c['job'] === 'Director') $directors[] = $c['name'];
        if (in_array($c['job'], array('Writer', 'Screenplay'))) $writers[] = $c['name'];
    }
}
$directors = array_slice($directors, 0, 3);
$writers = array_slice($writers, 0, 3);

$seasons = array();
if ($type !== 'movie' && !empty($detail['seasons'])) {
    $seasons = $detail['seasons'];
}

// 推荐/相似
$recommendations = array_slice(($detail['recommendations']['results'] ?? array()), 0, 12);
$similar = array_slice(($detail['similar']['results'] ?? array()), 0, 12);

// 是否收藏
$favorited = false;
if (Auth::isLoggedIn()) {
    $db = Database::getInstance();
    $exists = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", array($_SESSION['user_id'], $id, $type));
    if ($exists) $favorited = true;
}
?>

<div class="detail-hero" <?php if ($backdrop): ?>style="background-image:url('<?php echo e($backdrop); ?>'); background-size:cover; background-position:center;"<?php endif; ?>>
    <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(15,15,26,0.3) 0%, rgba(15,15,26,0.85) 60%, var(--bg-primary) 100%);"></div>
    <div class="detail-wrapper">
        <div class="detail-poster">
            <?php if ($poster): ?>
                <img src="<?php echo e($poster); ?>" alt="<?php echo e($title); ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);">无海报</div>
            <?php endif; ?>
        </div>
        <div class="detail-info">
            <h1><?php echo e($title); ?></h1>
            <?php if ($originalTitle && $originalTitle !== $title): ?>
                <div style="color:var(--text-muted); font-size: 14px; margin-bottom: 12px;"><?php echo e($originalTitle); ?></div>
            <?php endif; ?>
            
            <div style="display:flex; align-items:center; gap: 20px; margin-bottom: 10px; flex-wrap: wrap;">
                <span style="display:inline-flex; align-items:center; gap:6px; color:#fbbf24; font-weight:800; font-size:20px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php echo number_format($rating, 1); ?>
                    <span style="color:var(--text-muted);font-weight:500;font-size:14px;">(<?php echo number_format($voteCount); ?>人评分)</span>
                </span>
            </div>

            <div class="detail-tags">
                <?php foreach ($genres as $g): ?>
                    <span class="detail-tag"><?php echo e($g); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="detail-meta-grid">
                <?php if ($year): ?>
                <div>
                    <div class="detail-meta-item-label">年份</div>
                    <div class="detail-meta-item-value"><?php echo e($year); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($runtimeStr): ?>
                <div>
                    <div class="detail-meta-item-label">片长</div>
                    <div class="detail-meta-item-value"><?php echo e($runtimeStr); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($detail['status'])): ?>
                <div>
                    <div class="detail-meta-item-label">状态</div>
                    <div class="detail-meta-item-value"><?php echo $detail['status'] === 'Released' ? '已上映' : ($detail['status'] === 'Returning Series' ? '连载中' : e($detail['status'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($detail['number_of_seasons'])): ?>
                <div>
                    <div class="detail-meta-item-label">季数</div>
                    <div class="detail-meta-item-value"><?php echo intval($detail['number_of_seasons']); ?>季</div>
                </div>
                <?php endif; ?>
                <?php if (!empty($detail['number_of_episodes'])): ?>
                <div>
                    <div class="detail-meta-item-label">集数</div>
                    <div class="detail-meta-item-value"><?php echo intval($detail['number_of_episodes']); ?>集</div>
                </div>
                <?php endif; ?>
                <?php if (!empty($detail['original_language'])): ?>
                <div>
                    <div class="detail-meta-item-label">原音语言</div>
                    <div class="detail-meta-item-value"><?php echo e(strtoupper($detail['original_language'])); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- 语言切换：普通话 / 原话  -->
            <div style="margin: 22px 0 10px; display:flex; align-items:center; gap:12px; flex-wrap: wrap;">
                <span style="font-weight:600; color:var(--text-secondary); font-size: 14px;">播放语言:</span>
                <div class="lang-switcher">
                    <button class="lang-btn active" data-lang="zh">普通话配音</button>
                    <button class="lang-btn" data-lang="orig">原版原声</button>
                </div>
            </div>

            <div id="fav"></div>
            <div class="detail-actions">
                <a href="player.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    立即播放
                </a>
                <button class="btn <?php echo $favorited ? 'btn-primary' : 'btn-outline'; ?> btn-lg" data-favorite='{"media_id":<?php echo $id; ?>,"media_type":"<?php echo e($type); ?>","media_title":"<?php echo e($title); ?>","media_poster":"<?php echo e($poster); ?>"}'>
                    <?php if ($favorited): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        <span class="fav-text">已收藏</span>
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        <span class="fav-text">收藏</span>
                    <?php endif; ?>
                </button>
                <button class="btn btn-ghost btn-lg" onclick="toast('已分享到剪贴板');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    分享
                </button>
            </div>

            <h3 class="detail-overview-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                剧情简介
            </h3>
            <p class="detail-overview"><?php echo e($overview); ?></p>

            <!-- 季选择 -->
            <?php if (!empty($seasons)): ?>
                <h3 class="detail-overview-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    选择季
                </h3>
                <div class="seasons-tabs">
                    <?php foreach ($seasons as $idx => $s): ?>
                        <button class="season-tab <?php echo $idx === 0 ? 'active' : ''; ?>" data-season="season-<?php echo $s['season_number']; ?>">
                            <?php echo e($s['name']); ?>
                            <?php if (!empty($s['episode_count'])): ?>
                                <span style="opacity:0.7; font-weight:400; margin-left:4px;">(<?php echo intval($s['episode_count']); ?>集)</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($seasons as $idx => $s): ?>
                    <div data-season-panel="season-<?php echo $s['season_number']; ?>" style="display: <?php echo $idx === 0 ? '' : 'none'; ?>; margin-bottom: 20px;">
                        <div style="display:flex; gap:20px; padding: 20px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; margin-bottom: 16px; align-items: flex-start;">
                            <?php
                                $sPoster = TMDB::getImageUrl($s['poster_path'] ?? '', 'w200');
                            ?>
                            <?php if ($sPoster): ?>
                                <img src="<?php echo e($sPoster); ?>" style="width:120px; border-radius: 10px; flex-shrink:0;" alt="">
                            <?php endif; ?>
                            <div>
                                <h4 style="font-size:18px; font-weight:800; margin-bottom: 6px;"><?php echo e($s['name']); ?></h4>
                                <div style="color:var(--text-muted); font-size:13px; margin-bottom: 10px;">
                                    首播: <?php echo e($s['air_date'] ?? '未知'); ?>
                                    <?php if (!empty($s['vote_average'])): ?> · 评分 <?php echo number_format($s['vote_average'], 1); ?><?php endif; ?>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 14px; line-height: 1.7;">
                                    <?php echo e($s['overview'] ?: '暂无简介'); ?>
                                </div>
                            </div>
                        </div>
                        <h4 style="font-weight:700; margin-bottom: 10px;">选择集数开始播放</h4>
                        <div class="episodes-grid">
                            <?php
                                $numEp = intval($s['episode_count'] ?? 0);
                                $maxEp = max(1, min($numEp ?: 24, 50));
                                for ($ep = 1; $ep <= $maxEp; $ep++):
                            ?>
                                <a href="player.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $s['season_number']; ?>&episode=<?php echo $ep; ?>" class="episode-btn">
                                    <div>第<?php echo $ep; ?>集</div>
                                    <div class="ep-sub">S<?php echo $s['season_number']; ?>E<?php echo $ep; ?></div>
                                </a>
                            <?php endfor; ?>
                            <?php if (!$numEp): ?>
                                <a href="player.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="episode-btn" style="grid-column: span 3;">
                                    <div>立即播放</div>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">

    <!-- 电影：直接按钮 -->
    <?php if ($type === 'movie'): ?>
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-title-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg></span>
                    播放源
                </h2>
            </div>
            <?php
            $sources = TMDB::getPlaySources();
            if ($sources):
            ?>
            <div class="source-tabs" data-sources-list>
                <?php foreach ($sources as $idx => $src): ?>
                    <div class="source-tab <?php echo $idx === 0 ? 'active' : ''; ?>" data-source="<?php echo $src['id']; ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?php echo e($src['name']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="player.php?id=<?php echo $id; ?>&type=movie" class="btn btn-primary btn-lg" style="margin-top:10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                立即播放
            </a>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- 演职员 -->
    <?php if (!empty($directors) || !empty($writers) || !empty($cast)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                演职员
            </h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <?php if (!empty($directors)): ?>
            <div style="padding: 16px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;">
                <div style="color:var(--text-muted); font-size:12px; margin-bottom:6px;">导演</div>
                <div style="font-weight:700;"><?php echo e(implode(' / ', $directors)); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($writers)): ?>
            <div style="padding: 16px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;">
                <div style="color:var(--text-muted); font-size:12px; margin-bottom:6px;">编剧</div>
                <div style="font-weight:700;"><?php echo e(implode(' / ', $writers)); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <h3 style="font-size:16px; font-weight:800; margin-bottom: 14px;">主演</h3>
        <div class="credits-scroll">
            <?php foreach ($cast as $c):
                $photo = TMDB::getImageUrl($c['profile_path'] ?? '', 'w185');
            ?>
                <div class="cast-card">
                    <div class="cast-photo">
                        <?php if ($photo): ?>
                            <img src="<?php echo e($photo); ?>" alt="<?php echo e($c['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;color:var(--text-muted); font-size:14px;"><?php echo e(mb_substr($c['name'],0,1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="cast-name"><?php echo e($c['name']); ?></div>
                    <div class="cast-role"><?php echo e($c['character'] ?? ''); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- 相似推荐 -->
    <?php if (!empty($recommendations) || !empty($similar)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a9.993 9.993 0 0 0-9.95 9h11.644L12 5.5 8.306 11H2.05A9.994 9.994 0 0 0 12 22a9.99 9.99 0 0 0 9.95-9h-11.644L12 18.5 15.694 13H21.95A9.994 9.994 0 0 0 12 2Z"/></svg></span>
                猜你喜欢
            </h2>
        </div>
        <div class="media-grid">
            <?php
            $combined = array_merge($recommendations, $similar);
            $seen = array();
            foreach ($combined as $m):
                if (in_array($m['id'], $seen)) continue;
                $seen[] = $m['id'];
                $mType = $m['media_type'] ?? ($type === 'movie' ? 'movie' : 'tv');
                $mt = $m['title'] ?? $m['name'] ?? '';
                $mp = TMDB::getImageUrl($m['poster_path'] ?? '', 'w342');
                $mr = $m['vote_average'] ?? 0;
                $my = '';
                $mds = $m['release_date'] ?? ($m['first_air_date'] ?? '');
                if ($mds) $my = substr($mds, 0, 4);
            ?>
            <div class="media-card" onclick="window.location='detail.php?id=<?php echo $m['id']; ?>&type=<?php echo $mType; ?>'">
                <div class="media-poster">
                    <?php if ($mp): ?>
                        <img src="<?php echo e($mp); ?>" alt="<?php echo e($mt); ?>" loading="lazy">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:var(--bg-tertiary);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:14px;">无海报</div>
                    <?php endif; ?>
                    <?php if ($mr > 0): ?>
                        <div class="media-rating"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php echo number_format($mr, 1); ?></div>
                    <?php endif; ?>
                    <div class="media-play-overlay"><div class="media-play-btn"></div></div>
                </div>
                <div class="media-info">
                    <div class="media-title"><?php echo e($mt); ?></div>
                    <div class="media-meta"><?php if ($my): ?><span><?php echo e($my); ?></span><?php endif; ?></div>
                </div>
            </div>
            <?php
                if (count($seen) >= 12) break;
            endforeach;
            ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
