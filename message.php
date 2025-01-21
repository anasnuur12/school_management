<?php
session_start();
include('config.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];

    $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $_SESSION['user_id'], $receiver_id, $message);
    if ($stmt->execute()) {
        echo "Message sent successfully.";
    } else {
        echo "Error sending message.";
    }
}

// Fetch all users (could be parents or teachers)
$sql = "SELECT * FROM users WHERE role = 'student' OR role = 'teacher'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message</title>
</head>
<body>
    <h2>Send Message</h2>
    <form action="message.php" method="post">
        <label for="receiver_id">Select Receiver:</label>
        <select name="receiver_id" required>
            <?php while ($row = $result->fetch_assoc()): ?>
                <option value="<?= $row['id']; ?>"><?= $row['username']; ?></option>
            <?php endwhile; ?>
        </select><br>

        <label for="message">Message:</label>
        <textarea name="message" rows="4" required></textarea><br>

        <button type="submit">Send Message</button>
    </form>
</body>
</html>
