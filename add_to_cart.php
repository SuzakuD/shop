<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $pdo = getConnection();
    
    // ตรวจสอบสินค้าและสต็อก
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'สินค้าไม่พบ']);
        exit;
    }
    
    if ($product['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'สินค้าหมด']);
        exit;
    }
    
    // เริ่มต้น cart session
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // ตรวจสอบจำนวนในตะกร้า
    $current_cart_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
    $new_total_qty = $current_cart_qty + $quantity;
    
    if ($new_total_qty > $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'จำนวนสินค้าเกินสต็อกที่มี']);
        exit;
    }
    
    // เพิ่มสินค้าลงตะกร้า
    $_SESSION['cart'][$product_id] = $new_total_qty;
    
    // คำนวณจำนวนสินค้าทั้งหมดในตะกร้า
    $cart_count = array_sum($_SESSION['cart']);
    
    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว',
        'cart_count' => $cart_count,
        'product_name' => $product['name']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
?>