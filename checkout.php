<?php
session_start();
require_once 'config.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้มีฟังก์ชัน getConnection() และอื่นๆ

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// คำนวณยอดรวม
$total = 0;
$cart_items = [];

// ดึงการเชื่อมต่อ PDO
$pdo = getConnection(); // สมมติว่า getConnection() อยู่ใน config.php

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC); // ใช้ PDO::FETCH_ASSOC เพื่อให้ได้ผลลัพธ์เป็น array associative
    
    if ($product && $quantity <= $product['stock']) {
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    } else if (!$product) {
        // หากสินค้าไม่พบในฐานข้อมูล (อาจถูกลบไปแล้ว)
        // คุณอาจต้องการจัดการตรงนี้ เช่น ลบออกจากตะกร้า
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['message'] = "สินค้าบางรายการในตะกร้าไม่พร้อมจำหน่ายและถูกนำออกไปแล้ว.";
        $_SESSION['message_type'] = "error";
        header('Location: cart.php'); // อาจจะพาไปหน้าตะกร้าเพื่อให้ผู้ใช้เห็นการเปลี่ยนแปลง
        exit();
    } else if ($quantity > $product['stock']) {
        // หากจำนวนที่สั่งเกินสต็อก
        $_SESSION['message'] = "สินค้า " . htmlspecialchars($product['name']) . " มีจำนวนไม่พอ คุณสามารถสั่งได้สูงสุด " . $product['stock'] . " ชิ้น.";
        $_SESSION['message_type'] = "error";
        $_SESSION['cart'][$product_id] = $product['stock']; // ปรับจำนวนในตะกร้าให้เป็นจำนวนสูงสุดที่มี
        header('Location: cart.php');
        exit();
    }
}

// ประมวลผลการสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_address = $_POST['customer_address'] ?? '';
    
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        try {
            $pdo->beginTransaction(); // เริ่มต้น transaction
            
            // เพิ่มผู้ใช้ใหม่หรือใช้ผู้ใช้ที่มีอยู่
            $user_id = null;
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
            } else {
                // สร้างผู้ใช้ชั่วคราว (Guest User) หากไม่ได้ล็อกอิน
                // ตรวจสอบว่าอีเมลนี้เคยถูกใช้เป็น guest user หรือไม่เพื่อหลีกเลี่ยงการสร้างซ้ำ
                $stmt_check_user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt_check_user->execute([$customer_email]);
                $existing_user = $stmt_check_user->fetch(PDO::FETCH_ASSOC);

                if ($existing_user) {
                    $user_id = $existing_user['id'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_guest) VALUES (?, ?, ?, 1)"); // เพิ่ม is_guest column
                    $temp_password = password_hash(uniqid('guest_'), PASSWORD_DEFAULT); // สร้างรหัสผ่านที่ไม่ซ้ำกัน
                    $stmt->execute([$customer_email, $temp_password, $customer_email]);
                    $user_id = $pdo->lastInsertId();
                }
            }
            
            // สร้างคำสั่งซื้อ
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status, customer_name, customer_email, customer_phone, customer_address) VALUES (?, ?, 'pending', ?, ?, ?, ?)");
            $stmt->execute([$user_id, $total, $customer_name, $customer_email, $customer_phone, $customer_address]);
            $order_id = $pdo->lastInsertId();
            
            // เพิ่มรายการสินค้าในคำสั่งซื้อและอัปเดตสต็อก
            foreach ($cart_items as $item) {
                // ตรวจสอบสต็อกอีกครั้งก่อนบันทึกจริงเพื่อป้องกัน Race Condition
                $stmt_check_stock = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE"); // ใช้ FOR UPDATE เพื่อ Lock แถว
                $stmt_check_stock->execute([$item['product']['id']]);
                $current_stock = $stmt_check_stock->fetchColumn();

                if ($item['quantity'] > $current_stock) {
                    throw new Exception("สินค้า " . htmlspecialchars($item['product']['name']) . " มีจำนวนไม่พอในสต็อก กรุณาลองใหม่อีกครั้ง");
                }

                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product']['id'], $item['quantity'], $item['product']['price']]);
                
                // อัพเดตสต็อกสินค้า
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product']['id']]);
            }
            
            $pdo->commit(); // ยืนยัน transaction
            
            // ล้างตะกร้า
            unset($_SESSION['cart']);
            
            // บันทึกข้อมูลการสั่งซื้อใน session เพื่อแสดงในหน้ายืนยัน
            $_SESSION['order_success'] = [
                'order_id' => $order_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'customer_address' => $customer_address,
                'total' => $total
            ];
            
            header('Location: order_success.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollback(); // ยกเลิก transaction หากเกิดข้อผิดพลาด
            $error = "เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage();
            // อาจจะเพิ่ม logging ของ $e->getMessage() เพื่อ debug
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - Toom Tam Fishing</title>
    <style>
        /* รีเซ็ตและสไตล์พื้นฐาน */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
            color: #333;
        }

        /* Container หลัก */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1rem 0;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }

        /* Checkout Layout */
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px; /* ฟอร์มจะกว้างกว่า สรุปคำสั่งซื้อจะฟิกซ์ความกว้าง */
            gap: 2rem;
            margin-top: 2rem;
        }

        .checkout-form,
        .order-summary {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .order-summary {
            height: fit-content; /* ปรับความสูงตามเนื้อหา */
            position: sticky; /* ทำให้เลื่อนตามหน้าจอ */
            top: 20px; /* ระยะห่างจากด้านบน */
        }

        /* ฟอร์ม */
        .checkout-form h2,
        .order-summary h2 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #555;
            font-size: 1.05rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1.05rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.06);
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        textarea {
            resize: vertical; /* อนุญาตให้ปรับขนาดแนวตั้ง */
            min-height: 120px;
        }

        /* สรุปคำสั่งซื้อ */
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px dashed #eee; /* ใช้เส้นประเพื่อความเบา */
        }

        .order-item:last-of-type { /* เลือก item สุดท้ายก่อน Total */
            border-bottom: none;
            margin-bottom: 1rem;
        }

        .order-total { /* สไตล์สำหรับยอดรวมทั้งสิ้น */
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 2px solid #3498db;
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.3rem;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .item-details {
            color: #777;
            font-size: 0.95rem;
        }

        .item-price {
            font-weight: 600;
            color: #27ae60;
            font-size: 1.1rem;
            text-align: right;
        }

        /* ปุ่ม */
        .btn {
            padding: 0.8rem 1.8rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
        }

        .error {
            background: #e74c3c;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #c0392b;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .form-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
            justify-content: flex-end; /* จัดปุ่มไปทางขวา */
        }

        .form-actions .btn {
            flex-grow: 1; /* ทำให้ปุ่มขยายเต็มพื้นที่ถ้ามีน้อยกว่า 2 ปุ่ม */
            max-width: 200px; /* จำกัดความกว้างของปุ่ม */
        }

        /* Responsive */
        @media (max-width: 992px) {
            .checkout-container {
                grid-template-columns: 1fr; /* เปลี่ยนเป็น 1 คอลัมน์สำหรับหน้าจอขนาดเล็ก */
                gap: 1.5rem;
            }

            .order-summary {
                position: static; /* ยกเลิก sticky */
            }

            .header h1 {
                font-size: 2rem;
            }

            .checkout-form h2,
            .order-summary h2 {
                font-size: 1.5rem;
            }

            .btn {
                padding: 0.7rem 1.2rem;
                font-size: 1rem;
            }

            .form-actions {
                flex-direction: column; /* เปลี่ยนปุ่มให้อยู่ในแนวตั้ง */
                align-items: stretch; /* ขยายเต็มความกว้าง */
            }

            .form-actions .btn {
                max-width: none; /* ยกเลิก max-width */
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 10px;
            }

            .checkout-form,
            .order-summary {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ชำระเงิน - Toom Tam Fishing</h1>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="checkout-container">
            <div class="checkout-form">
                <h2>ข้อมูลการจัดส่ง</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="customer_name">ชื่อ-นามสกุล <span style="color: red;">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" required 
                               value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>"
                               placeholder="กรอกชื่อ-นามสกุลของคุณ">
                    </div>

                    <div class="form-group">
                        <label for="customer_email">อีเมล <span style="color: red;">*</span></label>
                        <input type="email" id="customer_email" name="customer_email" required 
                               value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>"
                               placeholder="กรอกอีเมลของคุณ">
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
                        <input type="tel" id="customer_phone" name="customer_phone" required 
                               value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>"
                               placeholder="กรอกเบอร์โทรศัพท์ของคุณ">
                    </div>

                    <div class="form-group">
                        <label for="customer_address">ที่อยู่จัดส่ง <span style="color: red;">*</span></label>
                        <textarea id="customer_address" name="customer_address" required 
                                  placeholder="บ้านเลขที่ หมู่ที่ ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์"><?php echo htmlspecialchars($_POST['customer_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="cart.php" class="btn btn-secondary">กลับไปตะกร้า</a>
                        <button type="submit" class="btn btn-primary">ยืนยันการสั่งซื้อ</button>
                    </div>
                </form>
            </div>

            <div class="order-summary">
                <h2>สรุปคำสั่งซื้อ</h2>
                <?php if (empty($cart_items)): ?>
                    <p style="text-align: center; color: #777;">ไม่พบสินค้าในตะกร้า</p>
                    <p style="text-align: center; margin-top: 10px;"><a href="index.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 20px;">เลือกซื้อสินค้า</a></p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['product']['name']); ?></div>
                                <div class="item-details">
                                    <?php echo number_format($item['product']['price'], 2); ?> บาท × <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <div class="item-price"><?php echo number_format($item['subtotal'], 2); ?> บาท</div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="order-total">
                        <div>ยอดรวมทั้งสิ้น</div>
                        <div class="item-price"><?php echo number_format($total, 2); ?> บาท</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>