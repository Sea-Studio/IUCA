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

if (isset($_POST['add_blacklist'])) {
    $contact_info = $_POST['contact_info'];
    $contact_type = $_POST['contact_type'];
    $reason = $_POST['reason'];

    $stmt = $db->prepare("INSERT INTO blacklist (contact_info, contact_type, reason) VALUES (:contact_info, :contact_type, :reason)");
    $stmt->bindParam(':contact_info', $contact_info, PDO::PARAM_STR);
    $stmt->bindParam(':contact_type', $contact_type, PDO::PARAM_STR);
    $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
    $stmt->execute();

    $stmt = $db->prepare("UPDATE idc_info SET is_blacklisted = 1 WHERE contact_info = :contact_info OR email = :contact_info");
    $stmt->bindParam(':contact_info', $contact_info, PDO::PARAM_STR);
    $stmt->execute();

    header('Location: blacklist.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $db->prepare("SELECT contact_info FROM blacklist WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $contact_info = $row['contact_info'];

        $stmt = $db->prepare("DELETE FROM blacklist WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $db->prepare("SELECT id FROM blacklist WHERE contact_info = :contact_info");
        $stmt->bindParam(':contact_info', $contact_info, PDO::PARAM_STR);
        $stmt->execute();
        $has_other = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$has_other) {
            $stmt = $db->prepare("UPDATE idc_info SET is_blacklisted = 0 WHERE contact_info = :contact_info OR email = :contact_info");
            $stmt->bindParam(':contact_info', $contact_info, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    header('Location: blacklist.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM blacklist ORDER BY created_at DESC");
$stmt->execute();
$blacklist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PDO连接会在脚本结束时自动关闭，无需手动关闭
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>黑名单管理</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-btn { margin-right: 5px; padding: 5px 10px; }
        .delete-btn { background-color: #f44336; }
        .delete-btn:hover { background-color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>黑名单管理</h1>

        <h2>添加黑名单</h2>
        <form method="post" action="blacklist.php">
            <div class="form-group">
                <label for="contact_type">联系方式类型:</label>
                <select id="contact_type" name="contact_type" required>
                    <option value="email">邮箱</option>
                    <option value="phone">电话</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div class="form-group">
                <label for="contact_info">联系方式:</label>
                <input type="text" id="contact_info" name="contact_info" required>
            </div>
            <div class="form-group">
                <label for="reason">黑名单原因:</label>
                <textarea id="reason" name="reason" rows="3"></textarea>
            </div>
            <button type="submit" name="add_blacklist">添加到黑名单</button>
        </form>

        <h2>黑名单列表</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>联系方式类型</th>
                <th>联系方式</th>
                <th>原因</th>
                <th>添加时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($blacklist_items as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo $item['contact_type']; ?></td>
                <td><?php echo $item['contact_info']; ?></td>
                <td><?php echo $item['reason']; ?></td>
                <td><?php echo $item['created_at']; ?></td>
                <td>
                    <a href="blacklist.php?delete=<?php echo $item['id']; ?>" class="action-btn delete-btn" onclick="return confirm('确定要删除这条黑名单记录吗？');">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>