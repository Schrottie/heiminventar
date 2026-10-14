/**
 * Drag & Drop Steuerung (Sortable.js für den Verschiebebahnhof)
 */
document.addEventListener('DOMContentLoaded', () => {
    const dropzones = document.querySelectorAll('.item-dropzone');
    const statusBadge = document.getElementById('drag-status-badge');

    if (typeof Sortable !== 'undefined' && dropzones.length > 0) {
        dropzones.forEach(zone => {
            new Sortable(zone, {
                group: 'shared-items',
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'bg-info-subtle',
                delay: 150,
                delayOnTouchOnly: true,
                touchStartThreshold: 5,

                onEnd: function (evt) {
                    const itemEl = evt.item;
                    const newLocationEl = evt.to;
                    const itemId = itemEl.getAttribute('data-item-id');
                    const newLocationId = newLocationEl.getAttribute('data-location-id');
                    const oldLocationId = evt.from.getAttribute('data-location-id');

                    if (oldLocationId === newLocationId) return;

                    if (statusBadge) {
                        statusBadge.className = 'badge bg-warning text-dark';
                        statusBadge.textContent = 'Wird gespeichert...';
                    }

                    fetch('api/change-location.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            item_id: parseInt(itemId, 10),
                            location_id: parseInt(newLocationId, 10)
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (statusBadge) {
                                statusBadge.className = 'badge bg-success';
                                statusBadge.textContent = 'Erfolgreich verschoben';
                                setTimeout(() => {
                                    statusBadge.className = 'badge bg-secondary';
                                    statusBadge.textContent = 'Bereit';
                                }, 2000);
                            }
                        } else {
                            alert('Fehler beim Verschieben: ' + (data.error || 'Unbekannter Fehler'));
                            evt.from.appendChild(itemEl);
                            if (statusBadge) {
                                statusBadge.className = 'badge bg-danger';
                                statusBadge.textContent = 'Fehler';
                            }
                        }
                    })
                    .catch(() => {
                        alert('Speichern fehlgeschlagen. Bitte Verbindung prüfen.');
                        evt.from.appendChild(itemEl);
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-danger';
                            statusBadge.textContent = 'Fehler';
                        }
                    });
                }
            });
        });
    }
});