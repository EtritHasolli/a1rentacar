<?php
include_once "functions/db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "You must be logged in.";
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email      = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Invalid email format.";
        exit();
    }

    // Fetch current data if fields be empty
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($cur_fn, $cur_em);
    $stmt->fetch();
    $stmt->close();

    $name = !empty($name) ? $name : $cur_fn;
    $email      = !empty($email)      ? $email      : $cur_em;

    // Update yon database
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $user_id);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        http_response_code(500);
        echo "Database update failed.";
    }

    $stmt->close();
    $conn->close();
}
?>
