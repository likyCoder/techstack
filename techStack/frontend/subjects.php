<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['class_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = (int)$_GET['class_id'];

try {
    // Fetch class info if user is enrolled
    $stmt = $conn->prepare("
        SELECT c.class_name, c.class_code
        FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        WHERE e.user_id = ? AND e.class_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $class_info = $stmt->get_result()->fetch_assoc();

    if (!$class_info) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'You are not enrolled in this class.'];
        header("Location: my_classes.php");
        exit();
    }

    // Get subjects and progress
    $subjects_stmt = $conn->prepare("
        SELECT 
            s.id,
            s.subject_name,
            s.subject_code,
            s.description,
            sp.status,
            sp.progress,
            sp.last_accessed,
            sp.completed_at
        FROM subjects s
        LEFT JOIN subject_progress sp ON s.id = sp.subject_id AND sp.user_id = ?
        WHERE s.class_id = ?
        ORDER BY s.subject_name
    ");
    $subjects_stmt->bind_param("ii", $user_id, $class_id);
    $subjects_stmt->execute();
    $subjects = $subjects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Something went wrong while loading the subjects.'];
    header("Location: my_classes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($class_info['class_name']) ?> | Subjects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .subject-card {
            transition: all 0.3s ease-in-out;
            border-left: 4px solid #0d6efd;
        }
        .subject-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .progress-thin {
            height: 8px;
        }
        .status-badge {
            text-transform: capitalize;
            font-size: 0.75rem;
            padding: 0.4em 0.6em;
        }
        .no-subjects {
            padding: 2rem;
            text-align: center;
            color: #888;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="classes.php">My Classes</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($class_info['class_name']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?= htmlspecialchars($class_info['class_name']) ?> Subjects</h2>
        <a href="classes.php" class="btn btn-outline-primary">Back to Classes</a>
    </div>

    <?php if (!empty($subjects)): ?>
        <div class="row g-4">
            <?php foreach ($subjects as $subject): 
                $status = $subject['status'] ?? 'not_started';
                $progress = (int)($subject['progress'] ?? 0);
                $badge_class = match ($status) {
                    'completed' => 'success',
                    'in_progress' => 'warning',
                    default => 'secondary'
                };
                $action_label = ($status === 'completed') ? 'Review' : 'Continue';
            ?>
                <div class="col-md-6">
                    <div class="card subject-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($subject['subject_name']) ?></h5>
                                    <p class="text-muted mb-2"><?= htmlspecialchars($subject['subject_code']) ?></p>
                                </div>
                                <span class="badge bg-<?= $badge_class ?> status-badge">
                                    <?= str_replace('_', ' ', $status) ?>
                                </span>
                            </div>
                            <p class="card-text"><?= nl2br(htmlspecialchars($subject['description'] ?? 'No description')) ?></p>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Progress</small>
                                    <small class="text-muted"><?= $progress ?>%</small>
                                </div>
                                <div class="progress progress-thin">
                                    <div class="progress-bar bg-primary" style="width: <?= $progress ?>%"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="subject.php?subject_id=<?= $subject['id'] ?>" class="btn btn-sm btn-primary">
                                    <?= $action_label ?>
                                </a>
                                <?php if ($subject['last_accessed']): ?>
                                    <small class="text-muted">Last accessed: <?= date('M j, Y', strtotime($subject['last_accessed'])) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-subjects">
            <h5>No subjects found for this class.</h5>
            <p class="text-muted">Please check back later or contact your instructor.</p>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
