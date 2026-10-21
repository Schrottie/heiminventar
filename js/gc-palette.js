document.addEventListener("DOMContentLoaded", function() {
    const cmdModalEl = document.getElementById('commandPaletteModal');
    if (!cmdModalEl) return;

    const cmdModal = new bootstrap.Modal(cmdModalEl);
    const cmdInput = document.getElementById('cmdPaletteInput');
    const cmdResults = document.getElementById('cmdPaletteResults');

    // Statische System-Befehle
    const systemCommands = [
        { title: 'Neuen Gegenstand anlegen', icon: 'fa-plus text-primary', action: () => window.location.href = 'item.php' },
        { title: 'Lagerorte verwalten', icon: 'fa-folder-tree text-warning', action: () => window.location.href = 'locations.php' },
        { title: 'Dashboard öffnen', icon: 'fa-chart-pie text-info', action: () => window.location.href = 'dashboard.php' },
        { title: 'Verschiebebahnhof (Drag & Drop)', icon: 'fa-shuffle text-success', action: () => window.location.href = 'drag-n-drop.php' },
        { title: 'Druck- & Berichtsgenerator', icon: 'fa-print text-danger', action: () => window.location.href = 'print.php' },
        { title: 'Systemeinstellungen', icon: 'fa-gear text-secondary', action: () => window.location.href = 'settings.php' },
        { title: 'Hilfe & Anleitungen', icon: 'fa-circle-question text-danger', action: () => window.location.href = 'help.php' }
    ];

    // Tastenkombination Strg+K / Cmd+K abfangen
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            cmdModal.show();
        }
    });

    // Nach Öffnen des Modals direkt das Suchfeld fokussieren
    cmdModalEl.addEventListener('shown.bs.modal', function() {
        cmdInput.value = '';
        renderResults(systemCommands);
        cmdInput.focus();
    });

    // Live-Eingabe verarbeiten
    let debounceTimer;
    cmdInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim().toLowerCase();

        if (query === '') {
            renderResults(systemCommands);
            return;
        }

        debounceTimer = setTimeout(() => {
            // Statische Befehle filtern
            const filteredCommands = systemCommands.filter(cmd => cmd.title.toLowerCase().includes(query));

            // Dynamische Suche per AJAX (Gegenstände & Lagerorte)
            fetch('api/gc-palette.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    const combined = [...filteredCommands, ...data];
                    renderResults(combined);
                })
                .catch(() => renderResults(filteredCommands));
        }, 150);
    });

    // Ergebnisse rendern
    function renderResults(items) {
        cmdResults.innerHTML = '';
        if (items.length === 0) {
            cmdResults.innerHTML = '<div class="p-3 text-center text-muted small">Keine Ergebnisse gefunden.</div>';
            return;
        }

        items.forEach((item, index) => {
            const a = document.createElement('a');
            a.href = '#';
            a.className = `list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 mb-1 border-0 ${index === 0 ? 'active-cmd' : ''}`;
            
            const iconClass = item.icon || 'fa-box text-secondary';
            a.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fa-solid ${iconClass} me-3 fs-6 style="width:20px; text-align:center;"></i>
                    <div>
                        <div class="fw-semibold small">${escapeHtml(item.title)}</div>
                        ${item.subtitle ? `<div class="text-muted extra-small">${escapeHtml(item.subtitle)}</div>` : ''}
                    </div>
                </div>
                <span class="badge bg-body-secondary text-muted small">Aktion</span>
            `;

            a.addEventListener('click', (e) => {
                e.preventDefault();
                cmdModal.hide();
                if (item.action) {
                    item.action();
                } else if (item.url) {
                    window.location.href = item.url;
                }
            });

            cmdResults.appendChild(a);
        });
    }

    // Tastatur-Navigation (Pfeiltasten ↑ / ↓ und Enter)
    cmdInput.addEventListener('keydown', function(e) {
        const active = cmdResults.querySelector('.active-cmd');
        if (!active) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const next = active.nextElementSibling;
            if (next && next.classList.contains('list-group-item')) {
                active.classList.remove('active-cmd');
                next.classList.add('active-cmd');
                next.scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prev = active.previousElementSibling;
            if (prev && prev.classList.contains('list-group-item')) {
                active.classList.remove('active-cmd');
                prev.classList.add('active-cmd');
                prev.scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            active.click();
        }
    });

    function escapeHtml(str) {
        return str ? str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';
    }
});
