<?php
// ไฟล์แก้ปัญหาทั้งหมดในครั้งเดียว
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>แก้ปัญหาระบบ Fishing Store</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .box {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #2980b9; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th { background: #f0f0f0; }
        code {
            background: #f0f0f0;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>🔧 แก้ปัญหาระบบ Fishing Store</h1>

    <?php
    // 1. ข้อมูลระบบ
    echo '<div class="box">';
    echo '<h2>1. ข้อมูลระบบ</h2>';
    echo '<p>PHP Version: ' . phpversion() . '</p>';
    echo '<p>Current Directory: <code>' . __DIR__ . '</code></p>';
    echo '<p>Script URL: <code>' . $_SERVER['REQUEST_URI'] . '</code></p>';
    echo '<p>Document Root: <code>' . $_SERVER['DOCUMENT_ROOT'] . '</code></p>';
    echo '</div>';

    // 2. ทดสอบฐานข้อมูล
    echo '<div class="box">';
    echo '<h2>2. การเชื่อมต่อฐานข้อมูล</h2>';
    
    $db_connected = false;
    $pdo = null;
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=fishing_store;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo '<p class="success">✓ เชื่อมต่อฐานข้อมูล fishing_store สำเร็จ</p>';
        $db_connected = true;
    } catch(PDOException $e) {
        echo '<p class="error">✗ เชื่อมต่อฐานข้อมูลไม่ได้: ' . $e->getMessage() . '</p>';
        echo '<p class="warning">⚠ ตรวจสอบว่า:</p>';
        echo '<ul>';
        echo '<li>MySQL/MariaDB ทำงานอยู่</li>';
        echo '<li>มีฐานข้อมูล fishing_store</li>';
        echo '<li>username: root, password: (ว่าง)</li>';
        echo '</ul>';
    }
    echo '</div>';

    // 3. ตรวจสอบไฟล์
    echo '<div class="box">';
    echo '<h2>3. ตรวจสอบไฟล์สำคัญ</h2>';
    
    $required_files = [
        'config.php' => 'ไฟล์การตั้งค่า',
        'index.php' => 'หน้าแรก',
        'login.php' => 'หน้า login',
        'product.php' => 'หน้าสินค้า',
        'cart.php' => 'ตะกร้าสินค้า'
    ];
    
    $missing_files = [];
    foreach ($required_files as $file => $desc) {
        if (file_exists($file)) {
            echo '<p class="success">✓ ' . $file . ' - ' . $desc . '</p>';
        } else {
            echo '<p class="error">✗ ' . $file . ' - ' . $desc . ' (ไม่พบไฟล์)</p>';
            $missing_files[] = $file;
        }
    }
    
    if (count($missing_files) > 0) {
        echo '<p class="warning">⚠ กรุณาตรวจสอบว่าไฟล์อยู่ในโฟลเดอร์ที่ถูกต้อง</p>';
    }
    echo '</div>';

    // 4. แสดง Users และแก้รหัสผ่าน
    if ($db_connected) {
        echo '<div class="box">';
        echo '<h2>4. จัดการ Users</h2>';
        
        // แสดง users
        $stmt = $pdo->query("SELECT id, username, email, password FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Hash (ย่อ)</th></tr>';
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td><strong>' . $user['username'] . '</strong></td>';
            echo '<td>' . $user['email'] . '</td>';
            echo '<td><code>' . substr($user['password'], 0, 20) . '...</code></td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // ฟอร์มแก้รหัสผ่าน
        if (isset($_POST['fix_passwords'])) {
            $new_password = 'password123';
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ?");
            $stmt->execute([$hash]);
            
            echo '<p class="success">✓ อัพเดตรหัสผ่านทั้งหมดเป็น <strong>password123</strong> เรียบร้อย!</p>';
            echo '<script>setTimeout(function(){ location.reload(); }, 2000);</script>';
        }
        
        // ทดสอบ password verify
        if (count($users) > 0) {
            $test_user = $users[0];
            $test_result = password_verify('password123', $test_user['password']);
            
            if ($test_result) {
                echo '<p class="success">✓ รหัสผ่านปัจจุบันใช้ได้กับ password123</p>';
            } else {
                echo '<p class="warning">⚠ รหัสผ่านปัจจุบันใช้ไม่ได้ - ต้องแก้ไข</p>';
                echo '<form method="POST">';
                echo '<button type="submit" name="fix_passwords">🔧 แก้ไขรหัสผ่านทั้งหมดเป็น password123</button>';
                echo '</form>';
            }
        }
        echo '</div>';
    }

    // 5. สร้างโฟลเดอร์รูปภาพ
    echo '<div class="box">';
    echo '<h2>5. โฟลเดอร์รูปภาพ</h2>';
    
    $dirs = ['images', 'images/products'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0777, true)) {
                echo '<p class="success">✓ สร้างโฟลเดอร์ ' . $dir . ' สำเร็จ</p>';
            } else {
                echo '<p class="error">✗ สร้างโฟลเดอร์ ' . $dir . ' ไม่สำเร็จ</p>';
            }
        } else {
            echo '<p class="success">✓ โฟลเดอร์ ' . $dir . ' มีอยู่แล้ว</p>';
        }
    }
    echo '</div>';

    // 6. ทดสอบ Login
    if (isset($_POST['test_login'])) {
        echo '<div class="box">';
        echo '<h2>ทดสอบ Login</h2>';
        
        $username = $_POST['test_username'];
        $password = $_POST['test_password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo '<p class="success">✓ Login สำเร็จ! Session ถูกสร้างแล้ว</p>';
            echo '<p>User ID: ' . $_SESSION['user_id'] . '</p>';
            echo '<p>Username: ' . $_SESSION['username'] . '</p>';
        } else {
            echo '<p class="error">✗ Login ไม่สำเร็จ - username หรือ password ไม่ถูกต้อง</p>';
        }
        echo '</div>';
    }
    ?>

    <!-- 7. ฟอร์มทดสอบ Login -->
    <div class="box">
        <h2>6. ทดสอบ Login</h2>
        <form method="POST">
            <p>
                Username: <input type="text" name="test_username" value="admin" required>
            </p>
            <p>
                Password: <input type="text" name="test_password" value="password123" required>
                <small>(แสดงเป็น text เพื่อให้เห็นว่าพิมพ์อะไร)</small>
            </p>
            <button type="submit" name="test_login">🔐 ทดสอบ Login</button>
        </form>
    </div>

    <!-- 8. ลิงก์ไปหน้าต่างๆ -->
    <div class="box">
        <h2>7. ลิงก์ทดสอบ</h2>
        <p>
            <a href="index.php">🏠 หน้าแรก</a> |
            <a href="login.php">🔐 Login</a> |
            <a href="register.php">📝 Register</a> |
            <a href="admin.php">👨‍💼 Admin</a>
        </p>
    </div>

    <!-- 9. คำแนะนำ -->
    <div class="box">
        <h2>💡 คำแนะนำ</h2>
        <ol>
            <li>ถ้ารหัสผ่านใช้ไม่ได้ ให้คลิก "แก้ไขรหัสผ่านทั้งหมด"</li>
            <li>ทดสอบ login ด้วย admin / password123</li>
            <li>ถ้า login สำเร็จในหน้านี้ แต่หน้า login.php ใช้ไม่ได้ แสดงว่าไฟล์ login.php มีปัญหา</li>
            <li>ตรวจสอบว่าใช้ URL ที่ถูกต้อง (FISHING_STORE ตัวพิมพ์ใหญ่)</li>
        </ol>
    </div>
</body>
</html>