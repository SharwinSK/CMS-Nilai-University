<?php
session_start();

// Init
$response = ['success' => false];

// Validate event ID
if (!isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing event ID']);
    exit;
}

$event_id = $_POST['event_id'];

// Save Event Flows
$eventFlows = [];
if (!empty($_POST['eventTime']) && !empty($_POST['eventDescription'])) {
    foreach ($_POST['eventTime'] as $i => $time) {
        $desc = $_POST['eventDescription'][$i];
        $eventFlows[] = [
            'time' => $time,
            'description' => $desc
        ];
    }
}

// Save Meetings
$meetings = [];
if (!empty($_POST['meetingDate'])) {
    foreach ($_POST['meetingDate'] as $i => $date) {
        $meetings[] = [
            'date' => $date,
            'start_time' => $_POST['meetingStartTime'][$i],
            'end_time' => $_POST['meetingEndTime'][$i],
            'location' => $_POST['meetingLocation'][$i],
            'description' => $_POST['meetingDescription'][$i] ?? ($_POST['meeting_description'][$i] ?? '')
        ];
    }
}

// Save photos (for preview only – real upload is done in final submit)
$photoNames = [];
if (!empty($_FILES['eventPhotos']['name'][0])) {
    foreach ($_FILES['eventPhotos']['name'] as $key => $name) {
        $photoNames[] = $name; // for preview only
    }
}

// Save IR filenames
$individualReports = [];
foreach ($_FILES as $key => $file) {
    if (strpos($key, 'individualReport') === 0 && !empty($file['name'])) {
        $individualReports[$key] = $file['name'];
    }
}

// Save everything into session
$_SESSION['post_event_data'] = [
    'event_id' => $event_id,
    'event_flows' => $eventFlows,
    'meetings' => $meetings,
    'challenges' => $_POST['challenges'] ?? '',
    'recommendation' => $_POST['recommendations'] ?? '',
    'conclusion' => $_POST['conclusion'] ?? '',
    'photo_filenames' => $photoNames,
    'budget_statement' => $_FILES['budgetStatement']['name'] ?? '',
    'individual_reports' => $individualReports
];

// Fetch committee claimers from DB (for attendance marking)
include('../../db/dbconfig.php');
$committee = [];
$stmt = $conn->prepare("SELECT Com_ID, Com_Name, Com_Position FROM committee WHERE Ev_ID = ? AND Com_COCUClaimers = 1");
$stmt->bind_param("s", $event_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $committee[] = $row;
}
$stmt->close();
$conn->close();

$_SESSION['post_event_data']['committee'] = $committee;

$response['success'] = true;
echo json_encode($response);
exit;
