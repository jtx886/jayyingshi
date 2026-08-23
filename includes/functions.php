<?php
/**
 * Jay影视网站公共函数库
 * 兼容PHP 5.6+
 * @author Jay影视
 */

// 引入配置文件
require_once dirname(dirname(__FILE__)) . '/config.php';

// 启动session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 1. 数据库连接函数
// =========================================================================

/**
 * 获取数据库连接实例（单例模式）
 * 使用PDO连接SQLite数据库
 * 
 * @return PDO 数据库连接实例
 * @throws Exception 数据库连接失败时抛出异常
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA journal_mode=WAL");
            $pdo->exec("PRAGMA foreign_keys=ON");
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败：' . $e->getMessage());
        }
    }
    return $pdo;
}

// =========================================================================
// 2. 用户相关函数
// =========================================================================

/**
 * 获取当前登录用户信息
 * 从session中读取用户ID并查询数据库获取完整用户信息
 * 
 * @return array|false 用户信息数组，未登录返回false
 */
function getCurrentUser() {
    $key = SESSION_PREFIX . 'user_id';
    if (empty($_SESSION[$key])) {
        return false;
    }
    $userId = intval($_SESSION[$key]);
    if ($userId <= 0) {
        return false;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute(array($userId));
        $user = $stmt->fetch();
        if (!$user) {
            unset($_SESSION[$key]);
            return false;
        }
        if (isBanned($userId)) {
            unset($_SESSION[$key]);
            return false;
        }
        return $user;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 判断用户是否已登录
 * 
 * @return bool 已登录返回true，未登录返回false
 */
function isLoggedIn() {
    return getCurrentUser() !== false;
}

/**
 * 判断当前用户是否为管理员
 * 
 * @return bool 是管理员返回true，否则返回false
 */
function isAdmin() {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    return isset($user['role']) && intval($user['role']) === 1;
}

/**
 * 判断用户是否被封禁
 * 如果封禁时间已过期，则自动解禁
 * 
 * @param int $userId 用户ID
 * @return bool 被封禁返回true，未封禁返回false
 */
function isBanned($userId) {
    $userId = intval($userId);
    if ($userId <= 0) {
        return false;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT banned, ban_expire_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute(array($userId));
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        if (intval($row['banned']) === 1) {
            if (!empty($row['ban_expire_at']) && intval($row['ban_expire_at']) > 0) {
                if (time() > intval($row['ban_expire_at'])) {
                    $stmt = $pdo->prepare("UPDATE users SET banned = 0, ban_expire_at = 0, ban_reason = '' WHERE id = ?");
                    $stmt->execute(array($userId));
                    return false;
                }
            }
            return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 检查是否登录，未登录则跳转到登录页面
 * 用于需要登录才能访问的页面
 * 
 * @return void 无返回值，未登录时直接跳转
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION[SESSION_PREFIX . 'login_redirect'] = $_SERVER['REQUEST_URI'];
        redirect(SITE_URL . '/login.php');
        exit;
    }
}

// =========================================================================
// 3. 邮件发送函数
// =========================================================================

/**
 * 使用SMTP协议发送邮件（基于fsockopen实现）
 * 支持SSL连接，适用于不支持PHPMailer或mail()函数的环境
 * 
 * @param string $to 收件人邮箱
 * @param string $subject 邮件主题
 * @param string $body 邮件正文（支持HTML格式）
 * @return bool 发送成功返回true，失败返回false
 */
function sendEmail($to, $subject, $body) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $from = SMTP_FROM;
    $fromName = SMTP_FROM_NAME;
    $secure = SMTP_SECURE;

    if (strtolower($secure) == 'ssl') {
        $host = 'ssl://' . $host;
    }

    $fp = @fsockopen($host, $port, $errno, $errstr, 30);
    if (!$fp) {
        return false;
    }

    stream_set_blocking($fp, true);
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($fp);
        return false;
    }

    $send_data = 'EHLO ' . $_SERVER['HTTP_HOST'] . "\r\n";
    fwrite($fp, $send_data);
    $response = '';
    while ($str = fgets($fp, 515)) {
        $response .= $str;
        if (substr($str, 3, 1) == ' ') {
            break;
        }
    }
    if (substr($response, 0, 3) != '250') {
        fclose($fp);
        return false;
    }

    if (strtolower($secure) == 'tls') {
        fwrite($fp, "STARTTLS\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '220') {
            fclose($fp);
            return false;
        }
        if (function_exists('stream_socket_enable_crypto')) {
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
        fwrite($fp, 'EHLO ' . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = '';
        while ($str = fgets($fp, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }
        if (substr($response, 0, 3) != '250') {
            fclose($fp);
            return false;
        }
    }

    fwrite($fp, "AUTH LOGIN\r\n");
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($fp);
        return false;
    }

    fwrite($fp, base64_encode($user) . "\r\n");
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($fp);
        return false;
    }

    fwrite($fp, base64_encode($pass) . "\r\n");
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '235') {
        fclose($fp);
        return false;
    }

    fwrite($fp, "MAIL FROM: <" . $from . ">\r\n");
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($fp);
        return false;
    }

    fwrite($fp, "RCPT TO: <" . $to . ">\r\n");
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($fp);
        return false;
    }

    fwrite($fp, "DATA\r\n");
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '354') {
        fclose($fp);
        return false;
    }

    $boundary = "----=_Part_" . md5(uniqid(mt_rand(), true));

    $headers = array();
    $headers[] = "Date: " . date('r');
    $headers[] = "To: <" . $to . ">";
    $headers[] = "From: " . '=?UTF-8?B?' . base64_encode($fromName) . '?=' . " <" . $from . ">";
    $headers[] = "Subject: " . '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
    $headers[] = "X-Mailer: PHP/" . phpversion();

    $message = implode("\r\n", $headers) . "\r\n\r\n";

    $message .= "--" . $boundary . "\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $textBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));
    $message .= chunk_split(base64_encode($textBody)) . "\r\n";

    $message .= "--" . $boundary . "\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($body)) . "\r\n";

    $message .= "--" . $boundary . "--\r\n";
    $message .= ".\r\n";

    fwrite($fp, $message);
    $response = fgets($fp, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($fp);
        return false;
    }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);

    return true;
}

