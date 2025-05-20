<?php
include_once "header.php";
include_once "functions/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$carId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch car details
$car = null;
$sql = "SELECT car_id, make, model, transmission, description FROM cars WHERE car_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $carId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $car = $result->fetch_assoc();
}
$stmt->close();

// Fetch current rentals (end_date > CURRENT_DATE)
$occupiedRanges = [];
if ($car) {
    $rentalSql = "SELECT r.rental_id, r.start_date, r.end_date, r.daily_rate, r.total_amount, r.place_contacted, c.client_id, c.full_name AS client_name, c.phone, r.notes 
                  FROM rentals r 
                  JOIN clients c ON r.client_id = c.client_id 
                  WHERE r.car_id = ? AND r.end_date > CURRENT_DATE";
    $stmt = $conn->prepare($rentalSql);
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $rentalResult = $stmt->get_result();
    while ($rental = $rentalResult->fetch_assoc()) {
        $occupiedRanges[] = [
            'rental_id' => $rental['rental_id'],
            'client_id' => $rental['client_id'],
            'client_name' => $rental['client_name'],
            'phone' => $rental['phone'],
            'place_contacted' => $rental['place_contacted'],
            'start_date' => $rental['start_date'],
            'end_date' => $rental['end_date'],
            'daily_rate' => $rental['daily_rate'],
            'total_amount' => $rental['total_amount'],
            'notes' => $rental['notes']
        ];
    }
    $stmt->close();
}

