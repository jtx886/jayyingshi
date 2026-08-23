<?php
/**
 * Jay影视网站 安装程序
 * 兼容 PHP 5.6+
 * 使用 PDO 连接 SQLite
 */

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbFile = __DIR__ . '/data/jayying.db';
$sqlFile = __DIR__ . '/data/init.sql';
$messages = array();
$hasError = false;

function addMessage($type, $text) {
    global $messages;
    $messages[] = array('type' => $type, 'text' => $text);
}

function checkRequirements() {
    $ok = true;

    if (!extension_loaded('pdo')) {
        addMessage('error', '未安装 PDO 扩展，请先安装 PDO 和 pdo_sqlite 扩展');
        $ok = false;
    }

    if (!extension_loaded('pdo_sqlite')) {
        addMessage('error', '未安装 pdo_sqlite 扩展，请先安装 pdo_sqlite 扩展');
        $ok = false;
    }

    if (!extension_loaded('mbstring')) {
        addMessage('warning', '未安装 mbstring 扩展，建议安装以获得更好的中文支持');
    }

    if (version_compare(PHP_VERSION, '5.6.0', '<')) {
        addMessage('error', 'PHP 版本过低，要求 PHP 5.6 或更高版本，当前版本：' . PHP_VERSION);
        $ok = false;
    } else {
        addMessage('success', 'PHP 版本检查通过：' . PHP_VERSION);
    }

    return $ok;
}

function checkFiles() {
    global $sqlFile, $dbFile;
    $ok = true;

    if (!file_exists($sqlFile)) {
        addMessage('error', '找不到初始化 SQL 文件：' . $sqlFile);
        $ok = false;
    } else {
        addMessage('success', '找到初始化 SQL 文件：' . $sqlFile);
    }

    $dataDir = dirname($dbFile);
    if (!is_dir($dataDir)) {
        if (!@mkdir($dataDir, 0755, true)) {
            addMessage('error', '无法创建数据目录：' . $dataDir . '，请检查目录权限');
            $ok = false;
        } else {
            addMessage('success', '已创建数据目录：' . $dataDir);
        }
    }

    if (!is_writable($dataDir)) {
        addMessage('error', '数据目录不可写：' . $dataDir . '，请设置目录权限为 755 或 777');
        $ok = false;
    } else {
        addMessage('success', '数据目录可写：' . $dataDir);
    }

    if (file_exists($dbFile)) {
        addMessage('warning', '数据库文件已存在：' . $dbFile . '，将尝试在现有数据库上执行初始化（使用 IF NOT EXISTS）');
        if (!is_writable($dbFile)) {
            addMessage('error', '数据库文件不可写：' . $dbFile . '，请设置文件权限');
            $ok = false;
        }
    }

    return $ok;
}

