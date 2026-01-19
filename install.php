<?php
// 安装脚本 - 创建数据库表
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // 读取SQL文件
    $sql = file_get_contents('database.sql');
    
    // 执行SQL
    $pdo->exec($sql);
    
    echo "数据库安装成功！<br>";
    echo "请删除此文件（install.php）以确保安全。<br>";
    echo "<a href='index.php'>前往首页</a>";
    
} catch (Exception $e) {
    echo "安装失败: " . $e->getMessage();
}

