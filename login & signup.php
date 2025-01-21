<?php
session_start();
include 'db_connection.php'; // Include your database connection file

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_name'] = $user['username'];
        header("Location: dashboard.php");
    } else {
        echo "<script>alert('Invalid username or password.');</script>";
    }
}

if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        echo "<script>alert('Signup successful. Please login.');</script>";
    } else {
        echo "<script>alert('Signup failed. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        form {
            margin: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            max-width: 400px;
        }
        label {
            font-size: 18px;
            display: block;
            margin-bottom: 8px;
        }
        input[type="text"], input[type="password"] {
            font-size: 18px;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            font-size: 18px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
    <script>
        function toggleForm(form) {
            document.getElementById('loginForm').style.display = form === 'login' ? 'block' : 'none';
            document.getElementById('signupForm').style.display = form === 'signup' ? 'block' : 'none';
            document.getElementById('currentForm').value = form;
            if (form === 'signup') {
                document.getElementById('signupForm').scrollIntoView({ behavior: 'smooth' });
            } else {
                document.getElementById('loginForm').scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</head>
<body onload="toggleForm('<?php echo isset($_POST['signup']) ? 'signup' : 'login'; ?>')">
<div class="container my-5">
    <div class="d-flex justify-content-center mb-4">
        <button class="btn btn-secondary me-2" onclick="toggleForm('login')">Login</button>
        <button class="btn btn-secondary" onclick="toggleForm('signup')">Signup</button>
    </div>
    <div id="loginForm">
        <h2>Login</h2>
        <form method="POST" action="">
            <input type="hidden" id="currentForm" name="currentForm" value="login">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" name="login">Login</button>
        </form>
    </div>
    <div id="signupForm" style="display:none;">
        <h2>Signup</h2>
        <form method="POST" action="">
            <input type="hidden" id="currentForm" name="currentForm" value="signup">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" name="signup">Signup</button>
        </form>
    </div>
</div>
</body>
</html>
