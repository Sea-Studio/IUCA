<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */

// 定义上次恢复时间的文件路径
$last_reset_file = __DIR__ . '/demo_last_reset.txt';

/**
 * 检查是否需要恢复数据库
 * @param int $interval 恢复间隔(分钟)
 * @return bool 是否需要恢复
 */
function should_reset_database($interval = 5) {
    global $last_reset_file;
    
    // 如果上次恢复时间文件不存在，需要恢复
    if (!file_exists($last_reset_file)) {
        return true;
    }
    
    // 获取上次恢复时间
    $last_reset_time = file_get_contents($last_reset_file);
    
    // 计算距离上次恢复的时间(分钟)
    $time_diff = (time() - $last_reset_time) / 60;
    
    // 如果距离上次恢复的时间超过了指定的间隔，需要恢复
    return $time_diff >= $interval;
}

/**
 * 记录数据库恢复时间
 */
function record_reset_time() {
    global $last_reset_file;
    file_put_contents($last_reset_file, time());
}

/**
 * 恢复数据库
 * @param string $sql_file_path SQL文件路径
 * @param string &$error_message 用于存储错误信息的引用变量
 * @return bool 恢复是否成功
 */
function reset_database($sql_file_path, &$error_message = null) {
    // 检查SQL文件是否存在
    if (!file_exists($sql_file_path)) {
        $error_message = "SQL文件不存在: $sql_file_path";
        return false;
    }
    
    try {
        // 获取数据库配置
        $config = require __DIR__ . '/mysql_config.php';
        
        // 创建数据库连接(包含数据库名)
        $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';port=' . $config['port'] . ';charset=' . $config['charset'];
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 读取SQL文件内容
        $sql = file_get_contents($sql_file_path);
        
        // 禁用外键检查
        $pdo->exec('SET foreign_key_checks = 0');
        
        // 分割SQL语句并执行
        // 使用更健壮的方法分割SQL语句，确保能正确处理MySQL特有的语法和分号位置
        $statements = [];
        $statement = '';
        $in_comment = false;
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next_char = ($i + 1 < $length) ? $sql[$i + 1] : '';
            
            // 处理MySQL的特殊注释 /*! ... */
            if ($char === '/' && $next_char === '*' && ($i + 2 < $length) && $sql[$i + 2] === '!') {
                $in_comment = true;
                $statement .= $char;
            } elseif ($char === '*' && $next_char === '/' && $in_comment) {
                $in_comment = false;
                $statement .= $char . $next_char;
                $i++; // 跳过下一个字符
            } elseif ($char === ';' && !$in_comment) {
                // 遇到分号，结束当前语句
                $statement .= $char;
                $statements[] = $statement;
                $statement = '';
            } else {
                $statement .= $char;
            }
        }
        
        // 添加最后一个语句（如果有的话）
        if (!empty(trim($statement))) {
            $statements[] = $statement;
        }
        
        // 执行所有分割后的SQL语句
        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed === '') {
                continue;
            }
            $pdo->exec($statement);
        }
        
        // 启用外键检查
        $pdo->exec('SET foreign_key_checks = 1');
        
        return true;
    } catch (PDOException $e) {
        $error_message = "数据库连接或执行错误: " . $e->getMessage();
        error_log('数据库恢复失败: ' . $e->getMessage());
        return false;
    } catch (Exception $e) {
        $error_message = "数据库恢复失败: " . $e->getMessage();
        error_log('数据库恢复失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 检查并执行数据库恢复(如果需要)
 * @param int $interval 恢复间隔(分钟)
 */
function check_and_reset_database($interval = 5) {
    // 只有在演示模式开启时才执行
    $demo_mode = require __DIR__ . '/demo_mode.php';
    if (!$demo_mode) {
        return;
    }
    
    // 检查是否需要恢复数据库
    if (should_reset_database($interval)) {
        // 执行数据库恢复
        $sql_file = __DIR__ . '/dome.sql';
        $error_message = '';
        $success = reset_database($sql_file, $error_message);
        
        // 如果恢复成功，记录恢复时间
        if ($success) {
            record_reset_time();
        } else {
            // 记录错误日志
            error_log('自动数据库恢复失败: ' . $error_message);
        }
    }
}

// 如果直接运行此脚本，则执行检查和恢复
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    check_and_reset_database();
}

?>