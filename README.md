# Fishing Store - Complete E-commerce Website

A modern, responsive fishing equipment e-commerce website built with PHP, MySQL, and Bootstrap 5.

## 🌟 Features

### Core Functionality
- **User Authentication**: Registration, login with popup modal, and logout
- **Admin System**: Role-based access control with admin dashboard
- **Product Catalog**: Browse products by category with search and filtering
- **Shopping Cart**: Add, update, remove products with AJAX functionality
- **Category Management**: Admin can add, edit, and delete categories (with admin-only buttons)
- **Responsive Design**: Mobile-first Bootstrap 5 design
- **Modern UI**: Clean, professional interface with smooth animations

### Key Improvements Made
- ✅ **Login as Popup Modal**: No separate login page, modal appears on index page
- ✅ **Admin Category Controls**: Add, Edit, Delete buttons only visible to admin users
- ✅ **Complete Rebuild**: Entirely new codebase with modern design patterns
- ✅ **Secure Authentication**: Proper password hashing and session management
- ✅ **Professional Design**: Modern CSS with gradients, shadows, and animations

## 📁 Project Structure

```
fishing-store/
├── index.php              # Homepage with login modal and category management
├── register.php           # User registration page
├── products.php           # Product listing with filters and search
├── cart.php               # Shopping cart with AJAX functionality
├── about.php              # About us page
├── contact.php            # Contact page with form
├── config.php             # Database configuration and helper functions
├── database.sql           # Complete database schema and sample data
├── setup.php              # Database installation script
├── admin/
│   └── manage_categories.php  # Admin category management backend
├── images/
│   ├── placeholder.jpg    # Default product image
│   └── [product images]   # Product images
└── README.md              # This file
```

## 🚀 Installation

### Prerequisites
- PHP 7.4+ with GD extension
- MySQL 5.7+ or MariaDB
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone/Download** the project files to your web server directory

2. **Configure Database**:
   - Update database credentials in `config.php` if needed:
   ```php
   $host = 'localhost';
   $dbname = 'fishing_store';
   $username = 'root';
   $password = '';
   ```

3. **Install Database**:
   - Navigate to `http://your-domain/setup.php`
   - The script will automatically:
     - Create the `fishing_store` database
     - Create all necessary tables
     - Insert sample data including categories and products
     - Create the default admin user

4. **Access the Website**:
   - Visit `http://your-domain/index.php`
   - The website is now ready to use!

### Default Admin Account
- **Username**: `admin`
- **Password**: `password`

⚠️ **Security**: Change the admin password immediately after installation!

## 🎯 User Guide

### For Regular Users
1. **Browse Products**: Visit the homepage or products page to browse items
2. **Register Account**: Click "Register" to create a new account
3. **Login**: Use the login modal on any page (appears as popup)
4. **Shopping**: Add products to cart and manage quantities
5. **Categories**: Filter products by category using the sidebar

### For Admin Users
1. **Login** with admin credentials
2. **Admin Badge**: You'll see an "Admin" badge next to your name
3. **Category Management**: 
   - View **Add, Edit, Delete** buttons on category cards (only visible to admins)
   - Add new categories using the "Add Category" card
   - Edit existing categories by clicking the "Edit" button
   - Delete categories (with protection for categories that have products)

## 🗄️ Database Schema

### Core Tables
- `users` - User accounts with role-based access (customer/admin)
- `categories` - Product categories with descriptions
- `products` - Product catalog with prices, stock, and images
- `cart` - Shopping cart items for logged-in users
- `orders` & `order_items` - Order management (ready for expansion)
- `settings` - Site configuration options

### Sample Data Included
- **6 Categories**: Fishing Rods, Reels, Lures & Baits, Lines & Leaders, Tackle Boxes, Accessories
- **18 Products**: Variety of fishing equipment across all categories
- **1 Admin User**: For immediate testing and management

## 🎨 Design Features

### Modern UI Elements
- **Gradient Buttons**: Beautiful gradient backgrounds with hover effects
- **Card-based Layout**: Clean cards with hover animations
- **Professional Typography**: Inter font family for clean, modern text
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Color Scheme**: Blue primary theme appropriate for fishing/water theme

### User Experience
- **Smooth Animations**: CSS transitions and hover effects
- **Loading States**: Proper feedback for user actions
- **Error Handling**: Comprehensive error messages and validation
- **Intuitive Navigation**: Clear menu structure and breadcrumbs

## 🔧 Technical Details

### Security Features
- Password hashing with PHP `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control

### Performance
- Optimized SQL queries with proper indexing
- Lazy loading for product images
- Minimal external dependencies
- Efficient pagination for product listings

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 📱 Mobile Responsiveness

The website is fully responsive and includes:
- Mobile-optimized navigation with hamburger menu
- Touch-friendly buttons and form elements
- Responsive product grids
- Optimized images for different screen sizes

## 🔄 Future Enhancements

The codebase is designed for easy expansion:
- **Payment Integration**: Stripe, PayPal, etc.
- **Order Management**: Complete order workflow
- **Inventory Management**: Stock tracking and alerts
- **Reviews & Ratings**: Customer feedback system
- **Email Notifications**: Order confirmations, newsletters
- **Advanced Search**: Filters by price, brand, features
- **Wishlist**: Save products for later

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**: Check MySQL credentials in `config.php`
2. **Images Not Loading**: Ensure proper file permissions on `images/` directory
3. **Admin Functions Not Working**: Verify user has 'admin' role in database
4. **Sessions Not Working**: Check PHP session configuration

### Support
For technical support or feature requests, please contact the development team.

## 📄 License

This project is created for educational and commercial use. Feel free to modify and distribute as needed.

---

**Built with ❤️ for the fishing community**