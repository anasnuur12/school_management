<?php
$servername = "localhost";  // Server name (badanaa localhost)
$username = "root";         // MySQL username (badanaa root)
$password = "";             // MySQL password (haddii aan la dhiggin password, waxaad ku qori kartaa "" ama waxaad gelineysaa password)
$dbname = "school1"; // Magaca database-ka

// Xiriir la samee MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Hubi haddii xiriirku uu guuleysto
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
