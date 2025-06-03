<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db_connect.php';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get user's classes based on role
if ($role == 'teacher' || $role == 'admin') {
    $stmt = $conn->prepare("SELECT * FROM classes WHERE created_by = ?");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT c.* FROM classes c JOIN enrollments e ON c.id = e.class_id WHERE e.user_id = ?");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$classes = $stmt->get_result();

// Get recent announcements (if any)
$announcements = [];
if ($role == 'admin') {
    $stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EduPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .welcome-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: none;
        }
        
        .class-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            border: none;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .class-card .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .announcement-card {
            border-left: 4px solid var(--accent-color);
        }
        
        .quick-actions .btn {
            border-radius: 50px;
            padding: 10px 20px;
            margin: 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">EduPortal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="home.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="classes.php"><i class="fas fa-layer-group me-1"></i> My Classes</a>
                    </li>
                    <?php if ($role == 'teacher' || $role == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="create_class.php"><i class="fas fa-plus-circle me-1"></i> Create Class</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-cog me-1"></i> Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($username) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Welcome back, <?= htmlspecialchars($username) ?>!</h1>
                    <p class="mb-0"><?= 
                        $role == 'admin' ? 'Administrator Dashboard' : 
                        ($role == 'teacher' ? 'Instructor Dashboard' : 'Student Dashboard') 
                    ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-light text-primary fs-6 p-2"><?= ucfirst($role) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Welcome Card -->
                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="card-title">Getting Started</h3>
                                <p class="card-text">
                                    <?php if ($role == 'student'): ?>
                                        Browse your classes, access materials, and track your progress.
                                    <?php elseif ($role == 'teacher'): ?>
                                        Manage your classes, create subjects, and upload resources for your students.
                                    <?php else: ?>
                                        Oversee the entire platform, manage users, and create announcements.
                                    <?php endif; ?>
                                </p>
                                <div class="quick-actions">
                                    <a href="classes.php" class="btn btn-primary">
                                        <i class="fas fa-layer-group me-1"></i> View Classes
                                    </a>
                                    <?php if ($role == 'teacher' || $role == 'admin'): ?>
                                        <a href="create_class.php" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-1"></i> New Class
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($role == 'admin'): ?>
                                        <a href="admin_dashboard.php" class="btn btn-outline-dark">
                                            <i class="fas fa-cog me-1"></i> Admin Panel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-center d-none d-md-block">
                                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User" style="height: 120px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Classes -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h3 class="mb-0"><i class="fas fa-book-open me-2"></i> My Classes</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($classes->num_rows > 0): ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <?php while ($class = $classes->fetch_assoc()): ?>
                                    <div class="col">
                                        <div class="card class-card h-100">
                                            <div class="card-header">
                                                <?= htmlspecialchars($class['class_name']) ?>
                                            </div>
                                         <div class="card-body">
                                         <h6 class="card-subtitle mb-2 text-muted"><?= $class['class_code'] ?></h6>
                                         <p class="card-text"><?= nl2br(htmlspecialchars(substr($class['description'], 0, 100) . (strlen($class['description']) > 100 ? '...' : ''))) ?></p>
                                         </div>
                                     
                                            <div class="card-footer bg-white">
                                                <a href="subjects.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    View Subjects
                                                </a>
                                                <?php if ($role == 'teacher' || $role == 'admin'): ?>
                                                    <a href="edit_class.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <?php if ($role == 'teacher'): ?>
                                    You haven't created any classes yet. <a href="create_class.php">Create your first class</a>.
                                <?php else: ?>
                                    You aren't enrolled in any classes yet. Contact your instructor for access.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Announcements -->
                <?php if (!empty($announcements) || $role == 'admin'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white border-bottom-0">
                            <h3 class="mb-0"><i class="fas fa-bullhorn me-2"></i> Announcements</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($announcements)): ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="card announcement-card mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($announcement['title']) ?></h5>
                                            <p class="card-text"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                                            <small class="text-muted">Posted on <?= date('M j, Y', strtotime($announcement['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">No announcements yet.</div>
                            <?php endif; ?>
                            <?php if ($role == 'admin'): ?>
                                <a href="create_announcement.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> New Announcement
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Links -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h3 class="mb-0"><i class="fas fa-link me-2"></i> Quick Links</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="classes.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-book me-2"></i> My Classes
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                            <a href="calendar.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-calendar me-2"></i> Calendar
                            </a>
                            <?php if ($role == 'teacher' || $role == 'admin'): ?>
                                <a href="resources.php" class="btn btn-outline-primary text-start">
                                    <i class="fas fa-file-upload me-2"></i> My Resources
                                </a>
                            <?php endif; ?>
                            <?php if ($role == 'student'): ?>
                                <a href="assignments.php" class="btn btn-outline-primary text-start">
                                    <i class="fas fa-tasks me-2"></i> My Assignments
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header bg-white border-bottom-0">
                        <h3 class="mb-0"><i class="fas fa-history me-2"></i> Recent Activity</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="text-muted">Today</small>
                                </div>
                                <p class="mb-1">You logged in to EduPortal</p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="text-muted">Yesterday</small>
                                </div>
                                <p class="mb-1">Viewed Mathematics 101 class</p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="text-muted">2 days ago</small>
                                </div>
                                <p class="mb-1">Downloaded Algebra Basics PDF</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>