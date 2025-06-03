<?php
session_start();
include 'includes\db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch admin details from the database
    $sql = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        // Verify password
        if ($password === $admin['password']) { // Replace with password_verify if passwords are hashed
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: frontend/home.php');
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No admin found with that username.";
    }
    $stmt->close();
    $conn->close();
}
?>