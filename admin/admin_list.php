<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../include/db.php';
$db = require '../include/db.php';

$admin_id = $_SESSION['admin_id'];
$stmt = $db->prepare("SELECT is_admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin['is_admin'] != 1) {
    echo '<div class="alert alert-error">您没有权限执行此操作</div>';
    echo '<a href="index.php" class="btn btn-primary">返回首页</a>';
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = $_GET['id'];
    
    if ($delete_id == $admin_id) {
        echo '<div class="alert alert-error">不能删除当前登录的管理员</div>';
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND is_admin = 1");
$stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success">管理员删除成功！</div>';
        } else {
            echo '<div class="alert alert-error">删除失败: ' . implode(' ', $stmt->errorInfo()) . '</div>';
        }
    }
}

$stmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE is_admin = 1 ORDER BY created_at DESC");
$stmt->execute();
$admin_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 管理员列表</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/main.js"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>IUCA - 管理员列表</h1>
        
        <a href="add_admin.php" class="btn btn-primary">添加管理员</a>
        <a href="index.php" class="btn btn-secondary">返回首页</a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($admin_list) > 0): ?>
                    <?php foreach ($admin_list as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">删除</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">暂无管理员数据</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmDelete(id, username) {
            if (confirm('确定要删除管理员 ' + username + ' 吗？')) {
                window.location.href = 'admin_list.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>