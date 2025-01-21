<?php
if (isset($_POST['fetch_data'])) {
    if (empty($_POST['type'])) {
        echo "<div class='alert alert-danger'>Please select a type first!</div>";
    } else {
        $id = $_POST['id'];
        $type = $_POST['type'];

        $table = ($type == "students") ? "students" : "teachers";
        $idField = ($type == "students") ? "student_id" : "teacher_id";

        $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            echo "<form method='POST' action='' class='mt-4'>
                    <div class='mb-3'>
                        <label class='form-label'>Name</label>
                        <input type='text' class='form-control' name='name' value='{$data['name']}' required>
                    </div>
                    <div class='mb-3'>
                        <label class='form-label'>Phone</label>
                        <input type='text' class='form-control' name='phone' value='{$data['phone']}' required>
                    </div>
                    <div class='mb-3'>
                        <label class='form-label'>Degmada</label>
                        <input type='text' class='form-control' name='degma' value='{$data['degmada']}' required>
                    </div>";

            if ($type == "students") {
                echo "<div class='mb-3'>
                        <label class='form-label'>Class</label>
                        <select class='form-select' name='additional' required>";
                $classes = ['1', '2', '3', '4', '5', '6', '7', '8', 'F1', 'F2', 'F3', 'F4'];
                foreach ($classes as $class) {
                    $selected = ($data['class'] == $class) ? 'selected' : '';
                    echo "<option value='$class' $selected>$class</option>";
                }
                echo "</select></div>";
            } else {
                $selectedSubjects = explode(',', $data['subjects']);
                echo "<div class='mb-3'>
                        <label class='form-label'>Subjects</label>
                        <select class='form-select multiple-select' name='additional[]' multiple required>";
                foreach ($subjects as $subject) {
                    $selected = in_array($subject, $selectedSubjects) ? 'selected' : '';
                    echo "<option value='$subject' $selected>$subject</option>";
                }
                echo "</select>
                      <small class='form-text text-muted'>Hold Ctrl (Windows) or Command (Mac) to select multiple subjects</small>
                      </div>";
            }

            echo "<input type='hidden' name='id' value='$id'>
                  <input type='hidden' name='type' value='$type'>
                  <div class='btn-group'>
                      <button type='submit' class='btn btn-primary' name='update_data'>Update</button>
                      <button type='submit' class='btn btn-danger' name='delete_data' onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</button>
                  </div>
                </form>";
        } else {
            echo "<div class='alert alert-danger mt-3'>No record found!</div>";
        }
    }
}
?>