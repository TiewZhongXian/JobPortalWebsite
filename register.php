<?php
session_start();

$error_msg = "";
$success_msg = "";
$show_login_button = false;

// Retrieve messages from session (Post-Redirect-Get pattern)
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    $show_login_button = true;
    unset($_SESSION['success_msg']);
}

$old_name = $_SESSION['form_data']['name'] ?? '';
$old_email = $_SESSION['form_data']['email'] ?? '';
$old_phone = $_SESSION['form_data']['phone'] ?? '';

if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_msg'] = "Registration failed: All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_msg'] = "Registration failed: Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $_SESSION['error_msg'] = "Registration failed: Phone number must contain only numbers (10-11 digits).";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error_msg'] = "Registration failed: Passwords do not match.";
    } else {
        $file = 'users.txt';
        $email_exists = false;
        $phone_exists = false;

        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $parts = explode('|', $line, 4);

                $stored_name = trim($parts[0] ?? '');
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
