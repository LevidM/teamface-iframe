# 签到页面 - 过滤Baidu脚本版本

这个项目创建了一个HTML页面，内嵌指定的签到表单页面，并尝试过滤掉所有baidu相关的JavaScript。

## 文件说明

- `index.html` - 主页面，包含iframe和过滤逻辑
- `sw.js` - Service Worker文件，用于拦截网络请求（需要HTTPS环境）

## 使用方法

### 方法1：直接打开（基础过滤）

直接在浏览器中打开 `index.html` 文件。这种方式会：
- 在父页面拦截baidu相关的请求
- 使用CSP策略限制资源加载
- 尝试拦截iframe内部的baidu脚本（受跨域限制）

### 方法2：使用本地服务器 + HTTPS（完整过滤）

为了使用Service Worker进行更彻底的过滤，需要：

1. 使用HTTPS环境运行（或localhost）
2. 启动本地服务器，例如：
   ```bash
   # 使用Python
   python -m http.server 8000
   
   # 或使用Node.js的http-server
   npx http-server -p 8000 --ssl --cert cert.pem --key key.pem
   ```
3. 通过 `https://localhost:8000` 访问

## 功能说明

### 已实现的过滤机制

1. **Content Security Policy (CSP)**
   - 限制只能加载teamface.fszi.org的资源
   - 阻止baidu域名的资源

2. **JavaScript拦截**
   - 拦截fetch请求中的baidu请求
   - 拦截XMLHttpRequest中的baidu请求
   - 阻止动态创建baidu相关的script标签
   - 过滤innerHTML中的baidu脚本

3. **Service Worker拦截**（需要HTTPS）
   - 在网络层面拦截所有baidu相关的请求
   - 返回403阻止请求

4. **iframe监控**
   - 尝试监控iframe内部的DOM变化
   - 移除动态添加的baidu脚本（受跨域限制）

## 限制说明

由于浏览器的**跨域安全策略**，父页面无法直接控制iframe内部的JavaScript执行。因此：

- ✅ 可以阻止父页面的baidu请求
- ✅ 可以阻止iframe加载baidu资源（通过CSP和Service Worker）
- ⚠️ 无法直接修改iframe内部已执行的JavaScript代码
- ⚠️ 如果iframe内部已经加载了baidu脚本，可能无法完全阻止其执行

## 表单提交

iframe的sandbox属性已设置为允许表单提交：
- `allow-forms` - 允许表单提交
- `allow-scripts` - 允许脚本执行（必要的）
- `allow-same-origin` - 允许同源访问

表单应该可以正常提交。

## 调试

打开浏览器开发者工具（F12）查看控制台，可以看到：
- 被阻止的baidu请求日志
- Service Worker注册状态
- iframe加载状态

## 注意事项

1. Service Worker需要HTTPS环境（localhost除外）
2. 某些浏览器扩展可能会影响过滤效果
3. 如果目标页面使用了其他方式加载baidu脚本，可能需要额外处理

