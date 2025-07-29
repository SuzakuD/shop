<?php
session_start();
require_once 'config.php';

$cart_count = getCartCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?= $site_name ?></title>
    
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
        
        .navbar {
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            background: white !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(0,102,204,0.9), rgba(0,68,153,0.9)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23e3f2fd" width="1200" height="600"/><path fill="%23bbdefb" d="M0,100 Q300,50 600,100 T1200,100 L1200,600 L0,600 Z"/></svg>') center/cover;
            min-height: 400px;
            display: flex;
            align-items: center;
            color: white;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 3rem;
            position: relative;
            text-align: center;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }
        
        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .team-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .team-photo {
            height: 250px;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary-color);
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
        
        .stats-section {
            background: var(--light-gray);
            padding: 4rem 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
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
                        <a class="nav-link fw-medium active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav align-items-center">
                    <?php if (isLoggedIn()): ?>
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
                    <?php else: ?>
                        <li class="nav-item me-2">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="register.php">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item ms-3">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart fs-5"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                                    <?= $cart_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">About Our Fishing Store</h1>
                    <p class="lead">
                        For over 25 years, we've been passionate about providing anglers with the finest fishing equipment 
                        and expert advice to make every fishing adventure unforgettable.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="mb-4">Our Story</h2>
                    <p class="mb-4">
                        Founded in 1999 by a group of passionate anglers, our fishing store began as a small tackle shop 
                        with a simple mission: to provide fellow fishing enthusiasts with the highest quality gear and 
                        knowledgeable advice.
                    </p>
                    <p class="mb-4">
                        Over the years, we've grown from a local tackle shop to a trusted online destination for anglers 
                        across the country. Our team combines decades of fishing experience with a commitment to customer 
                        service that's second to none.
                    </p>
                    <p>
                        Whether you're a weekend warrior or a professional angler, we're here to help you find the perfect 
                        equipment for your next fishing adventure.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <div style="height: 400px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 5rem;">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Why Choose Us</h2>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Premium Quality</h4>
                        <p class="text-muted">
                            We carefully select only the finest fishing equipment from trusted manufacturers 
                            to ensure you get the best performance on the water.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Expert Advice</h4>
                        <p class="text-muted">
                            Our team of experienced anglers is always ready to share their knowledge 
                            and help you choose the right gear for your fishing needs.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Fast Shipping</h4>
                        <p class="text-muted">
                            Get your gear quickly with our fast and reliable shipping. 
                            Free shipping on orders over $75!
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Customer Focused</h4>
                        <p class="text-muted">
                            Your satisfaction is our priority. We stand behind every product 
                            and offer excellent customer service.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">25+</span>
                        <h5 class="fw-bold">Years in Business</h5>
                        <p class="text-muted">Serving the fishing community since 1999</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">50,000+</span>
                        <h5 class="fw-bold">Happy Customers</h5>
                        <p class="text-muted">Anglers who trust us for their gear</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">1,000+</span>
                        <h5 class="fw-bold">Products</h5>
                        <p class="text-muted">Extensive selection of fishing equipment</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <h5 class="fw-bold">Satisfaction Rate</h5>
                        <p class="text-muted">Customer satisfaction is our priority</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Meet Our Team</h2>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <div class="team-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="fw-bold mb-2">John Anderson</h4>
                            <p class="text-primary mb-3">Founder & CEO</p>
                            <p class="text-muted">
                                With 30+ years of fishing experience, John founded our store with 
                                a vision to provide the best fishing gear to fellow anglers.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <div class="team-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="fw-bold mb-2">Sarah Mitchell</h4>
                            <p class="text-primary mb-3">Head of Customer Service</p>
                            <p class="text-muted">
                                Sarah ensures every customer receives exceptional service and 
                                expert advice for their fishing adventures.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <div class="team-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="p-4 text-center">
                            <h4 class="fw-bold mb-2">Mike Johnson</h4>
                            <p class="text-primary mb-3">Product Specialist</p>
                            <p class="text-muted">
                                Mike's expertise in fishing equipment helps us select the best 
                                products and provide technical guidance to customers.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">Ready to Start Your Fishing Adventure?</h2>
                    <p class="lead mb-4">
                        Browse our extensive collection of premium fishing equipment and gear up for your next trip!
                    </p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="products.php" class="btn btn-light btn-lg">
                            <i class="fas fa-search me-2"></i>Shop Now
                        </a>
                        <a href="contact.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>