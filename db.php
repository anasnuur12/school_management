<?php
$servername = "localhost";
$username = "root";  // or your username
$password = "";      // or
?>
<?php
$servername = "localhost";
$username = "root";  // ama magacaaga user
$password = "";      // ama password-kaaga
$dbname = "school_management";

// Isku xirka database-ka
$conn = new mysqli($servername, $username, $password, $dbname);

// Hubinta haddii xiriirku uu guuleystay
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

