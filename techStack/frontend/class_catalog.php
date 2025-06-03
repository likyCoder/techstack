<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get all available classes
$classes = [];
try {
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM enrollments WHERE class_id = c.id AND user_id = ?) as is_enrolled
        FROM classes c
        ORDER BY c.class_name
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to load classes'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .already-enrolled {
            opacity: 0.8;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container py-5">
        <!-- Display messages -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message']['type'] ?>">
                <?= $_SESSION['message']['text'] ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <h2 class="mb-4">Available Classes</h2>
        
        <div class="row">
            <?php foreach ($classes as $class): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 <?= $class['is_enrolled'] ? 'already-enrolled' : '' ?>">
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
                                <form action="enroll.php" method="POST" class="enroll-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                    <button type="submit" name="enroll" class="btn btn-primary w-100">
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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