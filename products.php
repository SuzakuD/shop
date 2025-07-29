<?php
session_start();
include 'config.php';

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $is_admin = ($user && $user['role'] === 'admin');
}

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build the query
$where_conditions = [];
$params = [];

if ($category_id > 0) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM products $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get products
$query = "SELECT * FROM products $where_clause ORDER BY name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get selected category name
$selected_category = null;
if ($category_id > 0) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $selected_category = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Fishing Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-3px);
        }
        .navbar-brand {
            font-weight: bold;
            color: #2c5aa0 !important;
        }
        .btn-primary {
            background-color: #2c5aa0;
            border-color: #2c5aa0;
        }
        .btn-primary:hover {
            background-color: #1e3d6f;
            border-color: #1e3d6f;
        }
        .filter-sidebar {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish"></i> Fishing Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <?php if ($is_admin): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="index.php?action=logout">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary me-2" href="index.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-3">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-primary">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="display-4 mb-3">Our Products</h1>
                    <p class="lead">Discover our wide range of quality fishing equipment</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-3"><i class="fas fa-filter"></i> Filters</h5>
                    
                    <!-- Search -->
                    <form method="GET" class="mb-4">
                        <?php if ($category_id): ?>
                            <input type="hidden" name="category" value="<?= $category_id ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Categories -->
                    <h6 class="mb-3">Categories</h6>
                    <div class="list-group">
                        <a href="products.php" class="list-group-item list-group-item-action <?= $category_id == 0 ? 'active' : '' ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="products.php?category=<?= $category['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                               class="list-group-item list-group-item-action <?= $category_id == $category['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Products -->
            <div class="col-lg-9">
                <!-- Results info -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0">
                            <?php if ($selected_category): ?>
                                <?= htmlspecialchars($selected_category) ?>
                            <?php elseif ($search): ?>
                                Search Results for "<?= htmlspecialchars($search) ?>"
                            <?php else: ?>
                                All Products
                            <?php endif; ?>
                        </h5>
                        <small class="text-muted"><?= $total_products ?> products found</small>
                    </div>
                    <div>
                        <?php if ($search || $category_id): ?>
                            <a href="products.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all categories.</p>
                        <a href="products.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card product-card h-100">
                                    <img src="images/<?= htmlspecialchars($product['image']) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <p class="card-text text-muted flex-grow-1">
                                            <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...
                                        </p>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="h5 text-primary mb-0">$<?= number_format($product['price'], 2) ?></span>
                                                <span class="text-muted small">Stock: <?= $product['stock'] ?></span>
                                            </div>
                                            <div class="d-grid gap-2">
                                                <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                <?php if ($product['stock'] > 0): ?>
                                                    <button class="btn btn-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled>
                                                        <i class="fas fa-times"></i> Out of Stock
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $category_id ? '&category=' . $category_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $category_id ? '&category=' . $category_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $category_id ? '&category=' . $category_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-fish"></i> Fishing Store</h5>
                    <p class="text-muted">Your trusted partner for all fishing adventures.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="products.php" class="text-muted text-decoration-none">Products</a></li>
                        <li><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-muted text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@fishingstore.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Fishing St, Marina Bay</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <p>&copy; 2024 Fishing Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            // Add to cart functionality would go here
            alert('Product added to cart! (This would be implemented with actual cart functionality)');
        }
    </script>
</body>
</html>