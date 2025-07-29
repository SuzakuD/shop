<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Fishing Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('images/hero-bg.jpg') center/cover;
            height: 300px;
            display: flex;
            align-items: center;
            color: white;
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
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-3">About Fishing Store</h1>
                    <p class="lead">Your trusted partner in fishing adventures since 1995</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-5">
                        <h2 class="display-5 fw-bold mb-4">Our Story</h2>
                        <p class="lead text-muted">
                            For over 25 years, Fishing Store has been the premier destination for fishing enthusiasts 
                            across the region. What started as a small family business has grown into a trusted name 
                            in the fishing community.
                        </p>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <i class="fas fa-trophy fa-3x text-primary"></i>
                            </div>
                            <h4>Quality Products</h4>
                            <p class="text-muted">We carefully select only the highest quality fishing gear from trusted manufacturers worldwide.</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h4>Expert Advice</h4>
                            <p class="text-muted">Our team consists of experienced anglers who are passionate about sharing their knowledge.</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <i class="fas fa-shipping-fast fa-3x text-primary"></i>
                            </div>
                            <h4>Fast Delivery</h4>
                            <p class="text-muted">Quick and reliable shipping to get your gear to you when you need it most.</p>
                        </div>
                    </div>

                    <div class="bg-light p-5 rounded mb-5">
                        <h3 class="mb-4">Our Mission</h3>
                        <p class="mb-3">
                            At Fishing Store, our mission is to provide anglers of all skill levels with the tools, 
                            knowledge, and support they need to make every fishing trip successful and enjoyable.
                        </p>
                        <p class="mb-0">
                            We believe that fishing is more than just a hobby – it's a way to connect with nature, 
                            spend quality time with family and friends, and create lasting memories. That's why we're 
                            committed to offering not just products, but a complete fishing experience.
                        </p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <h4>Why Choose Us?</h4>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> 25+ years of experience</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Competitive prices</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Expert customer service</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Wide product selection</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Fast & reliable shipping</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Money-back guarantee</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>Our Values</h4>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-heart text-primary me-2"></i> Passion for fishing</li>
                                <li class="mb-2"><i class="fas fa-handshake text-primary me-2"></i> Customer satisfaction</li>
                                <li class="mb-2"><i class="fas fa-leaf text-primary me-2"></i> Environmental responsibility</li>
                                <li class="mb-2"><i class="fas fa-star text-primary me-2"></i> Quality assurance</li>
                                <li class="mb-2"><i class="fas fa-users text-primary me-2"></i> Community support</li>
                                <li class="mb-2"><i class="fas fa-graduation-cap text-primary me-2"></i> Continuous learning</li>
                            </ul>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <h4 class="mb-3">Ready to Start Your Fishing Adventure?</h4>
                        <a href="products.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-search"></i> Browse Products
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-fish"></i> Fishing Store</h5>
                    <p class="text-muted">Your trusted partner for all fishing adventures. Quality gear, expert advice, and unbeatable prices.</p>
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
</body>
</html>