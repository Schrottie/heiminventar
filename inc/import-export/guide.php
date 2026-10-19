<?php
// /inc/import-export/guide.php
?>
<div class="card shadow-sm border-0 bg-body-tertiary">
    <div class="card-body">
        <h5 class="card-title fs-6 fw-bold mb-3">
            <i class="fa-solid fa-circle-info text-info me-2"></i>Leitfaden zum erweiterten CSV-Format
        </h5>
        <p class="small text-muted mb-3">
            Das Format ist vollständig kompatibel mit der Export-Datei. Sie können exportierte CSV-Dateien direkt bearbeiten, neue Gegenstände oder Anbau-/Zubehörteile hinzufügen und wieder importieren.
        </p>
       
        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm bg-white small mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">Nr.</th>
                        <th>Spaltenbezeichnung</th>
                        <th style="width: 80px;">Pflicht?</th>
                        <th>Beschreibung / Format</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td><code>Typ</code></td>
                        <td><span class="badge bg-danger">Ja</span></td>
                        <td><code>Hauptartikel</code> oder <code>Anbau-/Zubehörteil</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td><code>ID</code></td>
                        <td>Nein</td>
                        <td>Interne ID. Für neue Gegenstände leer lassen.</td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td><code>Hauptartikel-ID</code></td>
                        <td>Teils</td>
                        <td>Bei Zubehörteilen erforderlich: Verweist auf die <code>ID</code> (Spalte 2) des Hauptartikels.</td>
                    </tr>
                    <tr>
                        <td class="text-center">4</td>
                        <td><code>Name</code></td>
                        <td><span class="badge bg-danger">Ja</span></td>
                        <td>Bezeichnung des Artikels oder Zubehörs (Baum-Zeichen wie <code>└─</code> werden automatisch bereinigt).</td>
                    </tr>
                    <tr>
                        <td class="text-center">5</td>
                        <td><code>Beschreibung</code></td>
                        <td>Nein</td>
                        <td>Freitext zur Beschreibung des Gegenstands.</td>
                    </tr>
                    <tr>
                        <td class="text-center">6</td>
                        <td><code>Hersteller</code></td>
                        <td>Nein</td>
                        <td>Name des Herstellers/Marke.</td>
                    </tr>
                    <tr>
                        <td class="text-center">7</td>
                        <td><code>Lagerort</code></td>
                        <td>Nein</td>
                        <td>Name des Lagerorts (wird automatisch zugeordnet; sonst greift der Standard-Lagerort).</td>
                    </tr>
                    <tr>
                        <td class="text-center">8</td>
                        <td><code>Menge</code></td>
                        <td>Nein</td>
                        <td>Anzahl (Standard: 1).</td>
                    </tr>
                    <tr>
                        <td class="text-center">9</td>
                        <td><code>Verliehen</code></td>
                        <td>Nein</td>
                        <td>Anzahl der aktuell verliehenen Exemplare.</td>
                    </tr>
                    <tr>
                        <td class="text-center">10</td>
                        <td><code>Einzelpreis (€)</code></td>
                        <td>Nein</td>
                        <td>Preis mit Komma oder Punkt (z.B. <code>19,99</code>).</td>
                    </tr>
                    <tr>
                        <td class="text-center">11</td>
                        <td><code>Gesamtpreis (€)</code></td>
                        <td>Nein</td>
                        <td>Wird beim Import ignoriert und automatisch berechnet.</td>
                    </tr>
                    <tr>
                        <td class="text-center">12</td>
                        <td><code>Kaufdatum</code></td>
                        <td>Nein</td>
                        <td>Format: <code>YYYY-MM-DD</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center">13</td>
                        <td><code>Kaufort / Händler</code></td>
                        <td>Nein</td>
                        <td>Name des Händlers oder Shops.</td>
                    </tr>
                    <tr>
                        <td class="text-center">14</td>
                        <td><code>Versichert</code></td>
                        <td>Nein</td>
                        <td><code>Ja</code> oder <code>Nein</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center">15</td>
                        <td><code>Ist Set</code></td>
                        <td>Nein</td>
                        <td><code>Ja</code> oder <code>Nein</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center">16</td>
                        <td><code>Ablaufdatum</code></td>
                        <td>Nein</td>
                        <td>Format: <code>YYYY-MM-DD</code>.</td>
                    </tr>
                    <tr>
                        <td class="text-center">17</td>
                        <td><code>Tags</code></td>
                        <td>Nein</td>
                        <td>Durch Komma getrennte Schlagwörter (z.B. <code>Werkzeug, Elektro</code>).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
