<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าเป็น admin หรือไม่
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$pdo = getConnection();

// ตรวจสอบว่าเป็น admin
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['username'] !== 'admin') {
    exit('Unauthorized');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    exit('Invalid order ID');
}

// ดึงข้อมูลคำสั่งซื้อ
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    exit('Order not found');
}

// ดึงรายการสินค้าในคำสั่งซื้อ
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.description 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// สร้าง HTML สำหรับแสดงรายละเอียด
?>
<div class="order-detail">
    <div class="row mb-3">
        <div class="col-md-6">
            <h6>ข้อมูลคำสั่งซื้อ</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>เลขที่คำสั่งซื้อ:</strong></td>
                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                </tr>
                <tr>
                    <td><strong>วันที่สั่งซื้อ:</strong></td>
                    <td><?php echo date('d/m/Y H:i:s', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <td><strong>สถานะ:</strong></td>
                    <td>
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
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6>ข้อมูลลูกค้า</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>ชื่อผู้ใช้:</strong></td>
                    <td><?php echo htmlspecialchars($order['username'] ?: 'ผู้ใช้ทั่วไป'); ?></td>
                </tr>
                <tr>
                    <td><strong>อีเมล:</strong></td>
                    <td><?php echo htmlspecialchars($order['email'] ?: '-'); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <h6>รายการสินค้า</h6>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>สินค้า</th>
                <th class="text-center">ราคา</th>
                <th class="text-center">จำนวน</th>
                <th class="text-end">รวม</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                    </td>
                    <td class="text-center"><?php echo formatPrice($item['price']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">ยอดรวมทั้งสิ้น:</th>
                <th class="text-end text-primary"><?php echo formatPrice($order['total']); ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-pending { background-color: #ffc107; color: #333; }
    .status-paid { background-color: #28a745; color: white; }
    .status-shipped { background-color: #17a2b8; color: white; }
    .status-delivered { background-color: #6c757d; color: white; }
    .status-cancelled { background-color: #dc3545; color: white; }
</style>