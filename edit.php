<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @copyright © 瀚海云创IDC信息查询平台 及 瀚海云科技
 * @license MPL
 */

$page_title = '修改IDC信息 - 您的查询平台名称';
require_once 'include/header.php';

// 包含工具函数
require_once 'include/functions.php';
// 包含黑名单功能函数
require_once 'include/blacklist_functions.php';

// 检查是否提供了ID参数
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-error">无效的请求</div>';
    echo '<a href="index.php" class="btn btn-primary">返回首页</a>';
    exit;
}

$id = $_GET['id'];

// 连接数据库
require_once 'include/db.php';
$db = require 'include/db.php';

// 获取IDC信息
$stmt = $db->prepare("SELECT * FROM idc_info WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$idc_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$idc_info) {
    echo '<div class="alert alert-error">未找到该IDC信息</div>';
    echo '<a href="index.php" class="btn btn-primary">返回首页</a>';
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否已经验证
    if (isset($_POST['verify_step'])) {
        // 验证验证码
        if (!verify_captcha($idc_info['email'], $_POST['captcha'])) {
            echo '<div class="alert alert-error">验证码无效或已过期，请重新获取</div>';
        } else {
            // 验证通过，检查是否在黑名单中
            // 重新连接数据库
            $db = require 'include/db.php';
            
            // 检查联系方式是否在黑名单中
            $is_blacklisted = is_in_blacklist($db, $_POST['contact_info'] ?? $idc_info['contact_info']);
            
            // 检查邮箱是否在黑名单中
            if (!$is_blacklisted) {
                $is_blacklisted = is_in_blacklist($db, $_POST['email'] ?? $idc_info['email']);
            }
            
            // PDO连接会在脚本结束时自动关闭，无需手动关闭
            
            if ($is_blacklisted) {
                echo '<div class="alert alert-error">您的联系方式或邮箱已被列入黑名单，无法修改信息</div>';
            } else {
                // 验证通过，显示修改表单
                $show_edit_form = true;
            }
        }
    } else {
        // 处理修改提交
        // 验证验证码
        if (!verify_captcha($idc_info['email'], $_POST['captcha'])) {
            echo '<div class="alert alert-error">验证码无效或已过期，请重新获取</div>';
        } else {
            // 处理文件上传
            $logo_filename = $idc_info['logo_filename'];
            if (!empty($_FILES['logo']['name'])) {
                $target_dir = 'images/';
                $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $new_logo_filename = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_logo_filename;
                
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
                            // 删除旧文件
                            if (!empty($logo_filename) && file_exists($target_dir . $logo_filename)) {
                                unlink($target_dir . $logo_filename);
                            }
                            $logo_filename = $new_logo_filename;
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
                // 更新数据库
                // 检查是否在黑名单中
                $is_blacklisted = false;
                
                // 检查联系方式是否在黑名单中
                $is_blacklisted = is_in_blacklist($db, $_POST['contact_info']);
                
                // 检查邮箱是否在黑名单中
                if (!$is_blacklisted) {
                    $is_blacklisted = is_in_blacklist($db, $_POST['email']);
                }
                
                // 更新is_blacklisted字段
                update_idc_blacklist_status($db, $id, $is_blacklisted);
                
                // 准备更新语句
                $stmt = $db->prepare("UPDATE idc_info SET
                                             idc_name = :idc_name,
                                             website = :website,
                                             company_name = :company_name,
                                             contact_type = :contact_type,
                                             contact_info = :contact_info,
                                             email = :email,
                                             logo_filename = :logo_filename,
                                             is_blacklisted = :is_blacklisted,
                                             updated_at = CURRENT_TIMESTAMP
                                             WHERE id = :id");
                $is_blacklisted_value = $is_blacklisted ? 1 : 0;
                $stmt->bindParam(':is_blacklisted', $is_blacklisted_value, PDO::PARAM_INT);
                $stmt->bindParam(':idc_name', $_POST['idc_name'], PDO::PARAM_STR);
                $stmt->bindParam(':website', $_POST['website'], PDO::PARAM_STR);
                $stmt->bindParam(':company_name', $_POST['company_name'], PDO::PARAM_STR);
                $stmt->bindParam(':contact_type', $_POST['contact_type'], PDO::PARAM_STR);
                $stmt->bindParam(':contact_info', $_POST['contact_info'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
                $stmt->bindParam(':logo_filename', $logo_filename, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">修改成功！</div>';
                    echo '<a href="index.php" class="btn btn-primary">返回首页</a>';
                } else {
                    echo '<div class="alert alert-error">修改失败: ' . implode(', ', $db->errorInfo()) . '</div>';
                }
            }
        }
    }
}

// PDO连接会在脚本结束时自动关闭，无需手动关闭
?>

<div class="container">
    <h1>修改IDC信息</h1>
    
    <?php if (!isset($show_edit_form)): ?>
    <!-- 验证码验证表单 -->
    <div class="verify-form">
        <p>请输入发送到邮箱 <strong><?php echo htmlspecialchars($idc_info['email']); ?></strong> 的验证码以验证身份</p>
        <form method="post" action="edit.php?id=<?php echo $id; ?>">
            <input type="hidden" name="verify_step" value="1">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_step']) && !verify_captcha($idc_info['email'], $_POST['captcha'])) {
                echo '<div class="alert alert-error">验证码无效或已过期，请重新获取</div>';
            } ?>
                <div class="captcha-input">
                    <label for="captcha">验证码 <span style="color: red;">*</span></label>
                    <input type="text" id="captcha" name="captcha" required placeholder="请输入验证码">
                </div>
                <div class="captcha-btn">
                    <label>&nbsp;</label>
                    <button type="button" id="send_captcha" class="btn btn-secondary">发送验证码</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">验证</button>
            <a href="index.php" class="btn btn-secondary">返回首页</a>
        </form>
    </div>
    <?php else: ?>
    <!-- 修改信息表单 -->
    <form method="post" action="edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label for="idc_name">IDC名称 <span style="color: red;">*</span></label>
            <input type="text" id="idc_name" name="idc_name" required value="<?php echo htmlspecialchars($idc_info['idc_name']); ?>">
        </div>
        <div class="form-group">
            <label for="website">官网 <span style="color: red;">*</span></label>
            <input type="text" id="website" name="website" required value="<?php echo htmlspecialchars($idc_info['website']); ?>">
        </div>
        <div class="form-group">
            <label for="company_name">公司名称</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($idc_info['company_name']); ?>">
        </div>
        <div class="form-group">
            <label for="contact_type">联系方式 <span style="color: red;">*</span></label>
            <select id="contact_type" name="contact_type" required>
                <option value="微信" <?php echo ($idc_info['contact_type'] == '微信') ? 'selected' : ''; ?>>微信</option>
                <option value="QQ" <?php echo ($idc_info['contact_type'] == 'QQ') ? 'selected' : ''; ?>>QQ</option>
                <option value="电话" <?php echo ($idc_info['contact_type'] == '电话') ? 'selected' : ''; ?>>电话</option>
            </select>
        </div>
        <div class="form-group">
            <label for="contact_info">联系方式详情 <span style="color: red;">*</span></label>
            <input type="text" id="contact_info" name="contact_info" required value="<?php echo htmlspecialchars($idc_info['contact_info']); ?>">
        </div>
        <div class="form-group">
            <label for="email">邮箱 <span style="color: red;">*</span></label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($idc_info['email']); ?>">
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
            <?php if (!empty($idc_info['logo_filename'])): ?>
            <div class="logo-preview-container">
                <p>当前LOGO:</p>
                <img src="images/<?php echo htmlspecialchars($idc_info['logo_filename']); ?>" alt="<?php echo htmlspecialchars($idc_info['idc_name']); ?> Logo" class="logo-preview">
            </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">保存修改</button>
        <a href="index.php" class="btn btn-secondary">返回首页</a>
    </form>
    <?php endif; ?>
</div>

<script>
    // 发送验证码
    document.getElementById('send_captcha').addEventListener('click', function() {
        var email = '<?php echo htmlspecialchars($idc_info['email']); ?>';

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
                        alert('发送失败: ' + response.message);
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