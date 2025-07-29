<?php
session_start();
require_once 'config.php';

$cart_count = getCartCount();

$success = '';
$errors = [];

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($message)) $errors[] = 'Message is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    
    if (empty($errors)) {
        // In a real application, you would send this email or save to database
        // For now, we'll just show a success message
        $success = 'Thank you for your message! We will get back to you within 24 hours.';
        
        // Clear form data on success
        $name = $email = $phone = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?= $site_name ?></title>
    
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
        
        .contact-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,102,204,0.25);
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
        
        .contact-form {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .map-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            height: 400px;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 3rem;
        }
        
        .office-hours {
            background: var(--light-gray);
            border-radius: var(--border-radius);
            padding: 2rem;
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
                        <a class="nav-link fw-medium active" href="contact.php">Contact</a>
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
                    <h1 class="display-4 fw-bold mb-4">Get in Touch</h1>
                    <p class="lead">
                        We're here to help! Contact us for expert fishing advice, product questions, 
                        or just to share your latest fishing adventure.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card text-center">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Visit Our Store</h4>
                        <p class="text-muted mb-2">123 Fishing Street</p>
                        <p class="text-muted mb-2">Marina Bay, CA 90210</p>
                        <p class="text-muted">United States</p>
                        <a href="#" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="fas fa-directions me-2"></i>Get Directions
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card text-center">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Call Us</h4>
                        <p class="text-muted mb-2">
                            <strong>Phone:</strong> +1 (555) 123-4567
                        </p>
                        <p class="text-muted mb-2">
                            <strong>Toll Free:</strong> 1-800-FISHING
                        </p>
                        <p class="text-muted">Mon-Sat: 9AM-6PM PST</p>
                        <a href="tel:+15551234567" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="fas fa-phone me-2"></i>Call Now
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card text-center">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Email Us</h4>
                        <p class="text-muted mb-2">
                            <strong>General:</strong> info@fishingstore.com
                        </p>
                        <p class="text-muted mb-2">
                            <strong>Support:</strong> support@fishingstore.com
                        </p>
                        <p class="text-muted">We respond within 24 hours</p>
                        <a href="mailto:info@fishingstore.com" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="fas fa-envelope me-2"></i>Send Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form and Map -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-6">
                    <div class="contact-form">
                        <h2 class="mb-4">
                            <i class="fas fa-paper-plane me-2 text-primary"></i>Send Us a Message
                        </h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label fw-medium">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($name ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label fw-medium">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label fw-medium">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($phone ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label fw-medium">Subject *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Choose a subject...</option>
                                        <option value="product_inquiry" <?= ($subject ?? '') === 'product_inquiry' ? 'selected' : '' ?>>Product Inquiry</option>
                                        <option value="technical_support" <?= ($subject ?? '') === 'technical_support' ? 'selected' : '' ?>>Technical Support</option>
                                        <option value="order_status" <?= ($subject ?? '') === 'order_status' ? 'selected' : '' ?>>Order Status</option>
                                        <option value="fishing_advice" <?= ($subject ?? '') === 'fishing_advice' ? 'selected' : '' ?>>Fishing Advice</option>
                                        <option value="feedback" <?= ($subject ?? '') === 'feedback' ? 'selected' : '' ?>>Feedback</option>
                                        <option value="other" <?= ($subject ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label fw-medium">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" 
                                          placeholder="Tell us how we can help you..." required><?= htmlspecialchars($message ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    Subscribe to our newsletter for fishing tips and special offers
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Map and Office Hours -->
                <div class="col-lg-6">
                    <div class="mb-4">
                        <h2 class="mb-4">
                            <i class="fas fa-map-marked-alt me-2 text-primary"></i>Find Us
                        </h2>
                        <div class="map-container">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                    
                    <div class="office-hours">
                        <h4 class="fw-bold mb-3">
                            <i class="fas fa-clock me-2 text-primary"></i>Store Hours
                        </h4>
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-2"><strong>Monday - Friday</strong></p>
                                <p class="mb-2"><strong>Saturday</strong></p>
                                <p class="mb-2"><strong>Sunday</strong></p>
                            </div>
                            <div class="col-6">
                                <p class="mb-2">9:00 AM - 6:00 PM</p>
                                <p class="mb-2">8:00 AM - 8:00 PM</p>
                                <p class="mb-2">10:00 AM - 5:00 PM</p>
                            </div>
                        </div>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Holiday Hours:</strong> Please call ahead during holidays as our hours may vary.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">
                <i class="fas fa-question-circle me-2 text-primary"></i>Frequently Asked Questions
            </h2>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer a 30-day return policy on all unused items in their original packaging. 
                                    Custom or personalized items may not be returnable. Please contact us for specific details.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Do you offer fishing gear recommendations?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutely! Our experienced team is happy to help you choose the right gear based on 
                                    your fishing style, target species, and budget. Contact us or visit our store for personalized advice.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How long does shipping take?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Standard shipping takes 3-5 business days. Expedited shipping options are available. 
                                    We offer free shipping on orders over $75 within the continental United States.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Do you repair fishing equipment?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer repair services for select items including rod repairs and reel maintenance. 
                                    Contact us with details about your equipment and we'll let you know if we can help.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>