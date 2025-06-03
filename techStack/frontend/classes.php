<?php
session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../includes/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize messages
$enroll_message = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle class enrollment
    if (isset($_POST['enroll_class'])) {
        // Verify CSRF token first
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Security token mismatch'];
            header("Location: ".$_SERVER['HTTP_REFERER']);
            exit();
        }

        // Validate class ID
        $class_id = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
        $user_id = $_SESSION['user_id'];

        if ($class_id === false || $class_id <= 0) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid class selection'];
            header("Location: ".$_SERVER['HTTP_REFERER']);
            exit();
        }

        try {
            // Verify database connection
            if (!$conn || $conn->connect_error) {
                throw new Exception("Database connection failed");
            }

            // Start transaction
            $conn->begin_transaction();

            // Check if class exists
           
            $stmt = $conn->prepare("SELECT user_id FROM enrollments WHERE user_id = ? AND class_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: ".$conn->error);
            }
            $stmt->bind_param("ii", $user_id, $class_id); // Bind BOTH user_id and class_id
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Class not found'];
                $conn->rollback();
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit();
            }

            // Check if already enrolled
            $stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND class_id = ?");
            $stmt->bind_param("ii", $user_id, $class_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $_SESSION['message'] = ['type' => 'warning', 'text' => 'You are already enrolled in this class'];
                $conn->rollback();
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit();
            }

            // Enroll the student
            $stmt = $conn->prepare("INSERT INTO enrollments (user_id, class_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $class_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Enrollment failed: ".$stmt->error);
            }

            // Initialize subject progress for all subjects in this class
            $init_stmt = $conn->prepare("
                INSERT INTO subject_progress (user_id, subject_id, status, progress)
                SELECT ?, id, 'not_started', 0 FROM subjects WHERE class_id = ?
            ");
            $init_stmt->bind_param("ii", $user_id, $class_id);
            $init_stmt->execute();

            // Commit transaction
            $conn->commit();

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Class enrollment successful!'];
           header("Location: classes.php?view=catalog");
            exit();

        } catch (Exception $e) {
            if (isset($conn) && $conn instanceof mysqli) {
                $conn->rollback();
            }
            error_log("Enrollment error: ".$e->getMessage());
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'System error - please try again later'];
            header("Location: ".$_SERVER['HTTP_REFERER']);
            exit();
        }
    }
    
    // Handle subject enrollment
    if (isset($_POST['enroll_subject'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $enroll_message = ['type' => 'danger', 'text' => 'Security token mismatch'];
        } else {
            $subject_id = filter_var($_POST['subject_id'], FILTER_VALIDATE_INT);
            $user_id = $_SESSION['user_id'];
            
            if ($subject_id === false) {
                $enroll_message = ['type' => 'danger', 'text' => 'Invalid subject selection'];
            } else {
                try {
                    // Check if subject exists and student is enrolled in the class
                    $stmt = $conn->prepare("
                        SELECT s.id FROM subjects s
                        JOIN enrollments e ON s.class_id = e.class_id
                        WHERE s.id = ? AND e.user_id = ?
                    ");
                    $stmt->bind_param("ii", $subject_id, $user_id);
                    $stmt->execute();
                    
                    if (!$stmt->get_result()->num_rows) {
                        $enroll_message = ['type' => 'danger', 'text' => 'Subject not available or you are not enrolled in this class'];
                    } else {
                        // Check if already enrolled in subject
                        $stmt = $conn->prepare("SELECT id FROM subject_enrollments WHERE user_id = ? AND subject_id = ?");
                        $stmt->bind_param("ii", $user_id, $subject_id);
                        $stmt->execute();
                        
                        if ($stmt->get_result()->num_rows) {
                            $enroll_message = ['type' => 'warning', 'text' => 'You are already enrolled in this subject'];
                        } else {
                            // Enroll the student in subject
                            $stmt = $conn->prepare("INSERT INTO subject_enrollments (user_id, subject_id) VALUES (?, ?)");
                            $stmt->bind_param("ii", $user_id, $subject_id);
                            $stmt->execute();
                            
                            $enroll_message = ['type' => 'success', 'text' => 'Subject enrollment successful!'];
                        }
                    }
                } catch (mysqli_sql_exception $e) {
                    error_log("Enrollment error: " . $e->getMessage());
                    $enroll_message = ['type' => 'danger', 'text' => 'System error - please try again later'];
                }
            }
        }
    }
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Check if we're showing the catalog view
$catalog_view = isset($_GET['view']) && $_GET['view'] === 'catalog';

// Get classes based on view mode
if ($catalog_view) {
    // Get all available classes for catalog view
    $classes = [];
    try {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM enrollments WHERE class_id = c.id AND user_id = ?) as is_enrolled
            FROM classes c
            ORDER BY c.class_name
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to load classes'];
    }
} else {
    // Get available classes (for students) in dashboard view
    if ($role == 'student') {
        $available_classes = $conn->prepare("
            SELECT c.* FROM classes c
            WHERE c.id NOT IN (
                SELECT class_id FROM enrollments WHERE user_id = ?
            )
            ORDER BY class_name
        ");
        $available_classes->bind_param("i", $user_id);
        $available_classes->execute();
        $available_classes = $available_classes->get_result();
    }

    // Get user's enrolled classes
    if ($role == 'teacher' || $role == 'admin') {
        $classes_stmt = $conn->prepare("SELECT * FROM classes WHERE created_by = ? ORDER BY class_name");
    } else {
        $classes_stmt = $conn->prepare("
            SELECT c.* FROM classes c
            JOIN enrollments e ON c.id = e.class_id
            WHERE e.user_id = ?
            ORDER BY class_name
        ");
    }
    $classes_stmt->bind_param("i", $user_id);
    $classes_stmt->execute();
    $user_classes = $classes_stmt->get_result();

    // Get recent activity
    $activity_stmt = $conn->prepare("SELECT * FROM user_activity WHERE user_id = ? ORDER BY activity_date DESC LIMIT 3");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    $recent_activity = $activity_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $catalog_view ? 'Class Catalog' : 'Dashboard' ?> - EduPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #1a1a2e;
            --success-color: #28a745;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.2);
        }
        
        .class-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .class-card .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
        }
        
        .subject-item {
            border-left: 3px solid var(--accent-color);
            transition: all 0.2s ease;
        }
        
        .subject-item:hover {
            background-color: rgba(76, 201, 240, 0.05);
        }
        
        .enrolled-badge {
            background-color: var(--success-color);
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            z-index: 100;
        }
        
        .available-class-card {
            border: 2px dashed var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        /* Catalog specific styles */
        .already-enrolled {
            opacity: 0.8;
            background-color: #f8f9fa;
        }
        
        .catalog-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .catalog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-graduation-cap me-2"></i>EduPortal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= !$catalog_view ? 'active' : '' ?>" href="home.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $catalog_view ? 'active' : '' ?>" href="classes.php?view=catalog"><i class="fas fa-book me-1"></i> Class Catalog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php"><i class="fas fa-chart-line me-1"></i> Progress</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=random" alt="User" class="rounded-circle me-2" width="32" height="32">
                            <span><?= htmlspecialchars($username) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Display messages -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['message']['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Enrollment Messages -->
        <?php if ($enroll_message): ?>
            <div class="alert alert-<?= $enroll_message['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($enroll_message['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($catalog_view): ?>
            <!-- Catalog View -->
            <h2 class="mb-4">Available Classes</h2>
            
            <div class="row">
                <?php foreach ($classes as $class): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 catalog-card <?= $class['is_enrolled'] ? 'already-enrolled' : '' ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($class['class_name']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($class['class_code']) ?></h6>
                                <p class="card-text"><?= htmlspecialchars($class['description'] ?? 'No description available') ?></p>
                                
                                <?php if ($class['is_enrolled']): ?>
                                    <span class="badge bg-success">Already Enrolled</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white">
                                <?php if (!$class['is_enrolled']): ?>
                                    <form action="classes.php" method="POST" class="enroll-form">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                        <button type="submit" name="enroll_class" class="btn btn-primary w-100">
                                            <span class="enroll-text">Enroll Now</span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="subjects.php?class_id=<?= $class['id'] ?>" class="btn btn-outline-success w-100">View Subjects</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- Dashboard View -->
            <!-- Welcome Banner -->
            <div class="welcome-banner p-4 mb-5 animate__animated animate__fadeIn">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-3">Hi <?= htmlspecialchars($username) ?>, welcome back!</h2>
                        <p class="mb-0">Continue your learning journey or explore new classes</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-inline-block bg-white text-primary px-3 py-2 rounded-pill">
                            <i class="fas fa-user-graduate me-2"></i>
                            <?= ucfirst($role) ?> Account
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Classes (for students) -->
            <?php if ($role == 'student' && $available_classes->num_rows > 0): ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="mb-4"><i class="fas fa-door-open me-2"></i>Available Classes</h3>
                        <div class="row g-4">
                            <?php while ($class = $available_classes->fetch_assoc()): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="card class-card h-100 available-class-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span><?= htmlspecialchars($class['class_name']) ?></span>
                                            <span class="badge bg-light text-dark"><?= $class['class_code'] ?></span>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text text-muted mb-3"><?= nl2br(htmlspecialchars(substr($class['description'], 0, 120))) ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-book-open me-1"></i>
                                                    <?= rand(3, 8) ?> Subjects available
                                                </small>
                                               <form action="classes.php" method="POST">
                                                   <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                   <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                                   <button type="submit" name="enroll_class" class="btn btn-primary">Enroll Now</button>
                                               </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Enrolled Classes -->
            <div class="row mb-5">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0"><i class="fas fa-book me-2"></i>Your Classes</h3>
                    <?php if ($role == 'teacher' || $role == 'admin'): ?>
                        <a href="create_class.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Class
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($user_classes->num_rows > 0): ?>
                    <?php while ($class = $user_classes->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6 mb-4 animate__animated animate__fadeInUp">
                            <div class="card class-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($class['class_name']) ?></span>
                                    <span class="badge bg-light text-dark"><?= $class['class_code'] ?></span>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-muted mb-3"><?= nl2br(htmlspecialchars(substr($class['description'], 0, 120))) ?></p>
                                    
                                    <?php 
                                    // Get subjects for this class
                                    $subject_stmt = $conn->prepare("SELECT * FROM subjects WHERE class_id = ?");
                                    $subject_stmt->bind_param("i", $class['id']);
                                    $subject_stmt->execute();
                                    $subjects = $subject_stmt->get_result();
                                    ?>
                                    
                                    <div class="mb-3">
                                        <h6><i class="fas fa-bookmark me-2"></i>Subjects</h6>
                                        <div class="list-group list-group-flush">
                                            <?php if ($subjects->num_rows > 0): ?>
                                                <?php while ($subject = $subjects->fetch_assoc()): ?>
                                                    <div class="list-group-item subject-item px-0 py-2 border-0">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong><?= htmlspecialchars($subject['subject_name']) ?></strong>
                                                                <small class="d-block text-muted"><?= htmlspecialchars($subject['subject_code']) ?></small>
                                                            </div>
                                                            <?php if ($role == 'student'): ?>
                                                                <?php 
                                                                // Check if student is enrolled in this subject
                                                                $enrolled_stmt = $conn->prepare("
                                                                    SELECT 1 FROM subject_enrollments 
                                                                    WHERE user_id = ? AND subject_id = ?
                                                                ");
                                                                $enrolled_stmt->bind_param("ii", $user_id, $subject['id']);
                                                                $enrolled_stmt->execute();
                                                                $is_enrolled = $enrolled_stmt->get_result()->num_rows > 0;
                                                                ?>
                                                                
                                                                <?php if ($is_enrolled): ?>
                                                                    <span class="badge enrolled-badge">
                                                                        <i class="fas fa-check me-1"></i>Enrolled
                                                                    </span>
                                                                <?php else: ?>
                                                                    <form method="POST" class="m-0">
                                                                        <input type="hidden" name="enroll_subject" value="1">
                                                                        <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                                            <i class="fas fa-user-plus me-1"></i>Enroll
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <a href="manage_subject.php?id=<?= $subject['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="fas fa-cog"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <div class="text-muted">No subjects available</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-book-open me-1"></i>
                                            <?= $subjects->num_rows ?> Subjects
                                        </small>
                                        <a href="subjects.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            View All <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">
                                    <?= ($role == 'teacher') ? "You haven't created any classes yet" : "You aren't enrolled in any classes yet" ?>
                                </h4>
                                <?php if ($role == 'teacher'): ?>
                                    <a href="create_class.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-2"></i>Create Your First Class
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted mt-2">Browse available classes above to get started</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity Section -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_activity->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                        <div class="list-group-item py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <small class="text-muted float-end"><?= date('h:i A', strtotime($activity['activity_date'])) ?></small>
                                                    <h6 class="mb-1"><?= htmlspecialchars($activity['activity_title']) ?></h6>
                                                    <p class="mb-0 text-muted"><?= htmlspecialchars($activity['activity_description']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clock fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No recent activity yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Resources Section -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><i class="fas fa-bookmark me-2"></i>Quick Resources</h4>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-pdf text-danger me-3 fa-lg"></i>
                                        <div>
                                            <h6 class="mb-1">Algebra Basics Guide</h6>
                                            <small class="text-muted">Mathematics • 2.4 MB</small>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-video text-primary me-3 fa-lg"></i>
                                        <div>
                                            <h6 class="mb-1">Introduction to Biology</h6>
                                            <small class="text-muted">Science • 15 min video</small>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-word text-info me-3 fa-lg"></i>
                                        <div>
                                            <h6 class="mb-1">English Essay Template</h6>
                                            <small class="text-muted">English • 1.1 MB</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating Action Button (for mobile) -->
    <a href="#" class="floating-btn d-lg-none animate__animated animate__bounceIn">
        <i class="fas fa-book-open"></i>
    </a>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>EduPortal</h5>
                    <p class="text-muted">Empowering students and educators with modern learning tools.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted">Home</a></li>
                        <li><a href="#" class="text-muted">Classes</a></li>
                        <li><a href="#" class="text-muted">Progress</a></li>
                        <li><a href="#" class="text-muted">Help Center</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-envelope me-2"></i> support@eduportal.com</li>
                        <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="text-center text-muted">
                <small>&copy; 2023 EduPortal. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation trigger
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.animate__animated');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add(entry.target.dataset.animate);
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => {
                observer.observe(card);
            });

            // Form submission handling for catalog view
            document.querySelectorAll('.enroll-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const button = this.querySelector('button[type="submit"]');
                    button.disabled = true;
                    const enrollText = button.querySelector('.enroll-text');
                    const spinner = button.querySelector('.spinner-border');
                    
                    if (enrollText) enrollText.classList.add('d-none');
                    if (spinner) spinner.classList.remove('d-none');
                });
            });
        });
    </script>
</body>
</html>