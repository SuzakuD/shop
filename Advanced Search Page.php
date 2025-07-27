<?php
require_once 'config.php';

$pdo = getConnection();

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// รับค่าการค้นหา
$keyword = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$in_stock_only = isset($_GET['in_stock']) ? true : false;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// ดึงหมวดหมู่ทั้งหมด
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// สร้าง query สำหรับค้นหา
$where = "WHERE 1=1";
$params = [];

if ($keyword) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($category_id > 0) {
    $where .= " AND EXISTS (SELECT 1 FROM product_category pc WHERE pc.product_id = p.id AND pc.category_id = ?)";
    $params[] = $category_id;
}

if ($min_price > 0) {
    $where .= " AND p.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where .= " AND p.price <= ?";
    $params[] = $max_price;
}

if ($in_stock_only) {
    $where .= " AND p.stock > 0";
}

// กำหนดการเรียงลำดับ
$order_by = "ORDER BY ";
switch ($sort_by) {
    case 'name_asc':
        $order_by .= "p.name ASC";
        break;
    case 'name_desc':
        $order_by .= "p.name DESC";
        break;
    case 'price_asc':
        $order_by .= "p.price ASC";
        break;
    case 'price_desc':
        $order_by .= "p.price DESC";
        break;
    case 'newest':
        $order_by .= "p.created_at DESC";
        break;
    case 'rating':
        $order_by .= "avg_rating DESC NULLS LAST";
        break;
    default:
        $order_by .= "p.name ASC";
}

// นับจำนวนผลลัพธ์
$count_query = "SELECT COUNT(DISTINCT p.id) FROM products p $where";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_results = $count_stmt->fetchColumn();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_results / $limit);

// ดึงข้อมูลสินค้า
$query = "
    SELECT p.*, 
           c.name as category_name,
           AVG(pr.rating) as avg_rating,
           COUNT(pr.id) as review_count
    FROM products p
    LEFT JOIN product_category pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
    $where
    GROUP BY p.id
    $order_by
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// สร้าง URL สำหรับ pagination
function buildSearchUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'search.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาสินค้า - Toom Tam Fishing</title>
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
            color: white;
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

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Search Header */
        .search-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        /* Advanced Search Form */
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }

        .price-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-inputs input {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }

        .btn-search {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            grid-column: span 2;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-reset {
            background: #95a5a6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-reset:hover {
            background: #7f8c8d;
        }

        /* Results Section */
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .result-count {
            color: #7f8c8d;
        }

        .sort-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            cursor: pointer;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .product-card a {
            flex: 1;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
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
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .product-category {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .stars {
            color: #f39c12;
            font-size: 0.9rem;
        }

        .rating-count {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
            margin-top: auto;
        }

        .product-stock {
            color: #27ae60;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .product-stock.out-of-stock {
            color: #e74c3c;
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
            margin: 0 15px 15px;
            width: calc(100% - 30px);
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
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #2c3e50;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-results i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .btn-search {
                grid-column: span 1;
            }
            
            .results-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
        <!-- Search Form -->
        <div class="search-header">
            <h1 class="search-title">ค้นหาสินค้าขั้นสูง</h1>
            
            <form method="GET" class="search-form">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="q">คำค้นหา</label>
                    <input type="text" class="form-control" id="q" name="q" 
                           value="<?php echo htmlspecialchars($keyword); ?>"
                           placeholder="ชื่อสินค้า หรือคำอธิบาย...">
                </div>

                <div class="form-group">
                    <label for="category">หมวดหมู่</label>
                    <select class="form-control" id="category" name="category">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ช่วงราคา (บาท)</label>
                    <div class="price-inputs">
                        <input type="number" class="form-control" name="min_price" 
                               value="<?php echo $min_price > 0 ? $min_price : ''; ?>"
                               placeholder="ต่ำสุด">
                        <span>-</span>
                        <input type="number" class="form-control" name="max_price" 
                               value="<?php echo $max_price > 0 ? $max_price : ''; ?>"
                               placeholder="สูงสุด">
                    </div>
                </div>

                <div class="form-group">
                    <label for="sort">เรียงตาม</label>
                    <select class="form-control" id="sort" name="sort">
                        <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>ชื่อ ก-ฮ</option>
                        <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>ชื่อ ฮ-ก</option>
                        <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>ราคาต่ำ-สูง</option>
                        <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>ราคาสูง-ต่ำ</option>
                        <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                        <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>คะแนนสูงสุด</option>
                    </select>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="in_stock" name="in_stock" 
                               <?php echo $in_stock_only ? 'checked' : ''; ?>>
                        <label for="in_stock">เฉพาะสินค้าที่มีในสต็อก</label>
                    </div>
                </div>

                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
                
                <a href="search.php" class="btn-reset">
                    <i class="fas fa-undo"></i> ล้างการค้นหา
                </a>
            </form>
        </div>

        <!-- Results -->
        <?php if ($total_results > 0): ?>
            <div class="results-info">
                <span class="result-count">
                    พบสินค้า <?php echo $total_results; ?> รายการ
                    <?php if ($keyword): ?>
                        สำหรับ "<?php echo htmlspecialchars($keyword); ?>"
                    <?php endif; ?>
                </span>
                <select class="sort-select" onchange="window.location.href=updateQueryString('sort', this.value)">
                    <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>ชื่อ ก-ฮ</option>
                    <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>ชื่อ ฮ-ก</option>
                    <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>ราคาต่ำ-สูง</option>
                    <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>ราคาสูง-ต่ำ</option>
                    <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                    <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>คะแนนสูงสุด</option>
                </select>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <div class="product-image">
                                <?php if ($product['image'] && file_exists('images/products/' . $product['image'])): ?>
                                    <img src="images/products/<?php echo $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-fish"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                
                                <?php if ($product['category_name']): ?>
                                    <div class="product-category">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($product['review_count'] > 0): ?>
                                    <div class="product-rating">
                                        <div class="stars">
                                            <?php
                                            $rating = round($product['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $rating): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor;
                                            ?>
                                        </div>
                                        <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                                
                                <div class="product-stock <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>">
                                    <?php if ($product['stock'] > 0): ?>
                                        <i class="fas fa-check-circle"></i> มีสินค้า
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> สินค้าหมด
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildSearchUrl($page - 1); ?>">&laquo; ก่อนหน้า</a>
                    <?php endif; ?>

                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="<?php echo buildSearchUrl(1); ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo buildSearchUrl($i); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="<?php echo buildSearchUrl($total_pages); ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo buildSearchUrl($page + 1); ?>">ถัดไป &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>ไม่พบสินค้าที่ค้นหา</h3>
                <p>ลองค้นหาด้วยคำอื่น หรือลองปรับเงื่อนไขการค้นหา</p>
                <a href="search.php" class="btn-reset">ค้นหาใหม่</a>
            </div>
        <?php endif; ?>
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

        function updateQueryString(key, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            return url.toString();
        }
    </script>
</body>
</html>