<?php
// 手动设置meta信息
// 如果自动提取失败，可以使用此工具手动设置

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

$code = $_GET['code'] ?? '';
$action = $_POST['action'] ?? '';

if ($action === 'save' && $code) {
    // 保存meta信息
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE links SET meta_title = ?, meta_description = ?, meta_image = ? WHERE short_code = ?");
        $stmt->execute([
            $_POST['title'] ?? null,
            $_POST['description'] ?? null,
            $_POST['image'] ?? null,
            $code
        ]);
        echo "<script>alert('保存成功！'); window.location.href='manual_meta.php?code=$code';</script>";
        exit;
    } catch (Exception $e) {
        echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
    }
}

if (empty($code)) {
    // 显示链接列表
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT short_code, original_url, meta_title FROM links ORDER BY id DESC LIMIT 50");
        $links = $stmt->fetchAll();
        
        echo "<h1>手动设置Meta信息</h1>";
        echo "<p>选择要编辑的链接：</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
        echo "<tr><th>短链代码</th><th>原始URL</th><th>当前标题</th><th>操作</th></tr>";
        foreach ($links as $link) {
            echo "<tr>";
            echo "<td>{$link['short_code']}</td>";
            echo "<td>" . htmlspecialchars(substr($link['original_url'], 0, 60)) . "...</td>";
            echo "<td>" . ($link['meta_title'] ? htmlspecialchars($link['meta_title']) : '<span style="color:red">未设置</span>') . "</td>";
            echo "<td><a href='?code={$link['short_code']}'>编辑</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
    }
    exit;
}

// 显示编辑表单
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $link = $stmt->fetch();
    
    if (!$link) {
        die('链接不存在');
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>手动设置Meta信息</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"], textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            textarea { height: 100px; }
            button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #45a049; }
            .info { background: #f0f0f0; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <h1>手动设置Meta信息</h1>
        
        <div class="info">
            <strong>短链代码：</strong><?php echo htmlspecialchars($code); ?><br>
            <strong>原始URL：</strong><a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank"><?php echo htmlspecialchars($link['original_url']); ?></a>
        </div>
        
        <form method="post">
            <input type="hidden" name="action" value="save">
            
            <div class="form-group">
                <label for="title">标题 (Title):</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($link['meta_title'] ?? ''); ?>" placeholder="例如：2025年会签到">
            </div>
            
            <div class="form-group">
                <label for="description">描述 (Description):</label>
                <textarea id="description" name="description" placeholder="例如：欢迎参加2025年会，请在此签到"><?php echo htmlspecialchars($link['meta_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">缩略图URL (Image URL):</label>
                <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($link['meta_image'] ?? ''); ?>" placeholder="例如：https://example.com/image.jpg">
                <small>必须是完整的图片URL（http://或https://开头）</small>
            </div>
            
            <button type="submit">保存</button>
            <a href="manual_meta.php" style="margin-left:10px;">返回列表</a>
        </form>
        
        <div style="margin-top:30px;padding:15px;background:#e7f3ff;border-radius:4px;">
            <h3>说明：</h3>
            <ul>
                <li>如果自动提取失败（如SPA单页应用），可以使用此工具手动设置</li>
                <li>标题建议控制在50字以内</li>
                <li>描述建议控制在100字以内</li>
                <li>图片URL必须是公网可访问的绝对地址</li>
                <li>保存后，微信转发时会显示这些信息</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

