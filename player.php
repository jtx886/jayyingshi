<?php
require_once __DIR__ . '/includes/auth.php';
// 播放页需要登录
Auth::requireLogin();
require_once __DIR__ . '/includes/tmdb.php';
require_once __DIR__ . '/includes/settings.php';

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'movie';
$season = intval($_GET['season'] ?? 1);
$episode = intval($_GET['episode'] ?? 1);

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
    redirect('index.php');
}

$title = $detail['title'] ?? ($detail['name'] ?? '');
$pageTitle = '播放: ' . $title;
include __DIR__ . '/header.php';

$sources = TMDB::getPlaySources();
$parserUrl = getPlayerParser();

// 默认使用第一个播放源并搜索关键词
$sourceKeyword = $title;
$seasonSuffix = '';
if ($type !== 'movie') {
    $seasonSuffix = " 第{$season}季 第{$episode}集";
    $sourceKeyword .= $seasonSuffix;
}

$epTitle = '';
if ($type !== 'movie') {
    $epTitle = "第{$season}季 · 第{$episode}集";
    if (!empty($detail['seasons'])) {
        foreach ($detail['seasons'] as $s) {
            if ($s['season_number'] == $season) {
                $epTitle = "{$s['name']} · 第{$episode}集";
                break;
            }
        }
    }
}
?>

