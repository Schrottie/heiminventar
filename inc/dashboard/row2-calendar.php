<?php
// 3 Monate generieren: Aktueller Monat + 2 Folgemonate
$monthsToDisplay = [];
for ($i = 0; $i < 3; $i++) {
    $monthsToDisplay[] = strtotime("first day of +" . $i . " month");
}

$germanMonths = [
    1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 
    5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
];
$germanWeekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-body d-flex align-items-center justify-content-between py-3">
        <h5 class="mb-0"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Termin-Übersicht (3 Monate)</h5>
        <span class="text-muted small">Inventur, Verfall &amp; Wartung im Blick</span>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <?php foreach ($monthsToDisplay as $mTimestamp): 
                $year = date('Y', $mTimestamp);
                $monthNum = (int)date('m', $mTimestamp);
                $daysInMonth = (int)date('t', $mTimestamp);
                // Wochentag des 1. Tages (0 = Sonntag, 1 = Montag, ..., 6 = Samstag) -> Anpassen auf Montag-Start (0 = Montag)
                $firstDayOfWeek = (int)date('N', strtotime("$year-$monthNum-01")) - 1;
            ?>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-body-tertiary">
                        <h6 class="text-center fw-bold mb-3"><?= $germanMonths[$monthNum] ?> <?= $year ?></h6>
                        
                        <!-- Wochentage Kopf -->
                        <div class="d-grid text-center fw-semibold text-muted small mb-2" style="grid-template-columns: repeat(7, 1fr);">
                            <?php foreach ($germanWeekdays as $wd): ?>
                                <div><?= $wd ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Kalendertage Grid -->
                        <div class="d-grid gap-1 text-center" style="grid-template-columns: repeat(7, 1fr);">
                            <?php 
                            // Leere Felder für Tage vor dem Monatsstart
                            for ($es = 0; $es < $firstDayOfWeek; $es++): 
                            ?>
                                <div></div>
                            <?php endfor; ?>

                            <?php 
                            for ($day = 1; $day <= $daysInMonth; $day++):
                                $currentDateStr = sprintf('%04d-%02d-%02d', $year, $monthNum, $day);
                                $hasEvents = isset($calendarEvents[$currentDateStr]);
                                $isToday = ($currentDateStr === date('Y-m-d'));
                                
                                // Events für diesen Tag vorbereiten
                                $dayEventsJson = $hasEvents ? htmlspecialchars(json_encode($calendarEvents[$currentDateStr]), ENT_QUOTES, 'UTF-8') : '';
                            ?>
                                <div class="position-relative py-1">
                                    <div class="cal-day d-inline-flex align-items-center justify-content-center rounded-circle 
                                        <?= $isToday ? 'border border-primary fw-bold' : '' ?> 
                                        <?= $hasEvents ? 'bg-primary text-white has-event cursor-pointer shadow-sm' : 'text-secondary' ?>"
                                        style="width: 30px; height: 30px; font-size: 0.85rem;"
                                        <?php if ($hasEvents): ?>
                                            data-date="<?= $currentDateStr ?>"
                                            data-events="<?= $dayEventsJson ?>"
                                        <?php endif; ?>>
                                        <?= $day ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Globales Popover / Infobox Element für den Kalender -->
<div id="calEventPopup" class="card shadow-lg border-0 position-absolute d-none" style="width: 280px; z-index: 1050; font-size: 0.85rem;">
    <div class="card-header bg-dark text-white py-1 px-2 d-flex justify-content-between align-items-center">
        <span id="popupDate" class="fw-bold">Datum</span>
        <button type="button" class="btn-close btn-close-white btn-sm" onclick="closeCalPopup()"></button>
    </div>
    <div class="card-body py-2 px-3" id="popupContent">
        <!-- Inhalt wird dynamisch gefüllt -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('calEventPopup');
    const popupDate = document.getElementById('popupDate');
    const popupContent = document.getElementById('popupContent');
    
    let activePinnedElement = null; // Speichert das aktuell per Klick fixierte Element
    let isPinned = false;

    // Alle Tage mit Terminen auslesen
    const eventDays = document.querySelectorAll('.has-event');

    eventDays.forEach(dayEl => {
        const eventsData = JSON.parse(dayEl.getAttribute('data-events'));
        const dateStr = dayEl.getAttribute('data-date');

        // Formatiertes Datum für die Anzeige (z.B. 24.10.2026)
        const parts = dateStr.split('-');
        const formattedDate = `${parts[2]}.${parts[1]}.${parts[0]}`;

        // 1. Mouseover (Hover) - nur wenn nicht festgeklickt ist
        dayEl.addEventListener('mouseenter', function() {
            if (isPinned) return; // Wenn ein Klick-Popup offen ist, Hover ignorieren
            showPopup(dayEl, formattedDate, eventsData);
        });

        dayEl.addEventListener('mouseleave', function() {
            if (isPinned) return;
            hidePopup();
        });

        // 2. Klick (Toggle / Fixierung)
        dayEl.addEventListener('click', function(e) {
            e.stopPropagation(); // Verhindert sofortiges Schließen durch Dokument-Klick

            if (activePinnedElement === dayEl && isPinned) {
                // Erneuter Klick auf denselben Tag schließt das Popup
                unpinPopup();
            } else {
                // Klick auf einen neuen Tag (oder erster Klick)
                activePinnedElement = dayEl;
                isPinned = true;
                showPopup(dayEl, formattedDate, eventsData, true);
            }
        });
    });

    // Klick außerhalb schließt das fixierte Popup
    document.addEventListener('click', function(e) {
        if (isPinned && !popup.contains(e.target) && !e.target.classList.contains('has-event')) {
            unpinPopup();
        }
    });

    function showPopup(targetEl, dateText, events, pinned = false) {
        popupDate.textContent = dateText;
        
        let html = '<ul class="mb-0 ps-3">';
        events.forEach(ev => {
            let badgeClass = 'bg-secondary';
            if (ev.type === 'danger') badgeClass = 'bg-danger';
            if (ev.type === 'info') badgeClass = 'bg-info text-dark';
            if (ev.type === 'primary') badgeClass = 'bg-primary';

            html += `<li class="mb-1"><span class="badge ${badgeClass} me-1">&bull;</span> ${ev.text}</li>`;
        });
        html += '</ul>';
        if (pinned) {
            html += '<div class="text-end mt-2 pt-1 border-top"><small class="text-muted" style="font-size:0.7rem;">(Erneut klicken zum Schließen)</small></div>';
        }
        popupContent.innerHTML = html;

        // Positionierung über dem Element berechnen
        const rect = targetEl.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        popup.style.top = (rect.top + scrollTop - popup.offsetHeight - 8) + 'px';
        popup.style.left = (rect.left + scrollLeft - (popup.offsetWidth / 2) + (rect.width / 2)) + 'px';
        
        popup.classList.remove('d-none');
    }

    function hidePopup() {
        if (!isPinned) {
            popup.classList.add('d-none');
        }
    }

    window.closeCalPopup = function() {
        unpinPopup();
    }

    function unpinPopup() {
        isPinned = false;
        activePinnedElement = null;
        popup.classList.add('d-none');
    }
});
</script>
<style>
.cursor-pointer { cursor: pointer; }
.has-event:hover { opacity: 0.85; transform: scale(1.1); transition: all 0.15s ease-in-out; }
</style>
