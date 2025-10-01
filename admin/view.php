<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 查看IDC详情</title>
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
    .result-item {
        margin-top: 20px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 25px;
        background-color: #fff;
    }
    .result-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .result-title {
        margin: 0;
        color: #2c3e50;
        font-size: 24px;
    }
    .result-status {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-normal {
        background-color: #d4edda;
        color: #155724;
    }
    .status-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    .status-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    .status-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    .status-abnormal {
        background-color: #fd7e14;
        color: #fff;
    }
    .logo-container {
        margin: 20px 0;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 4px;
        display: inline-block;
    }
    .logo-preview {
        max-width: 200px;
        max-height: 100px;
    }
    p {
        margin: 12px 0;
        line-height: 1.6;
    }
    strong {
        color: #495057;
    }
    .tags {
        margin: 20px 0;
    }
    .tag {
        display: inline-block;
        background-color: #e9ecef;
        padding: 4px 10px;
        border-radius: 20px;
        margin-right: 8px;
        margin-bottom: 8px;
        font-size: 14px;
        color: #495057;
    }
    .remarks {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #007bff;
    }
    .btn-group {
        margin-top: 30px;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-right: 10px;
        font-size: 16px;
        font-weight: 500;
        transition: background-color 0.3s;
    }
    .btn:hover {
        background-color: #0069d9;
    }
    .btn-secondary {
        background-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>IUCA - 查看IDC详情</h1>
        
        <?php
/**
 * 查看IDC信息页面
 * 用于查看系统中的IDC详细信息
 * 
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
        
        // 检查是否提供了ID参数
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo '<div class="alert alert-error">无效的请求</div>';
            echo '<a href="index.php" class="btn btn-primary">返回列表</a>';
            exit;
        }
        
        $id = $_GET['id'];
        
        // 连接数据库
        require_once '../include/db.php';
        $db = require '../include/db.php';

        // 获取认证类型图标映射
        $auth_stmt = $db->prepare("SELECT code, icon FROM authentication_types");
        $auth_stmt->execute();
        $auth_icons = [];
        while ($auth_row = $auth_stmt->fetch(PDO::FETCH_ASSOC)) {
            $auth_icons[$auth_row['code']] = $auth_row['icon'];
        }

        // 获取运营方类型映射
        $operator_stmt = $db->prepare("SELECT id, name FROM operator_types");
        $operator_stmt->execute();
        $operator_types = [];
        while ($operator_row = $operator_stmt->fetch(PDO::FETCH_ASSOC)) {
            $operator_types[$operator_row['id']] = $operator_row['name'];
        }
        
        // 获取IDC信息
        $stmt = $db->prepare("SELECT * FROM idc_info WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $idc_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$idc_info) {
            echo '<div class="alert alert-error">未找到该IDC信息</div>';
            echo '<a href="index.php" class="btn btn-primary">返回列表</a>';
            exit;
        }
        
        // 解析标签
        $tags = !empty($idc_info['tags']) ? explode(',', $idc_info['tags']) : [];
        
        // 状态样式映射
        $status_class = '';
        $status_text = '';

        // 获取认证图标
        $auth_code = $idc_info['authentication'] ?? 'unverified';
        $auth_icon = $auth_icons[$auth_code] ?? 'gray_v.svg';

        // 检查是否在黑名单中
        if (isset($idc_info['is_blacklisted']) && $idc_info['is_blacklisted'] == 1) {
            $status_class = 'status-danger';
            $status_text = '黑名单';
        } else {
            switch ($idc_info['status']) {
                case 'normal':
                    $status_class = 'status-normal';
                    $status_text = '正常';
                    break;
                case 'closed':
                    $status_class = 'status-danger';
                    $status_text = '倒闭';
                    break;
                case 'runaway':
                    $status_class = 'status-danger';
                    $status_text = '跑路';
                    break;
                case 'unknown':
                    $status_class = 'status-warning';
                    $status_text = '未知';
                    break;
                case 'abnormal':
                    $status_class = 'status-abnormal';
                    $status_text = '异常';
                    break;
                default:
                    $status_class = 'status-info';
                    $status_text = $idc_info['status'];
            }
        }
        
        // PDO连接会在脚本结束时自动关闭，无需手动关闭
        ?>

        <div class="result-item">
            <div class="result-header">
                <h2 class="result-title"><?php echo htmlspecialchars($idc_info['idc_name']); ?></h2>
                <div style="display: flex; align-items: center;">
                    <img src="../images/<?php echo htmlspecialchars($auth_icon); ?>" alt="认证图标" width="24" height="24" style="margin-right: 10px;">
                    <span class="result-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
            </div>

            <!-- 显示Logo -->
            <?php if (!empty($idc_info['logo_filename'])): ?>
            <div class="logo-container">
                <img src="../images/<?php echo htmlspecialchars($idc_info['logo_filename']); ?>" alt="<?php echo htmlspecialchars($idc_info['idc_name']); ?> Logo" class="logo-preview">
            </div>
            <?php endif; ?>

            <p><strong>官网:</strong> <a href="<?php echo htmlspecialchars($idc_info['website']); ?>" target="_blank"><?php echo htmlspecialchars($idc_info['website']); ?></a></p>
            <?php if (!empty($idc_info['company_name'])): ?>
            <p><strong>公司名称:</strong> <?php echo htmlspecialchars($idc_info['company_name']); ?></p>
            <?php endif; ?>
            <p><strong>运营方类型:</strong> <?php echo isset($idc_info['operator_type_id']) && !empty($idc_info['operator_type_id']) ? htmlspecialchars($operator_types[$idc_info['operator_type_id']]) : '未设置'; ?></p>
            <p><strong>联系方式:</strong> <?php echo htmlspecialchars($idc_info['contact_type']); ?> - <?php echo htmlspecialchars($idc_info['contact_info']); ?></p>
            <p><strong>邮箱:</strong> <?php echo htmlspecialchars($idc_info['email']); ?></p>
            <p><strong>登记时间:</strong> <?php echo htmlspecialchars($idc_info['created_at']); ?></p>
            <p><strong>更新时间:</strong> <?php echo htmlspecialchars($idc_info['updated_at']); ?></p>

            <!-- 显示标签 -->
            <?php if (!empty($tags)): ?>
            <div class="tags">
                <strong>标签:</strong> 
                <?php foreach ($tags as $tag): ?>
                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 管理员备注 -->
            <?php if (!empty($idc_info['remarks'])): ?>
            <div class="remarks">
                <strong>管理员备注:</strong>
                <p><?php echo nl2br(htmlspecialchars($idc_info['remarks'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="btn-group">
                <a href="edit_idc.php?id=<?php echo $id; ?>" class="btn btn-primary">修改信息</a>
                <a href="index.php" class="btn btn-secondary">返回列表</a>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>