<?php
// Xiriirinta database-ka
include('config.php'); // ku dar file-ka database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_name = $_POST['student_name'];
    $student_email = $_POST['student_email'];
    $student_class = $_POST['student_class'];

    // Query si loo geliyo xogta ardayda
    $sql = "INSERT INTO students (name, email, class) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $student_name, $student_email, $student_class);

    if ($stmt->execute()) {
        echo "Student added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
