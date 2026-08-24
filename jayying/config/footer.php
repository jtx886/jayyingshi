<?php
$site_name = isset($site_name) ? $site_name : 'JayYing';
$site_url = isset($site_url) ? $site_url : '';
$current_year = date('Y');
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-section">
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="footer-logo-icon"></div>
                    <span class="footer-logo-text"><?php echo $site_name; ?></span>
                </div>
                <p class="footer-desc">为影迷提供优质的影视观看体验，汇聚全球精彩内容。</p>
            </div>
            <div class="footer-links-group">
                <div class="footer-links">
                    <h4>导航</h4>
                    <ul>
                        <li><a href="<?php echo $site_url; ?>/">首页</a></li>
                        <li><a href="<?php echo $site_url; ?>/movies">电影</a></li>
                        <li><a href="<?php echo $site_url; ?>/tv">电视剧</a></li>
                        <li><a href="<?php echo $site_url; ?>/variety">综艺</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>支持</h4>
                    <ul>
                        <li><a href="<?php echo $site_url; ?>/feedback">反馈</a></li>
                        <li><a href="<?php echo $site_url; ?>/contact">联系我们</a></li>
                        <li><a href="<?php echo $site_url; ?>/faq">常见问题</a></li>
                        <li><a href="<?php echo $site_url; ?>/report">侵权投诉</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>法律</h4>
                    <ul>
                        <li><a href="<?php echo $site_url; ?>/terms">服务条款</a></li>
                        <li><a href="<?php echo $site_url; ?>/privacy">隐私政策</a></li>
                        <li><a href="<?php echo $site_url; ?>/copyright">版权声明</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-friend-links">
            <h4>友情链接</h4>
            <ul>
                <li><a href="https://www.themoviedb.org" target="_blank" rel="nofollow">TMDB</a></li>
                <li><a href="https://www.imdb.com" target="_blank" rel="nofollow">IMDb</a></li>
                <li><a href="https://movie.douban.com" target="_blank" rel="nofollow">豆瓣电影</a></li>
                <li><a href="https://www.bilibili.com" target="_blank" rel="nofollow">哔哩哔哩</a></li>
                <li><a href="https://github.com" target="_blank" rel="nofollow">GitHub</a></li>
            </ul>
        </div>

        <div class="footer-social">
            <div class="social-icons">
                <a href="#" class="social-icon" title="微信">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.743-3.753-4.689-6.32-9.596-6.32zM5.785 5.991c.642 0 1.162.529 1.162 1.18 0 .65-.52 1.178-1.162 1.178-.642 0-1.162-.528-1.162-1.178 0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18 0 .65-.52 1.178-1.162 1.178-.642 0-1.162-.528-1.162-1.178 0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.024-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.27-.027-.407-.03zm-2.53 3.274c.535 0 .969.44.969.982 0 .543-.434.983-.969.983a.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982zm4.844 0c.535 0 .969.44.969.982 0 .543-.434.983-.969.983a.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/></svg>
                </a>
                <a href="#" class="social-icon" title="微博">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10.098 20c-4.038 0-7.312-1.997-7.312-6.2 0-2.128 1.147-4.353 3.06-6.09C8.338 4.354 10.34 3.6 11.442 4.3c.517.325.626.942.416 1.55-.146.425.45.216.45.216-1.39-.605-2.636-.564-3.016-.03-.19.265-.243.595-.15.93.186.64 1.66 1.055 2.64.33 0 0 .39.234.29.564-.12.418.28.138.28.138C6.534 8.39 4.47 9.897 4.47 12.23c0 2.666 3.274 4.3 6.452 4.3 4.15 0 6.928-2.41 6.928-4.3 0-.012-.002-.024-.002-.038 0-.014-.003-.026-.005-.038 0-.006.002-.012.003-.02 0-.027-.007-.055-.022-.082 0 0-.003-.006-.007-.013a.488.488 0 0 0-.028-.045c-.005-.007-.011-.013-.017-.02a.327.327 0 0 0-.036-.033c-.012-.01-.026-.018-.04-.027a.512.512 0 0 0-.05-.033c-.02-.012-.042-.023-.063-.034a.62.62 0 0 0-.075-.037c-.029-.013-.06-.025-.09-.036a.68.68 0 0 0-.106-.041c-.04-.014-.083-.027-.125-.039-.05-.015-.102-.028-.155-.04a1.437 1.437 0 0 0-.19-.029c-.074-.01-.15-.018-.226-.024a2.23 2.23 0 0 0-.26-.014c-.093-.003-.186-.004-.28 0a2.18 2.18 0 0 0-.54.105 2.198 2.198 0 0 0-.678.333 2.226 2.226 0 0 0-.47.515c-.122.197-.213.42-.267.662-.054.242-.07.5-.046.745a2.17 2.17 0 0 0 .213.668c.09.185.212.35.358.487.146.137.315.244.494.307.18.064.37.084.557.06a1.89 1.89 0 0 0 .517-.167 1.855 1.855 0 0 0 .418-.325 1.82 1.82 0 0 0 .28-.478 1.79 1.79 0 0 0 .107-.574c.01-.196-.012-.393-.065-.577-.053-.184-.136-.355-.245-.503a1.53 1.53 0 0 0-.396-.372 1.426 1.426 0 0 0-.503-.19 1.377 1.377 0 0 0-.607.045c-.195.067-.37.18-.516.333a1.29 1.29 0 0 0-.31.526 1.243 1.243 0 0 0-.008.66c.048.176.135.337.253.466.119.13.264.224.423.274.16.05.33.056.493.014a1.67 1.67 0 0 0 .588-.286c.17-.13.313-.316.416-.534.103-.218.16-.46.166-.707zm7.552-10.892a4.037 4.037 0 0 0-4.019-4.054 4.034 4.034 0 0 0-4.019 4.054 4.035 4.035 0 0 0 4.02 4.055 4.037 4.037 0 0 0 4.018-4.055zM8.727 13.884c-1.508.15-2.817-.41-2.935-1.257-.118-.848.976-1.647 2.486-1.798 1.51-.153 2.817.409 2.936 1.258.118.848-.975 1.647-2.487 1.797zm8.81-6.677c-.324.335-.797.507-1.292.507-.495 0-.969-.172-1.292-.507a1.73 1.73 0 0 1-.536-1.256c0-.476.195-.932.536-1.266.323-.334.797-.506 1.292-.506.495 0 .968.172 1.292.506.34.334.536.79.536 1.266 0 .475-.196.931-.536 1.256z"/></svg>
                </a>
                <a href="#" class="social-icon" title="GitHub">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.6.113.793-.26.793-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 17.07 3.633 16.7 3.633 16.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.627-5.373-12-12-12z"/></svg>
                </a>
                <a href="#" class="social-icon" title="邮件">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </a>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo $current_year; ?> <?php echo $site_name; ?>. 保留所有权利.</p>
            <p class="footer-warning">本站所有内容均来自互联网，仅供学习交流使用，版权归原作者所有.</p>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background: var(--bg-secondary);
    border-top: 1px solid var(--border-color);
    padding: 48px 0 24px;
    margin-top: 60px;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
}

