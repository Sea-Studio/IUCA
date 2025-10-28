<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @copyright © 瀚海云创IDC信息查询平台 及 瀚海云科技
 * 
 * 此源代码的使用受MPL 2.0许可证的约束。
 * 许可证的完整文本可以在 https://www.mozilla.org/en-US/MPL/2.0/ 找到。
 */
 
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '您的查询平台名称'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">您的查询平台名称</a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">首页</a>
                </li>
                <li class="nav-item">
                    <a href="blacklist.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blacklist.php' ? 'active' : ''; ?>">黑名单查询</a>
                </li>
                <li class="nav-item">
                    <a href="register.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">注册IDC</a>
                </li>
            </ul>
        </div>
    </nav>