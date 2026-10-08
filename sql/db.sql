CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    name VARCHAR(255) NOT NULL,
    CONSTRAINT fk_locations_parent
    FOREIGN KEY (parent_id)
    REFERENCES locations(id)
    ON DELETE CASCADE
);

CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_location
    FOREIGN KEY(location_id)
    REFERENCES locations(id)
);

CREATE TABLE item_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------
   INSERT CONFIG & SAMPLE DATA
   --------------------------- */

-- Standard ist helles Theme
INSERT IGNORE INTO settings (setting_key, setting_value) 
VALUES ('theme', 'light');

-- Standard ist private Nutzung, keine Anmeldung erforderlich!
INSERT IGNORE INTO settings (setting_key, setting_value) 
VALUES ('auth_enabled', '0');

-- Default Admin anlegen (Passwort: admin123)
INSERT IGNORE INTO users (id, username, password_hash, role, is_active, is_default) 
VALUES (1, 'admin', '$2y$10$8k9.aX1xN4U5hYV/Xg/3eO5aA1P3n.l7gY8R1f5k8U9z0q2W4e6yS', 'admin', 1, 1);

-- Beispiellagerort anlegen
INSERT INTO `locations` (`id`, `parent_id`, `name`) VALUES
(1, NULL, 'Keller');

-- Beispielgegenstand anlegen
INSERT INTO `inventory_items` (`id`, `name`, `description`, `location_id`, `quantity`, `created`) VALUES
(1, 'Kinderfahrrad', 'Gelbes Puky', 1, 1, '2026-09-30 04:42:03');