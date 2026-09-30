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