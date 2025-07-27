<?php
session_start();
require_once 'config.php';

echo "<h2>Debug Login System</h2>";

// รับค่าจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h3>ข้อมูลที่ส่งมา:</h3>";
    echo "<p>Username: <strong>$username</strong></p>";
    echo "<p>Password: <strong>$password</strong></p>";
    
    try {
        $pdo = getConnection();
        
        // ดึงข้อมูล user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<h3>พบ User:</h3>";
            echo "<p>ID: " . $user['id'] . "</p>";
            echo "<p>Username: " . $user['username'] . "</p>";
            echo "<p>Email: " . $user['email'] . "</p>";
            echo "<p>Password Hash: <code style='word-break:break-all;'>" . $user['password'] . "</code></p>";
            
            // ทดสอบ password
            echo "<h3>ทดสอบ Password:</h3>";
            if (password_verify($password, $user['password'])) {
                echo "<p style='color:green'>✓ Password ถูกต้อง! - ควรจะล็อกอินได้</p>";
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                echo "<p style='color:green'>✓ Session ถูกสร้างแล้ว</p>";
                echo "<p><a href='index.php'>ไปหน้าแรก</a></p>";
            } else {
                echo "<p style='color:red'>✗ Password ไม่ถูกต้อง</p>";
                
                // ทดสอบ password_verify
                echo "<h4>ทดสอบเพิ่มเติม:</h4>";
                $test_hash = password_hash($password, PASSWORD_DEFAULT);
                echo "<p>Hash ใหม่จาก password ที่ใส่: <code style='word-break:break-all;'>$test_hash</code></p>";
                
                if (password_verify('password123', $user['password'])) {
                    echo "<p style='color:orange'>แต่ password 'password123' ใช้ได้กับ hash ในฐานข้อมูล</p>";
                }
            }
        } else {
            echo "<p style='color:red'>✗ ไม่พบ username นี้ในฐานข้อมูล</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Database Error: " . $e->getMessage() . "</p>";
    }
}

// แสดง users ทั้งหมด
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, username, email FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h3>Users ในระบบ:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td>" . $u['username'] . "</td>";
        echo "<td>" . $u['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>ไม่สามารถดึงข้อมูล users: " . $e->getMessage() . "</p>";
}
?>

<h3>ทดสอบ Login:</h3>
<form method="POST">
    <p>
        Username: <input type="text" name="username" value="admin">
    </p>
    <p>
        Password: <input type="text" name="password" value="password123"> 
        <small>(แสดงเป็น text เพื่อให้เห็นว่าพิมพ์อะไร)</small>
    </p>
    <button type="submit">Test Login</button>
</form>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}
code {
    background: #e0e0e0;
    padding: 2px 5px;
    font-size: 0.9em;
}
table {
    border-collapse: collapse;
    margin: 10px 0;
}
th {
    background: #3498db;
    color: white;
}
</style>