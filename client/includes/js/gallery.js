document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = parseInt(document.querySelector('meta[name="current-user-id"]')?.getAttribute('content')) || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    window.currentUserId = currentUserId;
    window.csrfToken = csrfToken;
    
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
                        displayComments(commentsContainer, data.comments, photoId);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading comments:', error);
            });
    }
    
    function displayComments(container, comments, photoId) {
        if (comments.length === 0) {
            container.innerHTML = '<small class="text-muted">No comments yet. Be the first to comment!</small>';
            return;
        }
        container.innerHTML = comments.map(comment => `
            <div class="comment mb-2" data-comment-id="${comment.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong class="text-primary">${comment.username}</strong>
                        <p class="mb-1 small">${comment.comment_text}</p>
                    </div>
                    <div class="ms-2 d-flex flex-column align-items-end">
                        <small class="text-muted">${comment.created_at}</small>
                        ${window.currentUserId && comment.user_id == window.currentUserId ? `
                            <button class="btn btn-link btn-sm text-danger delete-comment-btn p-0 mt-1" 
                                    title="Delete comment" 
                                    data-comment-id="${comment.id}"
                                    style="font-size: 12px; line-height: 1;">
                                🗑️
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        // Adicionar event listener para deletar
        container.querySelectorAll('.delete-comment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                deleteComment(commentId, photoId);
            });
        });
        
        container.scrollTop = container.scrollHeight;
    }

    function deleteComment(commentId, photoId) {
        if (!confirm('Are you sure you want to delete this comment?')) return;
        
        // Desabilitar o botão temporariamente
        const deleteBtn = document.querySelector(`[data-comment-id="${commentId}"]`);
        if (deleteBtn) {
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '⏳';
        }
        
        fetch('comments.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                comment_id: commentId,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Recarregar comentários
                loadComments(photoId);
                
                // Atualizar contador de comentários no HTML
                updateCommentCount(photoId, -1);
                
                // Mostrar feedback visual
                showNotification('Comment deleted successfully', 'success');
            } else {
                alert(data.error || 'Failed to delete comment');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '🗑️';
                }
            }
        })
        .catch(() => {
            alert('Error deleting comment');
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '🗑️';
            }
        });
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
                loadComments(photoId);
                
                // Atualizar contador de comentários
                updateCommentCount(photoId, 1);
                
                showNotification('Comment added successfully', 'success');
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
    
    function updateCommentCount(photoId, increment) {
        // Encontrar o span que mostra o número de comentários
        const commentSpan = document.querySelector(`[data-photo-id="${photoId}"]`)
            ?.closest('.card')
            ?.querySelector('.text-muted');
        
        if (commentSpan) {
            const text = commentSpan.textContent;
            const match = text.match(/💬 (\d+) comments/);
            if (match) {
                const currentCount = parseInt(match[1]);
                const newCount = Math.max(0, currentCount + increment);
                commentSpan.innerHTML = `💬 ${newCount} comments`;
            }
        }
    }
    
    function showNotification(message, type = 'info') {
        // Criar notificação simples
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} alert-dismissible position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remover após 3 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
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