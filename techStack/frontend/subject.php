<?php
session_start();
require_once '../includes/db_connect.php';

// Generate or validate CSRF token functions
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Require user login and subject_id parameter
if (!isset($_SESSION['user_id']) || !isset($_GET['subject_id'])) {
    redirect("../index.php");
}

$user_id = $_SESSION['user_id'];
$subject_id = (int)$_GET['subject_id'];
$subject = [];
$progress = [];

try {
    // Fetch subject and verify user enrollment
    $stmt = $conn->prepare("
        SELECT s.*, c.class_name, c.id AS class_id
        FROM subjects s
        INNER JOIN classes c ON s.class_id = c.id
        INNER JOIN enrollments e ON c.id = e.class_id
        WHERE e.user_id = ? AND s.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $subject_id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();

    if (!$subject) {
        // No access or subject doesn't exist
        redirect("classes.php");
    }

    // Update last accessed timestamp in subject_progress (create if missing)
    $stmt = $conn->prepare("
        INSERT INTO subject_progress (user_id, subject_id, last_accessed)
        VALUES (?, ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE last_accessed = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("ii", $user_id, $subject_id);
    $stmt->execute();

    // Fetch current progress info
    $progress_stmt = $conn->prepare("
        SELECT progress, status, last_accessed, completed_at
        FROM subject_progress
        WHERE user_id = ? AND subject_id = ?
        LIMIT 1
    ");
    $progress_stmt->bind_param("ii", $user_id, $subject_id);
    $progress_stmt->execute();
    $progress = $progress_stmt->get_result()->fetch_assoc();

} catch (Exception $e) {
    error_log("Subject page error: " . $e->getMessage());
    // Show user-friendly error and stop
    die("An error occurred. Please try again later.");
}

// Handle POST progress update securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['progress'], $_POST['csrf_token'])) {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'])) {
        die("Invalid request. Please try again.");
    }

    $progress_value = (int)$_POST['progress'];
    if ($progress_value < 0 || $progress_value > 100) {
        die("Invalid progress value.");
    }
    $status = ($progress_value >= 100) ? 'completed' : 'in_progress';

    try {
        if ($progress_value >= 100) {
            $update_query = "
                UPDATE subject_progress
                SET progress = ?, status = ?, completed_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND subject_id = ?
            ";
        } else {
            $update_query = "
                UPDATE subject_progress
                SET progress = ?, status = ?, completed_at = NULL
                WHERE user_id = ? AND subject_id = ?
            ";
        }

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("isii", $progress_value, $status, $user_id, $subject_id);
        $update_stmt->execute();

        // Redirect to avoid resubmission
        redirect("subject.php?subject_id=$subject_id");

    } catch (Exception $e) {
        error_log("Progress update error: " . $e->getMessage());
        die("Could not update progress. Please try again later.");
    }
}

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($subject['subject_name'] ?? 'Subject') ?> - EduPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<style>
    body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
  margin: 0;
  padding: 30px;
  color: #222;
}

    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
    }
    .btn {
        min-width: 140px;
    }
</style>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="classes.php">My Classes</a></li>
                <li class="breadcrumb-item"><a href="subjects.php?class_id=<?= (int)$subject['class_id'] ?>"><?= htmlspecialchars($subject['class_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($subject['subject_name']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title"><?= htmlspecialchars($subject['subject_name']) ?></h2>
                        <h6 class="card-subtitle mb-3 text-muted"><?= htmlspecialchars($subject['subject_code']) ?></h6>

                        <div class="mb-4">
                            <p><?= htmlspecialchars($subject['description'] ?? 'No description available.') ?></p>
                        </div>

                      <div class="card mb-4 shadow-sm">
                            <div class="card-body">
                                <h4 class="card-title text-primary mb-3"><?php echo htmlspecialchars($subject['subject_name']); ?></h4>
                        
                                <?php if (!empty($subject['description'])): ?>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($subject['description'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">No subject description provided.</p>
                                <?php endif; ?>
                        
                                <div class="mt-4 d-flex flex-wrap gap-2">
                                    <a href="lectures.php?subject_id=<?php echo urlencode($subject['id']); ?>" class="btn btn-outline-primary">
                                        📚 Learn now 
                                    </a>
                                    <a href="library.php?subject_id=<?php echo urlencode($subject['id']); ?>" class="btn btn-success">
                                        📝 Library
                                    </a>
                                </div>
                            </div>
                        </div>
                        

                        <!-- Progress update form -->
                        <form method="POST" class="mt-4" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
                            <div class="mb-3">
                                <label class="form-label" for="progress-select">Update Your Progress</label>
                                <select id="progress-select" name="progress" class="form-select" required>
                                    <?php
                                    $progress_options = [
                                        0 => '0% - Not started',
                                        25 => '25% - Started',
                                        50 => '50% - Halfway',
                                        75 => '75% - Mostly done',
                                        100 => '100% - Completed'
                                    ];
                                    $current_progress = $progress['progress'] ?? 0;
                                    foreach ($progress_options as $value => $label) {
                                        $selected = ($current_progress == $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Progress</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Completion</span>
                                <span><?= htmlspecialchars($progress['progress'] ?? 0) ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?= htmlspecialchars($progress['progress'] ?? 0) ?>%;" aria-valuenow="<?= htmlspecialchars($progress['progress'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <?php if (!empty($progress) && is_array($progress)): ?>
                        <div class="mb-3">
                            <p><strong>Status:</strong>
                                <span class="badge bg-<?=
                                    ($progress['status'] === 'completed') ? 'success' :
                                    (($progress['status'] === 'in_progress') ? 'warning' : 'secondary')
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', htmlspecialchars($progress['status'] ?? 'not started'))) ?>
                                </span>
                            </p>

                            <?php if (!empty($progress['last_accessed'])): ?>
                                <p><strong>Last accessed:</strong> <?= date('M j, Y g:i a', strtotime($progress['last_accessed'])) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($progress['completed_at'])): ?>
                                <p><strong>Completed on:</strong> <?= date('M j, Y', strtotime($progress['completed_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <p><strong>Status:</strong> Not started</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
