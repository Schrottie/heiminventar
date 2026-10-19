<?php
// /inc/import-export/guide.php
?>
<div class="card shadow-sm border-0 bg-body-tertiary">
    <div class="card-body">
        <h5 class="card-title fs-6 fw-bold mb-3">
            <i class="fa-solid fa-circle-info text-info me-2"></i>Leitfaden zum CSV-Format &amp; Funktionsweise
        </h5>
       
        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm bg-white small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Spalte</th>
                        <th>Attribut</th>
                        <th>Pflicht?</th>
                        <th>Beschreibung / Format</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><code>Bezeichnung</code></td>
                        <td><span class="badge bg-danger">Ja</span></td>
                        <td>Der Name des Gegenstands (Dient als Erkennungsmerkmal für Updates).</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><code>Beschreibung</code></td>
                        <td>Nein</td>
                        <td>Freitext zur Beschreibung des Gegenstands.</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><code>Menge</code></td>
                        <td>Nein</td>
                        <td>Ganzzahl (Standard: 0).</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><code>Mindestbestand</code></td>
                        <td>Nein</td>
                        <td>Ganzzahl für Nachbestell-Warnungen (Standard: 0).</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td><code>Preis</code></td>
                        <td>Nein</td>
                        <td>Preis mit Punkt oder Komma (z.B. <code>19.99</code>).</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td><code>Ablaufdatum</code></td>
                        <td>Nein</td>
                        <td>Format: <code>YYYY-MM-DD</code>.</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td><code>Verliehen an</code></td>
                        <td>Nein</td>
                        <td>Name der Person.</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td><code>lagerort_id</code></td>
                        <td>Nein</td>
                        <td>ID des Lagerorts (überschreibt Standard-Lagerort).</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td><code>Lagerort</code></td>
                        <td>Nein</td>
                        <td>Name des Lagerorts (wird automatisch zugeordnet, falls vorhanden).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
