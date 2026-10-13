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
