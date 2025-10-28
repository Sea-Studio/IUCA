<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @copyright © 瀚海云创IDC信息查询平台 及 瀚海云科技
 * @license MPL
 */

$page_title = 'IDC注册 - 瀚海云创IDC查询';
require_once 'include/header.php';

// 包含验证码生成和邮件发送功能
require_once 'include/functions.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证验证码
    if (!verify_captcha($_POST['email'], $_POST['captcha'])) {
        echo '<div class="alert alert-error">验证码无效或已过期，请重新获取</div>';
    } else {
        // 处理文件上传
        $logo_filename = '';
        if (!empty($_FILES['logo']['name'])) {
            $target_dir = 'images/';
            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $logo_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $logo_filename;
            
            // 检查文件大小
            if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                echo '<div class="alert alert-error">文件大小超过5MB限制</div>';
                $upload_ok = false;
            } else {
                // 允许的文件格式
                $allowed_formats = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                if (!in_array($file_extension, $allowed_formats)) {
                    echo '<div class="alert alert-error">只允许JPG, JPEG, PNG, GIF和SVG格式的文件</div>';
                    $upload_ok = false;
                } else {
                    // 移动上传文件
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                        $upload_ok = true;
                    } else {
                        echo '<div class="alert alert-error">上传文件失败</div>';
                        $upload_ok = false;
                    }
                }
            }
        } else {
            $upload_ok = true;
        }
        
        if ($upload_ok) {
            // 保存数据到数据库
            require_once 'include/db.php';
            $db = require 'include/db.php';
            
            $stmt = $db->prepare("INSERT INTO idc_info (idc_name, website, company_name, contact_type, contact_info, email, logo_filename)
                                 VALUES (:idc_name, :website, :company_name, :contact_type, :contact_info, :email, :logo_filename)");
            $stmt->bindParam(':idc_name', $_POST['idc_name'], PDO::PARAM_STR);
            $stmt->bindParam(':website', $_POST['website'], PDO::PARAM_STR);
            $stmt->bindParam(':company_name', $_POST['company_name'], PDO::PARAM_STR);
            $stmt->bindParam(':contact_type', $_POST['contact_type'], PDO::PARAM_STR);
            $stmt->bindParam(':contact_info', $_POST['contact_info'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
            $stmt->bindParam(':logo_filename', $logo_filename, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                // 获取新插入的IDC记录ID
                $idc_info_id = $db->lastInsertId();
                
                // 生成10位随机标识码（大写字母和数字）
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $identifier = '';
                // 使用更好的随机数生成函数mt_rand以提高随机性
                for ($i = 0; $i < 10; $i++) {
                    $identifier .= $characters[mt_rand(0, strlen($characters) - 1)];
                }
                
                // 将标识码插入到idcbsm表
                $stmt_bsm = $db->prepare("INSERT INTO idcbsm (identifier, idc_info_id, created_at)
                                     VALUES (:identifier, :idc_info_id, CURRENT_TIMESTAMP)");
                $stmt_bsm->bindParam(':identifier', $identifier, PDO::PARAM_STR);
                $stmt_bsm->bindParam(':idc_info_id', $idc_info_id, PDO::PARAM_INT);
                $stmt_bsm->execute();
                
                echo '<div class="alert alert-success">注册成功！您的标识码是：' . $identifier . '</div>';
                echo '<a href="index.php" class="btn btn-primary">返回首页</a>';
            } else {
                $errorInfo = $db->errorInfo();
                echo '<div class="alert alert-error">注册失败: ' . $errorInfo[2] . '</div>';
            }
            
            // PDO连接会在脚本结束时自动关闭，无需手动关闭
        }
    }
}
?>

<div class="container">
    <h1>IDC注册</h1>
    
    <form method="post" action="register.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="idc_name">IDC名称 <span style="color: red;">*</span></label>
            <input type="text" id="idc_name" name="idc_name" required placeholder="请输入IDC名称">
        </div>
        <div class="form-group">
            <label for="website">官网 <span style="color: red;">*</span></label>
            <input type="text" id="website" name="website" required placeholder="请输入官网地址">
        </div>
        <div class="form-group">
            <label for="company_name">公司名称</label>
            <input type="text" id="company_name" name="company_name" placeholder="请输入公司名称（选填）">
        </div>
        <div class="form-group">
            <label for="contact_type">联系方式 <span style="color: red;">*</span></label>
            <select id="contact_type" name="contact_type" required>
                <option value="微信">微信</option>
                <option value="QQ">QQ</option>
                <option value="电话">电话</option>
            </select>
        </div>
        <div class="form-group">
            <label for="contact_info">联系方式详情 <span style="color: red;">*</span></label>
            <input type="text" id="contact_info" name="contact_info" required placeholder="请输入微信/QQ/电话号码">
        </div>
        <div class="form-group">
            <label for="email">邮箱 <span style="color: red;">*</span></label>
            <input type="email" id="email" name="email" required placeholder="请输入邮箱地址">
        </div>
        <div class="form-group captcha-group">
            <div class="captcha-input">
                <label for="captcha">验证码 <span style="color: red;">*</span></label>
                <input type="text" id="captcha" name="captcha" required placeholder="请输入验证码">
            </div>
            <div class="captcha-btn">
                <label>&nbsp;</label>
                <button type="button" id="send_captcha" class="btn btn-secondary">发送验证码</button>
            </div>
        </div>
        <div class="form-group">
            <label for="logo">上传IDC LOGO（最大5MB）</label>
            <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.gif,.svg">
        </div>

        <button type="submit" class="btn btn-primary">注册</button>
        <a href="index.php" class="btn btn-secondary">返回首页</a>
    </form>
</div>

<script>
    // 发送验证码
    document.getElementById('send_captcha').addEventListener('click', function() {
        var email = document.getElementById('email').value;
        if (!email) {
            alert('请输入邮箱地址');
            return;
        }

        // 禁用按钮，防止重复发送
        this.disabled = true;
        this.textContent = '发送中...';

        // 发送请求到服务器获取验证码
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/send_captcha.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                document.getElementById('send_captcha').disabled = false;
                document.getElementById('send_captcha').textContent = '发送验证码';

                if (xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('验证码发送成功，请查收');

                        // 倒计时
                        var countdown = 60;
                        document.getElementById('send_captcha').disabled = true;
                        var timer = setInterval(function() {
                            document.getElementById('send_captcha').textContent = '重新发送(' + countdown + ')';
                            countdown--;
                            if (countdown < 0) {
                                clearInterval(timer);
                                document.getElementById('send_captcha').textContent = '发送验证码';
                                document.getElementById('send_captcha').disabled = false;
                            }
                        }, 1000);
                    } else {
                        alert('发送失败: ' . response.message);
                    }
                } else {
                    alert('发送失败，请稍后重试');
                }
            }
        };
        xhr.send('email=' + encodeURIComponent(email));
    });
</script>

<?php
require_once 'include/footer.php';
?>