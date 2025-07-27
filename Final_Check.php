<?php
// ตรวจสอบครั้งสุดท้าย
echo "<h1>ตรวจสอบระบบครั้งสุดท้าย</h1>";

$required_files = [
    'config.php' => 'ไฟล์การตั้งค่า',
    'index.php' => 'หน้าแรก',
    'product.php' => 'หน้าสินค้า',
    'cart.php' => 'ตะกร้าสินค้า',
    'login.php' => 'หน้า login',
    'register.php' => 'หน้าสมัครสมาชิก',
    'contact.php' => 'หน้าติดต่อเรา',
    'search.php' => 'หน้าค้นหา',
    'add_to_cart.php' => 'เพิ่มสินค้าลงตะกร้า',
    'checkout.php' => 'หน้าชำระเงิน',
    'logout.php' => 'ออกจากระบบ'
];

echo "<h2>ตรวจสอบไฟล์ทั้งหมด</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>";
echo "<tr><th>ไฟล์</th><th>คำอธิบาย</th><th>สถานะ</th><th>ขนาด</th></tr>";

$missing = 0;
foreach ($required_files as $file => $desc) {
    echo "<tr>";
    echo "<td>$file</td>";
    echo "<td>$desc</td>";
    
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<td style='background:#d4edda; color:#155724;'>✓ มี</td>";
        echo "<td>" . number_format($size) . " bytes</td>";
    } else {
        echo "<td style='background:#f8d7da; color:#721c24;'>✗ ไม่มี</td>";
        echo "<td>-</td>";
        $missing++;
    }
    echo "</tr>";
}
echo "</table>";

if ($missing == 0) {
    echo "<div style='background:#d4edda; color:#155724; padding:20px; margin:20px 0; border-radius:5px;'>";
    echo "<h2>✓ ระบบพร้อมใช้งาน!</h2>";
    echo "<p>ไฟล์ครบทุกไฟล์แล้ว สามารถเริ่มใช้งานได้</p>";
    echo "<p><a href='index.php'>ไปหน้าแรก</a> | <a href='login.php'>Login</a></p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da; color:#721c24; padding:20px; margin:20px 0; border-radius:5px;'>";
    echo "<h2>⚠ ยังขาดไฟล์ $missing ไฟล์</h2>";
    echo "<p>กรุณาสร้างไฟล์ที่ขาดหายตามรายการด้านบน</p>";
    echo "</div>";
}

// ตรวจสอบโฟลเดอร์รูปภาพ
echo "<h2>ตรวจสอบโฟลเดอร์</h2>";
$dirs = ['images', 'images/products'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p style='color:green;'>✓ โฟลเดอร์ $dir มีอยู่</p>";
    } else {
        echo "<p style='color:red;'>✗ โฟลเดอร์ $dir ไม่มี</p>";
        if (mkdir($dir, 0777, true)) {
            echo "<p style='color:orange;'>→ สร้างโฟลเดอร์ $dir สำเร็จ</p>";
        }
    }
}

// รีเซ็ตรหัสผ่านอัตโนมัติ
if (isset($_POST['reset_all_passwords'])) {
    try {
        require_once 'config.php';
        $pdo = getConnection();
        
        $new_password = 'password123';
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?");
        $stmt->execute([$hash]);
        
        echo "<div style='background:#d4edda; color:#155724; padding:20px; margin:20px 0; border-radius:5px;'>";
        echo "<h3>✓ รีเซ็ตรหัสผ่านสำเร็จ!</h3>";
        echo "<p>ทุก user สามารถ login ด้วย password: <strong>password123</strong></p>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<h2>แก้ไขรหัสผ่าน</h2>
<form method="POST">
    <button type="submit" name="reset_all_passwords" 
            style="background:#e74c3c; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">
        🔧 รีเซ็ตรหัสผ่านทุก User เป็น password123
    </button>
</form>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}
table {
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
th {
    background: #3498db;
    color: white;
}
</style>