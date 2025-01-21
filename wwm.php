<?php
session_start();

// Check if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Add logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Connection to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_managementt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default tab or get from session
if (isset($_GET['tab'])) {
    $_SESSION['active_tab'] = $_GET['tab'];
} elseif (!isset($_SESSION['active_tab'])) {
    $_SESSION['active_tab'] = 'register_student';
}

// Function to fetch data by ID
function fetchDataByID($conn, $table, $idField, $idValue) {
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ?");
    $stmt->bind_param("s", $idValue);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch class by student ID
function fetchClassByStudentID($conn, $studentId) {
    $stmt = $conn->prepare("SELECT class FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['class'];
}

// Function to fetch student details by student ID
function fetchStudentDetailsByID($conn, $studentId) {
    $stmt = $conn->prepare("SELECT name, class FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch photo by ID
function fetchPhotoByID($conn, $table, $idField, $idValue) {
    $stmt = $conn->prepare("SELECT photo FROM $table WHERE $idField = ?");
    $stmt->bind_param("s", $idValue);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['photo'];
}

// Fetch Classes
function fetchClasses($conn) {
    $result = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row['class'];
    }
    return $classes;
}

// Insert Student
if (isset($_POST['register_student'])) {
    $name = $_POST['student_name'];
    $phone = $_POST['student_phone'];
    $degma = $_POST['student_degma'];
    $class = $_POST['student_class'];
    $date = $_POST['student_date'];
    $photo = $_FILES['student_photo']['name'];
    $target = "uploads/" . basename($photo);
    move_uploaded_file($_FILES['student_photo']['tmp_name'], $target);

    $stmt = $conn->prepare("INSERT INTO students (name, phone, degmada, class, date, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $phone, $degma, $class, $date, $photo);
    if ($stmt->execute()) {
        echo "<script>alert('Student registered successfully!');</script>";
    } else {
        echo "<script>alert('Error registering student.');</script>";
    }
    $_SESSION['active_tab'] = 'register_student';
}

// Insert Teacher
if (isset($_POST['register_teacher'])) {
    $name = $_POST['teacher_name'];
    $phone = $_POST['teacher_phone'];
    $degma = $_POST['teacher_degma'];
    $subjects = implode(',', $_POST['teacher_subject']);
    $date = $_POST['teacher_date'];
    $photo = $_FILES['teacher_photo']['name'];
    $target = "uploads/" . basename($photo);
    move_uploaded_file($_FILES['teacher_photo']['tmp_name'], $target);

    $stmt = $conn->prepare("INSERT INTO teachers (name, phone, degmada, subjects, date, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $phone, $degma, $subjects, $date, $photo);
    if ($stmt->execute()) {
        echo "<script>alert('Teacher registered successfully!');</script>";
    } else {
        echo "<script>alert('Error registering teacher.');</script>";
    }
    $_SESSION['active_tab'] = 'register_teacher';
}

// Update data
if (isset($_POST['update_data']) && $_SESSION['active_tab'] == 'reports') {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $degma = $_POST['degma'];
    $additional = $_POST['additional'];

    $table = ($type == "students") ? "students" : "teachers";
    $idField = ($type == "students") ? "student_id" : "teacher_id";

    if ($type == "students") {
        $query = "UPDATE $table SET name=?, phone=?, degmada=?, class=? WHERE $idField=?";
    } else {
        $query = "UPDATE $table SET name=?, phone=?, degmada=?, subjects=? WHERE $idField=?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $name, $phone, $degma, $additional, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Record updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating record.');</script>";
    }
    $_SESSION['active_tab'] = 'manage_details';
}

// Delete data
if (isset($_POST['delete_data']) && $_SESSION['active_tab'] == 'reports') {
    $id = $_POST['id'];
    $type = $_POST['type'];

    $table = ($type == "students") ? "students" : "teachers";
    $idField = ($type == "students") ? "student_id" : "teacher_id";

    $stmt = $conn->prepare("DELETE FROM $table WHERE $idField = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting record.');</script>";
    }
    $_SESSION['active_tab'] = 'manage_details';
}

// Mark Attendance
if (isset($_POST['mark_attendance'])) {
    if (empty($_POST['attendance_type'])) {
        echo "<script>alert('Please select a type first!');</script>";
    } else {
        $id = $_POST['attendance_id'];
        $type = $_POST['attendance_type'];
        
        // Check if the ID exists in the respective table
        $table = ($type == "students") ? "students" : "teachers";
        $idField = ($type == "students") ? "student_id" : "teacher_id";
        $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo "<script>alert('Not registered student');</script>";
        } else {
            $message = ($type == "students") ? 
                "Fadlan waxaan araknay inaad maqnayd fadlan ilaali xuduurka" : 
                "Fadlan maanta waa maqnayd ilaali xuduurka";
            
            $attendanceTable = ($type == "students") ? "student_attendance" : "teacher_attendance";

            $stmt = $conn->prepare("INSERT INTO $attendanceTable (id, date, message) VALUES (?, NOW(), ?)");
            $stmt->bind_param("ss", $id, $message);
            if ($stmt->execute()) {
                echo "<script>alert('Attendance marked successfully!');</script>";
            } else {
                echo "<script>alert('Error marking attendance.');</script>";
            }
        }
    }
    $_SESSION['active_tab'] = 'attendance';
}

// Insert Exam
if (isset($_POST['add_exam'])) {
    $studentId = $_POST['student_id'];
    $class = $_POST['class'];
    $subjects = $_POST['subjects'];
    $marks = $_POST['marks'];
    $date = $_POST['date'];

    // Check if student ID exists
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $studentId); // Fix parameter name here
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "<script>alert('Student ID does not exist. Please enter a valid Student ID.');</script>";
    } else {
        foreach ($subjects as $index => $subject) {
            $mark = $marks[$index];
            // Check for duplicate entry of Student ID, Class, and Subject
            $stmt = $conn->prepare("SELECT * FROM exam WHERE student_id = ? AND class = ? AND subject = ?");
            $stmt->bind_param("sss", $studentId, $class, $subject); // Fix parameter name here
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<script>alert('Exam entry for this Student ID, Class, and Subject already exists.');</script>";
            } else {
                // Check for duplicate entry and handle Exam ID increment
                $duplicate = true;
                $initial_exam_id = 2242;
                $max_exam_id = 2300;
                while ($duplicate && $initial_exam_id <= $max_exam_id) {
                    $exam_id = str_pad($initial_exam_id, 5, '0', STR_PAD_LEFT);
                    $stmt = $conn->prepare("SELECT * FROM exam WHERE exam_id = ?");
                    $stmt->bind_param("s", $exam_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $initial_exam_id++;
                    } else {
                        $duplicate = false;
                    }
                }

                if ($duplicate) {
                    echo "<script>alert('Could not generate a unique Exam ID. Please try again.');</script>";
                } else {
                    $stmt = $conn->prepare("INSERT INTO exam (exam_id, student_id, class, subject, marks, date) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $exam_id, $studentId, $class, $subject, $mark, $date); // Fix parameter name here
                    if ($stmt->execute()) {
                        echo "<script>alert('Exam details added successfully!');</script>";
                    } else {
                        echo "<script>alert('Error adding exam details.');</script>";
                    }
                }
            }
        }
    }
    $_SESSION['active_tab'] = 'exam';
}

// Function to calculate grade
function calculateGrade($average) {
    if ($average >= 90) {
        return 'A';
    } elseif ($average >= 80) {
        return 'B';
    } elseif ($average >= 70) {
        return 'C';
    } elseif ($average >= 60) {
        return 'D';
    } else {
        return 'F';
    }
}

// Fetch Exam Results
if (isset($_POST['view_result'])) {
    $student_id = $_POST['student_id'];

    $stmt = $conn->prepare("SELECT e.*, s.name, s.photo, e.class FROM exam e JOIN students s ON e.student_id = s.student_id WHERE e.student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam_results = $result->fetch_all(MYSQLI_ASSOC);

    if (!empty($exam_results)) {
        $total_marks = 0;
        $num_exams = count($exam_results);
        $subjects_marks = [];
        foreach ($exam_results as $exam) {
            $total_marks += $exam['marks'];
            $subjects_marks[$exam['subject']] = $exam['marks'];
        }
        $average_marks = $num_exams > 0 ? $total_marks / $num_exams : 0;
        $grade = calculateGrade($average_marks);
    } else {
        $exam_results = [];
    }

    $_SESSION['active_tab'] = 'result';
}

// Fetch Exam Reports
if (isset($_POST['view_exam_reports'])) {
    $stmt = $conn->prepare("SELECT e.*, s.name, s.photo, e.class FROM exam e JOIN students s ON e.student_id = s.student_id ORDER BY e.date DESC");
    $stmt->execute();
    $exam_reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $_SESSION['active_tab'] = 'exam_reports';

    // Calculate total marks, average marks, and grade for each student
    $student_reports = [];
    foreach ($exam_reports as $exam) {
        $student_id = $exam['student_id'];
        if (!isset($student_reports[$student_id])) {
            $student_reports[$student_id] = [
                'total_marks' => 0,
                'num_exams' => 0,
                'average_marks' => 0,
                'grade' => '',
                'name' => $exam['name'],
                'class' => $exam['class'],
                'photo' => $exam['photo'],
                'subjects_marks' => [],
                'exam_ids' => [] // Add this line
            ];
        }
        $student_reports[$student_id]['total_marks'] += $exam['marks'];
        $student_reports[$student_id]['num_exams']++;
        $student_reports[$student_id]['subjects_marks'][$exam['subject']] = $exam['marks'];
        $student_reports[$student_id]['exam_ids'][] = $exam['exam_id']; // Add this line
    }
    foreach ($student_reports as $student_id => $report) {
        $student_reports[$student_id]['average_marks'] = $report['num_exams'] > 0 ? $report['total_marks'] / $report['num_exams'] : 0;
        $student_reports[$student_id]['grade'] = calculateGrade($student_reports[$student_id]['average_marks']);
    }
}

// Update and Delete Reports
if (isset($_POST['update_report'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $message = $_POST['message'];

    $table = ($type == "students") ? "student_attendance" : "teacher_attendance";
    $stmt = $conn->prepare("UPDATE $table SET message=? WHERE id=?");
    $stmt->bind_param("ss", $message, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Report updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating report.');</script>";
    }
    $_SESSION['active_tab'] = 'reports';
}

if (isset($_POST['delete_report'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];

    $table = ($type == "students") ? "student_attendance" : "teacher_attendance";
    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Report deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting report.');</script>";
    }
    $_SESSION['active_tab'] = 'reports';
}

// Update and Delete Exam Results
if (isset($_POST['update_exam'])) {
    $exam_id = $_POST['exam_id'];
    $student_id = $_POST['student_id'];
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $marks = $_POST['marks'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE exam SET class=?, subject=?, marks=?, date=? WHERE exam_id=? AND student_id=?");
    $stmt->bind_param("ssssss", $class, $subject, $marks, $date, $exam_id, $student_id);
    if ($stmt->execute()) {
        echo "<script>alert('Exam result updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating exam result.');</script>";
    }
    $_SESSION['active_tab'] = 'result';
}

if (isset($_POST['delete_exam'])) {
    $exam_id = $_POST['exam_id'];
    $student_id = $_POST['student_id'];

    $stmt = $conn->prepare("DELETE FROM exam WHERE exam_id = ? AND student_id = ?");
    $stmt->bind_param("ss", $exam_id, $student_id);
    if ($stmt->execute()) {
        echo "<script>alert('Exam result deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting exam result.');</script>";
    }
    $_SESSION['active_tab'] = 'result';
}

// Fetch Exam Details for Update
if (isset($_POST['fetch_exam'])) {
    $exam_id = $_POST['exam_id'];
    $stmt = $conn->prepare("SELECT * FROM exam WHERE exam_id = ?");
    $stmt->bind_param("s", $exam_id);
    $stmt->execute();
    $exam_details = $stmt->get_result()->fetch_assoc();
    $_SESSION['active_tab'] = 'exam';
}

// Fetch Students by Class
if (isset($_POST['view_students_by_class'])) {
    $class = $_POST['class'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ?");
    $stmt->bind_param("s", $class);
    $stmt->execute();
    $students_by_class = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $_SESSION['active_tab'] = 'students_by_class';
}

// Fetch Teachers by Subject
if (isset($_POST['view_teachers_by_subject'])) {
    $subject = $_POST['subject'];
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE FIND_IN_SET(?, subjects)");
    $stmt->bind_param("s", $subject);
    $stmt->execute();
    $teachers_by_subject = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $_SESSION['active_tab'] = 'teachers_by_subject';
}

// Fetch Exams by Class
if (isset($_POST['view_exams_by_class'])) {
    $class = $_POST['class'];
    $stmt = $conn->prepare("SELECT e.*, s.name FROM exam e JOIN students s ON e.student_id = s.student_id WHERE e.class = ?");
    $stmt->bind_param("s", $class);
    $stmt->execute();
    $exams_by_class = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $_SESSION['active_tab'] = 'exams_by_class';

    // Aggregate exam details by student
    $class_reports = [];
    foreach ($exams_by_class as $exam) {
        $student_id = $exam['student_id'];
        if (!isset($class_reports[$student_id])) {
            $class_reports[$student_id] = [
                'name' => $exam['name'],
                'class' => $exam['class'],
                'subjects_marks' => [],
                'exam_ids' => [] // Add this line
            ];
        }
        $class_reports[$student_id]['subjects_marks'][$exam['subject']] = $exam['marks'];
        $class_reports[$student_id]['exam_ids'][] = $exam['exam_id']; // Add this line
    }
}

// Delete Student by ID
if (isset($_POST['delete_student_by_id'])) {
    $student_id = $_POST['student_id'];
    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $_SESSION['active_tab'] = 'students_by_class';
}

// Delete Teacher by ID
if (isset($_POST['delete_teacher_by_id'])) {
    $teacher_id = $_POST['teacher_id'];
    $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $_SESSION['active_tab'] = 'teachers_by_subject';
}

// Delete Exam by ID
if (isset($_POST['delete_exam_by_id'])) {
    $exam_id = $_POST['exam_id'];
    $stmt = $conn->prepare("DELETE FROM exam WHERE exam_id = ?");
    $stmt->bind_param("s", $exam_id);
    $stmt->execute();
    $_SESSION['active_tab'] = 'exams_by_class';
}

// Fetch Student for Edit
if (isset($_POST['edit_student_by_id'])) {
    $student_id = $_POST['student_id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student_edit = $stmt->get_result()->fetch_assoc();
    $_SESSION['active_tab'] = 'students_by_class';
}

// Fetch Teacher for Edit
if (isset($_POST['edit_teacher_by_id'])) {
    $teacher_id = $_POST['teacher_id'];
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $teacher_edit = $stmt->get_result()->fetch_assoc();
    $_SESSION['active_tab'] = 'teachers_by_subject';
}

// Fetch Exam for Edit
if (isset($_POST['edit_exam_by_id'])) {
    $exam_id = $_POST['exam_id'];
    $stmt = $conn->prepare("SELECT * FROM exam WHERE exam_id = ?");
    $stmt->bind_param("s", $exam_id);
    $stmt->execute();
    $exam_edit = $stmt->get_result()->fetch_assoc();
    $_SESSION['active_tab'] = 'exams_by_class';
}

// Update Student
if (isset($_POST['update_student'])) {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $degma = $_POST['degma'];
    $class = $_POST['class'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE students SET name=?, phone=?, degmada=?, class=?, date=? WHERE student_id=?");
    $stmt->bind_param("ssssss", $name, $phone, $degma, $class, $date, $student_id);
    $stmt->execute();
    $_SESSION['active_tab'] = 'students_by_class';
}

// Update Teacher
if (isset($_POST['update_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $degma = $_POST['degma'];
    $subjects = $_POST['subjects'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE teachers SET name=?, phone=?, degmada=?, subjects=?, date=? WHERE teacher_id=?");
    $stmt->bind_param("ssssss", $name, $phone, $degma, $subjects, $date, $teacher_id);
    $stmt->execute();
    $_SESSION['active_tab'] = 'teachers_by_subject';
}

// Update Exam
if (isset($_POST['update_exam_by_id'])) {
    $exam_id = $_POST['exam_id'];
    $student_id = $_POST['student_id'];
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $marks = $_POST['marks'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE exam SET student_id=?, class=?, subject=?, marks=?, date=? WHERE exam_id=?");
    $stmt->bind_param("ssssss", $student_id, $class, $subject, $marks, $date, $exam_id);
    $stmt->execute();
    $_SESSION['active_tab'] = 'exams_by_class';
}

// Update Exam Result
if (isset($_POST['update_exam_result'])) {
    $exam_id = $_POST['exam_id'];
    $student_id = $_POST['student_id'];
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $marks = $_POST['marks'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("UPDATE exam SET class=?, subject=?, marks=?, date=? WHERE exam_id=? AND student_id=?");
    $stmt->bind_param("ssssss", $class, $subject, $marks, $date, $exam_id, $student_id);
    if ($stmt->execute()) {
        echo "<script>alert('Exam result updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating exam result.');</script>";
    }
    $_SESSION['active_tab'] = 'result';
}

// Update Attendance
if (isset($_POST['update_attendance'])) {
    $id = $_POST['attendance_id'];
    $type = $_POST['attendance_type'];
    $message = $_POST['message'];

    $table = ($type == "students") ? "student_attendance" : "teacher_attendance";
    $stmt = $conn->prepare("UPDATE $table SET message=? WHERE id=?");
    $stmt->bind_param("ss", $message, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Attendance updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating attendance.');</script>";
    }
    $_SESSION['active_tab'] = 'manage_attendance';
}

// Delete Attendance
if (isset($_POST['delete_attendance'])) {
    $id = $_POST['attendance_id'];
    $type = $_POST['attendance_type'];

    $table = ($type == "students") ? "student_attendance" : "teacher_attendance";
    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Attendance deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting attendance.');</script>";
    }
    $_SESSION['active_tab'] = 'manage_attendance';
}

// Fetch Class by Student ID for Exam Tab
if (isset($_GET['fetch_class_by_student_id'])) {
    $student_id = $_GET['student_id'];
    $class = fetchClassByStudentID($conn, $student_id);
    echo $class;
    exit;
}

// Fetch Exam Details for Editing
if (isset($_POST['edit_exam_report']) || isset($_POST['edit_exam_by_class'])) {
    $student_id = $_POST['student_id'];
    $class = $_POST['class'];
    $subjects_marks = json_decode($_POST['subjects_marks'], true);

    $exam_edit_details = [
        'student_id' => $student_id,
        'class' => $class,
        'subjects_marks' => $subjects_marks
    ];
    $_SESSION['active_tab'] = 'exam_edit';
}

// Update Exam Details
if (isset($_POST['update_exam_details'])) {
    $student_id = $_POST['student_id'];
    $class = $_POST['class'];
    $subjects = $_POST['subjects'];
    $marks = $_POST['marks'];

    foreach ($subjects as $index => $subject) {
        $mark = $marks[$index];
        $stmt = $conn->prepare("UPDATE exam SET marks=? WHERE student_id=? AND class=? AND subject=?");
        $stmt->bind_param("ssss", $mark, $student_id, $class, $subject);
        $stmt->execute();
    }

    echo "<script>alert('Exam details updated successfully!');</script>";
    $_SESSION['active_tab'] = 'exam_reports';
}

// Delete Exam Details
if (isset($_POST['delete_exam_details'])) {
    $student_id = $_POST['student_id'];
    $class = $_POST['class'];
    $subjects = $_POST['subjects'];

    foreach ($subjects as $subject) {
        $stmt = $conn->prepare("DELETE FROM exam WHERE student_id=? AND class=? AND subject=?");
        $stmt->bind_param("sss", $student_id, $class, $subject);
        $stmt->execute();
    }

    echo "<script>alert('Exam details deleted successfully!');</script>";
    $_SESSION['active_tab'] = 'exam_reports';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        .nav-link.active {
            background-color: #007bff !important;
            color: white !important;
        }
        .nav-item {
            cursor: pointer;
        }
        h1 { font-size: 3em; color: #fff; /* Qoraalka ka dhig cadaan si uu uga soocnaado buluuga */ background-color: #007bff; /* Background color buluug ah */ padding: 20px; /* Padding ku darso si uu u muuqdo mid fiican */ border-radius: 10px; /* Si koonayaasha uga dhawaadaan */ transition: all 0.5s ease-in-out; animation: fadeIn 3s ease-in-out; text-align: center; } h1:hover { color: #ff6347; transform: scale(1.2); }
         .welcome-message {
    color: #6F6F00FF; /* Initial color */
    font-weight: bold;
    animation: slide-in 1s forwards, slide-out 5s infinite 1s, resize 5s infinite, color-change 5s infinite;
}

@keyframes slide-in {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slide-out {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes resize {
    0%, 100% {
        font-size: 16px; /* Initial size */
    }
    50% {
        font-size: 32px; /* Enlarged size */
    }
}

@keyframes color-change {
    0% {
        color: #6F6F00FF; /* Initial color */
    }
    20% {
        color: #FF5733FF; /* Color 2 */
    }
    40% {
        color: #33FF57FF; /* Color 3 */
    }
    60% {
        color: #3357FFFF; /* Color 4 */
    }
    80% {
        color: #FF33FF; /* Color 5 */
    }
    100% {
        color: #6F6F00FF; /* Back to initial color */
    }
}

        .tab-pane#result {
            background-color: #f0f8ff;
        }
        .btn-fetch {
            background-color: #8B4513;
            color: white;
        }
        .btn-fetch:hover {
            background-color: #8B4513;
            color: white;
        }
        .btn-view-result {
            background-color: #28a745;
            color: white;
        }
        .btn-view-result:hover {
            background-color: #28a745;
            color: white;
        }
        .form-label {
        font-weight: bold;
        color: #007bff;
    }
    .form-control {
        border-radius: 5px;
        border: 1px solid #007bff;
    }
    .form-select {
        border-radius: 5px;
        border: 1px solid #007bff;
    }
    .btn-primary, .btn-success, .btn-warning, .btn-danger, .btn-dark, .btn-info {
        border-radius: 5px;
    }
    .btn-primary:hover, .btn-success:hover, .btn-warning:hover, .btn-danger:hover, .btn-dark:hover, .btn-info:hover {
        opacity: 0.8;
    }
    .mb-3 {
        margin-bottom: 1.5rem;
    }
    .btn-group {
        margin-top: 1rem;
    }
        
    </style>
</head>
<body>
<div class="container my-5">
    <!-- Welcome Message -->
    <p class="welcome-message">Welcome, <?php echo $_SESSION['user_name']; ?>!</p>

    <!-- Header with Logout Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">School Management System</h1>
        <a href="?logout=1" class="btn btn-danger">Logout</a>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'register_student') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#register_student" 
               onclick="window.location.href='?tab=register_student'">Register Student</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'register_teacher') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#register_teacher"
               onclick="window.location.href='?tab=register_teacher'">Register Teacher</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'view_students') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#view_students"
               onclick="window.location.href='?tab=view_students'">View Students</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'view_teachers') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#view_teachers"
               onclick="window.location.href='?tab=view_teachers'">View Teachers</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'manage_details') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#manage_details"
               onclick="window.location.href='?tab=manage_details'">Fetch & Manage Details</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'attendance') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#attendance"
               onclick="window.location.href='?tab=attendance'">Attendance</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'reports') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#reports"
               onclick="window.location.href='?tab=reports'">Reports</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'exam') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#exam"
               onclick="window.location.href='?tab=exam'">Exam</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'result') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#result"
               onclick="window.location.href='?tab=result'">Result</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'exam_reports') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#exam_reports"
               onclick="window.location.href='?tab=exam_reports'">Exam Reports</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'manage_attendance') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#manage_attendance"
               onclick="window.location.href='?tab=manage_attendance'">Manage Attendance</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'students_by_class') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#students_by_class"
               onclick="window.location.href='?tab=students_by_class'">Students by Class</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'teachers_by_subject') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#teachers_by_subject"
               onclick="window.location.href='?tab=teachers_by_subject'">Teachers by Subject</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'exams_by_class') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#exams_by_class"
               onclick="window.location.href='?tab=exams_by_class'">Exams by Class</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3">
                <!-- Register Student -->
                <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'register_student') ? 'show active' : ''; ?>" id="register_student">
            <h3>Register Student</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="student_name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="student_name" name="student_name" required>
                </div>
                <div class="mb-3">
                    <label for="student_phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="student_phone" name="student_phone" required>
                </div>
                <div class="mb-3">
                    <label for="student_degma" class="form-label">Degmada</label>
                    <input type="text" class="form-control" id="student_degma" name="student_degma" required>
                </div>
                <div class="mb-3">
                    <label for="student_class" class="form-label">Class</label>
                    <select class="form-select" id="student_class" name="student_class" required>
                        <option value="" selected>Select</option>
                        <option value="semister_1">semister_1</option>
                        <option value="semister_2">semister_2</option>
                        <option value="semister_3">semister_3</option>
                        <option value="semister_4">semister_4</option>
                        <option value="semister_5">semister_5</option>
                        <option value="semister_6">semister_6</option>
                        <option value="semister_7">semister_7</option>
                        <option value="semister_8">semister_8</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="student_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="student_date" name="student_date" required>
                </div>
                <div class="mb-3">
                    <label for="student_photo" class="form-label">Photo</label>
                    <input type="file" class="form-control" id="student_photo" name="student_photo" required>
                </div>
                <button type="submit" class="btn btn-primary" name="register_student">Register</button>
            </form>
        </div>

        <!-- Register Teacher -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'register_teacher') ? 'show active' : ''; ?>" id="register_teacher">
            <h3>Register Teacher</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="teacher_name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
                </div>
                <div class="mb-3">
                    <label for="teacher_phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="teacher_phone" name="teacher_phone" required>
                </div>
                <div class="mb-3">
                    <label for="teacher_degma" class="form-label">Degmada</label>
                    <input type="text" class="form-control" id="teacher_degma" name="teacher_degma" required>
                </div>
                <div class="mb-3">
                    <label for="teacher_subject" class="form-label">Subjects</label>
                    <select class="form-select" id="teacher_subject" name="teacher_subject[]" multiple required>
                        <option value="Math">Math</option>
                        <option value="English">English</option>
                        <option value="Arabic">Arabic</option>
                        <option value="Islamic">Islamic</option>
                        <option value="Chemistry">Chemistry</option>
                        <option value="ICT">ICT</option>
                        <option value="Critical Thinking,">Critical Thinking,</option>
                        <option value="Cell Biology">Cell Biology</option>
                        <option value="Medical Terminolog">Medical Terminolog</option>
                        <option value="General Physics">General Physics </option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="teacher_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="teacher_date" name="teacher_date" required>
                </div>
                <div class="mb-3">
                    <label for="teacher_photo" class="form-label">Photo</label>
                    <input type="file" class="form-control" id="teacher_photo" name="teacher_photo" required>
                </div>
                <button type="submit" class="btn btn-success" name="register_teacher">Register</button>
            </form>
        </div>
                <!-- Attendance -->
                <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'attendance') ? 'show active' : ''; ?>" id="attendance">
            <h3>Mark Attendance</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="attendance_type" class="form-label">Type</label>
                    <select class="form-select" id="attendance_type" name="attendance_type" required onchange="updateAttendanceIdLabel()">
                        <option value="">Select Type</option>
                        <option value="students">Student</option>
                        <option value="teachers">Teacher</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="attendance_id" class="form-label" id="attendanceIdLabel">Enter ID</label>
                    <input type="text" class="form-control" id="attendance_id" name="attendance_id" required>
                </div>
                <div class="mb-3">
                    <label for="person_name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="person_name" readonly>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <input type="text" class="form-control" id="message" name="message" readonly>
                </div>
                <button type="submit" class="btn btn-warning" name="mark_attendance">Mark Attendance</button>
            </form>

            <script>
                function updateAttendanceIdLabel() {
                    const typeSelect = document.getElementById('attendance_type');
                    const idLabel = document.getElementById('attendanceIdLabel');
                    const messageInput = document.getElementById('message');
                    
                    if (typeSelect.value === 'students') {
                        idLabel.textContent = 'Enter Student ID';
                        messageInput.value = 'Fadlan waxaan araknay inaad maqnayd fadlan ilaali xuduurka';
                    } else if (typeSelect.value === 'teachers') {
                        idLabel.textContent = 'Enter Teacher ID';
                        messageInput.value = 'Fadlan maanta waa maqnayd ilaali xuduurka';
                    } else {
                        idLabel.textContent = 'Enter ID';
                        messageInput.value = '';
                    }
                }

                // Add this new function to check ID and update name
                document.getElementById('attendance_id').addEventListener('change', function() {
                    const type = document.getElementById('attendance_type').value;
                    const id = this.value;
                    const nameField = document.getElementById('person_name');

                    if(type && id) {
                        fetch(`get_name.php?type=${type}&id=${id}`)
                            .then(response => response.text())
                            .then(name => {
                                nameField.value = name;
                            });
                    }
                });
            </script>
        </div>

        <!-- Reports -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'reports') ? 'show active' : ''; ?>" id="reports">
            <h3>Reports</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="report_type" class="form-label">Type</label>
                    <select class="form-select" id="report_type" name="report_type" required>
                        <option value="">Select Type</option>
                        <option value="students">Student Report</option>
                        <option value="teachers">Teacher Report</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-dark" name="generate_report">Generate Report</button>
            </form>
            <?php
            if (isset($_POST['generate_report'])) {
                if (empty($_POST['report_type'])) {
                    echo "<p class='text-danger mt-3'>Please select a type first!</p>";
                } else {
                    $_SESSION['active_tab'] = 'reports';
                    $type = $_POST['report_type'];
                    $table = ($type == "students") ? "student_attendance" : "teacher_attendance";
                    $mainTable = ($type == "students") ? "students" : "teachers";
                    $idField = ($type == "students") ? "student_id" : "teacher_id";

                    // Join with main table to get names
                    $sql = "SELECT a.*, m.name 
                           FROM $table a 
                           LEFT JOIN $mainTable m ON a.id = m.$idField 
                           ORDER BY a.date DESC";
                    
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        echo "<table class='table table-bordered mt-3'>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>";
                        while ($row = $result->fetch_assoc()) {
                            $message = ($type == "students") ? 
                                "Fadlan waxaan araknay inaad maqnayd fadlan ilaali xuduurka" : 
                                "Fadlan maanta waa maqnayd ilaali xuduurka";
                            
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['name']}</td>
                                    <td>{$row['date']}</td>
                                    <td>{$message}</td>
                                  </tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        $noRecordsMessage = ($type == "students") ? "No students attendance records found!" : "No teachers attendance records found!";
                        echo "<p class='text-danger mt-3'>{$noRecordsMessage}</p>";
                    }
                }
            }
            ?>
        </div>
                <!-- View Students -->
                <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'view_students') ? 'show active' : ''; ?>" id="view_students">
            <h3>Registered Students</h3>
            <div class="mb-3">
                <input type="text" class="form-control" id="studentSearch" placeholder="Search students..." onkeyup="searchStudents()">
            </div>
            <?php
            $result = $conn->query("SELECT * FROM students ORDER BY student_id");
            if ($result->num_rows > 0) {
                echo "<div class='table-responsive'>
                        <table class='table table-bordered mt-3' id='studentsTable'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Degmada</th>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Photo</th>
                                </tr>
                            </thead>
                            <tbody>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['student_id']}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['phone']}</td>
                            <td>{$row['degmada']}</td>
                            <td>{$row['class']}</td>
                            <td>{$row['date']}</td>
                            <td><img src='uploads/{$row['photo']}' alt='Photo' width='50'></td>
                          </tr>";
                }
                echo "</tbody></table></div>";
                echo "<p class='mt-3'>Total Students: " . $result->num_rows . "</p>";
            } else {
                echo "<p class='text-danger mt-3'>No students registered yet!</p>";
            }
            ?>
        </div>

        <!-- View Teachers -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'view_teachers') ? 'show active' : ''; ?>" id="view_teachers">
            <h3>Registered Teachers</h3>
            <div class="mb-3">
                <input type="text" class="form-control" id="teacherSearch" placeholder="Search teachers..." onkeyup="searchTeachers()">
            </div>
            <?php
            $result = $conn->query("SELECT * FROM teachers ORDER BY teacher_id");
            if ($result->num_rows > 0) {
                echo "<div class='table-responsive'>
                        <table class='table table-bordered mt-3' id='teachersTable'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Degmada</th>
                                    <th>Subjects</th>
                                    <th>Date</th>
                                    <th>Photo</th>
                                </tr>
                            </thead>
                            <tbody>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['teacher_id']}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['phone']}</td>
                            <td>{$row['degmada']}</td>
                            <td>{$row['subjects']}</td>
                            <td>{$row['date']}</td>
                            <td><img src='uploads/{$row['photo']}' alt='Photo' width='50'></td>
                          </tr>";
                }
                echo "</tbody></table></div>";
                echo "<p class='mt-3'>Total Teachers: " . $result->num_rows . "</p>";
            } else {
                echo "<p class='text-danger mt-3'>No teachers registered yet!</p>";
            }
            ?>
        </div>

        <script>
        function searchStudents() {
            var input = document.getElementById("studentSearch");
            var filter = input.value.toUpperCase();
            var table = document.getElementById("studentsTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td");
                var found = false;
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        var txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }

        function searchTeachers() {
            var input = document.getElementById("teacherSearch");
            var filter = input.value.toUpperCase();
            var table = document.getElementById("teachersTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td");
                var found = false;
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        var txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }

        function editStudent(id) {
            window.location.href = `?tab=manage_details&type=students&id=${id}`;
        }

        function deleteStudent(id) {
            if(confirm('Are you sure you want to delete this student?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="type" value="students">
                    <input type="hidden" name="delete_data" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editTeacher(id) {
            window.location.href = `?tab=manage_details&type=teachers&id=${id}`;
        }

        function deleteTeacher(id) {
            if(confirm('Are you sure you want to delete this teacher?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="type" value="teachers">
                    <input type="hidden" name="delete_data" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>
                <!-- Manage Details -->
                <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'manage_details') ? 'show active' : ''; ?>" id="manage_details">
            <h3>Fetch & Manage Details</h3>
            <form method="POST" action="" class="mb-4">
                <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" required onchange="updateIdLabel()">
                        <option value="">Select Type</option>
                        <option value="students" <?php echo (isset($_POST['type']) && $_POST['type'] == 'students') ? 'selected' : ''; ?>>Student</option>
                        <option value="teachers" <?php echo (isset($_POST['type']) && $_POST['type'] == 'teachers') ? 'selected' : ''; ?>>Teacher</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id" class="form-label" id="idLabel">Enter ID</label>
                    <input type="text" class="form-control" id="id" name="id" value="<?php echo isset($_POST['id']) ? $_POST['id'] : ''; ?>" required>
                </div>
                <button type="submit" class="btn btn-fetch" name="fetch_data">Fetch</button>
            </form>

            <script>
                function updateIdLabel() {
                    const typeSelect = document.getElementById('type');
                    const idLabel = document.getElementById('idLabel');
                    
                    if (typeSelect.value === 'students') {
                        idLabel.textContent = 'Enter Student ID';
                    } else if (typeSelect.value === 'teachers') {
                        idLabel.textContent = 'Enter Teacher ID';
                    } else {
                        idLabel.textContent = 'Enter ID';
                    }
                }

                // Call the function on page load if type is pre-selected
                window.onload = function() {
                    updateIdLabel();
                }
            </script>

            <?php
            // Fetch data if form is submitted
            if (isset($_POST['fetch_data'])) {
                $id = $_POST['id'];
                $type = $_POST['type'];
                $table = ($type == "students") ? "students" : "teachers";
                $idField = ($type == "students") ? "student_id" : "teacher_id";
                $data = fetchDataByID($conn, $table, $idField, $id);
                $classes = fetchClasses($conn);
                $photo = fetchPhotoByID($conn, $table, $idField, $id);

                if ($data) {
                    $additionalField = ($type == "students") ? "class" : "subjects";
                    echo "
                    <form method='POST' class='mt-3' enctype='multipart/form-data'>
                        <div class='mb-3'>
                            <label for='type' class='form-label'>Type</label>
                            <input type='text' class='form-control' value='" . ($type == "students" ? "Student" : "Teacher") . "' readonly>
                        </div>
                        <div class='mb-3'>
                            <label for='name' class='form-label'>Name</label>
                            <input type='text' class='form-control' id='name' name='name' value='{$data['name']}' required>
                        </div>
                        <div class='mb-3'>
                            <label for='phone' class='form-label'>Phone</label>
                            <input type='text' class='form-control' id='phone' name='phone' value='{$data['phone']}' required>
                        </div>
                        <div class='mb-3'>
                            <label for='degma' class='form-label'>Degmada</label>
                            <input type='text' class='form-control' id='degma' name='degma' value='{$data['degmada']}' required>
                        </div>";
                    
                    if ($type == "students") {
                        echo "<div class='mb-3'>
                                <label for='additional' class='form-label'>Class</label>
                                <select class='form-select' id='additional' name='additional' required>";
                        foreach ($classes as $class) {
                            $selected = ($data['class'] == $class) ? 'selected' : '';
                            echo "<option value='$class' $selected>$class</option>";
                        }
                        echo "</select>
                            </div>";
                    } else {
                        echo "<div class='mb-3'>
                                <label for='additional' class='form-label'>Subjects</label>
                                <input type='text' class='form-control' id='additional' name='additional' value='{$data['subjects']}' required>
                            </div>";
                    }

                    echo "<div class='mb-3'>
                            <label for='photo' class='form-label'>Photo</label>
                            <input type='file' class='form-control' id='photo' name='photo'>
                            <img src='uploads/{$photo}' alt='Photo' width='100' class='mt-2'>";
                    if ($photo) {
                        echo "
                            <div class='form-check'>
                                <input class='form-check-input' type='checkbox' id='delete_photo' name='delete_photo'>
                                <label class='form-check-label' for='delete_photo'>Delete Photo</label>
                            </div>";
                    }
                    echo "
                        </div>";

                    echo "<input type='hidden' name='id' value='{$id}'>
                          <input type='hidden' name='type' value='{$type}'>
                          <div class='btn-group'>
                              <button type='submit' class='btn btn-primary' name='update_data' onclick='return confirm(\"Are you sure you want to update this record?\")'>Update</button>
                              <button type='submit' class='btn btn-danger' name='delete_data' onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</button>
                          </div>
                    </form>";
                } else {
                    echo "<p class='text-danger mt-3'>No record found!</p>";
                }
            }
            ?>

            <?php
            // Update data
            if (isset($_POST['update_data']) && $_SESSION['active_tab'] == 'manage_details') {
                $id = $_POST['id'];
                $type = $_POST['type'];
                $name = $_POST['name'];
                $phone = $_POST['phone'];
                $degma = $_POST['degma'];
                $additional = $_POST['additional'];

                $table = ($type == "students") ? "students" : "teachers";
                $idField = ($type == "students") ? "student_id" : "teacher_id";
                $additionalField = ($type == "students") ? "class" : "subjects"; // Add this line

                $photo = $_FILES['photo']['name'];
                $deletePhoto = isset($_POST['delete_photo']) ? true : false;
                if ($deletePhoto) {
                    $oldPhoto = fetchPhotoByID($conn, $table, $idField, $id);
                    if ($oldPhoto && file_exists("uploads/" . $oldPhoto)) {
                        unlink("uploads/" . $oldPhoto);
                    }
                    $query = "UPDATE $table SET name=?, phone=?, degmada=?, $additionalField=?, photo=NULL WHERE $idField=?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssss", $name, $phone, $degma, $additional, $id);
                } else {
                    if ($photo) {
                        $target = "uploads/" . basename($photo);
                        move_uploaded_file($_FILES['photo']['tmp_name'], $target);
                        // Remove old photo
                        $oldPhoto = fetchPhotoByID($conn, $table, $idField, $id);
                        if ($oldPhoto && file_exists("uploads/" . $oldPhoto)) {
                            unlink("uploads/" . $oldPhoto);
                        }
                        $query = "UPDATE $table SET name=?, phone=?, degmada=?, $additionalField=?, photo=? WHERE $idField=?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssssss", $name, $phone, $degma, $additional, $photo, $id);
                    } else {
                        $query = "UPDATE $table SET name=?, phone=?, degmada=?, $additionalField=? WHERE $idField=?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssss", $name, $phone, $degma, $additional, $id);
                    }
                }
                if ($stmt->execute()) {
                    echo "<script>alert('Record updated successfully!');</script>";
                } else {
                    echo "<script>alert('Error updating record.');</script>";
                }
                $_SESSION['active_tab'] = 'manage_details';
            }
            ?>
        </div>
                <!-- Exam -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'exam') ? 'show active' : ''; ?>" id="exam">
    <h3>Add Exam Details</h3>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="student_id" class="form-label">Student ID</label>
            <input type="text" class="form-control" id="student_id" name="student_id" required onchange="fetchStudentDetails()">
        </div>
        <div class="mb-3">
            <label for="exam_id" class="form-label">Exam ID</label>
            <input type="text" class="form-control" id="exam_id" name="exam_id" value="02242" required>
        </div>
        <div class="mb-3">
            <label for="person_name" class="form-label">Student Name</label>
            <input type="text" class="form-control" id="person_name" name="student_name" readonly>
        </div>
        <div class="mb-3">
            <label for="student_photo" class="form-label">Photo</label>
            <img id="student_photo" src="" alt="Student Photo" width="100">
        </div>
        <div class="mb-3">
            <label for="class" class="form-label">Class</label>
            <select class="form-select" id="class" name="class" required>
                <option value="" selected>Select</option>
                <option value="semister_1">semister_1</option>
                <option value="semister_2">semister_2</option>
                <option value="semister_3">semister_3</option>
                <option value="semister_4">semister_4</option>
                <option value="semister_5">semister_5</option>
                <option value="semister_6">semister_6</option>
                <option value="semister_7">semister_7</option>
                <option value="semister_8">semister_8</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="subjects" class="form-label">Subjects and Marks</label>
            <div id="subjects-container">
                <div class="d-flex mb-2">
                    <select class="form-select me-2" name="subjects[]" required>
                        <option value="Math">Math</option>
                        <option value="English">English</option>
                        <option value="Arabic">Arabic</option>
                        <option value="Islamic">Islamic</option>
                        <option value="Chemistry">Chemistry</option>
                        <option value="ICT">ICT</option>
                        <option value="Critical Thinking">Critical Thinking</option>
                        <option value="Cell Biology">Cell Biology</option>
                        <option value="Medical Terminology">Medical Terminology</option>
                        <option value="General Physics">General Physics</option>
                    </select>
                    <input type="text" class="form-control" name="marks[]" placeholder="Marks" required>
                    <button type="button" class="btn btn-success ms-2" onclick="addSubject()">+</button>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="date" class="form-label">Date</label>
            <input type="date" class="form-control" id="date" name="date" required>
        </div>
        <button type="submit" class="btn btn-primary" name="add_exam">Add Exam</button>
    </form>
    <script>
        function addSubject() {
            const container = document.getElementById('subjects-container');
            const newSubject = document.createElement('div');
            newSubject.className = 'd-flex mb-2';
            const subjects = [
                "Math", "English", "Arabic", "Islamic", "Chemistry", 
                "ICT", "Critical Thinking", "Cell Biology", 
                "Medical Terminology", "General Physics"
            ];
            const currentSubjects = Array.from(container.querySelectorAll('select')).map(select => select.value);
            const availableSubjects = subjects.filter(subject => !currentSubjects.includes(subject));
            
            if (availableSubjects.length === 0) {
                document.querySelector('.btn-success').disabled = true;
                return;
            }

            newSubject.innerHTML = `
                <select class="form-select me-2" name="subjects[]" required>
                    ${availableSubjects.map(subject => `<option value="${subject}">${subject}</option>`).join('')}
                </select>
                <input type="text" class="form-control" name="marks[]" placeholder="Marks" required>
                <button type="button" class="btn btn-danger ms-2" onclick="removeSubject(this)">-</button>
            `;
            container.appendChild(newSubject);
        }

        function removeSubject(button) {
            button.parentElement.remove();
            document.querySelector('.btn-success').disabled = false;
        }
    </script>
    <script>
        function fetchClass() {
            const studentId = document.getElementById('student_id').value;
            const classSelect = document.getElementById('class');

            if (studentId) {
                fetch(`dashboard.php?fetch_class_by_student_id=1&student_id=${studentId}`)
                    .then(response => response.text())
                    .then(classValue => {
                        classSelect.value = classValue;
                    });
            }
        }

        document.getElementById('student_id').addEventListener('change', fetchClass);
    </script>
</div>

        <!-- Result -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'result') ? 'show active' : ''; ?>" id="result">
            <h3>View Exam Results</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo isset($_POST['student_id']) ? $_POST['student_id'] : ''; ?>" required>
                </div>
                <button type="submit" class="btn btn-view-result" name="view_result">View Result</button>
            </form>
            <?php if (isset($exam_results) && !empty($exam_results)): ?>
                <div class="mt-3">
                    <img src="uploads/<?php echo $exam_results[0]['photo']; ?>" alt="Student Photo" width="100">
                </div>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Math</th>
                            <th>English</th>
                            <th>Arabic</th>
                            <th>Islamic</th>
                            <th>Chemistry</th>
                            <th>ICT</th>
                            <th>Critical Thinking</th>
                            <th>Cell Biology</th>
                            <th>Medical Terminology</th>
                            <th>General Physics</th>
                            <th>Total Marks</th>
                            <th>Average Marks</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $exam_results[0]['student_id']; ?></td>
                            <td><?php echo $exam_results[0]['name']; ?></td>
                            <td><?php echo $exam_results[0]['class']; ?></td>
                            <td><?php echo isset($subjects_marks['Math']) ? $subjects_marks['Math'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['English']) ? $subjects_marks['English'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['Arabic']) ? $subjects_marks['Arabic'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['Islamic']) ? $subjects_marks['Islamic'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['Chemistry']) ? $subjects_marks['Chemistry'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['ICT']) ? $subjects_marks['ICT'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['Critical Thinking']) ? $subjects_marks['Critical Thinking'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['Cell Biology']) ? $subjects_marks['Cell Biology'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['Medical Terminology']) ? $subjects_marks['Medical Terminology'] : ''; ?></td>
                            <td><?php echo isset($subjects_marks['General Physics']) ? $subjects_marks['General Physics'] : ''; ?></td>
                            <td><?php echo $total_marks; ?></td>
                            <td><?php echo $average_marks; ?></td>
                            <td><?php echo $grade; ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-danger mt-3">No exam results found for this student.</p>
            <?php endif; ?>
        </div>

        <!-- Exam Reports -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'exam_reports') ? 'show active' : ''; ?>" id="exam_reports">
            <h3>Exam Reports</h3>
            <form method="POST" action="">
                <button type="submit" class="btn btn-info" name="view_exam_reports">View Exam Reports</button>
            </form>
            <?php if (isset($exam_reports)): ?>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Math</th>
                            <th>English</th>
                            <th>Arabic</th>
                            <th>Islamic</th>
                            <th>Chemistry</th>
                            <th>ICT</th>
                            <th>Critical Thinking</th>
                            <th>Cell Biology</th>
                            <th>Medical Terminology</th>
                            <th>General Physics</th>
                            <th>Total Marks</th>
                            <th>Average Marks</th>
                            <th>Grade</th>
                            <th>Actions</th> <!-- Add this line -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_reports as $student_id => $report): ?>
                            <tr>
                                <td><?php echo $student_id; ?></td>
                                <td><?php echo $report['name']; ?></td>
                                <td><?php echo $report['class']; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Math']) ? $report['subjects_marks']['Math'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['English']) ? $report['subjects_marks']['English'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Arabic']) ? $report['subjects_marks']['Arabic'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Islamic']) ? $report['subjects_marks']['Islamic'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Chemistry']) ? $report['subjects_marks']['Chemistry'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['ICT']) ? $report['subjects_marks']['ICT'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Critical Thinking']) ? $report['subjects_marks']['Critical Thinking'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Cell Biology']) ? $report['subjects_marks']['Cell Biology'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Medical Terminology']) ? $report['subjects_marks']['Medical Terminology'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['General Physics']) ? $report['subjects_marks']['General Physics'] : ''; ?></td>
                                <td><?php echo $report['total_marks']; ?></td>
                                <td><?php echo $report['average_marks']; ?></td>
                                <td><?php echo $report['grade']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                        <input type="hidden" name="class" value="<?php echo $report['class']; ?>">
                                        <input type="hidden" name="subjects_marks" value="<?php echo htmlspecialchars(json_encode($report['subjects_marks'])); ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" name="edit_exam_report">Edit</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Manage Attendance -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'manage_attendance') ? 'show active' : ''; ?>" id="manage_attendance">
            <h3>Manage Attendance</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="attendance_type_manage" class="form-label">Type</label>
                    <select class="form-select" id="attendance_type_manage" name="attendance_type_manage" required>
                        <option value="">Select Type</option>
                        <option value="students">Student</option>
                        <option value="teachers">Teacher</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="attendance_id_manage" class="form-label">Enter ID</label>
                    <input type="text" class="form-control" id="attendance_id_manage" name="attendance_id_manage" required>
                </div>
                <button type="submit" class="btn btn-primary" name="fetch_attendance">Fetch Attendance</button>
            </form>

            <?php
            if (isset($_POST['fetch_attendance'])) {
                $id = $_POST['attendance_id_manage'];
                $type = $_POST['attendance_type_manage'];
                $attendanceTable = ($type == "students") ? "student_attendance" : "teacher_attendance";

                $stmt = $conn->prepare("SELECT * FROM $attendanceTable WHERE id = ? ORDER BY date DESC");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo "<table class='table table-bordered mt-3'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Message</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['date']}</td>
                                <td>{$row['message']}</td>
                                <td>
                                    <form method='POST' class='d-inline'>
                                        <input type='hidden' name='attendance_id' value='{$row['id']}'>
                                        <input type='hidden' name='attendance_type' value='{$type}'>
                                        <input type='hidden' name='message' value='{$row['message']}'>
                                        <button type='submit' class='btn btn-warning btn-sm' name='edit_attendance'>Edit</button>
                                    </form>
                                    <form method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this attendance?\")'>
                                        <input type='hidden' name='attendance_id' value='{$row['id']}'>
                                        <input type='hidden' name='attendance_type' value='{$type}'>
                                        <button type='submit' class='btn btn-danger btn-sm' name='delete_attendance'>Delete</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p class='text-danger mt-3'>No attendance records found!</p>";
                }
            }

            if (isset($_POST['edit_attendance'])) {
                $id = $_POST['attendance_id'];
                $type = $_POST['attendance_type'];
                $message = $_POST['message'];
                echo "
                <h3 class='mt-5'>Edit Attendance</h3>
                <form method='POST' action=''>
                    <div class='mb-3'>
                        <label for='attendance_id' class='form-label'>ID</label>
                        <input type='text' class='form-control' id='attendance_id' name='attendance_id' value='$id' readonly>
                    </div>
                    <div class='mb-3'>
                        <label for='attendance_type' class='form-label'>Type</label>
                        <input type='text' class='form-control' id='attendance_type' name='attendance_type' value='$type' readonly>
                    </div>
                    <div class='mb-3'>
                        <label for='message' class='form-label'>Message</label>
                        <input type='text' class='form-control' id='message' name='message' value='$message' required>
                    </div>
                    <button type='submit' class='btn btn-primary' name='update_attendance'>Update Attendance</button>
                </form>";
            }
            ?>
        </div>

        <!-- Students by Class -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'students_by_class') ? 'show active' : ''; ?>" id="students_by_class">
            <h3>View Students by Class</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="class" class="form-label">Class</label>
                    <select class="form-select" id="class" name="class" required>
                        <option value="semister_1" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_1') ? 'selected' : ''; ?>>semister_1</option>
                        <option value="semister_2" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_2') ? 'selected' : ''; ?>>semister_2</option>
                        <option value="semister_3" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_3') ? 'selected' : ''; ?>>semister_3</option>
                        <option value="semister_4" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_4') ? 'selected' : ''; ?>>semister_4</option>
                        <option value="semister_5" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_5') ? 'selected' : ''; ?>>semister_5</option>
                        <option value="semister_6" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_6') ? 'selected' : ''; ?>>semister_6</option>
                        <option value="semister_7" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_7') ? 'selected' : ''; ?>>semister_7</option>
                        <option value="semister_8" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_8') ? 'selected' : ''; ?>>semister_8</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="view_students_by_class">View Students</button>
            </form>
            <?php if (isset($students_by_class)): ?>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Degmada</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_by_class as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['phone']; ?></td>
                                <td><?php echo $student['degmada']; ?></td>
                                <td><?php echo $student['class']; ?></td>
                                <td><?php echo $student['date']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" name="edit_student_by_id">Edit</button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this student?')">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" name="delete_student_by_id">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (isset($student_edit)): ?>
                <h3 class="mt-5">Edit Student</h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $student_edit['name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $student_edit['phone']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="degma" class="form-label">Degmada</label>
                        <input type="text" class="form-control" id="degma" name="degma" value="<?php echo $student_edit['degmada']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class" required>
                            <option value="semister_1" <?php echo ($student_edit['class'] == 'semister_1') ? 'selected' : ''; ?>>semister_1</option>
                            <option value="semister_2" <?php echo ($student_edit['class'] == 'semister_2') ? 'selected' : ''; ?>>semister_2</option>
                            <option value="semister_3" <?php echo ($student_edit['class'] == 'semister_3') ? 'selected' : ''; ?>>semister_3</option>
                            <option value="semister_4" <?php echo ($student_edit['class'] == 'semister_4') ? 'selected' : ''; ?>>semister_4</option>
                            <option value="semister_5" <?php echo ($student_edit['class'] == 'semister_5') ? 'selected' : ''; ?>>semister_5</option>
                            <option value="semister_6" <?php echo ($student_edit['class'] == 'semister_6') ? 'selected' : ''; ?>>semister_6</option>
                            <option value="semister_7" <?php echo ($student_edit['class'] == 'semister_7') ? 'selected' : ''; ?>>semister_7</option>
                            <option value="semister_8" <?php echo ($student_edit['class'] == 'semister_8') ? 'selected' : ''; ?>>semister_8</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $student_edit['date']; ?>" required>
                    </div>
                    <input type="hidden" name="student_id" value="<?php echo $student_edit['student_id']; ?>">
                    <button type="submit" class="btn btn-primary" name="update_student" onclick="return confirm('Are you sure you want to update this student?')">Update Student</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Teachers by Subject -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'teachers_by_subject') ? 'show active' : ''; ?>" id="teachers_by_subject">
            <h3>View Teachers by Subject</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject" required>
                        <option value="Math" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Math') ? 'selected' : ''; ?>>Math</option>
                        <option value="English" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'English') ? 'selected' : ''; ?>>English</option>
                        <option value="Arabic" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Arabic') ? 'selected' : ''; ?>>Arabic</option>
                        <option value="Islamic" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Islamic') ? 'selected' : ''; ?>>Islamic</option>
                        <option value="Chemistry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Chemistry') ? 'selected' : ''; ?>>Chemistry</option>
                        <option value="ICT" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'ICT') ? 'selected' : ''; ?>>ICT</option>
                        <option value="Critical Thinking" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Critical Thinking') ? 'selected' : ''; ?>>Critical Thinking</option>
                        <option value="Cell Biology" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Cell Biology') ? 'selected' : ''; ?>>Cell Biology</option>
                        <option value="Medical Terminology" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Medical Terminology') ? 'selected' : ''; ?>>Medical Terminology</option>
                        <option value="General Physics" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'General Physics') ? 'selected' : ''; ?>>General Physics</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="view_teachers_by_subject">View Teachers</button>
            </form>
            <?php if (isset($teachers_by_subject)): ?>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Degmada</th>
                            <th>Subjects</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers_by_subject as $teacher): ?>
                            <tr>
                                <td><?php echo $teacher['teacher_id']; ?></td>
                                <td><?php echo $teacher['name']; ?></td>
                                <td><?php echo $teacher['phone']; ?></td>
                                <td><?php echo $teacher['degmada']; ?></td>
                                <td><?php echo $teacher['subjects']; ?></td>
                                <td><?php echo $teacher['date']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" name="edit_teacher_by_id">Edit</button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this teacher?')">
                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" name="delete_teacher_by_id">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (isset($teacher_edit)): ?>
                <h3 class="mt-5">Edit Teacher</h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $teacher_edit['name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $teacher_edit['phone']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="degma" class="form-label">Degmada</label>
                        <input type="text" class="form-control" id="degma" name="degma" value="<?php echo $teacher_edit['degmada']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="subjects" class="form-label">Subjects</label>
                        <input type="text" class="form-control" id="subjects" name="subjects" value="<?php echo $teacher_edit['subjects']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $teacher_edit['date']; ?>" required>
                    </div>
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_edit['teacher_id']; ?>">
                    <button type="submit" class="btn btn-primary" name="update_teacher" onclick="return confirm('Are you sure you want to update this teacher?')">Update Teacher</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Exams by Class -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'exams_by_class') ? 'show active' : ''; ?>" id="exams_by_class">
            <h3>View Exams by Class</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="class" class="form-label">Class</label>
                    <select class="form-select" id="class" name="class" required>
                        <option value="semister_1" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_1') ? 'selected' : ''; ?>>semister_1</option>
                        <option value="semister_2" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_2') ? 'selected' : ''; ?>>semister_2</option>
                        <option value="semister_3" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_3') ? 'selected' : ''; ?>>semister_3</option>
                        <option value="semister_4" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_4') ? 'selected' : ''; ?>>semister_4</option>
                        <option value="semister_5" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_5') ? 'selected' : ''; ?>>semister_5</option>
                        <option value="semister_6" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_6') ? 'selected' : ''; ?>>semister_6</option>
                        <option value="semister_7" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_7') ? 'selected' : ''; ?>>semister_7</option>
                        <option value="semister_8" <?php echo (isset($_POST['class']) && $_POST['class'] == 'semister_8') ? 'selected' : ''; ?>>semister_8</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="view_exams_by_class">View Exams</button>
            </form>
            <?php if (isset($class_reports)): ?>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Math</th>
                            <th>English</th>
                            <th>Arabic</th>
                            <th>Islamic</th>
                            <th>Chemistry</th>
                            <th>ICT</th>
                            <th>Critical Thinking</th>
                            <th>Cell Biology</th>
                            <th>Medical Terminology</th>
                            <th>General Physics</th>
                            <th>Actions</th> <!-- Add this line -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($class_reports as $student_id => $report): ?>
                            <tr>
                                <td><?php echo $student_id; ?></td>
                                <td><?php echo $report['name']; ?></td>
                                <td><?php echo $report['class']; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Math']) ? $report['subjects_marks']['Math'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['English']) ? $report['subjects_marks']['English'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Arabic']) ? $report['subjects_marks']['Arabic'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Islamic']) ? $report['subjects_marks']['Islamic'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Chemistry']) ? $report['subjects_marks']['Chemistry'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['ICT']) ? $report['subjects_marks']['ICT'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Critical Thinking']) ? $report['subjects_marks']['Critical Thinking'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Cell Biology']) ? $report['subjects_marks']['Cell Biology'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['Medical Terminology']) ? $report['subjects_marks']['Medical Terminology'] : ''; ?></td>
                                <td><?php echo isset($report['subjects_marks']['General Physics']) ? $report['subjects_marks']['General Physics'] : ''; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                        <input type="hidden" name="class" value="<?php echo $report['class']; ?>">
                                        <input type="hidden" name="subjects_marks" value="<?php echo htmlspecialchars(json_encode($report['subjects_marks'])); ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" name="edit_exam_by_class">Edit</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Exam Edit -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'exam_edit') ? 'show active' : ''; ?>" id="exam_edit">
            <h3>Edit Exam Details</h3>
            <?php if (isset($exam_edit_details)): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo $exam_edit_details['student_id']; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="class" class="form-label">Class</label>
                        <input type="text" class="form-control" id="class" name="class" value="<?php echo $exam_edit_details['class']; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="subjects" class="form-label">Subjects and Marks</label>
                        <div id="subjects-container">
                            <?php foreach ($exam_edit_details['subjects_marks'] as $subject => $marks): ?>
                                <div class="d-flex mb-2">
                                    <input type="text" class="form-control me-2" name="subjects[]" value="<?php echo $subject; ?>" readonly>
                                    <input type="text" class="form-control" name="marks[]" value="<?php echo $marks; ?>" required>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" name="update_exam_details">Update Exam</button>
                        <button type="submit" class="btn btn-danger" name="delete_exam_details" onclick="return confirm('Are you sure you want to delete these exam details?')">Delete Exam</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        </div> <!-- End of tab-content -->
