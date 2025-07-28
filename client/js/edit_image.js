let stickers = [];
let draggingSticker = null;
let resizingSticker = null;
let offsetX = 0, offsetY = 0;
let selectedStickerIndex = null;
let isCapturing = false;
let animationId = null;

const video = document.getElementById('webcam');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

// Inicializa webcam
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => { 
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            // Canvas invisível inicialmente - só desenha sobre o vídeo
            startVideoOverlay();
        };
    })
    .catch(err => { alert('Could not access webcam'); });

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

// Canvas overlay invisível para mostrar stickers sobre o vídeo
function startVideoOverlay() {
    // Posiciona o canvas exatamente sobre o vídeo
    canvas.style.position = 'absolute';
    canvas.style.top = video.offsetTop + 'px';
    canvas.style.left = video.offsetLeft + 'px';
    canvas.style.display = 'block';
    canvas.style.pointerEvents = 'auto';
    
    function drawOverlay() {
        if (!isCapturing) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            stickers.forEach((sticker, i) => {
                ctx.globalAlpha = 1;
                ctx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
                
                // Se estiver selecionado, desenha controles
                if (i === selectedStickerIndex) {
                    // Borda de seleção
                    ctx.strokeStyle = '#007bff';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(sticker.x, sticker.y, sticker.width, sticker.height);
                    
                    // Botão X para deletar
                    const deleteX = sticker.x + sticker.width - 10;
                    const deleteY = sticker.y - 10;
                    ctx.fillStyle = '#ff0000';
                    ctx.fillRect(deleteX, deleteY, 20, 20);
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '14px Arial';
                    ctx.fillText('×', deleteX + 6, deleteY + 14);
                    
                    // Handle de redimensionamento
                    const resizeX = sticker.x + sticker.width - 10;
                    const resizeY = sticker.y + sticker.height - 10;
                    ctx.fillStyle = '#007bff';
                    ctx.fillRect(resizeX, resizeY, 20, 20);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillText('⟲', resizeX + 4, resizeY + 14);
                }
            });
            
            animationId = requestAnimationFrame(drawOverlay);
        }
    }
    drawOverlay();
}

// Função para verificar se clicou no botão X
function isClickOnDelete(mx, my, sticker) {
    const deleteX = sticker.x + sticker.width - 10;
    const deleteY = sticker.y - 10;
    return mx >= deleteX && mx <= deleteX + 20 && my >= deleteY && my <= deleteY + 20;
}

// Função para verificar se clicou no handle de resize
function isClickOnResize(mx, my, sticker) {
    const resizeX = sticker.x + sticker.width - 10;
    const resizeY = sticker.y + sticker.height - 10;
    return mx >= resizeX && mx <= resizeX + 20 && my >= resizeY && my <= resizeY + 20;
}

// Mouse events
canvas.addEventListener('mousedown', function(e) {
    if (isCapturing) return;
    
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;
    
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
        const mx = e.clientX - rect.left;
        const my = e.clientY - rect.top;
        
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

// Remover sticker com Delete
window.addEventListener('keydown', function(e) {
    if (e.key === 'Delete' && selectedStickerIndex !== null && !isCapturing) {
        stickers.splice(selectedStickerIndex, 1);
        selectedStickerIndex = null;
    }
});

// Capturar foto
document.getElementById('capture-btn').onclick = function() {
    isCapturing = true;
    
    if (animationId) {
        cancelAnimationFrame(animationId);
    }
    
    // Esconde o vídeo e canvas overlay
    video.style.display = 'none';
    canvas.style.display = 'none';
    
    // Cria canvas temporário para capturar
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = 480;
    tempCanvas.height = 360;
    const tempCtx = tempCanvas.getContext('2d');
    
    // Desenha vídeo + stickers
    tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);
    stickers.forEach(sticker => {
        tempCtx.drawImage(sticker.img, sticker.x, sticker.y, sticker.width, sticker.height);
    });
    
    // Mostra a foto capturada
    const photoResult = document.getElementById('photo-result');
    photoResult.src = tempCanvas.toDataURL('image/png');
    document.getElementById('captured-photo').style.display = 'block';
    
    // Salva a imagem no canvas principal para salvar depois
    canvas.width = 480;
    canvas.height = 360;
    ctx.drawImage(tempCanvas, 0, 0);
    
    document.getElementById('save-btn').style.display = 'inline-block';
    this.textContent = 'Nova Foto';
    this.onclick = resetCapture;
};

// Resetar para nova captura
function resetCapture() {
    isCapturing = false;
    stickers = [];
    selectedStickerIndex = null;
    
    // Mostra vídeo novamente
    video.style.display = 'block';
    document.getElementById('captured-photo').style.display = 'none';
    document.getElementById('save-btn').style.display = 'none';
    
    document.getElementById('capture-btn').textContent = 'Capture Photo';
    document.getElementById('capture-btn').onclick = arguments.callee;
    
    // Reinicia overlay
    startVideoOverlay();
}

// Salvar imagem
document.getElementById('save-btn').onclick = function() {
    const dataURL = canvas.toDataURL('image/png');
    fetch('save_image.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: dataURL })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Imagem salva com sucesso!');
            location.reload();
        } else {
            alert('Erro ao salvar imagem.');
        }
    })
    .catch(() => alert('Erro ao salvar imagem.'));
};