update_profile.php<?php
session_start();
require_once 'includes/db_connect.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate and sanitize input
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($first_name)) {
    $response['errors']['first_name'] = 'First name is required';
}
if (empty($last_name)) {
    $response['errors']['last_name'] = 'Last name is required';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = 'Valid email is required';
}

// Check if email exists (excluding current user)
$email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$email_check->bind_param("si", $email, $user_id);
$email_check->execute();
if ($email_check->get_result()->num_rows > 0) {
    $response['errors']['email'] = 'Email is already in use';
}

// If changing password, validate password fields
if (!empty($new_password)) {
    if (empty($current_password)) {
        $response['errors']['current_password'] = 'Current password is required for changes';
    }
    if (strlen($new_password) < 8) {
        $response['errors']['new_password'] = 'Password must be at least 8 characters';
    }
    if ($new_password !== $confirm_password) {
        $response['errors']['confirm_password'] = 'Passwords do not match';
    }
}

// If there are validation errors, return them
if (!empty($response['errors'])) {
    $response['message'] = 'Please correct the errors below';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

try {
    // Verify current password if changing password
    if (!empty($new_password)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $response['errors']['current_password'] = 'Current password is incorrect';
            $response['message'] = 'Current password is incorrect';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Build the update query
    $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?";
    $params = [$first_name, $last_name, $email];
    $types = "sss";
    
    if (!empty($new_password)) {
        $query .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }
    
    $query .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully';
        
        // Update session variables if needed
        $_SESSION['username'] = $email; // Assuming username is email
    } else {
        $response['message'] = 'Failed to update profile';
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>