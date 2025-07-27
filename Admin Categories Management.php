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
$edit_category = null;

// ดำเนินการเมื่อมีการเพิ่ม/แก้ไข/ลบหมวดหมู่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add' || $action == 'edit') {
            $name = trim($_POST['name']);
            
            if (empty($name)) {
                $message = 'กรุณากรอกชื่อหมวดหมู่';
            } else {
                try {
                    if ($action == 'add') {
                        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                        $stmt->execute([$name]);
                        $message = 'เพิ่มหมวดหมู่เรียบร้อยแล้ว';
                    } else {
                        $category_id = $_POST['category_id'];
                        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                        $stmt->execute([$name, $category_id]);
                        $message = 'แก้ไขหมวดหมู่เรียบร้อยแล้ว';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'ชื่อหมวดหมู่นี้มีอยู่แล้ว';
                    } else {
                        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action == 'delete') {
            $category_id = $_POST['category_id'];
            
            // ตรวจสอบว่ามีสินค้าในหมวดหมู่นี้หรือไม่
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_category WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $product_count = $stmt->fetchColumn();
            
            if ($product_count > 0) {
                $message = 'ไม่สามารถลบหมวดหมู่นี้ได้ เนื่องจากมีสินค้าอยู่ ' . $product_count . ' รายการ';
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    $message = 'ลบหมวดหมู่เรียบร้อยแล้ว';
                } catch (Exception $e) {
                    $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                }
            }
        }
    }
}

// ดึงข้อมูลหมวดหมู่สำหรับแก้ไข
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

// ดึงรายการหมวดหมู่ทั้งหมดพร้อมจำนวนสินค้า
$stmt = $pdo->query("
    SELECT c.*, COUNT(pc.product_id) as product_count 
    FROM categories c 
    LEFT JOIN product_category pc ON c.id = pc.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - Toom Tam Fishing Admin</title>
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
        .category-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .category-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        .product-count {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
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
                            <a class="nav-link active" href="admin_categories.php">
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
                    <h2 class="mb-4">จัดการหมวดหมู่สินค้า</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- ฟอร์มเพิ่ม/แก้ไขหมวดหมู่ -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <?php echo $edit_category ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่'; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                                        <?php if ($edit_category): ?>
                                            <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">ชื่อหมวดหมู่</label>
                                            <input type="text" class="form-control" name="name" required
                                                   value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>"
                                                   placeholder="เช่น เบ็ดตกปลา, เหยื่อปลอม">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-gradient w-100">
                                            <i class="fas fa-save"></i> 
                                            <?php echo $edit_category ? 'บันทึกการแก้ไข' : 'เพิ่มหมวดหมู่'; ?>
                                        </button>
                                        
                                        <?php if ($edit_category): ?>
                                            <a href="admin_categories.php" class="btn btn-secondary w-100 mt-2">ยกเลิก</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- รายการหมวดหมู่ -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">รายการหมวดหมู่ทั้งหมด</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categories)): ?>
                                        <p class="text-muted text-center">ยังไม่มีหมวดหมู่</p>
                                    <?php else: ?>
                                        <?php foreach ($categories as $category): ?>
                                            <div class="category-card">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                        <span class="product-count">
                                                            <i class="fas fa-box"></i> 
                                                            <?php echo $category['product_count']; ?> สินค้า
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <a href="?edit=<?php echo $category['id']; ?>" 
                                                           class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($category['product_count'] == 0): ?>
                                                            <form method="POST" class="d-inline" 
                                                                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบหมวดหมู่นี้?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-danger" disabled 
                                                                    title="ไม่สามารถลบได้เนื่องจากมีสินค้าในหมวดหมู่นี้">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>