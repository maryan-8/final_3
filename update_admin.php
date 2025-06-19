<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "bearfruitsstudios";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "UPDATE admin SET username=?, password=? WHERE id=1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    echo "Admin credentials updated successfully.";
} else {
    echo "Failed to update credentials.";
}

$conn->close();
?>
