<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['class_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = (int)$_POST['class_id'];

try {
    // Verify enrollment exists
    $check_stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND class_id = ?");
    $check_stmt->bind_param("ii", $user_id, $class_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Remove subject progress records first
        $delete_progress = $conn->prepare("
            DELETE sp FROM subject_progress sp
            JOIN subjects s ON sp.subject_id = s.id
            WHERE sp.user_id = ? AND s.class_id = ?
        ");
        $delete_progress->bind_param("ii", $user_id, $class_id);
        $delete_progress->execute();
        
        // Remove enrollment
        $delete_enrollment = $conn->prepare("DELETE FROM enrollments WHERE user_id = ? AND class_id = ?");
        $delete_enrollment->bind_param("ii", $user_id, $class_id);
        $delete_enrollment->execute();
    }
    
    header("Location: classes.php");
    exit();
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Failed to unenroll from class. Please try again.";
    header("Location: classes.php");
    exit();
}
?>