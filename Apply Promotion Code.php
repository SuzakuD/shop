<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'remove') {
        // ยกเลิกใช้โปรโมชั่น
        unset($_SESSION['promo_code']);
    } else {
        // ใช้โปรโมชั่น
        $promo_code = trim($_POST['promo_code']);
        
        if (!empty($promo_code)) {
            // ตรวจสอบโปรโมชั่น
            $stmt = $pdo->prepare("
                SELECT * FROM promotions 
                WHERE code = ? 
                AND status = 'active'
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                AND (usage_limit IS NULL OR usage_count < usage_limit)
            ");
            $stmt->execute([$promo_code]);
            $promotion = $stmt->fetch();
            
            if ($promotion) {
                $_SESSION['promo_code'] = $promo_code;
            }
        }
    }
}

// กลับไปหน้า checkout
header('Location: checkout.php');
exit();
?>