function splitSqlFile($sql) {
    $statements = array();
    $current = '';
    $inComment = false;
    $inString = false;
    $stringChar = '';
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $nextChar = isset($sql[$i + 1]) ? $sql[$i + 1] : '';

        if ($inString) {
            $current .= $char;
            if ($char === '\\' && $nextChar === $stringChar) {
                $current .= $nextChar;
                $i++;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
            continue;
        }

        if ($inComment) {
            if ($char === "\n") {
                $inComment = false;
            }
            continue;
        }

        if ($char === '-' && $nextChar === '-') {
            $inComment = true;
            continue;
        }

        if ($char === "'" || $char === '"') {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
            continue;
        }

        if ($char === ';') {
            $trimmed = trim($current);
            if (!empty($trimmed)) {
                $statements[] = $trimmed . ';';
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $trimmed = trim($current);
    if (!empty($trimmed)) {
        $statements[] = $trimmed . (substr($trimmed, -1) === ';' ? '' : ';');
    }

    return $statements;
}

function executeSql(PDO $pdo, $sqlFile) {
    global $hasError;

    $sqlContent = file_get_contents($sqlFile);
    if ($sqlContent === false) {
        addMessage('error', '无法读取 SQL 文件：' . $sqlFile);
        return false;
    }

    $statements = splitSqlFile($sqlContent);
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;

    foreach ($statements as $index => $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) {
            continue;
        }

        try {
            $result = $pdo->exec($stmt);
            if ($result === false) {
                $errorInfo = $pdo->errorInfo();
                addMessage('warning', 'SQL 语句 #' . ($index + 1) . ' 执行跳过：' . $errorInfo[2]);
                $skipCount++;
            } else {
                $successCount++;
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'already exists') !== false) {
                addMessage('info', 'SQL 语句 #' . ($index + 1) . '：表/索引已存在，跳过');
                $skipCount++;
            } else {
                addMessage('error', 'SQL 语句 #' . ($index + 1) . ' 执行失败：' . $msg);
                addMessage('error', '语句内容：' . substr($stmt, 0, 200) . (strlen($stmt) > 200 ? '...' : ''));
                $errorCount++;
                $hasError = true;
            }
        }
    }

    addMessage('success', 'SQL 执行完成：成功 ' . $successCount . ' 条，跳过 ' . $skipCount . ' 条，失败 ' . $errorCount . ' 条');

    return $errorCount === 0;
}

function insertDefaultAdmin(PDO $pdo) {
    global $hasError;

    $adminEmail = 'admin@jaytv.com';
    $adminUsername = 'admin';
    $adminPassword = '101113';

    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $checkStmt->execute(array($adminEmail, $adminUsername));
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        addMessage('info', '管理员账号已存在，跳过插入');
        return true;
    }

    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        addMessage('error', '密码加密失败');
        $hasError = true;
        return false;
    }

    $now = time();
    $insertStmt = $pdo->prepare('
        INSERT INTO users (email, username, password, avatar, is_admin, is_banned, ban_start_time, ban_end_time, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    try {
        $result = $insertStmt->execute(array(
            $adminEmail,
            $adminUsername,
            $passwordHash,
            '',
            1,
            0,
            0,
            0,
            $now,
            $now
        ));

        if ($result) {
            addMessage('success', '默认管理员账号创建成功');
            addMessage('info', '管理员邮箱：' . $adminEmail);
            addMessage('info', '管理员用户名：' . $adminUsername);
            addMessage('info', '管理员密码：' . $adminPassword . '（请登录后及时修改）');
            return true;
        } else {
            $errorInfo = $insertStmt->errorInfo();
            addMessage('error', '管理员账号创建失败：' . $errorInfo[2]);
            $hasError = true;
            return false;
        }
    } catch (Exception $e) {
        addMessage('error', '管理员账号创建异常：' . $e->getMessage());
        $hasError = true;
        return false;
    }
}

function insertDefaultPlaySources(PDO $pdo) {
    global $hasError;

    $now = time();

    $sources = array(
        array(
            'name' => '默认播放源',
            'url' => 'https://jx.playerjy.com/?url=',
            'api_type' => 'yyzy',
            'is_default' => 1,
            'status' => 1,
            'sort_order' => 1
        ),
        array(
            'name' => '备用播放源一',
            'url' => 'https://jx.jsonplayer.com/player/?url=',
            'api_type' => 'yyzy',
            'is_default' => 0,
            'status' => 1,
            'sort_order' => 2
        ),
        array(
            'name' => '备用播放源二',
            'url' => 'https://jx.xmflv.com/?url=',
            'api_type' => 'yyzy',
            'is_default' => 0,
            'status' => 1,
            'sort_order' => 3
        )
    );

    $checkStmt = $pdo->query('SELECT COUNT(*) FROM play_sources');
    $count = $checkStmt ? intval($checkStmt->fetchColumn()) : 0;

    if ($count > 0) {
        addMessage('info', '播放源已存在，跳过插入（当前已有 ' . $count . ' 条记录）');
        return true;
    }

    $insertStmt = $pdo->prepare('
        INSERT INTO play_sources (name, url, api_type, is_default, status, sort_order, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $successCount = 0;
    foreach ($sources as $source) {
        try {
            $result = $insertStmt->execute(array(
                $source['name'],
                $source['url'],
                $source['api_type'],
                $source['is_default'],
                $source['status'],
                $source['sort_order'],
                $now,
                $now
            ));
            if ($result) {
                $successCount++;
            }
        } catch (Exception $e) {
            addMessage('warning', '播放源 "' . $source['name'] . '" 插入失败：' . $e->getMessage());
        }
    }

    if ($successCount > 0) {
        addMessage('success', '默认播放源插入完成，成功 ' . $successCount . ' 条');
        return true;
    } else {
        addMessage('error', '默认播放源插入失败');
        $hasError = true;
        return false;
    }
}

function insertDefaultSiteSettings(PDO $pdo) {
    global $hasError;

    $now = time();

    $settings = array(
        'site_name' => 'Jay影视',
        'site_description' => 'Jay影视 - 免费在线观看高清电影电视剧',
        'site_keywords' => 'Jay影视,免费电影,在线观看,高清电视剧',
        'theme_color' => '#5b5cff',
        'theme_color_secondary' => '#6c7a89',
        'record_per_page' => '20',
        'enable_register' => '1',
        'enable_comment' => '1',
        'default_player' => '1',
        'site_logo' => '',
        'site_icp' => '',
        'site_footer' => '© ' . date('Y') . ' Jay影视 版权所有',
        'maintenance_mode' => '0',
        'maintenance_message' => '网站维护中，请稍后访问...',
        'default_play_source' => '1'
    );

    $successCount = 0;
    $skipCount = 0;

    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM site_settings WHERE setting_key = ?');
    $insertStmt = $pdo->prepare('
        INSERT INTO site_settings (setting_key, setting_value, updated_at)
        VALUES (?, ?, ?)
    ');
    $updateStmt = $pdo->prepare('
        UPDATE site_settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?
    ');

    foreach ($settings as $key => $value) {
        try {
            $checkStmt->execute(array($key));
            $exists = intval($checkStmt->fetchColumn()) > 0;

            if ($exists) {
                $skipCount++;
            } else {
                $result = $insertStmt->execute(array($key, $value, $now));
                if ($result) {
                    $successCount++;
                }
            }
        } catch (Exception $e) {
            addMessage('warning', '网站设置 "' . $key . '" 操作失败：' . $e->getMessage());
        }
    }

    addMessage('success', '网站设置初始化完成：新增 ' . $successCount . ' 条，跳过 ' . $skipCount . ' 条');
    return true;
}

function insertDefaultAnnouncement(PDO $pdo) {
    $now = time();

    $checkStmt = $pdo->query('SELECT COUNT(*) FROM announcements');
    $count = $checkStmt ? intval($checkStmt->fetchColumn()) : 0;

    if ($count > 0) {
        addMessage('info', '公告已存在，跳过插入默认公告');
        return true;
    }

    $insertStmt = $pdo->prepare('
        INSERT INTO announcements (title, content, created_at, updated_at)
        VALUES (?, ?, ?, ?)
    ');

    try {
        $result = $insertStmt->execute(array(
            '欢迎使用Jay影视',
            '<p>欢迎来到Jay影视！本站免费提供高清电影、电视剧在线观看服务。</p><p>如遇播放问题，请尝试切换播放源。</p><p>祝您观影愉快！</p>',
            $now,
            $now
        ));
        if ($result) {
            addMessage('success', '默认公告插入成功');
            return true;
        }
    } catch (Exception $e) {
        addMessage('warning', '默认公告插入失败：' . $e->getMessage());
    }

    return true;
}

$isInstall = isset($_GET['action']) && $_GET['action'] === 'install';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jay影视 - 安装程序</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #5b5cff 0%, #7c4dff 100%);
            color: #fff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .message-list {
            list-style: none;
        }
        .message-list li {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.5;
        }
        .message-list li.success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .message-list li.error {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .message-list li.warning {
            background: #fffbeb;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        .message-list li.info {
            background: #eff6ff;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        .btn {
            display: inline-block;
            padding: 12px 40px;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #5b5cff 0%, #7c4dff 100%);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(91, 92, 255, 0.4);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-box p {
            font-size: 14px;
            line-height: 1.8;
            color: #475569;
            margin-bottom: 8px;
        }
        .info-box p:last-child {
            margin-bottom: 0;
        }
        .info-box strong {
            color: #1e293b;
        }
        .footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8fafc;
            color: #94a3b8;
            font-size: 13px;
        }
        .success-banner {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .success-banner h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .error-banner {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .error-banner h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .step {
            padding: 8px 16px;
            background: #f1f5f9;
            border-radius: 20px;
            font-size: 13px;
            color: #64748b;
        }
        .step.done {
            background: #dcfce7;
            color: #166534;
        }
        .step.active {
            background: #5b5cff;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Jay影视</h1>
            <p>网站安装程序 v1.0</p>
        </div>
        <div class="content">

            <?php if (!$isInstall): ?>

                <div class="info-box">
                    <p><strong>欢迎使用 Jay影视 安装程序！</strong></p>
                    <p>本程序将引导您完成 Jay影视 网站的数据库初始化工作。</p>
                    <p>安装过程将执行以下操作：</p>
                    <p>&nbsp;&nbsp;• 创建 SQLite 数据库文件</p>
                    <p>&nbsp;&nbsp;• 初始化所有数据表（共11个表）</p>
                    <p>&nbsp;&nbsp;• 创建默认管理员账号</p>
                    <p>&nbsp;&nbsp;• 添加默认播放源</p>
                    <p>&nbsp;&nbsp;• 初始化网站设置（包含主题颜色等）</p>
                </div>

                <div class="section">
                    <div class="section-title">📋 环境检查</div>
                    <ul class="message-list">
                        <?php
                        checkRequirements();
                        checkFiles();
                        foreach ($messages as $msg):
                        ?>
                        <li class="<?php echo htmlspecialchars($msg['type']); ?>">
                            <?php echo htmlspecialchars($msg['text']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="actions">
                    <form method="get" action="">
                        <input type="hidden" name="action" value="install">
                        <button type="submit" class="btn btn-primary"
                            <?php echo $hasError ? 'disabled' : ''; ?>>
                            <?php echo $hasError ? '环境检查未通过' : '🚀 开始安装'; ?>
                        </button>
                    </form>
                </div>

            <?php else: ?>

                <?php
                $messages = array();
                $hasError = false;

                $envOk = checkRequirements() && checkFiles();

                if ($envOk) {
                    try {
                        $pdo = new PDO('sqlite:' . $dbFile);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                        addMessage('success', '数据库连接成功：' . $dbFile);
                    } catch (Exception $e) {
                        addMessage('error', '数据库连接失败：' . $e->getMessage());
                        $hasError = true;
                        $pdo = null;
                    }

                    if ($pdo) {
                        echo '<div class="step-indicator">';
                        echo '<span class="step active">1. 环境检查</span>';
                        echo '<span class="step">2. 创建数据表</span>';
                        echo '<span class="step">3. 管理员账号</span>';
                        echo '<span class="step">4. 默认播放源</span>';
                        echo '<span class="step">5. 网站设置</span>';
                        echo '</div>';

                        echo '<div class="section"><div class="section-title">📋 安装进度</div><ul class="message-list">';
                        foreach ($messages as $msg) {
                            echo '<li class="' . htmlspecialchars($msg['type']) . '">' . htmlspecialchars($msg['text']) . '</li>';
                        }
                        echo '</ul></div>';

                        echo '<div class="section"><div class="section-title">2️⃣ 创建数据表</div><ul class="message-list">';
                        $messages = array();
                        executeSql($pdo, $sqlFile);
                        foreach ($messages as $msg) {
                            echo '<li class="' . htmlspecialchars($msg['type']) . '">' . htmlspecialchars($msg['text']) . '</li>';
                        }
                        echo '</ul></div>';

                        echo '<div class="section"><div class="section-title">3️⃣ 创建管理员账号</div><ul class="message-list">';
                        $messages = array();
                        insertDefaultAdmin($pdo);
                        foreach ($messages as $msg) {
                            echo '<li class="' . htmlspecialchars($msg['type']) . '">' . htmlspecialchars($msg['text']) . '</li>';
                        }
                        echo '</ul></div>';

                        echo '<div class="section"><div class="section-title">4️⃣ 添加默认播放源</div><ul class="message-list">';
                        $messages = array();
                        insertDefaultPlaySources($pdo);
                        insertDefaultAnnouncement($pdo);
                        foreach ($messages as $msg) {
                            echo '<li class="' . htmlspecialchars($msg['type']) . '">' . htmlspecialchars($msg['text']) . '</li>';
                        }
                        echo '</ul></div>';

                        echo '<div class="section"><div class="section-title">5️⃣ 初始化网站设置</div><ul class="message-list">';
                        $messages = array();
                        insertDefaultSiteSettings($pdo);
                        foreach ($messages as $msg) {
                            echo '<li class="' . htmlspecialchars($msg['type']) . '">' . htmlspecialchars($msg['text']) . '</li>';
                        }
                        echo '</ul></div>';
                    }
                }

                $installOk = !$hasError;
                ?>

                <?php if ($installOk): ?>
                <div class="success-banner">
                    <h2>✅ 安装成功！</h2>
                    <p>Jay影视 网站已成功安装并初始化</p>
                </div>
                <?php else: ?>
                <div class="error-banner">
                    <h2>❌ 安装遇到问题</h2>
                    <p>安装过程中出现了一些错误，请查看上方日志并修复后重试</p>
                </div>
                <?php endif; ?>

                <div class="info-box">
                    <p><strong>📂 数据库文件位置：</strong>data/jaytv.db</p>
                    <p><strong>⚠️  安全提示：</strong>安装完成后，建议将 install.php 文件重命名或删除，以防止被恶意访问。</p>
                    <p><strong>🔧 后续操作：</strong>请使用管理员账号登录后台，修改默认密码并完善网站配置。</p>
                </div>

                <div class="actions">
                    <a href="install.php" class="btn btn-primary">🔄 重新安装</a>
                </div>

            <?php endif; ?>

        </div>
        <div class="footer">
            <p>Jay影视 Installer v1.0 | PHP <?php echo PHP_VERSION; ?></p>
        </div>
    </div>
</body>
</html>
