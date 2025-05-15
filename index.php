<?php 
include_once "header.php";
include_once "functions/db.php";

include_once "functions/functions.php";

$isAdmin = isset($_SESSION['user_id']);

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
        
        $cars[] = [
            'id' => $row['car_id'],
            'name' => $row['make'] . ' ' . $row['model'] . ' ' . $row['transmission'],
            'image' => $row['image_path'],
            'occupiedRanges' => $occupiedRanges,
            'details' => $row // Include all car details
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>A1 Rent a Car</title>
  <style>
    .everything {
      flex: 1;
      width: 100%;
    }
    
    .hero {
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1503376780353-7e6692767b70?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      color: white;
      text-align: center;
      padding: 150px 20px;
      margin-bottom: 60px;
      position: relative;
      overflow: hidden;
    }
    
    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(26,26,46,0.8), rgba(22,33,62,0.8));
      z-index: 1;
    }
    
    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 800px;
      margin: 0 auto;
    }
    
    .hero h1 {
      font-size: 4rem;
      margin-bottom: 25px;
      letter-spacing: 2px;
      line-height: 1.2;
    }
    
    .hero h1 span {
      display: inline;
      background: linear-gradient(45deg, #fff, #f0f0f0);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .hero p {
      font-size: 1.4rem;
      max-width: 700px;
      margin: 0 auto 40px;
      line-height: 1.6;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
    
    .cta-button {
      display: inline-block;
      padding: 15px 40px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 30px;
      font-weight: bold;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .cta-button:hover {
      background: transparent;
      border-color: white;
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .section-title {
      text-align: center;
      color: var(--text-dark);
      font-size: 2.5rem;
      margin-bottom: 40px;
      letter-spacing: 1px;
    }
    
    .featured-cars {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
    }
    
    .car-card {
      display: flex;
      flex-direction: column;
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      position: relative;
      height: 100%;
    }
    
    .car-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .car-image {
      height: 250px;
      background-color: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
    }
    
    .car-image img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform 0.3s ease;
    }

    .car-card:hover .car-image img {
      transform: scale(1.05);
    }

    .car-info {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      flex-grow: 1;
      padding: 25px;
      background: white;
    }

    .car-info h3 {
      margin-top: 0;
      color: var(--primary);
      font-size: 1.4rem;
      margin-bottom: 15px;
      line-height: 1.3;
    }

    .car-details {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    .car-detail {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-dark);
      font-size: 0.95rem;
    }

    .car-detail i {
      color: var(--primary);
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }
    
    .rentEdit {
      display: flex;
      flex-direction: row;
      margin: 15px 0 0;
      gap: 15px;
    }

    .rentEdit a {
      cursor: pointer;
      flex: 1;
      text-align: center;
    }
    
    .rent-button {
      display: inline-block;
      width: 100%;
      padding: 12px 20px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
      border: 2px solid transparent;
    }
    
    .rent-button:hover {
      background: transparent;
      color: var(--primary);
      border-color: var(--primary);
    }

    .car-price {
      position: absolute;
      top: 20px;
      right: 20px;
      background: var(--primary);
      color: white;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: bold;
      font-size: 1.1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .filter-section {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      margin-bottom: 40px;
    }
    
    .filter {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      align-items: end;
    }
    
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .filter-group label {
      font-size: 0.9rem;
      color: var(--text-dark);
      font-weight: 500;
    }
    
    .filter input, .filter select {
      padding: 12px 15px;
      font-size: 1rem;
      border-radius: 8px;
      border: 2px solid #e0e0e0;
      min-width: 100%;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }
    
    .filter input:hover, .filter select:hover {
      border-color: var(--primary);
      background-color: white;
    }

    .filter input:focus, .filter select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(26,26,46,0.1);
      background-color: white;
    }
    
    .filter-buttons {
      display: flex;
      gap: 10px;
      grid-column: 1 / -1;
      justify-content: center;
      margin-top: 10px;
    }
    
    .filter button {
      padding: 12px 25px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      border: 2px solid transparent;
    }
    
    .filter button:hover {
      background: transparent;
      color: var(--primary);
      border-color: var(--primary);
    }

    .filter button i {
      font-size: 1.1rem;
    }

    .filter button.clear {
      background: #f8f9fa;
      color: var(--text-dark);
      border: 2px solid #e0e0e0;
    }

    .filter button.clear:hover {
      background: #e9ecef;
      border-color: #dee2e6;
    }
    
    #calendarModal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      z-index: 999;
      min-width: 320px;
    }
    
    #calendarModal h3 {
      color: var(--text-dark);
      margin-bottom: 20px;
      font-size: 1.4rem;
      text-align: center;
    }
    
    #calendarModal table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    #calendarModal th {
      color: var(--text-dark);
      padding: 10px;
      text-align: center;
      font-weight: 500;
    }
    
    #calendarModal td {
      text-align: center; 
      padding: 12px;
      cursor: pointer;
      color: var(--text-dark);
      border-radius: 8px;
      transition: all 0.2s ease;
    }
    
    #calendarModal td:hover {
      background: var(--primary);
      color: white;
    }

    #calendarModal td.disabled {
      color: #ccc;
      cursor: not-allowed;
      background: #f8f9fa;
    }

    #calendarModal td.disabled:hover {
      background: #f8f9fa;
      color: #ccc;
    }

    #calendarModal td.selected {
      background: var(--primary);
      color: white;
    }
    
    #overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
      z-index: 998;
    }

    .loading {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255,255,255,0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      backdrop-filter: blur(4px);
    }

    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid var(--primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .no-results {
      text-align: center;
      padding: 40px;
      color: var(--text-dark);
      font-size: 1.2rem;
    }

    .no-results i {
      font-size: 3rem;
      color: #ccc;
      margin-bottom: 20px;
    }

    .occupied-dates li {
      display: flex;
      flex-direction: row;
      justify-content: space-evenly;
      gap: 10px;
      color: var(--text-dark);
    }

    @media (max-width: 1024px) {
      .hero h1 span:nth-child(2) {
        display: block;
      }

      .car-image {
        height: 220px;
      }
    }

    @media (max-width: 768px) {
      .featured-cars {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }

      #featured h2 span:nth-child(2) {
        display: block;
      }

      .hero h1 {
        font-size: 2rem;
      }

      .hero p {
        font-size: 1rem;
      }

      .car-image {
        height: 200px;
        padding: 15px;
      }

      .car-info {
        padding: 20px;
      }

      .car-info h3 {
        font-size: 1.2rem;
        margin-bottom: 12px;
      }

      .car-details {
        gap: 10px;
        margin-bottom: 15px;
      }

      .car-detail {
        font-size: 0.9rem;
      }

      .rent-button {
        padding: 8px 12px;
        font-size: 0.9rem;
      }

      .filter input, .filter select {
        min-width: 120px;
        font-size: 12px;
      }

      .filter button {
        padding: 8px 15px;
        font-size: 12px;
      }

      .rentEdit {
        display: flex;
        flex-direction: row;
        margin: 0 auto;
        gap: 5px;
      }
    }

    @media (max-width: 480px) {
      .filter input, .filter select {
        min-width: 100px;
      }

      .rent-button {
        padding: 6px 8px;
        font-size: 0.8rem;
      }
      
      .rentEdit {
        display: flex;
        flex-direction: row;
        margin: 0 auto;
        gap: 1px;
      }

      .featured-cars {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .car-image {
        height: 180px;
        padding: 12px;
      }

      .car-info {
        padding: 15px;
      }

      .car-info h3 {
        font-size: 1.1rem;
        margin-bottom: 10px;
      }

      .car-details {
        gap: 8px;
        margin-bottom: 12px;
      }

      .car-detail {
        font-size: 0.85rem;
      }

      .occupied-dates {
        flex-direction: column;
        gap: 4px;
        align-items: center;
        padding: 0;
        list-style: none;
      }

      .occupied-dates li {
        flex-direction: column;
        align-items: center;
        gap: 0px;
        word-break: break-word;
        text-align: center;
        border-bottom: var(--border);
      }
    }
  </style>
</head>
<body>
  <div class="everything">
    <!-- Loading Spinner -->
    <div class="loading" style="display: none;">
      <div class="loading-spinner"></div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <h1><span>Welcome to </span><span>A1 Rent A Car</span></h1>
        <p>Experience the thrill of driving premium cars at affordable prices. Book your dream car today!</p>
        <a href="#featured" class="cta-button">View Our Cars</a>
      </div>
    </section>
    
    <div class="container">
      <!-- Filter Section -->
      <?php if($isAdmin) {?>
      <section class="filter-section">
        <div class="filter">
          <div class="filter-group">
            <label for="makeFilter">Car Make</label>
            <input type="text" id="makeFilter" placeholder="e.g., Volkswagen">
          </div>
          <div class="filter-group">
            <label for="transmissionFilter">Transmission</label>
            <select id="transmissionFilter">
              <option value="">All Transmissions</option>
              <option value="Automatic">Automatic</option>
              <option value="Manual">Manual</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="fuelTypeFilter">Fuel Type</label>
            <select id="fuelTypeFilter">
              <option value="">All Fuel Types</option>
              <option value="Petrol">Petrol</option>
              <option value="Diesel">Diesel</option>
              <option value="Hybrid">Hybrid</option>
              <option value="Electric">Electric</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="seatsFilter">Number of Seats</label>
            <select id="seatsFilter">
              <option value="">All Seats</option>
              <option value="2">2 Seats</option>
              <option value="4">4 Seats</option>
              <option value="5">5 Seats</option>
              <option value="7">7 Seats</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="startDate">Start Date</label>
            <input type="date" id="startDate">
          </div>
          <div class="filter-group">
            <label for="endDate">End Date</label>
            <input type="date" id="endDate">
          </div>
          <div class="filter-buttons">
            <button onclick="filterCars()">
              <i class="fas fa-filter"></i> Apply Filters
            </button>
            <button onclick="resetFilter()" class="clear">
              <i class="fas fa-times"></i> Clear Filters
            </button>
          </div>
        </div>
      </section>
      <?php }?>
      
      <!-- Featured Cars -->
      <section id="featured">
        <h2 class="section-title"><span>Our List of </span> <span>Cars</span></h2>
        <div class="featured-cars" id="carList">
          <!-- Cars will be dynamically inserted here -->
        </div>
      </section>
    </div>
    
    <!-- Calendar Modal -->
    <div id="overlay"></div>
    <div id="calendarModal">
      <h3>Select Rental Dates</h3>
      <table>
        <thead>
          <tr>
            <th>Su</th>
            <th>Mo</th>
            <th>Tu</th>
            <th>We</th>
            <th>Th</th>
            <th>Fr</th>
            <th>Sa</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div class="modal-buttons" style="display: flex; gap: 10px; justify-content: center;">
        <button onclick="closeCalendar()" class="cancelBtn" style="background: #f8f9fa; color: var(--text-dark); border: 2px solid #e0e0e0;">Cancel</button>
        <button onclick="applyFilter()" class="saveBtn" style="background: var(--primary); color: white;">Apply</button>
      </div>
    </div>
  </div>
  
  <?php include "footer.php"?>

  <script>
    // Convert PHP cars array to JavaScript
    const cars = <?php echo json_encode($cars); ?>;
    
    let selecting = '';
    let currentCarId = null;

    function displayCars(list = cars) {
      const carList = document.getElementById('carList');
      carList.innerHTML = '';
      
      if (list.length === 0) {
        carList.innerHTML = `
          <div class="no-results">
            <i class="fas fa-car"></i>
            <p>No cars found matching your criteria</p>
          </div>
        `;
        return;
      }
      
      list.forEach(car => {
        const card = document.createElement('div');
        card.className = 'car-card';

        const message = `Përshëndetje, jam i interesuar në makinën: ${car.name}`;
        const whatsappLink = `https://api.whatsapp.com/send?phone=38348204402&text=${encodeURIComponent(message)}`;
        
        card.innerHTML = `
          <div class="car-image">
            <img src="${car.image}" alt="${car.name}">
          </div>
          <div class="car-info">
            <div>
              <h3>${car.name}</h3>
              <div class="car-details">
                <div class="car-detail">
                  <i class="fas fa-cog"></i>
                  <span>${car.details.transmission}</span>
                </div>
                <div class="car-detail">
                  <i class="fas fa-gas-pump"></i>
                  <span>${car.details.fuel_type}</span>
                </div>
                <div class="car-detail">
                  <i class="fas fa-chair"></i>
                  <span>${car.details.seats} Seats</span>
                </div>
                <div class="car-detail">
                  <i class="fas fa-car"></i>
                  <span>${car.details.make}</span>
                </div>
              </div>
            </div>
            <div class="rentEdit">
            <?php if(!$isAdmin){?>
              <a href="${whatsappLink}" class="rent-button" target="_blank">
                <i class="fab fa-whatsapp"></i> Rent Now
              </a>
              <?php }?>
              <?php if($isAdmin) { ?>
                <a class="rent-button" onclick="editDates(${car.id})">
                  <i class="fas fa-edit"></i> Edit Dates
                </a>
              <?php } ?>
            </div>
          </div>
        `;

        carList.appendChild(card);
      });
    }

    function showLoading() {
      document.querySelector('.loading').style.display = 'flex';
    }

    function hideLoading() {
      document.querySelector('.loading').style.display = 'none';
    }

    function filterCars() {
      showLoading();
      setTimeout(() => {
        const make = document.getElementById('makeFilter').value.trim().toLowerCase();
        const transmission = document.getElementById('transmissionFilter').value;
        const fuelType = document.getElementById('fuelTypeFilter').value;
        const seats = document.getElementById('seatsFilter').value;
        const start = new Date(document.getElementById('startDate').value);
        const end = new Date(document.getElementById('endDate').value);

        const filtered = cars.filter(car => {
          const makeMatch = !make || car.details.make.toLowerCase().includes(make);
          const transmissionMatch = !transmission || car.details.transmission === transmission;
          const fuelTypeMatch = !fuelType || car.details.fuel_type === fuelType;
          const seatsMatch = !seats || car.details.seats === parseInt(seats);
          const dateMatch = (!start || !end) || car.occupiedRanges.every(range =>
            new Date(range.end) < start || new Date(range.start) > end
          );

          return makeMatch && transmissionMatch && fuelTypeMatch && seatsMatch && dateMatch;
        });

        displayCars(filtered);
        hideLoading();
      }, 500); // Simulate loading for better UX
    }

    function renderCalendar() {
      const table = document.querySelector('#calendarModal tbody');
      table.innerHTML = '';

      const today = new Date();
      const year = today.getFullYear();
      const month = today.getMonth();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const firstDay = new Date(year, month, 1).getDay();

      // Add empty cells for days before the first day of the month
      for (let i = 0; i < firstDay; i++) {
        const td = document.createElement('td');
        table.appendChild(td);
      }

      // Add days of the month
      for (let d = 1; d <= daysInMonth; d++) {
        const td = document.createElement('td');
        td.textContent = d;
        
        // Check if date is in the past
        const currentDate = new Date(year, month, d);
        if (currentDate < today) {
          td.classList.add('disabled');
        } else {
          td.onclick = () => {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            document.getElementById(selecting + 'Date').value = dateStr;
            
            // Remove selected class from all cells
            table.querySelectorAll('td').forEach(cell => cell.classList.remove('selected'));
            // Add selected class to clicked cell
            td.classList.add('selected');
          };
        }
        
        table.appendChild(td);
      }
    }

    function closeCalendar() {
      document.getElementById('calendarModal').style.display = 'none';
      document.getElementById('overlay').style.display = 'none';
    }

    function applyFilter() {
      const selectedDate = document.getElementById(selecting + 'Date').value;
      if (!selectedDate || !currentCarId) return;

      const car = cars.find(c => c.id === currentCarId);
      if (!car) return;

      if (selecting === 'start') {
        car.tempStart = selectedDate;
      } else if (selecting === 'end' && car.tempStart) {
        car.occupiedRanges.push({ start: car.tempStart, end: selectedDate });
        delete car.tempStart;
      }

      displayCars();
      document.getElementById('calendarModal').style.display = 'none';
      document.getElementById('overlay').style.display = 'none';
    }

    function resetFilter() {
      document.getElementById('makeFilter').value = '';
      document.getElementById('transmissionFilter').value = '';
      document.getElementById('fuelTypeFilter').value = '';
      document.getElementById('seatsFilter').value = '';
      document.getElementById('startDate').value = '';
      document.getElementById('endDate').value = '';
      displayCars();
    }

    function editDates(carId) {
      window.location.href = `car-details.php?id=${carId}`;
    }

    function formatDate(dateStr) {
      const date = new Date(dateStr);
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}.${month}.${year}`;
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', () => {
      displayCars();
      hideLoading();
    });

    document.querySelector('a[href="#featured"]').addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector('#featured');
      const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
      const startPosition = window.pageYOffset;
      const distance = targetPosition - startPosition;
      const duration = 1000; // in milliseconds, increase for slower
      let start = null;

      function step(timestamp) {
          if (!start) start = timestamp;
          const progress = timestamp - start;
          const ease = easeInOutQuad(progress, startPosition, distance, duration);
          window.scrollTo(0, ease);
          if (progress < duration) {
              window.requestAnimationFrame(step);
          } else {
              window.scrollTo(0, targetPosition);
          }
      }

      function easeInOutQuad(t, b, c, d) {
          t /= d / 2;
          if (t < 1) return c / 2 * t * t + b;
          t--;
          return -c / 2 * (t * (t - 2) - 1) + b;
      }

      window.requestAnimationFrame(step);
    });
  </script>
</body>
</html>