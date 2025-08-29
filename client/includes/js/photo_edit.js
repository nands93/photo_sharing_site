let stickers = [];
let draggingSticker = null;
let resizingSticker = null;
let offsetX = 0, offsetY = 0;
let selectedStickerIndex = null;
let isCapturing = false;
let animationId = null;
let selectedPhotoId = null;
let webcamInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
    const instructionToastEl = document.getElementById('instructionToast');
    if (instructionToastEl) {
        setTimeout(() => {
            instructionToastEl.classList.add('show');
            instructionToastEl.style.display = 'block';
            setTimeout(() => {
                instructionToastEl.classList.remove('show');
                instructionToastEl.style.display = 'none';
            }, 5000);
        }, 1000);
    }

    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const webcamContainer = document.getElementById('webcam-container');
    const uploadContainer = document.getElementById('upload-container');
    const modeWebcamBtn = document.getElementById('mode-webcam-btn');
    const modeUploadBtn = document.getElementById('mode-upload-btn');
    const uploadInput = document.getElementById('upload-input');
    const uploadPreview = document.getElementById('upload-preview');
    const stickersSection = document.getElementById('superposable-images');
    const captureBtn = document.getElementById('capture-btn');
    const postUploadBtn = document.getElementById('post-upload-btn');
    const thumbnailsContainer = document.getElementById('thumbnails');
    const cancelSelectionBtn = document.getElementById('cancel-selection-btn');
    const postPhotoBtn = document.getElementById('post-photo-btn');
    const deletePhotoBtn = document.getElementById('delete-photo-btn');
    const togglePublicBtn = document.getElementById('toggle-public-btn');
    const selectedPreview = document.getElementById('selected-photo-preview');
    const uploadPreviewContainer = document.querySelector('.upload-preview-container');

    function attachCanvasToUpload() {
        if (canvas && uploadPreviewContainer && canvas.parentElement !== uploadPreviewContainer) {
            uploadPreviewContainer.appendChild(canvas);
        }
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.zIndex = '10';
        canvas.style.pointerEvents = 'auto';
        canvas.style.borderRadius = '0.375rem';
        canvas.style.display = 'block';
    }

    function attachCanvasToWebcam() {
        if (canvas && webcamContainer && canvas.parentElement !== webcamContainer) {
            webcamContainer.appendChild(canvas);
        }
        canvas.style.position = 'absolute';
        canvas.style.top = video.offsetTop + 'px';
        canvas.style.left = video.offsetLeft + 'px';
        canvas.style.zIndex = '10';
        canvas.style.pointerEvents = 'auto';
        canvas.style.borderRadius = '0.375rem';
        canvas.style.display = 'block';
    }

    const requiredElements = {
        video, canvas, webcamContainer, uploadContainer, 
        modeWebcamBtn, modeUploadBtn, uploadInput, uploadPreview,
        stickersSection, captureBtn, postUploadBtn, thumbnailsContainer,
        cancelSelectionBtn, postPhotoBtn, deletePhotoBtn, togglePublicBtn,
        selectedPreview
    };
    
    for (const [name, element] of Object.entries(requiredElements)) {
        if (!element) {
            return;
        }
    }

    window.addEventListener('resize', function() {
        if (modeUploadBtn.classList.contains('active') && uploadPreview.style.display === 'block') {
            canvas.style.width = uploadPreview.offsetWidth + 'px';
            canvas.style.height = uploadPreview.offsetHeight + 'px';
            canvas.style.top = '0';
            canvas.style.left = '0';
        }
        if (modeWebcamBtn.classList.contains('active') && webcamContainer.style.display !== 'none') {
            setTimeout(() => {
                canvas.style.top = video.offsetTop + 'px';
                canvas.style.left = video.offsetLeft + 'px';
            }, 100);
        }
    });
    
    function switchToUploadMode() {
        modeUploadBtn.classList.add('active', 'btn-camagru');
        modeUploadBtn.classList.remove('btn-outline-secondary');
        modeWebcamBtn.classList.remove('active', 'btn-camagru');
        modeWebcamBtn.classList.add('btn-outline-secondary');
        
        webcamContainer.style.display = 'none';
        uploadContainer.style.display = 'block';
        captureBtn.classList.add('d-none');
        
        const canvas = document.getElementById('canvas');
        canvas.style.display = 'none';
        canvas.style.position = 'absolute';
        canvas.style.top = '';
        canvas.style.left = '';
        canvas.style.border = 'none';
        
        if (animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }
        
        stickers = [];
        selectedStickerIndex = null;

        if (uploadPreviewContainer && canvas.parentElement !== uploadPreviewContainer) {
            uploadPreviewContainer.appendChild(canvas);
        }
        
        if (uploadInput.files && uploadInput.files.length > 0) {
            postUploadBtn.classList.remove('d-none');
            stickersSection.classList.remove('d-none');
            setupUploadCanvas();
        } else {
            postUploadBtn.classList.add('d-none');
            stickersSection.classList.add('d-none');
        }
    }

    function setupUploadCanvas() {
        const uploadPreview = document.getElementById('upload-preview');
        const canvas = document.getElementById('canvas');
        const uploadContainer = document.getElementById('upload-container');
        
        uploadPreview.onload = function() {
            setTimeout(() => {
                canvas.width = uploadPreview.naturalWidth;
                canvas.height = uploadPreview.naturalHeight;
                
                attachCanvasToUpload();
                
                canvas.style.display = 'block';
                canvas.style.pointerEvents = 'auto';
                
                const ctx = canvas.getContext('2d');
                canvas.ctx = ctx;
                
                stickers = [];
                selectedStickerIndex = null;
                
                stickersSection.classList.remove('d-none');
                
                startUploadOverlay();
            }, 100);
        };
    }

    function attachCanvasToUpload() {
        if (canvas && uploadPreviewContainer && canvas.parentElement !== uploadPreviewContainer) {
            uploadPreviewContainer.appendChild(canvas);
        }
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.zIndex = '10';
        canvas.style.pointerEvents = 'auto';
        canvas.style.borderRadius = '0.375rem';
        canvas.style.display = 'block';
    }
    
    function switchToWebcamMode() {
        modeWebcamBtn.classList.add('active', 'btn-camagru');
        modeWebcamBtn.classList.remove('btn-outline-secondary');
        modeUploadBtn.classList.remove('active', 'btn-camagru');
        modeUploadBtn.classList.add('btn-outline-secondary');
        
        webcamContainer.style.display = 'block';
        uploadContainer.style.display = 'none';
        stickersSection.classList.remove('d-none');
        captureBtn.classList.remove('d-none');
        postUploadBtn.classList.add('d-none');
        
        if (animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }
        
        const canvas = document.getElementById('canvas');
        canvas.style.display = 'none';
        canvas.style.position = 'absolute';
        canvas.style.top = '';
        canvas.style.left = '';
        canvas.style.border = 'none';
        
        stickers = [];
        selectedStickerIndex = null;
        
        if (webcamContainer && canvas.parentElement !== webcamContainer) {
            webcamContainer.appendChild(canvas);
        }

        if (!webcamInitialized) {
            initializeWebcam();
        } else {
            setTimeout(() => startVideoOverlay(), 200);
        }
    }

    modeWebcamBtn.addEventListener('click', function(e) {
        e.preventDefault();
        switchToWebcamMode();
    });

    modeUploadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        switchToUploadMode();
    });

    uploadInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file && file.type.startsWith('image/')) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File size too large. Please select an image smaller than 5MB.');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(ev) {
                uploadPreview.src = ev.target.result;
                uploadPreview.style.display = 'block';
                uploadPreview.classList.add('mx-auto');
                postUploadBtn.classList.remove('d-none');
                
                const canvas = document.getElementById('canvas');
                canvas.style.display = 'none';
                
                setupUploadCanvas();
            };
            reader.onerror = () => {
                alert('Error reading file. Please try again.');
            };
            reader.readAsDataURL(file);
        } else {
            uploadPreview.style.display = 'none';
            postUploadBtn.classList.add('d-none');
            stickersSection.classList.add('d-none');
            const canvas = document.getElementById('canvas');
            canvas.style.display = 'none';
            if (file) {
                alert('Please select a valid image file (jpg, png, gif, etc.)');
                this.value = '';
            }
        }
    });

    function startUploadOverlay() {
        const canvas = document.getElementById('canvas');
        const ctx = canvas.ctx;
        
        
        if (!ctx) return;
        
        function drawUploadOverlay() {
            if (modeUploadBtn.classList.contains('active') && uploadPreview.style.display === 'block') {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                stickers.forEach((sticker, i) => {
                    if (sticker.img && sticker.img.complete) {
                        ctx.globalAlpha = 1;
                        ctx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
                        
                        if (i === selectedStickerIndex) {
                            ctx.strokeStyle = '#007bff';
                            ctx.lineWidth = 2;
                            ctx.strokeRect(sticker.x, sticker.y, sticker.width, sticker.height);
                            
                            const deleteX = sticker.x + sticker.width - 10;
                            const deleteY = sticker.y - 10;
                            ctx.fillStyle = '#ff0000';
                            ctx.fillRect(deleteX, deleteY, 20, 20);
                            ctx.fillStyle = '#ffffff';
                            ctx.font = '14px Arial';
                            ctx.fillText('×', deleteX + 6, deleteY + 14);
                            
                            const resizeX = sticker.x + sticker.width - 10;
                            const resizeY = sticker.y + sticker.height - 10;
                            ctx.fillStyle = '#007bff';
                            ctx.fillRect(resizeX, resizeY, 20, 20);
                            ctx.fillStyle = '#ffffff';
                            ctx.fillText('⟲', resizeX + 4, resizeY + 14);
                        }
                    }
                });
                
                animationId = requestAnimationFrame(drawUploadOverlay);
            }
        }
        
        drawUploadOverlay();
    }

    postUploadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const file = uploadInput.files[0];
        if (!file) {
            alert('Please select an image to upload.');
            return;
        }
        
        const button = this;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';

        if (stickers.length > 0) {
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            
            const tempCanvas = document.createElement('canvas');
            const tempCtx = tempCanvas.getContext('2d');
            
            const img = new Image();
            img.onload = () => {
                tempCanvas.width = img.width;
                tempCanvas.height = img.height;
                
                tempCtx.drawImage(img, 0, 0);
                
                stickers.forEach(sticker => {
                    if (sticker.img && sticker.img.complete) {
                        tempCtx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
                    }
                });
                
                const compositeDataURL = tempCanvas.toDataURL('image/png');
                sendCompositeImage(compositeDataURL, button);
            };
            img.src = uploadPreview.src;
        } else {
            sendOriginalFile(file, button);
        }
    });

    function sendCompositeImage(dataURL, button) {
        fetch('save_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                image: dataURL,
                make_public: '1'
            })
        })
        .then(response => response.json())
        .then(data => {
            handleUploadResponse(data, button);
        })
        .catch(error => {
            alert('An error occurred during the upload.');
            resetButton(button);
        });
    }
    
    function sendOriginalFile(file, button) {
        const formData = new FormData();
        formData.append('image_file', file);
        formData.append('make_public', '1');

        fetch('save_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            handleUploadResponse(data, button);
        })
        .catch(error => {
            alert('An error occurred during the upload.');
            resetButton(button);
        });
    }
    
    function handleUploadResponse(data, button) {
        if (data.success && data.photo) {
            alert('Photo uploaded and saved successfully!');
            addPhotoToGallery(data.photo);
            uploadInput.value = '';
            uploadPreview.style.display = 'none';
            postUploadBtn.classList.add('d-none');
            stickersSection.classList.add('d-none');
            stickers = [];
            selectedStickerIndex = null;
            const canvas = document.getElementById('canvas');
            canvas.style.display = 'none';
            
            if (animationId) {
                cancelAnimationFrame(animationId);
                animationId = null;
            }
        } else {
            alert('Error saving photo: ' + (data.error || 'Unknown error'));
        }
        
        resetButton(button);
    }
    
    function resetButton(button) {
        button.disabled = false;
        button.innerHTML = '📤 Post Photo';
    }

    document.querySelectorAll('.sticker-thumb').forEach(img => {
        img.addEventListener('click', function() {
            if (isCapturing) return;
            const isWebcamMode = modeWebcamBtn.classList.contains('active');
            const isUploadMode = modeUploadBtn.classList.contains('active') && uploadPreview.style.display === 'block';
            
            
            if (!isWebcamMode && !isUploadMode) {
                alert('Please select webcam mode or upload an image first.');
                return;
            }
            
            const canvas = document.getElementById('canvas');
            const stickerSize = Math.min(canvas.width * 0.15, canvas.height * 0.15);
            
            const sticker = {
                src: this.src, 
                x: canvas.width * 0.1,
                y: canvas.height * 0.1,
                width: stickerSize, 
                height: stickerSize,
                img: new Image()
            };
            
            sticker.img.src = sticker.src;
            sticker.img.onload = () => {
                stickers.push(sticker);
                selectedStickerIndex = stickers.length - 1;
            };
        });
    });

    captureBtn.addEventListener('click', handleCapturePhoto);

    document.getElementById('save-btn').addEventListener('click', function() {
        const dataURL = canvas.toDataURL('image/png');
        fetch('save_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: dataURL })
        })
        .then(response => response.text())
        .then(responseText => {
            try {
                const data = JSON.parse(responseText);
                if (data.success) {
                    alert('Imagem salva com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao salvar imagem: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (parseError) {
                alert('Erro de comunicação com servidor.');
            }
        })
        .catch(error => alert('Erro ao salvar imagem: ' + error.message));
    });

    thumbnailsContainer.addEventListener('click', function(e) {
        const photoContainer = e.target.closest('.position-relative');
        if (photoContainer) {
            const galleryImage = photoContainer.querySelector('.gallery-image');
            if (galleryImage) {
                e.preventDefault();
                selectPhoto(galleryImage.dataset.photoId, galleryImage);
            }
        }
    });


    if (cancelSelectionBtn) {
        cancelSelectionBtn.addEventListener('click', function() {
            document.querySelectorAll('.gallery-image').forEach(gi => gi.classList.remove('selected'));
            selectedPhotoId = null;
            if (selectedPreview) {
                selectedPreview.style.display = 'none';
            }
        });
    }

    if (postPhotoBtn) {
        postPhotoBtn.addEventListener('click', function() {
            if (!selectedPhotoId) {
                alert('Please select a photo first.');
                return;
            }
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch('post_photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ photo_id: selectedPhotoId, csrf_token: csrfToken })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Photo posted successfully to the main gallery!');
                    document.querySelectorAll('.gallery-image').forEach(gi => gi.classList.remove('selected'));
                    selectedPhotoId = null;
                    if (selectedPreview) selectedPreview.style.display = 'none';
                    if (confirm('Would you like to view your post in the main gallery?')) {
                        safeRedirect('index.php');
                    }
                } else {
                    alert('Error posting photo: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => alert('Error posting photo. Please try again.'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '📤 Post to Gallery';
            });
        });
    }

    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function() {
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
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Toggling...';
            fetch('toggle_photo_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ photo_id: selectedPhotoId, csrf_token: csrfToken })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.new_status ? 'Photo is now PUBLIC!' : 'Photo is now PRIVATE!');
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
            .catch(error => alert('Error toggling status. Please try again.'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '🔒 Toggle Public/Private';
            });
        });
    }
    
    switchToUploadMode();
});
    
    function initializeWebcam() {
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Your browser does not support webcam access. Please use the upload mode.');
            switchToUploadMode();
            return;
        }
        
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        })
        .then(stream => { 
            video.srcObject = stream;
            
            video.onloadedmetadata = () => {
                const container = document.getElementById('webcam-container');
                const containerWidth = container.clientWidth - 40;
                const aspectRatio = video.videoWidth / video.videoHeight;
                
                let newWidth = containerWidth;
                let newHeight = containerWidth / aspectRatio;
                
                const maxHeight = 400;
                if (newHeight > maxHeight) {
                    newHeight = maxHeight;
                    newWidth = maxHeight * aspectRatio;
                }
                
                video.width = newWidth;
                video.height = newHeight;
                video.style.width = newWidth + 'px';
                video.style.height = newHeight + 'px';
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.style.width = newWidth + 'px';
                canvas.style.height = newHeight + 'px';
                
                video.play().catch(err => console.error('Error playing video:', err));
                
                if (!canvas.getContext) return;
                const ctx = canvas.getContext('2d');
                if (!ctx) return;
                canvas.ctx = ctx;
                
                setTimeout(() => startVideoOverlay(), 200);
            };
            
            video.onerror = (err) => {
                console.error('Video error:', err);
                alert('Error loading video stream');
            };
            
            webcamInitialized = true;
        })
        .catch(err => { 
            console.error('getUserMedia error:', err);
            let errorMessage = 'Could not access webcam: ';
            switch(err.name) {
                case 'NotAllowedError': errorMessage += 'Permission denied.'; break;
                case 'NotFoundError': errorMessage += 'No camera found.'; break;
                case 'NotReadableError': errorMessage += 'Camera is in use.'; break;
                case 'OverconstrainedError': errorMessage += 'Camera does not support constraints.'; break;
                default: errorMessage += err.message;
            }
            alert(errorMessage);
            switchToUploadMode();
        });
    }

