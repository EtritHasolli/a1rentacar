<?php
include_once "header.php";
include_once "functions/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch all rentals for the user
$rentals = [];
$sql = $rentalSql = "SELECT r.rental_id, r.start_date, r.end_date, r.daily_rate, r.total_amount, r.place_contacted,
               c.client_id, c.full_name AS client_name, c.phone,
               cars.make, cars.model, cars.transmission, r.notes
                FROM rentals r
                JOIN clients c ON r.client_id = c.client_id
                JOIN cars ON r.car_id = cars.car_id
                WHERE r.end_date < CURRENT_DATE";
$stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($rental = $result->fetch_assoc()) {
    $rentals[] = [
        'rental_id' => $rental['rental_id'],
        'client_id' => $rental['client_id'],
        'client_name' => $rental['client_name'],
        'phone' => $rental['phone'],
        'place_contacted' => $rental['place_contacted'],
        'start_date' => $rental['start_date'],
        'end_date' => $rental['end_date'],
        'daily_rate' => $rental['daily_rate'],
        'total_amount' => $rental['total_amount'],
        'car_make' => $rental['make'],
        'car_model' => $rental['model'],
        'car_transmission' => $rental['transmission'],
        'notes' => $rental['notes']
    ];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profili Im</title>
    <!-- Include Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .profile-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            padding: 2rem 1rem;
            box-sizing: border-box;
            min-height: 100vh;
            background: #f6f8fa;
            flex: none;
            margin-bottom: 3rem;
        }
        .card {
            display: flex;
            flex-direction: column;
            background: var(--white);
            padding: 2.5rem 2rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1200px;
            height: 100%;
            /* margin: 2rem 0; */
            /* gap: 2.5rem; */
        }
        .card h1 {
            margin: 0 0 2rem 0;
            font-size: 2.1rem;
            color: var(--primary);
            text-align: left;
            font-weight: 700;
            letter-spacing: 1px;
        }
        #rentalListContainer {
            margin-top: 0.625rem;
            border-radius: 0.625rem;
        }
        #rentalList {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        #rentalList li {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
            color: var(--text-dark);
            background: #fff;
            border: var(--border);
            border-radius: 0.5rem;
            padding: 1.2rem 1rem;
            box-shadow: 0 2px 8px rgba(26,26,46,0.04);
        }
        .rentalData {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .rentalData strong {
            color: var(--primary);
        }
        .dataInRowForm {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .pricePart {
            display: flex;
            flex-direction: row;
            gap: 1.2rem;
            font-size: 1.08rem;
            color: var(--primary-dark);
        }
        .pricePart strong {
            color: var(--primary);
        }
        /* Responsive adjustments */
        @media (max-width: 900px) {
            .card {
                padding: 1.5rem 0.5rem;
            }
            #rentalList li {
                flex-direction: column;
                gap: 0.7rem;
                padding: 1rem 0.7rem;
            }
        }
        @media (max-width: 600px) {
            .profile-container {
                padding: 0.5rem;
            }
            .card {
                padding: 0.7rem 0.2rem;
                margin: 1rem 0;
            }
            .card h1 {
                font-size: 1.2rem;
            }
            #rentalListContainer {
                padding: 0.7rem 0.3rem;
            }
            #rentalList li {
                font-size: 0.97rem;
                padding: 0.7rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <div class="card">
            <h1>All Rentals</h1>
            <div id="rentalListContainer">
                <ul id="rentalList"></ul>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>

    <!-- Include Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Pass PHP data to JavaScript
        const rentals = <?php echo json_encode($rentals); ?>;

        // Format date from yyyy-mm-dd to dd/mm/yyyy
        function formatDateToDMY(dateStr) {
            if (!dateStr) return '';
            const [year, month, day] = dateStr.split('-');
            return `${day}/${month}/${year}`;
        }

        // Render rental list
        function renderRentalList(ranges) {
            const list = document.getElementById("rentalList");
            list.innerHTML = "";
            if (ranges.length === 0) {
                const li = document.createElement("li");
                li.innerHTML = `<span>There are no registered rentals.</span>`;
                list.appendChild(li);
                return;
            }
            ranges.forEach(range => {
                const li = document.createElement("li");
                li.innerHTML = `
                    <div class="rentalData">
                        <span><strong>${range.car_make} ${range.car_model} ${range.car_transmission}</strong></span>
                        <span><strong>${range.client_name || 'Unknown'}</strong> (${range.phone || 'N/A'}, ${range.place_contacted || 'N/A'})</span>
                    </div>
                    <div class="rentalData">
                        <div class="dataInRowForm">
                            <input type="text" class="flatpickr-display" value="${formatDateToDMY(range.start_date)}" disabled>
                            →
                            <input type="text" class="flatpickr-display" value="${formatDateToDMY(range.end_date)}" disabled>
                        </div>
                        <div class="dataInRowForm">
                            <div class="pricePart">
                                <span>${parseFloat(range.daily_rate).toFixed(2)}€/day</span>
                                <span><strong>Total: ${parseFloat(range.total_amount).toFixed(2)}€</strong></span>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($range['notes'])): ?>
                        <div class="rentalNotes"><strong>Client Requests:</strong> <?php echo htmlspecialchars($range['notes']); ?></div>
                    <?php endif; ?>
                `;
                list.appendChild(li);
            });
            // Initialize Flatpickr for display-only inputs
            flatpickr(".flatpickr-display", {
                dateFormat: "d/m/Y",
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: false,
                disableMobile: true
            });
        }

        // Initialize UI
        renderRentalList(rentals);
    </script>
</body>
</html>