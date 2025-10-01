<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */

// 启用输出缓冲区，防止BOM头或其他前导输出影响响应头
ob_start();

// 允许跨域请求
header('Access-Control-Allow-Origin: *');
// 确保Content-Type设置为application/json
header('Content-Type: application/json; charset=utf-8');

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

// 获取POST数据
$to_email = $_POST['to_email'] ?? '';

// 验证数据
if (empty($to_email)) {
    echo json_encode(['success' => false, 'message' => '请输入收件人邮箱']);
    exit;
}

// 引入邮件服务
require_once '../include/EmailService.php';

// 发送测试邮件
try {
    // 创建邮件服务实例
    $emailService = new EmailService();

    // 发送测试邮件
    $subject = 'IUCA - 测试邮件';
    $body = "这是一封测试邮件，用于验证您的邮箱配置是否正确。\r\n\r\n如果您收到这封邮件，说明您的邮箱配置正常。";

    $result = $emailService->send($to_email, $subject, $body);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => '测试邮件发送成功，请查收']);
    } else {
        echo json_encode(['success' => false, 'message' => '测试邮件发送失败: ' . $result['message']]);
    }
} catch (Exception $e) {
    // 确保输出有效的JSON
    echo json_encode(['success' => false, 'message' => '发送测试邮件时发生错误: ' . $e->getMessage()]);
} catch (Error $e) {
    // 捕获PHP错误
    echo json_encode(['success' => false, 'message' => 'PHP错误: ' . $e->getMessage()]);
} finally {
    // 刷新输出缓冲区
    ob_end_flush();
    // 确保脚本终止
    exit;
}