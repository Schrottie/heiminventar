/**
 * Allgemeine UI-Interaktionen (Theme-Switch, Benutzer-Modals)
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Toggle (Dark / Light Mode)
    const themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const body = document.body;
            const icon = themeBtn.querySelector('i');
            
            body.classList.toggle('theme-dark');
            const isDark = body.classList.contains('theme-dark');

            if (icon) {
                icon.classList.toggle('fa-moon', !isDark);
                icon.classList.toggle('fa-sun', isDark);
            }

            document.cookie = "theme=" + (isDark ? "dark" : "light") + ";path=/;max-age=31536000";
        });
    }

    // 2. Benutzer bearbeiten Modal (Datenübertragung)
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            document.getElementById('modalUserId').value = button.getAttribute('data-userid');
            document.getElementById('modalUsername').value = button.getAttribute('data-username');
            document.getElementById('modalRole').value = button.getAttribute('data-role');
        });
    }
});

/**
 * Markdown-Parsing und automatisches Anzeigen des Release-Modals
 */
document.addEventListener('DOMContentLoaded', function() {
    // Falls das globale Release-Objekt im Template definiert wurde
    const rawContent = typeof window.unseenReleaseContent !== 'undefined' ? window.unseenReleaseContent : '';
    const contentContainer = document.getElementById('autoReleaseContent');

    if (!rawContent || !contentContainer) return;

    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,
            gfm: true
        });
        contentContainer.innerHTML = marked.parse(rawContent);
    } else {
        contentContainer.innerText = rawContent;
    }

    const modalEl = document.getElementById('autoReleaseModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const autoModal = new bootstrap.Modal(modalEl);
        autoModal.show();
    }
});

/**
 * Markiert ein Release als gesehen via API
 */
function markReleaseAsSeen(version) {
    fetch('api/mark-release-seen.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ version: version })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('API-Fehler beim Markieren des Releases:', data.error);
        }
    })
    .catch(err => console.error('Fehler beim Aktualisieren:', err));
}

/**
 * Öffnet das Detail-Modal für Release-Notes mit Markdown-Parsing
 */
function viewReleaseModal(version, title, rawContent) {
    document.getElementById('releaseModalVersion').innerText = '✨ Version ' + version;
    document.getElementById('releaseModalTitle').innerText = title;
   
    const contentEl = document.getElementById('releaseModalContent');
    if (!contentEl) return;

    if (typeof marked !== 'undefined') {
        contentEl.innerHTML = marked.parse(rawContent);
    } else {
        contentEl.innerText = rawContent;
    }

    const modalEl = document.getElementById('releaseDetailModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
