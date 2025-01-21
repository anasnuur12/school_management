<?php
require 'db_connection.php'; // Include your database connection file

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    $table = ($type == "students") ? "students" : "teachers";
    $idField = ($type == "students") ? "student_id" : "teacher_id";

    $stmt = $conn->prepare("SELECT name FROM $table WHERE $idField = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    echo $data['name'];
}
?>