<?php
session_start();
include_once "db.php";

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    $conn->close();
    exit;
}

$sql = "SELECT client_id, full_name FROM clients ORDER BY full_name";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    $conn->close();
    exit;
}

$clients = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
}

echo json_encode($clients);
$conn->close();
?>