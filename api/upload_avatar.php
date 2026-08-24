<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) jsonResponse(array('success' => false, 'require_login' => true, 'message' => '请先登录'));

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(array('success' => false, 'message' => '请选择图片文件'));
}

$file = $_FILES['avatar'];
if ($file['size'] > 2 * 1024 * 1024) jsonResponse(array('success' => false, 'message' => '图片最大2MB'));

$imgInfo = @getimagesize($file['tmp_name']);
if (!$imgInfo) jsonResponse(array('success' => false, 'message' => '不是有效的图片'));

$allowed = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP);
if (!in_array($imgInfo[2], $allowed)) jsonResponse(array('success' => false, 'message' => '仅支持JPG/PNG/GIF/WEBP'));

$extMap = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');
$ext = $extMap[$imgInfo[2]];

$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
if (!is_dir($uploadDir)) jsonResponse(array('success' => false, 'message' => '上传目录不可写'));

$uid = $_SESSION['user_id'];
$filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
$target = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) jsonResponse(array('success' => false, 'message' => '保存失败'));

// 生成缩放后头像
try {
    $maxSize = 256;
    list($w, $h, $t) = getimagesize($target);
    if ($w > $maxSize || $h > $maxSize) {
        $scale = min($maxSize / $w, $maxSize / $h);
        $nw = intval($w * $scale);
        $nh = intval($h * $scale);
        $src = null;
        switch ($t) {
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($target); break;
            case IMAGETYPE_PNG: $src = imagecreatefrompng($target); break;
            case IMAGETYPE_GIF: $src = imagecreatefromgif($target); break;
            case IMAGETYPE_WEBP: if (function_exists('imagecreatefromwebp')) $src = imagecreatefromwebp($target); break;
        }
        if ($src) {
            $dst = imagecreatetruecolor($nw, $nh);
            // PNG透明处理
            if ($t == IMAGETYPE_PNG) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            switch ($t) {
                case IMAGETYPE_JPEG: imagejpeg($dst, $target, 90); break;
                case IMAGETYPE_PNG: imagepng($dst, $target); break;
                case IMAGETYPE_GIF: imagegif($dst, $target); break;
                case IMAGETYPE_WEBP: if (function_exists('imagewebp')) imagewebp($dst, $target, 90); break;
            }
            imagedestroy($src);
            imagedestroy($dst);
        }
    }
} catch (Exception $e) {}

$relativePath = 'uploads/avatars/' . $filename;
$db = Database::getInstance();
$db->update('users', array('avatar' => $relativePath), 'id = ?', array($uid));
$_SESSION['avatar'] = $relativePath;

jsonResponse(array('success' => true, 'message' => '头像更新成功', 'url' => $relativePath));
?>
