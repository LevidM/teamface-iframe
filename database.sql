-- 数据库表结构
-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `iframe_shortlink` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `iframe_shortlink`;

-- 创建链接表
CREATE TABLE IF NOT EXISTS `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `short_code` varchar(20) NOT NULL COMMENT '短链代码',
  `original_url` text NOT NULL COMMENT '原始URL',
  `domain` varchar(255) DEFAULT NULL COMMENT '提取的域名（用于CSP）',
  `meta_title` varchar(255) DEFAULT NULL COMMENT '页面标题（微信转发）',
  `meta_description` text DEFAULT NULL COMMENT '页面描述（微信转发）',
  `meta_image` text DEFAULT NULL COMMENT '缩略图URL（微信转发）',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `access_count` int(11) NOT NULL DEFAULT '0' COMMENT '访问次数',
  `last_accessed` datetime DEFAULT NULL COMMENT '最后访问时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `short_code` (`short_code`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='链接表';

