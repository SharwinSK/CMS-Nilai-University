<?php
// Start session and DB connection
session_start();
include('db/dbconfig.php'); // adjust path as needed

function getFileSizeIfExists($path)
{
    $fullPath = "../" . ltrim($path, "/"); // adjust if needed
    return (file_exists($fullPath) && is_file($fullPath)) ? filesize($fullPath) : 0;
}

$totalMB = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ev_id'])) {
    $ev_id = $_POST['ev_id'];
    $totalBytes = 0;

    // 1. Poster & Additional Info
    $stmt = $conn->prepare("SELECT Ev_Poster, Ev_AdditionalInfo FROM events WHERE Ev_ID = ?");
    $stmt->bind_param("s", $ev_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalBytes += getFileSizeIfExists($row['Ev_Poster']);
        $totalBytes += getFileSizeIfExists($row['Ev_AdditionalInfo']);
    } else {
        $error = "Event ID not found.";
    }

    // 2. COCU PDFs
    $stmt = $conn->prepare("SELECT COCU_Statement FROM committee WHERE Ev_ID = ?");
    $stmt->bind_param("s", $ev_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $totalBytes += getFileSizeIfExists($row['COCU_Statement']);
    }

    // 3. Budget Statement
    $stmt = $conn->prepare("SELECT Budget_Statement FROM budgetsummary WHERE Ev_ID = ?");
    $stmt->bind_param("s", $ev_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalBytes += getFileSizeIfExists($row['Budget_Statement']);
    }

    // 4. Individual Reports
    $stmt = $conn->prepare("SELECT Report_Path FROM individualreport WHERE Ev_ID = ?");
    $stmt->bind_param("s", $ev_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $totalBytes += getFileSizeIfExists($row['Report_Path']);
    }

    // 5. Event Photos (optional: only if you have a table for this)
    $stmt = $conn->prepare("SELECT Photo_Path FROM eventphotos WHERE Ev_ID = ?");
    $stmt->bind_param("s", $ev_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $totalBytes += getFileSizeIfExists($row['Photo_Path']);
    }

    if (!$error) {
        $totalMB = round($totalBytes / (1024 * 1024), 2); // Convert to MB
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Event Storage Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h2 class="mb-4 text-center">📦 Event Storage Size Calculator</h2>
        <form method="POST" class="card shadow p-4">
            <div class="mb-3">
                <label for="ev_id" class="form-label">Enter Event ID</label>
                <input type="text" name="ev_id" id="ev_id" class="form-control" required placeholder="e.g. EV23001">
            </div>
            <button type="submit" class="btn btn-success">Calculate Storage</button>
        </form>

        <?php if ($error): ?>
            <div class="alert alert-danger mt-4"><?= $error ?></div>
        <?php elseif ($totalMB !== null): ?>
            <div class="alert alert-info mt-4">
                ✅ Total storage used for <strong><?= htmlspecialchars($_POST['ev_id']) ?></strong>: <strong><?= $totalMB ?>
                    MB</strong>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>