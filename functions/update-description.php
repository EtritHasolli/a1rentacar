<?php
header('Content-Type: application/json');
include_once "db.php";

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $carId = isset($data['carId']) ? intval($data['carId']) : 0;
    $description = isset($data['description']) ? trim($data['description']) : '';

    if ($carId <= 0 || empty($description)) {
        $response['message'] = 'Invalid car ID or description.';
        echo json_encode($response);
        exit;
    }

    // Update description in the database
    $sql = "UPDATE cars SET description = ? WHERE car_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $description, $carId);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Description updated successfully.';
    } else {
        $response['message'] = 'Failed to update description: ' . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>