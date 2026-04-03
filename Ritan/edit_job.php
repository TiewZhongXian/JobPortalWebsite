<?php
session_start();
include "db_connect.php";

/* ==============================
   1. AUTHORIZATION CHECK
   Requirement 9:
   Restrict access to authorized employers only
============================== */
if (!isset($_SESSION['employer_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = (int) $_SESSION['employer_id'];
$error = "";

/* ==============================
   2. PREDEFINED OPTIONS
   Requirement 11 & 12:
   Restrict job type and location to predefined options
============================== */
$allowedJobTypes = ['Full-time', 'Part-time', 'Contract','Internship'];
$allowedLocations = ['Kuala Lumpur', 'Selangor', 'Penang', 'Johor Bahru', 'Perak','Melaka', 'Negeri Sembilan', 'Pahang', 'Terengganu', 'Kelantan','Sabah', 'Sarawak'];

/* ==============================
   3. VALIDATE JOB ID
============================== */
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    die("Invalid job ID.");
}

$job_id = (int) $_GET['job_id'];

/* ==============================
   4. HANDLE CANCEL ACTION
   Requirement 10:
   Allow cancel and return to dashboard
============================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel'])) {
    header("Location: dashboard.php");
    exit();
}

/* ==============================
   5. FETCH JOB
   Requirement 1 + 9:
   Allow edit only for active job posting
   owned by the logged-in employer
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
   6. HANDLE FORM SUBMISSION
============================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save'])) {

    $job_title = trim($_POST['job_title'] ?? '');
    $job_description = trim($_POST['job_description'] ?? '');
    $job_requirement = trim($_POST['job_requirement'] ?? '');
    $salary_min = trim($_POST['salary_min'] ?? '');
    $salary_max = trim($_POST['salary_max'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');

    /* Requirement 2:
       Validate required fields are not empty */
    if (
        $job_title === '' ||
        $job_description === '' ||
        $job_requirement === '' ||
        $salary_min === '' ||
        $salary_max === '' ||
        $location === '' ||
        $job_type === ''
    ) {
        $error = "All fields are required.";
    }

    /* Requirement 3:
       Validate salary inputs are numerical */
    elseif (!is_numeric($salary_min) || !is_numeric($salary_max)) {
        $error = "Salary minimum and salary maximum must be numeric values.";
    }

    /* Convert after numeric validation */
    else {
        $salary_min = (float)$salary_min;
        $salary_max = (float)$salary_max;

        /* Requirement 4:
           Minimum salary must be at least RM500 */
        if ($salary_min < 500) {
            $error = "Minimum salary must be at least RM500.";
        }   

        /* Requirement 6:
           Minimum salary must not exceed maximum salary */
        elseif ($salary_min > $salary_max) {
            $error = "Minimum salary cannot exceed maximum salary.";
        }

        /* Requirement 11:
           Restrict job type to predefined options */
        elseif (!in_array($job_type, $allowedJobTypes, true)) {
            $error = "Invalid job type selected.";
        }

        /* Requirement 12:
           Restrict location to predefined options */
        elseif (!in_array($location, $allowedLocations, true)) {
            $error = "Invalid location selected.";
        }

        /* Requirement 7 + 8:
           Allow valid submission and update/save in database */
        else {
            $updateStmt = $conn->prepare("
                UPDATE job_postings
                SET job_title = ?, job_description = ?, job_requirement = ?, salary_min = ?, salary_max = ?, location = ?, job_type = ?
                WHERE job_id = ? AND employer_id = ? AND status = 'Active'
            ");

            $updateStmt->bind_param(
                "sssddssii",
                $job_title,
                $job_description,
                $job_requirement,
                $salary_min,
                $salary_max,
                $location,
                $job_type,
                $job_id,
                $employer_id
            );

            if ($updateStmt->execute()) {
                $updateStmt->close();
                header("Location: dashboard.php?updated=1");
                exit();
            } else {
                $error = "Failed to update job posting.";
            }

            $updateStmt->close();
        }
    }

    /* Keep entered values after validation error */
    $job['job_title'] = $job_title;
    $job['job_description'] = $job_description;
    $job['job_requirement'] = $job_requirement;
    $job['salary_min'] = $salary_min;
    $job['salary_max'] = $salary_max;
    $job['location'] = $location;
    $job['job_type'] = $job_type;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Job Posting</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 650px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .salary-group {
            display: flex;
            gap: 10px;
        }

        .salary-group input {
            width: 100%;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        button {
            padding: 10px;
            width: 100%;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .save-btn {
            background: #007bff;
        }

        .save-btn:hover {
            background: #0056b3;
        }

        .cancel-btn {
            background: gray;
        }

        .cancel-btn:hover {
            background: #555;
        }

        .error {
            color: red;
            margin-bottom: 12px;
            background: #ffeaea;
            border: 1px solid #ffb3b3;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Job Posting</h2>

    <?php if ($error !== ""): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Job Title</label>
        <input type="text" name="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>">

        <label>Job Description</label>
        <textarea name="job_description"><?php echo htmlspecialchars($job['job_description']); ?></textarea>

        <label>Job Requirement</label>
        <textarea name="job_requirement" placeholder="Enter required skills, qualifications, experience, or other job requirements"><?php echo htmlspecialchars($job['job_requirement'] ?? ''); ?></textarea>

        <label>Salary Range</label>
        <div class="salary-group">
            <input type="text" name="salary_min" placeholder="Minimum Salary" value="<?php echo htmlspecialchars($job['salary_min']); ?>">
            <input type="text" name="salary_max" placeholder="Maximum Salary" value="<?php echo htmlspecialchars($job['salary_max']); ?>">
        </div>

        <label>Location</label>
        <select name="location">
            <option value="">-- Select Location --</option>
            <?php foreach ($allowedLocations as $loc): ?>
                <option value="<?php echo htmlspecialchars($loc); ?>" <?php if (($job['location'] ?? '') === $loc) echo "selected"; ?>>
                    <?php echo htmlspecialchars($loc); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Job Type</label>
        <select name="job_type">
            <option value="">-- Select Job Type --</option>
            <?php foreach ($allowedJobTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php if (($job['job_type'] ?? '') === $type) echo "selected"; ?>>
                    <?php echo htmlspecialchars($type); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="button-group">
            <button type="submit" name="save" class="save-btn">Save Changes</button>
            <button type="submit" name="cancel" class="cancel-btn">Cancel</button>
        </div>
    </form>
</div>

</body>
</html>
