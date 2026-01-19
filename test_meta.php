<?php
// 测试meta信息提取
require_once 'config.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('请在URL中添加 ?code=你的短链代码');
}

echo "<h1>Meta信息测试</h1>";

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $link = $stmt->fetch();
    
    if (!$link) {
        die('链接不存在');
    }
    
    echo "<h2>数据库中的数据：</h2>";
    echo "<pre>";
    echo "标题: " . ($link['meta_title'] ?? 'NULL') . "\n";
    echo "描述: " . ($link['meta_description'] ?? 'NULL') . "\n";
    echo "图片: " . ($link['meta_image'] ?? 'NULL') . "\n";
    echo "原始URL: " . $link['original_url'] . "\n";
    echo "</pre>";
    
    echo "<h2>重新提取Meta信息：</h2>";
    $metaInfo = extractMetaInfo($link['original_url']);
    
    echo "<pre>";
    print_r($metaInfo);
    echo "</pre>";
    
    echo "<h2>测试提取函数：</h2>";
    
    // 手动测试提取
    $url = $link['original_url'];
    echo "<p>正在提取: <a href='$url' target='_blank'>$url</a></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8'
            ],
            'timeout' => 10,
            'follow_location' => 1,
            'max_redirects' => 3
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context, 0, 51200);
    
    if ($html === false) {
        echo "<p style='color:red'>❌ 无法获取页面内容</p>";
        echo "<p>错误信息：" . error_get_last()['message'] . "</p>";
    } else {
        echo "<p style='color:green'>✅ 成功获取页面内容 (" . strlen($html) . " 字节)</p>";
        
        // 显示前3000字符（查看更多内容）
        echo "<h3>页面前3000字符：</h3>";
        echo "<pre style='max-height:500px;overflow:auto;background:#f5f5f5;padding:10px;font-size:12px;'>";
        echo htmlspecialchars(substr($html, 0, 3000));
        echo "</pre>";
        
        // 尝试查找所有meta标签
        echo "<h3>所有meta标签：</h3>";
        if (preg_match_all('/<meta[^>]+>/i', $html, $metaMatches)) {
            echo "<pre style='max-height:300px;overflow:auto;background:#f5f5f5;padding:10px;font-size:12px;'>";
            foreach ($metaMatches[0] as $metaTag) {
                echo htmlspecialchars($metaTag) . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p style='color:orange'>未找到任何meta标签</p>";
        }
        
        // 尝试提取
        $patterns = [
            'og:title' => '/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i',
            'title' => '/<title[^>]*>([^<]+)<\/title>/i',
            'og:description' => '/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i',
            'description' => '/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i',
            'og:image' => '/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i',
        ];
        
        echo "<h3>提取结果：</h3>";
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                echo "<p><strong>$name:</strong> " . htmlspecialchars($matches[1]) . "</p>";
            } else {
                echo "<p><strong>$name:</strong> <span style='color:red'>未找到</span></p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

