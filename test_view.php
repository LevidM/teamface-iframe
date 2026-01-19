<?php
// 测试文件：用于测试view.php是否正常工作
// 访问方式：test_view.php?code=你的短链代码

require_once 'config.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('请在URL中添加 ?code=你的短链代码');
}

echo "<h1>测试短链访问</h1>";
echo "<p>短链代码: <strong>$code</strong></p>";

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $link = $stmt->fetch();
    
    if ($link) {
        echo "<p style='color:green'>✅ 找到链接！</p>";
        echo "<pre>";
        print_r($link);
        echo "</pre>";
        echo "<p><a href='view.php?code=$code' target='_blank'>点击查看iframe页面</a></p>";
    } else {
        echo "<p style='color:red'>❌ 链接不存在！</p>";
        echo "<p>请检查：</p>";
        echo "<ul>";
        echo "<li>数据库连接是否正确</li>";
        echo "<li>短链代码是否正确</li>";
        echo "<li>数据库表是否已创建</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ 数据库错误: " . $e->getMessage() . "</p>";
}

