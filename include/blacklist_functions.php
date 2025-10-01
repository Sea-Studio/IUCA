<?php
/**
 * @copyright © IUCA 及 朝阳热心市民
 * @license MPL
 */
// 黑名单功能相关函数

/**
 * 检查联系方式是否在黑名单中
 * @param PDO $db 数据库连接对象
 * @param string $contact_info 联系方式
 * @return bool 是否在黑名单中
 */
function is_in_blacklist($db, $contact_info) {
    $stmt = $db->prepare("SELECT id FROM blacklist WHERE contact_info = :contact_info");
    $stmt->bindParam(':contact_info', $contact_info);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row !== false;
}

/**
 * 检查IDC信息中的联系方式是否在黑名单中
 * @param PDO $db 数据库连接对象
 * @param array $idc_info IDC信息数组
 * @return bool 是否在黑名单中
 */
function check_idc_blacklist($db, $idc_info) {
    // 检查联系信息
    if (is_in_blacklist($db, $idc_info['contact_info'])) {
        return true;
    }

    // 检查邮箱
    if (is_in_blacklist($db, $idc_info['email'])) {
        return true;
    }

    return false;
}

/**
 * 更新IDC信息的黑名单状态
 * @param PDO $db 数据库连接对象
 * @param int $idc_id IDC信息ID
 * @param bool $is_blacklisted 是否在黑名单中
 * @return bool 是否更新成功
 */
function update_idc_blacklist_status($db, $idc_id, $is_blacklisted) {
    $stmt = $db->prepare("UPDATE idc_info SET is_blacklisted = :is_blacklisted WHERE id = :id");
    $stmt->bindParam(':is_blacklisted', $is_blacklisted, PDO::PARAM_INT);
    $stmt->bindParam(':id', $idc_id, PDO::PARAM_INT);
    return $stmt->execute() !== false;
}
?>