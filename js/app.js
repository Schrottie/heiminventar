/**
 * Zentrale Anwendungs-Initialisierung und globale Shortcuts
 */
document.addEventListener('DOMContentLoaded', () => {
    // Hilfeseite mit F1 aufrufen
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F1') {
            e.preventDefault();
            window.location.href = 'help.php';
        }
    });
});

// Header-Verkleinerung beim Scrollen
const hero = document.getElementById("heroHeader");
let isCollapsed = false;

window.addEventListener("scroll", () => {
    if (!hero) return;
    const scrollY = window.scrollY;

    if (!isCollapsed && scrollY > 80) {
        hero.classList.add("hero-small");
        isCollapsed = true;
    } else if (isCollapsed && scrollY < 40) {
        hero.classList.remove("hero-small");
        isCollapsed = false;
    }
});

// Bootstrap Tooltips aktivieren (für den Tag-Hilfetext)
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});

// Meldung des Tages schließen
function hideDailyNotice() {
    const banner = document.getElementById('dailyNoticeBanner');
    if (banner) banner.style.display = 'none';
    // Cookie für den aktuellen Tag setzen (gilt bis Mitternacht)
    const tomorrow = new Date();
    tomorrow.setHours(23, 59, 59, 999);
    document.cookie = "hide_daily_notice=" + new Date().toISOString().slice(0,10) + "; path=/; expires=" + tomorrow.toUTCString();
}

// Such- bzw. Fiulterzustand der Übersicht sichern und wiederherstellen
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.querySelector('input[name="q"], #quickFilter');
    
    // 1. Suchbegriff wiederherstellen (falls vorhanden und Feld leer ist)
    if (searchInput) {
        // Prüfen, ob URL gerade keinen Suchparameter hat, aber im Storage noch einer liegt
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('q') && localStorage.getItem('inventory_last_search')) {
            const savedQuery = localStorage.getItem('inventory_last_search');
            searchInput.value = savedQuery;
            
            // Wenn es das Schnellfilter-Feld ist, direkt auslösen
            if (searchInput.id === 'quickFilter' && typeof filterTable === 'function') {
                filterTable();
            }
        }

        // Bei Eingabe den Suchbegriff im Storage merken
        searchInput.addEventListener('input', function() {
            localStorage.setItem('inventory_last_search', this.value);
        });

        // Beim Absenden der Suche ebenfalls sichern
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function() {
                localStorage.setItem('inventory_last_search', searchInput.value);
            });
        }
    }

    // 2. Filter-Tags oder Ansichts-Modus im Storage halten
    const currentMode = new URLSearchParams(window.location.search).get('mode');
    if (currentMode) {
        localStorage.setItem('inventory_last_mode', currentMode);
    }
});
