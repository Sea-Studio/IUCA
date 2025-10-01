<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IUCA</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>IUCA</h1>
        
        <!-- 查询表单 -->
        <div class="search-form">
            <form method="get" action="index.php">
                <div class="form-group">
                    <label for="search_type">查询类型</label>
                    <select id="search_type" name="search_type">
                        <option value="idc_name" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'idc_name') ? 'selected' : ''; ?>>IDC名称</option>
                        <option value="domain" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'domain') ? 'selected' : ''; ?>>域名</option>
                        <option value="identifier" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'identifier') ? 'selected' : ''; ?>>标识码</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="search_keyword">查询关键词</label>
                    <input type="text" id="search_keyword" name="search_keyword" placeholder="请输入查询关键词" value="<?php echo isset($_GET['search_keyword']) ? htmlspecialchars($_GET['search_keyword']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary">查询</button>
            </form>
        </div>

        <!-- 查询结果 -->
        <div class="search-results">
            <?php
            /**
             * IUCA系统首页
             * 系统的主入口页面
             * 
             * @copyright © IUCA 及 朝阳热心市民
             * @license MPL
             */
            
            // 处理查询
            if (isset($_GET['search_keyword']) && !empty($_GET['search_keyword'])) {
                require_once 'include/db.php';
                $db = require 'include/db.php';
                
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
                
                $search_type = $_GET['search_type'];
                $search_keyword = $_GET['search_keyword'];
                
                // 根据查询类型构建SQL
                if ($search_type == 'idc_name') {
                    $stmt = $db->prepare("SELECT * FROM idc_info WHERE idc_name LIKE :keyword");
                } else if ($search_type == 'domain') {
                    // 对于域名查询，我们需要从website字段中提取域名
                    $stmt = $db->prepare("SELECT * FROM idc_info WHERE website LIKE :keyword");
                } else if ($search_type == 'identifier') {
                    // 对于标识码查询，我们需要先从idcbsm表中查询对应的IDC信息ID
                    $stmt = $db->prepare("SELECT idc_info.* FROM idc_info
                                           INNER JOIN idcbsm ON idc_info.id = idcbsm.idc_info_id
                                           WHERE idcbsm.identifier = :keyword");
                }
                
                // 对于标识码查询，我们需要精确匹配
                if ($search_type == 'identifier') {
                    $stmt->bindParam(':keyword', $search_keyword, PDO::PARAM_STR);
                } else {
                    $keyword = '%' . $search_keyword . '%';
                    $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
                }
                $stmt->execute();

                $found = false;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $found = true;
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
                    
                    // 解析标签
                    $tags = !empty($row['tags']) ? explode(',', $row['tags']) : [];
                    
                    echo '<div class="result-item">';
                    // 获取认证图标
                    $auth_code = $row['authentication'] ?? 'unverified';
                    $auth_icon = $auth_icons[$auth_code] ?? 'gray_v.svg';
                    
                    echo '<div class="result-header">';
                      echo '<h3 class="result-title">' . htmlspecialchars($row['idc_name']) . '</h3>';
                      echo '<span class="result-status ' . $status_class . '">' . $status_text . '</span>';
                      echo '</div>';
                    
                    // 显示Logo
                    if (!empty($row['logo_filename'])) {
                        echo '<div class="logo-container">';
                        echo '<img src="images/' . htmlspecialchars($row['logo_filename']) . '" alt="' . htmlspecialchars($row['idc_name']) . ' Logo" class="logo-preview">';
                        echo '</div>';
                    }
                    
                    echo '<p><strong>官网:</strong> <a href="' . htmlspecialchars($row['website']) . '" target="_blank">' . htmlspecialchars($row['website']) . '</a></p>';
                    if (!empty($row['company_name'])) {
                        echo '<p><strong>公司名称:</strong> ' . htmlspecialchars($row['company_name']) . '</p>';
                    }
                    echo '<p><strong>联系方式:</strong> ' . htmlspecialchars($row['contact_type']) . ' - ' . htmlspecialchars($row['contact_info']) . '</p>';
                    echo '<p><strong>邮箱:</strong> ' . htmlspecialchars($row['email']) . '</p>';
                    echo '<p><strong>认证状态:</strong> ';
                    echo '<img src="images/' . htmlspecialchars($auth_icon) . '" alt="认证图标" width="20" height="20" style="vertical-align: middle; margin-right: 5px;">';
                    // 根据认证代码显示对应的认证名称
                    $auth_name = '';
                    switch($auth_code) {
                        case 'unverified':
                            $auth_name = '未认证';
                            break;
                        case 'normal':
                            $auth_name = '普通认证';
                            break;
                        case 'premium':
                            $auth_name = '高级认证';
                            break;
                        case 'enterprise':
                            $auth_name = '企业认证';
                            break;
                        default:
                            $auth_name = '未知认证';
                    }
                    echo htmlspecialchars($auth_name);
                    echo '</p>';
                    
                    // 显示运营方类型
                    echo '<p><strong>运营方类型:</strong> ';
                    $operator_type_id = $row['operator_type_id'] ?? null;
                    if ($operator_type_id && isset($operator_types[$operator_type_id])) {
                        echo htmlspecialchars($operator_types[$operator_type_id]);
                    } else {
                        echo '未设置';
                    }
                    echo '</p>';
                    
                    // 查询并显示标识码
                    $stmt_identifier = $db->prepare("SELECT identifier FROM idcbsm WHERE idc_info_id = :idc_info_id");
                    $stmt_identifier->bindParam(':idc_info_id', $row['id'], PDO::PARAM_INT);
                    $stmt_identifier->execute();
                    $identifier_row = $stmt_identifier->fetch(PDO::FETCH_ASSOC);
                    if ($identifier_row) {
                        echo '<p><strong>标识码:</strong> ' . htmlspecialchars($identifier_row['identifier']) . '</p>';
                    } else {
                        echo '<p><strong>标识码:</strong> 未设置</p>';
                    }
                    echo '<p><strong>登记时间:</strong> ' . htmlspecialchars($row['created_at']) . '</p>';
                    
                    // 检查相同联系方式或邮箱的其他IDC
                    require_once 'include/functions.php';
                    $related_idcs = check_related_idcs($row['contact_info'], $row['email'], $row['id']);
                    
                    if (!empty($related_idcs)) {
                        $all_normal = true;
                        foreach ($related_idcs as $related_idc) {
                            if ($related_idc['status'] !== 'normal') {
                                $all_normal = false;
                                break;
                            }
                        }
                        
                        echo '<div class="related-idc-status">';
                        if ($all_normal) {
                            echo '<span class="status-normal">该IDC联系方式/邮箱其余IDC正常</span>';
                        } else {
                            echo '<span class="status-warning">该IDC联系方式/邮箱其余IDC存在异常</span>';
                        }
                        echo '</div>';
                    }
                    
                    // 显示标签
                    if (!empty($tags)) {
                        echo '<div class="tags">';
                        echo '<strong>标签:</strong> ';
                        foreach ($tags as $tag) {
                            echo '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                        }
                        echo '</div>';
                    }
                    
                    // 操作按钮
                    echo '<div class="btn-group">';
                    echo '<a href="index.php" class="btn btn-secondary">返回首页</a>';
                    echo '<a href="edit.php?id=' . $row['id'] . '" class="btn btn-primary">修改信息</a>';
                    echo '</div>';
                    echo '</div>';
                }
                
                if (!$found) {
                    echo '<div class="alert alert-warning">未找到相关IDC信息</div>';
                    echo '<div class="no-result-actions">';
                    echo '<p>快来加入吧！</p>';
                    echo '<a href="register.php" class="btn btn-primary">这就来</a>';
                    echo '</div>';
                }
                
            }
            ?>
        </div>

        <!-- 底部注册按钮 -->
        <div class="footer-actions">
            <a href="register.php" class="btn btn-primary">注册新IDC</a>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>