<p class="small text-muted mb-2">
    Über die Schnittstelle können Bestände flexibel gesichert oder massenhaft aktualisiert werden:
</p>
<ul class="small text-muted ps-3 mb-0">
    <li class="mb-1">
        <strong>CSV-Export:</strong> Das gesamte Inventar lässt sich per Mausklick als strukturierte CSV-Datei (kompatibel mit Excel und LibreOffice) im UTF-8-Format herunterladen. Die Datei enthält alle Gegenstandsattribute inklusive der Lagerort-ID und des Lagerortnamens.
    </li>
    <li class="mb-1">
        <strong>Lagerort-Export:</strong> Zur Vorbereitung von umfassenden Importen ist es sinnvoll, die jeweiligen IDs der verschiedenen Lagerorte zu kennen, damit im Anschluß an den Export nicht zu viele nacharbeiten notwendig sind. Dieser Export zeigt die IDs der aktuell verfügbaren Lagerorte.
    </li>
    <li class="mb-1">
        <strong>Automatisierter Import &amp; Updates:</strong> Durch Hochladen einer formatierten CSV-Datei werden neue Gegenstände angelegt oder bestehende Gegenstände anhand ihrer exakten Bezeichnung automatisch aktualisiert.
    </li>
    <li class="mb-1">
        <strong>Standard-Lagerort:</strong> Für den Import kann vorab ein Standard-Lagerort im Formular ausgewählt werden, der automatisch für neue Gegenstände greift, sofern in der CSV keine individuelle Zuordnung erfolgt.
    </li>
    <li class="mb-1">
        <strong>Unterstützte Spalten &amp; Format:</strong> Die CSV-Datei kann mit Semikolon (<code>;</code>) oder Komma (<code>,</code>) getrennt werden. Neben Pflichtfeldern wie der <em>Bezeichnung</em> werden optional auch Angaben zu Beschreibung, Menge, Mindestbestand, Preis, Ablaufdatum, Verleihstatus sowie <code>lagerort_id</code> oder <code>Lagerort</code> unterstützt.
    </li>
    <li>
        <strong>Transaktionssicherheit:</strong> Der Import läuft abgesichert ab. Bei fehlerhaften Datumsformaten oder ungültigen Werten bricht der Vorgang vollständig ab (Rollback), sodass absolut keine Daten beschädigt werden.
    </li>
</ul>