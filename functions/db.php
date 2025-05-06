<?php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['DatabaseHost'];
$username = $_ENV['DatabaseUsername'];
$password = $_ENV['DatabasePassword'];      
$dbname = $_ENV['DatabaseName'];  

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully";
?>
