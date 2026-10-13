/**
 * Inventar-Filter und Artikel-Verwaltung
 */
document.addEventListener('DOMContentLoaded', () => {
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    let lowStockActive = false;

    // Filtert die Inventarliste basierend auf Suchbegriff und Mindestbestand
    function applyFilters() {
        const query = quickFilterInput ? quickFilterInput.value.toLowerCase().trim() : '';
        
        document.querySelectorAll('.inventory-item').forEach(item => {
            const searchData = (item.getAttribute('data-search') || '').toLowerCase();
            const isLowStock = item.getAttribute('data-low-stock') === '1';

            const matchesSearch = query === '' || searchData.includes(query);
            const matchesLowStock = !lowStockActive || isLowStock;

            item.style.display = (matchesSearch && matchesLowStock) ? '' : 'none';
        });
    }

    if (quickFilterInput) {
        quickFilterInput.addEventListener('input', applyFilters);
    }

    if (lowStockBtn) {
        lowStockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            lowStockActive = !lowStockActive;
            lowStockBtn.classList.toggle('btn-danger', lowStockActive);
            lowStockBtn.classList.toggle('btn-outline-danger', !lowStockActive);
            applyFilters();
        });
    }
});

/**
 * Führt die Archivierung eines Gegenstands per AJAX aus
 */
function archiveItem(id) {
    if (!confirm('Möchtest du diesen Gegenstand wirklich archivieren?')) return;
    
    fetch('api/item-archive.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'index.php';
        } else {
            alert('Fehler beim Archivieren: ' + data.error);
        }
    });
}