function startVideoOverlay() {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const container = document.getElementById('webcam-container');
    
    if (!canvas.ctx) canvas.ctx = canvas.getContext('2d');
    const ctx = canvas.ctx;
    
    if (video.videoWidth === 0 || video.videoHeight === 0) {
        setTimeout(startVideoOverlay, 100);
        return;
    }
    
    canvas.style.position = 'absolute';
    canvas.style.top = video.offsetTop + 'px';
    canvas.style.left = video.offsetLeft + 'px';
    canvas.style.display = 'block';
    canvas.style.pointerEvents = 'auto';
    canvas.style.zIndex = '10';
    
    function drawOverlay() {
        if (!isCapturing) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            stickers.forEach((sticker, i) => {
                if (sticker.img && sticker.img.complete) {
                    ctx.globalAlpha = 1;
                    ctx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
                    if (i === selectedStickerIndex) {
                        ctx.strokeStyle = '#007bff';
                        ctx.lineWidth = 2;
                        ctx.strokeRect(sticker.x, sticker.y, sticker.width, sticker.height);
                        const deleteX = sticker.x + sticker.width - 10;
                        const deleteY = sticker.y - 10;
                        ctx.fillStyle = '#ff0000';
                        ctx.fillRect(deleteX, deleteY, 20, 20);
                        ctx.fillStyle = '#ffffff';
                        ctx.font = '14px Arial';
                        ctx.fillText('×', deleteX + 6, deleteY + 14);
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

function addPhotoToGallery(photo) {
    const thumbnailsContainer = document.getElementById('thumbnails');
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
                <img src="${photo.file_path}" alt="${photo.filename}" class="img-fluid rounded gallery-image" 
                     style="height: 120px; width: 100%; object-fit: cover; cursor: pointer; transition: all 0.3s;"
                     data-photo-id="${photo.id}" data-is-public="${photo.is_public}" data-was-posted="${photo.was_posted || 0}"
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

function resetCapture() {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('capture-btn');
    const webcamContainer = document.getElementById('webcam-container');
    isCapturing = false;
    stickers = [];
    selectedStickerIndex = null;
    video.style.display = 'block';
    document.getElementById('captured-photo').style.display = 'none';
    document.getElementById('save-btn').style.display = 'none';
    canvas.style.display = 'none';
    captureBtn.textContent = '📷 Capture Photo';
    captureBtn.onclick = handleCapturePhoto;
    if (webcamContainer.style.display !== 'none') {
        setTimeout(() => startVideoOverlay(), 100);
    }
}

function safeRedirect(url) {
    const allowedUrls = ['index.php', './index.php', '/index.php', 'profile.php', './profile.php', '/profile.php'];
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

document.getElementById('canvas').addEventListener('mousedown', function(e) {
    if (isCapturing) return;
    const canvas = this;
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

document.getElementById('canvas').addEventListener('mousemove', function(e) {
    if (!isCapturing) {
        const canvas = this;
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
            resizingSticker.width = Math.max(20, mx - resizingSticker.x);
            resizingSticker.height = Math.max(20, my - resizingSticker.y);
        }
    }
});

document.getElementById('canvas').addEventListener('mouseup', function() {
    draggingSticker = null;
    resizingSticker = null;
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Delete' && selectedStickerIndex !== null && !isCapturing) {
        stickers.splice(selectedStickerIndex, 1);
        selectedStickerIndex = null;
    }
});

function handleCapturePhoto() {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const webcamContainer = document.getElementById('webcam-container');
    
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
        if (sticker.img && sticker.img.complete) {
            tempCtx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
        }
    });
    
    const photoResult = document.getElementById('photo-result');
    photoResult.src = tempCanvas.toDataURL('image/png');
    document.getElementById('captured-photo').style.display = 'block';
    
    canvas.width = tempCanvas.width;
    canvas.height = tempCanvas.height;
    const mainCtx = canvas.getContext('2d');
    mainCtx.drawImage(tempCanvas, 0, 0);
    
    document.getElementById('save-btn').style.display = 'inline-block';
    const captureBtn = document.getElementById('capture-btn');
    captureBtn.textContent = 'Nova Foto';
    captureBtn.onclick = resetCapture;
    
}

function selectPhoto(photoId, imgElement) {
    document.querySelectorAll('.gallery-image').forEach(gi => gi.classList.remove('selected'));
    imgElement.classList.add('selected');
    selectedPhotoId = photoId;
    const selectedPreview = document.getElementById('selected-photo-preview');
    const uploadPreviewContainer = document.querySelector('.upload-preview-container');
    const selectedPreviewImg = document.getElementById('selected-preview-img');
    if (selectedPreviewImg && selectedPreview) {
        selectedPreviewImg.src = imgElement.src;
        selectedPreview.style.display = 'block';
    }
    const togglePublicBtn = document.getElementById('toggle-public-btn');
    if (togglePublicBtn) {
        const wasPosted = imgElement.getAttribute('data-was-posted') === '1';
        togglePublicBtn.style.display = wasPosted ? 'block' : 'none';
    }
}

function deletePhoto(photoId, imgElement) {
    if (!confirm('Are you sure you want to delete this photo? This action cannot be undone.')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('delete_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ photo_id: photoId, csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const photoContainer = imgElement.closest('.col-6');
            photoContainer.remove();
            if (selectedPhotoId == photoId) {
                selectedPhotoId = null;
                const selectedPreview = document.getElementById('selected-photo-preview');
                if (selectedPreview) selectedPreview.style.display = 'none';
            }
            alert('Photo deleted successfully!');
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
                    </div>`;
            }
        } else {
            alert('Error deleting photo: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => alert('Error deleting photo. Please try again.'));
}