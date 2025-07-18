<?php
$rep_id = $_GET['rep_id'] ?? 'Unknown';
$event_id = $_GET['event_id'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submission Confirmation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #9de5ff, #ac73ff);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      background: white;
      text-align: center;
    }
    .btn {
      border-radius: 30px;
      margin: 10px;
      padding: 10px 25px;
    }
    .rep-info {
      font-size: 1.1rem;
      color: #555;
    }
    .checkmark {
      font-size: 60px;
      color: #28a745;
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="checkmark mb-3">✔️</div>
    <h2>Post-Event Report Submitted</h2>
    <p class="rep-info">Report ID: <strong><?= htmlspecialchars($rep_id) ?></strong></p>
    <p class="rep-info">Event ID: <strong><?= htmlspecialchars($event_id) ?></strong></p>

    <div class="mt-4">
      <a href="StudentDashboard.php" class="btn btn-primary">
        Return to Dashboard
      </a>
      <a href="exportPostPDF.php?rep_id=<?= urlencode($rep_id) ?>" class="btn btn-success" target="_blank">
        Export PDF
      </a>
    </div>
  </div>

</body>
</html>
