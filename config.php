<?php
session_start();

// Check if not logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_managementt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define subjects array
$subjects = ['Math', 'Science', 'English', 'History', 'Geography', 'Art', 'PE', 'Music', 'ICT', 'Language'];

// Set default tab or get from session
if (isset($_GET['tab'])) {
    $_SESSION['active_tab'] = $_GET['tab'];
} elseif (!isset($_SESSION['active_tab'])) {
    $_SESSION['active_tab'] = 'register_student';
}

// Add logout functionality
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Register Student
if (isset($_POST['register_student'])) {
    $name = $_POST['student_name'];
    $phone = $_POST['student_phone'];
    $degma = $_POST['student_degma'];
    $class = $_POST['student_class'];

    $stmt = $conn->prepare("INSERT INTO students (name, phone, degmada, class) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $phone, $degma, $class);
    if ($stmt->execute()) {
        echo "<script>alert('Student registered successfully!');</script>";
    } else {
        echo "<script>alert('Error registering student.');</script>";
    }
    $_SESSION['active_tab'] = 'register_student';
}

// Register Teacher
if (isset($_POST['register_teacher'])) {
    $name = $_POST['teacher_name'];
    $phone = $_POST['teacher_phone'];
    $degma = $_POST['teacher_degma'];
    $subjects = implode(',', $_POST['teacher_subject']);

    $stmt = $conn->prepare("INSERT INTO teachers (name, phone, degmada, subjects) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $phone, $degma, $subjects);
    if ($stmt->execute()) {
        echo "<script>alert('Teacher registered successfully!');</script>";
    } else {
        echo "<script>alert('Error registering teacher.');</script>";
    }
    $_SESSION['active_tab'] = 'register_teacher';
}

// Update data
if (isset($_POST['update_data'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $degma = $_POST['degma'];
    $additional = is_array($_POST['additional']) ? implode(',', $_POST['additional']) : $_POST['additional'];

    $table = ($type == "students") ? "students" : "teachers";
    $idField = ($type == "students") ? "student_id" : "teacher_id";
    $additionalField = ($type == "students") ? "class" : "subjects";

    $stmt = $conn->prepare("UPDATE $table SET name=?, phone=?, degmada=?, $additionalField=? WHERE $idField=?");
    $stmt->bind_param("sssss", $name, $phone, $degma, $additional, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Record updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating record.');</script>";
    }
    $_SESSION['active_tab'] = 'manage_details';
}

// Delete data
if (isset($_POST['delete_data'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];

    $table = ($type == "students") ? "students" : "teachers";
    $idField = ($type == "students") ? "student_id" : "teacher_id";

    // First delete related exam records if it's a student
    if ($type == "students") {
        $stmt = $conn->prepare("DELETE FROM exams WHERE student_id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
    }

    $stmt = $conn->prepare("DELETE FROM $table WHERE $idField = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting record.');</script>";
    }
    $_SESSION['active_tab'] = 'manage_details';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        .nav-link.active {
            background-color: #007bff !important;
            color: white !important;
        }
        .nav-item {
            cursor: pointer;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alert {
            margin-top: 20px;
        }
        .btn-group {
            gap: 10px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .multiple-select {
            height: auto !important;
            min-height: 100px;
        }
    </style>
</head>
<body>
<div class="container my-5">
    <!-- Header with Welcome and Logout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>School Management Dashboard</h1>
            <h5 class="text-muted">Welcome, <?php echo $_SESSION['user_name']; ?>!</h5>
        </div>
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
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'exams') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#exams"
               onclick="window.location.href='?tab=exams'">Exams</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($_SESSION['active_tab'] == 'results') ? 'active' : ''; ?>" 
               data-bs-toggle="tab" href="#results"
               onclick="window.location.href='?tab=results'">Results</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3">
                <!-- Register Student -->
                <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'register_student') ? 'show active' : ''; ?>" id="register_student">
            <div class="form-container">
                <div class="card">
                    <div class="card-header">
                        <h3>Register Student</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="student_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="student_phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="student_phone" name="student_phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="student_degma" class="form-label">Degmada</label>
                                <input type="text" class="form-control" id="student_degma" name="student_degma" required>
                            </div>
                            <div class="mb-3">
                                <label for="student_class" class="form-label">Class</label>
                                <select class="form-select" id="student_class" name="student_class" required>
                                    <option value="">Select Class</option>
                                    <?php
                                    $classes = ['1', '2', '3', '4', '5', '6', '7', '8', 'F1', 'F2', 'F3', 'F4'];
                                    foreach ($classes as $class) {
                                        echo "<option value='$class'>$class</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" name="register_student">Register Student</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Register Teacher -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'register_teacher') ? 'show active' : ''; ?>" id="register_teacher">
            <div class="form-container">
                <div class="card">
                    <div class="card-header">
                        <h3>Register Teacher</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="teacher_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="teacher_phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="teacher_phone" name="teacher_phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="teacher_degma" class="form-label">Degmada</label>
                                <input type="text" class="form-control" id="teacher_degma" name="teacher_degma" required>
                            </div>
                            <div class="mb-3">
                                <label for="teacher_subject" class="form-label">Subjects</label>
                                <select class="form-select multiple-select" id="teacher_subject" name="teacher_subject[]" multiple required>
                                    <?php
                                    foreach ($subjects as $subject) {
                                        echo "<option value='$subject'>$subject</option>";
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple subjects</small>
                            </div>
                            <button type="submit" class="btn btn-success" name="register_teacher">Register Teacher</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Students -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'view_students') ? 'show active' : ''; ?>" id="view_students">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Registered Students</h3>
                    <input type="text" class="form-control w-25" id="studentSearch" placeholder="Search students..." onkeyup="searchStudents()">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php
                        $result = $conn->query("SELECT * FROM students ORDER BY student_id");
                        if ($result->num_rows > 0) {
                            echo "<table class='table table-bordered table-hover' id='studentsTable'>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Degmada</th>
                                            <th>Class</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>";
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['student_id']}</td>
                                        <td>{$row['name']}</td>
                                        <td>{$row['phone']}</td>
                                        <td>{$row['degmada']}</td>
                                        <td>{$row['class']}</td>
                                        <td>
                                            <button class='btn btn-sm btn-primary' onclick='editStudent({$row['student_id']})'>Edit</button>
                                            <button class='btn btn-sm btn-danger' onclick='deleteStudent({$row['student_id']})'>Delete</button>
                                        </td>
                                    </tr>";
                            }
                            echo "</tbody></table>";
                        } else {
                            echo "<p class='text-center'>No students registered yet.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Teachers -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'view_teachers') ? 'show active' : ''; ?>" id="view_teachers">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Registered Teachers</h3>
                    <input type="text" class="form-control w-25" id="teacherSearch" placeholder="Search teachers..." onkeyup="searchTeachers()">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php
                        $result = $conn->query("SELECT * FROM teachers ORDER BY teacher_id");
                        if ($result->num_rows > 0) {
                            echo "<table class='table table-bordered table-hover' id='teachersTable'>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Degmada</th>
                                            <th>Subjects</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>";
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['teacher_id']}</td>
                                        <td>{$row['name']}</td>
                                        <td>{$row['phone']}</td>
                                        <td>{$row['degmada']}</td>
                                        <td>{$row['subjects']}</td>
                                        <td>
                                            <button class='btn btn-sm btn-primary' onclick='editTeacher({$row['teacher_id']})'>Edit</button>
                                            <button class='btn btn-sm btn-danger' onclick='deleteTeacher({$row['teacher_id']})'>Delete</button>
                                        </td>
                                    </tr>";
                            }
                            echo "</tbody></table>";
                        } else {
                            echo "<p class='text-center'>No teachers registered yet.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
                <!-- Fetch & Manage Details -->
                <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'manage_details') ? 'show active' : ''; ?>" id="manage_details">
            <div class="form-container">
                <div class="card">
                    <div class="card-header">
                        <h3>Fetch & Manage Details</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="mb-4">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type" required onchange="updateIdLabel()">
                                    <option value="">Select Type</option>
                                    <option value="students">Student</option>
                                    <option value="teachers">Teacher</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id" class="form-label" id="idLabel">Enter ID</label>
                                <input type="text" class="form-control" id="id" name="id" required>
                            </div>
                            <button type="submit" class="btn btn-primary" name="fetch_data">Fetch Details</button>
                        </form>

                        <?php include 'fetch_details.php'; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Attendance Tab -->
<div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'attendance') ? 'show active' : ''; ?>" id="attendance">
    <div class="form-container">
        <div class="card">
            <div class="card-header">
                <h3>Mark Attendance</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="mb-4">
                    <div class="mb-3">
                        <label for="attendance_type" class="form-label">Type</label>
                        <select class="form-select" id="attendance_type" name="attendance_type" required>
                            <option value="">Select Type</option>
                            <option value="students">Student</option>
                            <option value="teachers">Teacher</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="attendance_id" class="form-label">ID</label>
                        <input type="text" class="form-control" id="attendance_id" name="attendance_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="attendance_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="attendance_message" class="form-label">Message/Notes</label>
                        <textarea class="form-control" id="attendance_message" name="attendance_message" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="mark_attendance">Mark Attendance</button>
                </form>

                <!-- View Attendance Records -->
                <div class="mt-4">
                    <h4>Attendance Records</h4>
                    <form method="POST" action="" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" name="view_type" required>
                                    <option value="">Select Type</option>
                                    <option value="students">Students</option>
                                    <option value="teachers">Teachers</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" name="view_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary" name="view_attendance">View Records</button>
                            </div>
                        </div>
                    </form>

                    <?php
                    if (isset($_POST['view_attendance'])) {
                        $view_type = $_POST['view_type'];
                        $view_date = $_POST['view_date'];
                        $table = ($view_type == "students") ? "student_attendance" : "teacher_attendance";
                        $id_type = ($view_type == "students") ? "student_id" : "teacher_id";

                        $query = "SELECT a.*, COALESCE(s.name, t.name) as name 
                                FROM $table a 
                                LEFT JOIN students s ON (a.id = s.student_id AND '$view_type' = 'students')
                                LEFT JOIN teachers t ON (a.id = t.teacher_id AND '$view_type' = 'teachers')
                                WHERE DATE(a.date) = ?
                                ORDER BY a.date DESC";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $view_date);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            echo "<div class='table-responsive'>
                                    <table class='table table-bordered table-hover'>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Date/Time</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>";
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['id']}</td>
                                        <td>{$row['name']}</td>
                                        <td>{$row['date']}</td>
                                        <td>{$row['message']}</td>
                                    </tr>";
                            }
                            echo "</tbody></table></div>";
                        } else {
                            echo "<div class='alert alert-info'>No attendance records found for this date.</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>


        <!-- Exams -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'exams') ? 'show active' : ''; ?>" id="exams">
            <div class="form-container">
                <div class="card">
                    <div class="card-header">
                        <h3>Enter Exam Marks</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="mb-4">
                            <div class="mb-3">
                                <label for="exam_student_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="exam_student_id" name="exam_student_id" required>
                            </div>
                            <div class="mb-3">
                                <label for="exam_student_name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="exam_student_name" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="exam_subject" class="form-label">Subject</label>
                                <select class="form-select" id="exam_subject" name="exam_subject" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    foreach ($subjects as $subject) {
                                        echo "<option value='$subject'>$subject</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="exam_marks" class="form-label">Marks</label>
                                <input type="number" class="form-control" id="exam_marks" name="exam_marks" min="0" max="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="exam_date" class="form-label">Exam Date</label>
                                <input type="date" class="form-control" id="exam_date" name="exam_date" required>
                            </div>
                            <button type="submit" class="btn btn-primary" name="submit_exam">Submit Marks</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'results') ? 'show active' : ''; ?>" id="results">
            <div class="card">
                <div class="card-header">
                    <h3>Student Results</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="result_student_id" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="result_student_id" name="result_student_id">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="result_subject" class="form-label">Subject</label>
                                    <select class="form-select" id="result_subject" name="result_subject">
                                        <option value="">All Subjects</option>
                                        <?php
                                        foreach ($subjects as $subject) {
                                            echo "<option value='$subject'>$subject</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100" name="search_results">Search</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php include 'results_display.php'; ?>
                </div>
            </div>
        </div>

        <!-- Reports -->
        <div class="tab-pane fade <?php echo ($_SESSION['active_tab'] == 'reports') ? 'show active' : ''; ?>" id="reports">
            <div class="card">
                <div class="card-header">
                    <h3>Generate Reports</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Student Statistics -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Student Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Total students
                                    $result = $conn->query("SELECT COUNT(*) as total FROM students");
                                    $total_students = $result->fetch_assoc()['total'];

                                    // Students by class
                                    $result = $conn->query("SELECT class, COUNT(*) as count FROM students GROUP BY class");
                                    echo "<p>Total Students: $total_students</p>";
                                    echo "<h6>Students by Class:</h6>";
                                    echo "<ul>";
                                    while($row = $result->fetch_assoc()) {
                                        echo "<li>Class {$row['class']}: {$row['count']} students</li>";
                                    }
                                    echo "</ul>";
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Exam Statistics -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Exam Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Average marks by subject
                                    $result = $conn->query("SELECT subject, AVG(marks) as avg_marks FROM exams GROUP BY subject");
                                    echo "<h6>Average Marks by Subject:</h6>";
                                    echo "<ul>";
                                    while($row = $result->fetch_assoc()) {
                                        $avg = round($row['avg_marks'], 2);
                                        echo "<li>{$row['subject']}: $avg%</li>";
                                    }
                                    echo "</ul>";

                                    // Performance distribution
                                    $result = $conn->query("
                                        SELECT 
                                            CASE 
                                                WHEN marks >= 90 THEN 'A+'
                                                WHEN marks >= 80 THEN 'A'
                                                WHEN marks >= 70 THEN 'B'
                                                WHEN marks >= 60 THEN 'C'
                                                WHEN marks >= 50 THEN 'D'
                                                ELSE 'F'
                                            END as grade,
                                            COUNT(*) as count
                                        FROM exams
                                        GROUP BY grade
                                        ORDER BY marks DESC
                                    ");
                                    echo "<h6>Grade Distribution:</h6>";
                                    echo "<ul>";
                                    while($row = $result->fetch_assoc()) {
                                        echo "<li>Grade {$row['grade']}: {$row['count']} students</li>";
                                    }
                                    echo "</ul>";
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher Statistics -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Teacher Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Total teachers
                                    $result = $conn->query("SELECT COUNT(*) as total FROM teachers");
                                    $total_teachers = $result->fetch_assoc()['total'];

                                    // Teachers by subject
                                    echo "<p>Total Teachers: $total_teachers</p>";
                                    echo "<h6>Teachers by Subject Area:</h6>";
                                    echo "<ul>";
                                    foreach ($subjects as $subject) {
                                        $result = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE subjects LIKE '%$subject%'");
                                        $count = $result->fetch_assoc()['count'];
                                        echo "<li>$subject: $count teachers</li>";
                                    }
                                    echo "</ul>";
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Download Reports -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Download Reports</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="generate_report.php">
                                        <div class="mb-3">
                                            <label for="report_type" class="form-label">Report Type</label>
                                            <select class="form-select" id="report_type" name="report_type" required>
                                                <option value="">Select Report Type</option>
                                                <option value="student_list">Student List</option>
                                                <option value="teacher_list">Teacher List</option>
                                                <option value="exam_results">Exam Results</option>
                                                <option value="class_performance">Class Performance</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-success">Generate Report</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End of tab-content -->
</div> <!-- End of container -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
// ... (JavaScript functions from previous parts remain the same)

// Add report generation function
function generateReport(type) {
    window.location.href = `generate_report.php?type=${type}`;
}
</script>
</body>
</html>
    </div>