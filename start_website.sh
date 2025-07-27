#!/bin/bash

echo "Starting Toom Tam Fishing Store Website..."

# Start Apache web server
sudo apache2ctl start

# Check if Apache is running
if pgrep apache2 > /dev/null; then
    echo "✅ Apache web server is running"
    echo "🌐 Website is available at: http://localhost"
    echo ""
    echo "📝 Website Features:"
    echo "   - Home page: http://localhost/index.php"
    echo "   - Product pages: http://localhost/product.php?id=1"
    echo "   - Login system: http://localhost/login.php"
    echo "   - Shopping cart: http://localhost/cart.php"
    echo "   - Admin panel: http://localhost/Admin%20Dashboard.php"
    echo ""
    echo "🔐 Default admin credentials:"
    echo "   Username: admin"
    echo "   Password: admin123"
    echo ""
    echo "💾 Database: SQLite (fishing_store.db)"
    echo "📁 Web files: /var/www/html/"
    echo ""
    echo "Press Ctrl+C to stop the server"
else
    echo "❌ Failed to start Apache web server"
    exit 1
fi

# Keep the script running
while true; do
    sleep 1
done