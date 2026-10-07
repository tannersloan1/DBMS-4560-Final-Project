<?php
include 'db_config.php'; // contains $servername, $username, $password, $dbname

//create connection
$conn = new mysqli($servername, $username, $password, $dbname);

//check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>