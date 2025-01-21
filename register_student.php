<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school1";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_name = htmlspecialchars($_POST['student_name']);
    $student_age = (int) $_POST['student_age'];
    $student_class = htmlspecialchars($_POST['student_class']);
    $student_phone = htmlspecialchars($_POST['student_phone']);

    // Validate inputs
    if (!is_numeric($student_age) || strlen($student_phone) < 7) {
        echo "<script>alert('Invalid input data! Please check the form.'); window.location.href = 'index.php';</script>";
        exit();
    }

    // Insert student data into the database
    $sql = "INSERT INTO student (name, age, class, phone) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $student_name, $student_age, $student_class, $student_phone);

    if ($stmt->execute()) {
        echo "<script>alert('Student registered successfully!'); window.location.href = 'dashboard.php';</script>";
    } else {
        echo "<script>alert('Error: Could not register student. Please try again.'); window.location.href = 'dashboard.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
