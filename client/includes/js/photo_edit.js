let stickers = [];
let draggingSticker = null;
let resizingSticker = null;
let offsetX = 0, offsetY = 0;
let selectedStickerIndex = null;
let isCapturing = false;
let animationId = null;
let selectedPhotoId = null;
let webcamInitialized = false;

const video = document.getElementById('webcam');
const canvas = document.getElementById('canvas');
//const ctx = canvas.getContext('2d');
const webcamContainer = document.getElementById('webcam-container');
const uploadContainer = document.getElementById('upload-container');
const modeWebcamBtn = document.getElementById('mode-webcam-btn');
const modeUploadBtn = document.getElementById('mode-upload-btn');
const uploadInput = document.getElementById('upload-input');
const uploadPreview = document.getElementById('upload-preview');
const stickersSection = document.getElementById('superposable-images');
const captureBtn = document.getElementById('capture-btn');
const postUploadBtn = document.getElementById('post-upload-btn');

postUploadBtn.onclick = function() {
    const file = uploadInput.files[0];
    if (!file) {
        alert('Please select an image to upload.');
        return;
    }
    const formData = new FormData();
    formData.append('image_file', file);
    formData.append('make_public', '1'); 

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';

    fetch('save_image.php', {
        method: 'POST',
        body: formData // Send as FormData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.photo) {
            alert('Photo uploaded and saved successfully!');
            addPhotoToGallery(data.photo);
            
            // Reset the upload form
            uploadInput.value = '';
            uploadPreview.style.display = 'none';
            postUploadBtn.classList.add('d-none');

        } else {
            alert('Error saving photo: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('An error occurred during the upload.');
    })
    .finally(() => {
        // Restore button
        this.disabled = false;
        this.innerHTML = '📤 Post Photo';
    });
};

modeWebcamBtn.onclick = function() {
    modeWebcamBtn.classList.add('active');
    modeWebcamBtn.classList.remove('btn-outline-secondary');
    modeWebcamBtn.classList.add('btn-camagru');
    modeUploadBtn.classList.remove('active');
    modeUploadBtn.classList.add('btn-outline-secondary');
    modeUploadBtn.classList.remove('btn-camagru');
    webcamContainer.style.display = 'block';
    uploadContainer.style.display = 'none';
    stickersSection.classList.remove('d-none');
    captureBtn.classList.remove('d-none');
    postUploadBtn.classList.add('d-none');
    
    // Só inicializar webcam se ainda não foi inicializada
    if (!webcamInitialized) {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => { 
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                   if (!canvas.ctx) {
                        canvas.ctx = canvas.getContext('2d');
                    }
                    startVideoOverlay();
                };
                webcamInitialized = true;
            })
            .catch(err => { 
                alert('Could not access webcam: ' + err.message);
                // Voltar para modo upload se não conseguir acessar webcam
                modeUploadBtn.click();
            });
    } else {
        // Se já foi inicializada, apenas reativar o overlay
        startVideoOverlay();
    }
};

modeUploadBtn.onclick = function() {
    modeUploadBtn.classList.add('active');
    modeUploadBtn.classList.remove('btn-outline-secondary');
    modeUploadBtn.classList.add('btn-camagru');
    modeWebcamBtn.classList.remove('active');
    modeWebcamBtn.classList.add('btn-outline-secondary');
    modeWebcamBtn.classList.remove('btn-camagru');
    webcamContainer.style.display = 'none';
    uploadContainer.style.display = 'block';
    stickersSection.classList.add('d-none');
    captureBtn.classList.add('d-none');
    postUploadBtn.classList.remove('d-none');
    
    // Parar animation frame se estiver rodando
    if (animationId) {
        cancelAnimationFrame(animationId);
        animationId = null;
    }

    // Opcional: parar stream da webcam para economizar recursos
    // (comentado para manter a webcam "pronta" caso usuário volte)
    /*
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
        webcamInitialized = false;
    }
    */
};

