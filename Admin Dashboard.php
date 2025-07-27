<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าเป็น admin หรือไม่ (ในที่นี้ใช้ username = 'admin')
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pdo = getConnection();

// ตรวจสอบว่าเป็น admin
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['username'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// สถิติต่างๆ
$stats = [];

// ยอดขายวันนี้
$stmt = $pdo->prepare("SELECT SUM(total) as daily_sales FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['daily_sales'] = $stmt->fetchColumn() ?: 0;

// ยอดขายเดือนนี้
$stmt = $pdo->prepare("SELECT SUM(total) as monthly_sales FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->execute();
$stats['monthly_sales'] = $stmt->fetchColumn() ?: 0;

// จำนวนคำสั่งซื้อทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders");
$stmt->execute();
$stats['total_orders'] = $stmt->fetchColumn();

// จำนวนสินค้าทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM products");
$stmt->execute();
$stats['total_products'] = $stmt->fetchColumn();

// จำนวนสมาชิก
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetchColumn();

// สินค้าขายดี Top 5
$stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_sold, p.price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute();
$best_sellers = $stmt->fetchAll();

// คำสั่งซื้อล่าสุด
$stmt = $pdo->prepare("
    SELECT o.*, u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// สินค้าสต็อกต่ำ (น้อยกว่า 10)
$stmt = $pdo->prepare("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC");
$stmt->execute();
$low_stock = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แอดมิน - Toom Tam Fishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: white;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background-color: #ffc107; color: #333; }
        .status-paid { background-color: #28a745; color: white; }
        .status-shipped { background-color: #17a2b8; color: white; }
        .status-cancelled { background-color: #dc3545; color: white; }
        .low-stock { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar p-3">
                    <h4 class="mb-4">
                        <i class="fas fa-fish"></i> Toom Tam Admin
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="admin.php">
                                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_products.php">
                                <i class="fas fa-box"></i> จัดการสินค้า
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_orders.php">
                                <i class="fas fa-shopping-cart"></i> จัดการคำสั่งซื้อ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_categories.php">
                                <i class="fas fa-tags"></i> จัดการหมวดหมู่
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_users.php">
                                <i class="fas fa-users"></i> จัดการสมาชิก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar"></i> รายงาน
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home"></i> กลับหน้าร้าน
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">
                    <h2 class="mb-4">แดshboard</h2>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($stats['daily_sales'], 2); ?></div>
                                <div class="stat-label">ยอดขายวันนี้ (บาท)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($stats['monthly_sales'], 2); ?></div>
                                <div class="stat-label">ยอดขายเดือนนี้ (บาท)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                                <div class="stat-label">สินค้าทั้งหมด</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- สินค้าขายดี -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">สินค้าขายดี Top 5</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($best_sellers)): ?>
                                        <p class="text-muted">ยังไม่มีข้อมูลการขาย</p>
                                    <?php else: ?>
                                        <?php foreach ($best_sellers as $item): ?>
                                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo formatPrice($item['price']); ?></small>
                                                </div>
                                                <span class="badge bg-success"><?php echo $item['total_sold']; ?> ชิ้น</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- คำสั่งซื้อล่าสุด -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">คำสั่งซื้อล่าสุด</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_orders)): ?>
                                        <p class="text-muted">ยังไม่มีคำสั่งซื้อ</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                <div>
                                                    <strong>#<?php echo $order['id']; ?></strong>
                                                    <?php if ($order['username']): ?>
                                                        <br><small><?php echo htmlspecialchars($order['username']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <div><?php echo formatPrice($order['total']); ?></div>
                                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                                        <?php
                                                        switch($order['status']) {
                                                            case 'pending': echo 'รอดำเนินการ'; break;
                                                            case 'paid': echo 'จ่ายแล้ว'; break;
                                                            case 'shipped': echo 'จัดส่งแล้ว'; break;
                                                            case 'cancelled': echo 'ยกเลิก'; break;
                                                            default: echo $order['status'];
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- สินค้าสต็อกต่ำ -->
                    <?php if (!empty($low_stock)): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">
                                            <i class="fas fa-exclamation-triangle"></i> สินค้าสต็อกต่ำ
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>ชื่อสินค้า</th>
                                                        <th>ราคา</th>
                                                        <th>สต็อกคงเหลือ</th>
                                                        <th>จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($low_stock as $product): ?>
                                                        <tr class="<?php echo $product['stock'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                            <td><?php echo formatPrice($product['price']); ?></td>
                                                            <td>
                                                                <span class="badge <?php echo $product['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                                    <?php echo $product['stock']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <a href="admin_products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                                    เพิ่มสต็อก
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>