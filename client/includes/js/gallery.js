document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Carregar comentários para todas as fotos
    document.querySelectorAll('.comments-section').forEach(section => {
        const photoId = section.dataset.photoId;
        loadComments(photoId);
    });
    
    // Event listeners para likes
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const photoId = this.dataset.photoId;
            toggleLike(photoId, this);
        });
    });
    
    // Event listeners para formulários de comentário
    document.querySelectorAll('.add-comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const photoId = this.dataset.photoId;
            const input = this.querySelector('.comment-input');
            const commentText = input.value.trim();
            
            if (commentText) {
                addComment(photoId, commentText, input);
            }
        });
    });
    
    function loadComments(photoId) {
        fetch(`comments.php?action=get&photo_id=${photoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentsContainer = document.querySelector(`.comments-section[data-photo-id="${photoId}"] .comments-list`);
                    if (commentsContainer) {
                        displayComments(commentsContainer, data.comments);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading comments:', error);
            });
    }
    
    function displayComments(container, comments) {
        if (comments.length === 0) {
            container.innerHTML = '<small class="text-muted">No comments yet. Be the first to comment!</small>';
            return;
        }
        
        container.innerHTML = comments.map(comment => `
            <div class="comment mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong class="text-primary">${comment.username}</strong>
                        <p class="mb-1 small">${comment.comment_text}</p>
                    </div>
                    <small class="text-muted ms-2">${comment.created_at}</small>
                </div>
            </div>
        `).join('');
        
        // Scroll para o final dos comentários
        container.scrollTop = container.scrollHeight;
    }
    
    function addComment(photoId, commentText, inputElement) {
        const submitBtn = inputElement.closest('form').querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        fetch('comments.php?action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                photo_id: photoId,
                comment_text: commentText,
                csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                inputElement.value = '';
                loadComments(photoId); // Recarregar comentários
                
                // Atualizar contador de comentários
                const commentCountSpan = document.querySelector(`[data-photo-id="${photoId}"] .comment-count`);
                if (commentCountSpan) {
                    const currentCount = parseInt(commentCountSpan.textContent) || 0;
                    commentCountSpan.textContent = currentCount + 1;
                }
            } else {
                alert('Error adding comment: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error adding comment:', error);
            alert('Error adding comment. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    function toggleLike(photoId, buttonElement) {
        const likeIcon = buttonElement.querySelector('.like-icon');
        const likeCount = buttonElement.querySelector('.like-count');
        const originalIcon = likeIcon.textContent;
        
        buttonElement.disabled = true;
        likeIcon.textContent = '⏳';
        
        fetch('likes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                photo_id: photoId,
                csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                likeCount.textContent = data.like_count;
                likeIcon.textContent = data.liked ? '❤️' : '🤍';
                
                // Adicionar efeito visual
                if (data.action === 'added') {
                    buttonElement.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        buttonElement.style.transform = 'scale(1)';
                    }, 200);
                }
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                likeIcon.textContent = originalIcon;
            }
        })
        .catch(error => {
            console.error('Error toggling like:', error);
            likeIcon.textContent = originalIcon;
            alert('Error. Please try again.');
        })
        .finally(() => {
            buttonElement.disabled = false;
        });
    }
});