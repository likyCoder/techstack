<?php
session_start();
require_once 'includes/db_connect.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get and validate settings
$notifications = $_POST['notifications'] ?? 'all';
$dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
$email_updates = isset($_POST['email_updates']) ? 1 : 0;

// Validate notification preference
if (!in_array($notifications, ['all', 'important', 'none'])) {
    $notifications = 'all';
}

try {
    // Check if settings exist for this user
    $check_stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Update existing settings
        $stmt = $conn->prepare("
            UPDATE user_settings 
            SET notifications = ?, dark_mode = ?, email_updates = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->bind_param("siii", $notifications, $dark_mode, $email_updates, $user_id);
    } else {
        // Insert new settings
        $stmt = $conn->prepare("
            INSERT INTO user_settings 
            (user_id, notifications, dark_mode, email_updates, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param("isii", $user_id, $notifications, $dark_mode, $email_updates);
    }
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Settings saved successfully';
        
        // Update session with dark mode preference
        $_SESSION['dark_mode'] = $dark_mode;
    } else {
        $response['message'] = 'Failed to save settings';
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>