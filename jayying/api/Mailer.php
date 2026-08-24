<?php
// 邮件发送类 - 使用原生PHP实现，兼容所有版本

class Mailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $fromName;
    private $socket;
    private $connected = false;

    public function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->user = SMTP_USER;
        $this->pass = SMTP_PASS;
        $this->from = SMTP_FROM;
        $this->fromName = SMTP_FROM_NAME;
    }

    // 发送邮件
    public function send($to, $toName, $subject, $body, $isHTML = true) {
        if (!$this->connect()) {
            return false;
        }

        $this->command('EHLO localhost', 250);
        $this->command('STARTTLS', 220);
        $this->command('EHLO localhost', 250);
        $this->command('AUTH LOGIN', 334);
        $this->command(base64_encode($this->user), 334);
        $this->command(base64_encode($this->pass), 235);
        $this->command("MAIL FROM:<{$this->from}>", 250);
        $this->command("RCPT TO:<{$to}>", 250);
        $this->command('DATA', 354);

        $headers = $this->buildHeaders($to, $toName, $subject, $body, $isHTML);
        $this->socketWrite($headers . "\r\n.\r\n");
        $response = $this->readResponse();

        $this->command('QUIT', 221);
        $this->disconnect();

        return strpos($response, '250') !== false;
    }

    // 构建邮件头
    private function buildHeaders($to, $toName, $subject, $body, $isHTML) {
        $boundary = md5(uniqid((string)mt_rand(), true));
        $headers = "Date: " . date("r") . "\r\n";
        $headers .= "From: " . $this->encodeHeader($this->fromName) . "<{$this->from}>\r\n";
        $headers .= "To: " . $this->encodeHeader($toName) . "<{$to}>\r\n";
        $headers .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if ($isHTML) {
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "\r\n";
            $headers .= "--{$boundary}\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "\r\n";
            $headers .= strip_tags($body) . "\r\n";
            $headers .= "\r\n";
            $headers .= "--{$boundary}\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "\r\n";
            $headers .= $body . "\r\n";
            $headers .= "\r\n";
            $headers .= "--{$boundary}--\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "\r\n";
            $headers .= $body . "\r\n";
        }

        return $headers;
    }

    // 编码头部
    private function encodeHeader($str) {
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }

    // 连接SMTP服务器
    private function connect() {
        $port = $this->port == 465 ? 'ssl://' . $this->host : $this->host;
        $this->socket = @fsockopen($port, $this->port, $errno, $errstr, 15);
        
        if (!$this->socket) {
            $this->log("连接SMTP服务器失败: {$errstr}");
            return false;
        }

        $this->connected = true;
        $this->readResponse();

        if ($this->port == 587) {
            $this->command('STARTTLS', 220);
        }

        return true;
    }

    // 断开连接
    private function disconnect() {
        if ($this->connected && $this->socket) {
            @fclose($this->socket);
            $this->connected = false;
        }
    }

    // 发送命令
    private function command($command, $expectedCode) {
        $this->socketWrite($command . "\r\n");
        $response = $this->readResponse();
        $code = intval(substr($response, 0, 3));
        
        if ($code != $expectedCode) {
            $this->log("SMTP命令失败: {$command} -> {$response}");
            throw new Exception("SMTP Error: Expected {$expectedCode}, got {$code}");
        }
        return $response;
    }

    // 写入数据
    private function socketWrite($data) {
        fwrite($this->socket, $data);
    }

    // 读取响应
    private function readResponse() {
        $response = '';
        while ($str = @fgets($this->socket, 515)) {
            $response .= $str;
            if (isset($str[3]) && $str[3] == ' ') {
                break;
            }
        }
        return $response;
    }

    // 记录日志
    private function log($message) {
        $logFile = __DIR__ . '/../data/mail.log';
        $time = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[{$time}] {$message}\n", FILE_APPEND);
    }

    // 发送验证码邮件
    public function sendVerificationCode($email, $username, $code) {
        $subject = 'Jay影视 - 邮箱验证码';
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>验证码</title>
        </head>
        <body style="margin:0;padding:0;background:#0b1019;font-family:Arial,sans-serif;">
            <div style="max-width:600px;margin:0 auto;background:linear-gradient(135deg,#0e1929 0%,#161f2e 100%);padding:40px 20px;">
                <div style="text-align:center;margin-bottom:30px;">
                    <h1 style="color:#05d4c7;font-size:28px;margin:0;text-shadow:0 0 20px rgba(5,212,199,0.3);">Jay影视</h1>
                    <div style="width:60px;height:3px;background:linear-gradient(90deg,#05d4c7,#1f80d6);margin:10px auto;border-radius:2px;"></div>
                </div>
                <div style="background:rgba(255,255,255,0.05);border-radius:16px;padding:40px 30px;border:1px solid rgba(5,212,199,0.2);">
                    <h2 style="color:#fff;font-size:20px;margin:0 0 20px 0;text-align:center;">你好，' . htmlspecialchars($username) . '</h2>
                    <p style="color:#b3b3b3;font-size:14px;line-height:1.6;margin:0 0 25px 0;text-align:center;">感谢您注册 Jay影视<br>请使用以下验证码完成注册：</p>
                    <div style="background:linear-gradient(135deg,#05d4c7 0%,#1f80d6 100%);border-radius:12px;padding:25px;text-align:center;margin-bottom:25px;">
                        <span style="color:#fff;font-size:36px;font-weight:bold;letter-spacing:8px;text-shadow:0 2px 10px rgba(0,0,0,0.3);">' . $code . '</span>
                    </div>
                    <p style="color:#b3b3b3;font-size:13px;line-height:1.6;margin:0;text-align:center;">
                        验证码有效时间为 5 分钟<br>
                        请勿将验证码分享给他人
                    </p>
                </div>
                <div style="text-align:center;margin-top:30px;color:#666;font-size:12px;">
                    <p style="margin:0;">这是一封自动发送的邮件，请勿直接回复</p>
                </div>
            </div>
        </body>
        </html>';
        return $this->send($email, $username, $subject, $body);
    }

    // 发送封禁通知
    public function sendBanNotification($email, $username, $reason, $banUntil) {
        $subject = 'Jay影视 - 账号封禁通知';
        $banTime = date('Y-m-d H:i:s', strtotime($banUntil));
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin:0;padding:0;background:#0b1019;font-family:Arial,sans-serif;">
            <div style="max-width:600px;margin:0 auto;background:linear-gradient(135deg,#0e1929 0%,#161f2e 100%);padding:40px 20px;">
                <div style="text-align:center;margin-bottom:30px;">
                    <h1 style="color:#ff6b6b;font-size:28px;margin:0;">⚠️ 账号封禁通知</h1>
                    <div style="width:60px;height:3px;background:linear-gradient(90deg,#ff6b6b,#ee5a6f);margin:10px auto;border-radius:2px;"></div>
                </div>
                <div style="background:rgba(255,107,107,0.1);border-radius:16px;padding:40px 30px;border:1px solid rgba(255,107,107,0.3);">
                    <h2 style="color:#fff;font-size:20px;margin:0 0 20px 0;text-align:center;">' . htmlspecialchars($username) . '</h2>
                    <p style="color:#b3b3b3;font-size:14px;line-height:1.6;margin:0 0 25px 0;text-align:center;">
                        您的账号已被管理员暂时封禁
                    </p>
                    <div style="background:rgba(255,107,107,0.15);border-radius:12px;padding:20px;margin-bottom:20px;">
                        <p style="color:#fff;font-size:14px;margin:0 0 10px 0;"><strong>封禁原因：</strong>' . htmlspecialchars($reason) . '</p>
                        <p style="color:#fff;font-size:14px;margin:0;"><strong>解封时间：</strong>' . $banTime . '</p>
                    </div>
                    <p style="color:#b3b3b3;font-size:13px;line-height:1.6;margin:0;text-align:center;">
                        在此期间您将无法登录和使用我们的服务<br>
                        如有疑问请联系管理员
                    </p>
                </div>
                <div style="text-align:center;margin-top:30px;color:#666;font-size:12px;">
                    <p style="margin:0;">这是一封自动发送的邮件</p>
                </div>
            </div>
        </body>
        </html>';
        return $this->send($email, $username, $subject, $body);
    }

    // 发送自定义通知邮件
    public function sendCustomNotification($email, $username, $subject, $message) {
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin:0;padding:0;background:#0b1019;font-family:Arial,sans-serif;">
            <div style="max-width:600px;margin:0 auto;background:linear-gradient(135deg,#0e1929 0%,#161f2e 100%);padding:40px 20px;">
                <div style="text-align:center;margin-bottom:30px;">
                    <h1 style="color:#05d4c7;font-size:28px;margin:0;text-shadow:0 0 20px rgba(5,212,199,0.3);">Jay影视</h1>
                </div>
                <div style="background:rgba(255,255,255,0.05);border-radius:16px;padding:40px 30px;border:1px solid rgba(5,212,199,0.2);">
                    <h2 style="color:#fff;font-size:18px;margin:0 0 20px 0;text-align:center;">' . htmlspecialchars($subject) . '</h2>
                    <p style="color:#b3b3b3;font-size:14px;line-height:1.6;margin:0;text-align:center;">' . nl2br(htmlspecialchars($message)) . '</p>
                </div>
            </div>
        </body>
        </html>';
        return $this->send($email, $username, $subject, $body);
    }
}
