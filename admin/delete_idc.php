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
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

require_once '../include/db.php';
$db = require '../include/db.php';

$stmt = $db->prepare("SELECT logo_filename FROM idc_info WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$idc_info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($idc_info) {
    if (!empty($idc_info['logo_filename']) && file_exists('../images/' . $idc_info['logo_filename'])) {
        unlink('../images/' . $idc_info['logo_filename']);
    }

    $stmt_b = $db->prepare("DELETE FROM idcbsm WHERE idc_info_id = :id");
    $stmt_b->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_b->execute();

    $stmt = $db->prepare("DELETE FROM idc_info WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

// PDO连接会在脚本结束时自动关闭，无需手动关闭

header('Location: index.php');
exit;
?>