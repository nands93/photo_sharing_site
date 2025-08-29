document.addEventListener('DOMContentLoaded', function() {
    const newPasswordField = document.getElementById('new_password');
    const currentPasswordField = document.getElementById('current_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
            if (this.value) {
                if (currentPasswordField) currentPasswordField.required = true;
                if (confirmPasswordField) confirmPasswordField.required = true;
            } else {
                if (currentPasswordField) {
                    currentPasswordField.required = false;
                    currentPasswordField.value = '';
                }
                if (confirmPasswordField) {
                    confirmPasswordField.required = false;
                    confirmPasswordField.value = '';
                }
            }
        });
    }
    
    const emailField = document.getElementById('email');
    const originalMaskedEmail = maskedEmail;

    emailField.value = originalMaskedEmail;
    emailField.type = 'text';

    emailField.addEventListener('focus', function() {
        if (this.value === originalMaskedEmail) {
            this.value = '';
            this.type = 'email';
            this.placeholder = 'Enter new email address';
        }
    });

    emailField.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            this.value = originalMaskedEmail;
            this.type = 'text';
            this.placeholder = 'Enter new email or leave as is';
        }
    });
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const newPassword = newPasswordField ? newPasswordField.value : '';
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
});