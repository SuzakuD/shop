<?php
session_start();
require_once 'config.php';

// รับค่าการค้นหา
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';

try {
    $pdo = getConnection();
    
    // สร้าง query พื้นฐาน
    $sql = "SELECT DISTINCT p.*, 
            (SELECT c.name FROM categories c 
             JOIN product_category pc ON c.id = pc.category_id 
             WHERE pc.product_id = p.id LIMIT 1) as category_name
            FROM products p
            LEFT JOIN product_category pc ON p.id = pc.product_id
            LEFT JOIN categories c ON pc.category_id = c.id
            WHERE 1=1";
    
    $params = [];
    
    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($search_query)) {
        $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $search_query . '%';
    }
    
    // เพิ่มเงื่อนไขหมวดหมู่
    if (!empty($category_filter)) {
        $sql .= " AND c.id = :category";
        $params[':category'] = $category_filter;
    }
    
    // เพิ่มเงื่อนไขราคา
    $sql .= " AND p.price >= :min_price AND p.price <= :max_price";
    $params[':min_price'] = $min_price;
    $params[':max_price'] = $max_price;
    
    // เพิ่มการเรียงลำดับ
    switch($sort_by) {
        case 'price_asc':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY p.created_at DESC";
            break;
        default:
            $sql .= " ORDER BY p.name ASC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // ดึงหมวดหมู่ทั้งหมด
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // หาราคาสูงสุดและต่ำสุด
    $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
    $price_range = $stmt->fetch();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาสินค้า - Toomtam Fishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B35;
            --secondary: #F7931E;
            --dark: #2C3E50;
            --light: #ECF0F1;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .search-header {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-right: 50px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }

        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            color: white;
        }

        .filter-sidebar {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
        }

        .filter-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e0e0e0;
        }

        .filter-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .filter-section h5 {
            color: var(--dark);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-range input {
            width: 100%;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
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
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.4;
            min-height: 2.8em;
        }

        .product-price {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 600;
            margin-top: auto;
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: white;
            border-radius: 10px;
        }

        .sort-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 20px;
            transition: background 0.3s;
        }

        .btn-filter:hover {
            background: var(--secondary);
            color: white;
        }

        .btn-clear {
            background: #e0e0e0;
            color: #666;
            border: none;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .btn-clear:hover {
            background: #ccc;
            color: #333;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            margin-top: 30px;
        }

        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .filter-sidebar {
                position: static;
                margin-bottom: 30px;
            }
            
            .results-info {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish"></i> Toomtam Fishing
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">ติดต่อเรา</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <form method="GET">
                <div class="search-box">
                    <input type="text" class="form-control form-control-lg" 
                           name="q" value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="ค้นหาสินค้า...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <form method="GET">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        
                        <!-- Categories -->
                        <div class="filter-section">
                            <h5><i class="fas fa-list"></i> หมวดหมู่</h5>
                            <select name="category" class="form-select">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="filter-section">
                            <h5><i class="fas fa-tag"></i> ช่วงราคา</h5>
                            <div class="price-range">
                                <input type="number" class="form-control" name="min_price" 
                                       placeholder="ต่ำสุด" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                                <span>-</span>
                                <input type="number" class="form-control" name="max_price" 
                                       placeholder="สูงสุด" value="<?php echo $max_price < 999999 ? $max_price : ''; ?>">
                            </div>
                        </div>

                        <!-- Sort -->
                        <div class="filter-section">
                            <h5><i class="fas fa-sort"></i> เรียงตาม</h5>
                            <select name="sort" class="form-select">
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>ชื่อสินค้า</option>
                                <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>ราคา: ต่ำ - สูง</option>
                                <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>ราคา: สูง - ต่ำ</option>
                                <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-filter w-100">
                            <i class="fas fa-filter"></i> กรองสินค้า
                        </button>
                        
                        <?php if (!empty($search_query) || !empty($category_filter) || $min_price > 0 || $max_price < 999999): ?>
                            <a href="search.php" class="btn btn-clear w-100 mt-2">
                                <i class="fas fa-times"></i> ล้างตัวกรอง
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Products -->
            <div class="col-lg-9">
                <!-- Results Info -->
                <div class="results-info">
                    <div>
                        <?php if (!empty($search_query)): ?>
                            <h5 class="mb-0">ผลการค้นหา "<?php echo htmlspecialchars($search_query); ?>"</h5>
                        <?php endif; ?>
                        <p class="mb-0 text-muted">พบ <?php echo count($products); ?> รายการ</p>
                    </div>
                </div>

                <!-- Product Grid -->
                <?php if (count($products) > 0): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                   style="text-decoration: none; color: inherit;">
                                    <div class="product-image">
                                        <?php if ($product['image'] && file_exists('images/products/' . $product['image'])): ?>
                                            <img src="images/products/<?php echo $product['image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-fish"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-title">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            <?php echo htmlspecialchars($product['category_name'] ?: 'ไม่ระบุหมวดหมู่'); ?>
                                        </div>
                                        <div class="product-price">
                                            ฿<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4>ไม่พบสินค้าที่ค้นหา</h4>
                        <p class="text-muted">ลองเปลี่ยนคำค้นหาหรือปรับตัวกรองใหม่</p>
                        <a href="search.php" class="btn btn-primary mt-3">
                            <i class="fas fa-redo"></i> ค้นหาใหม่
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>