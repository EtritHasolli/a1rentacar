<?php
$servername = "sql7.freesqldatabase.com";
$username = "sql7773932"; 
$password = "1A4XR5m71v";      
$dbname = "sql7773932";  

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully";
?>
