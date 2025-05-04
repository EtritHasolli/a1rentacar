<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection (uncomment and configure your db.php)
include_once "db.php";

if (isset($_POST['submit'])) {
    // Get the submitted form data
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if email and password fields are empty
    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=emptyfields");
        exit();
    }

    try {
        // Prepare SQL query
        $stmt = $conn->prepare("SELECT user_id, password_hash, full_name, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password (using password_verify for hashed passwords)
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, start the session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = 1; // Assuming 1 is admin role

                header("Location: ../index.php");
                exit();
            } else {
                // Incorrect password
                header("Location: ../login.php?error=wrongpassword");
                exit();
            }
        } else {
            // No user with this email
            header("Location: ../login.php?error=nouser");
            exit();
        }
    } catch (Exception $e) {
        // Database error
        header("Location: ../login.php?error=dberror");
        exit();
    }
} else {
    // Form wasn't submitted
    header("Location: ../login.php");
    exit();
}