.footer-section {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 48px;
    margin-bottom: 40px;
}

.footer-brand {
    max-width: 320px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.footer-logo-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--primary) 0%, #0ea5e9 100%);
    border-radius: 8px;
    position: relative;
}

.footer-logo-icon::before {
    content: '';
    position: absolute;
    width: 14px;
    height: 14px;
    border: 2px solid #fff;
    border-radius: 3px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(45deg);
}

.footer-logo-text {
    font-size: 18px;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary) 0%, #0ea5e9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.footer-desc {
    color: var(--text-muted);
    font-size: 14px;
    line-height: 1.6;
}

.footer-links-group {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.footer-links h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 16px;
}

.footer-links ul {
    list-style: none;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    color: var(--text-muted);
    font-size: 14px;
    transition: color 0.2s;
}

.footer-links a:hover {
    color: var(--primary);
}

.footer-friend-links {
    padding: 24px 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 24px;
}

.footer-friend-links h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 16px;
}

.footer-friend-links ul {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
}

.footer-friend-links a {
    color: var(--text-muted);
    font-size: 14px;
    transition: color 0.2s;
}

.footer-friend-links a:hover {
    color: var(--primary);
}

.footer-social {
    margin-bottom: 32px;
}

.social-icons {
    display: flex;
    gap: 12px;
}

.social-icon {
    width: 40px;
    height: 40px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: all 0.2s;
}

.social-icon:hover {
    background: var(--primary);
    color: #0b1019;
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--primary-glow);
}

.social-icon svg {
    width: 20px;
    height: 20px;
}

.footer-bottom {
    text-align: center;
    padding-top: 24px;
}

.footer-bottom p {
    color: var(--text-muted);
    font-size: 13px;
    margin-bottom: 6px;
}

.footer-warning {
    color: var(--text-muted);
    font-size: 12px;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .site-footer {
        padding: 32px 0 20px;
        margin-top: 40px;
    }
    .footer-container {
        padding: 0 16px;
    }
    .footer-section {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .footer-links-group {
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    .footer-friend-links ul {
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .footer-links-group {
        grid-template-columns: 1fr;
    }
}
</style>
</body>
</html>