<div class="container">
    <div class="player-wrapper">
        <div class="player-container" id="playerContainer">
            <iframe id="playerFrame" src="about:blank" allowfullscreen allow="autoplay; encrypted-media; fullscreen; picture-in-picture"></iframe>
        </div>

        <div class="player-info">
            <div class="player-info-left">
                <h2><?php echo e($title); ?></h2>
                <?php if ($epTitle): ?>
                    <div class="player-episode-title"><?php echo e($epTitle); ?></div>
                <?php endif; ?>
            </div>
            <div class="detail-actions" style="margin: 0;">
                <button onclick="toast('线路切换中');" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                    换线路
                </button>
                <a href="detail.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="btn btn-ghost btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    返回详情
                </a>
            </div>
        </div>

        <!-- 选择季 -->
        <?php if ($type !== 'movie' && !empty($detail['seasons'])): ?>
            <div style="margin: 20px 0;">
                <div style="font-weight:700; margin-bottom: 10px; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    选择季
                </div>
                <div class="seasons-tabs">
                    <?php foreach ($detail['seasons'] as $s):
                        $active = $s['season_number'] == $season;
                    ?>
                        <a href="player.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $s['season_number']; ?>&episode=1" 
                           class="season-tab <?php echo $active ? 'active' : ''; ?>" 
                           data-season="s<?php echo $s['season_number']; ?>">
                            <?php echo e($s['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 选择集 -->
        <?php if ($type !== 'movie' && !empty($detail['seasons'])): ?>
            <div style="margin: 20px 0;">
                <div style="font-weight:700; margin-bottom: 10px; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m10 7 5 5-5 5V7z"/><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/></svg>
                    选择集
                </div>
                <div class="episodes-grid">
                    <?php
                        $totalEp = 0;
                        foreach ($detail['seasons'] as $s) {
                            if ($s['season_number'] == $season) {
                                $totalEp = intval($s['episode_count'] ?? 0);
                                break;
                            }
                        }
                        if (!$totalEp) $totalEp = 12;
                        for ($ep = 1; $ep <= $totalEp; $ep++):
                            $active = $ep == $episode;
                    ?>
                        <a href="player.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $season; ?>&episode=<?php echo $ep; ?>"
                           class="episode-btn <?php echo $active ? 'active' : ''; ?>">
                            <div>第<?php echo $ep; ?>集</div>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 播放源选择 -->
        <div style="margin: 24px 0;">
            <div style="font-weight:700; margin-bottom: 10px; display:flex; align-items:center; gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                选择播放源
            </div>
            <div class="source-tabs">
                <?php foreach ($sources as $idx => $src): ?>
                    <button class="source-tab <?php echo $idx === 0 ? 'active' : ''; ?>"
                            data-source-key="<?php echo $src['id']; ?>"
                            data-url="<?php echo e($src['url']); ?>"
                            data-keyword="<?php echo e($sourceKeyword); ?>"
                            onclick="playFromSource(this);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <?php echo e($src['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div id="playTracker" data-media-id="<?php echo $id; ?>" data-media-type="<?php echo e($type); ?>" data-episode="<?php echo $type === 'movie' ? 0 : $episode; ?>" data-seconds="0"></div>

<script>
    // 播放：通过 yyzy 接口搜索，提取第一个视频链接，套入解析播放器
    var parserUrl = <?php echo json_encode($parserUrl); ?>;

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    var tryCount = 0;
    function retryLoading(iframe) {
        tryCount++;
        if (tryCount > 2) return;
        setTimeout(function() {
            iframe.src = iframe.src;
            toast('重新加载播放源...');
            setTimeout(function() { retryLoading(iframe); }, 15000);
        }, 15000);
    }

    function playFromSource(btn) {
        // 更新UI
        document.querySelectorAll('.source-tab').forEach(function(t) {
            t.classList.toggle('active', t === btn);
        });
        var sourceUrl = btn.getAttribute('data-url');
        var keyword = btn.getAttribute('data-keyword');
        var iframe = document.getElementById('playerFrame');
        var container = document.getElementById('playerContainer');
        
        // 加载中提示
        container.innerHTML = `
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;background:#000;color:#fff;">
                <div style="width:54px;height:54px;border:4px solid rgba(255,255,255,0.15);border-top-color:var(--theme-color);border-radius:50%;animation:spin 1s linear infinite;"></div>
                <div style="color:#a0a0b8;font-size:14px;">正在解析播放源，耐心等待...</div>
            </div>
            <iframe id="playerFrame" style="display:none;" src="about:blank" allowfullscreen allow="autoplay; encrypted-media; fullscreen; picture-in-picture"></iframe>
            <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
        `;
        var newIframe = container.querySelector('#playerFrame');
        
        // 调用 yyzy API
        var apiUrl = sourceUrl + (sourceUrl.indexOf('?') > -1 ? '&' : '?') + 'wd=' + encodeURIComponent(keyword);
        fetch(apiUrl)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var direct = '';
                var foundIndex = 0;
                if (data && data.list && data.list.length > 0) {
                    // 遍历找到播放链接
                    loop:
                    for (var i = 0; i < data.list.length; i++) {
                        var item = data.list[i];
                        if (!item.vod_play_url) continue;
                        var playParts = item.vod_play_url.split('$$$').filter(Boolean);
                        for (var j = 0; j < playParts.length; j++) {
                            var chunk = playParts[j];
                            var eps = chunk.split('#').filter(Boolean);
                            // 选择与当前集匹配的
                            if (<?php echo $type === 'movie' ? 'true' : 'false'; ?>) {
                                // 电影取第一个
                                if (eps.length > 0) {
                                    var sp = eps[0].split('$');
                                    if (sp.length >= 2) { direct = sp[1]; break loop; }
                                }
                            } else {
                                // 剧集尝试根据集数找
                                var targetEp = <?php echo $episode; ?>;
                                for (var k = 0; k < eps.length; k++) {
                                    var ep = eps[k];
                                    var epSplit = ep.split('$');
                                    var label = epSplit[0];
                                    var url = epSplit[1] || '';
                                    if (!url) continue;
                                    // 解析集数：类似"第01集"
                                    var match = label.match(/(\d+)/);
                                    if (match && parseInt(match[1]) === targetEp) {
                                        direct = url;
                                        foundIndex = 1;
                                        break loop;
                                    }
                                }
                            }
                        }
                    }
                    // 如果没找到指定集，取第一个可用的
                    if (!direct && data.list.length > 0) {
                        var item0 = data.list[0];
                        if (item0.vod_play_url) {
                            var parts = item0.vod_play_url.split('$$$').filter(Boolean);
                            if (parts.length > 0) {
                                var lines = parts[0].split('#').filter(Boolean);
                                if (lines.length > 0) {
                                    var s = lines[<?php echo max(0, $episode - 1); ?>] || lines[0];
                                    var ssp = s.split('$');
                                    if (ssp.length >= 2) direct = ssp[1];
                                }
                            }
                        }
                    }
                }
                if (direct) {
                    var target = parserUrl + encodeURIComponent(direct);
                    setTimeout(function() {
                        container.querySelector('div[style*="position:absolute"]')?.remove();
                        newIframe.style.display = 'block';
                        newIframe.src = target;
                        tryCount = 0;
                        retryLoading(newIframe);
                        toast('播放源加载成功', 'success');
                    }, 400);
                } else {
                    throw new Error('未找到播放链接');
                }
            })
            .catch(function(err) {
                container.innerHTML = `
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;padding:40px;text-align:center;background:#000;">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div style="font-size:18px; font-weight:700;">暂未找到该播放源的匹配链接</div>
                        <div style="color:#a0a0b8; max-width:500px;">关键词：` + escapeHtml(keyword) + `<br>请尝试切换其他播放源，或者稍后再试</div>
                        <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap;justify-content:center;">
                            <button onclick="document.querySelectorAll('.source-tab').forEach(function(t){if(!t.classList.contains('active')){playFromSource(t);throw 0;}});" class="btn btn-primary" style="padding:10px 20px;">尝试下一线路</button>
                            <a href="detail.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="btn btn-outline" style="padding:10px 20px;">返回详情页</a>
                        </div>
                    </div>
                `;
            });
    }

    // 自动播放第一个源
    document.addEventListener('DOMContentLoaded', function() {
        var first = document.querySelector('.source-tab');
        if (first) playFromSource(first);
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>
