<?php
// Xiriirinta database-ka
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_name = $_POST['teacher_name'];
    $teacher_email = $_POST['teacher_email'];
    $subject = $_POST['subject'];

    // Query si loo geliyo xogta macallimiinta
    $sql = "INSERT INTO teachers (name, email, subject) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $teacher_name, $teacher_email, $subject);

    if ($stmt->execute()) {
        echo "Teacher added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
