document.querySelector('form').addEventListener('submit', function(e) {
    const checkbox = document.getElementById('notify_comments');
    if (!checkbox.checked) {
        if (!confirm('Are you sure you want to disable email notifications? You will not receive emails when someone comments on your photos.')) {
            e.preventDefault();
        }
    }
});