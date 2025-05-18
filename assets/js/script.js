// Bildvorschau bei Hover
document.addEventListener('DOMContentLoaded', function() {
    // Erstelle das Vorschau-Element einmalig
    const tooltip = document.createElement('div');
    tooltip.className = 'preview-tooltip';
    const tooltipImg = document.createElement('img');
    tooltip.appendChild(tooltipImg);
    document.body.appendChild(tooltip);

    // Füge Event-Listener zu allen Vorschau-Containern hinzu
    document.querySelectorAll('.attachment-preview').forEach(preview => {
        const img = preview.querySelector('img');
        if (!img) return;

        preview.addEventListener('mouseenter', function() {
            tooltipImg.src = img.src;
            tooltip.style.display = 'block';
        });

        preview.addEventListener('mousemove', function(e) {
            // Position relativ zum Mauszeiger
            let x = e.clientX + 20;
            let y = e.clientY - 150;
            
            // Stelle sicher, dass die Vorschau im Viewport bleibt
            const maxX = window.innerWidth - tooltip.offsetWidth - 20;
            const maxY = window.innerHeight - tooltip.offsetHeight - 20;
            
            x = Math.min(maxX, Math.max(20, x));
            y = Math.min(maxY, Math.max(20, y));
            
            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
        });

        preview.addEventListener('mouseleave', function() {
            tooltip.style.display = 'none';
        });
    });
}); 