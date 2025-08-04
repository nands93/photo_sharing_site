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
<main style="display: flex; gap: 2rem;">
    <!-- Main Section -->
    <section style="flex: 2;">
        <h2>Webcam Preview</h2>
        <div id="webcam-container">
            <video id="webcam" autoplay playsinline width="480" height="360" style="border:1px solid #ccc;"></video>
            <canvas id="canvas" width="480" height="360" style="display:none; border:1px solid #ccc;"></canvas>
            <div id="captured-photo" style="display:none;">
                <img id="photo-result" width="480" height="360" style="border:1px solid #ccc;">
            </div>
        </div>
        <div id="superposable-images" style="margin: 1rem 0;">
            <h3>Superposable Images</h3>
            <img src="images/stickers/hat.png" alt="Hat" class="sticker-thumb" style="width:60px;cursor:pointer;">
            <img src="images/stickers/glasses.png" alt="Glasses" class="sticker-thumb" style="width:60px;cursor:pointer;">
        </div>
        <button id="capture-btn">Capture Photo</button>
        <button id="save-btn" style="display:none; margin-left:1rem;">Save</button>
    </section>

    <!-- Side Section -->
    <aside style="flex: 1;">
        <h3>Previous Pictures</h3>
        <div id="thumbnails" style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php
            // Buscar imagens do usuário no banco de dados com mais informações
            $stmt = mysqli_prepare($conn, "
                SELECT file_path, filename, created_at, file_size, width, height 
                FROM user_photos 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY created_at DESC LIMIT 10
            ");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($photo = mysqli_fetch_assoc($result)) {
                $created_date = date('d/m/Y H:i', strtotime($photo['created_at']));
                $file_size_kb = round($photo['file_size'] / 1024, 1);
                echo '<div style="border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                        <img src="' . htmlspecialchars($photo['file_path']) . '" 
                             alt="' . htmlspecialchars($photo['filename']) . '" 
                             style="width:100px; cursor:pointer;" 
                             title="' . $created_date . ' - ' . $file_size_kb . 'KB">
                        <div style="font-size: 11px; color: #666;">
                            ' . $created_date . '<br>
                            ' . $file_size_kb . 'KB
                        </div>
                      </div>';
            }
            mysqli_stmt_close($stmt);
            ?>
        </div>
    </aside>
</main>
<script src="includes/js/edit_image.js"></script>
<?php include 'includes/footer.php'; ?>