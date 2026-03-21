<?php
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_msg = "Registration failed: All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Registration failed: Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error_msg = "Registration failed: Phone number must contain only numbers (10-11 digits).";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Registration failed: Passwords do not match.";
    } else {
        $error_msg = "Validation passed! (Database connection coming soon)";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Seeker Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; padding-top: 50px; }
        .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 400px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #004494; }
        .error { color: #d9534f; background-color: #fdf7f7; padding: 10px; border: 1px solid #d9534f; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Job Seeker Registration</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Phone Number *</label>
            <input type="text" name="phone" placeholder="e.g. 0123456789" required>
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
</div>

</body>
</html>
