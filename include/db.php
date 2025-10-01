<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 包含MySQL配置文件
$config = require 'mysql_config.php';

// 创建MySQL连接
try {
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';port=' . $config['port'] . ';charset=' . $config['charset'];
    $db = new PDO($dsn, $config['username'], $config['password']);
    // 设置错误模式为异常
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 设置默认获取模式为关联数组
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

// 返回数据库连接对象
return $db;
?>