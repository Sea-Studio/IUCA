<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 包含数据库连接文件
require_once 'db.php';

// 获取数据库连接
$db = require 'db.php';

// 创建用户表
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_admin TINYINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 创建IDC信息表
$db->exec("CREATE TABLE IF NOT EXISTS idc_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idc_name VARCHAR(100) NOT NULL,
    website VARCHAR(255) NOT NULL,
    company_name VARCHAR(100),
    contact_type VARCHAR(50) NOT NULL,
    contact_info TEXT NOT NULL,
    email VARCHAR(100) NOT NULL,
    logo_filename VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'normal',
    authentication VARCHAR(20) NOT NULL DEFAULT 'unverified',
    tags TEXT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// 创建认证类型表
$db->exec("CREATE TABLE IF NOT EXISTS authentication_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(100) NOT NULL
)");

// 插入默认认证类型
$auth_types = [
    ['name' => '未认证', 'code' => 'unverified', 'icon' => 'gray_v.svg'],
    ['name' => '普通认证', 'code' => 'normal', 'icon' => 'orange_v.svg'],
    ['name' => '高级认证', 'code' => 'premium', 'icon' => 'red_v.svg'],
    ['name' => '企业认证', 'code' => 'enterprise', 'icon' => 'blue_v.svg']
];
foreach ($auth_types as $auth) {
    $stmt = $db->prepare("SELECT id FROM authentication_types WHERE code = :code");
    $stmt->bindParam(':code', $auth['code']);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        $stmt = $db->prepare("INSERT INTO authentication_types (name, code, icon) VALUES (:name, :code, :icon)");
        $stmt->bindParam(':name', $auth['name']);
        $stmt->bindParam(':code', $auth['code']);
        $stmt->bindParam(':icon', $auth['icon']);
        $stmt->execute();
    }
}

// 创建默认管理员用户
$username = 'admin';
$password = password_hash('123456', PASSWORD_DEFAULT);
$email = 'admin@example.com';

// 检查管理员用户是否已存在
$stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
$stmt->bindParam(':username', $username);
$stmt->execute();
$row = $stmt->fetch();

// 如果不存在，则创建
if (!$row) {
    $stmt = $db->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (:username, :password, :email, 1)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
}

// 创建状态表
$db->exec("CREATE TABLE IF NOT EXISTS status_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
)");

// 创建IDC标识码表
$db->exec("CREATE TABLE IF NOT EXISTS idcbsm (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL UNIQUE,
    idc_info_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idc_info_id) REFERENCES idc_info(id)
)");

// 插入默认状态
$statuses = ['正常', '跑路', '未知', '倒闭'];
foreach ($statuses as $status) {
    $stmt = $db->prepare("SELECT id FROM status_types WHERE name = :name");
    $stmt->bindParam(':name', $status);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        $stmt = $db->prepare("INSERT INTO status_types (name) VALUES (:name)");
        $stmt->bindParam(':name', $status);
        $stmt->execute();
    }
}

// 添加认证类型表 (如果不存在)
$db->exec("CREATE TABLE IF NOT EXISTS authentication_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(100) NOT NULL
)");

// 检查认证类型表是否为空
$stmt = $db->query("SELECT COUNT(*) as count FROM authentication_types");
$row = $stmt->fetch();
$auth_count = $row['count'];
if ($auth_count == 0) {
    // 插入认证类型数据
    $auth_types = [
        ['name' => '未认证', 'code' => 'unverified', 'icon' => 'gray_v.svg'],
        ['name' => '普通认证', 'code' => 'standard', 'icon' => 'blue_v.svg'],
        ['name' => '高级认证', 'code' => 'premium', 'icon' => 'gold_v.svg'],
        ['name' => '企业认证', 'code' => 'enterprise', 'icon' => 'gold_v.svg']
    ];
    
    $stmt = $db->prepare("INSERT INTO authentication_types (name, code, icon) VALUES (:name, :code, :icon)");
    foreach ($auth_types as $auth) {
        $stmt->bindParam(':name', $auth['name']);
        $stmt->bindParam(':code', $auth['code']);
        $stmt->bindParam(':icon', $auth['icon']);
        $stmt->execute();
    }
}

// 创建黑名单表
$db->exec("CREATE TABLE IF NOT EXISTS blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_info TEXT NOT NULL,
    contact_type VARCHAR(50) NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 创建运营方类型表
$db->exec("CREATE TABLE IF NOT EXISTS operator_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 插入运营方类型数据
$operator_types = [
    ['name' => '个体工商户有证经营', 'code' => 'individual_licensed'],
    ['name' => '个体工商户无证经营', 'code' => 'individual_unlicensed'],
    ['name' => '企业有证经营', 'code' => 'enterprise_licensed'],
    ['name' => '企业无证经营', 'code' => 'enterprise_unlicensed'],
    ['name' => '个人经营', 'code' => 'personal'],
    ['name' => '非营利性企业有证经营', 'code' => 'nonprofit_licensed'],
    ['name' => '非营利性企业无证经营', 'code' => 'nonprofit_unlicensed'],
    ['name' => '未知', 'code' => 'unknown']
];

$stmt = $db->prepare("INSERT INTO operator_types (name, code) VALUES (:name, :code) ON DUPLICATE KEY UPDATE name = name");
foreach ($operator_types as $type) {
    $stmt->bindParam(':name', $type['name']);
    $stmt->bindParam(':code', $type['code']);
    $stmt->execute();
}

// 为idc_info表添加blacklist字段（如果不存在）
$stmt = $db->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'idc_info' AND COLUMN_NAME = 'is_blacklisted'");
$stmt->execute();
$row = $stmt->fetch();
if ($row['count'] == 0) {
    $db->exec("ALTER TABLE idc_info ADD COLUMN is_blacklisted TINYINT NOT NULL DEFAULT 0");
}

// 为idc_info表添加认证字段（如果不存在）
$stmt = $db->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'idc_info' AND COLUMN_NAME = 'authentication'");
$stmt->execute();
$row = $stmt->fetch();
if ($row['count'] == 0) {
    $db->exec("ALTER TABLE idc_info ADD COLUMN authentication VARCHAR(20) DEFAULT 'unverified'");
}

// 在idc_info表中添加运营方类型外键字段（如果不存在）
$stmt = $db->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'idc_info' AND COLUMN_NAME = 'operator_type_id'");
$stmt->execute();
$row = $stmt->fetch();
if ($row['count'] == 0) {
    $db->exec("ALTER TABLE idc_info ADD COLUMN operator_type_id INT DEFAULT NULL, ADD FOREIGN KEY (operator_type_id) REFERENCES operator_types(id)");
}

// PDO连接会在脚本结束时自动关闭，无需手动关闭
// 关闭数据库连接
// $db = null;

echo '数据库初始化完成！';
?>