<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 启动会话
session_start();

// 销毁会话
$_SESSION = array();
session_destroy();

// 重定向到登录页面
header('Location: login.php');
exit;
?>