function addPhotoToGallery(photo) {
    const thumbnailsContainer = document.getElementById('thumbnails');
    
    // Remove the "No photos yet" message if it exists
    const noPhotosMessage = thumbnailsContainer.querySelector('.text-center');
    if (noPhotosMessage) {
        noPhotosMessage.parentElement.remove();
    }

    const statusBadge = photo.is_public ? 
        '<span class="badge bg-success position-absolute top-0 end-0 m-1" style="font-size: 0.7em;">Public</span>' : 
        '<span class="badge bg-secondary position-absolute top-0 end-0 m-1" style="font-size: 0.7em;">Private</span>';

    const newPhotoHTML = `
        <div class="col-6">
            <div class="position-relative">
                <img src="${photo.file_path}" 
                     alt="${photo.filename}" 
                     class="img-fluid rounded gallery-image" 
                     style="height: 120px; width: 100%; object-fit: cover; cursor: pointer; transition: all 0.3s;"
                     data-photo-id="${photo.id}"
                     data-is-public="${photo.is_public}"
                     data-was-posted="${photo.was_posted || 0}"
                     title="Click to select">
                ${statusBadge}
                <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 rounded d-flex align-items-center justify-content-center" 
                     style="background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.3s;">
                    <span class="text-white">🖱️ Select</span>
                </div>
            </div>
        </div>`;

    thumbnailsContainer.insertAdjacentHTML('afterbegin', newPhotoHTML);
}

uploadInput.onchange = function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            uploadPreview.src = ev.target.result;
            uploadPreview.style.display = 'block';
            uploadPreview.classList.add('mx-auto'); // Center the image
            postUploadBtn.classList.remove('d-none'); // Show the post button
            // Aqui você pode adicionar lógica para permitir stickers sobre a imagem de upload
        };
        reader.readAsDataURL(file);
    } else {
        uploadPreview.style.display = 'none';
        postUploadBtn.classList.add('d-none'); // Hide button if no file is selected
    }
};

// Adiciona sticker ao clicar na miniatura
document.querySelectorAll('.sticker-thumb').forEach(img => {
    img.onclick = function() {
        if (isCapturing) return;
        
        const sticker = {
            src: this.src,
            x: 50,
            y: 50,
            width: 100,
            height: 80,
            img: new window.Image()
        };
        sticker.img.src = sticker.src;
        sticker.img.onload = () => {
            stickers.push(sticker);
            selectedStickerIndex = stickers.length - 1;
        };
    };
});

// Canvas overlay para stickers
function startVideoOverlay() {
    // Garantir que o canvas tenha o contexto
    if (!canvas.ctx) {
        canvas.ctx = canvas.getContext('2d');
    }
    
    const ctx = canvas.ctx;
    
    // Aguardar o vídeo estar carregado
    if (video.videoWidth === 0 || video.videoHeight === 0) {
        setTimeout(startVideoOverlay, 100);
        return;
    }
    
    // Posicionar o canvas sobre o vídeo
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = video.offsetLeft + 'px';
    canvas.style.display = 'block';
    canvas.style.pointerEvents = 'auto';
    canvas.style.zIndex = '10';
    canvas.style.width = video.offsetWidth + 'px';
    canvas.style.height = video.offsetHeight + 'px';
    
    // Definir dimensões reais do canvas
    canvas.width = video.videoWidth || 480;
    canvas.height = video.videoHeight || 360;
    
    function drawOverlay() {
        if (!isCapturing) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            stickers.forEach((sticker, i) => {
                if (sticker.img && sticker.img.complete) {
                    ctx.globalAlpha = 1;
                    ctx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
                    
                    if (i === selectedStickerIndex) {
                        // Borda de seleção
                        ctx.strokeStyle = '#007bff';
                        ctx.lineWidth = 2;
                        ctx.strokeRect(sticker.x, sticker.y, sticker.width, sticker.height);
                        
                        // Botão de deletar
                        const deleteX = sticker.x + sticker.width - 10;
                        const deleteY = sticker.y - 10;
                        ctx.fillStyle = '#ff0000';
                        ctx.fillRect(deleteX, deleteY, 20, 20);
                        ctx.fillStyle = '#ffffff';
                        ctx.font = '14px Arial';
                        ctx.fillText('×', deleteX + 6, deleteY + 14);
                        
                        // Botão de redimensionar
                        const resizeX = sticker.x + sticker.width - 10;
                        const resizeY = sticker.y + sticker.height - 10;
                        ctx.fillStyle = '#007bff';
                        ctx.fillRect(resizeX, resizeY, 20, 20);
                        ctx.fillStyle = '#ffffff';
                        ctx.fillText('⟲', resizeX + 4, resizeY + 14);
                    }
                }
            });
            
            animationId = requestAnimationFrame(drawOverlay);
        }
    }
    drawOverlay();
}

