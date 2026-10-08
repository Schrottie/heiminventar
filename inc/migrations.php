<?php
/**
 * Automatische Schema-Migration mit File-Cache Check
 */
function runDatabaseMigrations(PDO $pdo): void
{
    // 1. Definition aller Migrationen
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

        '003_create_auth_system' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('auth_enabled', '0');

            -- Default Admin anlegen (Passwort: admin123)
            INSERT IGNORE INTO users (id, username, password_hash, role, is_active, is_default) 
            VALUES (1, 'admin', '$2y$10$8k9.aX1xN4U5hYV/Xg/3eO5aA1P3n.l7gY8R1f5k8U9z0q2W4e6yS', 'admin', 1, 1);
        ",

        '004_create_image_setting' => "
            INSERT IGNORE INTO settings (setting_key, setting_value) 
            VALUES ('show_images', '1');
        ",

        '005_create_main_image_function' => "
            ALTER TABLE item_images ADD COLUMN is_main TINYINT(1) DEFAULT 0;
        ",

        '006_create_documents_table' => "
            CREATE TABLE IF NOT EXISTS item_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",

        '007_create_min_quantity_function' => "
            ALTER TABLE inventory_items ADD COLUMN min_quantity INT DEFAULT 0 AFTER quantity;
        "
    ];

    // 2. Cache-Check: Wurde das Migration-Array verändert?
    $cacheFile = __DIR__ . '/../cfg/.migration_cache';
    $currentHash = md5(serialize(array_keys($migrations)));

    // Falls die Datei existiert und der Hash übereinstimmt -> SOFORT ABBRECHEN (0ms DB-Last)
    if (file_exists($cacheFile) && file_get_contents($cacheFile) === $currentHash) {
        return;
    }

    // 3. Nur wenn ein Unterschied erkannt wurde, DB-Migration durchführen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $executed = $pdo->query("SELECT migration_name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($migrations as $name => $sql) {
        if (!in_array($name, $executed, true)) {
            try {
                $pdo->exec($sql);
                $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
                $stmt->execute([$name]);
            } catch (PDOException $e) {
                die("Fehler bei Datenbank-Migration '{$name}': " . $e->getMessage());
            }
        }
    }

    // 4. Cache-Datei nach erfolgreicher Migration aktualisieren
    file_put_contents($cacheFile, $currentHash);
}
