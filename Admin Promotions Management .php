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
$edit_promotion = null;

// ดำเนินการเมื่อมีการเพิ่ม/แก้ไข/ลบโปรโมชั่น
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add' || $action == 'edit') {
            $code = strtoupper(trim($_POST['code']));
            $description = $_POST['description'];
            $discount_type = $_POST['discount_type'];
            $discount_value = $_POST['discount_value'];
            $minimum_order = $_POST['minimum_order'] ?: 0;
            $usage_limit = $_POST['usage_limit'] ?: null;
            $start_date = $_POST['start_date'] ?: null;
            $end_date = $_POST['end_date'] ?: null;
            $status = $_POST['status'];
            
            try {
                if ($action == 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO promotions (code, description, discount_type, discount_value, 
                                              minimum_order, usage_limit, start_date, end_date, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$code, $description, $discount_type, $discount_value, 
                                   $minimum_order, $usage_limit, $start_date, $end_date, $status]);
                    $message = 'เพิ่มโปรโมชั่นเรียบร้อยแล้ว';
                } else {
                    $promotion_id = $_POST['promotion_id'];
                    $stmt = $pdo->prepare("
                        UPDATE promotions 
                        SET code = ?, description = ?, discount_type = ?, discount_value = ?,
                            minimum_order = ?, usage_limit = ?, start_date = ?, end_date = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$code, $description, $discount_type, $discount_value, 
                                   $minimum_order, $usage_limit, $start_date, $end_date, $status, $promotion_id]);
                    $message = 'แก้ไขโปรโมชั่นเรียบร้อยแล้ว';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = 'รหัสโปรโมชั่นนี้มีอยู่แล้ว';
                } else {
                    $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                }
            }
        } elseif ($action == 'delete') {
            $promotion_id = $_POST['promotion_id'];
            
            // ตรวจสอบว่ามีการใช้งานแล้วหรือไม่
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_promotions WHERE promotion_id = ?");
            $stmt->execute([$promotion_id]);
            $usage_count = $stmt->fetchColumn();
            
            if ($usage_count > 0) {
                $message = 'ไม่สามารถลบโปรโมชั่นนี้ได้ เนื่องจากมีประวัติการใช้งานแล้ว';
            } else {
                $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
                $stmt->execute([$promotion_id]);
                $message = 'ลบโปรโมชั่นเรียบร้อยแล้ว';
            }
        } elseif ($action == 'toggle_status') {
            $promotion_id = $_POST['promotion_id'];
            $stmt = $pdo->prepare("
                UPDATE promotions 
                SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END 
                WHERE id = ?
            ");
            $stmt->execute([$promotion_id]);
            $message = 'เปลี่ยนสถานะเรียบร้อยแล้ว';
        }
    }
}

// ดึงข้อมูลโปรโมชั่นสำหรับแก้ไข
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_promotion = $stmt->fetch();
}

