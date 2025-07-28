<?php
require_once 'config.php';

$pdo = getConnection();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            $_SESSION['message'] = 'เข้าสู่ระบบเรียบร้อยแล้ว';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: index.php');
    exit();
}

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where = "WHERE 1=1";
$params = [];

if ($category_id > 0) {
    $where .= " AND EXISTS (SELECT 1 FROM product_category pc WHERE pc.product_id = p.id AND pc.category_id = ?)";
    $params[] = $category_id;
}

if ($search) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT p.* FROM products p $where ORDER BY p.id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// Check if user is admin
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toom Tam Fishing - ร้านอุปกรณ์ตกปลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #2c3e50 !important;
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-4.0.3') center/cover;
            height: 400px;
            display: flex;
            align-items: center;
            color: white;
        }
        .product-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 40px 0;
            margin-top: 50px;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .admin-buttons {
            display: flex;
            gap: 5px;
        }
        .login-modal .modal-content {
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish"></i> Toom Tam Fishing
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">หน้าแรก</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">หมวดหมู่สินค้า</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php">ทั้งหมด</a></li>
                            <?php foreach ($categories as $category): ?>
                                <li><a class="dropdown-item" href="index.php?category=<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">ติดต่อ</a>
                    </li>
                    <?php if ($is_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="admin/dashboard.php">
                                <i class="fas fa-cog"></i> จัดการระบบ
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <form class="d-flex me-3" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="ค้นหาสินค้า..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-primary" type="submit">ค้นหา</button>
                </form>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> 
                            ตะกร้า <span class="badge bg-primary"><?= $cart_count ?></span>
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                                <?php if ($is_admin): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="account.php">บัญชีของฉัน</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">ออกจากระบบ</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">เข้าสู่ระบบ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">สมัครสมาชิก</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show m-0">
            <div class="container">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Login Modal -->
    <div class="modal fade login-modal" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-fish"></i> เข้าสู่ระบบ Toom Tam Fishing
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="login_submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="text-center w-100">
                        <p class="mb-0">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">ยินดีต้อนรับสู่ Toom Tam Fishing</h1>
            <p class="lead mb-4">อุปกรณ์ตกปลาคุณภาพสูง ราคาดี พร้อมส่งทั่วประเทศ</p>
            <a href="#products" class="btn btn-primary btn-lg">เลือกซื้อสินค้า</a>
        </div>
    </section>

    <!-- Categories Section (Admin View) -->
    <?php if ($is_admin): ?>
        <section class="py-4 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>จัดการหมวดหมู่สินค้า</h4>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus"></i> เพิ่มหมวดหมู่
                            </button>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($categories as $category): ?>
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="category-item">
                                        <span><?= htmlspecialchars($category['name']) ?></span>
                                        <div class="admin-buttons">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Products Section -->
    <section id="products" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2 class="text-center mb-5">สินค้าของเรา</h2>
                    
                    <?php if ($search || $category_id): ?>
                        <div class="alert alert-info">
                            แสดงผลการค้นหา: 
                            <?php if ($search): ?>
                                "<?= htmlspecialchars($search) ?>"
                            <?php endif; ?>
                            <?php if ($category_id): ?>
                                หมวดหมู่: <?= htmlspecialchars($categories[array_search($category_id, array_column($categories, 'id'))]['name']) ?>
                            <?php endif; ?>
                            (<?= $total_products ?> รายการ)
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center">
                        <p class="lead">ไม่พบสินค้าที่ตรงกับการค้นหา</p>
                        <a href="index.php" class="btn btn-primary">ดูสินค้าทั้งหมด</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card product-card h-100">
                                <img src="images/<?= htmlspecialchars($product['image']) ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>"
                                     style="height: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text flex-grow-1"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                                    <div class="mt-auto">
                                        <p class="h5 text-primary"><?= formatPrice($product['price']) ?></p>
                                        <p class="text-muted">คงเหลือ: <?= $product['stock'] ?> ชิ้น</p>
                                        <div class="d-grid gap-2">
                                            <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">ดูรายละเอียด</a>
                                            <?php if ($product['stock'] > 0): ?>
                                                <form method="POST" action="add_to_cart.php" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        <i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100" disabled>สินค้าหมด</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Product pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $category_id ? '&category=' . $category_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">ก่อนหน้า</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $category_id ? '&category=' . $category_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $category_id ? '&category=' . $category_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">ถัดไป</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มหมวดหมู่ใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="admin/manage_categories.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">ชื่อหมวดหมู่</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_category" class="btn btn-success">เพิ่มหมวดหมู่</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขหมวดหมู่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="admin/manage_categories.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_category_id" name="category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">ชื่อหมวดหมู่</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_category" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-fish"></i> Toom Tam Fishing</h5>
                    <p>ร้านอุปกรณ์ตกปลาครบครัน คุณภาพดี ราคาเป็นกันเอง</p>
                </div>
                <div class="col-md-4">
                    <h5>ติดต่อเรา</h5>
                    <p><i class="fas fa-phone"></i> 02-xxx-xxxx</p>
                    <p><i class="fas fa-envelope"></i> info@toomtamfishing.com</p>
                </div>
                <div class="col-md-4">
                    <h5>ติดตามเรา</h5>
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-2x"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-2x"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-line fa-2x"></i></a>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 Toom Tam Fishing. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(id, name) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }
        
        function deleteCategory(id, name) {
            if (confirm('คุณต้องการลบหมวดหมู่ "' + name + '" หรือไม่?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin/manage_categories.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'category_id';
                input.value = id;
                
                const submit = document.createElement('input');
                submit.type = 'hidden';
                submit.name = 'delete_category';
                submit.value = '1';
                
                form.appendChild(input);
                form.appendChild(submit);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>