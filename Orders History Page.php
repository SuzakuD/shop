<?php
session_start();
require_once 'config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// ดึงประวัติคำสั่งซื้อ
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// นับจำนวนคำสั่งซื้อทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// ดึงข้อมูลคำสั่งซื้อ
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(CONCAT(p.name, ' x', oi.quantity) SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $limit, $offset]);
$orders = $stmt->fetchAll();

// ฟังก์ชันดึงรายละเอียดคำสั่งซื้อสำหรับ Modal
if (isset($_GET['order_detail'])) {
    $order_id = (int)$_GET['order_detail'];
    
    // ตรวจสอบว่าเป็นคำสั่งซื้อของผู้ใช้นี้
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.description 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        // ส่งข้อมูลเป็น JSON
        header('Content-Type: application/json');
        echo json_encode([
            'order' => $order,
            'items' => $items
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติคำสั่งซื้อ - Toom Tam Fishing</title>
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

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #7f8c8d;
        }

        /* Orders Table */
        .orders-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .order-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .order-info h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending { background-color: #ffc107; color: #333; }
        .status-paid { background-color: #28a745; color: white; }
        .status-shipped { background-color: #17a2b8; color: white; }
        .status-delivered { background-color: #6c757d; color: white; }
        .status-cancelled { background-color: #dc3545; color: white; }

        .order-items {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.8;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .btn-view-detail {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view-detail:hover {
            background: #2980b9;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #95a5a6;
            margin-bottom: 20px;
        }

        .btn-shop {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-shop:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #2c3e50;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: #2c3e50;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
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
            
            .order-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .order-footer {
                flex-direction: column;
                gap: 10px;
                align-items: start;
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
                    <li><a href="contact.php"><i class="fas fa-phone"></i> ติดต่อ</a></li>
                    <li><a href="orders.php" class="active"><i class="fas fa-receipt"></i> คำสั่งซื้อ</a></li>
                    <li><a href="account.php"><i class="fas fa-user"></i> บัญชี</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
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
        <div class="page-header">
            <h1 class="page-title">ประวัติคำสั่งซื้อ</h1>
            <p class="page-subtitle">ดูรายละเอียดและติดตามสถานะคำสั่งซื้อของคุณ</p>
        </div>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-basket"></i>
                    <h3>ยังไม่มีประวัติคำสั่งซื้อ</h3>
                    <p>เริ่มช้อปปิ้งกับเราเพื่อรับสินค้าคุณภาพสำหรับการตกปลา</p>
                    <a href="index.php" class="btn-shop">
                        <i class="fas fa-shopping-cart"></i> เริ่มช้อปปิ้ง
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>คำสั่งซื้อ #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                <p class="order-date">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php
                                switch($order['status']) {
                                    case 'pending': echo 'รอดำเนินการ'; break;
                                    case 'paid': echo 'จ่ายแล้ว'; break;
                                    case 'shipped': echo 'จัดส่งแล้ว'; break;
                                    case 'delivered': echo 'ส่งถึงแล้ว'; break;
                                    case 'cancelled': echo 'ยกเลิก'; break;
                                    default: echo $order['status'];
                                }
                                ?>
                            </span>
                        </div>

                        <div class="order-items">
                            <strong>สินค้า:</strong> <?php echo htmlspecialchars($order['items']); ?>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-box"></i> <?php echo $order['item_count']; ?> รายการ
                            </small>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                ยอดรวม: <?php echo formatPrice($order['total']); ?>
                            </div>
                            <button class="btn-view-detail" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i> ดูรายละเอียด
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>">&laquo; ก่อนหน้า</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>">ถัดไป &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียด -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>รายละเอียดคำสั่งซื้อ</h2>
                <span class="close">&times;</span>
            </div>
            <div id="modalBody">
                <!-- รายละเอียดจะแสดงที่นี่ -->
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('orderModal');
        const span = document.getElementsByClassName('close')[0];

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function viewOrderDetail(orderId) {
            fetch('orders.php?order_detail=' + orderId)
                .then(response => response.json())
                .then(data => {
                    const order = data.order;
                    const items = data.items;
                    
                    let html = `
                        <div style="margin-bottom: 20px;">
                            <p><strong>เลขที่คำสั่งซื้อ:</strong> #${String(order.id).padStart(6, '0')}</p>
                            <p><strong>วันที่สั่งซื้อ:</strong> ${new Date(order.created_at).toLocaleString('th-TH')}</p>
                            <p><strong>สถานะ:</strong> <span class="status-badge status-${order.status}">${getStatusText(order.status)}</span></p>
                        </div>
                        
                        <h3>รายการสินค้า</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #ddd;">
                                    <th style="padding: 10px; text-align: left;">สินค้า</th>
                                    <th style="padding: 10px; text-align: center;">ราคา</th>
                                    <th style="padding: 10px; text-align: center;">จำนวน</th>
                                    <th style="padding: 10px; text-align: right;">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    items.forEach(item => {
                        const subtotal = item.price * item.quantity;
                        html += `
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;">
                                    <strong>${item.name}</strong><br>
                                    <small style="color: #666;">${item.description}</small>
                                </td>
                                <td style="padding: 10px; text-align: center;">${formatPrice(item.price)}</td>
                                <td style="padding: 10px; text-align: center;">${item.quantity}</td>
                                <td style="padding: 10px; text-align: right;">${formatPrice(subtotal)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">ยอดรวมทั้งสิ้น:</td>
                                    <td style="padding: 10px; text-align: right; font-weight: bold; color: #e74c3c; font-size: 1.2rem;">
                                        ${formatPrice(order.total)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                    
                    document.getElementById('modalBody').innerHTML = html;
                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                });
        }

        function getStatusText(status) {
            const statusMap = {
                'pending': 'รอดำเนินการ',
                'paid': 'จ่ายแล้ว',
                'shipped': 'จัดส่งแล้ว',
                'delivered': 'ส่งถึงแล้ว',
                'cancelled': 'ยกเลิก'
            };
            return statusMap[status] || status;
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price) + ' บาท';
        }
    </script>
</body>
</html>