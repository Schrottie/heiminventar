/**
 * Link-Ziele für Floating Actions Buttons zentral definieren
 */
const ROUTES = {
    ADD_ITEM: 'edit-item.php',
    LOCATIONS: 'locations.php'
};


document.addEventListener('DOMContentLoaded', () => {

    const filter = document.getElementById('quickFilter');

    if (!filter) return;

    filter.addEventListener('input', () => {

        const search = filter.value.toLowerCase().trim();

        document
            .querySelectorAll('.inventory-item')
            .forEach(item => {

                const content = item.dataset.search;

                item.style.display =
                    content.includes(search)
                        ? ''
                        : 'none';

            });

    });

});

document.addEventListener('DOMContentLoaded', () => {

    const fabContainer = document.getElementById('fabContainer');
    const fabMainBtn = document.getElementById('fabMainBtn');

    if (fabMainBtn) {

        fabMainBtn.addEventListener('click', () => {

            fabContainer.classList.toggle('open');

            const icon = fabMainBtn.querySelector('i');

            if (fabContainer.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }

        });

    }

    document.getElementById('addFabBtn')
        ?.addEventListener('click', () => {
            window.location.href = ROUTES.ADD_ITEM;
        });

    document.getElementById('locationsFabBtn')
        ?.addEventListener('click', () => {
            window.location.href = ROUTES.LOCATIONS;
        });

});

document.querySelectorAll('.location-delete-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        const id = btn.dataset.id;
        const name = btn.dataset.name || 'Dieser Lagerort';
        
        const infoContainer = document.getElementById('deleteLocationInfo');
        const confirmBtn = document.getElementById('confirmDeleteLocation');
        
        // Reset/Lade-Zustand setzen
        document.getElementById('deleteLocationName').textContent = name;
        infoContainer.innerHTML = 'Wird geladen...';
        confirmBtn.style.display = 'inline-block'; // Standardmäßig anzeigen

        try {
            const response = await fetch('api/location-info.php?id=' + id);
            if (!response.ok) throw new Error('Netzwerkfehler');
            const data = await response.json();

            // Prüfen, ob Items vorhanden sind
            if (data.items > 0) {
                // FALL 1: Mindestens 1 Item vorhanden -> Löschen NICHT erlauben
                const cntitemstring = data.items === 1 ? 'Inventargegenstand' : 'Inventargegenstände';
                const cntlocstring = data.locations === 1 ? 'Unterlagerort' : 'Unterlagerorte';
                infoContainer.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        Dieser Lagerort enthält noch <strong>${data.items} ${cntitemstring}</strong> (und ${data.locations} ${cntlocstring}).<br>
                        Er kann nicht gelöscht werden, solange sich Gegenstände darin befinden.
                    </div>`;
                
                // Löschen-Button ausblenden
                confirmBtn.style.display = 'none';
            } else {
                // FALL 2: Keine Items -> Löschen mit Hinweis auf Sublocations erlauben
                let hintText = '';
                if (data.locations > 0) {
                    const cntlocstring = data.locations === 1 ? 'Unterlagerort' : 'Unterlagerorte';
                    const cntdostring = data.locations === 1 ? 'wird' : 'werden';
                    hintText = `<p class="text-danger small mt-2 mb-0"><strong>Achtung:</strong> Es ${cntdostring} auch ${data.locations} ${cntlocstring} gelöscht.</p>`;
                }
                
                infoContainer.innerHTML = `
                    <p class="mb-0">Möchten Sie diesen Lagerort wirklich löschen?</p>
                    ${hintText}
                `;

                // Link für das Löschen setzen
                confirmBtn.href = 'api/location-delete.php?id=' + id;
            }

            // Modal manuell öffnen
            const modal = new bootstrap.Modal(document.getElementById('deleteLocationModal'));
            modal.show();

        } catch (error) {
            console.error('Fehler:', error);
            alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        }
    });
});
