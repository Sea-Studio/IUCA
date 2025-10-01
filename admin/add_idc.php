<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */

session_start();

function generateIdentifier() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $identifier = '';
    for ($i = 0; $i < 10; $i++) {
        $identifier .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $identifier;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../include/db.php';
$db = require '../include/db.php';

require_once '../include/blacklist_functions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idc_name = $_POST['idc_name'] ?? '';
            $website = $_POST['website'] ?? '';
            $company_name = $_POST['company_name'] ?? '';
            $contact_type = $_POST['contact_type'] ?? '';
            $contact_info = $_POST['contact_info'] ?? '';
            $email = $_POST['email'] ?? '';
            $status = $_POST['status'] ?? 'normal';
            $authentication = $_POST['authentication'] ?? 'unverified';
            $operator_type_id = null;
            if (isset($_POST['operator_type_id']) && !empty($_POST['operator_type_id']) && is_numeric($_POST['operator_type_id'])) {
                $temp_id = (int)$_POST['operator_type_id'];
                if ($temp_id > 0) {
                    $operator_type_id = $temp_id;
                }
            }
            $tags = $_POST['tags'] ?? '';
            $remarks = $_POST['remarks'] ?? '';
    $logo_filename = '';

        if (empty($idc_name) || empty($website) || empty($contact_type) || empty($contact_info) || empty($email)) {
            $error_message = 'IDC名称、官网、联系方式类型、联系方式和邮箱是必填项';
        } else {
        // 处理Logo上传
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../images/';
            $file_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo_filename = 'logo_' . time() . '.' . $file_ext;
            $upload_file = $upload_dir . $logo_filename;

            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_file)) {
                $error_message = 'Logo上传失败';
            }
        }

        if (empty($error_message)) {
            $warning_message = '';
            if (is_in_blacklist($db, $contact_info) || is_in_blacklist($db, $email)) {
                $status = 'abnormal';
                $warning_message = '警告：该联系方式或邮箱在黑名单中，已自动设置为异常状态';
            }
            
            if ($operator_type_id !== null) {
                $stmt_check = $db->prepare("SELECT COUNT(*) as count FROM operator_types WHERE id = :id");
                $stmt_check->bindParam(':id', $operator_type_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $row = $stmt_check->fetch();
                if ($row['count'] === 0) {
                    $error_message = '无效的运营方类型ID';
                }
            }
            
            if (empty($error_message)) {
                $stmt = $db->prepare("INSERT INTO idc_info (idc_name, website, company_name, contact_type, contact_info, email, status, authentication, operator_type_id, tags, remarks, logo_filename, created_at, updated_at) VALUES (:idc_name, :website, :company_name, :contact_type, :contact_info, :email, :status, :authentication, :operator_type_id, :tags, :remarks, :logo_filename, NOW(), NOW())");

                $stmt->bindParam(':idc_name', $idc_name, PDO::PARAM_STR);
                $stmt->bindParam(':website', $website, PDO::PARAM_STR);
                $stmt->bindParam(':company_name', $company_name, PDO::PARAM_STR);
                $stmt->bindParam(':contact_type', $contact_type, PDO::PARAM_STR);
                $stmt->bindParam(':contact_info', $contact_info, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':authentication', $authentication, PDO::PARAM_STR);
                if ($operator_type_id !== null) {
                    $stmt->bindParam(':operator_type_id', $operator_type_id, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':operator_type_id', null, PDO::PARAM_NULL);
                }
                $stmt->bindParam(':tags', $tags, PDO::PARAM_STR);
                $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
                $stmt->bindParam(':logo_filename', $logo_filename, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $idc_info_id = $db->lastInsertId();
                    
                    $identifier = generateIdentifier();
                    
                    $stmt_identifier = $db->prepare("INSERT INTO idcbsm (identifier, idc_info_id) VALUES (:identifier, :idc_info_id)");
                    $stmt_identifier->bindParam(':identifier', $identifier, PDO::PARAM_STR);
                    $stmt_identifier->bindParam(':idc_info_id', $idc_info_id, PDO::PARAM_INT);
                    $stmt_identifier->execute();
                    
                    $success_message = 'IDC信息添加成功，标识码: ' . $identifier;
                    $idc_name = $website = $company_name = $contact_type = $contact_info = $email = $tags = $remarks = '';
                    $status = 'normal';
                    $authentication = 'unverified';
                    $logo_filename = '';
                } else {
                    $error_message = '添加IDC信息失败: ' . $db->lastErrorMsg();
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 添加IDC信息</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #343a40;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .required {
            color: #dc3545;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="file"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0069d9;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .help-text {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>IUCA - 添加IDC信息</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($warning_message)): ?>
            <div class="alert alert-warning" style="color: #856404; background-color: #fff3cd; border-color: #ffeeba;"><?php echo $warning_message; ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="add_idc.php">
            <div class="form-group">
                <label for="idc_name">IDC名称 <span class="required">*</span></label>
                <input type="text" id="idc_name" name="idc_name" required value="<?php echo isset($idc_name) ? htmlspecialchars($idc_name) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="website">官网 <span class="required">*</span></label>
                <input type="text" id="website" name="website" required placeholder="http://或https://开头" value="<?php echo isset($website) ? htmlspecialchars($website) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="company_name">公司名称</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo isset($company_name) ? htmlspecialchars($company_name) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="contact_type">联系方式类型 <span class="required">*</span></label>
                <select id="contact_type" name="contact_type" required>
                    <option value="">请选择</option>
                    <option value="phone" <?php echo (isset($contact_type) && $contact_type == 'phone') ? 'selected' : ''; ?>>电话</option>
                    <option value="qq" <?php echo (isset($contact_type) && $contact_type == 'qq') ? 'selected' : ''; ?>>QQ</option>
                    <option value="wechat" <?php echo (isset($contact_type) && $contact_type == 'wechat') ? 'selected' : ''; ?>>微信</option>
                    <option value="other" <?php echo (isset($contact_type) && $contact_type == 'other') ? 'selected' : ''; ?>>其他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="contact_info">联系方式 <span class="required">*</span></label>
                <input type="text" id="contact_info" name="contact_info" required value="<?php echo isset($contact_info) ? htmlspecialchars($contact_info) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">邮箱 <span class="required">*</span></label>
                <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="status">状态</label>
                <select id="status" name="status">
                    <option value="normal" <?php echo (isset($status) && $status == 'normal') ? 'selected' : ''; ?>>正常</option>
                    <option value="closed" <?php echo (isset($status) && $status == 'closed') ? 'selected' : ''; ?>>倒闭</option>
                    <option value="runaway" <?php echo (isset($status) && $status == 'runaway') ? 'selected' : ''; ?>>跑路</option>
                    <option value="unknown" <?php echo (isset($status) && $status == 'unknown') ? 'selected' : ''; ?>>未知</option>
                </select>
            </div>

            <div class="form-group">
                <label for="authentication">认证类型</label>
                <select id="authentication" name="authentication">
                    <option value="unverified" <?php echo (isset($authentication) && $authentication == 'unverified') ? 'selected' : ''; ?>>未认证</option>
                    <option value="standard" <?php echo (isset($authentication) && $authentication == 'standard') ? 'selected' : ''; ?>>普通认证</option>
                    <option value="premium" <?php echo (isset($authentication) && $authentication == 'premium') ? 'selected' : ''; ?>>高级认证</option>
                    <option value="enterprise" <?php echo (isset($authentication) && $authentication == 'enterprise') ? 'selected' : ''; ?>>企业认证</option>
                </select>
            </div>

            <div class="form-group">
                <label for="operator_type_id">运营方类型</label>
                <select id="operator_type_id" name="operator_type_id">
                    <option value="">请选择</option>
                    <?php
                    // 获取运营方类型列表
                    $stmt = $db->query("SELECT id, name FROM operator_types");
                    $operator_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($operator_types as $type) {
                        $selected = (isset($operator_type_id) && $operator_type_id == $type['id']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($type['id'])."' ".htmlspecialchars($selected).">".htmlspecialchars($type['name'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tags">标签</label>
                <input type="text" id="tags" name="tags" placeholder="多个标签用逗号分隔" value="<?php echo isset($tags) ? htmlspecialchars($tags) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="remarks">备注</label>
                <textarea id="remarks" name="remarks"><?php echo isset($remarks) ? htmlspecialchars($remarks) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="logo">上传Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*">
                <p class="help-text">支持JPG、PNG等图片格式，文件大小建议不超过2MB</p>
            </div>

            <button type="submit" class="btn btn-primary">保存</button>
            <a href="index.php" class="btn btn-secondary">返回</a>
        </form>
    </div>
</body>
</html>