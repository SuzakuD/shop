<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Fishing Store</title>
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
        .contact-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .contact-card:hover {
            transform: translateY(-5px);
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
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
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

    <!-- Header -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 mb-3">Contact Us</h1>
                    <p class="lead">We'd love to hear from you! Get in touch with our team.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Content -->
    <section class="py-5">
        <div class="container">
            <!-- Contact Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card contact-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                            <h4>Visit Our Store</h4>
                            <p class="text-muted mb-0">
                                123 Fishing St<br>
                                Marina Bay, CA 90210<br>
                                United States
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card contact-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-phone fa-3x text-primary mb-3"></i>
                            <h4>Call Us</h4>
                            <p class="text-muted mb-0">
                                <strong>Store:</strong> (555) 123-4567<br>
                                <strong>Support:</strong> (555) 123-4568<br>
                                <strong>Fax:</strong> (555) 123-4569
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card contact-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                            <h4>Email Us</h4>
                            <p class="text-muted mb-0">
                                <strong>General:</strong> info@fishingstore.com<br>
                                <strong>Support:</strong> support@fishingstore.com<br>
                                <strong>Sales:</strong> sales@fishingstore.com
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form & Info -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Send Us a Message</h4>
                        </div>
                        <div class="card-body">
                            <form id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="firstName" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="lastName" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone">
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subject *</label>
                                        <select class="form-select" id="subject" required>
                                            <option value="">Choose a subject...</option>
                                            <option value="general">General Inquiry</option>
                                            <option value="product">Product Question</option>
                                            <option value="order">Order Support</option>
                                            <option value="return">Returns & Exchanges</option>
                                            <option value="feedback">Feedback</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message *</label>
                                        <textarea class="form-control" id="message" rows="5" placeholder="Tell us how we can help you..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="newsletter">
                                            <label class="form-check-label" for="newsletter">
                                                Subscribe to our newsletter for fishing tips and special offers
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Store Hours</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>Monday - Friday</span>
                                    <span>9:00 AM - 8:00 PM</span>
                                </li>
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>Saturday</span>
                                    <span>8:00 AM - 9:00 PM</span>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span>Sunday</span>
                                    <span>10:00 AM - 6:00 PM</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>FAQ</h5>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            Do you offer returns?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Yes! We offer 30-day returns on all products in original condition.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            How fast is shipping?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Standard shipping takes 3-5 business days. Express shipping is 1-2 days.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                            Do you price match?
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Yes, we price match with authorized dealers. Contact us for details.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>Follow Us</h5>
                        </div>
                        <div class="card-body text-center">
                            <a href="#" class="btn btn-outline-primary me-2 mb-2">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info me-2 mb-2">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger me-2 mb-2">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="btn btn-outline-dark mb-2">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
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
    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Here you would normally send the form data to a server
            alert('Thank you for your message! We\'ll get back to you soon.');
            
            // Reset the form
            this.reset();
        });
    </script>
</body>
</html>