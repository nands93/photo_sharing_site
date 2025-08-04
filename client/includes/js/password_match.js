document.getElementById('new_password').addEventListener('input', function() {
    const currentPasswordField = document.getElementById('current_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (this.value) {
        currentPasswordField.required = true;
        confirmPasswordField.required = true;
    } else {
        currentPasswordField.required = false;
        confirmPasswordField.required = false;
        confirmPasswordField.value = '';
    }
});

document.getElementById('email').addEventListener('focus', function() {
    if (this.value === '<?php echo htmlspecialchars($masked_email); ?>') {
        this.value = '';
    }
});

document.getElementById('email').addEventListener('blur', function() {
    if (this.value === '') {
        this.value = '<?php echo htmlspecialchars($masked_email); ?>';
    }
});

document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});