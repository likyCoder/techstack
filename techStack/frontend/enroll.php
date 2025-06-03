<?php
session_start();
require_once '../includes/db_connect.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verify CSRF token exists in session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Security token missing. Please refresh the page.'];
    header("Location: class_catalog.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    // Verify CSRF token matches
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid security token.'];
        header("Location: class_catalog.php");
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $class_id = (int)filter_var($_POST['class_id'], FILTER_SANITIZE_NUMBER_INT);

    // Validate class ID
    if ($class_id <= 0) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid class selection.'];
        header("Location: class_catalog.php");
        exit();
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // 1. Verify class exists
        $stmt = $conn->prepare("SELECT id FROM classes WHERE id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Class not found.'];
            $conn->rollback();
            header("Location: class_catalog.php");
            exit();
        }

        // 2. Check if already enrolled
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND class_id = ?");
        $stmt->bind_param("ii", $user_id, $class_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => 'You are already enrolled in this class.'];
            $conn->rollback();
            header("Location: classes.php");
            exit();
        }

        // 3. Create enrollment
        $stmt = $conn->prepare("INSERT INTO enrollments (user_id, class_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $class_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Enrollment creation failed: " . $stmt->error);
        }

        // 4. Initialize subject progress for all subjects in this class
        $subjects_stmt = $conn->prepare("SELECT id FROM subjects WHERE class_id = ?");
        $subjects_stmt->bind_param("i", $class_id);
        $subjects_stmt->execute();
        $subjects = $subjects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($subjects)) {
            $progress_stmt = $conn->prepare("
                INSERT INTO subject_progress (user_id, subject_id, status, progress) 
                VALUES (?, ?, 'not_started', 0)
            ");
            foreach ($subjects as $subject) {
                $progress_stmt->bind_param("ii", $user_id, $subject['id']);
                if (!$progress_stmt->execute()) {
                    throw new Exception("Progress initialization failed: " . $progress_stmt->error);
                }
            }
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Successfully enrolled in class!'];
        header("Location: my_classes.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Enrollment Error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to enroll: ' . $e->getMessage()];
        header("Location: class_catalog.php");
        exit();
    }
} else {
    header("Location: class_catalog.php");
    exit();
}
?>