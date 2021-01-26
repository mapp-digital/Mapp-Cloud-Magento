<?php
$host= "mysql";
$username = "root";
$password = "root";
 
// Create connection
$conn = new mysqli($host, $username, $password);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Drop Database
$sql_drop = "DROP DATABASE magento";
if ($conn->query($sql_drop) === TRUE) {
    echo "Magento database dropped. ";
} else {
    echo "Error dropping database: " . $conn->error;
}

 
// Create database
$sql_create = "CREATE DATABASE magento";
if ($conn->query($sql_create) === TRUE) {
    echo "Clean Magento database created.\n";
} else {
    echo "Error creating database: " . $conn->error;
}
 
$conn->close();
?>
