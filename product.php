<?php
session_start();
require_once 'config.php';

// ตรวจสอบ product id
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // ดึงข้อมูลสินค้า
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN product_category pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: index.php');
        exit();
    }
    
    // ดึงสินค้าที่เกี่ยวข้อง (ในหมวดหมู่เดียวกัน)
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM products p
        JOIN product_category pc ON p.id = pc.product_id
        WHERE pc.category_id = (
            SELECT category_id FROM product_category WHERE product_id = ?
        )
        AND p.id != ?
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$product_id, $product_id]);
    $related_products = $stmt->fetchAll();
    
    // ดึงรีวิวของสินค้า
    $stmt = $pdo->prepare("
        SELECT pr.*, u.username 
        FROM product_reviews pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.product_id = ? AND pr.status = 'approved'
        ORDER BY pr.created_at DESC
    ");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    
    // คำนวณคะแนนเฉลี่ย
    $avg_rating = 0;
    $total_reviews = count($reviews);
    if ($total_reviews > 0) {
        $sum_rating = array_sum(array_column($reviews, 'rating'));
        $avg_rating = round($sum_rating / $total_reviews, 1);
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// จัดการเพิ่มรีวิว
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'กรุณาเข้าสู่ระบบก่อนเขียนรีวิว';
        header("Location: login.php?redirect=product.php?id=$product_id");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    try {
        // ตรวจสอบว่าเคยรีวิวแล้วหรือไม่
        $stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$product_id, $user_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'คุณเคยรีวิวสินค้านี้แล้ว';
        } else {
            $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_id, $user_id, $rating, $comment]);
            $_SESSION['success'] = 'ขอบคุณสำหรับรีวิวของคุณ';
        }
        
        header("Location: product.php?id=$product_id");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// ฟังก์ชันแสดงดาว
function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Toomtam Fishing</title>
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

        .product-info h1 {
            color: var(--dark);
            font-weight: 600;
        }

        .price {
            font-size: 2rem;
            color: var(--primary);
            font-weight: 600;
        }

        .rating {
            color: #f39c12;
            margin: 10px 0;
        }

        .stock-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 10px 0;
        }

        .in-stock {
            background: #d4edda;
            color: #155724;
        }

        .low-stock {
            background: #fff3cd;
            color: #856404;
        }

        .out-stock {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 25px;
            transition: transform 0.3s;
        }

        .btn-add-cart:hover {
            transform: translateY(-2px);
            color: white;
        }

        .review-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .review-rating {
            color: #f39c12;
        }

        .star-rating {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
        }

        .star-rating .fas {
            color: #f39c12;
        }

        .related-product {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }

        .related-product:hover {
            transform: translateY(-5px);
        }

        .related-product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 2rem;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .quantity-input button {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
        }

        .quantity-input input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
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
            <div class="ms-auto">
                <a href="cart.php" class="btn btn-outline-light">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Product Detail -->
    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">หน้าแรก</a></li>
                <?php if ($product['category_name']): ?>
                    <li class="breadcrumb-item"><a href="index.php?category=<?php echo $product['category_name']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6">
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

            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <?php if ($total_reviews > 0): ?>
                        <div class="rating">
                            <?php echo renderStars($avg_rating); ?>
                            <span class="ms-2"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> รีวิว)</span>
                        </div>
                    <?php endif; ?>

                    <div class="price">฿<?php echo number_format($product['price'], 2); ?></div>

                    <?php
                    if ($product['stock'] > 10) {
                        echo '<span class="stock-status in-stock">มีสินค้า (' . $product['stock'] . ' ชิ้น)</span>';
                    } elseif ($product['stock'] > 0) {
                        echo '<span class="stock-status low-stock">สินค้าเหลือน้อย (' . $product['stock'] . ' ชิ้น)</span>';
                    } else {
                        echo '<span class="stock-status out-stock">สินค้าหมด</span>';
                    }
                    ?>

                    <p class="mt-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                    <?php if ($product['stock'] > 0): ?>
                        <form action="add_to_cart.php" method="POST" class="mt-4">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            
                            <div class="quantity-input">
                                <label>จำนวน:</label>
                                <button type="button" onclick="decreaseQuantity()">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                <button type="button" onclick="increaseQuantity()">+</button>
                            </div>

                            <button type="submit" class="btn btn-add-cart">
                                <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>รีวิวจากลูกค้า</h3>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Review Form -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="review-card mb-4">
                        <h5>เขียนรีวิว</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label>ให้คะแนน:</label>
                                <div class="star-rating" id="star-rating">
                                    <i class="far fa-star" data-rating="1"></i>
                                    <i class="far fa-star" data-rating="2"></i>
                                    <i class="far fa-star" data-rating="3"></i>
                                    <i class="far fa-star" data-rating="4"></i>
                                    <i class="far fa-star" data-rating="5"></i>
                                </div>
                                <input type="hidden" name="rating" id="rating" value="5" required>
                            </div>
                            <div class="mb-3">
                                <label>ความคิดเห็น:</label>
                                <textarea name="comment" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary">ส่งรีวิว</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <a href="login.php?redirect=product.php?id=<?php echo $product_id; ?>">เข้าสู่ระบบ</a> เพื่อเขียนรีวิว
                    </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                    <div class="review-rating">
                                        <?php echo renderStars($review['rating']); ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if ($related_products): ?>
            <div class="row mt-5">
                <h3>สินค้าที่เกี่ยวข้อง</h3>
                <?php foreach ($related_products as $related): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="product.php?id=<?php echo $related['id']; ?>" style="text-decoration: none; color: inherit;">
                            <div class="related-product">
                                <div class="related-product-image">
                                    <?php if ($related['image'] && file_exists('images/products/' . $related['image'])): ?>
                                        <img src="images/products/<?php echo $related['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-fish"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <h6><?php echo htmlspecialchars($related['name']); ?></h6>
                                    <p class="text-primary mb-0">฿<?php echo number_format($related['price'], 2); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Quantity buttons
        function increaseQuantity() {
            var input = document.getElementById('quantity');
            var max = parseInt(input.getAttribute('max'));
            var value = parseInt(input.value);
            if (value < max) {
                input.value = value + 1;
            }
        }

        function decreaseQuantity() {
            var input = document.getElementById('quantity');
            var value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        }

        // Star rating
        document.querySelectorAll('#star-rating i').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                document.getElementById('rating').value = rating;
                
                document.querySelectorAll('#star-rating i').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });

        // Initialize stars
        document.querySelectorAll('#star-rating i').forEach(s => {
            s.classList.remove('far');
            s.classList.add('fas');
        });
    </script>
</body>
</html>