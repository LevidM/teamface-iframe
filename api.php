<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createShortLink();
        break;
    case 'get':
        getLinkInfo();
        break;
    case 'update_meta':
        updateMetaInfo();
        break;
    default:
        echo json_encode(['success' => false, 'message' => '无效的操作']);
        break;
}

// 创建短链
function createShortLink() {
    $input = json_decode(file_get_contents('php://input'), true);
    $url = trim($input['url'] ?? '');
    
    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'URL不能为空']);
        exit;
    }
    
    // 验证URL格式
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'URL格式无效']);
        exit;
    }
    
    // 确保URL有协议
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'https://' . $url;
    }
    
    // 获取用户输入的meta信息
    $metaTitle = trim($input['meta_title'] ?? '');
    $metaDescription = trim($input['meta_description'] ?? '');
    $metaImage = trim($input['meta_image'] ?? '');
    
    // 如果图片为空，使用默认值
    if (empty($metaImage)) {
        $metaImage = 'http://cert.fszi.org/img/logo2.png';
    }
    
    try {
        $pdo = getDbConnection();
        
        // 检查URL是否已存在
        $stmt = $pdo->prepare("SELECT short_code FROM links WHERE original_url = ? LIMIT 1");
        $stmt->execute([$url]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $shortCode = $existing['short_code'];
            // 如果已存在，更新meta信息（如果用户提供了新的）
            if (!empty($metaTitle) || !empty($metaDescription) || !empty($metaImage)) {
                $updateStmt = $pdo->prepare("UPDATE links SET meta_title = ?, meta_description = ?, meta_image = ? WHERE short_code = ?");
                $updateStmt->execute([
                    $metaTitle ?: null,
                    $metaDescription ?: null,
                    $metaImage,
                    $shortCode
                ]);
            }
        } else {
            // 生成唯一的短链代码
            do {
                $shortCode = generateShortCode();
                $stmt = $pdo->prepare("SELECT id FROM links WHERE short_code = ? LIMIT 1");
                $stmt->execute([$shortCode]);
            } while ($stmt->fetch());
            
            // 提取域名
            $domain = extractDomain($url);
            
            // 插入数据库
            $stmt = $pdo->prepare("INSERT INTO links (short_code, original_url, domain, meta_title, meta_description, meta_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $shortCode, 
                $url, 
                $domain,
                $metaTitle ?: null,
                $metaDescription ?: null,
                $metaImage
            ]);
        }
        
        $shortUrl = BASE_URL . '/s/' . $shortCode;
        
        echo json_encode([
            'success' => true,
            'short_code' => $shortCode,
            'short_url' => $shortUrl,
            'original_url' => $url,
            'qr_url' => BASE_URL . '/qr.php?code=' . $shortCode
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '创建失败: ' . $e->getMessage()]);
    }
}

// 获取链接信息
function getLinkInfo() {
    $code = $_GET['code'] ?? '';
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => '代码不能为空']);
        exit;
    }
    
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $link = $stmt->fetch();
        
        if ($link) {
            // 更新访问统计
            $updateStmt = $pdo->prepare("UPDATE links SET access_count = access_count + 1, last_accessed = NOW() WHERE id = ?");
            $updateStmt->execute([$link['id']]);
            
            echo json_encode([
                'success' => true,
                'data' => $link
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '链接不存在']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '查询失败: ' . $e->getMessage()]);
    }
}

// 更新meta信息（手动设置）
function updateMetaInfo() {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $image = trim($input['image'] ?? '');
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => '代码不能为空']);
        exit;
    }
    
    try {
        $pdo = getDbConnection();
        
        // 检查链接是否存在
        $stmt = $pdo->prepare("SELECT id FROM links WHERE short_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $link = $stmt->fetch();
        
        if (!$link) {
            echo json_encode(['success' => false, 'message' => '链接不存在']);
            exit;
        }
        
        // 更新meta信息（允许为空，表示清除）
        $updateStmt = $pdo->prepare("UPDATE links SET meta_title = ?, meta_description = ?, meta_image = ? WHERE short_code = ?");
        $updateStmt->execute([
            $title ?: null,
            $description ?: null,
            $image ?: null,
            $code
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '更新成功'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '更新失败: ' . $e->getMessage()]);
    }
}

