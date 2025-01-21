<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'school_managementt');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT username, password FROM userss WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username']; // Store the user's name in the session
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Invalid username or password.'); window.location.href='login.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href='login.php';</script>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); /* Increased shadow for better visibility */
            width: 350px; /* Increased width for better spacing */
            border: 2px solid #007bff; /* Added border */
        }
        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px; /* Added margin for spacing */
        }
        label {
            font-weight: bold;
            color: #007bff;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px; /* Increased padding */
            margin: 10px 0;
            border: 1px solid #007bff;
            border-radius: 5px;
            box-sizing: border-box; /* Ensure padding doesn't affect total width */
        }
        button {
            width: 100%;
            padding: 12px; /* Increased padding */
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 20px; /* Rounded borders */
            cursor: pointer;
            transition: background-color 0.3s ease; /* Smooth transition */
            font-size: 16px; /* Increased font size */
        }
        button:hover {
            background-color: #0056b3;
        }
        .form-separator {
            margin: 20px 0; /* Added margin for spacing */
            text-align: center;
            color: #007bff;
        }
        .form-spacing {
            margin-top: 20px; /* Added margin for spacing */
        }
    </style>
    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }

        // Disable back and forward navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</head>
<body>
    <div class="login-container">
        <h2>Login Form</h2>
        <form method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" required><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br>
            <input type="checkbox" onclick="togglePassword()"> Show Password<br>
            <button type="submit" name="login" class="form-spacing">Login</button>
        </form>
        <div class="form-separator">or</div> <!-- Separator between forms -->
        <form action="signup.php" method="get">
            <button type="submit" class="form-spacing">Signup</button>
        </form>
    </div>
</body>
</html>
