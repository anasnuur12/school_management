<?php
session_start();

$verification_code = "12345"; // This should be generated and sent to the user in a real application

if (!isset($_SESSION['username'])) {
    header("Location: signup.php");
    exit();
}

if (isset($_POST['verify'])) {
    $entered_code = $_POST['verification_code'];

    if ($entered_code === $verification_code) {
        echo "<script>alert('Verification successful!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Invalid verification code.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h2>Verify</h2>
    <form method="POST" action="">
        <div class="mb-3"></div>
            <label for="verification_code" class="form-label">Verification Code</label>
            <input type="text" class="form-control" id="verification_code" name="verification_code" required>
        </div>
        <button type="submit" class="btn btn-primary" name="verify">Verify</button>
    </form>
</div>
</body>
</html>
