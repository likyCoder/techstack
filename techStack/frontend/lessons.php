<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['subject_id'])) {
    header("Location: classes.php");
    exit();
}

$subject_id = $_GET['subject_id'];
$user_id = $_SESSION['user_id'];

// Get subject and verify access
$subject = $conn->query("
    SELECT s.*, c.class_name, c.id as class_id 
    FROM subjects s
    JOIN classes c ON s.class_id = c.id
    WHERE s.id = $subject_id
")->fetch_assoc();

if (!$subject) {
    header("Location: classes.php");
    exit();
}

// Get lessons for this subject
$lessons = $conn->query("
    SELECT * FROM lessons 
    WHERE subject_id = $subject_id
    ORDER BY lesson_order
");

// Get resources
$resources = $conn->query("
    SELECT * FROM resources
    WHERE subject_id = $subject_id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $subject['subject_name'] ?> Lessons - EduPortal</title>
    <!-- Include your standard header content -->
    <style>
        .lesson-item {
            border-left: 3px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        .lesson-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        .lesson-completed {
            border-left-color: var(--success-color);
        }
        .resource-badge {
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                <li class="breadcrumb-item"><a href="classes.php">Classes</a></li>
                <li class="breadcrumb-item"><a href="subjects.php?class_id=<?= $subject['class_id'] ?>"><?= $subject['class_name'] ?></a></li>
                <li class="breadcrumb-item active"><?= $subject['subject_name'] ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><?= $subject['subject_name'] ?> Lessons</h3>
                            <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin'): ?>
                                <a href="create_lesson.php?subject_id=<?= $subject_id ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Lesson
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($lessons->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($lesson = $lessons->fetch_assoc()): ?>
                                    <div class="list-group-item lesson-item <?= rand(0,1) ? 'lesson-completed' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="me-3">
                                                <h5 class="mb-1">
                                                    <?php if ($_SESSION['role'] == 'student'): ?>
                                                        <i class="fas fa-circle-check text-success me-2"></i>
                                                    <?php endif; ?>
                                                    <?= $lesson['lesson_title'] ?>
                                                </h5>
                                                <p class="mb-1 text-muted"><?= substr($lesson['description'], 0, 120) ?>...</p>
                                                <div class="mt-2">
                                                    <?php if ($lesson['video_url']): ?>
                                                        <span class="resource-badge bg-danger bg-opacity-10 text-danger me-2">
                                                            <i class="fas fa-video me-1"></i> Video
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($lesson['document_path']): ?>
                                                        <span class="resource-badge bg-primary bg-opacity-10 text-primary me-2">
                                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($lesson['quiz_id']): ?>
                                                        <span class="resource-badge bg-warning bg-opacity-10 text-warning">
                                                            <i class="fas fa-question-circle me-1"></i> Quiz
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="lesson_view.php?lesson_id=<?= $lesson['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <?= ($_SESSION['role'] == 'student') ? 'Continue' : 'View' ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="card-body text-center py-5">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No lessons available yet</h4>
                                <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin'): ?>
                                    <a href="create_lesson.php?subject_id=<?= $subject_id ?>" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-2"></i>Create First Lesson
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted">Check back later for lesson materials</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Subject Resources</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($resources->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($resource = $resources->fetch_assoc()): ?>
                                    <a href="<?= $resource['file_path'] ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-center">
                                            <?php if (strpos($resource['file_type'], 'pdf') !== false): ?>
                                                <i class="fas fa-file-pdf text-danger me-3 fa-lg"></i>
                                            <?php elseif (strpos($resource['file_type'], 'word') !== false): ?>
                                                <i class="fas fa-file-word text-primary me-3 fa-lg"></i>
                                            <?php else: ?>
                                                <i class="fas fa-file-alt text-secondary me-3 fa-lg"></i>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1"><?= $resource['title'] ?></h6>
                                                <small class="text-muted"><?= strtoupper($resource['file_type']) ?> • <?= round($resource['file_size']/1024) ?> KB</small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No additional resources</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin'): ?>
                        <div class="card-footer bg-white">
                            <a href="upload_resource.php?subject_id=<?= $subject_id ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload Resource
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Subject Information</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Class</span>
                                <span class="fw-bold"><?= $subject['class_name'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Subject Code</span>
                                <span class="fw-bold"><?= $subject['subject_code'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Total Lessons</span>
                                <span class="badge bg-primary rounded-pill"><?= $lessons->num_rows ?></span>
                            </li>
                            <?php if ($_SESSION['role'] == 'student'): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Your Progress</span>
                                    <span class="fw-bold text-primary"><?= rand(0, 100) ?>%</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>