<?php
require_once 'includes/db_connect.php';

$errors = [];
$username = $email = $first_name = $last_name = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Trim and assign inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'student'; // Default role

    // Validation
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Check for duplicate username or email
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "Username or email already exists.";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $password_hash, $first_name, $last_name, $role);
            $stmt->execute();
            $stmt->close();

            header("Location: index.php?registration=success");
            exit();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - EduPortal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --error-color: #ff3333;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .auth-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }
        
        .auth-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            background: white;
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .auth-header h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .auth-body {
            padding: 2.5rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid #e0e0e0;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-block {
            width: 100%;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
        }
        
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            margin-top: 0.5rem;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            background: var(--error-color);
            transition: width 0.3s ease;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }
        
        .requirement i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }
        
        .requirement.valid {
            color: var(--success-color);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .auth-footer a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6c757d;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .divider::before {
            margin-right: 1rem;
        }
        
        .divider::after {
            margin-left: 1rem;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 0 15px;
            }
            
            .auth-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Join EduPortal</h2>
                <p>Create your account to access premium educational resources</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Please fix the following issues:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($first_name) ?>" required placeholder="John">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($last_name) ?>" required placeholder="Doe">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($username) ?>" required placeholder="johndoe">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($email) ?>" required placeholder="john@example.com">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="8" required placeholder="At least 8 characters">
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strength-bar"></div>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement" id="length-req">
                                <i class="fas fa-circle"></i> At least 8 characters
                            </div>
                            <div class="requirement" id="number-req">
                                <i class="fas fa-circle"></i> Contains a number
                            </div>
                            <div class="requirement" id="special-req">
                                <i class="fas fa-circle"></i> Contains a special character
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" minlength="8" required placeholder="Confirm your password">
                        </div>
                        <div class="mt-2" id="password-match"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block py-2 mb-3">
                        <i class="fas fa-user-plus me-2"></i> Create Account
                    </button>
                    
                    <div class="auth-footer">
                        <p>Already have an account? <a href="index.php">Sign in here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Password Strength Checker -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('strength-bar');
            const lengthReq = document.getElementById('length-req');
            const numberReq = document.getElementById('number-req');
            const specialReq = document.getElementById('special-req');
            const passwordMatch = document.getElementById('password-match');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check length
                if (password.length >= 8) {
                    strength += 1;
                    lengthReq.classList.add('valid');
                    lengthReq.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    lengthReq.classList.remove('valid');
                    lengthReq.querySelector('i').className = 'fas fa-circle';
                }
                
                // Check for numbers
                if (/\d/.test(password)) {
                    strength += 1;
                    numberReq.classList.add('valid');
                    numberReq.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    numberReq.classList.remove('valid');
                    numberReq.querySelector('i').className = 'fas fa-circle';
                }
                
                // Check for special characters
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    strength += 1;
                    specialReq.classList.add('valid');
                    specialReq.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    specialReq.classList.remove('valid');
                    specialReq.querySelector('i').className = 'fas fa-circle';
                }
                
                // Update strength bar
                let width = 0;
                let color = '#ff3333'; // red
                
                if (strength === 1) {
                    width = 33;
                    color = '#ff3333'; // red
                } else if (strength === 2) {
                    width = 66;
                    color = '#ffcc00'; // yellow
                } else if (strength === 3) {
                    width = 100;
                    color = '#4bb543'; // green
                }
                
                strengthBar.style.width = width + '%';
                strengthBar.style.backgroundColor = color;
            });
            
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value && this.value.length > 0) {
                    passwordMatch.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Passwords do not match</span>';
                } else if (this.value === passwordInput.value && this.value.length > 0) {
                    passwordMatch.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Passwords match</span>';
                } else {
                    passwordMatch.innerHTML = '';
                }
            });
        });
    </script>
</body>
</html>