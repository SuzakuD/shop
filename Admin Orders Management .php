<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าเป็น admin หรือไม่
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

$message = '';

// อัพเดตสถานะคำสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $message = 'อัพเดตสถานะคำสั่งซื้อเรียบร้อยแล้ว';
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// ดึงข้อมูลคำสั่งซื้อ
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($filter_status) {
    $where .= " AND o.status = ?";
    $params[] = $filter_status;
}

if ($search) {
    $where .= " AND (o.id = ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// นับจำนวนคำสั่งซื้อทั้งหมด
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    $where
");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// ดึงข้อมูลคำสั่งซื้อ
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email,
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(CONCAT(p.name, ' x', oi.quantity) SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// ดึงสถิติสถานะคำสั่งซื้อ
$status_stats = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $stmt->fetch()) {
    $status_stats[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - Toom Tam Fishing Admin</title>
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
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
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
        .status-cancelled { background-color: #dc3545; color: white; }
        .status-delivered { background-color: #6c757d; color: white; }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 25px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }
        .filter-tab:hover {
            background: #e0e0e0;
            color: #333;
        }
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .order-detail-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
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
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_products.php">
                                <i class="fas fa-box"></i> จัดการสินค้า
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_orders.php">
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
                            <a class="nav-link" href="admin_promotions.php">
                                <i class="fas fa-percentage"></i> จัดการโปรโมชั่น
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
                    <h2 class="mb-4">จัดการคำสั่งซื้อ</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <a href="admin_orders.php" class="filter-tab <?php echo !$filter_status ? 'active' : ''; ?>">
                            ทั้งหมด (<?php echo $total_orders; ?>)
                        </a>
                        <a href="?status=pending" class="filter-tab <?php echo $filter_status == 'pending' ? 'active' : ''; ?>">
                            รอดำเนินการ (<?php echo $status_stats['pending'] ?? 0; ?>)
                        </a>
                        <a href="?status=paid" class="filter-tab <?php echo $filter_status == 'paid' ? 'active' : ''; ?>">
                            จ่ายแล้ว (<?php echo $status_stats['paid'] ?? 0; ?>)
                        </a>
                        <a href="?status=shipped" class="filter-tab <?php echo $filter_status == 'shipped' ? 'active' : ''; ?>">
                            จัดส่งแล้ว (<?php echo $status_stats['shipped'] ?? 0; ?>)
                        </a>
                        <a href="?status=cancelled" class="filter-tab <?php echo $filter_status == 'cancelled' ? 'active' : ''; ?>">
                            ยกเลิก (<?php echo $status_stats['cancelled'] ?? 0; ?>)
                        </a>
                    </div>

                    <!-- Search -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="ค้นหาด้วยเลขคำสั่งซื้อ, ชื่อผู้ใช้, อีเมล" 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> ค้นหา
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>เลขที่</th>
                                            <th>ลูกค้า</th>
                                            <th>สินค้า</th>
                                            <th>ยอดรวม</th>
                                            <th>สถานะ</th>
                                            <th>วันที่สั่ง</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <?php if ($order['username']): ?>
                                                        <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                                    <?php else: ?>
                                                        <em>ผู้ใช้ทั่วไป</em>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($order['items'], 0, 50)); ?>
                                                    <?php echo strlen($order['items']) > 50 ? '...' : ''; ?></small>
                                                    <br>
                                                    <span class="badge bg-secondary"><?php echo $order['item_count']; ?> รายการ</span>
                                                </td>
                                                <td><strong><?php echo formatPrice($order['total']); ?></strong></td>
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
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                                    ก่อนหน้า
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                                    ถัดไป
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับดูรายละเอียดคำสั่งซื้อ -->
    <div class="modal fade order-detail-modal" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รายละเอียดคำสั่งซื้อ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <!-- จะแสดงข้อมูลจาก AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับอัพเดตสถานะ -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">อัพเดตสถานะคำสั่งซื้อ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="order_id" id="update_order_id">
                        
                        <div class="mb-3">
                            <label class="form-label">สถานะ</label>
                            <select class="form-select" name="status" id="update_status_select">
                                <option value="pending">รอดำเนินการ</option>
                                <option value="paid">จ่ายแล้ว</option>
                                <option value="shipped">จัดส่งแล้ว</option>
                                <option value="delivered">ส่งถึงแล้ว</option>
                                <option value="cancelled">ยกเลิก</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrderDetail(orderId) {
            fetch('get_order_detail.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
                });
        }

        function updateStatus(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('update_status_select').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
    </script>
</body>
</html>