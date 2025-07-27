<?php
session_start();
require_once 'config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// ดึงข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// อัพเดตข้อมูลผู้ใช้
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_email)) {
        $error_message = 'กรุณากรอกอีเมล';
    } else {
        // อัพเดตอีเมล
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$new_email, $user_id]);
        
        // อัพเดตรหัสผ่าน (ถ้ามี)
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error_message = 'รหัสผ่านไม่ตรงกัน';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
            }
        }
        
        if (empty($error_message)) {
            $success_message = 'อัพเดตข้อมูลสำเร็จ';
            // ดึงข้อมูลใหม่
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }
}

// ดึงประวัติการสั่งซื้อ
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
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบัญชี - Toom Tam Fishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #2c3e50 !important;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">🎣 Toom Tam Fishing</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">หน้าแรก</a>
                <a class="nav-link" href="cart.php">ตะกร้า</a>
                <a class="nav-link active" href="account.php">บัญชี</a>
                <a class="nav-link" href="logout.php">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <!-- ข้อมูลผู้ใช้ -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">ข้อมูลส่วนตัว</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">อีเมล</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">รหัสผ่านใหม่ (ถ้าต้องการเปลี่ยน)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                อัพเดตข้อมูล
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- ประวัติการสั่งซื้อ -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">ประวัติการสั่งซื้อ</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <p class="text-muted">ยังไม่มีประวัติการสั่งซื้อ</p>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6>คำสั่งซื้อ #<?php echo $order['id']; ?></h6>
                                            <p class="text-muted small mb-1">
                                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                            </p>
                                            <p class="mb-1"><?php echo htmlspecialchars($order['items']); ?></p>
                                            <strong>฿<?php echo number_format($order['total'], 2); ?></strong>
                                        </div>
                                        <div>
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
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>