function resetCapture() {
    isCapturing = false;
    stickers = [];
    selectedStickerIndex = null;
    
    video.style.display = 'block';
    document.getElementById('captured-photo').style.display = 'none';
    document.getElementById('save-btn').style.display = 'none';
    
    // Esconder o canvas para não interferir no posicionamento
    canvas.style.display = 'none';
    captureBtn.textContent = '📷 Capture Photo';
    captureBtn.onclick = handleCapturePhoto;
    
    // Reiniciar o overlay apenas se estamos no modo webcam
    if (webcamContainer.style.display !== 'none') {
        setTimeout(() => {
            startVideoOverlay();
        }, 100);
    }
}

function safeRedirect(url) {
    const allowedUrls = [
        'index.php',
        './index.php',
        '/index.php',
        'profile.php',
        './profile.php',
        '/profile.php'
    ];
    
    if (allowedUrls.includes(url) && !url.includes('://')) {
        window.location.href = url;
    } else {
        window.location.href = 'index.php';
    }
}

function isClickOnDelete(mx, my, sticker) {
    const deleteX = sticker.x + sticker.width - 10;
    const deleteY = sticker.y - 10;
    return mx >= deleteX && mx <= deleteX + 20 && my >= deleteY && my <= deleteY + 20;
}

function isClickOnResize(mx, my, sticker) {
    const resizeX = sticker.x + sticker.width - 10;
    const resizeY = sticker.y + sticker.height - 10;
    return mx >= resizeX && mx <= resizeX + 20 && my >= resizeY && my <= resizeY + 20;
}

// Mouse events para stickers
canvas.addEventListener('mousedown', function(e) {
    if (isCapturing) return;
    
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const mx = (e.clientX - rect.left) * scaleX;
    const my = (e.clientY - rect.top) * scaleY;
    
    selectedStickerIndex = null;
    draggingSticker = null;
    resizingSticker = null;
    
    for (let i = stickers.length - 1; i >= 0; i--) {
        const s = stickers[i];
        
        if (isClickOnDelete(mx, my, s)) {
            stickers.splice(i, 1);
            selectedStickerIndex = null;
            return;
        }
        
        if (isClickOnResize(mx, my, s)) {
            resizingSticker = s;
            selectedStickerIndex = i;
            offsetX = mx - (s.x + s.width);
            offsetY = my - (s.y + s.height);
            return;
        }
        
        if (mx >= s.x && mx <= s.x + s.width && my >= s.y && my <= s.y + s.height) {
            draggingSticker = s;
            selectedStickerIndex = i;
            offsetX = mx - s.x;
            offsetY = my - s.y;
            break;
        }
    }
});

canvas.addEventListener('mousemove', function(e) {
    if (!isCapturing) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const mx = (e.clientX - rect.left) * scaleX;
        const my = (e.clientY - rect.top) * scaleY;
        
        if (draggingSticker) {
            draggingSticker.x = mx - offsetX;
            draggingSticker.y = my - offsetY;
        }
        
        if (resizingSticker) {
            const newWidth = Math.max(20, mx - resizingSticker.x);
            const newHeight = Math.max(20, my - resizingSticker.y);
            resizingSticker.width = newWidth;
            resizingSticker.height = newHeight;
        }
    }
});

canvas.addEventListener('mouseup', function() {
    draggingSticker = null;
    resizingSticker = null;
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Delete' && selectedStickerIndex !== null && !isCapturing) {
        stickers.splice(selectedStickerIndex, 1);
        selectedStickerIndex = null;
    }
});

// Capturar foto
function handleCapturePhoto() {
    // Verificar se estamos no modo webcam
    if (webcamContainer.style.display === 'none') {
        alert('Please switch to webcam mode to capture photos.');
        return;
    }
    
    if (!webcamInitialized || !video.srcObject) {
        alert('Webcam is not ready. Please try again.');
        return;
    }
    
    isCapturing = true;
    
    if (animationId) {
        cancelAnimationFrame(animationId);
    }
    
    video.style.display = 'none';
    canvas.style.display = 'none';
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);
    stickers.forEach(sticker => {
        tempCtx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
    });
    
    const photoResult = document.getElementById('photo-result');
    photoResult.src = tempCanvas.toDataURL('image/png');
    document.getElementById('captured-photo').style.display = 'block';
    
    // Configurar o canvas principal com a imagem capturada
    canvas.width = tempCanvas.width;
    canvas.height = tempCanvas.height;
    const mainCtx = canvas.getContext('2d');
    mainCtx.drawImage(tempCanvas, 0, 0);
    
    // Mostrar botões
    document.getElementById('save-btn').style.display = 'inline-block';
    const captureBtn = document.getElementById('capture-btn');
    captureBtn.textContent = 'Nova Foto';
    captureBtn.onclick = resetCapture;
}

