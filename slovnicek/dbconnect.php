<?php
// PŘIPOJENÍ K DATABÁZÍ "slovnicek"
$conn = mysqli_connect('127.0.0.1', 'root', '', 'slovnicek');

if ($conn->connect_error) {
  die("Connection failed: " . mysqli_connect_error());
}
// printf("Success... %s\n", mysqli_get_host_info($conn));
// echo "Connected successfully";
mysqli_set_charset($conn, "utf8mb4");
?>