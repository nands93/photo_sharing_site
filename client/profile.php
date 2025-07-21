<?php
session_start();
require_once 'backend.php';

$message = $message ?? '';
$messageType = $messageType ?? '';

include 'includes/header.php';
?>
<div class="form-container">
    <h2>Profile</h2>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="profile.php" method="post">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
</div>
<?php include 'includes/footer.php'; ?>