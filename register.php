<?php
session_start();

$error_msg = "";
$success_msg = "";
$show_login_button = false;

// Retrieve flash messages from session
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    $show_login_button = true;
    unset($_SESSION['success_msg']);
}

// Retrieve old form values
$old_name = $_SESSION['form_data']['name'] ?? '';
$old_email = $_SESSION['form_data']['email'] ?? '';
$old_phone = $_SESSION['form_data']['phone'] ?? '';

if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $_SESSION['form_data'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ];

    if ($name === "" || $email === "" || $phone === "" || $password === "" || $confirm_password === "") {
        $_SESSION['error_msg'] = "Registration failed: All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_msg'] = "Registration failed: Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $_SESSION['error_msg'] = "Registration failed: Phone number must contain only numbers (10-11 digits).";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error_msg'] = "Registration failed: Passwords do not match.";
    } else {
        $file = __DIR__ . '/users.txt';
        $email_exists = false;
        $phone_exists = false;

        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $parts = explode('|', $line, 4);

                $stored_email = trim($parts[1] ?? '');
                $stored_phone = trim($parts[2] ?? '');

                if ($stored_email === $email) {
                    $email_exists = true;
                }

                if ($stored_phone === $phone) {
                    $phone_exists = true;
                }

                if ($email_exists || $phone_exists) {
                    break;
                }
            }
        }

        if ($email_exists) {
            $_SESSION['error_msg'] = "Registration failed: This email is already registered.";
        } elseif ($phone_exists) {
            $_SESSION['error_msg'] = "Registration failed: This phone number is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $new_user_record = $name . "|" . $email . "|" . $phone . "|" . $hashed_password . PHP_EOL;

            file_put_contents($file, $new_user_record, FILE_APPEND | LOCK_EX);

            $_SESSION['success_msg'] = "Account successfully created! Welcome to the Job Portal, $name.";
            unset($_SESSION['form_data']);
        }
    }

    header("Location: register.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Seeker Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            padding-top: 50px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #004494;
        }
        .error {
            color: #d9534f;
            background-color: #fdf7f7;
            padding: 10px;
            border: 1px solid #d9534f;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #3c763d;
            background-color: #dff0d8;
            padding: 10px;
            border: 1px solid #3c763d;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .login-btn {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .login-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Job Seeker Registration</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <?php if (!$show_login_button): ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($old_name); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="text" name="phone" placeholder="e.g. 0123456789" value="<?php echo htmlspecialchars($old_phone); ?>" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit">Register Account</button>
        </form>
    <?php else: ?>
        <a href="login.html" class="login-btn">Proceed to Login Page</a>
    <?php endif; ?>
</div>

</body>
</html>
