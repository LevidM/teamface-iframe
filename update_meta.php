<?php
// 批量更新现有链接的meta信息
// 使用方法：访问 update_meta.php 或通过命令行执行

require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // 获取所有没有meta信息的链接
    $stmt = $pdo->query("SELECT id, original_url FROM links WHERE meta_title IS NULL OR meta_title = '' LIMIT 100");
    $links = $stmt->fetchAll();
    
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
            echo "  ✓ 更新成功\n";
        } else {
            $failed++;
            echo "  ✗ 无法提取meta信息\n";
        }
        
        // 避免请求过快
        usleep(500000); // 0.5秒
    }
    
    echo "\n完成！更新: {$updated}, 失败: {$failed}\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

