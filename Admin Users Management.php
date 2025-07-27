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

// จัดการ actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($username) || empty($email) || empty($password)) {
                $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            } else {
                // ตรวจสอบว่า username หรือ email ซ้ำหรือไม่
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->fetch()) {
                    $message = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    
                    if ($stmt->execute([$username, $email, $hashed_password])) {
                        $message = 'เพิ่มสมาชิกเรียบร้อยแล้ว';
                    } else {
                        $message = 'เกิดข้อผิดพลาดในการเพิ่มสมาชิก';
                    }
                }
            }
        } elseif ($action == 'reset_password') {
            $user_id = $_POST['user_id'];
            $new_password = $_POST['new_password'];
            
            if (empty($new_password)) {
                $message = 'กรุณากรอกรหัสผ่านใหม่';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $message = 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน';
                }
            }
        } elseif ($action == 'delete') {
            $user_id = $_POST['user_id'];
            
            // ไม่ให้ลบ admin
            if ($user_id == $_SESSION['user_id']) {
                $message = 'ไม่สามารถลบบัญชีของตัวเองได้';
            } else {
                // ตรวจสอบว่ามีคำสั่งซื้อหรือไม่
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $order_count = $stmt->fetchColumn();
                
                if ($order_count > 0) {
                    $message = 'ไม่สามารถลบสมาชิกนี้ได้ เนื่องจากมีประวัติคำสั่งซื้อ';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        $message = 'ลบสมาชิกเรียบร้อยแล้ว';
                    } else {
                        $message = 'เกิดข้อผิดพลาดในการลบสมาชิก';
                    }
                }
            }
        }
    }
}

// ค้นหาและกรองข้อมูล
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// นับจำนวนสมาชิกทั้งหมด
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// ดึงข้อมูลสมาชิก
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT o.id) as order_count,
           COALESCE(SUM(o.total), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// สถิติสมาชิก
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as new_today FROM users WHERE DATE(created_at) = CURDATE()");
$stats['new_today'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as new_month FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['new_month'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - Toom Tam Fishing Admin</title>
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
        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            color: white;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
        }
        .admin-badge {
            background: #e74c3c;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
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
                            <a class="nav-link active" href="admin_users.php">
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
                    <h2 class="mb-4">จัดการสมาชิก</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- สถิติ -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">สมาชิกทั้งหมด</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['new_today']; ?></div>
                                <div class="stat-label">สมาชิกใหม่วันนี้</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['new_month']; ?></div>
                                <div class="stat-label">สมาชิกใหม่เดือนนี้</div>
                            </div>
                        </div>
                    </div>

                    <!-- เพิ่มสมาชิกและค้นหา -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <form method="GET" class="d-flex gap-2">
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="ค้นหาด้วยชื่อผู้ใช้ หรืออีเมล" 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> ค้นหา
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="fas fa-user-plus"></i> เพิ่มสมาชิก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ตารางสมาชิก -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>สมาชิก</th>
                                            <th>อีเมล</th>
                                            <th>วันที่สมัคร</th>
                                            <th>คำสั่งซื้อ</th>
                                            <th>ยอดซื้อรวม</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $member): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <?php echo strtoupper(substr($member['username'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <?php echo htmlspecialchars($member['username']); ?>
                                                            <?php if ($member['username'] == 'admin'): ?>
                                                                <span class="admin-badge">ADMIN</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($member['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $member['order_count']; ?></span>
                                                </td>
                                                <td><?php echo formatPrice($member['total_spent']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" 
                                                            onclick="resetPassword(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['username']); ?>')">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if ($member['username'] != 'admin' && $member['order_count'] == 0): ?>
                                                        <form method="POST" class="d-inline" 
                                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบสมาชิกนี้?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
                                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                                                    ก่อนหน้า
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
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

    <!-- Modal เพิ่มสมาชิก -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มสมาชิกใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">เพิ่มสมาชิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal รีเซ็ตรหัสผ่าน -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">รีเซ็ตรหัสผ่าน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        
                        <p>รีเซ็ตรหัสผ่านสำหรับ: <strong id="reset_username"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">รีเซ็ตรหัสผ่าน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetPassword(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
    </script>
</body>
</html>