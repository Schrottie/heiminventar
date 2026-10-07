<?php
/**
 * Automatische Schema-Migration für das Inventarsystem
 */

function runDatabaseMigrations(PDO $pdo): void
{
    // 1. Sicherheitstabelle für Migrationsstand erstellen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Bereits ausgeführte Migrationen laden
    $executed = $pdo->query("SELECT migration_name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

    // 2. Definition aller Datenbank-Migrationen (erweiterbar für die Zukunft)
    $migrations = [
        '001_create_base_tables' => "
            CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT NULL,
                name VARCHAR(255) NOT NULL,
                CONSTRAINT fk_locations_parent
                FOREIGN KEY (parent_id) REFERENCES locations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS inventory_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                location_id INT NULL,
                quantity INT NOT NULL DEFAULT 1,
                created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_inventory_location
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS item_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                CONSTRAINT fk_images_item
                FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",

        '002_create_settings_table' => "
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            INSERT IGNORE INTO settings (setting_key, setting_value) 
            VALUES ('theme', 'light');
        ",

        // Hier kann später '003_create_categories_table' usw. einfach angefügt werden!
    ];

    // 3. Migrationen der Reihe nach ausführen
    foreach ($migrations as $name => $sql) {
        if (!in_array($name, $executed, true)) {
            try {
                $pdo->exec($sql);
                $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
                $stmt->execute([$name]);
            } catch (PDOException $e) {
                // Bei Fehlern Migration abbrechen
                die("Fehler bei Datenbank-Migration '{$name}': " . $e->getMessage());
            }
        }
    }
}
