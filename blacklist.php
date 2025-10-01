<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */


session_start();


require_once 'include/db.php';
require_once 'include/blacklist_functions.php';


$test_result = '';
$db = null;


if (isset($_POST['test_contact'])) {
    try {
        $db = require 'include/db.php';
        $contact_info = $_POST['contact_info'];
        $is_blacklisted = is_in_blacklist($db, $contact_info);

        $escaped_contact_info = htmlspecialchars($contact_info, ENT_QUOTES, 'UTF-8');

        if ($is_blacklisted) {
            $test_result = "<div class='alert alert-error'>联系方式 '$escaped_contact_info' 在黑名单中</div>";
        } else {
            $test_result = "<div class='alert alert-success'>联系方式 '$escaped_contact_info' 不在黑名单中</div>";
        }
    } catch (Exception $e) {

        $escaped_error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $test_result = "<div class='alert alert-error'>测试失败: $escaped_error</div>";
    } finally {
        if ($db) {
            $db->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>黑名单查询</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 20px;
        background-color: #f8f9fa;
    }
    .container {
        max-width: 600px;
        margin: 0 auto;
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    h1 {
        color: #343a40;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    input[type='text'] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 500;
        transition: background-color 0.3s;
        border: none;
        cursor: pointer;
    }
    .btn:hover {
        background-color: #0069d9;
    }
    .alert {
        padding: 15px;
        margin-top: 20px;
        border-radius: 4px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .info-box {
        background-color: #e9ecef;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>黑名单查询</h1>
        
        <div class="info-box">
            <p>使用此页面查询您所使用的IDC是否存在问题。</p>
        </div>
        
        <form method="post">
            <div class="form-group">
                <label for="contact_info">联系方式/邮箱：</label>
                <input type="text" id="contact_info" name="contact_info" required placeholder="输入手机号、邮箱等">
            </div>
            
            <button type="submit" name="test_contact" class="btn">查询</button>
        </form>
        
        <?php echo $test_result; ?>
    </div>
</body>
</html>