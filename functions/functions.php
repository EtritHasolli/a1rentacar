<?php
// Include the database connection file
// include_once 'db.php';

function isLoggedIn($userid) {
    if($userid) {
        return true;
    } else {
        return false;
    }
}

function isAdmin($user_id) {
    // global $conn;
    
    // If role is already in session, use that
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'] == 1; // 1 = admin, 0 = regular user
    }
    
    // Otherwise, check the database
    // $stmt = $conn->prepare("SELECT role FROM Users WHERE id = ?");
    // $stmt->bind_param("i", $user_id);
    // $stmt->execute();
    // $stmt->bind_result($role);
    // $stmt->fetch();
    // $stmt->close();
    
    // Store the role in session for future use
    // $_SESSION['role'] = 1;
    
    // return $role == 1;
}
?>
