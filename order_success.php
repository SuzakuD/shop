<?php
session_start();

// ตรวจสอบว่ามีข้อมูลการสั่งซื้อหรือไม่
if (!isset($_SESSION['order_success'])) {
    header('Location: index.php');
    exit();
}

$order_data = $_SESSION['order_success'];
unset($_SESSION['order_success']); // ล้างข้อมูลหลังแสดงผล
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ - Toom Tam Fishing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #00b894;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            animation: checkmark 0.6s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.3);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-title {
            color: #2d3436;
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .success-subtitle {
            color: #636e72;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .order-details {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin: 2rem 0;
            text-align: left;
        }

        .order-details h3 {
            color: #2d3436;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            color: #00b894;
            font-size: 1.1rem;
        }

        .detail-label {
            color: #636e72;
            font-weight: 500;
        }

        .detail-value {
            color: #2d3436;
            font-weight: 500;
        }

        .customer-info {
            background: #e8f4f8;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1rem 0;
            text-align: left;
        }

        .customer-info h4 {
            color: #2d3436;
            margin-bottom: 1rem;
        }

        .customer-info p {
            color: #636e72;
            margin-bottom: 0.5rem;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(116, 185, 255, 0.4);
        }

        .btn-secondary {
            background: #ddd;
            color: #2d3436;
        }

        .btn-secondary:hover {
            background: #bbb;
        }

        .notice {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 1rem 0;
        }

        @media (max-width: 768px) {
            .success-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .success-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            ✓
        </div>
        
        <h1 class="success-title">สั่งซื้อสำเร็จ!</h1>
        <p class="success-subtitle">
            ขอบคุณที่ไว้วางใจ Toom Tam Fishing ทางร้านจะดำเนินการจัดส่งสินค้าในอีก 1-2 วันทำการ
        </p>

        <div class="order-details">
            <h3>รายละเอียดคำสั่งซื้อ</h3>
            <div class="detail-row">
                <span class="detail-label">เลขที่คำสั่งซื้อ:</span>
                <span class="detail-value">#<?php echo str_pad($order_data['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">วันที่สั่งซื้อ:</span>
                <span class="detail-value"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">ยอดรวม:</span>
                <span class="detail-value"><?php echo number_format($order_data['total'], 2); ?> บาท</span>
            </div>
        </div>

        <div class="customer-info">
            <h4>ข้อมูลการจัดส่ง</h4>
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($order_data['customer_name']); ?></p>
            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order_data['customer_email']); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order_data['customer_phone']); ?></p>
            <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($order_data['customer_address']); ?></p>
        </div>

        <div class="notice">
            <strong>หมายเหตุ:</strong> กรุณาเก็บหมายเลขคำสั่งซื้อไว้สำหรับติดตามสถานะการจัดส่ง
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-primary">กลับหน้าแรก</a>
            <a href="javascript:window.print()" class="btn btn-secondary">พิมพ์ใบเสร็จ</a>
        </div>
    </div>

    <script>
        // เอฟเฟกต์แสดงผล
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.success-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>