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

// --- TERMINE FÜR DEN KALENDER SAMMELN ---
$calStart = date('Y-m-01', $monthsToDisplay[0]);
$calEnd = date('Y-m-t', end($monthsToDisplay));
$calendarEvents = [];

try {
    // 1. Verfallsdaten (Link führt direkt zur Artikel-Detailseite)
    $stmtExp = $pdo->prepare("SELECT id, expiration_date AS event_date, name AS event_title FROM inventory_items WHERE is_archived='0' AND expiration_date BETWEEN ? AND ?");
    $stmtExp->execute([$calStart, $calEnd]);
    foreach ($stmtExp->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $calendarEvents[$row['event_date']][] = [
            'type' => 'danger', 
            'text' => 'Verfall: ' . $row['event_title'],
            'url' => 'item.php?id=' . (int)$row['id']
        ];
    }

    // 2. Wartungen (Link führt direkt zur Artikel-Detailseite)
    $stmtMaint = $pdo->query("SELECT id, name, last_maintenance_date, maintenance_interval_months FROM inventory_items WHERE is_archived='0' AND maintenance_interval_months > 0");
    foreach ($stmtMaint->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $baseDate = $item['last_maintenance_date'] ?: date('Y-m-d');
        $nextDate = date('Y-m-d', strtotime($baseDate . ' + ' . $item['maintenance_interval_months'] . ' months'));
        if ($nextDate >= $calStart && $nextDate <= $calEnd) {
            $calendarEvents[$nextDate][] = [
                'type' => 'info', 
                'text' => 'Wartung fällig: ' . $item['name'],
                'url' => 'item.php?id=' . (int)$item['id']
            ];
        }
    }

    // 3. Inventurtermine (Link führt direkt in den Inventur-Modus zum Ort)
    $stmtAud = $pdo->prepare("SELECT s.audit_date, s.title, s.location_id, l.name as loc FROM audit_schedules s LEFT JOIN locations l ON l.id = s.location_id WHERE s.audit_date BETWEEN ? AND ?");
    $stmtAud->execute([$calStart, $calEnd]);
    foreach ($stmtAud->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $auditUrl = 'inventory-audit.php';
        if (!empty($row['location_id'])) {
            $auditUrl .= '?location_id=' . (int)$row['location_id'];
        }
        $calendarEvents[$row['audit_date']][] = [
            'type' => 'primary', 
            'text' => 'Inventur: ' . $row['title'] . ($row['loc'] ? ' (' . $row['loc'] . ')' : ''),
            'url' => $auditUrl
        ];
    }
} catch (Exception $e) {}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
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
                                $hasEvents = !empty($calendarEvents[$currentDateStr]);
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

<!-- Popup-Element mit bg-transparent im Header -->
<div id="calEventPopup" class="card shadow-lg border d-none" style="position: fixed; width: 280px; z-index: 2147483647; font-size: 0.85rem; pointer-events: auto;">
    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-transparent border-bottom">
        <span id="popupDate" class="fw-bold">Datum</span>
        <button type="button" class="btn-close btn-sm" onclick="document.getElementById('calEventPopup').classList.add('d-none');"></button>
    </div>
    <div class="card-body py-2 px-3 bg-body text-body rounded-bottom" id="popupContent">
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
            const rawEvents = dayEl.getAttribute('data-events');
            if (rawEvents) {
                eventsData = JSON.parse(rawEvents);
            }
        } catch(e) {
            return;
        }

        const dateStr = dayEl.getAttribute('data-date');
        if (!dateStr) return;

        const parts = dateStr.split('-');
        const formattedDate = `${parts[2]}.${parts[1]}.${parts[0]}`;

        // Desktop Hover
        dayEl.addEventListener('mouseenter', function() {
            if (isPinned) return;
            showPopup(dayEl, formattedDate, eventsData);
        });

        dayEl.addEventListener('mouseleave', function() {
            if (isPinned) return;
            setTimeout(() => {
                if (!popup.matches(':hover') && !isPinned) {
                    popup.classList.add('d-none');
                }
            }, 100);
        });

        // Klick / Touch
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
            if (!ev || !ev.text) return;
            
            let badgeClass = 'bg-secondary';
            if (ev.type === 'danger') badgeClass = 'bg-danger';
            if (ev.type === 'info') badgeClass = 'bg-info text-dark';
            if (ev.type === 'primary') badgeClass = 'bg-primary';

            // Termine als klickbare Links gestalten, falls eine URL hinterlegt ist
            let contentHtml = ev.text;
            if (ev.url) {
                contentHtml = `<a href="${ev.url}" class="text-decoration-none text-body fw-semibold text-hover-primary">${ev.text} <i class="fa-solid fa-arrow-up-right-from-square ms-1 small text-muted"></i></a>`;
            }

            html += `<li class="mb-2"><span class="badge ${badgeClass} me-1">&bull;</span> ${contentHtml}</li>`;
        });
        html += '</ul>';
        
        if (pinned) {
            html += '<div class="text-end mt-2 pt-1 border-top"><small class="text-muted" style="font-size:0.7rem;">(Erneut tippen zum Schließen)</small></div>';
        }
        popupContent.innerHTML = html;

        popup.classList.remove('d-none');

        const rect = targetEl.getBoundingClientRect();
        let leftPos = rect.left + (rect.width / 2) - 140; 
        
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
.text-hover-primary:hover { color: var(--bs-primary) !important; }
</style>
