<?php
require_once 'config.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('无效的代码');
}

// 使用简单的QR码生成（可以替换为更强大的库如phpqrcode）
// 这里使用在线API生成二维码
$url = BASE_URL . '/s/' . $code;
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);

header('Content-Type: image/png');
readfile($qrApiUrl);

