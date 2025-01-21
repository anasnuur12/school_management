<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['psw'], PASSWORD_DEFAULT);

    // Database connection
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "school_managementt";

    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Save user data to the database
    $stmt = $conn->prepare("INSERT INTO user (name, username, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $username, $password);

    if ($stmt->execute()) {
        // Set session variables
        $_SESSION['username'] = $username;
        // Redirect to dashboard or home page
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signup Process</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
    <h2>Signup Successful</h2>
    <p>You have successfully signed up. <a href="login.php">Click here</a> to login.</p>
</body>
</html>
