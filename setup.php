<?php
/*
 * Database Setup Script for Fishing Store
 * Run this file once to set up the database and sample data
 */

echo "<h2>Fishing Store Database Setup</h2>\n";

try {
    // Database connection parameters
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    // Create connection without database first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✓ Database and tables created successfully</p>\n";
    echo "<p>✓ Sample data inserted</p>\n";
    
    // Verify the setup
    $pdo = new PDO("mysql:host=$host;dbname=fishing_store;charset=utf8mb4", $username, $password);
    
    // Check tables
    $tables = ['users', 'categories', 'products', 'cart', 'orders', 'order_items', 'settings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p>✓ Table '$table': $count records</p>\n";
    }
    
    echo "<h3>Setup Complete!</h3>\n";
    echo "<p><strong>Admin Login:</strong></p>\n";
    echo "<p>Username: admin<br>Password: password</p>\n";
    echo "<p><a href='index.php'>Go to Website</a></p>\n";
    
    // Optional: Delete this setup file for security
    echo "<p style='color: orange;'><strong>Security Note:</strong> Consider deleting this setup.php file after successful installation.</p>\n";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection settings and try again.</p>\n";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 800px; 
    margin: 50px auto; 
    padding: 20px; 
    background: #f5f5f5; 
}
h2, h3 { 
    color: #0066cc; 
}
p { 
    margin: 10px 0; 
}
a { 
    color: #0066cc; 
    text-decoration: none; 
    font-weight: bold; 
}
a:hover { 
    text-decoration: underline; 
}
</style>