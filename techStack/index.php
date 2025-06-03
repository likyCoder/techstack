<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: frontend/home.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/db.php';

    $usernameOrEmail = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: frontend/home.php');
            exit();
        } else {
            $login_error = "Invalid username/email or password.";
        }
    } else {
        $login_error = "Invalid username/email or password.";
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<style>
  .hero-section {
    background: linear-gradient(135deg, #e0f7fa, #fff3e0);
    padding: 60px 20px;
    color: #222;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .hero-container {
    max-width: 1200px;
    margin: auto;
  }

  .hero-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
  }

  .hero-text {
    flex: 1 1 500px;
    padding: 20px;
  }

  .hero-text h1 {
    font-size: 2.5em;
    color: #0d47a1;
    margin-bottom: 15px;
  }

  .hero-text p {
    font-size: 1.1em;
    line-height: 1.6;
    margin-bottom: 25px;
  }

  .hero-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .btn-primary, .btn-outline {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1em;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .btn-primary {
    background-color: #0d47a1;
    color: white;
  }

  .btn-primary:hover {
    background-color: #08306b;
  }

  .btn-outline {
    border: 2px solid #0d47a1;
    color: #0d47a1;
  }

  .btn-outline:hover {
    background-color: #0d47a1;
    color: white;
  }

  .hero-image {
    flex: 1 1 400px;
    padding: 20px;
  }

  .hero-image img {
    width: 100%;
    max-width: 500px;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
  }

  /* Responsive Tweaks */
  @media (max-width: 768px) {
    .hero-text h1 {
      font-size: 2em;
    }
    .hero-row {
      flex-direction: column;
      text-align: center;
    }
    .hero-buttons {
      justify-content: center;
    }
  }
</style>
<style>
html {
  scroll-behavior: smooth;
}
/* Scroll to Top Button */
#scrollTopBtn {
  position: fixed;
  bottom: 30px;
  right: 25px;
  z-index: 99;
  font-size: 20px;
  background: linear-gradient(135deg, #2196f3, #21cbf3);
  color: white;
  border: none;
  outline: none;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  box-shadow: 0 4px 15px rgba(33, 203, 243, 0.4);
  cursor: pointer;
  display: none;
  transition: opacity 0.3s, transform 0.3s;
}

#scrollTopBtn:hover {
  transform: translateY(-3px);
  background: linear-gradient(135deg, #1e88e5, #00bcd4);
}

</style>

<body>
<?php
include "includes/header.php";
?>

   <!-- Hero Section -->
<section class="hero-section">
  <div class="hero-container">
    <div class="hero-row">
      <div class="hero-text">
        <h1>Your Gateway to Seamless Learning</h1>
        <p>
          EduPortal connects students with their class materials in one secure, easy-to-use platform. Access your subjects, resources, and assignments anytime, anywhere.
        </p>
        <div class="hero-buttons">
          <a href="register.php" class="btn-primary">Get Started</a>
          <a href="#features" class="btn-outline">Learn More</a>
        </div>
      </div>
      <div class="hero-image">
        <img src="assets/images/study.jpg" alt="Study Image" />
      </div>
    </div>
  </div>
</section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center section-title">Key Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure Authentication</h3>
                        <p>Our robust login system ensures your data stays protected with secure password hashing and session management.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3>Class Organization</h3>
                        <p>Easily navigate through your classes with our intuitive interface designed for optimal educational workflow.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>Subject Resources</h3>
                        <p>Access all your subject materials in one place, including descriptions, resources, and assignment details.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
     <!-- Login Section -->
    <section id="login" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4">Login to Your Account</h2>
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                include 'includes/db.php';

                                $username = $_POST['username'];
                                $password = $_POST['password'];

                                $query = "SELECT * FROM users WHERE username = ? OR email = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("ss", $username, $username);         
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows == 1) {
                                    $user = $result->fetch_assoc();
                                    if (password_verify($password, $user['password'])) {
                                        session_start();
                                        $_SESSION['user_id'] = $user['id'];
                                        $_SESSION['username'] = $user['username'];
                                        $_SESSION['role'] = $user['role'];
                                        header('Location: classes.php');
                                        exit();
                                    } else {
                                        echo '<div class="alert alert-danger">Invalid username or password.</div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-danger">Invalid username or password.</div>';
                                }
                                $stmt->close();
                                $conn->close();
                            }
                            ?>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username or Email</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Login</button>
                                </div>
                                <div class="text-center mt-3">
                                    <p>Don't have an account? <a href="register.php">Sign up</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 how-it-works">
        <div class="container">
            <h2 class="text-center section-title">How It Works</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Create Your Account</h3>
                        <p>Sign up with your details to create a secure account. We only require your name, email, and a password.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Access Your Classes</h3>
                        <p>After logging in, you'll see all your available classes in a clean, organized dashboard.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Explore Subjects</h3>
                        <p>Click on any class to view all subjects within it, complete with descriptions and learning resources.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5">
        <div class="container">
            <h2 class="text-center section-title">What Our Users Say</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="testimonial-text">EduPortal has transformed how I access my course materials. Everything is so well organized and easy to find.</p>
                        <div class="testimonial-author">- Sarah Johnson, Student</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="testimonial-text">As an instructor, I appreciate the clean interface and secure system. My students have no trouble navigating the platform.</p>
                        <div class="testimonial-author">- Prof. Michael Chen</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p class="testimonial-text">The authentication system gives me peace of mind knowing my child's educational data is secure.</p>
                        <div class="testimonial-author">- David Wilson, Parent</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 how-it-works">
        <div class="container">
            <h2 class="text-center section-title">Simple Pricing</h2>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3>Basic</h3>
                        </div>
                        <div class="pricing-body text-center">
                            <div class="price">$0<span>/month</span></div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Access to 3 classes</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Basic subject materials</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Secure authentication</li>
                                <li class="mb-2 text-muted"><i class="fas fa-times me-2"></i> Advanced resources</li>
                                <li class="text-muted"><i class="fas fa-times me-2"></i> Priority support</li>
                            </ul>
                            <a href="register.php" class="btn btn-outline-primary">Get Started</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="pricing-header" style="background-color: var(--secondary-color);">
                            <h3>Premium</h3>
                            <div class="badge bg-light text-primary">Most Popular</div>
                        </div>
                        <div class="pricing-body text-center">
                            <div class="price">$9<span>/month</span></div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Unlimited classes</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> All subject materials</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Secure authentication</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Advanced resources</li>
                                <li><i class="fas fa-check text-success me-2"></i> Priority support</li>
                            </ul>
                            <a href="#login" class="btn btn-primary">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
           <!-- Scroll to Top Button -->
               <button id="scrollTopBtn" title="Go to top">
                 ↑
               </button>
               
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h4 class="mb-4">EduPortal</h4>
                    <p>Empowering students and educators with a secure, intuitive platform for accessing educational resources.</p>
                    <div class="social-icons mt-4">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-5 mb-md-0">
                    <div class="footer-links">
                        <h5>Quick Links</h5>
                        <a href="#features">Features</a>
                        <a href="#how-it-works">How It Works</a>
                        <a href="#testimonials">Testimonials</a>
                        <a href="#pricing">Pricing</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-5 mb-md-0">
                    <div class="footer-links">
                        <h5>Resources</h5>
                        <a href="#">Help Center</a>
                        <a href="#">Documentation</a>
                        <a href="#">Community</a>
                        <a href="#">Webinars</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="footer-links">
                        <h5>Contact Us</h5>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Education St, Learning City</p>
                        <p><i class="fas fa-phone me-2"></i> (123) 769256132</p>
                        <p><i class="fas fa-envelope me-2"></i> info@eduportal.com</p>
                    </div>
                </div>
            </div>
           <div class="copyright text-center">
    <p>&copy; 2023 EduPortal. All rights reserved.</p>

    <div class="footer-contact" style="margin-top: 15px;">
        <h5>📫 Contact</h5>
        <p>If you have any questions, suggestions, or feedback, feel free to reach out:</p>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li>📧 Email: <a href="mailto:likjosh123@gmail.com">likjosh123@gmail.com</a></li>
            <li>🌐 Website: <a href="https://likyjosh.likesyou.org" target="_blank">likyjosh.likesyou.org</a></li>
            <li>🌐 Website: <a href="https://likysolutions.vercel.app/" target="_blank">likysolutions.vercel.app</a></li>
        </ul>
    </div>
</div>

        </div>
    </footer>
    <script>
  const scrollTopBtn = document.getElementById("scrollTopBtn");

  // Show button after scrolling down
  window.onscroll = function () {
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
      scrollTopBtn.style.display = "block";
    } else {
      scrollTopBtn.style.display = "none";
    }
  };

  // Scroll to top when clicked
  scrollTopBtn.onclick = function () {
    window.scrollTo({
      top: 0,
      behavior: "smooth"
    });
  };
</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script src="assets/scripts.js"></script>
</body>
</html>