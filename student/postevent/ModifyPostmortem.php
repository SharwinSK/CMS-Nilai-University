<?php
session_start();
include('dbconfig.php');

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];

if (!isset($_GET['rep_id'])) {
    die("Missing postmortem ID.");
}

$rep_id = $_GET['rep_id'];

// Validate the report
$validation_query = "
    SELECT ep.Rep_ID, ep.Ev_ID, ep.Status_ID, st.Status_Name, 
           e.Ev_Name, e.Ev_Objectives, e.Ev_ProjectNature, 
           s.Stu_Name, c.Club_Name
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    JOIN eventstatus st ON ep.Status_ID = st.Status_ID
    WHERE ep.Rep_ID = ? AND e.Stu_ID = ? AND st.Status_Name = 'Postmortem Rejected'
";

$stmt = $conn->prepare($validation_query);
$stmt->bind_param("ss", $rep_id, $stu_id);  // both are string type
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    die("Invalid access or report is not rejected.");
}

$event_id = $event['Ev_ID'];

// Fetch challenges, recommendation, conclusion
$desc_query = $conn->prepare("SELECT Rep_ChallengesDifficulties, Rep_recomendation, Rep_Conclusion FROM eventpostmortem WHERE Rep_ID = ?");
$desc_query->bind_param("i", $rep_id);
$desc_query->execute();
$desc_result = $desc_query->get_result();
$desc = $desc_result->fetch_assoc();

// Optional: Fetch statement filename (PDF)
$statement_query = $conn->prepare("SELECT Statement FROM budgetsummary WHERE Ev_ID = ?");
$statement_query->bind_param("i", $event_id);
$statement_query->execute();
$statement_result = $statement_query->get_result();
$statement_row = $statement_result->fetch_assoc();
$statement_file = $statement_row['Statement'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $challenges = $_POST['challenges'];
    $recommendation = $_POST['recommendation'];
    $conclusion = $_POST['conclusion'];

    // Update postmortem table only
    $update_postmortem = $conn->prepare("UPDATE eventpostmortem 
        SET Rep_ChallengesDifficulties = ?, Rep_recomendation = ?, Rep_Conclusion = ?, Updated_At = NOW(), Status_ID = 6 
        WHERE Rep_ID = ?");
    $update_postmortem->bind_param("sssi", $challenges, $recommendation, $conclusion, $rep_id);
    $update_postmortem->execute();

    echo "<script>alert('Postmortem updated successfully.'); window.location.href = 'StudentDashboard.php';</script>";
    exit();
}
?>


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modify Postmortem Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #15b392;
            color: white;
            text-align: center;
        }

        .btn-success {
            background-color: #54c392;
            border-color: #54c392;
        }

        .btn-success:hover {
            background-color: #06d001;
        }

        .btn-primary {
            background-color: #54c392;
        }

        .btn-primary:hover {
            background-color: #06d001;
        }

        .btn-danger.btn-sm {
            font-size: 0.8rem;
            padding: 2px 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <h2>Modify Postmortem Report</h2>
            </div>
            <div class="card-body">
                <form action="ModifyPostmortem.php?rep_id=<?= $rep_id ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="rep_id" value="<?= $rep_id ?>">

                    <!-- Event Information Section -->
                    <h5>Event Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Proposal Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($event['Stu_Name']) ?>"
                                readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Event Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($event['Ev_Name']) ?>"
                                readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Club Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($event['Club_Name']) ?>"
                                readonly />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Event Objectives</label>
                            <textarea class="form-control" rows="3"
                                readonly><?= htmlspecialchars($event['Ev_Objectives']) ?></textarea>
                        </div>
                    </div>



                    <!-- 🔸 Challenges Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-warning">
                            <strong>Challenges and Difficulties</strong>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" name="challenges"
                                rows="4"><?= htmlspecialchars($desc['Rep_ChallengesDifficulties']) ?></textarea>
                        </div>
                    </div>

                    <!-- 🔸 Recommendation Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-info">
                            <strong>Recommendation</strong>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" name="recommendation"
                                rows="4"><?= htmlspecialchars($desc['Rep_recomendation']) ?></textarea>
                        </div>
                    </div>

                    <!-- 🔸 Conclusion Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <strong>Conclusion</strong>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" name="conclusion"
                                rows="4"><?= htmlspecialchars($desc['Rep_Conclusion']) ?></textarea>
                        </div>
                    </div>



                    <!-- Buttons -->
                    <div class="text-center mt-4">
                        <a href="StudentDashboard.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Update Report</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    <script>

        // ─── Optional Validation ─────────────────────────────────────
        document.querySelector("form").addEventListener("submit", function (e) {
            let isValid = true;
            let messages = [];

            const requiredTextareas = ["challenges", "recommendation", "conclusion"];
            requiredTextareas.forEach(id => {
                const value = document.getElementsByName(id)[0].value.trim();
                if (!value) {
                    isValid = false;
                    messages.push(`Please fill in the ${id.charAt(0).toUpperCase() + id.slice(1)} field.`);
                }
            });

            const photoInput = document.getElementById("inputPhoto");
            if (photoInput && photoInput.files.length === 0) {
                isValid = false;
                messages.push("Please upload at least one event photo.");
            }

            if (!isValid) {
                e.preventDefault();
                alert(messages.join("\n"));
            }
        });
    </script>

</body>

</html>