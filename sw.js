// Service Worker: 拦截并阻止所有baidu相关的请求
// 注意：Service Worker需要在HTTPS环境下运行，或者localhost

const BAIDU_PATTERNS = [
    'baidu.com',
    'baidustatic.com',
    'baidubce.com',
    'bdstatic.com',
    'baiduapi.com',
    'baidu',
    'bdstatic'
];

function isBaiduRequest(url) {
    if (!url) return false;
    const urlLower = url.toLowerCase();
    return BAIDU_PATTERNS.some(pattern => urlLower.includes(pattern));
}

// 安装Service Worker
self.addEventListener('install', function(event) {
    console.log('Service Worker 安装成功');
    self.skipWaiting();
});

// 激活Service Worker
self.addEventListener('activate', function(event) {
    console.log('Service Worker 激活成功');
    event.waitUntil(self.clients.claim());
});

// 拦截所有网络请求
self.addEventListener('fetch', function(event) {
    const url = event.request.url;
    
    if (isBaiduRequest(url)) {
        console.log('Service Worker: 已阻止baidu请求', url);
        // 返回一个空的响应，阻止请求
        event.respondWith(
            new Response('', {
                status: 403,
                statusText: 'Blocked by Service Worker',
                headers: {
                    'Content-Type': 'text/plain'
                }
            })
        );
        return;
    }
    
    // 允许其他请求正常通过
    event.respondWith(fetch(event.request));
});

