<?php
require_once 'config.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('无效的链接代码');
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM links WHERE short_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $link = $stmt->fetch();
    
    if (!$link) {
        die('链接不存在');
    }
    
    // 更新访问统计
    $updateStmt = $pdo->prepare("UPDATE links SET access_count = access_count + 1, last_accessed = NOW() WHERE id = ?");
    $updateStmt->execute([$link['id']]);
    
    $originalUrl = htmlspecialchars($link['original_url'], ENT_QUOTES, 'UTF-8');
    $domain = $link['domain'] ?? '';
    
    // 获取meta信息（用于微信转发）
    $metaTitle = $link['meta_title'] ?? '';
    $metaDescription = $link['meta_description'] ?? '';
    $metaImage = $link['meta_image'] ?? '';
    
    // 如果没有meta信息，使用默认值
    if (empty($metaTitle)) {
        $metaTitle = '内嵌页面';
    }
    if (empty($metaDescription)) {
        $metaDescription = '点击查看页面内容';
    }
    
    // 生成CSP策略
    $cspDomain = $domain ? "https://{$domain}" : "*";
    
    // 生成完整的短链URL（用于og:url）
    $shortUrl = BASE_URL . '/s/' . $code;
    
} catch (Exception $e) {
    die('数据库错误: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 基础meta信息 -->
    <title><?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- Open Graph meta标签（微信、Facebook等） -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($shortUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($metaImage)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($metaImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>
    
    <!-- 微信专用meta标签 -->
    <meta itemprop="name" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta itemprop="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($metaImage)): ?>
    <meta itemprop="image" content="<?php echo htmlspecialchars($metaImage, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    
    <!-- Content Security Policy: 阻止所有baidu相关的资源 -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' <?php echo $cspDomain; ?>; script-src 'self' <?php echo $cspDomain; ?> 'unsafe-inline' 'unsafe-eval'; style-src 'self' <?php echo $cspDomain; ?> 'unsafe-inline'; img-src 'self' <?php echo $cspDomain; ?> data: https:; font-src 'self' <?php echo $cspDomain; ?> data:; connect-src 'self' <?php echo $cspDomain; ?>; frame-src 'self' <?php echo $cspDomain; ?>; object-src 'none'; base-uri 'self'; form-action 'self' <?php echo $cspDomain; ?>;">
</head>
<body style="margin:0;padding:0;overflow:hidden;">
    <iframe 
        id="signin-frame"
        src="<?php echo $originalUrl; ?>"
        style="width:100%;height:100vh;border:none;display:block;"
        sandbox="allow-same-origin allow-scripts allow-forms"
        allow="fullscreen"
    ></iframe>
    
    <script id="blocker-script">
        (function() {
            // 阻止所有弹窗
            window.alert = function() {};
            window.confirm = function() { return false; };
            window.prompt = function() { return null; };
            window.open = function() { return null; };
            if (window.showModalDialog) {
                window.showModalDialog = function() { return null; };
            }
            
            // 阻止baidu相关的脚本执行
            const originalCreateElement = document.createElement;
            document.createElement = function(tagName) {
                const element = originalCreateElement.apply(this, arguments);
                if (tagName.toLowerCase() === 'script') {
                    const originalSetAttribute = element.setAttribute;
                    element.setAttribute = function(name, value) {
                        if (name === 'src' && typeof value === 'string' && (value.includes('baidu') || value.includes('bdstatic'))) {
                            return;
                        }
                        return originalSetAttribute.apply(this, arguments);
                    };
                }
                return element;
            };
            
            // 拦截fetch和XHR
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                const url = args[0];
                if (typeof url === 'string' && (url.includes('baidu') || url.includes('bdstatic'))) {
                    return Promise.reject(new Error('Blocked'));
                }
                return originalFetch.apply(this, args);
            };
            
            const originalXHROpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method, url) {
                if (typeof url === 'string' && (url.includes('baidu') || url.includes('bdstatic'))) {
                    return;
                }
                return originalXHROpen.apply(this, arguments);
            };
            
            // 定期检查并重新应用拦截（防止被覆盖）
            setInterval(function() {
                if (window.alert && window.alert.toString().indexOf('native') !== -1) {
                    window.alert = function() {};
                }
                if (window.confirm && window.confirm.toString().indexOf('native') !== -1) {
                    window.confirm = function() { return false; };
                }
                if (window.prompt && window.prompt.toString().indexOf('native') !== -1) {
                    window.prompt = function() { return null; };
                }
                if (window.open && window.open.toString().indexOf('native') !== -1) {
                    window.open = function() { return null; };
                }
            }, 50);
        })();
    </script>

    <script>
        // 注册Service Worker拦截baidu请求
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('./sw.js').catch(function() {});
            });
        }

        // 阻止所有baidu相关的请求和脚本执行
        (function() {
            // 拦截fetch请求
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                const url = args[0];
                if (typeof url === 'string' && (url.includes('baidu') || url.includes('bdstatic'))) {
                    return Promise.reject(new Error('Blocked'));
                }
                return originalFetch.apply(this, args);
            };

            // 拦截XMLHttpRequest
            const originalXHROpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(method, url, ...rest) {
                if (typeof url === 'string' && (url.includes('baidu') || url.includes('bdstatic'))) {
                    return;
                }
                return originalXHROpen.apply(this, [method, url, ...rest]);
            };

            // 阻止动态创建baidu相关的script标签
            const originalCreateElement = document.createElement;
            document.createElement = function(tagName, ...rest) {
                const element = originalCreateElement.apply(this, [tagName, ...rest]);
                if (tagName.toLowerCase() === 'script') {
                    const originalSetAttribute = element.setAttribute;
                    element.setAttribute = function(name, value) {
                        if (name === 'src' && typeof value === 'string' && (value.includes('baidu') || value.includes('bdstatic'))) {
                            return;
                        }
                        return originalSetAttribute.apply(this, arguments);
                    };
                }
                return element;
            };

            // 阻止通过innerHTML插入baidu脚本
            const originalInnerHTML = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
            Object.defineProperty(Element.prototype, 'innerHTML', {
                set: function(value) {
                    if (typeof value === 'string' && (value.includes('baidu') || value.includes('bdstatic')) && value.includes('<script')) {
                        value = value.replace(/<script[^>]*(baidu|bdstatic)[^>]*>[\s\S]*?<\/script>/gi, '');
                    }
                    originalInnerHTML.set.call(this, value);
                },
                get: originalInnerHTML.get
            });

            // 拦截window.open阻止弹窗
            const originalWindowOpen = window.open;
            window.open = function(url, name, features) {
                if (typeof url === 'string' && (url.includes('baidu') || url.includes('bdstatic'))) {
                    return null;
                }
                return null;
            };

            // 监听iframe加载完成并注入拦截脚本
            const iframe = document.getElementById('signin-frame');
            const blockerScript = document.getElementById('blocker-script').textContent;
            
            function injectBlocker() {
                try {
                    const iframeWindow = iframe.contentWindow;
                    const iframeDoc = iframe.contentDocument || iframeWindow.document;
                    
                    if (iframeDoc && iframeWindow) {
                        // 直接注入拦截脚本
                        try {
                            const script = iframeDoc.createElement('script');
                            script.textContent = blockerScript;
                            (iframeDoc.head || iframeDoc.documentElement).appendChild(script);
                            
                            // 同时直接重写弹窗方法
                            iframeWindow.alert = function() {};
                            iframeWindow.confirm = function() { return false; };
                            iframeWindow.prompt = function() { return null; };
                            iframeWindow.open = function() { return null; };
                            if (iframeWindow.showModalDialog) {
                                iframeWindow.showModalDialog = function() { return null; };
                            }
                        } catch (e) {}
                        
                        // 移除所有baidu相关的script标签
                        const scripts = iframeDoc.querySelectorAll('script');
                        scripts.forEach(script => {
                            if (script.src && (script.src.includes('baidu') || script.src.includes('bdstatic'))) {
                                script.remove();
                            } else if (script.textContent && (script.textContent.includes('baidu') || script.textContent.includes('bdstatic'))) {
                                script.textContent = '';
                            }
                        });
                        
                        // 监听iframe内部的DOM变化
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                mutation.addedNodes.forEach(function(node) {
                                    if (node.nodeName === 'SCRIPT') {
                                        if (node.src && (node.src.includes('baidu') || node.src.includes('bdstatic'))) {
                                            node.remove();
                                        } else if (node.textContent && (node.textContent.includes('baidu') || node.textContent.includes('bdstatic'))) {
                                            node.textContent = '';
                                        }
                                    }
                                });
                            });
                        });
                        observer.observe(iframeDoc.body || iframeDoc.documentElement, {
                            childList: true,
                            subtree: true
                        });
                        
                        // 定期检查并重新应用拦截
                        setInterval(function() {
                            try {
                                if (iframeWindow.alert && iframeWindow.alert.toString().indexOf('native') !== -1) {
                                    iframeWindow.alert = function() {};
                                }
                                if (iframeWindow.confirm && iframeWindow.confirm.toString().indexOf('native') !== -1) {
                                    iframeWindow.confirm = function() { return false; };
                                }
                                if (iframeWindow.prompt && iframeWindow.prompt.toString().indexOf('native') !== -1) {
                                    iframeWindow.prompt = function() { return null; };
                                }
                                if (iframeWindow.open && iframeWindow.open.toString().indexOf('native') !== -1) {
                                    iframeWindow.open = function() { return null; };
                                }
                            } catch (e) {}
                        }, 50);
                    }
                } catch (e) {
                    // 跨域限制，无法直接注入
                }
            }
            
            // 立即尝试注入（如果iframe已加载）
            if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
                injectBlocker();
            }
            
            // 监听iframe加载完成
            iframe.addEventListener('load', injectBlocker);
            
            // 多次尝试注入（处理动态加载）
            setTimeout(injectBlocker, 100);
            setTimeout(injectBlocker, 500);
            setTimeout(injectBlocker, 1000);
            setTimeout(injectBlocker, 2000);
        })();
    </script>
</body>
</html>

