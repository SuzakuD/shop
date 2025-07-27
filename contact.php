<?php
session_start();
require_once 'config.php';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // ในระบบจริง ควรส่งอีเมลหรือบันทึกลงฐานข้อมูล
    // ที่นี่จะแสดงข้อความสำเร็จเพื่อเป็นตัวอย่าง
    $_SESSION['contact_success'] = true;
    header('Location: contact.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา - Toomtam Fishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B35;
            --secondary: #F7931E;
            --dark: #2C3E50;
            --light: #ECF0F1;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .contact-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.1) 10px,
                rgba(255,255,255,0.1) 20px
            );
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            0% { transform: translate(-50%, -50%); }
            100% { transform: translate(0, 0); }
        }

        .contact-header h1 {
            position: relative;
            font-size: 3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .contact-info {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
            transition: transform 0.3s;
        }

        .contact-info:hover {
            transform: translateY(-5px);
        }

        .contact-info i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .contact-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }

        .btn-send {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 12px 40px;
            font-size: 1.1rem;
            border-radius: 25px;
            transition: transform 0.3s;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            color: white;
        }

        .map-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 30px;
            height: 400px;
        }

        .social-links {
            margin-top: 30px;
            text-align: center;
        }

        .social-links a {
            display: inline-block;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50%;
            margin: 0 10px;
            font-size: 1.5rem;
            transition: transform 0.3s;
        }

        .social-links a:hover {
            transform: translateY(-5px);
            color: white;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .working-hours {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .working-hours h5 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .working-hours p {
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish"></i> Toomtam Fishing
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">ติดต่อเรา</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contact Header -->
    <div class="contact-header">
        <div class="container">
            <h1>ติดต่อเรา</h1>
            <p class="lead">เรายินดีรับฟังความคิดเห็นและคำแนะนำจากคุณ</p>
        </div>
    </div>

    <!-- Contact Content -->
    <div class="container my-5">
        <?php if (isset($_SESSION['contact_success']) && $_SESSION['contact_success']): ?>
            <div class="success-message">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h4>ส่งข้อความเรียบร้อยแล้ว!</h4>
                <p>ทีมงานของเราจะติดต่อกลับโดยเร็วที่สุด ขอบคุณที่ติดต่อเรา</p>
            </div>
            <?php unset($_SESSION['contact_success']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Contact Info -->
            <div class="col-lg-4">
                <div class="contact-info text-center">
                    <i class="fas fa-map-marker-alt"></i>
                    <h4>ที่อยู่</h4>
                    <p>123 ถนนประมง<br>อำเภอเมือง จังหวัดพิษณุโลก<br>65000</p>
                </div>

                <div class="contact-info text-center">
                    <i class="fas fa-phone-alt"></i>
                    <h4>โทรศัพท์</h4>
                    <p>055-123-456<br>081-234-5678</p>
                </div>

                <div class="contact-info text-center">
                    <i class="fas fa-envelope"></i>
                    <h4>อีเมล</h4>
                    <p>info@toomtamfishing.com<br>support@toomtamfishing.com</p>
                </div>

                <div class="working-hours">
                    <h5><i class="fas fa-clock"></i> เวลาทำการ</h5>
                    <p><span>จันทร์ - ศุกร์</span><span>09:00 - 18:00</span></p>
                    <p><span>เสาร์</span><span>09:00 - 16:00</span></p>
                    <p><span>อาทิตย์</span><span>ปิดทำการ</span></p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="contact-form">
                    <h3 class="mb-4">ส่งข้อความถึงเรา</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ชื่อ-นามสกุล *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">อีเมล *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">หัวข้อ *</label>
                                <select class="form-control" name="subject" required>
                                    <option value="">เลือกหัวข้อ</option>
                                    <option value="สอบถามสินค้า">สอบถามสินค้า</option>
                                    <option value="ติดตามสินค้า">ติดตามสินค้า</option>
                                    <option value="แจ้งปัญหา">แจ้งปัญหา</option>
                                    <option value="ข้อเสนอแนะ">ข้อเสนอแนะ</option>
                                    <option value="อื่นๆ">อื่นๆ</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ข้อความ *</label>
                            <textarea class="form-control" name="message" rows="5" required></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-send">
                                <i class="fas fa-paper-plane"></i> ส่งข้อความ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Map -->
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3785.8961745732857!2d100.25878931531933!3d16.823972088439777!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30df9345d9c9f5e5%3A0x69e5a6a8d8f8a8a8!2sPhitsanulok!5e0!3m2!1sen!2sth!4v1234567890123" 
                width="100%" 
                height="100%" 
                style="border:0; border-radius: 10px;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>

        <!-- Social Links -->
        <div class="social-links">
            <h4 class="mb-4">ติดตามเราได้ที่</h4>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-line"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>