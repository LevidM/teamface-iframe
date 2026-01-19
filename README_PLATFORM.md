# iframe短链生成平台

一个用于生成过滤弹窗的iframe短链平台，支持批量处理多个页面链接。

## 功能特性

- ✅ **短链生成**：输入URL生成短链
- ✅ **二维码生成**：自动生成二维码方便分享
- ✅ **弹窗过滤**：自动过滤baidu相关脚本和弹窗
- ✅ **访问统计**：记录访问次数和时间
- ✅ **批量处理**：支持多个链接管理

## 文件结构

```
├── index.php          # 前端生成页面
├── view.php           # iframe展示页面（动态URL）
├── api.php            # API接口
├── config.php         # 数据库配置
├── database.sql       # 数据库表结构
├── qr.php             # 二维码生成
├── install.php        # 安装脚本
├── .htaccess          # URL重写规则
├── sw.js              # Service Worker（可选）
└── INSTALL.md         # 安装说明
```

## 快速开始

### 1. 安装

参考 `INSTALL.md` 文件进行安装配置。

### 2. 使用

1. 访问首页（`index.php`）
2. 输入需要内嵌的URL
3. 点击"生成短链"
4. 复制短链或扫描二维码分享

### 3. 访问短链

格式：`http://yourdomain.com/s/短链代码`

例如：`http://yourdomain.com/s/abc123`

## API接口

### 创建短链

```
POST api.php?action=create
Content-Type: application/json

{
  "url": "https://example.com/page"
}
```

响应：
```json
{
  "success": true,
  "short_code": "abc123",
  "short_url": "http://yourdomain.com/s/abc123",
  "original_url": "https://example.com/page",
  "qr_url": "http://yourdomain.com/qr.php?code=abc123"
}
```

### 获取链接信息

```
GET api.php?action=get&code=abc123
```

响应：
```json
{
  "success": true,
  "data": {
    "id": 1,
    "short_code": "abc123",
    "original_url": "https://example.com/page",
    "domain": "example.com",
    "created_at": "2025-01-01 12:00:00",
    "access_count": 10,
    "last_accessed": "2025-01-01 13:00:00"
  }
}
```

## 技术说明

### 弹窗过滤机制

1. **CSP策略**：通过Content Security Policy限制资源加载
2. **JavaScript拦截**：拦截fetch、XHR、动态脚本创建
3. **iframe注入**：向iframe注入拦截脚本（同源时）
4. **Service Worker**：网络层面拦截（需要HTTPS）

### 数据库结构

- `links` 表存储所有链接信息
- `short_code` 为唯一索引
- 自动记录访问统计

## 注意事项

1. **跨域限制**：由于浏览器安全策略，无法完全控制跨域iframe内的JavaScript
2. **HTTPS推荐**：使用HTTPS可以获得更好的过滤效果（Service Worker）
3. **域名配置**：确保 `config.php` 中的 `BASE_URL` 配置正确
4. **URL重写**：需要服务器支持URL重写（Apache/Nginx）

## 扩展功能

可以在此基础上扩展：

- 链接管理后台
- 批量导入导出
- 自定义短链代码
- 访问日志详细记录
- 链接有效期设置
- 访问权限控制

## 技术支持

如有问题，请检查：
1. 数据库连接配置
2. URL重写是否启用
3. PHP版本是否符合要求
4. 服务器错误日志

