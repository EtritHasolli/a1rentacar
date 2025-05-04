<?php
session_start();
include_once "db.php";

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Initialize response
$response = ['success' => false, 'message' => ''];

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Ju lutem identifikohuni për të vazhduar';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

try {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Debug: Log received data
    file_put_contents('debug.log', "Update-rental received data: " . print_r($data, true) . "\n", FILE_APPEND);

    // CSRF token validation
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Token i pavlefshëm CSRF';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // Handle action
    $action = isset($data['action']) ? $data['action'] : '';

    if ($action === 'delete') {
        // Handle deletion
        if (!isset($data['rentalId']) || !is_numeric($data['rentalId'])) {
            throw new Exception('ID e rezervimit është e pavlefshme');
        }

        $rentalId = intval($data['rentalId']);
        $deleteSql = "DELETE FROM rentals WHERE rental_id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("i", $rentalId);
        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            throw new Exception('Dështoi fshirja e rezervimit');
        }
        $stmt->close();
    } elseif ($action === 'update') {
        // Handle update
        $requiredFields = ['carId', 'startDate', 'endDate', 'totalAmount', 'dailyRate', 'rentalId', 'clientName', 'phone', 'placeContacted'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Fusha e kërkuar mungon: $field");
            }
        }

        // Validate rental exists
        $rentalCheckSql = "SELECT rental_id, client_id FROM rentals WHERE rental_id = ?";
        $stmt = $conn->prepare($rentalCheckSql);
        $stmt->bind_param("i", $data['rentalId']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Rezervimi nuk ekziston');
        }
        $rental = $result->fetch_assoc();
        $clientId = $rental['client_id'];
        $stmt->close();

        // Validate car exists
        $carCheckSql = "SELECT car_id FROM cars WHERE car_id = ?";
        $stmt = $conn->prepare($carCheckSql);
        $stmt->bind_param("i", $data['carId']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('ID e makinës është e pavlefshme');
        }
        $stmt->close();

        // Handle client update or creation
        $clientName = trim($data['clientName']);
        $phone = trim($data['phone']);
        $placeContacted = $data['placeContacted'];

        if (strlen($clientName) < 2) {
            throw new Exception('Emri i klientit duhet të ketë të paktën 2 karaktere');
        }
        if (!preg_match('/^\+?[0-9\s-]{7,20}$/', $phone)) {
            throw new Exception('Numri i telefonit është i pavlefshëm');
        }
        if (!in_array($placeContacted, ['WhatsApp', 'Viber', 'Phone'])) {
            throw new Exception('Mënyra e kontaktit është e pavlefshme');
        }

        if (isset($data['clientId']) && $data['clientId'] == $clientId) {
            // Update existing client
            $updateClientSql = "UPDATE clients SET full_name = ?, phone = ? WHERE client_id = ?";
            $stmt = $conn->prepare($updateClientSql);
            $stmt->bind_param("ssi", $clientName, $phone, $clientId);
            if (!$stmt->execute()) {
                throw new Exception('Dështoi përditësimi i klientit');
            }
            $stmt->close();
        } else {
            // Check if client with same name exists
            $clientCheckSql = "SELECT client_id, phone FROM clients WHERE full_name = ?";
            $stmt = $conn->prepare($clientCheckSql);
            $stmt->bind_param("s", $clientName);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // Client exists, verify phone number
                $client = $result->fetch_assoc();
                if ($client['phone'] === $phone) {
                    $clientId = $client['client_id'];
                } else {
                    throw new Exception('Një klient me këtë emër ekziston, por numri i telefonit nuk përputhet');
                }
            } else {
                // Create new client
                $insertClientSql = "INSERT INTO clients (full_name, phone) VALUES (?, ?)";
                $stmt = $conn->prepare($insertClientSql);
                $stmt->bind_param("ss", $clientName, $phone);
                if (!$stmt->execute()) {
                    throw new Exception('Dështoi krijimi i klientit të ri');
                }
                $clientId = $stmt->insert_id;
            }
            $stmt->close();
        }

        // Validate dates
        $startDate = new DateTime($data['startDate']);
        $endDate = new DateTime($data['endDate']);
        if ($startDate > $endDate) {
            throw new Exception('Data e mbarimit duhet të jetë pas datës së fillimit');
        }

        // Calculate days (exclude start and end date adjustment)
        $interval = $startDate->diff($endDate);
        $days = $interval->days;

        // Validate total amount
        if ($data['totalAmount'] <= 0) {
            throw new Exception('Shuma totale duhet të jetë më e madhe se 0');
        }

        // Calculate daily rate
        $calculatedDailyRate = $data['totalAmount'] / $days;

        // Update dailyRate to match calculated value
        $data['dailyRate'] = $calculatedDailyRate;

        // Check for overlapping dates (excluding current rental)
        $overlapSql = "SELECT rental_id FROM rentals WHERE car_id = ? AND rental_id != ? AND
                      ((start_date < ? AND end_date > ?) OR 
                       (start_date < ? AND end_date > ?) OR
                       (start_date > ? AND end_date < ?))";
        $stmt = $conn->prepare($overlapSql);
        $stmt->bind_param("iissssss", 
            $data['carId'], 
            $data['rentalId'],
            $data['endDate'], $data['startDate'],
            $data['startDate'], $data['endDate'],
            $data['startDate'], $data['endDate']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Datat e zgjedhura përkojnë me një rezervim ekzistues');
        }
        $stmt->close();

        // Update rental
        $updateSql = "UPDATE rentals 
                      SET car_id = ?, client_id = ?, place_contacted = ?, start_date = ?, end_date = ?, daily_rate = ?, total_amount = ?, status = 'Reserved'
                      WHERE rental_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("iisssddi", 
            $data['carId'], 
            $clientId,
            $placeContacted,
            $data['startDate'], 
            $data['endDate'], 
            $data['dailyRate'], 
            $data['totalAmount'],
            $data['rentalId']
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['rentalId'] = $data['rentalId'];
            $response['clientId'] = $clientId;
        } else {
            throw new Exception('Dështoi përditësimi i rezervimit');
        }
        $stmt->close();
    } else {
        throw new Exception('Veprim i pavlefshëm');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
$conn->close();
?>