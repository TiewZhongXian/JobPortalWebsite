<?php
session_start();
include "db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === "" || $password === "") {
        $error = "Please fill in all fields.";
    } else {

        $stmt = $conn->prepare("SELECT employer_id, password FROM employers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // simple password check (for demo)
            if ($password === $user['password']) {

                $_SESSION['employer_id'] = $user['employer_id'];
                $_SESSION['role'] = "Employer";

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Invalid password.";
            }

        } else {
            $error = "Account not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Employer Login</title>
</head>
<body>

<h2>Employer Login</h2>

<?php if ($error != "") echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
    Email:<br>
    <input type="email" name="email"><br><br>

    Password:<br>
    <input type="password" name="password"><br><br>

    <button type="submit">Login</button>
</form>

</body>
</html>
