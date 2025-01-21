<?php
// Xiriirinta database-ka
include('config.php');

// Hubi haddii user uu galo
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Hubi haddii foomka la soo gudbiyey
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ka soo qaad xogta foomka oo hubi in ay jiraan
    if (isset($_POST['attendance_student']) && isset($_POST['attendance_date']) && isset($_POST['attendance_status'])) {
        $attendance_student = $_POST['attendance_student'];
        $attendance_date = $_POST['attendance_date'];
        $attendance_status = $_POST['attendance_status'];

        // Hubi haddii xogta foomka ay sax tahay
        if (empty($attendance_student) || empty($attendance_date) || empty($attendance_status)) {
            echo "Fadlan buuxi dhammaan xogta.";
        } else {
            // Query si loo kaydiyo xogta joogitaanka ardayda
            $sql = "INSERT INTO attendance1 (student_id, date, status) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                // Xaqiiji in la kaydiyo xogta
                $stmt->bind_param('iss', $attendance_student, $attendance_date, $attendance_status);

                // Execute query
                if ($stmt->execute()) {
                    echo "Attendance submitted successfully!";
                } else {
                    echo "Error: " . $stmt->error;
                }

                // Xir prepared statement
                $stmt->close();
            } else {
                echo "Error preparing query: " . $conn->error;
            }
        }
    } else {
        echo "Fadlan hubi xogta foomka.";
    }
}

// Xir xiriirka database-ka
$conn->close();
?>
