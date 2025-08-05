<?php
    session_start();
    require_once 'backend.php';
    
    $is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
    
    $message = '';
    if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
        $message = 'Logout realizado com sucesso!';
    }
    $page_title = 'Camagru';
    $page_name = 'Camagru';

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $photos_per_page = 5;
    $offset = ($page - 1) * $photos_per_page;

    $sql = "
        SELECT 
            p.id,
            p.user_id,
            p.username,
            p.file_path,
            p.created_at,
            (SELECT COUNT(*) FROM likes l WHERE l.photo_id = p.id) as like_count,
            (SELECT COUNT(*) FROM comments c WHERE c.photo_id = p.id AND c.is_active = 1) as comment_count
        FROM user_photos p 
        WHERE p.is_public = 1 AND p.is_active = 1 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $photos_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $photos_result = mysqli_stmt_get_result($stmt);
    
    // Contar total de fotos para paginação
    $count_sql = "SELECT COUNT(*) as total FROM user_photos WHERE is_public = 1 AND is_active = 1";
    $count_result = mysqli_query($conn, $count_sql);
    $total_photos = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_photos / $photos_per_page);
    
    mysqli_stmt_close($stmt);

    include 'includes/header.php';
?>
    <div class="container my-4">
    <?php if ($message): ?>
        <div class="alert alert-success text-center">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Welcome Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
            <div class="card shadow custom-card text-center">
                <div class="card-body">
                    <?php if ($is_logged_in): ?>
                        <h2 class="card-title mb-3">Bem-vindo, <?php echo sanitize_input($_SESSION['username']); ?>!</h2>
                        <p class="card-text mb-3">Ready to create amazing photos with stickers?</p>
                        <a href="photo_edit.php" class="btn btn-camagru btn-lg">Enter Studio</a>
                    <?php else: ?>
                        <h2 class="card-title mb-3">Bem-vindo ao Camagru</h2>
                        <p class="card-text mb-3">Discover amazing photos created by our community!</p>
                        <a href="signup.php" class="btn btn-camagru btn-lg me-2">Sign Up</a>
                        <a href="login.php" class="btn btn-outline-secondary btn-lg">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Photos Gallery -->
    <div class="row">
        <div class="col-12">
            <h3 class="mb-4">📸 Community Gallery</h3>
            
            <?php if (mysqli_num_rows($photos_result) > 0): ?>
                <div class="row g-4">
                    <?php while ($photo = mysqli_fetch_assoc($photos_result)): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card shadow custom-card h-100">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" 
                                         class="card-img-top" 
                                         style="height: 300px; object-fit: cover;"
                                         alt="Photo by <?php echo htmlspecialchars($photo['username']); ?>">
                                    
                                    <!-- Photo overlay with username -->
                                    <div class="position-absolute top-0 start-0 end-0 p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="badge bg-dark bg-opacity-75 fs-6">
                                                👤 <?php echo htmlspecialchars($photo['username']); ?>
                                            </span>
                                            <span class="badge bg-dark bg-opacity-75 fs-6">
                                                📅 <?php echo date('M j', strtotime($photo['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($is_logged_in): ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-link p-0 me-3 like-btn" 
                                                    data-photo-id="<?php echo $photo['id']; ?>">
                                                <span class="like-icon">❤️</span>
                                                <span class="like-count"><?php echo $photo['like_count']; ?></span>
                                            </button>
                                            <span class="text-muted">
                                                💬 <?php echo $photo['comment_count']; ?> comments
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y \a\t H:i', strtotime($photo['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <!-- Comments Section -->
                                    <div class="comments-section" data-photo-id="<?php echo $photo['id']; ?>">
                                        <div class="comments-list mb-3" style="max-height: 150px; overflow-y: auto;">
                                            <!-- Comments will be loaded here -->
                                        </div>
                                        
                                        <!-- Add Comment Form -->
                                        <form class="add-comment-form" data-photo-id="<?php echo $photo['id']; ?>">
                                            <div class="input-group">
                                                <input type="text" 
                                                       class="form-control form-control-sm comment-input" 
                                                       placeholder="Add a comment..." 
                                                       maxlength="500"
                                                       required>
                                                <button class="btn btn-camagru btn-sm" type="submit">
                                                    📤
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="card-body">
                                    <div class="text-center text-muted">
                                        <p class="mb-2">❤️ <?php echo $photo['like_count']; ?> likes • 💬 <?php echo $photo['comment_count']; ?> comments</p>
                                        <small>
                                            <a href="login.php" class="link-camagru">Login</a> 
                                            to like and comment
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Gallery pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="text-muted">
                        <i class="display-1">📸</i>
                        <h4 class="mt-3">No photos yet!</h4>
                        <p>Be the first to share a photo with the community.</p>
                        <?php if ($is_logged_in): ?>
                            <a href="photo_edit.php" class="btn btn-camagru mt-3">Create First Photo</a>
                        <?php else: ?>
                            <a href="signup.php" class="btn btn-camagru mt-3">Join the Community</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($is_logged_in): ?>
<script src="includes/js/gallery.js"></script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>