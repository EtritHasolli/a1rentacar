<?php
session_start();
include_once "functions/db.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (isset($_SESSION['user_id']) && isset($data['carId']) && isset($data['startDate']) && isset($data['endDate'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM rentals WHERE car_id = ? AND start_date = ? AND end_date = ?");
        $stmt->bind_param("iss", $data['carId'], $data['startDate'], $data['endDate']);
        $stmt->execute();
        $response['success'] = true;
    } catch (Exception $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
} else {
    $response['message'] = "Missing required data";
}

echo json_encode($response);
?>