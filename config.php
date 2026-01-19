<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'iframe_shortlink');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 网站基础URL（需要根据实际情况修改）
define('BASE_URL', 'http://huodong.fszi.org');  // 修改为您的域名，如：https://yourdomain.com

// 数据库连接
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}

// 生成短链代码
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// 从URL提取域名（用于CSP）
function extractDomain($url) {
    $parsed = parse_url($url);
    if (isset($parsed['host'])) {
        $host = $parsed['host'];
        // 移除www前缀
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }
    return null;
}

// 从URL提取Open Graph meta信息（用于微信转发）
function extractMetaInfo($url) {
    $meta = [
        'title' => null,
        'description' => null,
        'image' => null
    ];
    
    try {
        // 设置用户代理，避免被拒绝
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
        
        // 获取页面内容（只获取前50KB，通常足够获取meta标签）
        $html = @file_get_contents($url, false, $context, 0, 51200);
        
        if ($html === false) {
            return $meta;
        }
        
        // 使用正则表达式提取meta标签
        // 提取 og:title 或 title
        if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $meta['title'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $meta['title'] = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }
        
        // 提取 og:description 或 description
        if (preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $meta['description'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $meta['description'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }
        
        // 提取 og:image
        if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $imageUrl = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            // 如果是相对URL，转换为绝对URL
            if (!preg_match('/^https?:\/\//', $imageUrl)) {
                $parsed = parse_url($url);
                $base = $parsed['scheme'] . '://' . $parsed['host'];
                if (isset($parsed['port'])) {
                    $base .= ':' . $parsed['port'];
                }
                if (strpos($imageUrl, '/') !== 0) {
                    $path = dirname($parsed['path'] ?? '/');
                    $imageUrl = $base . $path . '/' . $imageUrl;
                } else {
                    $imageUrl = $base . $imageUrl;
                }
            }
            $meta['image'] = $imageUrl;
        }
        
        // 限制长度
        if ($meta['title'] && mb_strlen($meta['title']) > 255) {
            $meta['title'] = mb_substr($meta['title'], 0, 252) . '...';
        }
        if ($meta['description'] && mb_strlen($meta['description']) > 500) {
            $meta['description'] = mb_substr($meta['description'], 0, 497) . '...';
        }
        
    } catch (Exception $e) {
        // 静默失败，返回空meta
    }
    
    return $meta;
}

