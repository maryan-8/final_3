<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "bearfruitsstudios";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$defaultUsername = "admin";
$defaultPassword = "password";


$sql = "UPDATE admin SET username=?, password=? WHERE id=1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $defaultUsername, $defaultPassword);

if ($stmt->execute()) {
    echo "Username and password have been reset to default.";
} else {
    echo "Failed to reset username and password.";
}

$conn->close();
?>