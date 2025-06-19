<?php

// Database config
$servername = "localhost";
$username = "root";
$password = ""; // default password in XAMPP is empty
$database = "bearfruitsstudios";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if($_SERVER["REQUEST_METHOD"] == "POST"){
  $name = $_POST["name"];
  $email = $_POST["email"];
  $phone = $_POST["phone"];
  $service = $_POST["service"];
  $message = $_POST["message"];
  $booking_date = $_POST["booking_date"];


  $sql = "INSERT INTO `bookings`(`name`, `email`, `phone`, `service`, `message`, `booking_date`) 
  VALUES ('$name','$email','$phone','$service','$message','$booking_date')";
  



    if ($conn->query($sql) === TRUE) {
      header("Location: success.html");
      exit();
    } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
    }
  }

$conn->close();
?>

