<?php
session_start();
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    $_SESSION['message_type'] = 'error';
    header('Location: ../index.php');
    exit();
}

$pdo = getConnection();

// Get statistics
$stats = [];

// Total products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Total categories
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['total_categories'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$stats['total_users'] = $stmt->fetchColumn();

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->query("SELECT o.*, u.username FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดแอดมิน - Toom Tam Fishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .admin-menu-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .admin-menu-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-fish"></i> Toom Tam Fishing - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">กลับหน้าแรก</a>
                <a class="nav-link" href="../logout.php">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-tachometer-alt"></i> แดชบอร์ดแอดมิน</h2>
                <p class="text-muted">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?></p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-box fa-2x mb-2"></i>
                    <h3><?= $stats['total_products'] ?></h3>
                    <p>สินค้าทั้งหมด</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-tags fa-2x mb-2"></i>
                    <h3><?= $stats['total_categories'] ?></h3>
                    <p>หมวดหมู่สินค้า</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?= $stats['total_users'] ?></h3>
                    <p>สมาชิก</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                    <h3><?= $stats['total_orders'] ?></h3>
                    <p>คำสั่งซื้อ</p>
                </div>
            </div>
        </div>

        <!-- Management Menu -->
        <div class="row mb-5">
            <div class="col-12">
                <h4 class="mb-3">เมนูจัดการ</h4>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card admin-menu-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-3x text-primary mb-3"></i>
                        <h5>จัดการสินค้า</h5>
                        <p class="text-muted">เพิ่ม แก้ไข ลบสินค้า</p>
                        <a href="products.php" class="btn btn-primary">จัดการสินค้า</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card admin-menu-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-tags fa-3x text-success mb-3"></i>
                        <h5>จัดการหมวดหมู่</h5>
                        <p class="text-muted">จัดการหมวดหมู่สินค้า</p>
                        <a href="../index.php" class="btn btn-success">จัดการหมวดหมู่</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card admin-menu-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-3x text-warning mb-3"></i>
                        <h5>จัดการคำสั่งซื้อ</h5>
                        <p class="text-muted">ตรวจสอบและจัดการออเดอร์</p>
                        <a href="orders.php" class="btn btn-warning">จัดการคำสั่งซื้อ</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">คำสั่งซื้อล่าสุด</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ลูกค้า</th>
                                        <th>ยอดรวม</th>
                                        <th>สถานะ</th>
                                        <th>วันที่</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">ไม่มีคำสั่งซื้อ</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?= $order['id'] ?></td>
                                                <td><?= htmlspecialchars($order['username'] ?? 'ไม่ระบุ') ?></td>
                                                <td><?= formatPrice($order['total']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $order['status'] == 'paid' ? 'success' : 'warning' ?>">
                                                        <?= $order['status'] == 'paid' ? 'ชำระแล้ว' : 'รอชำระ' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>