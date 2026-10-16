/**
 * Zentrale Skripte: Allgemeine UI-Interaktionen & Release-Verwaltung (Markdown)
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

    // 3. Marked.js Grundeinstellungen initialisieren (falls geladen)
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,
            gfm: true
        });
    }

    // 4. Automatisches Release-Ankündigungs-Modal initialisieren & parsen
    const autoContent = document.getElementById('autoReleaseContent');
    const autoModalEl = document.getElementById('autoReleaseModal');
    
    if (autoContent) {
        // Zuerst prüfen, ob ein globales window.unseenReleaseContent existiert, sonst innerHTML nutzen
        const rawContent = (typeof window.unseenReleaseContent !== 'undefined' && window.unseenReleaseContent) 
                           ? window.unseenReleaseContent 
                           : autoContent.innerHTML;

        if (rawContent && rawContent.trim() !== '') {
            const cleanedContent = rawContent.split('\n').map(line => line.trim()).join('\n');
            if (typeof marked !== 'undefined') {
                autoContent.innerHTML = marked.parse(cleanedContent);
            } else {
                autoContent.innerText = cleanedContent;
            }
        }

        // Automatisches Modal beim Start öffnen
        if (autoModalEl && typeof bootstrap !== 'undefined') {
            const autoModal = new bootstrap.Modal(autoModalEl);
            autoModal.show();
        }
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
 * Öffnet das Detail-Modal für Release-Notes mit Markdown-Parsing (Manueller Aufruf)
 */
function viewReleaseModal(version, title, content, date) {
    const modalTitleVer = document.getElementById('releaseModalVersion');
    const modalTitle = document.getElementById('releaseModalTitle');
    const modalDate = document.getElementById('releaseModalDate');
    const modalContent = document.getElementById('releaseModalContent');

    if (modalTitleVer) modalTitleVer.innerText = `✨ Release v${version}`;
    if (modalTitle) modalTitle.innerText = title;
    if (modalDate && date) modalDate.innerHTML = `<i class="fa-solid fa-calendar-days me-1"></i> Veröffentlicht am ${date}`;
   
    if (modalContent) {
        // Führende Einrückungen bereinigen, damit Marked.js saubere Listen baut
        const cleanedContent = content.split('\n').map(line => line.trim()).join('\n');
       
        if (typeof marked !== 'undefined') {
            modalContent.innerHTML = marked.parse(cleanedContent);
        } else {
            modalContent.innerText = cleanedContent;
        }
    }

    const modalEl = document.getElementById('releaseDetailModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Schaltet auf der Übersichtsseite zwischen Lagerort und Pfad um
 */
function toggleLocationPath(element, event) {
    event.preventDefault();
    const span = element.querySelector('.location-text');
    const shortText = element.getAttribute('data-short');
    const fullText = element.getAttribute('data-full');

    // Wenn der kurze Text da ist, auf den vollen Pfad wechseln – sonst zurück
    if (span.textContent === shortText) {
        span.textContent = fullText;
    } else {
        span.textContent = shortText;
    }
}
