<?php
// Include database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all students for the dropdown
$students_query = "SELECT id, name FROM student";
$students_result = $conn->query($students_query);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];

    // Fetch student details
    $student_query = "SELECT * FROM student WHERE id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $name = $student['name'];
        $age = $student['age'];
        $class = $student['class'];
        $phone = $student['phone'];

        echo "<script>alert('Attendance marked for $name');</script>";

        // Send message (simulated)
        $message = "Hello $name,   waxaa kugu dhacay inaad maqnayd maanta fadlan ilaali xuduurka .";
        echo "<script>alert('Message sent to $phone: $message');</script>";
    } else {
        echo "<script>alert('Student not found');</script>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Mark Attendance</h1>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="student_id" class="form-label">Student ID</label>
            <select class="form-select" id="student_id" name="student_id" required>
                <option value="" disabled selected>Select Student</option>
                <?php
                if ($students_result->num_rows > 0) {
                    while ($row = $students_result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . $row['id'] . " - " . $row['name'] . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Mark Attendance</button>
    </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
