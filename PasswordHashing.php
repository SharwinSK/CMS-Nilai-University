<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hasher</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
        }
        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 10px;
        }
        .password-container {
            display: flex;
            align-items: center;
        }
        input[type="password"], input[type="text"] {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
        }
        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            background: #e9ecef;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .toggle-btn {
            margin-left: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            background-color: #f4f4f9;
        }
        .toggle-btn:hover {
            background-color: #ddd;
        }
    </style>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById("password");
            const toggleButton = document.getElementById("togglePassword");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleButton.textContent = "Hide Password";
            } else {
                passwordField.type = "password";
                toggleButton.textContent = "Show Password";
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Password Hasher</h2>
        <form method="POST" action="">
            <label for="password">Enter Password:</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required>
                <button type="button" class="toggle-btn" id="togglePassword" onclick="togglePasswordVisibility()">Show Password</button>
            </div>
            <button type="submit">Generate Hashed Password</button>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = $_POST['password'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            echo "<div class='result'><strong>Hashed Password:</strong><br> $hashedPassword</div>";
        }
        ?>
    </div>
</body>
</html>
