<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 安装</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>IUCA - 安装</h1>
        
        <?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
        // 检查是否已经安装
        $db_file = 'database.db';
        $installed = file_exists($db_file) && filesize($db_file) > 0;
        
        if ($installed) {
            echo '<div class="alert alert-warning">系统已经安装完成！</div>';
            echo '<a href="index.php" class="btn btn-primary">返回首页</a>';
            echo '<a href="admin/login.php" class="btn btn-secondary">登录后台</a>';
        } else {
            // 处理表单提交
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 包含数据库连接文件
                require_once 'include/db.php';
                require_once 'include/init_db.php';
                
                // 更新管理员信息
                $db = require 'include/db.php';
                $username = $_POST['username'] ?: 'admin';
                $password = password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT);
                $email = $_POST['email'] ?: 'admin@example.com';
                
                $stmt = $db->prepare("UPDATE users SET username = :username, password = :password, email = :email WHERE is_admin = 1");
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->bindValue(':password', $password, PDO::PARAM_STR);
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $stmt->execute();
                
                // 保存邮箱配置
                $email_config = [
                    'host' => $_POST['smtp_host'],
                    'port' => $_POST['smtp_port'],
                    'username' => $_POST['smtp_username'],
                    'password' => $_POST['smtp_password'],
                    'encryption' => $_POST['smtp_encryption']
                ];
                
                file_put_contents('include/email_config.php', '<?php return ' . var_export($email_config, true) . '; ?>');
                
                echo '<div class="alert alert-success">安装成功！</div>';
                echo '<a href="index.php" class="btn btn-primary">前往首页</a>';
                echo '<a href="admin/login.php" class="btn btn-secondary">登录后台</a>';
            } else {
        ?>
        <form method="post" action="install.php">
            <h2>管理员信息</h2>
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" placeholder="默认: admin">
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" placeholder="默认: 123456">
            </div>
            <div class="form-group">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" placeholder="默认: admin@example.com">
            </div>

            <h2>邮箱配置 (用于发送验证码)</h2>
            <div class="alert alert-info">
                请填写您的SMTP服务器信息。如果您不知道这些信息，请咨询您的邮箱服务提供商。
                <br>示例：
                <br> - 163邮箱: SMTP服务器=smtp.163.com, 端口=465, 加密方式=ssl
                <br> - QQ邮箱: SMTP服务器=smtp.qq.com, 端口=465, 加密方式=ssl
                <br> - Gmail: SMTP服务器=smtp.gmail.com, 端口=587, 加密方式=tls
            </div>
            <div class="form-group">
                <label for="smtp_host">SMTP服务器</label>
                <input type="text" id="smtp_host" name="smtp_host" required>
            </div>
            <div class="form-group">
                <label for="smtp_port">SMTP端口</label>
                <input type="number" id="smtp_port" name="smtp_port" required>
            </div>
            <div class="form-group">
                <label for="smtp_username">SMTP用户名</label>
                <input type="text" id="smtp_username" name="smtp_username" required>
            </div>
            <div class="form-group">
                <label for="smtp_password">SMTP密码</label>
                <input type="password" id="smtp_password" name="smtp_password" required>
            </div>
            <div class="form-group">
                <label for="smtp_encryption">加密方式</label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="">无</option>
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">安装系统</button>
        </form>
        <?php
            }
        }
        ?>
    </div>

    <script src="js/main.js"></script>
</body>
</html>