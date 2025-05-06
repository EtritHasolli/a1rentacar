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
      padding: 100px 20px;
      margin-bottom: 40px;
    }
    
    .hero h1 {
      font-size: 3.5rem;
      margin-bottom: 20px;
      letter-spacing: 2px;
    }
    
    .hero h1 span {
      display: inline;
    }
    
    .hero p {
      font-size: 1.2rem;
      max-width: 700px;
      margin: 0 auto 30px;
    }
    
    .cta-button {
      display: inline-block;
      padding: 12px 30px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
      transition: var(--transition);
    }
    
    .cta-button:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
      box-shadow: var(--shadow);
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
      border-radius: 10px;
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: var(--transition);
    }
    
    .car-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow);
    }
    
    .car-image {
      height: 200px;
      background-size: cover;
      background-position: center;
    }
    
    .car-info {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      flex-grow: 1;
      padding: 20px;
    }
    
    .car-info h3 {
      margin-top: 0;
      color: var(--primary);
    }
    
    .car-info p {
      color: var(--text-dark);
      margin-bottom: 20px;
    }
    
    .rent-button {
      display: inline-block;
      padding: 8px 20px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 5px;
      transition: var(--transition);
    }
    
    .rent-button:hover {
      background: var(--primary-dark);
    }

    .rentEdit {
      display: flex;
      flex-direction: row;
      margin: 10px auto;
      gap: 20px;
    }

    .rentEdit a {
      cursor: pointer;  
    }
    
    .filter-section {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: var(--shadow);
      margin-bottom: 40px;
    }
    
    .filter {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
      align-items: center;
    }
    
    .filter input, .filter select {
      padding: 10px 15px;
      font-size: 16px;
      border-radius: 5px;
      border: 2px solid #ddd;
      min-width: 200px;
    }
    
    .filter input:hover, .filter select:hover {
      border: var(--border);
      box-shadow: var(--shadow);
      transition: box-shadow 0.3s ease, border-color 0.3s ease;
    }
    
    .filter button {
      padding: 10px 25px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .filter button:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
    }
    
    #calendarModal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: #222;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 20px black;
      z-index: 999;
    }
    
    #calendarModal table td {
      text-align: center; 
      padding: 10px;
      cursor: pointer;
      color: white;
    }
    
    #calendarModal table td:hover {
      background: var(--calendar-td-hover);
    }
    
    #overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 998;
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
    }

    @media (max-width: 768px) {
      .featured-cars {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
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
        height: 150px;
      }

      .car-info {
        padding: 10px;
      }

      .car-info h3 {
        font-size: 1.1rem;
      }

      .rent-button {
        padding: 6px 12px;
        font-size: 0.8rem;
      }

      .filter input, .filter select {
        min-width: 120px;
        font-size: 12px;
      }

      .filter button {
        padding: 6px 15px;
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

      <?php if($isAdmin) {?>
      .rent-button {
        padding: 6px 6px;
        font-size: 0.65rem;
      }
      <?php }?>
      
      .rentEdit {
        display: flex;
        flex-direction: row;
        margin: 0 auto;
        gap: 1px;
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
    <!-- Hero Section -->
    <section class="hero">
      <h1><span>Welcome to </span><span>A1 Rent A Car</span></h1>
      <p>Experience the thrill of driving premium cars at affordable prices. Book your dream car today!</p>
      <a href="#featured" class="cta-button">View Our Cars</a>
    </section>
    
    <div class="container">
      <!-- Filter Section -->
      <?php if($isAdmin) {?>
      <section class="filter-section">
        <div class="filter">
          <input type="text" id="makeFilter" placeholder="Make (e.g., Volkswagen)">
          <select id="transmissionFilter">
            <option value="">Choose Transmission</option>
            <option value="Automatic">Automatic</option>
            <option value="Manual">Manual</option>
          </select>
          <select id="fuelTypeFilter">
            <option value="">Choose Fuel Type</option>
            <option value="Petrol">Petrol</option>
            <option value="Diesel">Diesel</option>
            <option value="Hybrid">Hybrid</option>
            <option value="Electric">Electric</option>
          </select>
          <select id="seatsFilter">
            <option value="">Choose Fuel Type</option>
            <option value="2">2 Seats</option>
            <option value="4">4 Seats</option>
            <option value="5">5 Seats</option>
            <option value="7">7 Seats</option>
          </select>
          <input type="date" id="startDate" placeholder="End Date">
          <input type="date" id="endDate" placeholder="Start Date">
          <button onclick="filterCars()">Filter</button>
          <button onclick="resetFilter()">Clear</button>
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
      <h3 style="color: #fff">Pick a Date</h3>
      <table></table>
      <br>
      <button onclick="applyFilter()">Apply</button>
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
      
      list.forEach(car => {
        const card = document.createElement('div');
        card.className = 'car-card';

        const message = `Përshëndetje, jam i interesuar në makinën: ${car.name}`;
        const whatsappLink = `https://api.whatsapp.com/send?phone=38348204402&text=${encodeURIComponent(message)}`;
        
        card.innerHTML = `
          <div class="car-image" style="background-image: url('${car.image}'); background-repeat: no-repeat; background-size: 90%;"></div>
          <div class="car-info">
            <div>
              <h3>${car.name}</h3>
            </div>
            <div class="rentEdit">
            <?php if(!$isAdmin){?>
              <a href="${whatsappLink}" class="rent-button" target="_blank">Rent Now</a>
              <?php }?>
              <?php if($isAdmin) { ?>
                <a class="rent-button" onclick="editDates(${car.id})">Edit Dates</a>
              <?php } ?>
            </div>
          </div>
        `;

        carList.appendChild(card);
      });
    }

    function filterCars() {
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
    }

    function openCalendar(target) {
      selecting = target;
      document.getElementById('overlay').style.display = 'block';
      document.getElementById('calendarModal').style.display = 'block';
      renderCalendar();
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

    function renderCalendar() {
      const table = document.querySelector('#calendarModal table');
      table.innerHTML = '';

      const today = new Date();
      const year = today.getFullYear();
      const month = today.getMonth();
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      let tr = document.createElement('tr');
      for (let d = 1; d <= daysInMonth; d++) {
        const td = document.createElement('td');
        td.textContent = d;
        td.onclick = () => {
          const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
          document.getElementById(selecting + 'Date').value = dateStr;
          applyFilter();
        };
        tr.appendChild(td);
        if ((new Date(year, month, d)).getDay() === 6) {
          table.appendChild(tr);
          tr = document.createElement('tr');
        }
      }
      table.appendChild(tr);
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
      displayCars();

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