// ดึงรายการโปรโมชั่นทั้งหมด
$stmt = $pdo->query("
    SELECT p.*, 
           COUNT(DISTINCT op.order_id) as times_used,
           COALESCE(SUM(op.discount_amount), 0) as total_discount_given
    FROM promotions p
    LEFT JOIN order_promotions op ON p.id = op.promotion_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$promotions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโปรโมชั่น - Toom Tam Fishing Admin</title>
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
        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            color: white;
        }
        .promo-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1rem;
            color: #e74c3c;
        }
        .status-active {
            color: #27ae60;
        }
        .status-inactive {
            color: #e74c3c;
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
                            <a class="nav-link" href="admin_users.php">
                                <i class="fas fa-users"></i> จัดการสมาชิก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_promotions.php">
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
                    <h2 class="mb-4">จัดการโปรโมชั่น</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- ฟอร์มเพิ่ม/แก้ไขโปรโมชั่น -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $edit_promotion ? 'แก้ไขโปรโมชั่น' : 'เพิ่มโปรโมชั่นใหม่'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="<?php echo $edit_promotion ? 'edit' : 'add'; ?>">
                                <?php if ($edit_promotion): ?>
                                    <input type="hidden" name="promotion_id" value="<?php echo $edit_promotion['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">รหัสโปรโมชั่น</label>
                                        <input type="text" class="form-control text-uppercase" name="code" required
                                               value="<?php echo $edit_promotion ? htmlspecialchars($edit_promotion['code']) : ''; ?>"
                                               placeholder="เช่น SAVE20">
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">คำอธิบาย</label>
                                        <input type="text" class="form-control" name="description"
                                               value="<?php echo $edit_promotion ? htmlspecialchars($edit_promotion['description']) : ''; ?>"
                                               placeholder="เช่น ลด 20% สำหรับสินค้าทุกชิ้น">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">ประเภทส่วนลด</label>
                                        <select class="form-select" name="discount_type" required>
                                            <option value="percentage" <?php echo ($edit_promotion && $edit_promotion['discount_type'] == 'percentage') ? 'selected' : ''; ?>>
                                                เปอร์เซ็นต์ (%)
                                            </option>
                                            <option value="fixed" <?php echo ($edit_promotion && $edit_promotion['discount_type'] == 'fixed') ? 'selected' : ''; ?>>
                                                จำนวนเงิน (บาท)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">มูลค่าส่วนลด</label>
                                        <input type="number" class="form-control" name="discount_value" step="0.01" required
                                               value="<?php echo $edit_promotion ? $edit_promotion['discount_value'] : ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">ยอดขั้นต่ำ (บาท)</label>
                                        <input type="number" class="form-control" name="minimum_order" step="0.01"
                                               value="<?php echo $edit_promotion ? $edit_promotion['minimum_order'] : '0'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">จำกัดการใช้ (ครั้ง)</label>
                                        <input type="number" class="form-control" name="usage_limit"
                                               value="<?php echo $edit_promotion ? $edit_promotion['usage_limit'] : ''; ?>"
                                               placeholder="ไม่จำกัด">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">วันที่เริ่มต้น</label>
                                        <input type="datetime-local" class="form-control" name="start_date"
                                               value="<?php echo $edit_promotion && $edit_promotion['start_date'] ? date('Y-m-d\TH:i', strtotime($edit_promotion['start_date'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">วันที่สิ้นสุด</label>
                                        <input type="datetime-local" class="form-control" name="end_date"
                                               value="<?php echo $edit_promotion && $edit_promotion['end_date'] ? date('Y-m-d\TH:i', strtotime($edit_promotion['end_date'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">สถานะ</label>
                                        <select class="form-select" name="status">
                                            <option value="active" <?php echo ($edit_promotion && $edit_promotion['status'] == 'active') ? 'selected' : ''; ?>>
                                                เปิดใช้งาน
                                            </option>
                                            <option value="inactive" <?php echo ($edit_promotion && $edit_promotion['status'] == 'inactive') ? 'selected' : ''; ?>>
                                                ปิดใช้งาน
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-gradient">
                                    <i class="fas fa-save"></i> <?php echo $edit_promotion ? 'บันทึกการแก้ไข' : 'เพิ่มโปรโมชั่น'; ?>
                                </button>
                                <?php if ($edit_promotion): ?>
                                    <a href="admin_promotions.php" class="btn btn-secondary">ยกเลิก</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- ตารางแสดงโปรโมชั่น -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">รายการโปรโมชั่นทั้งหมด</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>รหัส</th>
                                            <th>คำอธิบาย</th>
                                            <th>ส่วนลด</th>
                                            <th>ขั้นต่ำ</th>
                                            <th>ใช้แล้ว/จำกัด</th>
                                            <th>ระยะเวลา</th>
                                            <th>สถานะ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promotions as $promo): ?>
                                            <tr>
                                                <td class="promo-code"><?php echo htmlspecialchars($promo['code']); ?></td>
                                                <td><?php echo htmlspecialchars($promo['description'] ?: '-'); ?></td>
                                                <td>
                                                    <?php if ($promo['discount_type'] == 'percentage'): ?>
                                                        <?php echo $promo['discount_value']; ?>%
                                                    <?php else: ?>
                                                        <?php echo formatPrice($promo['discount_value']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatPrice($promo['minimum_order']); ?></td>
                                                <td>
                                                    <?php echo $promo['times_used']; ?> / 
                                                    <?php echo $promo['usage_limit'] ?: 'ไม่จำกัด'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($promo['start_date'] || $promo['end_date']): ?>
                                                        <?php if ($promo['start_date']): ?>
                                                            <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                        ถึง
                                                        <?php if ($promo['end_date']): ?>
                                                            <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        ไม่จำกัด
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-<?php echo $promo['status']; ?>">
                                                        <?php echo $promo['status'] == 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="promotion_id" value="<?php echo $promo['id']; ?>">
                                                        <button type="submit" class="btn btn-sm <?php echo $promo['status'] == 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    <a href="?edit=<?php echo $promo['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($promo['times_used'] == 0): ?>
                                                        <form method="POST" class="d-inline" 
                                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบโปรโมชั่นนี้?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="promotion_id" value="<?php echo $promo['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-danger" disabled title="ไม่สามารถลบได้เนื่องจากมีการใช้งานแล้ว">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>