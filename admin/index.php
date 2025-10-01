<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA - 后台管理</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>IUCA - 后台管理</h1>
        
        <?php
/**
 * 后台管理系统首页
 * 管理员登录后的主界面
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
        
        // 引入演示模式数据库恢复检查
        require_once '../include/demo_db_reset.php';
        check_and_reset_database();
        
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
        
        // 处理搜索
        $search_keyword = '';
        $where_clause = '';
        $params = [];
        if (isset($_GET['search_keyword']) && !empty($_GET['search_keyword'])) {
            $search_keyword = $_GET['search_keyword'];
            $where_clause = "WHERE idc_name LIKE :search OR website LIKE :search";
            $params[':search'] = '%' . $search_keyword . '%';
        }
        
        // 处理按时间查询
        if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];
            
            if (!empty($where_clause)) {
                $where_clause .= " AND ";
            } else {
                $where_clause .= "WHERE ";
            }
            
            $where_clause .= "created_at BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date . ' 23:59:59';
        }
        
        // 查询IDC信息
        $query = "SELECT * FROM idc_info " . $where_clause . " ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取当前管理员信息
        $admin_id = $_SESSION['admin_id'];
        $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = :id");
        $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $is_super_admin = ($admin['is_admin'] == 1);
        
        // 处理批量操作
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'download_logos') {
                // 打包下载所有LOGO
                $zip = new ZipArchive();
                $zip_filename = 'idc_logos_' . date('YmdHis') . '.zip';
                
                if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    // 添加所有LOGO文件
                    foreach ($result as $row) {
                        if (!empty($row['logo_filename']) && file_exists('../images/' . $row['logo_filename'])) {
                            $zip->addFile('../images/' . $row['logo_filename'], $row['idc_name'] . '_' . $row['logo_filename']);
                        }
                    }
                    
                    $zip->close();
                    
                    // 下载文件
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
                    header('Content-Length: ' . filesize($zip_filename));
                    readfile($zip_filename);
                    unlink($zip_filename);
                    exit;
                } else {
                    echo '<div class="alert alert-error">创建ZIP文件失败</div>';
                }
            }
        }
        
        // PDO连接会在脚本结束时自动关闭，无需手动关闭
        ?>

        <!-- 管理员信息和操作 -->
        <div class="admin-info">
            <p>欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?>！</p>
            <div class="btn-group">
                <a href="add_idc.php" class="btn btn-primary">添加IDC信息</a>
                <a href="admin_list.php" class="btn btn-primary">管理员列表</a>
                <a href="email_settings.php" class="btn btn-primary" <?php echo !$is_super_admin ? 'disabled' : ''; ?>>邮箱配置</a>
                <a href="blacklist.php" class="btn btn-primary">黑名单管理</a>
                <a href="add_admin.php" class="btn btn-primary" <?php echo !$is_super_admin ? 'disabled' : ''; ?>>添加管理员</a>
                <a href="logout.php" class="btn btn-secondary">退出登录</a>
            </div>
        </div>

        <!-- 搜索和过滤 -->
        <div class="search-filter">
            <h2>搜索和过滤</h2>
            <form method="get" action="index.php">
                <div class="form-group">
                    <label for="search_keyword">关键词搜索</label>
                    <input type="text" id="search_keyword" name="search_keyword" placeholder="IDC名称或域名" value="<?php echo htmlspecialchars($search_keyword); ?>">
                </div>
                <div class="form-group">
                    <label for="start_date">开始日期</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">结束日期</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary">搜索</button>
                <a href="index.php" class="btn btn-secondary">重置</a>
            </form>
        </div>

        <!-- 批量操作 -->
        <div class="batch-actions">
            <h2>批量操作</h2>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="download_logos">
                <button type="submit" class="btn btn-primary">下载所有LOGO</button>
            </form>
        </div>

        <!-- IDC列表 -->
        <div class="idc-list">
            <h2>IDC信息列表</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>IDC名称</th>
                        <th>官网</th>
                        <th>公司名称</th>
                        <th>联系方式</th>
                        <th>邮箱</th>
                        <th>状态</th>
                        <th>认证</th>
                        <th>运营方类型</th>
                        <th>标识码</th>
                        <th>登记时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 重新查询数据
                    $db = require '../include/db.php';
                    $stmt = $db->prepare($query);
                    foreach ($params as $key => &$value) {
                        $stmt->bindParam($key, $value);
                    }
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($result as $row) {
                        // 状态样式映射
                        $status_class = '';
                        switch ($row['status']) {
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
                                $status_class = 'status-warning';
                                $status_text = '异常';
                                break;
                            default:
                                $status_class = 'status-info';
                                $status_text = $row['status'];
                        }
                        
                        echo '<tr>';
                        echo '<td>' . $row['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($row['idc_name']) . '</td>';
                        echo '<td><a href="' . htmlspecialchars($row['website']) . '" target="_blank">' . htmlspecialchars($row['website']) . '</a></td>';
                        echo '<td>' . htmlspecialchars($row['company_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['contact_type']) . ': ' . htmlspecialchars($row['contact_info']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        // 获取认证图标
                        $auth_code = $row['authentication'] ?? 'unverified';
                        $auth_icon = $auth_icons[$auth_code] ?? 'gray_v.svg';
                        
                        echo '<td><span class="result-status ' . $status_class . '">' . $status_text . '</span></td>';
                        echo '<td><img src="../images/' . htmlspecialchars($auth_icon) . '" alt="认证图标" width="24" height="24"></td>';
                        // 查询并显示运营方类型
                        $stmt_operator = $db->prepare("SELECT name FROM operator_types WHERE id = :id");
                        $stmt_operator->bindValue(':id', $row['operator_type_id'], PDO::PARAM_INT);
                        $operator_result = $stmt_operator->execute();
                        $operator_row = $operator_result ? $stmt_operator->fetch(PDO::FETCH_ASSOC) : false;
                        echo '<td>' . ($operator_row ? htmlspecialchars($operator_row['name']) : '未设置') . '</td>';
                        // 查询并显示标识码
                        $stmt_identifier = $db->prepare("SELECT identifier FROM idcbsm WHERE idc_info_id = :idc_info_id");
                        $stmt_identifier->bindValue(':idc_info_id', $row['id'], PDO::PARAM_INT);
                        $identifier_result = $stmt_identifier->execute();
                        $identifier_row = $identifier_result ? $stmt_identifier->fetch(PDO::FETCH_ASSOC) : false;
                        echo '<td>' . ($identifier_row ? htmlspecialchars($identifier_row['identifier']) : '未设置') . '</td>';
                        echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                        echo '<td>';
                        echo '<a href="view.php?id=' . $row['id'] . '" class="btn btn-primary">查看</a>';
                        echo '<a href="edit_idc.php?id=' . $row['id'] . '" class="btn btn-secondary">修改</a>';
                        echo '<a href="delete_idc.php?id=' . $row['id'] . '" class="btn btn-danger" onclick="return confirm(\'确定要删除这条IDC信息吗？\')">删除</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    // PDO连接会自动关闭，无需手动关闭
                    // $db->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>