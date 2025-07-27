<?php
// ไฟล์ทดสอบการติดตั้ง
echo "<h1>ทดสอบการติดตั้งระบบ Toom Tam Fishing</h1>";

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>1. การเชื่อมต่อฐานข้อมูล</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=fishing_store;charset=utf8mb4", "root", "");
    echo "<p style='color:green'>✓ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>✗ เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage() . "</p>";
}

// 2. ตรวจสอบไฟล์ที่จำเป็น
echo "<h2>2. ไฟล์ที่จำเป็น</h2>";
$required_files = [
    'config.php',
    'index.php', 
    'product.php',
    'cart.php',
    'login.php',
    'register.php',
    'contact.php',
    'search.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✓ $file พบไฟล์</p>";
    } else {
        echo "<p style='color:red'>✗ $file ไม่พบไฟล์</p>";
    }
}

// 3. ทดสอบสร้างรหัสผ่าน
echo "<h2>3. ทดสอบรหัสผ่าน</h2>";
$test_password = "password123";
$hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "<p>รหัสผ่าน: password123</p>";
echo "<p>Hash: $hash</p>";

// 4. ทดสอบ verify รหัสผ่าน
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
if (password_verify($test_password, $stored_hash)) {
    echo "<p style='color:green'>✓ รหัสผ่านถูกต้อง</p>";
} else {
    echo "<p style='color:red'>✗ รหัสผ่านไม่ถูกต้อง</p>";
}

// 5. ตรวจสอบ PHP Version
echo "<h2>4. PHP Version</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// 6. ตรวจสอบ Session
echo "<h2>5. Session</h2>";
session_start();
if (isset($_SESSION)) {
    echo "<p style='color:green'>✓ Session ทำงานปกติ</p>";
} else {
    echo "<p style='color:red'>✗ Session ไม่ทำงาน</p>";
}

// 7. แสดง path ปัจจุบัน
echo "<h2>6. Path Information</h2>";
echo "<p>Current Directory: " . __DIR__ . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

// 8. ลิงก์ทดสอบ
echo "<h2>7. ลิงก์ทดสอบ</h2>";
echo "<p><a href='index.php'>ไปหน้าแรก</a></p>";
echo "<p><a href='product.php?id=1'>ดูสินค้า ID 1</a></p>";
echo "<p><a href='login.php'>หน้า Login</a></p>";

// 9. สร้างปุ่มรีเซ็ตรหัสผ่าน admin
echo "<h2>8. รีเซ็ตรหัสผ่าน Admin</h2>";
if (isset($_POST['reset_password'])) {
    try {
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$new_hash]);
        echo "<p style='color:green'>✓ รีเซ็ตรหัสผ่าน admin เป็น 'admin123' สำเร็จ</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    }
}
?>
<form method="POST">
    <button type="submit" name="reset_password">รีเซ็ตรหัสผ่าน Admin เป็น 'admin123'</button>
</form>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}
h1, h2 {
    color: #333;
}
h2 {
    border-bottom: 2px solid #3498db;
    padding-bottom: 5px;
}
button {
    background: #3498db;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
button:hover {
    background: #2980b9;
}
</style>