<?php
require_once 'config.php';

$pdo = getConnection();

// รับ product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลสินค้า
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.id as category_id
    FROM products p
    LEFT JOIN product_category pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit();
}

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// ดึงรีวิวและคะแนนเฉลี่ย
$stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
    FROM product_reviews
    WHERE product_id = ? AND status = 'approved'
");
$stmt->execute([$product_id]);
$rating_info = $stmt->fetch();

// ดึงรีวิวทั้งหมด
$stmt = $pdo->prepare("
    SELECT pr.*, u.username
    FROM product_reviews pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.product_id = ? AND pr.status = 'approved'
    ORDER BY pr.created_at DESC
    LIMIT 10
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// ดึงสินค้าที่เกี่ยวข้อง
$stmt = $pdo->prepare("
    SELECT p.*
    FROM products p
    JOIN product_category pc ON p.id = pc.product_id
    WHERE pc.category_id = ? AND p.id != ? AND p.stock > 0
    ORDER BY RAND()
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll();

// ส่งรีวิว
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $message = 'กรุณาเข้าสู่ระบบก่อนเขียนรีวิว';
    } else {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        
        if ($rating < 1 || $rating > 5) {
            $message = 'กรุณาให้คะแนน 1-5 ดาว';
        } elseif (empty($comment)) {
            $message = 'กรุณาเขียนความคิดเห็น';
        } else {
            // ตรวจสอบว่าเคยรีวิวแล้วหรือไม่
            $stmt = $pdo->prepare("
                SELECT id FROM product_reviews 
                WHERE product_id = ? AND user_id = ?
            ");
            $stmt->execute([$product_id, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $message = 'คุณเคยรีวิวสินค้านี้แล้ว';
            } else {
                // ตรวจสอบว่าเคยซื้อสินค้านี้หรือไม่
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE oi.product_id = ? AND o.user_id = ?
                ");
                $stmt->execute([$product_id, $_SESSION['user_id']]);
                $has_purchased = $stmt->fetchColumn() > 0;
                
                // บันทึกรีวิว
                $stmt = $pdo->prepare("
                    INSERT INTO product_reviews (product_id, user_id, rating, comment, status)
                    VALUES (?, ?, ?, ?, 'approved')
                ");
                
                if ($stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment])) {
                    $message = 'ขอบคุณสำหรับรีวิวของคุณ';
                    
                    // รีโหลดข้อมูลรีวิว
                    header("Location: product.php?id=$product_id&reviewed=1");
                    exit();
                } else {
                    $message = 'เกิดข้อผิดพลาดในการบันทึกรีวิว';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Toom Tam Fishing</title>
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
            padding: 40px 20px;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Product Detail */
        .product-detail {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .product-image-section {
            text-align: center;
        }

        .main-image {
            width: 100%;
            max-width: 500px;
            height: 400px;
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 4rem;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .product-info-section h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stars {
            color: #f39c12;
        }

        .category-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .price-section {
            margin: 20px 0;
        }

        .price {
            font-size: 2rem;
            color: #e74c3c;
            font-weight: bold;
        }

        .stock-info {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .stock-available {
            color: #27ae60;
        }

        .stock-low {
            color: #f39c12;
        }

        .stock-out {
            color: #e74c3c;
        }

        .description {
            color: #666;
            line-height: 1.8;
            margin: 20px 0;
        }

        .add-to-cart-section {
            display: flex;
            gap: 15px;
            align-items: center;
            margin: 30px 0;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .quantity-btn {
            background: #f8f9fa;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .quantity-btn:hover {
            background: #e9ecef;
        }

        .quantity-input {
            width: 60px;
            padding: 10px;
            border: none;
            text-align: center;
            font-size: 1rem;
        }

        .btn-add-cart {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-add-cart:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .review-summary {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .rating-big {
            text-align: center;
        }

        .rating-number {
            font-size: 3rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .review-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .rating-input {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .rating-input label {
            cursor: pointer;
            color: #ddd;
            font-size: 1.5rem;
            transition: color 0.3s;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input input[type="radio"]:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #f39c12;
        }

        .review-item {
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: bold;
            color: #2c3e50;
        }

        .review-date {
            color: #95a5a6;
            font-size: 0.9rem;
        }

        .review-comment {
            color: #666;
            line-height: 1.6;
        }

        /* Related Products */
        .related-products {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .related-item {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }

        .related-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .related-image {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .related-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .related-price {
            color: #e74c3c;
            font-size: 1.1rem;
            font-weight: bold;
        }

        /* Alert */
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
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .review-summary {
                flex-direction: column;
            }
            
            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">หน้าแรก</a> /
            <?php if ($product['category_id']): ?>
                <a href="index.php?category=<?php echo $product['category_id']; ?>">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </a> /
            <?php endif; ?>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>

        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'ขอบคุณ') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['reviewed'])): ?>
            <div class="alert alert-success">
                ขอบคุณสำหรับรีวิวของคุณ!
            </div>
        <?php endif; ?>

        <!-- Product Detail -->
        <div class="product-detail">
            <div class="product-grid">
                <div class="product-image-section">
                    <div class="main-image">
                        <?php if ($product['image'] && file_exists('images/products/' . $product['image'])): ?>
                            <img src="images/products/<?php echo $product['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <i class="fas fa-fish"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-info-section">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="rating-display">
                        <div class="stars">
                            <?php
                            $avg_rating = $rating_info['avg_rating'] ?: 0;
                            $full_stars = floor($avg_rating);
                            $half_star = $avg_rating - $full_stars >= 0.5;
                            
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= $full_stars): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif;
                            endfor;
                            ?>
                        </div>
                        <span><?php echo number_format($avg_rating, 1); ?> (<?php echo $rating_info['review_count']; ?> รีวิว)</span>
                    </div>

                    <?php if ($product['category_name']): ?>
                        <div class="category-badge">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="price-section">
                        <div class="price"><?php echo formatPrice($product['price']); ?></div>
                    </div>

                    <div class="stock-info">
                        <?php if ($product['stock'] > 10): ?>
                            <span class="stock-available">
                                <i class="fas fa-check-circle"></i> มีสินค้า (คงเหลือ <?php echo $product['stock']; ?> ชิ้น)
                            </span>
                        <?php elseif ($product['stock'] > 0): ?>
                            <span class="stock-low">
                                <i class="fas fa-exclamation-circle"></i> สินค้าเหลือน้อย (คงเหลือ <?php echo $product['stock']; ?> ชิ้น)
                            </span>
                        <?php else: ?>
                            <span class="stock-out">
                                <i class="fas fa-times-circle"></i> สินค้าหมด
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>

                    <div class="add-to-cart-section">
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)" 
                                <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>รีวิวจากลูกค้า</h2>
            
            <?php if ($rating_info['review_count'] > 0): ?>
                <div class="review-summary">
                    <div class="rating-big">
                        <div class="rating-number"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="stars">
                            <?php
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= $full_stars): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif;
                            endfor;
                            ?>
                        </div>
                        <div><?php echo $rating_info['review_count']; ?> รีวิว</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Review Form -->
            <?php if (isLoggedIn()): ?>
                <div class="review-form">
                    <h3>เขียนรีวิว</h3>
                    <form method="POST">
                        <input type="hidden" name="submit_review" value="1">
                        
                        <div class="rating-input">
                            <input type="radio" name="rating" value="5" id="star5" required>
                            <label for="star5"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" name="rating" value="4" id="star4" required>
                            <label for="star4"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" name="rating" value="3" id="star3" required>
                            <label for="star3"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" name="rating" value="2" id="star2" required>
                            <label for="star2"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" name="rating" value="1" id="star1" required>
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="comment" class="form-control" rows="4" 
                                      placeholder="แบ่งปันประสบการณ์ของคุณกับสินค้านี้..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn-add-cart">
                            <i class="fas fa-paper-plane"></i> ส่งรีวิว
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <a href="login.php">เข้าสู่ระบบ</a> เพื่อเขียนรีวิว
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div>
                                <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="review-date">
                                <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                            </span>
                        </div>
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($reviews)): ?>
                    <p class="text-center text-muted">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h2>สินค้าที่เกี่ยวข้อง</h2>
                <div class="related-grid">
                    <?php foreach ($related_products as $related): ?>
                        <a href="product.php?id=<?php echo $related['id']; ?>" class="related-item">
                            <div class="related-image">
                                <i class="fas fa-fish"></i>
                            </div>
                            <div class="related-name"><?php echo htmlspecialchars($related['name']); ?></div>
                            <div class="related-price"><?php echo formatPrice($related['price']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            const newValue = parseInt(input.value) + change;
            const max = parseInt(input.max);
            
            if (newValue >= 1 && newValue <= max) {
                input.value = newValue;
            }
        }

        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=' + quantity
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
                    
                    alert('เพิ่ม ' + data.product_name + ' จำนวน ' + quantity + ' ชิ้น ลงตะกร้าเรียบร้อยแล้ว');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า');
            });
        }

        // Rating stars interaction
        const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
        const ratingLabels = document.querySelectorAll('.rating-input label');
        
        ratingLabels.forEach((label, index) => {
            label.addEventListener('click', () => {
                const rating = 5 - index;
                document.getElementById('star' + rating).checked = true;
            });
        });
    </script>
</body>
</html>