// =========================================================================
// 4. 生成验证码函数
// =========================================================================

/**
 * 生成指定长度的数字验证码
 * 
 * @param int $length 验证码长度，默认6位
 * @return string 生成的验证码字符串
 */
function generateCode($length = 6) {
    $length = max(1, intval($length));
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= mt_rand(0, 9);
    }
    return $code;
}

// =========================================================================
// 5. 发送邮箱验证码函数
// =========================================================================

/**
 * 发送邮箱验证码
 * 生成6位数字验证码存入数据库，有效期15分钟，并发送精美HTML邮件
 * 
 * @param string $email 收件人邮箱
 * @param string $type 验证码类型：register(注册)、reset(重置密码)、login(登录)等
 * @return bool 发送成功返回true，失败返回false
 */
function sendVerificationCode($email, $type = 'register') {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $code = generateCode(6);
    $expireAt = time() + 900;

    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE email = ? AND type = ?");
        $stmt->execute(array($email, $type));

        $stmt = $pdo->prepare("INSERT INTO verification_codes (email, code, type, expire_at, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array($email, $code, $type, $expireAt, time()));

        $pdo->commit();
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }

    $typeText = array(
        'register' => '注册账号',
        'reset' => '重置密码',
        'login' => '登录验证',
        'email' => '绑定邮箱'
    );
    $actionText = isset($typeText[$type]) ? $typeText[$type] : '身份验证';

    $siteName = SITE_NAME;
    $year = date('Y');

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$siteName} - 验证码</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f5f7fa;
        padding: 20px;
        color: #333;
    }
    .email-wrapper {
        max-width: 600px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    .email-header {
        background: linear-gradient(135deg, #01b4e4 0%, #0083b0 100%);
        padding: 30px 25px;
        text-align: center;
        color: #ffffff;
    }
    .email-header h1 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: 1px;
    }
    .email-header p {
        font-size: 14px;
        opacity: 0.9;
    }
    .email-body {
        padding: 35px 30px;
    }
    .greeting {
        font-size: 16px;
        color: #555;
        margin-bottom: 25px;
        line-height: 1.6;
    }
    .code-box {
        background: linear-gradient(135deg, #e8f7fc 0%, #f0f9ff 100%);
        border: 2px dashed #01b4e4;
        border-radius: 10px;
        padding: 30px 20px;
        text-align: center;
        margin: 25px 0;
    }
    .code-title {
        font-size: 14px;
        color: #0083b0;
        margin-bottom: 15px;
        font-weight: 500;
    }
    .code-text {
        font-size: 42px;
        font-weight: bold;
        color: #01b4e4;
        letter-spacing: 12px;
        font-family: 'Courier New', Courier, monospace;
        text-shadow: 0 2px 4px rgba(1, 180, 228, 0.2);
    }
    .tips {
        background: #fff8e1;
        border-left: 4px solid #ffc107;
        padding: 15px 18px;
        border-radius: 4px;
        margin-top: 25px;
        font-size: 13px;
        color: #795548;
        line-height: 1.7;
    }
    .tips strong {
        color: #f57c00;
    }
    .info-list {
        margin: 20px 0;
        padding: 0;
        list-style: none;
    }
    .info-list li {
        padding: 8px 0;
        font-size: 14px;
        color: #666;
        border-bottom: 1px dashed #eee;
    }
    .info-list li:last-child {
        border-bottom: none;
    }
    .info-list span {
        color: #333;
        font-weight: 500;
    }
    .email-footer {
        background: #fafbfc;
        padding: 20px 30px;
        text-align: center;
        border-top: 1px solid #eee;
    }
    .email-footer p {
        font-size: 12px;
        color: #999;
        line-height: 1.8;
    }
    .email-footer a {
        color: #01b4e4;
        text-decoration: none;
    }
    .divider {
        height: 1px;
        background: #eee;
        margin: 20px 0;
    }
</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>🎬 {$siteName}</h1>
            <p>您的专属影视平台</p>
        </div>
        <div class="email-body">
            <div class="greeting">
                您好！<br>
                您正在进行<strong style="color: #01b4e4;">{$actionText}</strong>操作，请使用下方验证码完成验证。
            </div>
            <div class="code-box">
                <div class="code-title">✨ 您的验证码 ✨</div>
                <div class="code-text">{$code}</div>
            </div>
            <ul class="info-list">
                <li>📧 验证邮箱：<span>{$email}</span></li>
                <li>⏰ 有效时长：<span>15分钟</span></li>
                <li>🔑 验证码类型：<span>{$actionText}</span></li>
            </ul>
            <div class="tips">
                <strong>⚠️ 温馨提示：</strong><br>
                1. 验证码有效期为15分钟，过期后请重新获取；<br>
                2. 请勿将验证码泄露给任何人，包括平台工作人员；<br>
                3. 如非本人操作，请忽略此邮件并及时修改密码。
            </div>
        </div>
        <div class="email-footer">
            <p>此邮件由 <a href="{$siteUrl}">{$siteName}</a> 自动发送，请勿直接回复</p>
            <p>&copy; {$year} {$siteName}. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;

    $body = str_replace('{$siteUrl}', SITE_URL, $body);

    return sendEmail($email, '【Jay影视】您的验证码', $body);
}

// =========================================================================
// 6. 验证验证码函数
// =========================================================================

/**
 * 验证邮箱验证码是否正确且未过期
 * 验证成功后自动标记为已使用
 * 
 * @param string $email 邮箱地址
 * @param string $code 验证码
 * @param string $type 验证码类型：register/reset/login等
 * @return bool 验证成功返回true，失败返回false
 */
function verifyCode($email, $code, $type = 'register') {
    $email = trim($email);
    $code = trim($code);
    if (empty($email) || empty($code)) {
        return false;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE email = ? AND type = ? AND used = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute(array($email, $type));
        $record = $stmt->fetch();
        if (!$record) {
            return false;
        }
        if (intval($record['expire_at']) < time()) {
            return false;
        }
        if (strval($record['code']) !== strval($code)) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE verification_codes SET used = 1, used_at = ? WHERE id = ?");
        $stmt->execute(array(time(), $record['id']));
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// =========================================================================
// 7. 网站设置函数
// =========================================================================

/**
 * 获取网站设置值
 * 从site_settings表读取指定配置项的值
 * 
 * @param string $key 配置项键名
 * @param mixed $default 配置项不存在时返回的默认值
 * @return mixed 配置项的值，不存在则返回默认值
 */
function getSetting($key, $default = '') {
    static $settings = array();
    $cacheKey = $key;
    if (isset($settings[$cacheKey])) {
        return $settings[$cacheKey];
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute(array($key));
        $row = $stmt->fetch();
        if ($row) {
            $value = $row['setting_value'];
            $json = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $settings[$cacheKey] = $json;
                return $json;
            }
            $settings[$cacheKey] = $value;
            return $value;
        }
    } catch (Exception $e) {
    }
    $settings[$cacheKey] = $default;
    return $default;
}

/**
 * 更新或插入网站设置值
 * 
 * @param string $key 配置项键名
 * @param mixed $value 配置项值（数组或对象会自动转为JSON）
 * @return bool 设置成功返回true，失败返回false
 */
function setSetting($key, $value) {
    if (empty($key)) {
        return false;
    }
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM site_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute(array($key));
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?");
            $stmt->execute(array($value, time(), $key));
        } else {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($key, $value, time(), time()));
        }
        if (function_exists('getSetting')) {
            $reflection = new ReflectionFunction('getSetting');
            $staticVars = $reflection->getStaticVariables();
            if (isset($staticVars['settings'][$key])) {
                $staticVars['settings'][$key] = $value;
            }
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 获取网站主题颜色
 * 
 * @return string 主题颜色十六进制值，默认#01b4e4（蓝色）
 */
function getThemeColor() {
    $color = getSetting('theme_color', '#01b4e4');
    if (empty($color) || !preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
        return '#01b4e4';
    }
    return $color;
}

// =========================================================================
// 8. HTTP请求函数
// =========================================================================

/**
 * 发送HTTP GET请求
 * 优先使用curl，不可用时使用file_get_contents
 * 
 * @param string $url 请求URL
 * @param array $headers 请求头数组，格式：array('Header-Name: value')
 * @return string|false 响应内容，失败返回false
 */
function httpGet($url, $headers = array()) {
    if (empty($url)) {
        return false;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false || $httpCode >= 400) {
            return false;
        }
        return $response;
    } else {
        $context = array(
            'http' => array(
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        if (!empty($headers)) {
            $context['http']['header'] = implode("\r\n", $headers);
        }
        $ctx = stream_context_create($context);
        $response = @file_get_contents($url, false, $ctx);
        return $response === false ? false : $response;
    }
}

/**
 * 发送HTTP POST请求
 * 使用curl发送POST请求
 * 
 * @param string $url 请求URL
 * @param mixed $data POST数据，数组或JSON字符串
 * @param array $headers 请求头数组
 * @return string|false 响应内容，失败返回false
 */
function httpPost($url, $data, $headers = array()) {
    if (empty($url) || !function_exists('curl_init')) {
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $isJson = false;
    foreach ($headers as $header) {
        if (stripos($header, 'Content-Type') !== false && stripos($header, 'application/json') !== false) {
            $isJson = true;
            break;
        }
    }
    if ($isJson && is_array($data)) {
        $postData = json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        $postData = is_array($data) ? http_build_query($data) : $data;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return false;
    }
    return $response;
}

// =========================================================================
// 9. TMDB API相关函数
// =========================================================================

/**
 * 调用TMDB API接口
 * 自动添加api_key和language=zh-CN参数
 * 
 * @param string $endpoint API端点路径，如 /movie/popular 或 movie/550
 * @param array $params 额外的查询参数
 * @return array|false API返回的数组数据，失败返回false
 */
function tmdbRequest($endpoint, $params = array()) {
    $endpoint = ltrim($endpoint, '/');
    $url = rtrim(TMDB_BASE_URL, '/') . '/' . $endpoint;

    $defaultParams = array(
        'api_key' => TMDB_API_KEY,
        'language' => 'zh-CN'
    );
    $allParams = array_merge($defaultParams, $params);
    $queryString = http_build_query($allParams);
    $url .= '?' . $queryString;

    $headers = array(
        'Accept: application/json'
    );

    $response = httpGet($url, $headers);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    return $data;
}

/**
 * 获取TMDB图片完整URL
 * 
 * @param string $path 图片相对路径，如 /xq1Ugd62d23K2knRUx6xxuALTZB.jpg
 * @param string $size 图片尺寸，如 w200, w300, w400, w500, original等
 * @return string 完整的图片URL，path为空时返回空字符串
 */
function getTmdbImage($path, $size = 'w500') {
    if (empty($path)) {
        return '';
    }
    $baseUrl = rtrim(TMDB_IMAGE_BASE_URL, '/');
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $size . '/' . $path;
}

// =========================================================================
// 10. 工具函数
// =========================================================================

/**
 * 302重定向跳转
 * 
 * @param string $url 目标URL
 * @return void 无返回值，执行后直接退出
 */
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url, true, 302);
    } else {
        echo '<script type="text/javascript">window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
    }
    exit;
}

/**
 * HTML安全转义输出
 * 使用htmlspecialchars转义特殊字符，防止XSS攻击
 * 
 * @param string $str 要转义的字符串
 * @return string 转义后的安全字符串
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 调试打印函数（dump and die）
 * 格式化打印变量内容并终止执行
 * 
 * @param mixed $var 要打印的变量
 * @return void 无返回值，执行后直接退出
 */
function dd($var) {
    echo '<pre style="background:#f5f5f5;border:1px solid #ddd;padding:15px;border-radius:4px;font-size:13px;line-height:1.5;overflow:auto;">';
    if (php_sapi_name() === 'cli') {
        var_dump($var);
    } else {
        echo htmlspecialchars(print_r($var, true), ENT_QUOTES, 'UTF-8');
    }
    echo '</pre>';
    die;
}

/**
 * 格式化时间戳为友好的日期时间字符串
 * 格式：YYYY-MM-DD HH:MM:SS
 * 
 * @param int $timestamp Unix时间戳
 * @return string 格式化后的日期时间字符串
 */
function formatTime($timestamp) {
    $timestamp = intval($timestamp);
    if ($timestamp <= 0) {
        return '';
    }
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * 将秒数格式化为 小时:分钟:秒 格式
 * 例如：3661秒 -> 01:01:01
 * 
 * @param int $seconds 秒数
 * @return string 格式化后的时间字符串 H:i:s
 */
function formatSeconds($seconds) {
    $seconds = max(0, intval($seconds));
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

// =========================================================================
// 11. 封禁用户函数
// =========================================================================

/**
 * 封禁用户
 * 更新users表的封禁状态和时间，并发送封禁通知邮件
 * 
 * @param int $userId 用户ID
 * @param int $duration 封禁时长（小时），0表示永久封禁
 * @param string $reason 封禁原因
 * @return bool 操作成功返回true，失败返回false
 */
function banUser($userId, $duration = 0, $reason = '') {
    $userId = intval($userId);
    $duration = intval($duration);
    if ($userId <= 0) {
        return false;
    }
    if ($duration < 0) {
        $duration = 0;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute(array($userId));
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        $banTime = time();
        if ($duration > 0) {
            $expireAt = $banTime + ($duration * 3600);
        } else {
            $expireAt = 0;
        }
        $stmt = $pdo->prepare("UPDATE users SET banned = 1, ban_reason = ?, ban_at = ?, ban_expire_at = ? WHERE id = ?");
        $stmt->execute(array($reason, $banTime, $expireAt, $userId));

        if (!empty($user['email'])) {
            $banTimeStr = formatTime($banTime);
            if ($duration > 0) {
                $expireStr = formatTime($expireAt);
                $durationStr = $duration . ' 小时';
            } else {
                $expireStr = '永久';
                $durationStr = '永久封禁';
            }
            $reasonStr = empty($reason) ? '未说明' : e($reason);
            $siteName = SITE_NAME;
            $year = date('Y');
            $siteUrl = SITE_URL;

            $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>账号封禁通知</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #fff5f5;
        padding: 20px;
        color: #333;
    }
    .email-wrapper {
        max-width: 600px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-top: 4px solid #e74c3c;
    }
    .email-header {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
        padding: 30px 25px;
        text-align: center;
    }
    .email-header .icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
    .email-header h1 {
        font-size: 22px;
        color: #e74c3c;
        margin-bottom: 8px;
    }
    .email-header p {
        font-size: 14px;
        color: #999;
    }
    .email-body {
        padding: 30px;
    }
    .greeting {
        font-size: 15px;
        color: #555;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    .info-card {
        background: #fff9f9;
        border: 1px solid #ffd6d6;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    .info-row {
        padding: 10px 0;
        font-size: 14px;
        border-bottom: 1px dashed #ffd6d6;
        display: flex;
        align-items: flex-start;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        color: #999;
        min-width: 90px;
        flex-shrink: 0;
    }
    .info-value {
        color: #333;
        font-weight: 500;
        flex: 1;
        word-break: break-all;
    }
    .info-value.red {
        color: #e74c3c;
    }
    .tips {
        background: #fff8e1;
        border-left: 4px solid #ffc107;
        padding: 15px 18px;
        border-radius: 4px;
        margin-top: 20px;
        font-size: 13px;
        color: #795548;
        line-height: 1.8;
    }
    .contact-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
        text-align: center;
    }
    .contact-box a {
        display: inline-block;
        padding: 10px 25px;
        background: #01b4e4;
        color: #fff !important;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        margin-top: 8px;
    }
    .email-footer {
        background: #fafbfc;
        padding: 20px 30px;
        text-align: center;
        border-top: 1px solid #eee;
    }
    .email-footer p {
        font-size: 12px;
        color: #999;
        line-height: 1.8;
    }
</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <div class="icon">🚫</div>
            <h1>账号封禁通知</h1>
            <p>{$siteName}</p>
        </div>
        <div class="email-body">
            <div class="greeting">
                尊敬的用户 <strong>{username}</strong>：<br><br>
                很遗憾地通知您，您在 {$siteName} 的账号已被封禁。请查看下方详细信息。
            </div>
            <div class="info-card">
                <div class="info-row">
                    <div class="info-label">封禁时长：</div>
                    <div class="info-value red">{$durationStr}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">封禁时间：</div>
                    <div class="info-value">{$banTimeStr}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">解除时间：</div>
                    <div class="info-value red">{$expireStr}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">封禁原因：</div>
                    <div class="info-value">{$reasonStr}</div>
                </div>
            </div>
            <div class="tips">
                <strong>📋 说明：</strong><br>
                1. 封禁期间您将无法登录账号及使用相关功能；<br>
                2. 如为临时封禁，到期后将自动解除封禁状态；<br>
                3. 如有异议或认为封禁有误，可联系客服进行申诉。
            </div>
            <div class="contact-box">
                <p>如有疑问，请联系我们的客服团队</p>
                <a href="{$siteUrl}">访问网站首页</a>
            </div>
        </div>
        <div class="email-footer">
            <p>此邮件由 {$siteName} 自动发送</p>
            <p>&copy; {$year} {$siteName}. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
            $body = str_replace('{username}', e($user['username']), $body);
            $subject = '【Jay影视】您的账号已被封禁';
            @sendEmail($user['email'], $subject, $body);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

// =========================================================================
// 12. 解禁用户函数
// =========================================================================

/**
 * 解禁用户
 * 清除users表中的封禁状态，并发送解封通知邮件
 * 
 * @param int $userId 用户ID
 * @return bool 操作成功返回true，失败返回false
 */
function unbanUser($userId) {
    $userId = intval($userId);
    if ($userId <= 0) {
        return false;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute(array($userId));
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE users SET banned = 0, ban_reason = '', ban_at = 0, ban_expire_at = 0 WHERE id = ?");
        $stmt->execute(array($userId));

        if (!empty($user['email'])) {
            $unbanTimeStr = formatTime(time());
            $siteName = SITE_NAME;
            $year = date('Y');
            $siteUrl = SITE_URL;

            $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>账号解封通知</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #f0fff4;
        padding: 20px;
        color: #333;
    }
    .email-wrapper {
        max-width: 600px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-top: 4px solid #27ae60;
    }
    .email-header {
        background: linear-gradient(135deg, #f0fff4 0%, #d5f5e3 100%);
        padding: 30px 25px;
        text-align: center;
    }
    .email-header .icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
    .email-header h1 {
        font-size: 22px;
        color: #27ae60;
        margin-bottom: 8px;
    }
    .email-header p {
        font-size: 14px;
        color: #666;
    }
    .email-body {
        padding: 30px;
    }
    .greeting {
        font-size: 15px;
        color: #555;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    .info-card {
        background: #f5fff7;
        border: 1px solid #b2e2c1;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    .info-row {
        padding: 10px 0;
        font-size: 14px;
        border-bottom: 1px dashed #b2e2c1;
        display: flex;
        align-items: flex-start;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        color: #999;
        min-width: 90px;
        flex-shrink: 0;
    }
    .info-value {
        color: #333;
        font-weight: 500;
        flex: 1;
    }
    .info-value.green {
        color: #27ae60;
    }
    .tips {
        background: #e8f5e9;
        border-left: 4px solid #27ae60;
        padding: 15px 18px;
        border-radius: 4px;
        margin-top: 20px;
        font-size: 13px;
        color: #2e7d32;
        line-height: 1.8;
    }
    .action-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        text-align: center;
    }
    .action-box p {
        color: #666;
        font-size: 14px;
        margin-bottom: 12px;
    }
    .btn-login {
        display: inline-block;
        padding: 12px 35px;
        background: linear-gradient(135deg, #01b4e4 0%, #0083b0 100%);
        color: #fff !important;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(1, 180, 228, 0.3);
    }
    .email-footer {
        background: #fafbfc;
        padding: 20px 30px;
        text-align: center;
        border-top: 1px solid #eee;
    }
    .email-footer p {
        font-size: 12px;
        color: #999;
        line-height: 1.8;
    }
</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <div class="icon">✅</div>
            <h1>账号解封通知</h1>
            <p>{$siteName}</p>
        </div>
        <div class="email-body">
            <div class="greeting">
                尊敬的用户 <strong>{username}</strong>：<br><br>
                很高兴地通知您，您在 {$siteName} 的账号已被解除封禁，现在可以正常登录使用了。
            </div>
            <div class="info-card">
                <div class="info-row">
                    <div class="info-label">解封状态：</div>
                    <div class="info-value green">✅ 已解封</div>
                </div>
                <div class="info-row">
                    <div class="info-label">解封时间：</div>
                    <div class="info-value">{$unbanTimeStr}</div>
                </div>
            </div>
            <div class="tips">
                <strong>💡 温馨提示：</strong><br>
                1. 请您遵守平台规则，避免再次出现违规行为；<br>
                2. 多次违规可能导致账号被永久封禁；<br>
                3. 请妥善保管账号密码，不要与他人共用账号。
            </div>
            <div class="action-box">
                <p>点击下方按钮，立即登录您的账号：</p>
                <a href="{$siteUrl}/login.php" class="btn-login">立即登录</a>
            </div>
        </div>
        <div class="email-footer">
            <p>此邮件由 {$siteName} 自动发送</p>
            <p>&copy; {$year} {$siteName}. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
            $body = str_replace('{username}', e($user['username']), $body);
            $subject = '【Jay影视】您的账号已解封';
            @sendEmail($user['email'], $subject, $body);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}
