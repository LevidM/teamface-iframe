<?php
// 诊断工具：检查meta信息问题
require_once 'config.php';

echo "<h1>Meta信息诊断工具</h1>";

try {
    $pdo = getDbConnection();
    
    // 检查数据库表结构
    echo "<h2>1. 检查数据库表结构</h2>";
    $stmt = $pdo->query("DESCRIBE links");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasMetaTitle = in_array('meta_title', $columns);
    $hasMetaDescription = in_array('meta_description', $columns);
    $hasMetaImage = in_array('meta_image', $columns);
    
    if ($hasMetaTitle && $hasMetaDescription && $hasMetaImage) {
        echo "<p style='color:green'>✅ 数据库字段已存在</p>";
    } else {
        echo "<p style='color:red'>❌ 数据库字段缺失！需要执行 database_update.sql</p>";
        echo "<p>缺失的字段：</p><ul>";
        if (!$hasMetaTitle) echo "<li>meta_title</li>";
        if (!$hasMetaDescription) echo "<li>meta_description</li>";
        if (!$hasMetaImage) echo "<li>meta_image</li>";
        echo "</ul>";
        echo "<p><strong>解决方案：执行 database_update.sql 文件</strong></p>";
        exit;
    }
    
    // 检查链接数据
    echo "<h2>2. 检查链接数据</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM links");
    $total = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM links WHERE meta_title IS NOT NULL AND meta_title != ''");
    $withMeta = $stmt->fetch()['total'];
    
    echo "<p>总链接数：{$total}</p>";
    echo "<p>有meta信息的链接：{$withMeta}</p>";
    echo "<p>无meta信息的链接：" . ($total - $withMeta) . "</p>";
    
    if ($total - $withMeta > 0) {
        echo "<p style='color:orange'>⚠️ 有 " . ($total - $withMeta) . " 个链接没有meta信息</p>";
        echo "<p><strong>解决方案：运行 update_meta.php 来更新现有链接</strong></p>";
    }
    
    // 测试提取功能
    echo "<h2>3. 测试meta提取功能</h2>";
    $testUrl = $_GET['test_url'] ?? '';
    
    if ($testUrl) {
        echo "<p>测试URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
        $metaInfo = extractMetaInfo($testUrl);
        
        echo "<pre>";
        print_r($metaInfo);
        echo "</pre>";
        
        if (empty($metaInfo['title']) && empty($metaInfo['description']) && empty($metaInfo['image'])) {
            echo "<p style='color:red'>❌ 提取失败！可能的原因：</p>";
            echo "<ul>";
            echo "<li>目标网站阻止了爬虫访问</li>";
            echo "<li>网络连接问题</li>";
            echo "<li>页面没有Open Graph标签</li>";
            echo "<li>PHP的file_get_contents函数被禁用</li>";
            echo "</ul>";
        } else {
            echo "<p style='color:green'>✅ 提取成功</p>";
        }
    } else {
        echo "<p>输入测试URL: <form method='get'><input type='text' name='test_url' placeholder='https://example.com' style='width:400px;padding:5px;'><button type='submit'>测试</button></form></p>";
    }
    
    // 显示最近的链接
    echo "<h2>4. 最近的链接（前5条）</h2>";
    $stmt = $pdo->query("SELECT id, short_code, original_url, meta_title, meta_description, meta_image FROM links ORDER BY id DESC LIMIT 5");
    $links = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>ID</th><th>短链代码</th><th>原始URL</th><th>标题</th><th>描述</th><th>图片</th></tr>";
    foreach ($links as $link) {
        echo "<tr>";
        echo "<td>{$link['id']}</td>";
        echo "<td>{$link['short_code']}</td>";
        echo "<td>" . htmlspecialchars(substr($link['original_url'], 0, 50)) . "...</td>";
        echo "<td>" . ($link['meta_title'] ? htmlspecialchars($link['meta_title']) : '<span style="color:red">空</span>') . "</td>";
        echo "<td>" . ($link['meta_description'] ? htmlspecialchars(substr($link['meta_description'], 0, 30)) . "..." : '<span style="color:red">空</span>') . "</td>";
        echo "<td>" . ($link['meta_image'] ? '<span style="color:green">有</span>' : '<span style="color:red">空</span>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>5. 操作建议</h2>";
    echo "<ol>";
    if ($total - $withMeta > 0) {
        echo "<li><strong>运行 update_meta.php</strong> 来更新现有链接的meta信息</li>";
    }
    echo "<li>创建新短链时，系统会自动提取meta信息</li>";
    echo "<li>如果提取失败，可以使用 <a href='test_meta.php?code=你的短链代码'>test_meta.php</a> 来调试</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