// Fetch past rentals (end_date <= CURRENT_DATE)
$occupiedRangesPast = [];
if ($car) {
    $pastRentalSql = "SELECT r.rental_id, r.start_date, r.end_date, r.daily_rate, r.total_amount, r.place_contacted, c.client_id, c.full_name AS client_name, c.phone, r.notes 
                      FROM rentals r 
                      JOIN clients c ON r.client_id = c.client_id 
                      WHERE r.car_id = ? AND r.end_date <= CURRENT_DATE";
    $stmt = $conn->prepare($pastRentalSql);
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $pastRentalResult = $stmt->get_result();
    while ($rental = $pastRentalResult->fetch_assoc()) {
        $occupiedRangesPast[] = [
            'rental_id' => $rental['rental_id'],
            'client_id' => $rental['client_id'],
            'client_name' => $rental['client_name'],
            'phone' => $rental['phone'],
            'place_contacted' => $rental['place_contacted'],
            'start_date' => $rental['start_date'],
            'end_date' => $rental['end_date'],
            'daily_rate' => $rental['daily_rate'],
            'total_amount' => $rental['total_amount'],
            'notes' => $rental['notes']
        ];
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Details - <?php echo htmlspecialchars($car ? $car['make'] . ' ' . $car['model'] : 'Not Found'); ?></title>
    <link href="css/car-details.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <div class="everything">
        <div class="card">
            <h2 id="carName"><?php echo htmlspecialchars($car ? $car['make'] . ' ' . $car['model'] . ' ' . $car['transmission'] : 'Car not found'); ?></h2>
            <p id="carDescription"><strong>Përshkrimi:</strong> <?php echo htmlspecialchars($car ? $car['description'] : 'No description available.'); ?></p>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <button class="editDescriptionBtn" onclick="openDescriptionModal()">Edit Description</button>
            <?php } ?>
            <div id="occupiedListContainer">
                <h3>Current Reservations</h3>
                <ul id="occupiedList"></ul>
                <button class="addRentalBtn" onclick="openRentalModal()">Add Rental</button>
            </div>
            <div id="pastRentalsContainer" style="margin-top: 20px;">
                <button class="togglePastRentalsBtn" onclick="togglePastRentals()">View past car reservations</button>
                <div id="pastRentals" style="display: none;">
                    <h3>Past Reservations</h3>
                    <ul id="pastOccupiedList"></ul>
                </div>
            </div>
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Rental Modal -->
    <div id="rentalModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add Rental</h3>
            <form id="rentalForm">
                <div class="modal-scroll-area">
                    <label for="clientSelection">Select or Edit Client</label>
                    <div class="clientNameChoice" style="display: flex;">
                        <input type="text" id="newClientName" placeholder="Client name" required>
                        <span style="align-self: center;">or</span>
                        <select id="existingClient" onchange="populateClientFields()">
                            <option value="">Select Client</option>
                        </select>
                    </div>
                    <label for="newClientPhone">Phone Number:</label>
                    <input type="tel" id="newClientPhone" placeholder="e.g., +383 4x xxx xxx" required>
                    <label for="placeContacted">Contact Method:</label>
                    <select id="placeContacted" required>
                        <option value="WhatsApp" selected>WhatsApp</option>
                        <option value="Viber">Viber</option>
                        <option value="Phone">Phone</option>
                    </select>
                    <label for="modalStartDate">Start Date:</label>
                    <input type="text" id="modalStartDate" class="flatpickr" placeholder="dd.mm.yyyy" required>
                    <label for="modalEndDate">End Date:</label>
                    <input type="text" id="modalEndDate" class="flatpickr" placeholder="dd.mm.yyyy" required>
                    <label for="modalTotalAmount">Total Payment (€):</label>
                    <input type="number" id="modalTotalAmount" min="0.01" step="0.01" required placeholder="e.g., 50.00">
                    <label for="modalDailyRate">Price per day (€):</label>
                    <input type="number" id="modalDailyRate" min="0" step="0.01" readonly>
                    <label for="modalNotes">Client Requests:</label>
                    <textarea id="modalNotes" placeholder="Enter any special requests or notes"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancelBtn" onclick="closeRentalModal()">Cancel</button>
                    <button type="submit" class="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Description Modal -->
    <div id="descriptionModal" class="modal">
        <div class="modal-content">
            <h3>Edit Description</h3>
            <form id="descriptionForm">
                <label for="modalDescription">Description:</label>
                <textarea id="modalDescription" required placeholder="Write the car description"></textarea>
                <div class="modal-buttons">
                    <button type="button" class="cancelBtn" onclick="closeDescriptionModal()">Cancel</button>
                    <button type="submit" class="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Pass PHP data to JavaScript
        const csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
        let clients = [];
        const carId = <?php echo json_encode($carId); ?>;
        const car = <?php echo json_encode($car); ?>;
        let occupiedRanges = <?php echo json_encode($occupiedRanges); ?>;
        let occupiedRangesPast = <?php echo json_encode($occupiedRangesPast); ?>;

        // Initialize Flatpickr for date inputs
        const flatpickrOptions = {
            disableMobile: true,
            dateFormat: "d.m.Y",
            altInput: true,
            altFormat: "d.m.Y",
            allowInput: true,
            onValueUpdate: calculateDailyRate
        };

        const startPicker = document.getElementById('modalStartDate');
        const endPicker = document.getElementById('modalEndDate');

        if (!startPicker._flatpickr) {
            flatpickr(startPicker, flatpickrOptions);
        }
        if (!endPicker._flatpickr) {
            flatpickr(endPicker, flatpickrOptions);
        }

        // Format date from yyyy-mm-dd to dd.mm.yyyy
        function formatDateToDMY(dateStr) {
            if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return '';
            const [year, month, day] = dateStr.split('-');
            return `${day}.${month}.${year}`;
        }

        // Parse date from dd.mm.yyyy to yyyy-mm-dd
        function parseDateFromDMY(dateStr) {
            if (!dateStr || !/^\d{2}\.\d{2}\.\d{4}$/.test(dateStr)) return '';
            const [day, month, year] = dateStr.split('.');
            return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        }

        // Fetch clients
        async function fetchClients() {
            try {
                const response = await fetch('functions/get-clients.php');
                if (!response.ok) throw new Error('Failed to fetch clients');
                clients = await response.json();
                populateExistingClients();
            } catch (error) {
                console.error('Error fetching clients:', error);
            }
        }

        // Populate client dropdown
        function populateExistingClients() {
            const select = document.getElementById('existingClient');
            select.innerHTML = '<option value="">Select client</option>';
            clients.forEach(client => {
                const option = document.createElement('option');
                option.value = client.client_id;
                option.textContent = `${client.full_name} (${client.phone}, ${client.place_contacted})`;
                select.appendChild(option);
            });
        }

        // Populate client fields when selecting existing client
        function populateClientFields() {
            const existingClientId = document.getElementById('existingClient').value;
            const newClientName = document.getElementById('newClientName');
            const newClientPhone = document.getElementById('newClientPhone');
            const placeContacted = document.getElementById('placeContacted');

            if (existingClientId) {
                const client = clients.find(c => c.client_id == existingClientId);
                if (client) {
                    newClientName.value = client.full_name;
                    newClientPhone.value = client.phone;
                    placeContacted.value = client.place_contacted || 'WhatsApp';
                }
            } else {
                newClientName.value = '';
                newClientPhone.value = '';
                placeContacted.value = 'WhatsApp';
            }
        }

        // Initialize UI
        if (car) {
            document.getElementById("carName").textContent = `${car.make} ${car.model} ${car.transmission}`;
            document.getElementById("carDescription").innerHTML = `<strong>Description:</strong> ${car.description || 'No description available.'}`;
            renderOccupiedList(occupiedRanges);
            renderPastOccupiedList(occupiedRangesPast);
        } else {
            console.error("Car not found for ID:", carId);
            document.getElementById("carName").textContent = "Car not found";
            document.getElementById("carDescription").innerHTML = `<strong>Description:</strong> No description available.`;
        }

        // Toggle past rentals visibility
        function togglePastRentals() {
            const pastRentalsDiv = document.getElementById('pastRentals');
            const toggleButton = document.querySelector('.togglePastRentalsBtn');
            if (pastRentalsDiv.style.display === 'none') {
                pastRentalsDiv.style.display = 'block';
                toggleButton.textContent = 'Hide the car\'s past reservations';
            } else {
                pastRentalsDiv.style.display = 'none';
                toggleButton.textContent = 'View the car\'s past reservations';
            }
        }

        // Render current rentals
        function renderOccupiedList(ranges) {
            const list = document.getElementById("occupiedList");
            list.innerHTML = "";
            ranges.forEach((range, index) => {
                const li = document.createElement("li");
                const startDate = formatDateToDMY(range.start_date);
                const endDate = formatDateToDMY(range.end_date);
                li.innerHTML = `
                    <div class="rentalData">
                        <span><strong>${escapeHTML(range.client_name || 'Unknown')}</strong> (${escapeHTML(range.phone || 'N/A')}, ${escapeHTML(range.place_contacted || 'N/A')})</span>
                    </div>
                    <div class="rentalData">
                        <div class="dataInRowForm" style="width: max-content;">
                            <input type="text" class="flatpickr-display" value="${startDate}" disabled>
                            →
                            <input type="text" class="flatpickr-display" value="${endDate}" disabled>
                        </div>
                        <div class="dataInRowForm">
                            <div class="dataInColForm">
                                <span>${parseFloat(range.daily_rate).toFixed(2)}€/day</span>
                                <span><strong>${parseFloat(range.total_amount).toFixed(2)}€</strong></span>
                            </div>
                            <div class="dataInColForm">
                                <button class="modifyBtn" onclick="openRentalModal(${index})">Modify</button>
                                <button class="removeBtn" onclick="removeRange(${index})">Delete</button>
                            </div>
                        </div>
                    </div>
                    ${range.notes ? `<div class='rentalNotes'><strong>Client Requests:</strong> ${escapeHTML(range.notes)}</div>` : ''}
                `;
                list.appendChild(li);
            });

            flatpickr(".flatpickr-display", {
                dateFormat: "d.m.Y",
                altInput: true,
                altFormat: "d.m.Y",
                allowInput: false,
                disableMobile: true
            });
        }

        // Render past rentals
        function renderPastOccupiedList(ranges) {
            const list = document.getElementById("pastOccupiedList");
            list.innerHTML = "";
            if (ranges.length === 0) {
                const li = document.createElement("li");
                li.innerHTML = `<span>There are no past reservations.</span>`;
                list.appendChild(li);
                return;
            }
            ranges.forEach(range => {
                const li = document.createElement("li");
                const startDate = formatDateToDMY(range.start_date);
                const endDate = formatDateToDMY(range.end_date);
                li.innerHTML = `
                    <div class="rentalData">
                        <span><strong>${escapeHTML(range.client_name || 'Unknown')}</strong> (${escapeHTML(range.phone || 'N/A')}, ${escapeHTML(range.place_contacted || 'N/A')})</span>
                    </div>
                    <div class="rentalData">
                        <div class="dataInRowForm" style="width: max-content;">
                            <input type="text" class="flatpickr-display" value="${startDate}" disabled>
                            →
                            <input type="text" class="flatpickr-display" value="${endDate}" disabled>
                        </div>
                        <div class="dataInRowForm">
                            <div class="dataInColForm">
                                <span>${parseFloat(range.daily_rate).toFixed(2)}€/day</span>
                                <span><strong>${parseFloat(range.total_amount).toFixed(2)}€</strong></span>
                            </div>
                        </div>
                    </div>
                    ${range.notes ? `<div class='rentalNotes'><strong>Client Requests:</strong> ${escapeHTML(range.notes)}</div>` : ''}
                `;
                list.appendChild(li);
            });

            flatpickr(".flatpickr-display", {
                dateFormat: "d.m.Y",
                altInput: true,
                altFormat: "d.m.Y",
                allowInput: false,
                disableMobile: true
            });
        }

        // Rental Modal handling
        let editingIndex = -1;

        async function openRentalModal(index = -1) {
            await fetchClients();
            
            const modal = document.getElementById("rentalModal");
            const form = document.getElementById("rentalForm");
            const title = document.getElementById("modalTitle");
            const newClientInput = document.getElementById("newClientName");
            const newClientPhone = document.getElementById("newClientPhone");
            const placeContacted = document.getElementById("placeContacted");
            const existingClientSelect = document.getElementById("existingClient");
            const startInput = document.getElementById("modalStartDate");
            const endInput = document.getElementById("modalEndDate");
            const dailyRateInput = document.getElementById("modalDailyRate");
            const totalAmountInput = document.getElementById("modalTotalAmount");
            const modalNotes = document.getElementById("modalNotes");

            editingIndex = index;

            if (index >= 0) {
                title.textContent = "Modify Rental";
                const range = occupiedRanges[index];
                newClientInput.value = range.client_name || '';
                newClientPhone.value = range.phone || '';
                placeContacted.value = range.place_contacted || 'WhatsApp';
                existingClientSelect.value = range.client_id || '';
                
                const formattedStartDate = formatDateToDMY(range.start_date);
                const formattedEndDate = formatDateToDMY(range.end_date);
                
                startInput._flatpickr.setDate(formattedStartDate, false);
                endInput._flatpickr.setDate(formattedEndDate, false);
                
                dailyRateInput.value = parseFloat(range.daily_rate).toFixed(2);
                totalAmountInput.value = parseFloat(range.total_amount).toFixed(2);
                modalNotes.value = range.notes || '';
            } else {
                title.textContent = "Add Rental";
                newClientInput.value = '';
                newClientPhone.value = '';
                placeContacted.value = 'WhatsApp';
                existingClientSelect.value = '';
                
                startInput._flatpickr.clear();
                endInput._flatpickr.clear();
                
                dailyRateInput.value = '';
                totalAmountInput.value = '';
                modalNotes.value = '';
            }

            modal.style.display = "flex";
            form.onsubmit = function (e) {
                e.preventDefault();
                saveRental();
            };
            calculateDailyRate();
        }

        // Calculate daily rate (unchanged)
        function calculateDailyRate() {
            const startInput = document.getElementById('modalStartDate');
            const endInput = document.getElementById('modalEndDate');
            const totalAmount = parseFloat(document.getElementById('modalTotalAmount').value) || 0;
            const dailyRateInput = document.getElementById('modalDailyRate');

            const startDate = startInput._flatpickr.altInput.value;
            const endDate = endInput._flatpickr.altInput.value;

            startInput._flatpickr.altInput.setCustomValidity('');
            endInput._flatpickr.altInput.setCustomValidity('');
            startInput._flatpickr.altInput.classList.remove('error');
            endInput._flatpickr.altInput.classList.remove('error');

            if (startDate && !isValidDate(startDate)) {
                startInput._flatpickr.altInput.setCustomValidity('Invalid date: the day does not exist for the selected month.');
                startInput._flatpickr.altInput.classList.add('error');
                dailyRateInput.value = '';
                return;
            }
            if (endDate && !isValidDate(endDate)) {
                endInput._flatpickr.altInput.setCustomValidity('Invalid date: the day does not exist in the selected month.');
                endInput._flatpickr.altInput.classList.add('error');
                dailyRateInput.value = '';
                return;
            }

            if (startDate && endDate && totalAmount > 0) {
                const start = parseDateFromDMY(startDate);
                const end = parseDateFromDMY(endDate);

                if (!start || !end) {
                    dailyRateInput.value = '';
                    return;
                }

                const startDateObj = new Date(start);
                const endDateObj = new Date(end);

                const diffTime = endDateObj - startDateObj;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays >= 1) {
                    const dailyRate = totalAmount / diffDays;
                    dailyRateInput.value = dailyRate.toFixed(2);
                } else {
                    dailyRateInput.value = totalAmount.toFixed(2);
                }
            } else {
                dailyRateInput.value = '';
            }
        }

        // Add event listeners for dynamic calculation
        document.getElementById('modalStartDate')._flatpickr.altInput.addEventListener('input', calculateDailyRate);
        document.getElementById('modalEndDate')._flatpickr.altInput.addEventListener('input', calculateDailyRate);
        document.getElementById('modalTotalAmount').addEventListener('input', calculateDailyRate);

        function closeRentalModal() {
            const modal = document.getElementById("rentalModal");
            modal.style.display = "none";
            editingIndex = -1;
        }

        async function saveRental() {
            const newClientName = document.getElementById("newClientName").value.trim();
            const newClientPhone = document.getElementById("newClientPhone").value.trim();
            const placeContacted = document.getElementById("placeContacted").value;
            const existingClientId = document.getElementById("existingClient").value;
            const startDate = document.getElementById("modalStartDate")._flatpickr.altInput.value;
            const endDate = document.getElementById("modalEndDate")._flatpickr.altInput.value;
            const dailyRate = parseFloat(document.getElementById("modalDailyRate").value);
            const totalAmount = parseFloat(document.getElementById("modalTotalAmount").value);
            const modalNotes = document.getElementById("modalNotes").value.trim();

            if (!newClientName) {
                alert("Please enter the customer's name.");
                return;
            }

            if (!newClientPhone || !/^\+?[0-9\s-]{7,20}$/.test(newClientPhone)) {
                alert("Please enter a valid phone number (7-20 digits).");
                return;
            }

            if (!placeContacted) {
                alert("Please choose a contact method.");
                return;
            }

            if (!startDate || !isValidDate(startDate)) {
                alert("Please enter a valid start date in the format dd.mm.yyyy (e.g., 24.04.2025).");
                return;
            }

            if (!endDate || !isValidDate(endDate)) {
                alert("Please enter a valid end date in the format dd.mm.yyyy (e.g., 24.04.2025).");
                return;
            }

            const start = parseDateFromDMY(startDate);
            const end = parseDateFromDMY(endDate);

            if (!start || !end || isNaN(dailyRate) || dailyRate <= 0 || isNaN(totalAmount) || totalAmount <= 0) {
                alert("Please fill in all fields with valid values.");
                return;
            }

            if (new Date(start) > new Date(end)) {
                alert("The end date must be after the start date.");
                return;
            }

            const newStartDate = new Date(start);
            const newEndDate = new Date(end);
            let overlappingRental = null;

            for (const range of occupiedRanges) {
                const existingStart = new Date(range.start_date);
                const existingEnd = new Date(range.end_date);
                
                if (editingIndex >= 0 && range.rental_id === occupiedRanges[editingIndex].rental_id) {
                    continue;
                }

                if (newStartDate <= existingEnd && newEndDate >= existingStart) {
                    overlappingRental = range;
                    break;
                }
            }

            if (overlappingRental) {
                const overlapMessage =  `Warning: The period you selected (${startDate} - ${endDate}) matches an existing reservation:\n\n` +
                                        `Client: ${overlappingRental.client_name}\n` +
                                        `Phone: ${overlappingRental.phone}\n` +
                                        `Date: ${formatDateToDMY(overlappingRental.start_date)} - ${formatDateToDMY(overlappingRental.end_date)}\n\n` +
                                        `Do you wish to continue?`;

                if (!confirm(overlapMessage)) {
                    return;
                }
            }

            const rentalData = {
                carId: carId,
                startDate: start,
                endDate: end,
                dailyRate: dailyRate,
                totalAmount: totalAmount,
                clientName: newClientName,
                phone: newClientPhone,
                placeContacted: placeContacted,
                csrf_token: csrfToken,
                notes: modalNotes
            };

            if (existingClientId) {
                rentalData.clientId = existingClientId;
            }

            if (editingIndex >= 0) {
                rentalData.rentalId = occupiedRanges[editingIndex].rental_id;
                rentalData.action = 'update';
            } else {
                rentalData.action = 'add';
            }

            try {
                const endpoint = editingIndex >= 0 ? 'functions/update-rental.php' : 'functions/add-rental.php';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(rentalData)
                });

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to save rental');
                }

                if (data.warning) {
                    const confirmMessage = `${data.message}\n\n` +
                                        `Would you like to update the phone number of the existing client or create a new client with the same name and a different number?`;
                    const userChoice = confirm(confirmMessage) ? 'update' : 'new';
                    
                    rentalData.clientChoice = userChoice;
                    if (userChoice === 'update') {
                        rentalData.clientId = data.existingClientId;
                    }
                    
                    const retryResponse = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(rentalData)
                    });

                    const retryData = await retryResponse.json();
                    
                    if (!retryResponse.ok || !retryData.success) {
                        throw new Error(retryData.message || 'Failed to save rental after client choice');
                    }

                    const rentalInfo = {
                        rental_id: retryData.rentalId || occupiedRanges[editingIndex]?.rental_id,
                        client_id: retryData.clientId || existingClientId,
                        client_name: newClientName,
                        phone: newClientPhone,
                        place_contacted: placeContacted,
                        start_date: start,
                        end_date: end,
                        daily_rate: dailyRate,
                        total_amount: totalAmount,
                        notes: modalNotes
                    };

                    if (editingIndex >= 0) {
                        occupiedRanges[editingIndex] = rentalInfo;
                    } else {
                        occupiedRanges.push(rentalInfo);
                    }
                    
                    renderOccupiedList(occupiedRanges);
                    closeRentalModal();
                    await fetchClients();
                    alert(editingIndex >= 0 ? "Reservation successfully updated!" : "Reservation successfully added!");
                } else if (data.success) {
                    const rentalInfo = {
                        rental_id: data.rentalId || occupiedRanges[editingIndex]?.rental_id,
                        client_id: data.clientId || existingClientId,
                        client_name: newClientName,
                        phone: newClientPhone,
                        place_contacted: placeContacted,
                        start_date: start,
                        end_date: end,
                        daily_rate: dailyRate,
                        total_amount: totalAmount,
                        notes: modalNotes
                    };

                    if (editingIndex >= 0) {
                        occupiedRanges[editingIndex] = rentalInfo;
                    } else {
                        occupiedRanges.push(rentalInfo);
                    }
                    
                    renderOccupiedList(occupiedRanges);
                    closeRentalModal();
                    await fetchClients();
                    alert(editingIndex >= 0 ? "Reservation successfully updated!" : "Reservation successfully added!");
                } else {
                    throw new Error(data.message || 'Failed to save rental');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            }
        }

        function isValidDate(dateStr) {
            if (!dateStr || !/^\d{2}\.\d{2}\.\d{4}$/.test(dateStr)) return false;
            const [day, month, year] = dateStr.split('.').map(Number);
            const date = new Date(year, month - 1, day);
            return date.getDate() === day && date.getMonth() === month - 1 && date.getFullYear() === year;
        }

        function openDescriptionModal() {
            const modal = document.getElementById("descriptionModal");
            const form = document.getElementById("descriptionForm");
            const descriptionInput = document.getElementById("modalDescription");

            descriptionInput.value = car ? car.description || '' : '';
            modal.style.display = "flex";
            form.onsubmit = function (e) {
                e.preventDefault();
                saveDescription();
            };
        }

        function closeDescriptionModal() {
            const modal = document.getElementById("descriptionModal");
            modal.style.display = "none";
        }

        function saveDescription() {
            const description = document.getElementById("modalDescription").value.trim();

            if (!description) {
                alert("Please write a description.");
                return;
            }

            fetch('functions/update-description.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    carId: carId,
                    description: description,
                    csrf_token: csrfToken
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Failed to update description');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById("carDescription").innerHTML = `<strong>Description:</strong> ${description}`;
                    car.description = description;
                    closeDescriptionModal();
                } else {
                    throw new Error(data.message || 'Failed to update description');
                }
            })
            .catch(error => {
                console.error('Error:', error.message);
                alert('Error: ' + error.message);
            });
        }

        function escapeHTML(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function removeRange(index) {
            const rentalId = occupiedRanges[index].rental_id;
            fetch('functions/update-rental.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    rentalId: rentalId,
                    action: 'delete',
                    csrf_token: csrfToken
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Failed to delete rental');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    occupiedRanges.splice(index, 1);
                    renderOccupiedList(occupiedRanges);
                } else {
                    throw new Error(data.message || 'Failed to delete rental');
                }
            })
            .catch(error => {
                console.error('Error:', error.message);
                alert('Error: ' + error.message);
            });
        }

        // Close modals on Esc key press
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeRentalModal();
                closeDescriptionModal();
            }
        });

        // Close modal when clicking outside the modal-content
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('mousedown', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    // Optionally reset editingIndex or other modal state here if needed
                }
            });
        });
    </script>
</body>
</html>