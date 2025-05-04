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

    // Debug: Log received data and session token
    file_put_contents('debug.log', "Received data: " . print_r($data, true) . "\nSession CSRF: " . ($_SESSION['csrf_token'] ?? 'unset') . "\n", FILE_APPEND);

    // CSRF token validation
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Token i pavlefshëm CSRF';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // Validate required fields
    $requiredFields = ['carId', 'startDate', 'endDate', 'dailyRate', 'totalAmount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Fusha e kërkuar mungon: $field");
        }
    }

    // Validate either clientId or clientName is provided
    if (empty($data['clientId']) && empty($data['clientName'])) {
        throw new Exception('Kërkohet ose ID e klientit ose emri i klientit');
    }

    // Validate car exists
    $carCheckSql = "SELECT car_id FROM cars WHERE car_id = ?";
    $stmt = $conn->prepare($carCheckSql);
    $stmt->bind_param("i", $data['carId']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('ID e makinës është e pavlefshme');
    }
    $stmt->close();

    // Handle client
    $clientId = null;
    if (!empty($data['clientId'])) {
        // Validate existing client
        $clientCheckSql = "SELECT client_id FROM clients WHERE client_id = ?";
        $stmt = $conn->prepare($clientCheckSql);
        $stmt->bind_param("i", $data['clientId']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('ID e klientit është e pavlefshme');
        }
        $clientId = $data['clientId'];
        $stmt->close();
    } elseif (!empty($data['clientName'])) {
        // Check if client with same name exists
        $clientName = trim($data['clientName']);
        $phone = trim($data['phone'] ?? '');
        
        if (strlen($clientName) < 2) {
            throw new Exception('Emri i klientit duhet të ketë të paktën 2 karaktere');
        }
        if (!preg_match('/^\+?[0-9\s-]{7,20}$/', $phone)) {
            throw new Exception('Numri i telefonit është i pavlefshëm');
        }

        // Check for existing client by name
        $clientCheckSql = "SELECT client_id, phone FROM clients WHERE full_name = ?";
        $stmt = $conn->prepare($clientCheckSql);
        $stmt->bind_param("s", $clientName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $client = $result->fetch_assoc();
            if ($client['phone'] === $phone) {
                $clientId = $client['client_id'];
            } else {
                // Check if user has made a choice
                if (isset($data['clientChoice'])) {
                    if ($data['clientChoice'] === 'update') {
                        // Update existing client's phone number
                        $updateClientSql = "UPDATE clients SET phone = ? WHERE client_id = ?";
                        $stmt = $conn->prepare($updateClientSql);
                        $stmt->bind_param("si", $phone, $client['client_id']);
                        if (!$stmt->execute()) {
                            throw new Exception('Dështoi përditësimi i numrit të telefonit të klientit');
                        }
                        $clientId = $client['client_id'];
                    } elseif ($data['clientChoice'] === 'new') {
                        // Insert new client
                        $insertClientSql = "INSERT INTO clients (full_name, phone) VALUES (?, ?)";
                        $stmt = $conn->prepare($insertClientSql);
                        $stmt->bind_param("ss", $clientName, $phone);
                        if (!$stmt->execute()) {
                            throw new Exception('Dështoi krijimi i klientit të ri');
                        }
                        $clientId = $stmt->insert_id;
                    } else {
                        throw new Exception('Zgjedhje e pavlefshme për klientin');
                    }
                } else {
                    // Return warning to front-end
                    $response['success'] = false;
                    $response['warning'] = true;
                    $response['message'] = "Një klient me emrin '$clientName' ekziston me numër telefoni të ndryshëm ({$client['phone']}).";
                    $response['existingClientId'] = $client['client_id'];
                    $response['existingPhone'] = $client['phone'];
                    http_response_code(200);
                    echo json_encode($response);
                    $stmt->close();
                    $conn->close();
                    exit;
                }
            }
        } else {
            // Insert new client
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
    $today = new DateTime(date('Y-m-d')); // Current date without time

    // Check if start date is before today
    if ($startDate < $today) {
        throw new Exception('Data e fillimit nuk mund të jetë në të shkuarën');
    }

    if ($startDate > $endDate) {
        throw new Exception('Data e mbarimit duhet të jetë pas datës së fillimit');
    }

    // Calculate days (exclude start and end date adjustment)
    $interval = $startDate->diff($endDate);
    $days = $interval->days; // Do not add +1

    // Validate total amount and daily rate
    if ($data['totalAmount'] <= 0) {
        throw new Exception('Shuma totale duhet të jetë më e madhe se 0');
    }
    if ($data['dailyRate'] <= 0) {
        throw new Exception('Çmimi ditor duhet të jetë më i madh se 0');
    }

    // Check for overlapping dates
    $overlapSql = "SELECT rental_id FROM rentals WHERE car_id = ? AND 
                  ((start_date < ? AND end_date > ?) OR 
                   (start_date < ? AND end_date > ?) OR
                   (start_date > ? AND end_date < ?))";
    $stmt = $conn->prepare($overlapSql);
    $stmt->bind_param("issssss", 
        $data['carId'], 
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
    
    $placeContacted = $data['placeContacted'] ?? null;

    // Insert new rental
    $insertSql = "INSERT INTO rentals 
                  (car_id, client_id, place_contacted, start_date, end_date, daily_rate, total_amount, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'Reserved')";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("iisssdd", 
        $data['carId'], 
        $clientId,
        $placeContacted,
        $data['startDate'], 
        $data['endDate'], 
        $data['dailyRate'], 
        $data['totalAmount']
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['rentalId'] = $stmt->insert_id;
        $response['totalAmount'] = $data['totalAmount'];
        $response['clientId'] = $clientId;
    } else {
        throw new Exception('Dështoi krijimi i rezervimit');
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
$conn->close();
?>