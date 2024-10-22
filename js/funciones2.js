// funciones.js
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.preview-link');
    const previewCache = new Map();
    
    async function fetchPreview(url) {
        if (previewCache.has(url)) {
            return previewCache.get(url);
        }
        
        try {
            const response = await fetch(`preview2.php?url=${encodeURIComponent(url)}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text(); // Obtener el texto primero
            
            try {
                const data = JSON.parse(text); // Intentar parsear el JSON
                if (data.success === false) {
                    console.error('Error en el servidor:', data.error);
                    throw new Error(data.error);
                }
                previewCache.set(url, data);
                return data;
            } catch (parseError) {
                console.error('Error al parsear JSON:', text); // Mostrar la respuesta cruda
                throw new Error('Error al parsear la respuesta del servidor');
            }
        } catch (error) {
            console.error('Error completo:', error);
            return {
                title: url,
                description: 'No se pudo cargar la previsualización',
                image: null,
                favicon: null,
                error: error.message
            };
        }
    }
    
    links.forEach(link => {
        const preview = document.createElement('div');
        preview.className = 'preview-container';
        link.appendChild(preview);
        
        let fetchTimeout;
        
        link.addEventListener('mouseenter', () => {
            preview.style.display = 'block';
            preview.innerHTML = '<div class="preview-loading">Cargando...</div>';
            
            fetchTimeout = setTimeout(async () => {
                const metadata = await fetchPreview(link.href);
                if (metadata) {
                    if (metadata.error) {
                        preview.innerHTML = `
                            <div class="preview-content">
                                <div class="preview-info">
                                    <p class="preview-title">Error</p>
                                    <p class="preview-description">${metadata.error}</p>
                                </div>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = `
                            <div class="preview-content">
                                ${metadata.image ? 
                                  `<img class="preview-image" src="${metadata.image}" alt="Preview" onerror="this.style.display='none'">` : 
                                  ''}
                                <div class="preview-info">
                                    <p class="preview-title">
                                        ${metadata.favicon ? 
                                          `<img class="site-icon" src="${metadata.favicon}" alt="Site icon" onerror="this.style.display='none'">` : 
                                          ''}
                                        ${metadata.title}
                                    </p>
                                    <p class="preview-description">${metadata.description || ''}</p>
                                </div>
                            </div>
                        `;
                    }
                }
            }, 300);
        });
        
        link.addEventListener('mouseleave', () => {
            preview.style.display = 'none';
            clearTimeout(fetchTimeout);
        });
    });
});
