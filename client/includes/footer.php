</div> <!-- Fecha container-fluid do header -->

<footer class="border-top mt-auto py-4 custom-footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 me-2">📸 Camagru</h5>
                    <small class="text-muted">42 Rio</small>
                </div>
            </div>
            <div class="col-md-6">
                <nav class="d-flex justify-content-md-end justify-content-center">
                    <ul class="nav">
                        <li class="nav-item">
                            <a class="nav-link text-dark" href="index.php">Camagru</a>
                        </li>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="signup.php">Sign Up</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="profile.php">Profile</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="row">
            <div class="col-12">
                <hr class="my-3">
                <p class="text-center text-muted mb-0">
                    &copy; <?php echo date('Y'); ?> <strong>Camagru</strong> - 
                    Developed by <strong>femarque</strong> |
                </p>
            </div>
        </div>
    </div>
</footer>

    <style>
    /* CSS customizado para o footer */
    .custom-footer {
        background: rgba(240, 236, 225, 0.95);
        backdrop-filter: blur(10px);
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .custom-footer .nav-link {
        font-weight: 500;
        color: #222 !important;
        transition: color 0.3s ease;
    }
    
    .custom-footer .nav-link:hover {
        color: #b37e03 !important;
    }
    
    /* Garante que o footer fique no final da página */
    html, body {
        height: 100%;
    }
    
    body {
        display: flex;
        flex-direction: column;
    }
    
    .container-fluid {
        flex: 1 0 auto;
    }
    
    footer {
        flex-shrink: 0;
    }
</style>
</body>
</html>