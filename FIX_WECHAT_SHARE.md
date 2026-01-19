# 微信转发问题修复指南

## 问题现象

微信转发时显示：
- 标题：**"内嵌页面"**（默认值）
- 描述：**"点击查看页面内容"**（默认值）
- 缩略图：**空**

## 原因分析

这表示数据库中的meta信息字段是空的，可能的原因：
1. **数据库字段未添加**：数据库表还没有meta相关字段
2. **提取失败**：创建链接时无法提取meta信息
3. **现有链接**：链接是在添加meta功能之前创建的

## 解决步骤

### 第一步：检查数据库字段

访问诊断工具：
```
http://huodong.fszi.org/check_meta.php
```

或者手动检查：
```sql
DESCRIBE links;
```

应该看到以下字段：
- `meta_title`
- `meta_description`
- `meta_image`

### 第二步：如果字段不存在，执行SQL更新

如果字段不存在，需要执行 `database_update.sql`：

**方法1：使用phpMyAdmin**
1. 登录phpMyAdmin
2. 选择数据库 `iframe_shortlink`
3. 点击"SQL"标签
4. 复制并执行以下SQL：
```sql
ALTER TABLE `links` 
ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL COMMENT '页面标题' AFTER `domain`,
ADD COLUMN `meta_description` TEXT DEFAULT NULL COMMENT '页面描述' AFTER `meta_title`,
ADD COLUMN `meta_image` TEXT DEFAULT NULL COMMENT '缩略图URL' AFTER `meta_description`;
```

**方法2：使用命令行**
```bash
mysql -u root -p iframe_shortlink < database_update.sql
```

### 第三步：更新现有链接的meta信息

访问批量更新工具：
```
http://huodong.fszi.org/update_meta.php
```

这个工具会：
- 查找所有没有meta信息的链接（最多100条）
- 逐一访问原始URL并提取meta信息
- 更新到数据库

**注意**：
- 处理可能需要几分钟时间
- 每个链接间隔0.5秒，避免对目标网站造成压力
- 如果提取失败，会跳过该链接

### 第四步：测试提取功能

访问测试工具，测试某个URL是否能正常提取：
```
http://huodong.fszi.org/test_meta.php?code=你的短链代码
```

或者使用诊断工具中的测试功能。

### 第五步：验证结果

1. 访问 `check_meta.php` 查看更新结果
2. 在浏览器中访问短链，查看页面源码（Ctrl+U），确认meta标签是否正确
3. 在微信中测试转发

## 常见问题

### Q1: 提取失败怎么办？

**可能原因：**
- 目标网站阻止了爬虫访问
- 网络连接问题
- 页面没有Open Graph标签
- PHP的file_get_contents函数被禁用

**解决方法：**
1. 检查 `test_meta.php` 的输出，查看具体错误
2. 如果目标网站需要登录，可能无法提取
3. 如果file_get_contents被禁用，需要使用curl（需要修改代码）

### Q2: 提取到的信息不准确？

某些网站可能：
- 没有设置Open Graph标签
- 只有基本的title标签
- 图片URL是相对路径

系统会：
- 优先使用og:title，如果没有则使用title
- 优先使用og:description，如果没有则使用description
- 自动将相对图片URL转换为绝对URL

### Q3: 微信中还是显示旧信息？

微信会缓存页面信息，需要：
1. 清除微信缓存：微信 → 设置 → 通用 → 存储空间 → 清理缓存
2. 等待24-48小时，微信会自动重新抓取
3. 使用微信调试工具强制刷新（需要公众号权限）

### Q4: 新创建的链接也没有meta信息？

1. 检查 `extractMetaInfo()` 函数是否正常工作
2. 查看PHP错误日志
3. 测试目标URL是否能被访问
4. 检查网络连接和防火墙设置

## 验证Meta标签

访问短链后，查看页面源码（右键 → 查看源码），应该看到：

```html
<meta property="og:title" content="实际的标题">
<meta property="og:description" content="实际的描述">
<meta property="og:image" content="图片URL">
```

如果看到的是默认值，说明数据库中确实没有提取到meta信息。

## 技术支持

如果问题仍未解决，请：
1. 运行 `check_meta.php` 获取诊断信息
2. 运行 `test_meta.php?code=你的短链代码` 测试提取功能
3. 查看PHP错误日志
4. 检查数据库字段是否已添加

