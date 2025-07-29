-- Fishing Store Database Schema
-- Drop existing database if exists
DROP DATABASE IF EXISTS fishing_store;
CREATE DATABASE fishing_store;
USE fishing_store;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    category_id INT,
    image VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT
);

-- Insert default admin user
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@fishingstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');
-- Password is 'password'

-- Insert sample categories
INSERT INTO categories (name, description, image) VALUES 
('Fishing Rods', 'High-quality fishing rods for all skill levels', 'category_rods.jpg'),
('Reels', 'Durable and smooth fishing reels', 'category_reels.jpg'),
('Lures & Baits', 'Effective lures and baits for all fish types', 'category_lures.jpg'),
('Lines & Leaders', 'Strong and reliable fishing lines', 'category_lines.jpg'),
('Tackle Boxes', 'Organized storage for your fishing gear', 'category_boxes.jpg'),
('Fishing Accessories', 'Essential accessories for fishing trips', 'category_accessories.jpg');

-- Insert sample products
INSERT INTO products (name, description, price, stock, category_id, image, featured) VALUES 
-- Fishing Rods
('Professional Bass Rod 7ft', 'High-performance bass fishing rod with sensitive tip and strong backbone', 89.99, 25, 1, 'rod_bass_pro.jpg', TRUE),
('Lightweight Trout Rod 6ft', 'Perfect for stream and lake trout fishing', 45.99, 30, 1, 'rod_trout_light.jpg', FALSE),
('Heavy Duty Saltwater Rod 8ft', 'Built for big saltwater fish and tough conditions', 129.99, 15, 1, 'rod_saltwater_heavy.jpg', TRUE),

-- Reels
('Spinning Reel 3000 Series', 'Smooth spinning reel with 5+1 ball bearings', 79.99, 40, 2, 'reel_spinning_3000.jpg', TRUE),
('Baitcasting Reel Pro', 'Professional baitcasting reel for advanced anglers', 149.99, 20, 2, 'reel_baitcast_pro.jpg', FALSE),
('Fly Fishing Reel 5/6wt', 'Lightweight fly reel for 5-6 weight lines', 99.99, 25, 2, 'reel_fly_56.jpg', TRUE),

-- Lures & Baits
('Crankbait Set (5 pieces)', 'Assorted crankbaits for various depths', 24.99, 50, 3, 'lure_crankbait_set.jpg', TRUE),
('Soft Plastic Worms', 'Realistic soft plastic worms - 20 pack', 12.99, 60, 3, 'lure_soft_worms.jpg', FALSE),
('Spinnerbait Gold/Silver', 'Flashy spinnerbait for bass and pike', 8.99, 80, 3, 'lure_spinnerbait.jpg', FALSE),

-- Lines & Leaders
('Monofilament Line 12lb', '300-yard spool of premium monofilament', 15.99, 100, 4, 'line_mono_12lb.jpg', FALSE),
('Braided Line 20lb', 'Super strong braided fishing line', 29.99, 75, 4, 'line_braid_20lb.jpg', TRUE),
('Fluorocarbon Leader 15lb', 'Invisible fluorocarbon leader material', 19.99, 50, 4, 'line_fluoro_15lb.jpg', FALSE),

-- Tackle Boxes
('Large Tackle Box 4-Tray', 'Spacious tackle box with 4 removable trays', 39.99, 35, 5, 'box_large_4tray.jpg', TRUE),
('Compact Tackle Bag', 'Portable tackle bag with multiple pockets', 24.99, 45, 5, 'box_compact_bag.jpg', FALSE),
('Professional Tackle System', 'Complete tackle organization system', 89.99, 20, 5, 'box_pro_system.jpg', FALSE),

-- Accessories
('Digital Fish Scale 50lb', 'Accurate digital scale for weighing your catch', 19.99, 60, 6, 'acc_scale_digital.jpg', FALSE),
('Fishing Net Landing', 'Rubber-coated landing net', 29.99, 40, 6, 'acc_net_landing.jpg', FALSE),
('Multi-Tool Fishing Pliers', 'Stainless steel pliers with multiple functions', 34.99, 55, 6, 'acc_pliers_multi.jpg', TRUE);

-- Insert settings
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('store_name', 'Fishing Store', 'Store name'),
('store_email', 'info@fishingstore.com', 'Store contact email'),
('store_phone', '+1 (555) 123-4567', 'Store phone number'),
('store_address', '123 Fishing St, Marina Bay, CA 90210', 'Store address'),
('shipping_cost', '9.99', 'Standard shipping cost'),
('free_shipping_threshold', '75.00', 'Free shipping threshold'),
('tax_rate', '0.08', 'Tax rate percentage');