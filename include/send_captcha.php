<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// 包含函数文件
require_once 'functions.php';

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效的请求方法']);
    exit;
}

// 获取邮箱
$email = isset($_POST['email']) ? $_POST['email'] : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => '请输入邮箱地址']);
    exit;
}

// 生成验证码
$captcha = generate_captcha();

// 发送验证码
$result = send_captcha($email, $captcha);

// 返回结果
echo json_encode($result);
?>