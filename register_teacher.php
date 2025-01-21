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
    $teacher_name = htmlspecialchars($_POST['teacher_name']);
    $teacher_age = (int) $_POST['teacher_age'];
    $teacher_subject = htmlspecialchars($_POST['teacher_subject']);
    $teacher_phone = htmlspecialchars($_POST['teacher_phone']);

    // Validate inputs
    if (!is_numeric($teacher_age) || strlen($teacher_phone) < 7) {
        echo "<script>alert('Invalid input data! Please check the form.'); window.location.href = 'index.php';</script>";
        exit();
    }

    // Insert teacher data into the database
    $sql = "INSERT INTO teacher (name, age, subject, phone) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $teacher_name, $teacher_age, $teacher_subject, $teacher_phone);

    if ($stmt->execute()) {
        echo "<script>alert('Teacher registered successfully!'); window.location.href = 'dashboard.php';</script>";
    } else {
        echo "<script>alert('Error: Could not register teacher.'); window.location.href = 'dashboard.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
