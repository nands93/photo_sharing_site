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

<div class="container-fluid my-4">
    <div class="row g-4">
        <!-- Main Section -->
        <div class="col-lg-8">
            <div class="card shadow custom-card h-100">
                <div class="card-body">
                    <h3 class="card-title mb-4">Studio</h3>
                    <!-- Modo de captura -->
                    <div class="mb-4 text-center">
                        <div class="btn-group" role="group" aria-label="Modo de captura">
                            <button type="button" id="mode-webcam-btn" class="btn btn-outline-secondary">Usar Webcam</button>
                            <button type="button" id="mode-upload-btn" class="btn btn-camagru active">Upload de Arquivo</button>
                        </div>
                    </div>
                    <!-- Webcam Container -->
                    <div id="webcam-container" class="text-center mb-4 position-relative" style="display: none;">
                        <video id="webcam" width="480" height="360" autoplay playsinline class="rounded border shadow-sm mx-auto"></video>
                        <canvas id="canvas" width="480" height="360" style="display: none;"></canvas>
                        <div id="captured-photo" style="display:none;">
                            <img id="photo-result" class="img-fluid rounded border shadow-sm mt-3" style="max-width:100%;">
                        </div>
                    </div>
                    <!-- Upload Container -->
                    <div id="upload-container" class="text-center mb-4" style="display: block;">
                        <input type="file" id="upload-input" accept="image/*" class="form-control mb-3" style="max-width: 300px; margin: 0 auto;">
                        <img id="upload-preview" style="display:none; max-width: 100%; height: auto;" class="rounded border shadow-sm mt-3">
                    </div>

                    <!-- Stickers Section -->
                    <div id="superposable-images" class="mb-4 d-none">
                        <!-- ...stickers... -->
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <div class="sticker-container">
                                <img src="images/stickers/hat.png" alt="Hat" class="sticker-thumb btn btn-outline-secondary p-2" 
                                     style="width:60px; height:60px; object-fit: contain; cursor:pointer;">
                                <small class="d-block text-center mt-1 text-muted">Hat</small>
                            </div>
                            <div class="sticker-container">
                                <img src="images/stickers/glasses.png" alt="Glasses" class="sticker-thumb btn btn-outline-secondary p-2" 
                                     style="width:60px; height:60px; object-fit: contain; cursor:pointer;">
                                <small class="d-block text-center mt-1 text-muted">Glasses</small>
                            </div>
                            <div class="sticker-container">
                                <img src="images/stickers/mustache.png" alt="Mustache" class="sticker-thumb btn btn-outline-secondary p-2" 
                                     style="width:60px; height:60px; object-fit: contain; cursor:pointer;">
                                <small class="d-block text-center mt-1 text-muted">Mustache</small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button id="capture-btn" class="btn btn-camagru btn-lg me-2 d-none">
                            📷 Capture Photo
                        </button>
                        <button id="post-upload-btn" class="btn btn-success btn-lg d-none">
                            📤 Post Photo
                        </button>
                        <button id="save-btn" class="btn btn-success btn-lg" style="display:none;">
                            💾 Save Photo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow custom-card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Gallery</h5>
                    
                    <div id="thumbnails" class="row g-2" style="max-height: 60vh; overflow-y: auto;">
                        <?php
                        // Buscar imagens do usuário no banco de dados
                        $stmt = mysqli_prepare($conn, "
                            SELECT id, file_path, filename, is_public, was_posted
                            FROM user_photos 
                            WHERE user_id = ? AND is_active = 1 
                            ORDER BY created_at DESC LIMIT 20
                        ");
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($photo = mysqli_fetch_assoc($result)) {
                                $was_posted = $photo['is_public'] ? 1 : ($photo['was_posted'] ?? 0);
                                $statusBadge = $photo['is_public'] ? 
                                    '<span class="badge bg-success position-absolute top-0 end-0 m-1" style="font-size: 0.7em;">Public</span>' : 
                                    '<span class="badge bg-secondary position-absolute top-0 end-0 m-1" style="font-size: 0.7em;">Private</span>';

                                echo '
                                <div class="col-6">
                                    <div class="position-relative">
                                        <img src="' . htmlspecialchars($photo['file_path']) . '" 
                                            alt="' . htmlspecialchars($photo['filename']) . '" 
                                            class="img-fluid rounded gallery-image" 
                                            style="height: 120px; width: 100%; object-fit: cover; cursor: pointer; transition: all 0.3s;"
                                            data-photo-id="' . $photo['id'] . '"
                                            data-is-public="' . $photo['is_public'] . '"
                                            data-was-posted="' . ($photo['was_posted'] ?? 0) . '"
                                            title="Click to select">
                                        ' . $statusBadge . '
                                        <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 rounded d-flex align-items-center justify-content-center" 
                                            style="background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.3s;">
                                            <span class="text-white">🖱️ Select</span>
                                        </div>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fs-1">📸</i>
                                        <p class="mt-2">No photos yet!</p>
                                        <small>Start by capturing your first photo with the webcam.</small>
                                    </div>
                                </div>
                            </div>';
                        }
                        mysqli_stmt_close($stmt);
                        ?>
                    </div>
                    
                    <!-- Selected Photo Preview -->
                    <div id="selected-photo-preview" class="mt-4 pt-3 border-top" style="display: none;">
                    <h6 class="mb-3">Selected Photo:</h6>
                    <div class="text-center mb-3">
                        <img id="selected-preview-img" class="img-fluid rounded" style="max-height: 150px;">
                    </div>
                    <div class="d-grid gap-2">
                        <button id="post-photo-btn" class="btn btn-camagru">
                            📤 Post to Gallery
                        </button>
                        <button id="toggle-public-btn" class="btn btn-warning" style="display:none;">
                            🔒 Toggle Public/Private
                        </button>
                        <button id="delete-photo-btn" class="btn btn-danger">
                            🗑️ Delete Photo
                        </button>
                        <button id="cancel-selection-btn" class="btn btn-outline-secondary btn-sm">
                            Cancel Selection
                        </button>
                    </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                🏠 Back to Gallery
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                                👤 View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Instructions Toast (Optional) -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div class="toast" id="instructionToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="toast-header">
            <strong class="me-auto">💡 Quick Tip</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Click on gallery images to select them, then post to main gallery or delete!
        </div>
    </div>
</div>

<script src="includes/js/photo_edit.js"></script>
<script>
// Show instruction toast on page load
document.addEventListener('DOMContentLoaded', function() {
    const toast = new bootstrap.Toast(document.getElementById('instructionToast'));
    setTimeout(() => {
        toast.show();
    }, 1000);
});
</script>

<?php include 'includes/footer.php'; ?>