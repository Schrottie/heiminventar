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

    bezeichnung VARCHAR(255) NOT NULL,
    beschreibung TEXT NULL,

    location_id INT NULL,

    menge INT NOT NULL DEFAULT 1,

    erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_location
    FOREIGN KEY(location_id)
    REFERENCES locations(id)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255)
);

CREATE TABLE item_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL
);