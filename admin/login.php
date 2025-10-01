<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 后台登录</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">IUCA - 后台登录</h1>
        
        <?php
        /**
         * @copyright © IUCA 及 朝阳热心市民
         * @license MPL
         */

        // 启动会话
        session_start();
        
        // 检查是否已登录
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: index.php');
            exit;
        }
        
        // 处理登录表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once '../include/db.php';
            $db = require '../include/db.php';
            
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            // 查询用户
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND is_admin = 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // 登录成功
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_id'] = $user['id'];
                
                header('Location: index.php');
                exit;
            } else {
                echo '<div class="alert alert-error">用户名或密码错误</div>';
            }
            
            // PDO连接会在脚本结束时自动关闭，无需手动关闭
        }
        ?>

        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="请输入密码">
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
            <a href="../index.php" class="btn btn-secondary">返回首页</a>
        </form>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>