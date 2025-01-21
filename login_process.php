<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['psw'];

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

    // Fetch user data from the database
    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['username'] = $username;
        // Redirect to dashboard or home page
        header("Location: dashboard.php");
        exit();
    } else {
        // Invalid credentials
        echo "Invalid username or password.";
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>
