<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['employer_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];

/* Fetch ACTIVE jobs */
$stmt = $conn->prepare("
    SELECT job_id, job_title, job_description, job_requirement, location, job_type, salary_min, salary_max
    FROM job_postings
    WHERE employer_id = ? AND status = 'Active'
");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Jobs Dashboard</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 30px;
    margin: 0;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

h2 {
    margin: 0;
}

/* Logout button */
.logout-btn {
    background: red;
    color: white;
    padding: 8px 15px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

.logout-btn:hover {
    background: darkred;
}

/* Success message */
.success-message {
    color: green;
    background: #eaf7ea;
    border: 1px solid #b7e1b7;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th, td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: center;
    vertical-align: top;
}

th {
    background: #eee;
}

a.edit {
    color: blue;
    text-decoration: underline;
}
</style>

</head>

<body>

<div class="header">
    <h2>My Job Postings (Active)</h2>

    <!-- Logout Button -->
    <form action="logout.php" method="POST">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="success-message">Job updated successfully.</div>
<?php endif; ?>

<table>
<tr>
    <th>Job Title</th>
    <th>Description</th>
    <th>Requirement</th>
    <th>Location</th>
    <th>Type</th>
    <th>Salary</th>
    <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?php echo htmlspecialchars($row['job_title']); ?></td>
    <td><?php echo htmlspecialchars($row['job_description']); ?></td>
    <td><?php echo htmlspecialchars($row['job_requirement']); ?></td>
    <td><?php echo htmlspecialchars($row['location']); ?></td>
    <td><?php echo htmlspecialchars($row['job_type']); ?></td>
    <td>RM <?php echo $row['salary_min']; ?> - RM <?php echo $row['salary_max']; ?></td>

    <td>
        <a class="edit" href="edit_job.php?job_id=<?php echo $row['job_id']; ?>">
            Edit
        </a>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
