<?php
session_start();
include('../db/dbconfig.php');

$currentPage = 'userguide';
if (!isset($_SESSION['Stu_ID'])) {
    header("Location: ../studentlogin.php");
    exit();
}

$student_name = $_SESSION['Stu_Name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student User Guide - Nilai University CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/student.css?v=<?= time() ?>" rel="stylesheet" />

    <style>
        body {
            background-color: #fdfdfd;
            font-family: "Segoe UI", sans-serif;
        }

        .guide-container {
            max-width: 900px;
            margin: auto;
            padding: 40px 20px;
        }

        .guide-section {
            margin-bottom: 30px;
        }

        .guide-section h4 {
            color: #2D4F2B;
        }

        .guide-section p {
            text-align: justify;
        }
    </style>
</head>

<body>

    <?php include('../components/header.php'); ?>
    <?php include('../components/offcanvas.php'); ?>

    <div class="guide-container">
        <h2 class="mb-4 text-center">📘 Student User Guide</h2>

        <div class="guide-section">
            <h4>1. Submitting a New Event Proposal</h4>
            <p>
                Go to the "Proposal" page and fill in all the required event details, including event name, date,
                objectives, PIC, committee, budget, and other relevant information. Make sure to upload all necessary
                supporting documents before submitting.
            </p>
        </div>

        <div class="guide-section">
            <h4>2. Checking Proposal Status</h4>
            <p>
                Visit the "Event Track Progress" page to monitor the current status of your proposal. If rejected, you
                may be allowed to modify and resubmit based on the advisor or coordinator's comments.
            </p>
        </div>

        <div class="guide-section">
            <h4>3. Submitting Post-Event Report</h4>
            <p>
                Once the proposal is approved and the event is conducted, you can submit your post-event report by
                clicking on "Create Post Event" in the Progress page. This includes uploading meeting attendance, final
                budget, and post-event reflections.
            </p>
        </div>

        <div class="guide-section">
            <h4>4. Managing Your Profile</h4>
            <p>
                You can update your personal information, email, and password from the "Profile" section in the
                dashboard.
            </p>
        </div>

        <div class="guide-section">
            <h4>5. Viewing Event History</h4>
            <p>
                To view completed events and export event data for your portfolio or reports, visit the "Event History"
                section. You can apply filters and download PDF summaries.
            </p>
        </div>

        <div class="guide-section">
            <h4>Need Help?</h4>
            <p>
                If you face any issues or bugs in the system, kindly contact the IT Department or your Co-Curricular
                Coordinator. Always ensure all required fields are properly filled before submission.
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>