<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @copyright © 瀚海云创IDC信息查询平台 及 瀚海云科技
 * 
 * 此源代码的使用受MPL 2.0许可证的约束。
 * 许可证的完整文本可以在 https://www.mozilla.org/en-US/MPL/2.0/ 找到。
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
        $db = null;
    }
}

$page_title = '黑名单查询 - 您的查询平台名称';
require_once 'include/header.php';
?>

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

<?php
require_once 'include/footer.php';
?>