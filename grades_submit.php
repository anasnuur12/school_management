<?php
// Xiriirinta database-ka
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $grade_student = $_POST['grade_student'];
    $subject = $_POST['subject'];
    $grade = $_POST['grade'];

    // Query si loo kaydiyo natiijada ardayga
    $sql = "INSERT INTO grades (student_id, subject, grade) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $grade_student, $subject, $grade);

    if ($stmt->execute()) {
        echo "Grade submitted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
