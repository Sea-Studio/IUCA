<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 管理员修改IDC信息</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>IUCA - 管理员修改IDC信息</h1>
        
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
        
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo '<div class="alert alert-error">无效的请求</div>';
            echo '<a href="index.php" class="btn btn-primary">返回列表</a>';
            exit;
        }
        
        $id = $_GET['id'];
        
        require_once '../include/db.php';
        $db = require '../include/db.php';
        
        $stmt = $db->prepare("SELECT * FROM idc_info WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $idc_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$idc_info) {
            echo '<div class="alert alert-error">未找到该IDC信息</div>';
            echo '<a href="index.php" class="btn btn-primary">返回列表</a>';
            exit;
        }
        
        $stmt = $db->prepare("SELECT name FROM status_types");
        $stmt->execute();
        $statuses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = $row['name'];
        }
        
        $tags = !empty($idc_info['tags']) ? explode(',', $idc_info['tags']) : [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $logo_filename = $idc_info['logo_filename'];
            if (!empty($_FILES['logo']['name'])) {
                $target_dir = '../images/';
                $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $new_logo_filename = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_logo_filename;
                
                if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                    echo '<div class="alert alert-error">文件大小超过5MB限制</div>';
                    $upload_ok = false;
                } else {

                    $allowed_formats = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                    if (!in_array($file_extension, $allowed_formats)) {
                        echo '<div class="alert alert-error">只允许JPG, JPEG, PNG, GIF和SVG格式的文件</div>';
                        $upload_ok = false;
                    } else {

                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {

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

                $tags_str = !empty($_POST['tags']) ? implode(',', $_POST['tags']) : '';
                

                $stmt = $db->prepare("UPDATE idc_info SET
                                     idc_name = :idc_name,
                                     website = :website,
                                     company_name = :company_name,
                                     contact_type = :contact_type,
                                     contact_info = :contact_info,
                                     email = :email,
                                     logo_filename = :logo_filename,
                                     status = :status,
                                     tags = :tags,
                                     remarks = :remarks,
                                     updated_at = CURRENT_TIMESTAMP
                                     WHERE id = :id");
                $stmt->bindParam(':idc_name', $_POST['idc_name'], PDO::PARAM_STR);
                $stmt->bindParam(':website', $_POST['website'], PDO::PARAM_STR);
                $stmt->bindParam(':company_name', $_POST['company_name'], PDO::PARAM_STR);
                $stmt->bindParam(':contact_type', $_POST['contact_type'], PDO::PARAM_STR);
                $stmt->bindParam(':contact_info', $_POST['contact_info'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
                $stmt->bindParam(':logo_filename', $logo_filename, PDO::PARAM_STR);
                $stmt->bindParam(':status', $_POST['status'], PDO::PARAM_STR);
                $stmt->bindParam(':tags', $tags_str, PDO::PARAM_STR);
                $stmt->bindParam(':remarks', $_POST['remarks'], PDO::PARAM_STR);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success">修改成功！</div>';
                    $stmt = $db->prepare("SELECT * FROM idc_info WHERE id = :id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $idc_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $tags = !empty($idc_info['tags']) ? explode(',', $idc_info['tags']) : [];
                } else {
                    echo '<div class="alert alert-error">修改失败: ' . $db->errorInfo()[2] . '</div>';
                }
            }
        }
        
        ?>


        <form method="post" action="edit_admin.php?id=<?php echo $id; ?>
" enctype="multipart/form-data">
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
            <div class="form-group">
                <label for="status">状态 <span style="color: red;">*</span></label>
                <select id="status" name="status" required>
                    <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo ($idc_info['status'] == $status) ? 'selected' : ''; ?>><?php echo $status; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tags">标签（多个标签用逗号分隔）</label>
                <input type="text" id="tags" name="tags" placeholder="例如: 国内,高防" value="<?php echo !empty($tags) ? implode(',', array_map('htmlspecialchars', $tags)) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="logo">上传IDC LOGO（最大5MB）</label>
                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.gif,.svg">
                <?php if (!empty($idc_info['logo_filename'])): ?>
                <div class="logo-preview-container">
                    <p>当前LOGO:</p>
                    <img src="../images/<?php echo htmlspecialchars($idc_info['logo_filename']); ?>" alt="<?php echo htmlspecialchars($idc_info['idc_name']); ?> Logo" class="logo-preview">
                </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="remarks">管理员备注（仅管理员可见）</label>
                <textarea id="remarks" name="remarks" rows="5" placeholder="请输入备注信息"><?php echo htmlspecialchars($idc_info['remarks']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">保存修改</button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">查看详情</a>
            <a href="index.php" class="btn btn-secondary">返回列表</a>
        </form>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>