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
$edit_product = null;

// ดำเนินการเมื่อมีการเพิ่มหรือแก้ไขสินค้า
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add' || $action == 'edit') {
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];
            $category_id = $_POST['category_id'];
            
            // จัดการอัพโหลดรูปภาพ
            $image_name = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['product_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    // สร้างชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
                    $image_name = 'product_' . time() . '.' . $ext;
                    $upload_path = 'images/products/' . $image_name;
                    
                    // สร้างโฟลเดอร์ถ้ายังไม่มี
                    if (!file_exists('images/products')) {
                        mkdir('images/products', 0777, true);
                    }
                    
                    // อัพโหลดไฟล์
                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        $message = 'ไม่สามารถอัพโหลดรูปภาพได้';
                        $image_name = null;
                    }
                }
            }
            
            try {
                if ($action == 'add') {
                    // เพิ่มสินค้าใหม่
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $stock, $image_name]);
                    $product_id = $pdo->lastInsertId();
                    
                    // เพิ่มความสัมพันธ์กับหมวดหมู่
                    $stmt = $pdo->prepare("INSERT INTO product_category (product_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$product_id, $category_id]);
                    
                    $message = 'เพิ่มสินค้าเรียบร้อยแล้ว';
                } else {
                    // แก้ไขสินค้า
                    $product_id = $_POST['product_id'];
                    
                    // ถ้าไม่มีรูปใหม่ ใช้รูปเดิม
                    if (!$image_name) {
                        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $old_product = $stmt->fetch();
                        $image_name = $old_product['image'];
                    }
                    
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $price, $stock, $image_name, $product_id]);
                    
                    // อัพเดตหมวดหมู่
                    $stmt = $pdo->prepare("DELETE FROM product_category WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    
                    $stmt = $pdo->prepare("INSERT INTO product_category (product_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$product_id, $category_id]);
                    
                    $message = 'แก้ไขสินค้าเรียบร้อยแล้ว';
                }
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        } elseif ($action == 'delete') {
            $product_id = $_POST['product_id'];
            
            try {
                // ลบความสัมพันธ์กับหมวดหมู่ก่อน
                $stmt = $pdo->prepare("DELETE FROM product_category WHERE product_id = ?");
                $stmt->execute([$product_id]);
                
                // ลบสินค้า
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                
                $message = 'ลบสินค้าเรียบร้อยแล้ว';
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        }
    }
}

// ดึงข้อมูลสินค้าสำหรับแก้ไข
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("
        SELECT p.*, pc.category_id 
        FROM products p 
        LEFT JOIN product_category pc ON p.id = pc.product_id 
        WHERE p.id = ?
    ");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}

// ดึงรายการสินค้าทั้งหมด
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN product_category pc ON p.id = pc.product_id 
    LEFT JOIN categories c ON pc.category_id = c.id 
    ORDER BY p.id DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

// ดึงหมวดหมู่ทั้งหมด
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Toom Tam Fishing Admin</title>
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
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .stock-low {
            color: #e74c3c;
            font-weight: bold;
        }
        .stock-ok {
            color: #27ae60;
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
                            <a class="nav-link active" href="admin_products.php">
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
                    <h2 class="mb-4">จัดการสินค้า</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- ฟอร์มเพิ่ม/แก้ไขสินค้า -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $edit_product ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                                <?php if ($edit_product): ?>
                                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ชื่อสินค้า</label>
                                        <input type="text" class="form-control" name="name" required
                                               value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">หมวดหมู่</label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">เลือกหมวดหมู่</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"
                                                    <?php echo ($edit_product && $edit_product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">รายละเอียด</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ราคา</label>
                                        <input type="number" class="form-control" name="price" step="0.01" required
                                               value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">จำนวนสต็อก</label>
                                        <input type="number" class="form-control" name="stock" required
                                               value="<?php echo $edit_product ? $edit_product['stock'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">รูปภาพสินค้า</label>
                                    <input type="file" class="form-control" name="product_image" accept="image/*">
                                    <?php if ($edit_product && $edit_product['image']): ?>
                                        <small class="text-muted">รูปปัจจุบัน: <?php echo $edit_product['image']; ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-gradient">
                                    <i class="fas fa-save"></i> <?php echo $edit_product ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้า'; ?>
                                </button>
                                <?php if ($edit_product): ?>
                                    <a href="admin_products.php" class="btn btn-secondary">ยกเลิก</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- ตารางแสดงสินค้า -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">รายการสินค้าทั้งหมด</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>รูป</th>
                                            <th>ชื่อสินค้า</th>
                                            <th>หมวดหมู่</th>
                                            <th>ราคา</th>
                                            <th>สต็อก</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?php echo $product['id']; ?></td>
                                                <td>
                                                    <?php if ($product['image'] && file_exists('images/products/' . $product['image'])): ?>
                                                        <img src="images/products/<?php echo $product['image']; ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?: '-'); ?></td>
                                                <td><?php echo formatPrice($product['price']); ?></td>
                                                <td class="<?php echo $product['stock'] < 10 ? 'stock-low' : 'stock-ok'; ?>">
                                                    <?php echo $product['stock']; ?>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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