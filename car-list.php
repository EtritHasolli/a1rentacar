<?php
include_once "header.php";
include_once "functions/db.php";

// Fetch cars from database
$cars = [];
$sql = "SELECT * FROM cars";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Get occupied dates for each car
        $occupiedRanges = [];
        $rentalSql = "SELECT start_date, end_date FROM rentals WHERE car_id = ?";
        $stmt = $conn->prepare($rentalSql);
        $stmt->bind_param("i", $row['car_id']);
        $stmt->execute();
        $rentalResult = $stmt->get_result();
        
        while($rental = $rentalResult->fetch_assoc()) {
            $occupiedRanges[] = [
                'start' => $rental['start_date'],
                'end' => $rental['end_date']
            ];
        }
        $stmt->close();
        
        $cars[] = [
            'id' => $row['car_id'],
            'name' => $row['make'] . ' ' . $row['model'] . ' ' . $row['transmission'],
            'description' => $row['description'] ?? 'No description available.',
            'occupiedRanges' => $occupiedRanges
        ];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista e Makinave - A1 Rent a Car</title>
    <link href="css/car-list.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="section-title">Lista e Makinave</h2>
        <ul class="car-list" id="carList">
            <!-- Cars will be dynamically inserted here -->
        </ul>
    </div>

    <?php include "footer.php"; ?>
</body>
<script>
    // Convert PHP cars array to JavaScript
    const cars = <?php echo json_encode($cars); ?>;

    // Format date from yyyy-mm-dd to dd.mm.yyyy
    function formatDateToDMY(dateStr) {
        if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return '';
        const [year, month, day] = dateStr.split('-');
        return `${day.padStart(2, '0')}.${month.padStart(2, '0')}.${year}`;
    }

    function displayCars(list = cars) {
        const carList = document.getElementById('carList');
        carList.innerHTML = '';

        list.forEach(car => {
            const listItem = document.createElement('li');
            listItem.className = 'car-item';
            
            // Get the last 3 occupied dates
            const displayedDates = car.occupiedRanges.slice(-3);
            const hasMoreDates = car.occupiedRanges.length > 3;
            
            listItem.innerHTML = `
                <a href="car-details.php?id=${car.id}" class="car-link">
                    <div class="car-content">
                        <div class="car-details">
                            <h3>${car.name}</h3>
                            <p><strong>Përshkrimi:</strong> ${car.description}</p>
                        </div>
                        <div class="car-dates">
                            <p><strong>Datat e zëna:</strong></p>
                            <ul class="occupied-dates">
                                ${displayedDates.length > 0 
                                    ? displayedDates.map(r => `<li><span>${formatDateToDMY(r.start)}</span> - <span>${formatDateToDMY(r.end)}</span></li>`).join('')
                                    : '<li>Nuk ka data të zëna</li>'}
                            </ul>
                            ${hasMoreDates ? `<span class="more-dates">Shiko më shumë</span>` : ''}
                        </div>
                    </div>
                </a>
            `;

            carList.appendChild(listItem);
        });
    }

    // Initialize the page
    displayCars();
</script>
</html>