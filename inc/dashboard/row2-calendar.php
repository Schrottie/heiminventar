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
        <h5 class="mb-0"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Termin-Übersicht</h5>
        <span class="text-muted small d-none d-sm-inline">Inventur, Verfall &amp; Wartung im Blick</span>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <?php foreach ($monthsToDisplay as $index => $mTimestamp): 
                $year = date('Y', $mTimestamp);
                $monthNum = (int)date('m', $mTimestamp);
                $daysInMonth = (int)date('t', $mTimestamp);
                $firstDayOfWeek = (int)date('N', strtotime("$year-$monthNum-01")) - 1;
                
                // Sichtbarkeit: Index 0 & 1 immer, Index 2 nur auf Desktop (xl)
                $visibilityClass = 'col-12 col-md-6 col-xl-4';
                if ($index === 2) {
                    $visibilityClass = 'col-12 col-md-6 col-xl-4 d-none d-xl-block';
                }
            ?>
                <div class="<?= $visibilityClass ?>">
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
                            for ($es = 0; $es < $firstDayOfWeek; $es++): 
                            ?>
                                <div></div>
                            <?php endfor; ?>

                            <?php 
                            for ($day = 1; $day <= $daysInMonth; $day++):
                                $currentDateStr = sprintf('%04d-%02d-%02d', $year, $monthNum, $day);
                                $hasEvents = isset($calendarEvents[$currentDateStr]);
                                $isToday = ($currentDateStr === date('Y-m-d'));
                                
                                $dayEventsJson = $hasEvents ? htmlspecialchars(json_encode($calendarEvents[$currentDateStr]), ENT_QUOTES, 'UTF-8') : '';
                            ?>
                                <div class="position-relative py-1 d-flex justify-content-center">
                                    <div class="cal-day d-inline-flex align-items-center justify-content-center rounded-circle 
                                        <?= $isToday ? 'border border-primary fw-bold' : '' ?> 
                                        <?= $hasEvents ? 'bg-primary text-white has-event cursor-pointer shadow-sm' : 'text-secondary' ?>"
                                        style="width: 32px; height: 32px; font-size: 0.85rem;"
                                        <?php if ($hasEvents): ?>
                                            data-date="<?= $currentDateStr ?>"
                                            data-events='<?= $dayEventsJson ?>'
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

<!-- Direkt integriertes Popup-Element mit hoher z-index und absolutem Schutz vor Verdeckung -->
<div id="calEventPopup" class="card shadow-lg border-0 d-none" style="position: fixed; width: 280px; z-index: 2147483647; font-size: 0.85rem; pointer-events: auto;">
    <div class="card-header bg-dark text-white py-1 px-2 d-flex justify-content-between align-items-center">
        <span id="popupDate" class="fw-bold">Datum</span>
        <button type="button" class="btn-close btn-close-white btn-sm" onclick="document.getElementById('calEventPopup').classList.add('d-none');"></button>
    </div>
    <div class="card-body py-2 px-3 bg-body rounded-bottom" id="popupContent">
        <!-- Inhalt -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('calEventPopup');
    const popupDate = document.getElementById('popupDate');
    const popupContent = document.getElementById('popupContent');
    
    let activePinnedElement = null;
    let isPinned = false;

    const eventDays = document.querySelectorAll('.has-event');

    eventDays.forEach(dayEl => {
        let eventsData = [];
        try {
            eventsData = JSON.parse(dayEl.getAttribute('data-events'));
        } catch(e) {
            return;
        }

        const dateStr = dayEl.getAttribute('data-date');
        const parts = dateStr.split('-');
        const formattedDate = `${parts[2]}.${parts[1]}.${parts[0]}`;

        // Desktop Hover
        dayEl.addEventListener('mouseenter', function() {
            if (isPinned) return;
            showPopup(dayEl, formattedDate, eventsData);
        });

        dayEl.addEventListener('mouseleave', function() {
            if (isPinned) return;
            // Kurze Verzögerung, damit man ins Popup fahren kann
            setTimeout(() => {
                if (!popup.matches(':hover') && !isPinned) {
                    popup.classList.add('d-none');
                }
            }, 100);
        });

        // Klick / Touch für Mobil & Tablet
        dayEl.addEventListener('click', function(e) {
            e.stopPropagation(); 

            if (activePinnedElement === dayEl && isPinned) {
                popup.classList.add('d-none');
                isPinned = false;
                activePinnedElement = null;
            } else {
                activePinnedElement = dayEl;
                isPinned = true;
                showPopup(dayEl, formattedDate, eventsData, true);
            }
        });
    });

    // Klick außerhalb schließt das Popup
    document.addEventListener('click', function(e) {
        if (isPinned && !popup.contains(e.target) && !e.target.classList.contains('has-event')) {
            popup.classList.add('d-none');
            isPinned = false;
            activePinnedElement = null;
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
            html += '<div class="text-end mt-2 pt-1 border-top"><small class="text-muted" style="font-size:0.7rem;">(Erneut tippen zum Schließen)</small></div>';
        }
        popupContent.innerHTML = html;

        // Einmal kurz anzeigen, damit Offset-Größen berechnet werden können
        popup.classList.remove('d-none');

        const rect = targetEl.getBoundingClientRect();
        let leftPos = rect.left + (rect.width / 2) - 140; // 140 = 280px / 2
        
        if (leftPos < 10) leftPos = 10;
        if (leftPos + 280 > window.innerWidth - 10) {
            leftPos = window.innerWidth - 290;
        }

        let topPos = rect.top - popup.offsetHeight - 8;
        if (topPos < 10) {
            topPos = rect.bottom + 8;
        }

        popup.style.top = topPos + 'px';
        popup.style.left = leftPos + 'px';
    }
});
</script>
<style>
.cursor-pointer { cursor: pointer; }
.has-event:hover { opacity: 0.85; transform: scale(1.1); transition: all 0.15s ease-in-out; }
</style>
