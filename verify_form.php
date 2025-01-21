<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
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
        .verify-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            width: 400px; /* Increased width */
            border: 2px solid #007bff;
            animation: borderEffect 2s infinite; /* Added border effect */
        }
        @keyframes borderEffect {
            0% { border-color: #007bff; }
            50% { border-color: #0056b3; }
            100% { border-color: #007bff; }
        }
        h2 {
            text-align: center;
            color: #007bff;
        }
        label {
            font-weight: bold;
            color: #007bff;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #007bff;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
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
        let timeLeft = 30;
        const timerElement = document.createElement('div');
        timerElement.style.textAlign = 'center';
        timerElement.style.marginTop = '10px';
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.verify-container').appendChild(timerElement);
            const countdown = setInterval(function() {
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    alert('Time is up! Typing is now disabled.');
                    document.querySelector('input[name="verification_code"]').disabled = true;
                } else {
                    timerElement.textContent = `Time left: ${timeLeft} seconds`;
                    timeLeft--;
                }
            }, 1000);
            document.querySelector('input[name="verification_code"]').onpaste = function(e) {
                e.preventDefault();
                alert('Copy-paste is not allowed!');
            };
        });

        // Disable back and forward navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</head>
<body>
    <div class="verify-container">
        <h2>Verify Code</h2>
        <form method="post" action="signup.php">
            <label for="verification_code">Verification Code:</label>
            <input type="text" name="verification_code" required><br>
            <button type="submit" name="verify">Verify</button>
        </form>
    </div>
</body>
</html>
