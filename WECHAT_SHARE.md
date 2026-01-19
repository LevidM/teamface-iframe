# 微信转发功能说明

## 功能概述

系统已添加微信转发功能，可以在微信中转发短链时显示：
- ✅ **标题**：从原始页面提取的标题
- ✅ **描述**：从原始页面提取的描述信息
- ✅ **缩略图**：从原始页面提取的图片

## 工作原理

1. **自动提取**：创建短链时，系统会自动访问原始页面，提取Open Graph meta标签（og:title, og:description, og:image）
2. **存储信息**：提取的meta信息存储在数据库中
3. **转发显示**：访问短链时，系统会在页面头部输出这些meta标签，供微信等平台识别

## 数据库更新

### 新安装

如果是从零开始安装，直接执行 `database.sql` 即可，已包含新字段。

### 现有数据库更新

如果数据库已经存在，需要执行 `database_update.sql` 来添加新字段：

```sql
ALTER TABLE `links` 
ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL COMMENT '页面标题',
ADD COLUMN `meta_description` TEXT DEFAULT NULL COMMENT '页面描述',
ADD COLUMN `meta_image` TEXT DEFAULT NULL COMMENT '缩略图URL';
```

## 使用方法

### 1. 创建新短链

创建新短链时，系统会自动提取meta信息，无需额外操作。

### 2. 批量更新现有链接

对于已经存在的链接，可以使用 `update_meta.php` 批量更新meta信息：

**方法1：浏览器访问**
```
http://yourdomain.com/update_meta.php
```

**方法2：命令行执行**
```bash
php update_meta.php
```

脚本会：
- 查找所有没有meta信息的链接（最多100条）
- 逐一访问原始URL并提取meta信息
- 更新到数据库

**注意**：建议在服务器空闲时执行，避免对目标网站造成压力。

## 支持的Meta标签

系统会按以下优先级提取：

### 标题（Title）
1. `<meta property="og:title" content="...">` （优先）
2. `<title>...</title>`

### 描述（Description）
1. `<meta property="og:description" content="...">` （优先）
2. `<meta name="description" content="...">`

### 图片（Image）
1. `<meta property="og:image" content="...">`
2. 自动处理相对URL，转换为绝对URL

## 微信转发测试

1. 在微信中打开短链
2. 点击右上角菜单
3. 选择"转发"或"分享到朋友圈"
4. 查看是否显示标题、描述和缩略图

**提示**：微信可能会缓存页面信息，如果更新了meta信息，可能需要等待一段时间才能看到新效果。

## 清除微信缓存

如果转发信息没有更新，可以尝试：

1. **清除微信缓存**：
   - 微信 → 设置 → 通用 → 存储空间 → 清理缓存

2. **使用微信调试工具**：
   - 访问：https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/JS-SDK.html#62
   - 使用调试接口刷新页面信息

3. **等待缓存过期**：
   - 微信通常会在24-48小时后重新抓取页面信息

## 注意事项

1. **提取失败**：如果原始页面没有Open Graph标签，系统会使用默认值
2. **网络超时**：提取meta信息有10秒超时限制
3. **相对URL**：图片URL会自动转换为绝对URL
4. **长度限制**：标题限制255字符，描述限制500字符
5. **性能考虑**：提取meta信息可能需要几秒钟，系统会在后台处理

## 技术细节

### 使用的Meta标签

系统会在 `view.php` 中输出以下meta标签：

```html
<!-- Open Graph (Facebook、微信等) -->
<meta property="og:type" content="website">
<meta property="og:title" content="标题">
<meta property="og:description" content="描述">
<meta property="og:url" content="短链URL">
<meta property="og:image" content="图片URL">

<!-- 微信专用 -->
<meta itemprop="name" content="标题">
<meta itemprop="description" content="描述">
<meta itemprop="image" content="图片URL">
```

### 提取函数

`extractMetaInfo()` 函数位于 `config.php` 中，使用正则表达式提取meta标签，支持：
- 单引号和双引号
- HTML实体解码
- 相对URL转绝对URL
- 字符编码处理

## 故障排除

### 问题：转发时不显示图片

**可能原因**：
1. 原始页面没有og:image标签
2. 图片URL无法访问（需要公网可访问）
3. 图片格式不支持（建议使用JPG/PNG）
4. 图片尺寸不合适（建议1200x630像素）

**解决方法**：
1. 检查原始页面是否包含og:image标签
2. 确保图片URL是公网可访问的绝对URL
3. 使用 `update_meta.php` 重新提取

### 问题：标题或描述显示不正确

**可能原因**：
1. 原始页面没有相应的meta标签
2. 字符编码问题
3. 提取时出错

**解决方法**：
1. 检查原始页面的源代码，确认meta标签是否存在
2. 使用 `update_meta.php` 重新提取
3. 检查数据库中的存储内容

### 问题：提取速度慢

**可能原因**：
1. 目标网站响应慢
2. 网络延迟
3. 页面内容过大

**解决方法**：
- 系统只获取前50KB内容，通常足够提取meta标签
- 提取失败时会使用默认值，不影响使用
- 可以考虑异步处理（未来版本）

