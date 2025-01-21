<?php
if (isset($_POST['search_results'])) {
    $student_id = !empty($_POST['result_student_id']) ? $_POST['result_student_id'] : null;
    $subject = !empty($_POST['result_subject']) ? $_POST['result_subject'] : null;
    
    // Build the query based on search criteria
    $query = "SELECT e.*, s.name as student_name 
             FROM exams e 
             JOIN students s ON e.student_id = s.student_id 
             WHERE 1=1";
    
    $params = array();
    $types = "";
    
    if ($student_id) {
        $query .= " AND e.student_id = ?";
        $params[] = $student_id;
        $types .= "i";
    }
    
    if ($subject) {
        $query .= " AND e.subject = ?";
        $params[] = $subject;
        $types .= "s";
    }
    
    $query .= " ORDER BY e.student_id, e.subject, e.exam_date DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Group results by student
        $students = array();
        while ($row = $result->fetch_assoc()) {
            $student_id = $row['student_id'];
            if (!isset($students[$student_id])) {
                $students[$student_id] = array(
                    'name' => $row['student_name'],
                    'subjects' => array()
                );
            }
            $students[$student_id]['subjects'][] = $row;
        }

        // Display results for each student
        foreach ($students as $student_id => $data) {
            echo "<div class='card mb-4'>
                    <div class='card-header'>
                        <h5>Student: {$data['name']} (ID: {$student_id})</h5>
                    </div>
                    <div class='card-body'>
                        <div class='table-responsive'>
                            <table class='table table-bordered'>
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Marks</th>
                                        <th>Exam Date</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>";
            
            $total_marks = 0;
            $subject_count = 0;
            
            foreach ($data['subjects'] as $exam) {
                // Calculate grade
                $grade = '';
                if ($exam['marks'] >= 90) $grade = 'A+';
                elseif ($exam['marks'] >= 80) $grade = 'A';
                elseif ($exam['marks'] >= 70) $grade = 'B';
                elseif ($exam['marks'] >= 60) $grade = 'C';
                elseif ($exam['marks'] >= 50) $grade = 'D';
                else $grade = 'F';

                echo "<tr>
                        <td>{$exam['subject']}</td>
                        <td>{$exam['marks']}</td>
                        <td>{$exam['exam_date']}</td>
                        <td>{$grade}</td>
                      </tr>";
                
                $total_marks += $exam['marks'];
                $subject_count++;
            }
            
            $average = $subject_count > 0 ? round($total_marks / $subject_count, 2) : 0;
            $overall_grade = '';
            if ($average >= 90) $overall_grade = 'A+';
            elseif ($average >= 80) $overall_grade = 'A';
            elseif ($average >= 70) $overall_grade = 'B';
            elseif ($average >= 60) $overall_grade = 'C';
            elseif ($average >= 50) $overall_grade = 'D';
            else $overall_grade = 'F';

            echo "</tbody>
                  <tfoot>
                    <tr class='table-info'>
                        <td><strong>Total</strong></td>
                        <td colspan='3'><strong>{$total_marks}</strong></td>
                    </tr>
                    <tr class='table-success'>
                        <td><strong>Average</strong></td>
                        <td><strong>{$average}</strong></td>
                        <td colspan='2'><strong>Overall Grade: {$overall_grade}</strong></td>
                    </tr>
                  </tfoot>
                </table>
            </div>
        </div>
    </div>";
        }
    } else {
        echo "<div class='alert alert-info'>No results found.</div>";
    }
}
?>