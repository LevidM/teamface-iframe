<?php
// 批量更新现有链接的meta信息
// 使用方法：访问 update_meta.php 或通过命令行执行

require_once 'config.php';

// 判断是否在命令行运行
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<html><head><meta charset='UTF-8'><title>批量更新Meta信息</title></head><body>";
    echo "<h1>批量更新Meta信息</h1>";
    echo "<pre>";
}

try {
    $pdo = getDbConnection();
    
    // 获取所有没有meta信息的链接
    $stmt = $pdo->query("SELECT id, original_url FROM links WHERE meta_title IS NULL OR meta_title = '' LIMIT 100");
    $links = $stmt->fetchAll();
    
    if (empty($links)) {
        echo "没有需要更新的链接。\n";
        if (!$isCli) echo "</pre></body></html>";
        exit;
    }
    
    echo "找到 " . count($links) . " 个需要更新的链接\n";
    echo "开始处理...\n\n";
    
    $updated = 0;
    $failed = 0;
    
    foreach ($links as $link) {
        echo "处理链接 ID {$link['id']}: {$link['original_url']}\n";
        
        $metaInfo = extractMetaInfo($link['original_url']);
        
        if (!empty($metaInfo['title']) || !empty($metaInfo['description']) || !empty($metaInfo['image'])) {
            $updateStmt = $pdo->prepare("UPDATE links SET meta_title = ?, meta_description = ?, meta_image = ? WHERE id = ?");
            $updateStmt->execute([
                $metaInfo['title'],
                $metaInfo['description'],
                $metaInfo['image'],
                $link['id']
            ]);
            $updated++;
            echo "  ✓ 更新成功";
            if ($metaInfo['title']) echo " - 标题: " . substr($metaInfo['title'], 0, 50);
            echo "\n";
        } else {
            $failed++;
            echo "  ✗ 无法提取meta信息\n";
        }
        
        // 避免请求过快
        if (!$isCli) {
            flush();
            ob_flush();
        }
        usleep(500000); // 0.5秒
    }
    
    echo "\n完成！更新: {$updated}, 失败: {$failed}\n";
    echo "\n<a href='check_meta.php'>返回诊断页面</a>";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

if (!$isCli) {
    echo "</pre></body></html>";
}

