<?php
require_once 'config.php';

$pdo = getConnection();

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toom Tam Fishing - ร้านขายอุปกรณ์ตกปลา</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,0.2);
        }

        .cart-icon {
            position: relative;
            font-size: 1.5rem;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            padding: 20px;
        }

        /* Sidebar */
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .sidebar h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-box button:hover {
            background: #2980b9;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 10px;
        }

        .category-list a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #2c3e50;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .category-list a:hover,
        .category-list a.active {
            background: #3498db;
            color: white;
        }

        /* Content Area */
        .content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .content h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 3rem;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .product-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .product-stock {
            color: #27ae60;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .add-to-cart {
            width: 100%;
            padding: 10px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .add-to-cart:hover {
            background: #219a52;
        }

        .add-to-cart:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #2c3e50;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
        }

        .pagination .current {
            background: #3498db;
            color: white;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            border-color: #27ae60;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #e74c3c;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-fish"></i> Toom Tam Fishing
            </a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> ตะกร้า</a></li>
                    <li><a href="contact.php"><i class="fas fa-phone"></i> ติดต่อ</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="orders.php"><i class="fas fa-receipt"></i> คำสั่งซื้อ</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar">
            <h3><i class="fas fa-search"></i> ค้นหาสินค้า</h3>
            <div class="search-box">
                <form method="GET">
                    <input type="text" name="search" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
                </form>
            </div>

            <h3><i class="fas fa-list"></i> หมวดหมู่สินค้า</h3>
            <ul class="category-list">
                <li><a href="index.php" class="<?php echo $category_id == 0 ? 'active' : ''; ?>">ทั้งหมด</a></li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="index.php?category=<?php echo $category['id']; ?>"
                           class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="content">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <h2>
                <?php if ($category_id > 0): ?>
                    <?php
                    $current_category = array_filter($categories, function($c) use ($category_id) {
                        return $c['id'] == $category_id;
                    });
                    $current_category = array_shift($current_category);
                    echo htmlspecialchars($current_category['name']);
                    ?>
                <?php elseif ($search): ?>
                    ผลการค้นหา "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    สินค้าทั้งหมด
                <?php endif; ?>
            </h2>

            <?php if (empty($products)): ?>
                <p>ไม่พบสินค้าที่ค้นหา</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                <div class="product-image">
                                    <i class="fas fa-fish"></i>
                                </div>
                                <div class="product-info">
                                    <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                                    <div class="product-stock">
                                        <?php if ($product['stock'] > 0): ?>
                                            <i class="fas fa-check-circle"></i> คงเหลือ <?php echo $product['stock']; ?> ชิ้น
                                        <?php else: ?>
                                            <i class="fas fa-times-circle" style="color: #e74c3c;"></i> สินค้าหมด
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <button class="add-to-cart"
                                    onclick="addToCart(<?php echo $product['id']; ?>)"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> ใส่ตะกร้า
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>">&laquo; ก่อนหน้า</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>">ถัดไป &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    } else if (data.cart_count > 0) {
                        const cartIcon = document.querySelector('.cart-icon');
                        cartIcon.innerHTML += '<span class="cart-count">' + data.cart_count + '</span>';
                    }

                    // Show success message
                    alert('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า');
            });
        }
    </script>
</body>
</html>