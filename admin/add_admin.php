<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$demo_mode = require '../include/demo_mode.php';
if ($demo_mode) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IUCA - 添加管理员</title>
        <link rel="stylesheet" href="../css/style.css">
    </head>
    <body>
        <div class="container">
            <h1>IUCA - 添加管理员</h1>
            <div class="alert alert-error">演示模式下不允许添加管理员</div>
            <a href="index.php" class="btn btn-primary">返回首页</a>
        </div>
    </body>
    </html>';
    exit;
}

require_once '../include/db.php';
$db = require '../include/db.php';

$admin_id = $_SESSION['admin_id'];
$stmt = $db->prepare("SELECT is_admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin['is_admin'] != 1) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IUCA - 添加管理员</title>
        <link rel="stylesheet" href="../css/style.css">
    </head>
    <body>
        <div class="container">
            <h1>IUCA - 添加管理员</h1>
            <div class="alert alert-error">您没有权限执行此操作</div>
            <a href="index.php" class="btn btn-primary">返回首页</a>
        </div>
    </body>
    </html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $error = '用户名已存在';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $error = '邮箱已存在';
        } else {
            // 添加新管理员
            $stmt = $db->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (:username, :password, :email, 1)");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $success = '管理员添加成功！';
            } else {
                $error = '添加失败: ' . implode(' ', $stmt->errorInfo());
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 添加管理员</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>IUCA - 添加管理员</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="add_admin.php">
            <div class="form-group">
                <label for="username">用户名 <span style="color: red;">*</span></label>
                <input type="text" id="username" name="username" required placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label for="password">密码 <span style="color: red;">*</span></label>
                <input type="password" id="password" name="password" required placeholder="请输入密码">
            </div>
            <div class="form-group">
                <label for="email">邮箱 <span style="color: red;">*</span></label>
                <input type="email" id="email" name="email" required placeholder="请输入邮箱地址">
            </div>

            <button type="submit" class="btn btn-primary">添加管理员</button>
            <a href="index.php" class="btn btn-secondary">返回首页</a>
        </form>
    </div>
</body>
</html>