document.getElementById('capture-btn').onclick = handleCapturePhoto;

// Salvar imagem
document.getElementById('save-btn').onclick = function() {
    const mainCtx = canvas.getContext('2d');
    const dataURL = canvas.toDataURL('image/png');
    console.log('Sending image data, length:', dataURL.length);
    
    fetch('save_image.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: dataURL })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text(); // Primeiro pegar como texto para debug
    })
    .then(responseText => {
        console.log('Raw response:', responseText);
        try {
            const data = JSON.parse(responseText);
            console.log('Parsed JSON:', data);
            if (data.success) {
                alert('Imagem salva com sucesso!');
                location.reload();
            } else {
                console.error('Server returned error:', data.error);
                alert('Erro ao salvar imagem: ' + (data.error || 'Erro desconhecido'));
            }
        } catch (parseError) {
            console.error('Failed to parse JSON:', parseError);
            console.error('Response was:', responseText);
            alert('Erro de comunicação com servidor.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Erro ao salvar imagem: ' + error.message);
    });
};

function selectPhoto(photoId, imgElement) {
    console.log('Selecting photo:', photoId);
    
    // Remove previous selection
    document.querySelectorAll('.gallery-image').forEach(gi => gi.classList.remove('selected'));
    
    // Select current image
    imgElement.classList.add('selected');
    selectedPhotoId = photoId;
    
    console.log('Selected photo ID:', selectedPhotoId);
    
    // Show preview
    const selectedPreview = document.getElementById('selected-photo-preview');
    const selectedPreviewImg = document.getElementById('selected-preview-img');
    
    if (selectedPreviewImg && selectedPreview) {
        selectedPreviewImg.src = imgElement.src;
        selectedPreview.style.display = 'block';
        console.log('Preview shown');
    }

    const togglePublicBtn = document.getElementById('toggle-public-btn');
    if (togglePublicBtn) {
        const wasPosted = imgElement.getAttribute('data-was-posted') === '1';
        togglePublicBtn.style.display = wasPosted ? 'block' : 'none';
    }
}

