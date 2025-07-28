<?php

session_start();
require_once 'backend.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Edit Photo';
$page_name = 'Edit Photo';

$message = '';
$messageType = '';
$user_id = $_SESSION['user_id'];

include 'includes/header.php';
?>

<?php include 'includes/footer.php'; ?>