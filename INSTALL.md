# 安装说明

## 环境要求

- PHP 7.0+
- MySQL 5.7+ 或 MariaDB 10.2+
- Apache/Nginx（支持URL重写）

## 安装步骤

### 1. 上传文件

将所有文件上传到服务器网站根目录（或子目录）

### 2. 配置数据库

编辑 `config.php` 文件，修改数据库连接信息：

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'iframe_shortlink');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('BASE_URL', 'https://yourdomain.com');  // 修改为您的域名
```

### 3. 创建数据库

方法1：使用安装脚本（推荐）
- 在浏览器访问：`http://yourdomain.com/install.php`
- 安装完成后删除 `install.php` 文件

方法2：手动创建
- 登录MySQL，执行 `database.sql` 文件中的SQL语句

### 4. 配置URL重写

#### Apache
确保已启用 `mod_rewrite` 模块，`.htaccess` 文件已包含在项目中。

#### Nginx（LNMP环境）

在Nginx站点配置文件中添加（通常在 `/usr/local/nginx/conf/vhost/域名.conf`）：

```nginx
# 短链访问：/s/代码 -> view.php?code=代码
location ~ ^/s/([a-zA-Z0-9]+)$ {
    try_files $uri /view.php?code=$1;
}
```

配置完成后执行：
```bash
nginx -t  # 测试配置
/etc/init.d/nginx reload  # 重载配置
```

**注意**：如果无法修改Nginx配置，可以临时使用 `view.php?code=短链代码` 的方式直接访问。

### 5. 设置文件权限

确保以下目录/文件有写入权限（如果需要）：
- 不需要特殊权限（使用数据库存储）

### 6. 测试

1. 访问首页：`http://yourdomain.com/`
2. 输入一个URL测试生成短链
3. 访问生成的短链，确认iframe正常显示
4. 确认弹窗已被过滤

## 使用说明

### 生成短链

1. 访问首页
2. 输入需要内嵌的URL
3. 点击"生成短链"
4. 复制生成的短链或扫描二维码

### 访问短链

访问格式：`http://yourdomain.com/s/短链代码`

例如：`http://yourdomain.com/s/abc123`

## 功能特性

- ✅ 短链生成和管理
- ✅ 二维码生成
- ✅ iframe内嵌显示
- ✅ 自动过滤baidu相关脚本
- ✅ 阻止弹窗
- ✅ 访问统计

## 安全建议

1. 删除 `install.php` 文件（安装完成后）
2. 定期备份数据库
3. 使用HTTPS（推荐）
4. 限制短链代码长度和字符集
5. 可以添加访问频率限制

## 故障排除

### 问题：URL重写不工作

- 检查Apache的 `mod_rewrite` 是否启用
- 检查 `.htaccess` 文件是否存在
- 检查Nginx配置是否正确

### 问题：数据库连接失败

- 检查 `config.php` 中的数据库配置
- 检查数据库用户权限
- 确认数据库已创建

### 问题：二维码不显示

- 检查网络连接（使用在线API生成）
- 可以安装phpqrcode库使用本地生成

