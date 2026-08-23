<?php
$simple = isset($simple_footer) ? (bool)$simple_footer : false;
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Jay影视';
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$siteFooter = function_exists('getSetting') ? getSetting('site_footer', '') : '';
if (empty($siteFooter)) {
    $siteFooter = '&copy; ' . date('Y') . ' ' . e($siteName) . ' 版权所有';
}
?>
<?php if (!$simple): ?>
    <footer class="site-footer" style="
        background: #f8fafc;
        border-top: 1px solid #eee;
        padding: 30px 20px;
        margin-top: 40px;
    ">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center; color: #94a3b8; font-size: 13px; line-height: 1.8;">
            <p><?php echo $siteFooter; ?></p>
        </div>
    </footer>
<?php endif; ?>
</body>
</html>
