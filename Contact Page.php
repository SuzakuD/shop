<?php
require_once 'config.php';

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

$message = '';
$message_type = '';

// ประมวลผลฟอร์มติดต่อ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $message_type = 'error';
    } else {
        // ในสถานการณ์จริง ควรส่งอีเมลหรือบันทึกลงฐานข้อมูล
        // สำหรับตัวอย่างนี้ แสดงข้อความสำเร็จ
        $message = 'ขอบคุณที่ติดต่อเรา เราจะติดต่อกลับโดยเร็วที่สุด';
        $message_type = 'success';
        
        // ล้างฟอร์ม
        $name = $email = $phone = $subject = $message_text = '';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา - Toom Tam Fishing</title>
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

        /* Header */
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

        .nav-menu a:hover,
        .nav-menu a.active {
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

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-title {
            text-align: center;
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 40px;
        }

        /* Contact Content */
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .contact-info {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .contact-info h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: start;
            margin-bottom: 20px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-text h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .info-text p {
            color: #7f8c8d;
            margin: 0;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .contact-form h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }

        /* Map Section */
        .map-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .map-container {
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            height: 400px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 1.2rem;
        }

        /* Messages */
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

        /* Social Media */
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-link:hover {
            background: #3498db;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-fish"></i> Toom Tam Fishing
            </a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> ตะกร้า</a></li>
                    <li><a href="contact.php" class="active"><i class="fas fa-phone"></i> ติดต่อ</a></li>
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
        <h1 class="page-title">ติดต่อเรา</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="contact-content">
            <!-- Contact Information -->
            <div class="contact-info">
                <h2>ข้อมูลการติดต่อ</h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-text">
                        <h3>ที่อยู่</h3>
                        <p>123 ถนนริมน้ำ ตำบลท่าน้ำ<br>
                           อำเภอเมือง จังหวัดสมุทรปราการ 10270</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-text">
                        <h3>เบอร์โทรศัพท์</h3>
                        <p>02-123-4567<br>
                           081-234-5678 (มือถือ)</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-text">
                        <h3>อีเมล</h3>
                        <p>info@toomtamfishing.com<br>
                           support@toomtamfishing.com</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-text">
                        <h3>เวลาทำการ</h3>
                        <p>จันทร์ - เสาร์: 09:00 - 18:00<br>
                           อาทิตย์: 10:00 - 16:00</p>
                    </div>
                </div>

                <div class="social-links">
                    <a href="#" class="social-link" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link" title="Line">
                        <i class="fab fa-line"></i>
                    </a>
                    <a href="#" class="social-link" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link" title="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2>ส่งข้อความถึงเรา</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">ชื่อ-นามสกุล *</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">อีเมล *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject">หัวข้อ *</label>
                        <input type="text" class="form-control" id="subject" name="subject" required
                               value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">ข้อความ *</label>
                        <textarea class="form-control" id="message" name="message" required><?php echo isset($message_text) ? htmlspecialchars($message_text) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> ส่งข้อความ
                    </button>
                </form>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2>แผนที่ร้าน</h2>
            <div class="map-container">
                <i class="fas fa-map-marked-alt fa-3x"></i>
                <p>แผนที่ Google Maps</p>
            </div>
        </div>
    </div>
</body>
</html>