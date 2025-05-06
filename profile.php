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
               cars.make, cars.model, cars.transmission
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
        'car_transmission' => $rental['transmission']
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
    <link href="css/car-details.css" rel="stylesheet"/>
    <!-- Include Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .card h1{
            margin:  auto;
        }

        .profile-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            padding: 1rem;
            box-sizing: border-box;
            min-height: auto;
        }

        .rentalData {
            display: flex;
            flex-direction: column;
        }

        #rentalListContainer {
            margin-top: 0.625rem;
            background-color: var(--background-color);
            padding: 0.9375rem;
            border-radius: 0.625rem;
            border: var(--border);
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
            align-items: center;
            gap: 0.3125rem;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .pricePart {
            display: flex;
            flex-direction: row;
            justify-content: space-evenly;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                padding: 0.9375rem;
                align-items: center;
            }

            #rentalList li {
                flex-direction: column;
                align-items: stretch;
                gap: 0.3125rem;
                padding-bottom: 0.625rem;
                position: relative;
            }

            #rentalList li .rentalData {
                flex-direction: column;
            }

            #rentalList li:not(:last-child)::after {
                content: "";
                display: block;
                height: 1px;
                background-color: var(--primary);
                margin-top: 0.625rem;
                margin-bottom: 0.3125rem;
            }

            #rentalList input[type="text"] {
                width: 100%;
            }

            #rentalList .dataInRowForm {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            #rentalListContainer {
                padding: 0.9375rem;
            }

            #rentalList li .dataInRowForm {
                flex-direction: row;
                align-items: flex-start;
                justify-content: space-evenly;
                gap: 0.5rem;
            }

            #rentalList .dataInRowForm span {
                display: block;
                width: 100%;
                padding: 0.25rem 0;
            }
        }

        @media (max-width: 380px) {
            #rentalList input[type="text"] {
                padding: 4px;
                width: auto;
                min-width: 6rem;
                font-size: 0.85rem;
            }

            #rentalListContainer {
                padding: 0.5rem;
            }

            #rentalList li .dataInRowForm {
                flex-direction: row;
                align-items: center;
                gap: 0.1rem;
            }

            #rentalList .dataInRowForm span {
                display: block;
                width: 100%;
                padding: 0.25rem 0;
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