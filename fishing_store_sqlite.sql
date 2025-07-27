-- SQLite Database Schema for Fishing Store

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT,
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INTEGER DEFAULT 0,
    image_url TEXT,
    category_id INTEGER,
    featured INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    product_id INTEGER,
    quantity INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    total_amount DECIMAL(10,2) NOT NULL,
    status TEXT DEFAULT 'pending',
    shipping_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    product_id INTEGER,
    quantity INTEGER,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert sample categories
INSERT OR IGNORE INTO categories (name, description) VALUES
('Rods', 'Fishing rods for all skill levels'),
('Reels', 'High-quality fishing reels'),
('Lures', 'Artificial lures and baits'),
('Tackle', 'Essential fishing tackle and accessories'),
('Apparel', 'Fishing clothing and gear');

-- Insert sample products
INSERT OR IGNORE INTO products (name, description, price, stock_quantity, image_url, category_id, featured) VALUES
('Professional Fishing Rod', 'High-quality carbon fiber fishing rod perfect for professionals', 299.99, 15, 'images/rod1.jpg', 1, 1),
('Spinning Reel Pro', 'Smooth spinning reel with excellent drag system', 189.99, 20, 'images/reel1.jpg', 2, 1),
('Tackle Box Deluxe', 'Large tackle box with multiple compartments', 79.99, 30, 'images/tackle1.jpg', 4, 0),
('Fishing Lure Set', 'Complete set of 20 different fishing lures', 49.99, 25, 'images/lures1.jpg', 3, 1),
('Waterproof Fishing Jacket', 'High-quality waterproof jacket for fishing', 149.99, 12, 'images/jacket1.jpg', 5, 0),
('Baitcasting Reel', 'Professional baitcasting reel for experienced anglers', 249.99, 8, 'images/reel2.jpg', 2, 1),
('Fly Fishing Rod', 'Lightweight fly fishing rod for stream fishing', 199.99, 10, 'images/rod2.jpg', 1, 0),
('Fishing Line 500m', 'High-strength monofilament fishing line', 24.99, 50, 'images/line1.jpg', 4, 0);

-- Insert sample admin user (password: admin123)
INSERT OR IGNORE INTO users (username, email, password, full_name, phone, address) VALUES
('admin', 'admin@fishingstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Store Administrator', '555-0123', '123 Admin St');