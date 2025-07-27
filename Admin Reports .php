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

// กำหนดช่วงเวลา
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// กำหนดวันที่เริ่มต้นและสิ้นสุดตาม period
switch ($period) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        $start_date = $custom_start ?: date('Y-m-01');
        $end_date = $custom_end ?: date('Y-m-d');
        break;
    default:
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
}

// สรุปยอดขาย
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as order_count,
        SUM(total) as total_sales,
        AVG(total) as avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$sales_summary = $stmt->fetch();

// ยอดขายตามสถานะ
$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(total) as total
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start_date, $end_date]);
$sales_by_status = $stmt->fetchAll();

// สินค้าขายดี
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue,
        COUNT(DISTINCT oi.order_id) as order_count
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$best_sellers = $stmt->fetchAll();

// หมวดหมู่ขายดี
$stmt = $pdo->prepare("
    SELECT 
        c.name as category_name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN product_category pc ON p.id = pc.product_id
    JOIN categories c ON pc.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY revenue DESC
");
$stmt->execute([$start_date, $end_date]);
$category_sales = $stmt->fetchAll();

// ยอดขายรายวัน (สำหรับกราฟ)
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as order_count,
        SUM(total) as daily_total
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY sale_date
");
$stmt->execute([$start_date, $end_date]);
$daily_sales = $stmt->fetchAll();

// ลูกค้าที่ซื้อมากที่สุด
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        COUNT(o.id) as order_count,
        SUM(o.total) as total_spent
    FROM users u
    JOIN orders o ON u.id = o.user_id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_customers = $stmt->fetchAll();

// สินค้าคงเหลือและมูลค่า
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(stock) as total_stock,
        SUM(stock * price) as inventory_value
    FROM products
    WHERE stock > 0
");
$inventory_summary = $stmt->fetch();

// สินค้าสต็อกต่ำ
$stmt = $pdo->query("
    SELECT * FROM products 
    WHERE stock < 10 
    ORDER BY stock ASC 
    LIMIT 10
");
$low_stock_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - Toom Tam Fishing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .period-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .period-btn:hover {
            background: #f0f0f0;
            color: #333;
        }
        .period-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .table-sm {
            font-size: 0.9rem;
        }
        .top-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .top-item:last-child {
            border-bottom: none;
        }
        .rank {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
        }
        .rank.gold { background: #ffd700; color: #333; }
        .rank.silver { background: #c0c0c0; color: #333; }
        .rank.bronze { background: #cd7f32; color: white; }
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
                            <a class="nav-link" href="admin_promotions.php">
                                <i class="fas fa-percentage"></i> จัดการโปรโมชั่น
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_reports.php">
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
                    <h2 class="mb-4">รายงานการขาย</h2>
                    
                    <!-- Period Selector -->
                    <div class="period-selector">
                        <a href="?period=today" class="period-btn <?php echo $period == 'today' ? 'active' : ''; ?>">วันนี้</a>
                        <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">7 วันล่าสุด</a>
                        <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">เดือนนี้</a>
                        <a href="?period=year" class="period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">ปีนี้</a>
                        <a href="#" class="period-btn <?php echo $period == 'custom' ? 'active' : ''; ?>" 
                           onclick="document.getElementById('customDateForm').style.display='block'; return false;">กำหนดเอง</a>
                    </div>

                    <!-- Custom Date Form -->
                    <div id="customDateForm" style="<?php echo $period == 'custom' ? 'display:block;' : 'display:none;'; ?>">
                        <form method="GET" class="card p-3 mb-3">
                            <input type="hidden" name="period" value="custom">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>วันที่เริ่มต้น</label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="<?php echo $period == 'custom' ? $start_date : ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>วันที่สิ้นสุด</label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="<?php echo $period == 'custom' ? $end_date : ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block">แสดงรายงาน</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Period Display -->
                    <div class="alert alert-info">
                        <i class="fas fa-calendar"></i> แสดงข้อมูลตั้งแต่ <?php echo date('d/m/Y', strtotime($start_date)); ?> 
                        ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo formatPrice($sales_summary['total_sales'] ?: 0); ?></div>
                                <div class="stat-label">ยอดขายรวม</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $sales_summary['order_count'] ?: 0; ?></div>
                                <div class="stat-label">จำนวนคำสั่งซื้อ</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo formatPrice($sales_summary['avg_order_value'] ?: 0); ?></div>
                                <div class="stat-label">มูลค่าเฉลี่ย/คำสั่งซื้อ</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo formatPrice($inventory_summary['inventory_value'] ?: 0); ?></div>
                                <div class="stat-label">มูลค่าสินค้าคงคลัง</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Sales Chart -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">กราฟยอดขายรายวัน</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales by Status -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">ยอดขายตามสถานะ</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart"></canvas>
                                    <table class="table table-sm mt-3">
                                        <?php foreach ($sales_by_status as $status): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    switch($status['status']) {
                                                        case 'pending': echo 'รอดำเนินการ'; break;
                                                        case 'paid': echo 'จ่ายแล้ว'; break;
                                                        case 'shipped': echo 'จัดส่งแล้ว'; break;
                                                        case 'delivered': echo 'ส่งถึงแล้ว'; break;
                                                        case 'cancelled': echo 'ยกเลิก'; break;
                                                        default: echo $status['status'];
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-end"><?php echo $status['count']; ?> รายการ</td>
                                                <td class="text-end"><?php echo formatPrice($status['total']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <!-- Best Selling Products -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">สินค้าขายดี Top 10</h5>
                                </div>
                                <div class="card-body">
                                    <?php $rank = 1; ?>
                                    <?php foreach ($best_sellers as $product): ?>
                                        <div class="top-item">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rank <?php echo $rank <= 3 ? ['gold', 'silver', 'bronze'][$rank-1] : ''; ?>">
                                                    <?php echo $rank; ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                    <small class="text-muted">
                                                        ขาย <?php echo $product['total_sold']; ?> ชิ้น | 
                                                        รายได้ <?php echo formatPrice($product['revenue']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $rank++; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Top Customers -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">ลูกค้าที่ซื้อมากที่สุด Top 10</h5>
                                </div>
                                <div class="card-body">
                                    <?php $rank = 1; ?>
                                    <?php foreach ($top_customers as $customer): ?>
                                        <div class="top-item">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rank <?php echo $rank <= 3 ? ['gold', 'silver', 'bronze'][$rank-1] : ''; ?>">
                                                    <?php echo $rank; ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($customer['username']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo $customer['order_count']; ?> คำสั่งซื้อ | 
                                                        ยอดซื้อ <?php echo formatPrice($customer['total_spent']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $rank++; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Sales -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">ยอดขายตามหมวดหมู่</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Low Stock Alert -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="fas fa-exclamation-triangle"></i> สินค้าสต็อกต่ำ
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>สินค้า</th>
                                                    <th class="text-center">คงเหลือ</th>
                                                    <th class="text-end">มูลค่า</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($low_stock_products as $product): ?>
                                                    <tr class="<?php echo $product['stock'] == 0 ? 'table-danger' : ''; ?>">
                                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                        <td class="text-center">
                                                            <span class="badge <?php echo $product['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                                <?php echo $product['stock']; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end"><?php echo formatPrice($product['price']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesData = <?php echo json_encode($daily_sales); ?>;
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesData.map(d => {
                    const date = new Date(d.sale_date);
                    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
                }),
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: salesData.map(d => d.daily_total),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($sales_by_status); ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => {
                    switch(s.status) {
                        case 'pending': return 'รอดำเนินการ';
                        case 'paid': return 'จ่ายแล้ว';
                        case 'shipped': return 'จัดส่งแล้ว';
                        case 'delivered': return 'ส่งถึงแล้ว';
                        case 'cancelled': return 'ยกเลิก';
                        default: return s.status;
                    }
                }),
                datasets: [{
                    data: statusData.map(s => s.total),
                    backgroundColor: [
                        '#ffc107',
                        '#28a745',
                        '#17a2b8',
                        '#6c757d',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_sales); ?>;
        
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.map(c => c.category_name),
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: categoryData.map(c => c.revenue),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>