</div> <!-- End of container -->

<!-- Bootstrap and Custom Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // Prevent going back to dashboard after logout
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function () {
        window.history.pushState(null, null, window.location.href);
    };

    // Show active tab based on URL parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if(tab) {
            const tabElement = document.querySelector(`a[href="#${tab}"]`);
            if(tabElement) {
                const tabInstance = new bootstrap.Tab(tabElement);
                tabInstance.show();
            }
        }
    });

    // Automatically redirect to dashboard after successful login
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        window.location.href = 'dashboard.php';
    <?php endif; ?>

    // Enable multiple select for teacher subjects
    const teacherSubject = document.getElementById('teacher_subject');
    if(teacherSubject) {
        teacherSubject.addEventListener('click', function(e) {
            e.target.size = e.target.size == 1 ? 5 : 1;
        });
        document.addEventListener('click', function(e) {
            if(e.target != teacherSubject) {
                teacherSubject.size = 1;
            }
        });
    }

    // Alert messages auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    });

    // Confirm before delete
    function confirmDelete() {
        return confirm('Are you sure you want to delete this record?');
    }

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    function fetchClass() {
        const studentId = document.getElementById('student_id').value;
        const classSelect = document.getElementById('class');

        if (studentId) {
            fetch(`get_class.php?student_id=${studentId}`)
                .then(response => response.text())
                .then(classValue => {
                    classSelect.value = classValue;
                });
        }
    }

    function fetchStudentDetails() {
        const studentId = document.getElementById('student_id').value;
        const studentNameInput = document.getElementById('person_name');
        const classSelect = document.getElementById('class');
        const studentPhoto = document.getElementById('student_photo');

        if (studentId) {
            fetch(`get_student_details.php?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        studentNameInput.value = data.name;
                        classSelect.value = data.class;
                        studentPhoto.src = `uploads/${data.photo}`;
                    } else {
                        studentNameInput.value = "Student not found";
                        classSelect.value = "";
                        studentPhoto.src = "";
                    }
                });
        }
    }
    function showOnlyRow(examId) {
        const rows = document.querySelectorAll('#exams_by_class tbody tr');
        rows.forEach(row => {
            if (row.id !== `exam-row-${examId}`) {
                row.style.display = 'none';
            }
        });
    }
    // Function to play audio message with a youthful and pleasant voice
    function playAudioMessage(message, isMale) {
        const audio = new SpeechSynthesisUtterance(message);
        const voices = speechSynthesis.getVoices();
        if (isMale) {
            audio.voice = voices.find(voice => voice.name === 'Google UK English Male');
        } else {
            audio.voice = voices.find(voice => voice.name === 'Google UK English Female');
        }
        audio.pitch = 1.2; // Higher pitch for a youthful voice
        audio.rate = 1.1; // Slightly faster rate for a pleasant tone
        window.speechSynthesis.cancel(); // Stop any ongoing speech
        window.speechSynthesis.speak(audio);
    }

    // Add event listeners to tabs
    document.querySelectorAll('.nav-link').forEach((tab, index) => {
        tab.addEventListener('click', function() {
            const tabName = this.textContent.trim();
            const isMale = index % 2 === 0; // Alternate between male and female voices
            playAudioMessage(`You have selected the ${tabName} tab`, isMale);
        });
    });
</script>
</body>
</html>