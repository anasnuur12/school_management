<?php
session_start();

// Function to generate a random 5-digit code
function generateCode() {
    return rand(10000, 99999);
}

// Database connection (update these credentials as needed)
$conn = new mysqli('localhost', 'root', '', 'school_managementt');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Handle Signup Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Store form data in session to repopulate if needed
    $_SESSION['form_data'] = [
        'name' => $name,
        'username' => $username,
        'password' => $password
    ];

    // Validate name (only text and at least 3 characters)
    if (!preg_match("/^[a-zA-Z]{3,}$/", $name)) {
        echo "<script>alert('Name should only contain letters and be at least 3 characters long.'); window.location.href='signup.php';</script>";
        exit;
    }

    // Validate username (only text and at least 3 characters)
    if (!preg_match("/^[a-zA-Z]{3,}$/", $username)) {
        echo "<script>alert('Username should only contain letters and be at least 3 characters long.'); window.location.href='signup.php';</script>";
        exit;
    }

    // Validate password (only numbers and at least 3 characters)
    if (!preg_match("/^[0-9]{3,}$/", $password)) {
        echo "<script>alert('Password should only contain numbers and be at least 3 characters long.'); window.location.href='signup.php';</script>";
        exit;
    }

    $password = password_hash($password, PASSWORD_BCRYPT); // Hash the password for security

    // Check if the username already exists
    $stmt = $conn->prepare("SELECT * FROM userss WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Username already exists. Please choose a different username.'); window.location.href='signup.php';</script>";
    } else {
        // Generate a 5-digit verification code
        $verification_code = generateCode();

        // Store data in session temporarily
        $_SESSION['signup_data'] = [
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'verification_code' => $verification_code
        ];

        // Add a 1-second delay before displaying the verification code
        sleep(2);

        // Display the verification form
        echo "<div style='text-align: center; margin-bottom: 10px;'>
                <strong>Fadlan hubi oo qor lambarkan:</strong><br>
                <span class='verification-code'>$verification_code</span>
              </div>";
        include 'verify_form.php'; // Load the verification form
        exit;
    }
    $stmt->close();
}

// Step 2: Handle Verification Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    // Add a 2-second delay before processing the verification code
    sleep(2);

    $user_code = $_POST['verification_code'];

    // Retrieve stored data from session
    $signup_data = $_SESSION['signup_data'] ?? null;

    if ($signup_data && $user_code == $signup_data['verification_code']) {
        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO userss (name, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $signup_data['name'], $signup_data['username'], $signup_data['password']);

        if ($stmt->execute()) {
            // Clear session data
            unset($_SESSION['signup_data']);
            unset($_SESSION['form_data']);
            // Redirect to dashboard.php
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Signup wuu guuldareystay: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "<div style='text-align: center; font-size: 120px; color: green; font-weight: bold;'>Verification code waa khalad. Fadlan dib isku day.</div>";
        echo "<div style='text-align: center;'><button onclick=\"window.location.href='signup.php';\">Back</button></div>";
    }

    // Clear session data
    unset($_SESSION['signup_data']);
    exit;
}

// Check if the form has been submitted to avoid changes on page refresh
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    exit;
}

// Generate a new verification code on page load if not already set
if (isset($_SESSION['signup_data']) && !isset($_SESSION['signup_data']['verification_code_generated'])) {
    $_SESSION['signup_data']['verification_code'] = generateCode();
    $_SESSION['signup_data']['verification_code_generated'] = true; // Mark code as generated
    $verification_code = $_SESSION['signup_data']['verification_code'];
    echo "<div style='text-align: center; margin-bottom: 10px;'>
            <strong>Fadlan hubi oo qor lambarkan:</strong><br>
            <span class='verification-code'>$verification_code</span>
          </div>";
    include 'verify_form.php'; // Load the verification form
    exit;
}

// Clear form data from session on page refresh or exit
unset($_SESSION['form_data']);

$conn->close();
?>

<!-- Signup Form (Step 1) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
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
        .signup-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); /* Increased shadow for better visibility */
            width: 400px; /* Increased width for better spacing */
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
        .show-password {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }
        .show-password input {
            margin-right: 5px;
        }
        .form-spacing {
            margin-top: 20px; /* Added margin for spacing */
        }
        .verification-code {
            font-size: 200px; /* Increased font size */
            color: blue; /* Changed color to blue */
            font-weight: bold;
            text-align: center;
            transition: none; /* Ensure size doesn't change */
        }
    </style>
    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementsByName('password')[0];
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        }
    </script>
</head>
<body>
    <div class="signup-container">
        <h2>Signup Form</h2>
        <form method="post">
            <label for="name">Name:</label>
            <input type="text" name="name" value="<?php echo $_SESSION['form_data']['name'] ?? ''; ?>" required><br>
            <label for="username">Username:</label>
            <input type="text" name="username" value="<?php echo $_SESSION['form_data']['username'] ?? ''; ?>" required><br>
            <label for="password">Password:</label>
            <input type="password" name="password" value="<?php echo $_SESSION['form_data']['password'] ?? ''; ?>" required><br>
            <div class="show-password">
                <input type="checkbox" onclick="togglePasswordVisibility()"> Show Password
            </div>
            <button type="submit" name="signup" class="form-spacing">Signup</button>
        </form>
        <div class="form-separator">or</div> <!-- Separator between forms -->
        <form action="login.php" method="get">
            <button type="submit" class="form-spacing">Login</button>
        </form>
    </div>
</body>
</html>