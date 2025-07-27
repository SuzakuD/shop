<?php
// ตรวจสอบ path และ URL
echo "<h1>ตรวจสอบ Path และ URL</h1>";

echo "<h2>1. ข้อมูล Path</h2>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>2. URL ที่ควรใช้</h2>";
$folder_name = basename(__DIR__);
echo "<p><strong>ชื่อโฟลเดอร์:</strong> $folder_name</p>";
echo "<p><strong>URL ที่ถูกต้อง:</strong> http://localhost/$folder_name/</p>";

echo "<h2>3. ไฟล์ในโฟลเดอร์</h2>";
$files = scandir('.');
$important_files = ['index.php', 'login.php', 'config.php', 'product.php', 'cart.php', 'contact.php', 'search.php'];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ไฟล์</th><th>สถานะ</th><th>ขนาด</th></tr>";
foreach ($important_files as $file) {
    echo "<tr>";
    echo "<td>$file</td>";
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<td style='color:green'>✓ มี</td>";
        echo "<td>" . number_format($size) . " bytes</td>";
    } else {
        echo "<td style='color:red'>✗ ไม่มี</td>";
        echo "<td>-</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. คำแนะนำ</h2>";
echo "<div style='background:#ffffcc; padding:10px; border:1px solid #cccc00;'>";
echo "<p>1. ใช้ URL: <strong>http://localhost/$folder_name/</strong></p>";
echo "<p>2. ถ้าไฟล์ใดขาดหาย ให้สร้างจากโค้ดที่ Claude ให้ไว้</p>";
echo "<p>3. ตรวจสอบว่า XAMPP/Apache ทำงานอยู่</p>";
echo "</div>";

echo "<h2>5. ลิงก์ทดสอบ</h2>";
echo "<p>";
echo "<a href='index.php'>index.php</a> | ";
echo "<a href='login.php'>login.php</a> | ";
echo "<a href='test_login.php'>test_login.php</a> | ";
echo "<a href='create_missing_files.php'>create_missing_files.php</a>";
echo "</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
}
table {
    border-collapse: collapse;
    width: 100%;
}
th {
    background: #f0f0f0;
}
</style>