<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['lesson_id'])) {
    header("Location: classes.php");
    exit();
}

$lesson_id = $_GET['lesson_id'];
$user_id = $_SESSION['user_id'];

// Get lesson details
$lesson = $conn->query("
    SELECT l.*, s.subject_name, s.id as subject_id, c.class_name
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE l.id = $lesson_id
")->fetch_assoc();

if (!$lesson) {
    header("Location: classes.php");
    exit();
}

// Mark as viewed/completed for student
if ($_SESSION['role'] == 'student') {
    $conn->query("
        INSERT INTO lesson_progress (user_id, lesson_id, status, last_viewed)
        VALUES ($user_id, $lesson_id, 'viewed', NOW())
        ON DUPLICATE KEY UPDATE last_viewed = NOW()
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $lesson['lesson_title'] ?> - EduPortal</title>
    <!-- Include your standard header content -->
    <style>
        .lesson-content {
            line-height: 1.8;
        }
        .lesson-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }
        .resource-card {
            border-left: 3px solid var(--primary-color);
        }
        .quiz-card {
            border-left: 3px solid var(--warning);
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
                <li class="breadcrumb-item"><a href="subjects.php?class_id=<?= $lesson['class_id'] ?>"><?= $lesson['class_name'] ?></a></li>
                <li class="breadcrumb-item"><a href="lessons.php?subject_id=<?= $lesson['subject_id'] ?>"><?= $lesson['subject_name'] ?></a></li>
                <li class="breadcrumb-item active"><?= $lesson['lesson_title'] ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h2 class="mb-0"><?= $lesson['lesson_title'] ?></h2>
                        <?php if ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin'): ?>
                            <div class="mt-2">
                                <a href="edit_lesson.php?lesson_id=<?= $lesson_id ?>" class="btn btn-sm btn-outline-secondary me-2">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body lesson-content">
                        <?= $lesson['content'] ?>
                        
                        <?php if ($lesson['video_url']): ?>
                            <div class="ratio ratio-16x9 my-4">
                                <iframe src="<?= $lesson['video_url'] ?>" allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <?php if ($_SESSION['role'] == 'student'): ?>
                            <button class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i>Mark as Completed
                            </button>
                            <div>
                                <small class="text-muted me-3">Last viewed: <?= date('M j, Y') ?></small>
                                <a href="#" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-question-circle me-1"></i>Get Help
                                </a>
                            </div>
                        <?php else: ?>
                            <small class="text-muted">Last updated: <?= date('M j, Y', strtotime($lesson['updated_at'])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($lesson['quiz_id']): ?>
                    <div class="card quiz-card mb-4">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><i class="fas fa-question-circle text-warning me-2"></i>Lesson Quiz</h4>
                        </div>
                        <div class="card-body">
                            <p>Test your knowledge with this lesson's quiz:</p>
                            <a href="quiz.php?quiz_id=<?= $lesson['quiz_id'] ?>" class="btn btn-warning">
                                <i class="fas fa-play me-2"></i>Start Quiz
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card resource-card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-paperclip me-2"></i>Lesson Resources</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($lesson['document_path']): ?>
                            <div class="d-grid gap-2">
                                <a href="<?= $lesson['document_path'] ?>" class="btn btn-outline-primary text-start" download>
                                    <i class="fas fa-file-pdf me-2 text-danger"></i>Download Lesson Notes
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No additional resources for this lesson</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Lesson Details</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Subject</span>
                                <span class="fw-bold"><?= $lesson['subject_name'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Class</span>
                                <span class="fw-bold"><?= $lesson['class_name'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Duration</span>
                                <span class="badge bg-primary rounded-pill"><?= $lesson['duration'] ?> mins</span>
                            </li>
                            <?php if ($_SESSION['role'] == 'student'): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Your Status</span>
                                    <span class="badge bg-success rounded-pill">In Progress</span>
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