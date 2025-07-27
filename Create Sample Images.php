<?php
// สร้างรูปภาพตัวอย่างสำหรับสินค้า
header('Content-Type: text/html; charset=utf-8');

// สร้างโฟลเดอร์ถ้ายังไม่มี
if (!file_exists('images')) {
    mkdir('images', 0777, true);
}
if (!file_exists('images/products')) {
    mkdir('images/products', 0777, true);
}

// ฟังก์ชันสร้างรูปภาพตัวอย่าง
function createSampleImage($filename, $text, $bgColor, $textColor = '#FFFFFF') {
    $width = 600;
    $height = 600;
    
    // สร้างรูปภาพ
    $image = imagecreatetruecolor($width, $height);
    
    // แปลงสีจาก hex เป็น RGB
    list($r, $g, $b) = sscanf($bgColor, "#%02x%02x%02x");
    $bg = imagecolorallocate($image, $r, $g, $b);
    
    list($tr, $tg, $tb) = sscanf($textColor, "#%02x%02x%02x");
    $textcolor = imagecolorallocate($image, $tr, $tg, $tb);
    
    // เติมพื้นหลัง
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);
    
    // ใส่ข้อความ
    $font_size = 5; // 1-5 สำหรับ built-in font
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y, $text, $textcolor);
    
    // บันทึกเป็นไฟล์ JPEG
    imagejpeg($image, 'images/products/' . $filename, 90);
    imagedestroy($image);
    
    return true;
}

// สร้างรูปภาพตัวอย่าง
$images = [
    ['product_1.jpg', 'Shimano Rod', '#3498db'],
    ['product_2.jpg', 'Rapala Lure', '#e74c3c'],
    ['product_3.jpg', 'Daiwa Reel', '#2c3e50'],
    ['product_4.jpg', 'Fishing Line', '#27ae60'],
    ['product_5.jpg', 'Tackle Box', '#f39c12'],
    ['product_6.jpg', 'Abu Garcia', '#9b59b6'],
    ['product_7.jpg', 'Lucky Craft', '#1abc9c'],
    ['product_8.jpg', 'Penn Reel', '#34495e'],
    ['product_9.jpg', 'Fluorocarbon', '#16a085'],
    ['product_10.jpg', 'Meiho Box', '#d35400'],
    ['product_11.jpg', 'Okuma Rod', '#c0392b'],
    ['product_12.jpg', 'Megabass', '#8e44ad'],
    ['product_13.jpg', 'Shimano Stella', '#2980b9'],
    ['product_14.jpg', 'Braided Line', '#27ae60'],
    ['product_15.jpg', 'Rapala Box', '#f39c12'],
    ['product_16.jpg', 'Owner Hook', '#7f8c8d'],
    ['product_17.jpg', 'Fish Grip', '#95a5a6'],
    ['product_18.jpg', 'UV Shirt', '#3498db'],
    ['product_19.jpg', 'Pliers', '#e67e22'],
    ['product_20.jpg', 'LED Light', '#f1c40f']
];

echo "<h1>สร้างรูปภาพตัวอย่าง</h1>";

// ตรวจสอบว่ามี GD Library หรือไม่
if (!extension_loaded('gd')) {
    echo "<p style='color:red'>❌ ไม่มี GD Library - ไม่สามารถสร้างรูปได้</p>";
    echo "<p>กรุณาเปิด extension=gd ใน php.ini</p>";
} else {
    echo "<p style='color:green'>✅ พบ GD Library</p>";
    
    foreach ($images as $img) {
        if (createSampleImage($img[0], $img[1], $img[2])) {
            echo "<p style='color:green'>✅ สร้าง {$img[0]} สำเร็จ</p>";
        } else {
            echo "<p style='color:red'>❌ สร้าง {$img[0]} ไม่สำเร็จ</p>";
        }
    }
}

// อัพเดตฐานข้อมูล
echo "<h2>อัพเดตฐานข้อมูล</h2>";

try {
    require_once 'config.php';
    $pdo = getConnection();
    
    // อัพเดตชื่อไฟล์รูปในฐานข้อมูล
    for ($i = 1; $i <= 20; $i++) {
        $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
        $stmt->execute(["product_$i.jpg", $i]);
    }
    
    echo "<p style='color:green'>✅ อัพเดตฐานข้อมูลเรียบร้อย</p>";
    
    // แสดงผลลัพธ์
    echo "<h2>ตรวจสอบผลลัพธ์</h2>";
    echo "<div style='display:grid; grid-template-columns: repeat(5, 1fr); gap:10px;'>";
    
    for ($i = 1; $i <= 20; $i++) {
        $filename = "product_$i.jpg";
        if (file_exists("images/products/$filename")) {
            echo "<div style='text-align:center;'>";
            echo "<img src='images/products/$filename' style='width:100px; height:100px; object-fit:cover;'>";
            echo "<p>$filename</p>";
            echo "</div>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>กลับหน้าแรก</a></p>";
?>