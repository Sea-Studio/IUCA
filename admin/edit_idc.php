<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 启动会话
session_start();

// 检查是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 检查是否提供了IDC ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// 连接数据库
require_once '../include/db.php';
$db = require '../include/db.php';

// 获取认证类型
$auth_stmt = $db->prepare("SELECT name, code FROM authentication_types");
$auth_stmt->execute();
$auth_types = $auth_stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取IDC信息
$stmt = $db->prepare("SELECT * FROM idc_info WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$idc = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取标识码
$stmt_identifier = $db->prepare("SELECT identifier FROM idcbsm WHERE idc_info_id = :idc_info_id");
$stmt_identifier->bindParam(':idc_info_id', $id, PDO::PARAM_INT);
$stmt_identifier->execute();
$identifier_row = $stmt_identifier->fetch(PDO::FETCH_ASSOC);
$identifier = $identifier_row ? $identifier_row['identifier'] : '';

// 如果IDC不存在，重定向到列表页
if (!$idc) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $idc_name = $_POST['idc_name'] ?? '';
    $website = $_POST['website'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $contact_type = $_POST['contact_type'] ?? '';
    $contact_info = $_POST['contact_info'] ?? '';
    $email = $_POST['email'] ?? '';
    $status = $_POST['status'] ?? 'normal';
    $operator_type_id = $_POST['operator_type_id'] ?? null;
    $tags = $_POST['tags'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $logo_filename = $idc['logo_filename']; // 保留原有LOGO

    // 验证必填字段
    if (empty($idc_name) || empty($website) || empty($contact_type) || empty($contact_info) || empty($email)) {
        $error_message = 'IDC名称、官网、联系方式类型、联系方式和邮箱是必填项';
    } else {
        // 处理Logo上传
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $target_dir = '../images/';
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // 删除旧LOGO
            if (!empty($logo_filename) && file_exists($target_dir . $logo_filename)) {
                unlink($target_dir . $logo_filename);
            }

            // 移动上传文件
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_filename = $new_filename;
            } else {
                $error_message = 'LOGO上传失败';
            }
        }

        // 验证operator_type_id是否有效
        if ($operator_type_id !== null && $operator_type_id !== '') {
            // 检查operator_type_id是否为数字
            if (!is_numeric($operator_type_id) || intval($operator_type_id) <= 0) {
                $error_message = '运营方类型ID必须是大于0的整数';
            } else {
                // 转换为整数
                $operator_type_id = intval($operator_type_id);
                
                // 检查operator_type_id是否存在
                $stmt_check = $db->prepare("SELECT id FROM operator_types WHERE id = :operator_type_id");
                $stmt_check->bindValue(':operator_type_id', $operator_type_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $check_row = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$check_row) {
                    $error_message = '无效的运营方类型ID';
                }
            }
        } else {
            // 如果为空或null，设置为null
            $operator_type_id = null;
        }

        // 如果没有错误，更新数据库
        if (empty($error_message)) {
                // 获取认证等级和标识码
                $authentication = $_POST['authentication'] ?? '';
                $new_identifier = $_POST['identifier'] ?? '';
                
                // 更新IDC信息
                $stmt = $db->prepare("UPDATE idc_info SET idc_name = :idc_name, website = :website, company_name = :company_name, contact_type = :contact_type, contact_info = :contact_info, email = :email, status = :status, authentication = :authentication, operator_type_id = :operator_type_id, tags = :tags, remarks = :remarks, logo_filename = :logo_filename, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
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
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    // 处理标识码
                    if (!empty($new_identifier)) {
                        // 检查是否已存在标识码记录
                        $stmt_check = $db->prepare("SELECT id FROM idcbsm WHERE idc_info_id = :idc_info_id");
                        $stmt_check->bindValue(':idc_info_id', $id, PDO::PARAM_INT);
                        $check_result = $stmt_check->execute();
                        $check_row = $stmt_check->fetch(PDO::FETCH_ASSOC);

                        if ($check_row) {
                            // 更新已存在的记录
                            $stmt_update = $db->prepare("UPDATE idcbsm SET identifier = :identifier WHERE id = :id");
                        $stmt_update->bindParam(':identifier', $new_identifier, PDO::PARAM_STR);
                        $stmt_update->bindParam(':id', $check_row['id'], PDO::PARAM_INT);
                        $stmt_update->execute();
                        } else {
                            // 插入新记录
                            $stmt_insert = $db->prepare("INSERT INTO idcbsm (identifier, idc_info_id, created_at) VALUES (:identifier, :idc_info_id, CURRENT_TIMESTAMP)");
                            $stmt_insert->bindValue(':identifier', $new_identifier, PDO::PARAM_STR);
                            $stmt_insert->bindValue(':idc_info_id', $id, PDO::PARAM_INT);
                            $stmt_insert->execute();
                        }
                    }

                    $success_message = 'IDC信息更新成功';
                    // 重新获取更新后的IDC信息
                    $stmt = $db->prepare("SELECT * FROM idc_info WHERE id = :id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $idc = $stmt->fetch(PDO::FETCH_ASSOC);
                    // 重新获取标识码
                    $stmt_identifier = $db->prepare("SELECT identifier FROM idcbsm WHERE idc_info_id = :idc_info_id");
                    $stmt_identifier->bindValue(':idc_info_id', $id, PDO::PARAM_INT);
                    $stmt_identifier->execute();
                    $identifier_row = $stmt_identifier->fetch(PDO::FETCH_ASSOC);
                    $identifier = $identifier_row ? $identifier_row['identifier'] : '';
                } else {
                    $error_message = '更新失败，请重试';
                }
            }
    }
}

// PDO连接会自动关闭，无需手动关闭
// $db->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑IDC信息 - IUCA</title>
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
        input, select, textarea {
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
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0069d9;
        }
        button[type="reset"] {
            background-color: #6c757d;
        }
        button[type="reset"]:hover {
            background-color: #5a6268;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        .help-text {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .logo-preview {
            margin-top: 10px;
            max-width: 200px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>编辑IDC信息</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="idc_name">IDC名称 <span class="required">*</span></label>
                <input type="text" id="idc_name" name="idc_name" required value="<?php echo htmlspecialchars($idc['idc_name']); ?>">
            </div>

            <div class="form-group">
                <label for="website">官网 <span class="required">*</span></label>
                <input type="url" id="website" name="website" required value="<?php echo htmlspecialchars($idc['website']); ?>">
                <div class="help-text">请输入完整网址，包含http://或https://</div>
            </div>

            <div class="form-group">
                <label for="company_name">公司名称</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($idc['company_name']); ?>">
            </div>

            <div class="form-group">
                <label for="identifier">标识码</label>
                <input type="text" id="identifier" name="identifier" value="<?php echo htmlspecialchars($identifier); ?>">
                <div class="help-text">可输入任意格式的标识符</div>
            </div>

            <div class="form-group">
                <label for="contact_type">联系方式类型 <span class="required">*</span></label>
                <select id="contact_type" name="contact_type" required>
                    <option value="">请选择</option>
                    <option value="phone" <?php echo ($idc['contact_type'] == 'phone') ? 'selected' : ''; ?>>电话</option>
                    <option value="qq" <?php echo ($idc['contact_type'] == 'qq') ? 'selected' : ''; ?>>QQ</option>
                    <option value="wechat" <?php echo ($idc['contact_type'] == 'wechat') ? 'selected' : ''; ?>>微信</option>
                    <option value="other" <?php echo ($idc['contact_type'] == 'other') ? 'selected' : ''; ?>>其他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="contact_info">联系方式 <span class="required">*</span></label>
                <input type="text" id="contact_info" name="contact_info" required value="<?php echo htmlspecialchars($idc['contact_info']); ?>">
            </div>

            <div class="form-group">
                <label for="email">邮箱 <span class="required">*</span></label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($idc['email']); ?>">
            </div>

            <div class="form-group">
                <label for="status">状态</label>
                <select id="status" name="status">
                    <option value="normal" <?php echo ($idc['status'] == 'normal') ? 'selected' : ''; ?>>正常</option>
                    <option value="runaway" <?php echo ($idc['status'] == 'runaway') ? 'selected' : ''; ?>>跑路</option>
                    <option value="unknown" <?php echo ($idc['status'] == 'unknown') ? 'selected' : ''; ?>>未知</option>
                    <option value="closed" <?php echo ($idc['status'] == 'closed') ? 'selected' : ''; ?>>倒闭</option>
                    <option value="abnormal" <?php echo ($idc['status'] == 'abnormal') ? 'selected' : ''; ?>>异常</option>
                </select>
            </div>

            <div class="form-group">
                <label for="authentication">认证等级</label>
                <select id="authentication" name="authentication">
                    <?php foreach ($auth_types as $auth) : ?>
                        <option value="<?php echo $auth['code']; ?>" <?php echo ($idc['authentication'] == $auth['code']) ? 'selected' : ''; ?>>
                            <?php echo $auth['name']; ?>
                        </option>
                    <?php endforeach; ?>
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
                        $selected = ($idc['operator_type_id'] == $type['id']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($type['id'])."' $selected>".htmlspecialchars($type['name'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tags">标签</label>
                <input type="text" id="tags" name="tags" placeholder="多个标签用逗号分隔" value="<?php echo htmlspecialchars($idc['tags']); ?>">
            </div>

            <div class="form-group">
                <label for="remarks">备注</label>
                <textarea id="remarks" name="remarks"><?php echo htmlspecialchars($idc['remarks']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="logo">LOGO</label>
                <input type="file" id="logo" name="logo" accept="image/*">
                <?php if (!empty($idc['logo_filename']) && file_exists('../images/' . $idc['logo_filename'])): ?>
                    <div class="help-text">当前LOGO:</div>
                    <img src="../images/<?php echo htmlspecialchars($idc['logo_filename']); ?>" alt="IDC Logo" class="logo-preview">
                <?php endif; ?>
                <div class="help-text">如需更换LOGO，请选择新图片；如不更换，请保持为空</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存修改</button>
                <button type="reset" class="btn btn-secondary">重置</button>
                <a href="index.php" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>