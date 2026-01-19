-- 数据库更新：添加微信转发meta信息字段
-- 执行此SQL来更新现有数据库表

USE `iframe_shortlink`;

-- 添加meta信息字段
ALTER TABLE `links` 
ADD COLUMN `meta_title` VARCHAR(255) DEFAULT NULL COMMENT '页面标题' AFTER `domain`,
ADD COLUMN `meta_description` TEXT DEFAULT NULL COMMENT '页面描述' AFTER `meta_title`,
ADD COLUMN `meta_image` TEXT DEFAULT NULL COMMENT '缩略图URL' AFTER `meta_description`;

