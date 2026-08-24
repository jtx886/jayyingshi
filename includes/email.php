<?php
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/db.php';

class Email {
    private static function generateCode() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    public static function saveCode($email, $type = 'register') {
        $code = self::generateCode();
        $db = Database::getInstance();
        $expire = date('Y-m-d H:i:s', time() + 600); // 10分钟有效
        $db->insert('email_codes', array(
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'expire_time' => $expire
        ));
        return $code;
    }
    
    public static function verifyCode($email, $code, $type = 'register') {
        $db = Database::getInstance();
        $record = $db->fetchOne(
            "SELECT * FROM email_codes WHERE email = ? AND code = ? AND type = ? AND used = 0 AND expire_time >= NOW() ORDER BY id DESC LIMIT 1",
            array($email, $code, $type)
        );
        if ($record) {
            $db->update('email_codes', array('used' => 1), 'id = ?', array($record['id']));
            return true;
        }
        return false;
    }
    
    // 使用fsockopen实现的SMTP邮件发送（兼容所有PHP版本，无需扩展）
    public static function send($to, $subject, $content, $type = null, $userId = null) {
        $boundary = "----=_Part_" . md5(uniqid());
        
        $headers = array();
        $headers[] = "Date: " . date('r');
        $headers[] = "To: <" . $to . ">";
        $headers[] = "From: " . "=?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?=" . " <" . SMTP_FROM . ">";
        $headers[] = "Subject: " . "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
        $headers[] = "X-Mailer: PHP/JayYsMail";
        
        $body = "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode(strip_tags($content))) . "\r\n";
        $body .= "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($content)) . "\r\n";
        $body .= "--" . $boundary . "--\r\n";
        
        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        
        $result = self::smtpSend($to, $data);
        
        // 记录日志
        $db = Database::getInstance();
        $db->insert('email_logs', array(
            'to_email' => $to,
            'subject' => $subject,
            'content' => $content,
            'type' => $type,
            'user_id' => $userId
        ));
        
        return $result;
    }
    
    private static function smtpSend($to, $data) {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $from = SMTP_FROM;
        
        $timeout = 30;
        $fp = @fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            // 尝试不使用SSL
            $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if (!$fp) {
                return false;
            }
        }
        
        stream_set_blocking($fp, true);
        $response = self::readResponse($fp);
        if (strpos($response, '220') === false) {
            @fclose($fp);
            return false;
        }
        
        self::sendCommand($fp, "EHLO " . $_SERVER['HTTP_HOST']);
        $response = self::readResponse($fp);
        if (strpos($response, '250') === false) {
            @fclose($fp);
            return false;
        }
        
        // 尝试STARTTLS
        if (strpos($response, 'STARTTLS') !== false) {
            self::sendCommand($fp, "STARTTLS");
            $response = self::readResponse($fp);
            if (strpos($response, '220') !== false) {
                @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::sendCommand($fp, "EHLO " . $_SERVER['HTTP_HOST']);
                self::readResponse($fp);
            }
        }
        
        // 登录
        self::sendCommand($fp, "AUTH LOGIN");
        self::readResponse($fp);
        self::sendCommand($fp, base64_encode($user));
        self::readResponse($fp);
        self::sendCommand($fp, base64_encode($pass));
        $response = self::readResponse($fp);
        if (strpos($response, '235') === false) {
            @fclose($fp);
            return false;
        }
        
        self::sendCommand($fp, "MAIL FROM:<" . $from . ">");
        self::readResponse($fp);
        
        self::sendCommand($fp, "RCPT TO:<" . $to . ">");
        self::readResponse($fp);
        
        self::sendCommand($fp, "DATA");
        self::readResponse($fp);
        
        self::sendCommand($fp, $data . "\r\n.");
        $response = self::readResponse($fp);
        
        self::sendCommand($fp, "QUIT");
        @fclose($fp);
        
        return strpos($response, '250') !== false;
    }
    
    private static function sendCommand($fp, $cmd) {
        fwrite($fp, $cmd . "\r\n");
    }
    
    private static function readResponse($fp) {
        $data = '';
        while ($str = fgets($fp, 515)) {
            $data .= $str;
            if ($str[3] == ' ') break;
        }
        return $data;
    }
    
    public static function getEmailTemplate($title, $contentHtml) {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); margin: 0; padding: 40px 0; }
    .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); }
    .header { background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%); padding: 40px 30px; text-align: center; }
    .logo { font-size: 32px; font-weight: 800; color: #fff; letter-spacing: -1px; }
    .logo span { background: linear-gradient(135deg, #f472b6, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .body { padding: 40px 30px; color: #1f2937; line-height: 1.7; }
    .title { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 20px; }
    .code-box { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; padding: 30px; text-align: center; margin: 25px 0; border: 2px dashed #a78bfa; }
    .code { font-size: 42px; font-weight: 800; color: #7c3aed; letter-spacing: 12px; font-family: 'Courier New', monospace; }
    .info-box { background: #faf5ff; border-left: 4px solid #a78bfa; padding: 16px 20px; border-radius: 8px; margin: 20px 0; }
    .footer { background: #f9fafb; padding: 25px 30px; text-align: center; color: #6b7280; font-size: 13px; }
    .btn { display: inline-block; background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%); color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 600; margin: 15px 0; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">Jay<span>影视</span></div>
    </div>
    <div class="body">
        {$contentHtml}
    </div>
    <div class="footer">
        <p>© {$year} Jay影视 版权所有 · 这是自动发送邮件请勿直接回复</p>
    </div>
</div>
</body>
</html>
HTML;
    }
}
?>
