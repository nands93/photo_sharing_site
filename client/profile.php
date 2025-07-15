<?php
session_start();
require_once 'backend.php';

$message = $message ?? '';
$messageType = $messageType ?? '';

include 'includes/header.php';
?>
<div class="form-container">
</div>
<?php include 'includes/footer.php'; ?>