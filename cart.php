<?php
session_start();
require_once 'config.php';

// Require login for cart
requireLogin();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            
            if ($product_id) {
                // Check if product exists and has stock
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product && $product['stock'] >= $quantity) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) 
                                             ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
                        $response['success'] = true;
                        $response['message'] = 'Product added to cart';
                    } catch (PDOException $e) {
                        $response['message'] = 'Error adding to cart';
                    }
                } else {
                    $response['message'] = 'Product not available or insufficient stock';
                }
            }
            break;
            
        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = max(0, (int)($_POST['quantity'] ?? 0));
            
            if ($product_id) {
                if ($quantity > 0) {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $product_id]);
                }
                $response['success'] = true;
                $response['message'] = 'Cart updated';
            }
            break;
            
        case 'remove':
            $product_id = (int)($_POST['product_id'] ?? 0);
            
            if ($product_id) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $product_id]);
                $response['success'] = true;
                $response['message'] = 'Product removed from cart';
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get cart items
$cart_items = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
");
$cart_items->execute([$_SESSION['user_id']]);
$cart_items = $cart_items->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = $subtotal >= 75 ? 0 : 9.99; // Free shipping over $75
$tax = $subtotal * 0.08; // 8% tax
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?= $site_name ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #004499;
            --accent-color: #ff6b35;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --border-radius: 12px;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .cart-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            background: var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
        }
        
        .summary-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 100px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: var(--border-radius);
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,102,204,0.3);
        }
        
        .navbar {
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            background: white !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish me-2"></i><?= $site_name ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-medium" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?= htmlspecialchars($_SESSION['full_name']) ?>
                            <?php if (isAdmin()): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?action=logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item ms-3">
                        <span class="nav-link">
                            <i class="fas fa-shopping-cart fs-5 text-primary"></i>
                            <span class="badge bg-danger"><?= count($cart_items) ?></span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center mb-4">
                    <h1 class="mb-0 me-3">
                        <i class="fas fa-shopping-cart me-2 text-primary"></i>Shopping Cart
                    </h1>
                    <span class="badge bg-secondary fs-6"><?= count($cart_items) ?> items</span>
                </div>
            </div>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fs-1 text-muted mb-3"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted mb-4">Add some products to get started!</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Browse Products
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div id="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="product-image me-3">
                                                <img src="images/<?= htmlspecialchars($item['image']) ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                                     onerror="this.src='images/placeholder.jpg'">
                                            </div>
                                            <div>
                                                <h5 class="mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                                <p class="text-muted mb-0">
                                                    <?= formatPrice($item['price']) ?> each
                                                </p>
                                                <small class="text-muted">Stock: <?= $item['stock'] ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-outline-secondary btn-sm" 
                                                    onclick="updateQuantity(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control quantity-input mx-2" 
                                                   value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                                                   onchange="updateQuantity(<?= $item['product_id'] ?>, this.value)">
                                            <button class="btn btn-outline-secondary btn-sm" 
                                                    onclick="updateQuantity(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)"
                                                    <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="text-end">
                                            <strong class="item-total">
                                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                                            </strong>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-1">
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="removeItem(<?= $item['product_id'] ?>)"
                                                title="Remove item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h4 class="mb-4">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h4>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <strong id="subtotal"><?= formatPrice($subtotal) ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping:</span>
                            <span id="shipping">
                                <?php if ($shipping > 0): ?>
                                    <?= formatPrice($shipping) ?>
                                <?php else: ?>
                                    <span class="text-success">Free</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($subtotal < 75): ?>
                            <div class="alert alert-info p-2 mb-3">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Add <?= formatPrice(75 - $subtotal) ?> more for free shipping!
                                </small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax (8%):</span>
                            <span id="tax"><?= formatPrice($tax) ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="fs-5 text-primary" id="total"><?= formatPrice($total) ?></strong>
                        </div>
                        
                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg mb-3" onclick="proceedToCheckout()">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </button>
                            <button class="btn btn-outline-secondary" onclick="clearCart()">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateQuantity(productId, quantity) {
            quantity = Math.max(0, parseInt(quantity));
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        }
        
        function removeItem(productId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error removing item: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }
        
        function clearCart() {
            if (confirm('Are you sure you want to clear your entire cart?')) {
                // Remove all items
                const items = document.querySelectorAll('.cart-item');
                items.forEach(item => {
                    const productId = item.dataset.productId;
                    fetch('cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=remove&product_id=${productId}`
                    });
                });
                
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        }
        
        function proceedToCheckout() {
            alert('Checkout functionality would be implemented here!');
            // window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>