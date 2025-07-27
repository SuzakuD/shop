<?php
require_once 'config.php';

$pdo = getConnection();

// ดำเนินการเมื่อมีการอัพเดตตะกร้า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['quantities'] ?? [];
        
        foreach ($quantities as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;
            
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                // ตรวจสอบสต็อก
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $stock = $stmt->fetchColumn();
                
                if ($quantity > $stock) {
                    $_SESSION['message'] = "จำนวนสินค้าเกินสต็อกที่มี";
                    $_SESSION['message_type'] = "error";
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
            }
        }
        
        if (!isset($_SESSION['message'])) {
            $_SESSION['message'] = "อัพเดตตะกร้าเรียบร้อยแล้ว";
            $_SESSION['message_type'] = "success";
        }
        
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$product_id]);
        
        $_SESSION['message'] = "ลบสินค้าออกจากตะกร้าเรียบร้อยแล้ว";
        $_SESSION['message_type'] = "success";
        
        header('Location: cart.php');
        exit;
    }
}

// ดึงข้อมูลสินค้าในตะกร้า
$cart_items = [];
$total_price = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total_price += $subtotal;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - Toom Tam Fishing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,0.2);
        }

        .cart-icon {
            position: relative;
            font-size: 1.5rem;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .cart-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .cart-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            border-color: #27ae60;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #e74c3c;
            color: #721c24;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .cart-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 2rem;
        }

        .product-details h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .product-details p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .quantity-input {
            width: 70px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }

        .price {
            font-weight: bold;
            color: #e74c3c;
            font-size: 1.1rem;
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #c0392b;
        }

        .cart-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .total-price {
            font-size: 1.5rem;
            color: #e74c3c;
        }

        .cart-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .empty-cart {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2rem;
            padding: 50px;
        }

        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .cart-table {
                font-size: 0.9rem;
            }
            
            .cart-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-fish"></i> Toom Tam Fishing
            </a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> ตะกร้า</a></li>
                    <li><a href="contact.php"><i class="fas fa-phone"></i> ติดต่อ</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="orders.php"><i class="fas fa-receipt"></i> คำสั่งซื้อ</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="container">
        <div class="cart-content">
            <h1 class="cart-title">
                <i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า
            </h1>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>ตะกร้าสินค้าของคุณว่างเปล่า</h3>
                    <p>เลือกสินค้าที่คุณต้องการและเพิ่มลงในตะกร้า</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-shopping-bag"></i> เลือกซื้อสินค้า
                    </a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>สินค้า</th>
                                <th>ราคา</th>
                                <th>จำนวน</th>
                                <th>รวม</th>
                                <th>ลบ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image">
                                                <i class="fas fa-fish"></i>
                                            </div>
                                            <div class="product-details">
                                                <h4><?php echo htmlspecialchars($item['product']['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($item['product']['description']); ?></p>
                                                <small>คงเหลือ: <?php echo $item['product']['stock']; ?> ชิ้น</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price"><?php echo formatPrice($item['product']['price']); ?></span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="quantities[<?php echo $item['product']['id']; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['product']['stock']; ?>"
                                               class="quantity-input">
                                    </td>
                                    <td>
                                        <span class="price"><?php echo formatPrice($item['subtotal']); ?></span>
                                    </td>
                                    <td>
                                        <button type="submit" name="remove_item" 
                                                value="<?php echo $item['product']['id']; ?>" 
                                                class="remove-btn"
                                                onclick="return confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-summary">
                        <div class="total-row">
                            <span>ยอดรวมทั้งหมด:</span>
                            <span class="total-price"><?php echo formatPrice($total_price); ?></span>
                        </div>
                    </div>

                    <div class="cart-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> ช้อปต่อ
                        </a>
                        <button type="submit" name="update_cart" class="btn btn-primary">
                            <i class="fas fa-sync"></i> อัพเดตตะกร้า
                        </button>
                        <a href="checkout.php" class="btn btn-success">
                            <i class="fas fa-credit-card"></i> ชำระเงิน
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>