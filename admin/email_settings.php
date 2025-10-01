<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 启动会话
session_start();

// 检查是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 检查演示模式
$demo_mode = require '../include/demo_mode.php';
if ($demo_mode) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IUCA - 邮箱配置</title>
        <link rel="stylesheet" href="../css/style.css">
        <style>
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .alert-error {
                color: #721c24;
                background-color: #f8d7da;
                border-color: #f5c6cb;
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            .btn {
                padding: 10px 15px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                display: inline-block;
            }
            .btn:hover {
                background-color: #0069d9;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>IUCA - 邮箱配置</h1>
            <div class="alert alert-error">演示模式下不允许修改邮箱配置</div>
            <a href="index.php" class="btn">返回首页</a>
        </div>
    </body>
    </html>';
    exit;
}

// 连接数据库
require_once '../include/db.php';
$db = require '../include/db.php';

// 获取当前管理员信息
$admin_id = $_SESSION['admin_id'];
$stmt = $db->prepare("SELECT is_admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// 检查是否为超级管理员
if ($admin['is_admin'] != 1) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IUCA - 邮箱配置</title>
        <link rel="stylesheet" href="../css/style.css">
    </head>
    <body>
        <div class="container">
            <h1>IUCA - 邮箱配置</h1>
            <div class="alert alert-error">您没有权限执行此操作</div>
            <a href="index.php" class="btn btn-primary">返回首页</a>
        </div>
    </body>
    </html>';
    exit;
}

// 读取当前邮箱配置
$email_config = [];
$config_file = '../include/email_config.php';
if (file_exists($config_file)) {
    $email_config = require $config_file;
}

// 处理表单提交
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_config = [
        'host' => $_POST['smtp_host'],
        'port' => $_POST['smtp_port'],
        'username' => $_POST['smtp_username'],
        'password' => $_POST['smtp_password'],
        'encryption' => $_POST['smtp_encryption']
    ];

    // 保存配置到文件
    $config_content = '<?php return ' . var_export($new_config, true) . '; ?>';
    if (file_put_contents($config_file, $config_content) !== false) {
        $success = '邮箱配置更新成功！';
        // 重新加载配置
        $email_config = $new_config;
    } else {
        $error = '保存配置失败，请检查文件权限';
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 邮箱配置</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], input[type="password"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0069d9;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>IUCA - 邮箱配置</h1>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <h3>SMTP配置说明</h3>
            请填写您的邮箱SMTP服务器信息，用于系统发送验证码邮件。以下是各字段的详细说明：
            <ul>
                <li><strong>SMTP服务器</strong>：您邮箱提供商的SMTP服务器地址（如smtp.163.com）</li>
                <li><strong>SMTP端口</strong>：SMTP服务器使用的端口（通常为25、465、587）</li>
                <li><strong>SMTP用户名</strong>：您的邮箱地址</li>
                <li><strong>SMTP密码</strong>：对于大多数邮箱提供商，这是您的邮箱密码或应用专用密码（不是登录密码）</li>
                <li><strong>加密方式</strong>：连接SMTP服务器使用的加密方式（无、TLS或SSL）</li>
            </ul>

            <h3>常见邮箱提供商配置示例</h3>
            <ul>
                <li><strong>163邮箱</strong>：SMTP服务器=smtp.163.com, 端口=465, 加密方式=ssl</li>
                <li><strong>QQ邮箱</strong>：SMTP服务器=smtp.qq.com, 端口=465, 加密方式=ssl</li>
                <li><strong>Gmail</strong>：SMTP服务器=smtp.gmail.com, 端口=587, 加密方式=tls</li>
                <li><strong>网易邮箱</strong>：SMTP服务器=smtp.126.com, 端口=465, 加密方式=ssl</li>
                <li><strong>新浪邮箱</strong>：SMTP服务器=smtp.sina.com, 端口=465, 加密方式=ssl</li>
                <li><strong>Outlook</strong>：SMTP服务器=smtp.office365.com, 端口=587, 加密方式=tls</li>
            </ul>

            <h3>重要提示</h3>
            <ul>
                <li>QQ邮箱和Gmail需要开启SMTP服务并生成应用专用密码，不能使用邮箱登录密码</li>
                <li>163邮箱需要开启POP3/SMTP服务</li>
                <li>配置完成后，建议发送测试邮件验证配置是否正确</li>
                <li>如遇到连接问题，请检查服务器端口是否开放，或尝试使用其他加密方式</li>
            </ul>
        </div>
        
        <form method="post" action="email_settings.php">
            <div class="form-group">
                <label for="smtp_host">SMTP服务器</label>
                <input type="text" id="smtp_host" name="smtp_host" required value="<?php echo isset($email_config['host']) ? htmlspecialchars($email_config['host']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="smtp_port">SMTP端口</label>
                <input type="number" id="smtp_port" name="smtp_port" required value="<?php echo isset($email_config['port']) ? htmlspecialchars($email_config['port']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="smtp_username">SMTP用户名</label>
                <input type="text" id="smtp_username" name="smtp_username" required value="<?php echo isset($email_config['username']) ? htmlspecialchars($email_config['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="smtp_password">SMTP密码</label>
                <input type="password" id="smtp_password" name="smtp_password" required value="<?php echo isset($email_config['password']) ? htmlspecialchars($email_config['password']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="smtp_encryption">加密方式</label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="" <?php echo (isset($email_config['encryption']) && $email_config['encryption'] == '') ? 'selected' : ''; ?>>无</option>
                    <option value="tls" <?php echo (isset($email_config['encryption']) && $email_config['encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo (isset($email_config['encryption']) && $email_config['encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">保存配置</button>
            <a href="index.php" class="btn btn-secondary">返回首页</a>
        </form>
    </div>
</body>
</html>