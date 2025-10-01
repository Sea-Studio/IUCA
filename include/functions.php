<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 检查是否有相同联系方式或邮箱的其他IDC
function check_related_idcs($contact_info, $email, $exclude_id = null) {
    require_once 'db.php';
    $db = require 'db.php';
    
    // 查询相同联系方式或邮箱的IDC
    $stmt = $db->prepare("SELECT * FROM idc_info WHERE (contact_info = :contact_info OR email = :email) " . 
                         ($exclude_id ? "AND id != :exclude_id" : "") . "");
    $stmt->bindParam(':contact_info', $contact_info);
    $stmt->bindParam(':email', $email);
    if ($exclude_id) {
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $related_idcs = $stmt->fetchAll();
    
    // PDO连接会在脚本结束时自动关闭，无需手动关闭
    return $related_idcs;
}

// 生成随机验证码
function generate_captcha($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// 发送验证码到邮箱
function send_captcha($email, $captcha) {
    // 启动会话
    session_start();
    
    // 检查是否在冷却期内
    $cooling_period = 30; // 60秒冷却期
    if (isset($_SESSION['last_captcha_sent']) && $_SESSION['last_captcha_sent'][$email] + $cooling_period > time()) {
        $remaining = $_SESSION['last_captcha_sent'][$email] + $cooling_period - time();
        return ['success' => false, 'message' => '验证码发送过于频繁，请' . $remaining . '秒后再试'];
    }

    // 保存验证码到会话
    $_SESSION['captcha'] = [
        'code' => $captcha,
        'email' => $email,
        'expires_at' => time() + 600 // 10分钟有效期
    ];

    // 记录发送时间
    $_SESSION['last_captcha_sent'][$email] = time();

    // 引入邮件服务
    require_once 'EmailService.php';

    try {
        // 创建邮件服务实例
        $emailService = new EmailService();

        // 发送验证码邮件
        return $emailService->sendVerificationCode($email, $captcha);
    } catch (Exception $e) {
        return ['success' => false, 'message' => '发送邮件时发生错误: ' . $e->getMessage()];
    }
}

// 验证验证码
function verify_captcha($email, $captcha) {
    session_start();
    
    if (!isset($_SESSION['captcha']) || empty($_SESSION['captcha'])) {
        return false;
    }
    
    $stored_captcha = $_SESSION['captcha'];
    
    // 检查是否过期
    if (time() > $stored_captcha['expires_at']) {
        unset($_SESSION['captcha']);
        return false;
    }
    
    // 检查邮箱和验证码是否匹配
    if ($stored_captcha['email'] != $email || $stored_captcha['code'] != $captcha) {
        return false;
    }
    
    // 验证成功，删除验证码
    unset($_SESSION['captcha']);
    return true;
}

// 检查是否已安装
function check_installation() {
    // 对于MySQL，我们通过尝试连接数据库并检查表是否存在来判断
    try {
        require_once 'db.php';
        $db = require 'db.php';
        
        // 检查users表是否存在
        $stmt = $db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'users'");
        $row = $stmt->fetch();
        return $row['count'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

// 验证URL格式是否以http://或https://开头
function verify_url_format($url) {
    // 使用正则表达式验证URL格式
    $pattern = '/^https?:\/\/[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(:\d+)?(\/[^\s]*)?$/';
    return preg_match($pattern, $url) === 1;
}
?>