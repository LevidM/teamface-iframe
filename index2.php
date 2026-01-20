<?php
/**
 * 跨域 SPA iframe + 百度地图过滤 + 微信分享中转页
 */

// ====================== 参数接收 ======================
$targetUrl = $_GET['url'] ?? '';

$wxTitle = $_GET['wx_title'] ?? '页面分享';
$wxDesc  = $_GET['wx_desc'] ?? '点击查看详情';
$wxImg   = 'http://cert.fszi.org/img/logo2.png';

// 当前页面 URL（微信分享用）
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// ====================== 代理模式 ======================
if (isset($_GET['proxy']) && $targetUrl) {
    proxyPage($targetUrl);
    exit;
}

// ====================== 反向代理函数 ======================
function proxyPage($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        http_response_code(502);
        echo '页面加载失败';
        return;
    }

    /**
     * 1️⃣ 删除百度地图版权 & 弹窗
     */
    $html = preg_replace('/<div[^>]*class="[^"]*(BMap_cpyCtrl|anchorBL)[^"]*"[^>]*>.*?<\/div>/is', '', $html);

    /**
     * 2️⃣ 替换百度地图 JS（阻断弹窗）
     */
    $html = preg_replace(
        '/https?:\/\/api\.map\.baidu\.com\/api[^"\']*/i',
        'about:blank',
        $html
    );

    /**
     * 3️⃣ 强制插入 CSS 隐藏残留浮层
     */
    $injectCss = <<<CSS
<style>
.BMap_cpyCtrl,
.anchorBL,
.BMap_pop {
    display:none !important;
}
</style>
CSS;

    $html = preg_replace('/<\/head>/i', $injectCss . '</head>', $html, 1);

    echo $html;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">

<!-- ================= 微信分享 Meta ================= -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= htmlspecialchars($wxTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($wxDesc) ?>">
<meta property="og:image" content="<?= htmlspecialchars($wxImg) ?>">
<meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">

<title><?= htmlspecialchars($wxTitle) ?></title>

<style>
body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
}
.top {
    padding: 12px;
    background: #f5f5f5;
}
input {
    width: 100%;
    padding: 6px;
    margin-bottom: 8px;
}
iframe {
    width: 100%;
    height: calc(100vh - 230px);
    border: none;
}
</style>
</head>

<body>

<div class="top">
<form method="get">

    <input
        type="url"
        name="url"
        placeholder="请输入目标 SPA 页面链接"
        value="<?= htmlspecialchars($targetUrl) ?>"
        required
    >

    <div id="wxSetting" style="display:none;">
        <input
            type="text"
            name="wx_title"
            placeholder="微信分享标题"
            value="<?= htmlspecialchars($wxTitle) ?>"
        >
        <input
            type="text"
            name="wx_desc"
            placeholder="微信分享摘要"
            value="<?= htmlspecialchars($wxDesc) ?>"
        >
    </div>

    <button type="submit">生成页面</button>
</form>

<?php if ($targetUrl): ?>
    <p>生成链接：</p>
    <input type="text" readonly value="<?= htmlspecialchars($currentUrl) ?>">

    <p>二维码：</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($currentUrl) ?>">
<?php endif; ?>
</div>

<?php if ($targetUrl): ?>
<iframe
    src="?proxy=1&url=<?= urlencode($targetUrl) ?>"
></iframe>
<?php endif; ?>

<script>
const urlInput = document.querySelector('input[name="url"]');
const wxSetting = document.getElementById('wxSetting');

if (urlInput.value.trim()) {
    wxSetting.style.display = 'block';
}

urlInput.addEventListener('input', function () {
    wxSetting.style.display = this.value.trim() ? 'block' : 'none';
});
</script>

</body>
</html>
