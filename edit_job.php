<?php
session_start();
include "db_connect.php";

/* ==============================
   1. AUTHORIZATION CHECK
============================== */
if (!isset($_SESSION['employer_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];

/* ==============================
   2. VALIDATE JOB ID
============================== */
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    die("Invalid job ID.");
}

$job_id = (int) $_GET['job_id'];

$error = "";
$success = "";

/* ==============================
   3. FETCH JOB (ONLY OWNER + ACTIVE)
============================== */
$stmt = $conn->prepare("
    SELECT *
    FROM job_postings
    WHERE job_id = ? AND employer_id = ? AND status = 'Active'
");
$stmt->bind_param("ii", $job_id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Job not found, not active, or you are not authorized.");
}

$job = $result->fetch_assoc();
$stmt->close();

/* ==============================
   4. HANDLE FORM SUBMISSION
============================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $job_title = trim($_POST['job_title']);
    $job_description = trim($_POST['job_description']);
    $salary_min = trim($_POST['salary_min']);
    $salary_max = trim($_POST['salary_max']);
    $location = trim($_POST['location']);
    $job_type = trim($_POST['job_type']);

    // Validation
    if (
        $job_title === "" ||
        $job_description === "" ||
        $salary_min === "" ||
        $salary_max === "" ||
        $location === "" ||
        $job_type === ""
    ) {
        $error = "All fields are required.";
    } elseif (!is_numeric($salary_min) || !is_numeric($salary_max)) {
        $error = "Salary must be numeric.";
    } elseif ($salary_min < 0 || $salary_max < 0) {
        $error = "Salary cannot be negative.";
    } elseif ($salary_min > $salary_max) {
        $error = "Minimum salary cannot exceed maximum salary.";
    } else {

        /* ==============================
           5. UPDATE DATABASE
        ============================== */
        $update = $conn->prepare("
            UPDATE job_postings
            SET job_title = ?, job_description = ?, salary_min = ?, salary_max = ?, location = ?, job_type = ?
            WHERE job_id = ? AND employer_id = ? AND status = 'Active'
        ");

        $update->bind_param(
            "ssddssii",
            $job_title,
            $job_description,
            $salary_min,
            $salary_max,
            $location,
            $job_type,
            $job_id,
            $employer_id
        );

        if ($update->execute()) {
            $success = "Job updated successfully.";

            // Update displayed data
            $job['job_title'] = $job_title;
            $job['job_description'] = $job_description;
            $job['salary_min'] = $salary_min;
            $job['salary_max'] = $salary_max;
            $job['location'] = $location;
            $job['job_type'] = $job_type;

        } else {
            $error = "Failed to update job.";
        }

        $update->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Job Posting</title>
<style>
body { font-family: Arial; background:#f4f6f9; }
.container { width:600px; margin:40px auto; background:white; padding:20px; border-radius:8px; }
label { display:block; margin-top:10px; font-weight:bold; }
input, textarea, select { width:100%; padding:8px; margin-top:5px; }
button { margin-top:15px; padding:10px; width:100%; background:#007bff; color:white; border:none; }
.error { color:red; }
.success { color:green; }
.salary-group { display:flex; gap:10px; }
</style>
</head>

<body>

<div class="container">
<h2>Edit Job Posting</h2>

<?php if ($error != "") echo "<p class='error'>$error</p>"; ?>
<?php if ($success != "") echo "<p class='success'>$success</p>"; ?>

<form method="POST">

<label>Job Title</label>
<input type="text" name="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>">

<label>Job Description</label>
<textarea name="job_description"><?php echo htmlspecialchars($job['job_description']); ?></textarea>

<label>Salary Range</label>
<div class="salary-group">
<input type="text" name="salary_min" value="<?php echo $job['salary_min']; ?>">
<input type="text" name="salary_max" value="<?php echo $job['salary_max']; ?>">
</div>

<label>Location</label>
<input type="text" name="location" value="<?php echo htmlspecialchars($job['location']); ?>">

<label>Job Type</label>
<select name="job_type">
<option value="">--Select--</option>
<option value="Full-time" <?php if($job['job_type']=="Full-time") echo "selected"; ?>>Full-time</option>
<option value="Part-time" <?php if($job['job_type']=="Part-time") echo "selected"; ?>>Part-time</option>
<option value="Contract" <?php if($job['job_type']=="Contract") echo "selected"; ?>>Contract</option>
</select>


<button type="submit">Save Changes</button>

<a href="dashboard.php">
    <button type="button" style="background:gray;">Cancel</button>
</a>
</form>
</div>

</body>
</html>