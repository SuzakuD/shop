# Toom Tam Fishing Store Website

A complete e-commerce website for fishing equipment and accessories, built with PHP and SQLite.

## 🚀 Quick Start

### Prerequisites
- Apache web server
- PHP 8.4+ with SQLite extension
- SQLite3

### Installation & Setup

1. **Start the website:**
   ```bash
   ./start_website.sh
   ```

2. **Access the website:**
   - Homepage: http://localhost/index.php
   - Admin Dashboard: http://localhost/Admin%20Dashboard.php

### Default Admin Credentials
- **Username:** admin
- **Email:** admin@fishingstore.com
- **Password:** admin123

## 📁 Project Structure

```
/
├── index.php                          # Homepage with featured products
├── login.php                          # User login system
├── product.php                        # Individual product pages
├── cart.php                           # Shopping cart functionality
├── checkout.php                       # Order processing
├── add_to_cart.php                    # Add items to cart
├── search.php                         # Product search
├── config.php                         # Database configuration
├── fishing_store.db                   # SQLite database
├── fishing_store_sqlite.sql           # Database schema
├── Admin Dashboard.php                # Admin control panel
├── Admin Products Management.php      # Product management
├── Admin Categories Management.php    # Category management
├── Admin Users Management.php         # User management
├── Admin Orders Management.php        # Order management
├── images/                            # Product images directory
└── start_website.sh                   # Startup script
```

## 🛍️ Features

### Customer Features
- **Product Catalog:** Browse fishing equipment by categories
- **Product Search:** Advanced search with filters
- **Shopping Cart:** Add/remove items, update quantities
- **User Registration & Login:** Secure user authentication
- **Order Management:** Place and track orders
- **Responsive Design:** Mobile-friendly interface

### Admin Features
- **Dashboard:** Overview of sales, orders, and inventory
- **Product Management:** Add, edit, delete products
- **Category Management:** Organize products into categories
- **User Management:** View and manage customer accounts
- **Order Management:** Process and track orders
- **Reports:** Sales and inventory reports

### Product Categories
1. **Rods** - Fishing rods for all skill levels
2. **Reels** - High-quality fishing reels
3. **Lures** - Artificial lures and baits
4. **Tackle** - Essential fishing tackle and accessories
5. **Apparel** - Fishing clothing and gear

## 🗄️ Database Schema

The website uses SQLite with the following main tables:
- `users` - Customer and admin accounts
- `categories` - Product categories
- `products` - Product catalog
- `cart` - Shopping cart items
- `orders` - Order information
- `order_items` - Order line items

## 🔧 Configuration

### Database Configuration (config.php)
```php
$db_path = __DIR__ . '/fishing_store.db';
$pdo = new PDO("sqlite:$db_path");
```

### Web Server
- **Document Root:** `/var/www/html/`
- **Default Port:** 80
- **PHP Version:** 8.4+

## 🛠️ Development

### Adding New Products
1. Access Admin Dashboard at `/Admin Dashboard.php`
2. Navigate to Product Management
3. Fill in product details and upload images
4. Set category and pricing information

### Customizing the Design
- CSS styles are embedded in individual PHP files
- Modify the `<style>` sections in each file
- Product images should be placed in `/images/` directory

### Database Maintenance
```bash
# Backup database
cp fishing_store.db fishing_store_backup.db

# Reset database
sqlite3 fishing_store.db < fishing_store_sqlite.sql
```

## 🚦 Troubleshooting

### Common Issues

1. **Database Connection Error:**
   - Ensure SQLite extension is installed: `php -m | grep sqlite`
   - Check file permissions on `fishing_store.db`

2. **Apache Not Starting:**
   - Check if port 80 is available
   - Verify Apache configuration: `sudo apache2ctl configtest`

3. **Missing Product Images:**
   - Upload images to `/var/www/html/images/` directory
   - Update image paths in database

### Log Files
- Apache logs: `/var/log/apache2/`
- PHP errors: Check browser developer console

## 📝 Sample Data

The website comes pre-loaded with:
- 8 sample products across 5 categories
- 1 admin user account
- Product categories and descriptions

## 🔒 Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention using prepared statements
- Session management for user authentication
- Input validation and sanitization

## 📱 Mobile Responsiveness

The website is designed to work on:
- Desktop computers
- Tablets
- Mobile phones
- Various screen sizes and orientations

## 🎯 Future Enhancements

Potential improvements:
- Payment gateway integration
- Email notifications
- Product reviews and ratings
- Inventory management alerts
- Multi-language support
- SEO optimization

---

**Created by:** AI Assistant
**Version:** 1.0
**Last Updated:** January 2025