<?php
include('dbconfig.php');

header('Content-Type: text/html');

// Fetch user inputs from the URL
$capacity = $_GET['ev_pax'] ?? null;
$eventType = $_GET['ev_type'] ?? null;
$eventDate = $_GET['ev_date'] ?? null;
$startTime = $_GET['ev_start_time'] ?? null;
$endTime = $_GET['ev_end_time'] ?? null;

if (!$capacity || !$eventType || !$eventDate || !$startTime || !$endTime) {
    echo "<p style='color: red;'>Missing required fields. Please go back and enter all details.</p>";
    exit;
}

// Debug Log Array
$debugLog = [];

// Fetch Available Venues (Filtering Step)
$query = "
    SELECT * FROM venue 
    WHERE Venue_Type = ? 
    AND Capacity >= ? 
    AND Opening_Hours <= ? 
    AND Closing_Hours >= ? 
    AND Condition_Status NOT IN ('Maintenance', 'Light Problem')
    AND Venue_ID NOT IN (
        SELECT Ev_Venue FROM events
        WHERE (
            Ev_Status IN ('Pending Advisor Review', 'Sent Back by Advisor', 'Approved by Advisor', 'Approved by Coordinator')
            OR (
                Ev_Status = 'Approved by Coordinator'
                AND Ev_ID NOT IN (
                    SELECT Ev_ID FROM eventpostmortem WHERE Rep_PostStatus IN ('Pending Coordinator', 'Accepted')
                )
            )
        )
        AND Ev_Date = ?
    )
";

$stmt = $conn->prepare($query);
$stmt->bind_param('sisss', $eventType, $capacity, $startTime, $endTime, $eventDate);
$stmt->execute();
$venues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($venues)) {
    echo "<h3>No suitable venues found after filtering.</h3>";
    exit;
}

// Step 1: Log Initial Filtering
foreach ($venues as $venue) {
    $debugLog['Initial_Filter'][] = [
        'Venue_Name' => $venue['Venue_Name'],
        'Capacity' => $venue['Capacity'],
        'Opening_Hours' => $venue['Opening_Hours'],
        'Closing_Hours' => $venue['Closing_Hours'],
        'Condition_Status' => $venue['Condition_Status'],
        'Included' => 'Yes',
    ];
}

// Define the Fitness Function
function calculateFitness($venue, $capacity)
{
    $fitness = 0;
    if ($venue['Capacity'] >= $capacity)
        $fitness += 10;

    // Condition Status Scoring
    switch ($venue['Condition_Status']) {
        case 'Good':
            $fitness += 10;
            break;
        case 'Projector Broken':
            $fitness += 8;
            break;
        case 'Projector Screen Not OK':
            $fitness += 5;
            break;
        case 'Air Condition Did Not Work':
            $fitness += 1;
            break;
    }

    return $fitness;
}

// Step 2: Fitness Calculation
foreach ($venues as &$venue) {
    $venue['Fitness'] = calculateFitness($venue, $capacity);
    $debugLog['Fitness_Calculation'][] = [
        'Venue_Name' => $venue['Venue_Name'],
        'Fitness_Score' => $venue['Fitness'],
    ];
}

// Step 3: Sorting by Fitness
usort($venues, function ($a, $b) {
    return $b['Fitness'] - $a['Fitness'];
});

// Step 4: Crossover Process
$children = [];
for ($i = 0; $i < count($venues) - 1; $i++) {
    $parent1 = $venues[$i];
    $parent2 = $venues[$i + 1];

    $child = [
        'Venue_Name' => $parent1['Venue_Name'],
        'Capacity' => ($parent1['Capacity'] + $parent2['Capacity']) / 2,
        'Condition_Status' => $parent1['Condition_Status'],
        'Fitness' => 0,
    ];
    $child['Fitness'] = calculateFitness($child, $capacity);
    $children[] = $child;

    $debugLog['Crossover'][] = [
        'Parent1' => $parent1['Venue_Name'],
        'Parent2' => $parent2['Venue_Name'],
        'Child' => $child['Venue_Name'],
        'Child_Fitness' => $child['Fitness'],
    ];
}

// Step 5: Mutation Process
foreach ($venues as &$venue) {
    if (rand(1, 100) <= 10) {
        $venue['Capacity'] += rand(-5, 5);
        $venue['Fitness'] = calculateFitness($venue, $capacity);
        $debugLog['Mutation'][] = [
            'Venue_Name' => $venue['Venue_Name'],
            'Mutated_Capacity' => $venue['Capacity'],
            'New_Fitness' => $venue['Fitness'],
        ];
    }
}

// Step 6: Final Selection
$bestVenue = $venues[0];
$debugLog['Final_Selection'] = [
    'Best_Venue' => $bestVenue['Venue_Name'],
    'Best_Fitness' => $bestVenue['Fitness'],
];

// Display Debug Log
echo "<h2>Genetic Algorithm Process</h2>";
foreach ($debugLog as $step => $details) {
    echo "<h3>$step</h3><pre>" . print_r($details, true) . "</pre>";
}

// Back Button
echo "<a href='ProposalEvent.php' class='btn btn-primary'>Back to Proposal</a>";

?>