function deletePhoto(photoId, imgElement) {
    if (!confirm('Are you sure you want to delete this photo? This action cannot be undone.')) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('delete_photo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            photo_id: photoId,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove o elemento da interface
            const photoContainer = imgElement.closest('.col-6');
            photoContainer.remove();
            
            // Reset selection if this photo was selected
            if (selectedPhotoId == photoId) {
                selectedPhotoId = null;
                const selectedPreview = document.getElementById('selected-photo-preview');
                if (selectedPreview) {
                    selectedPreview.style.display = 'none';
                }
            }
            
            // Show success message
            alert('Photo deleted successfully!');
            
            // Check if gallery is now empty
            const remainingPhotos = document.querySelectorAll('.gallery-image');
            if (remainingPhotos.length === 0) {
                document.getElementById('thumbnails').innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-4">
                            <div class="text-muted">
                                <i class="fs-1">📸</i>
                                <p class="mt-2">No photos yet!</p>
                                <small>Start by capturing your first photo with the webcam.</small>
                            </div>
                        </div>
                    </div>
                `;
            }
        } else {
            alert('Error deleting photo: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting photo. Please try again.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing gallery functionality');
    
    // Gallery image selection
    const galleryImages = document.querySelectorAll('.gallery-image');
    const thumbnailsContainer = document.getElementById('thumbnails');
    const selectedPreview = document.getElementById('selected-photo-preview');
    const selectedPreviewImg = document.getElementById('selected-preview-img');
    const postPhotoBtn = document.getElementById('post-photo-btn');
    const deletePhotoBtn = document.getElementById('delete-photo-btn');
    const cancelSelectionBtn = document.getElementById('cancel-selection-btn');
    const togglePublicBtn = document.getElementById('toggle-public-btn');
    
    console.log('Found elements:', {
        galleryImages: galleryImages.length,
        selectedPreview: !!selectedPreview,
        postPhotoBtn: !!postPhotoBtn,
        deletePhotoBtn: !!deletePhotoBtn,
        cancelSelectionBtn: !!cancelSelectionBtn
    });

    if (!thumbnailsContainer) {
        console.log('Thumbnails container not found');
        return;
    }

    if (galleryImages.length === 0) {
        console.log('No gallery images found');
        return;
    }

    galleryImages.forEach((img, index) => {
        console.log(`Setting up gallery image ${index}:`, img.dataset.photoId);
        
        img.addEventListener('click', function() {
            console.log('Gallery image clicked:', this.dataset.photoId);
            
            // Remove previous selection
            galleryImages.forEach(gi => gi.classList.remove('selected'));
            
            // Select current image
            this.classList.add('selected');
            selectedPhotoId = this.dataset.photoId;
            
            console.log('Selected photo ID:', selectedPhotoId);
            
            // Show preview
            if (selectedPreviewImg && selectedPreview) {
                selectedPreviewImg.src = this.src;
                selectedPreview.style.display = 'block';
                console.log('Preview shown');
            }
        });

        // Hover effects
        img.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            selectPhoto(img.dataset.photoId, this);
        });
        
        // Click handler no overlay
        const container = img.closest('.position-relative');
        const overlay = container ? container.querySelector('.gallery-overlay') : null;
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                selectPhoto(img.dataset.photoId, img);
            });
            
            // Tornar o overlay clicável
            overlay.style.cursor = 'pointer';
            overlay.style.pointerEvents = 'auto';
        }
    }); // <-- closes galleryImages.
    
    thumbnailsContainer.addEventListener('click', function(e) {
        // Find the container of the clicked photo
        const photoContainer = e.target.closest('.position-relative');
        if (photoContainer) {
            const galleryImage = photoContainer.querySelector('.gallery-image');
            if (galleryImage) {
                e.preventDefault();
                selectPhoto(galleryImage.dataset.photoId, galleryImage);
            }
        }
    });

    // Cancel selection
    if (cancelSelectionBtn) {
        cancelSelectionBtn.addEventListener('click', function() {
            console.log('Cancel selection clicked');
            galleryImages.forEach(gi => gi.classList.remove('selected'));
            selectedPhotoId = null;
            if (selectedPreview) {
                selectedPreview.style.display = 'none';
            }
        });
    }

    // Post photo to main gallery
    if (postPhotoBtn) {
        postPhotoBtn.addEventListener('click', function() {
            console.log('Post photo clicked, selectedPhotoId:', selectedPhotoId);
            
            if (!selectedPhotoId) {
                alert('Please select a photo first.');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            console.log('CSRF token:', csrfToken);

            fetch('post_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    photo_id: selectedPhotoId,
                    csrf_token: csrfToken
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    alert('Photo posted successfully to the main gallery!');
                    // Reset selection
                    galleryImages.forEach(gi => gi.classList.remove('selected'));
                    selectedPhotoId = null;
                    if (selectedPreview) {
                        selectedPreview.style.display = 'none';
                    }

                    if (confirm('Would you like to view your post in the main gallery?')) {
                        safeRedirect('index.php');
                    }
                } else {
                    alert('Error posting photo: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error posting photo. Please try again.');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '📤 Post to Gallery';
            });
        });
    }

    // Delete photo
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function() {
            console.log('Delete photo clicked, selectedPhotoId:', selectedPhotoId);
            
            if (!selectedPhotoId) {
                alert('Please select a photo first.');
                return;
            }

            const selectedImg = document.querySelector(`.gallery-image[data-photo-id="${selectedPhotoId}"]`);
            if (selectedImg) {
                deletePhoto(selectedPhotoId, selectedImg);
            }
        });
    }

    if (togglePublicBtn) {
        togglePublicBtn.addEventListener('click', function() {
            if (!selectedPhotoId) {
                alert('Please select a photo first.');
                return;
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            togglePublicBtn.disabled = true;
            togglePublicBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Toggling...';

            fetch('toggle_photo_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    photo_id: selectedPhotoId,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.new_status ? 'Photo is now PUBLIC!' : 'Photo is now PRIVATE!');
                    // Opcional: atualizar badge na galeria
                    const selectedImg = document.querySelector(`.gallery-image[data-photo-id="${selectedPhotoId}"]`);
                    if (selectedImg) {
                        const badge = selectedImg.parentElement.querySelector('.badge');
                        if (badge) {
                            badge.className = data.new_status ? 'badge bg-success position-absolute top-0 end-0 m-1' : 'badge bg-secondary position-absolute top-0 end-0 m-1';
                            badge.textContent = data.new_status ? 'Public' : 'Private';
                        }
                    }
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error toggling status. Please try again.');
            })
            .finally(() => {
                togglePublicBtn.disabled = false;
                togglePublicBtn.innerHTML = '🔒 Toggle Public/Private';
            });
        });
    }
}); // <-- closes DOMContentLoaded event listener