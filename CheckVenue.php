<?php
include('dbconfig.php');

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    if (empty($_POST['ev_pax']) || empty($_POST['ev_date']) || empty($_POST['ev_start_time']) || empty($_POST['ev_end_time'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $capacity = $_POST['ev_pax'];
    $eventType = $_POST['ev_type'];
    $eventDate = $_POST['ev_date'];
    $startTime = $_POST['ev_start_time'];
    $endTime = $_POST['ev_end_time'];
    $excludedVenues = isset($_POST['excluded_venues']) && !empty($_POST['excluded_venues'])
        ? explode(',', $_POST['excluded_venues'])
        : [];

    $excludedClause = '';
    if (!empty($excludedVenues)) {
        $excludedClause = 'AND Venue_ID NOT IN (' . implode(',', array_fill(0, count($excludedVenues), '?')) . ')';
    }
    //  fetch the venue from database 
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
                    SELECT Ev_ID 
                    FROM eventpostmortem 
                    WHERE Rep_PostStatus IN ('Pending Coordinator', 'Accepted')
                )
            )
        )
        AND Ev_Date = ?
    )
    $excludedClause
";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'SQL preparation error: ' . $conn->error]);
        exit;
    }

    $params = [$eventType, $capacity, $startTime, $endTime, $eventDate];
    if (!empty($excludedVenues)) {
        $params = array_merge($params, $excludedVenues);
    }
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $stmt->error]);
        exit;
    }

    $venues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($venues)) {
        echo json_encode([
            'success' => false,
            'message' => 'No suitable venues found. Try adjust the time, date or change number of participants.',
        ]);
        exit;
    }

    $population = $venues;

    // Define the fitness function
    function calculateFitness($venue, $capacity)
    {
        $fitness = 0;

        // Capacity check
        if ($venue['Capacity'] >= $capacity) {
            $fitness += 10;
        }

        switch ($venue['Condition_Status']) {
            case 'Good':
                $fitness += 15;
                break;
            case 'Projector Broken':
                $fitness += 10;
                break;
            case 'Projector Screen Not OK':
                $fitness += 8;
                break;
            case 'Air Condition Did Not Work':
                $fitness += 3;
                break;
        }
        if ($venue['Technical_Equipment'] === 'No Technical Equipment') {
            $fitness += 10;
        } else {
            $fitness += 10;
        }

        return $fitness;
    }

    //  Calculate fitness for each venue
    foreach ($population as &$venue) {
        $venue['Fitness'] = calculateFitness($venue, $capacity);
    }

    //  Sort population by fitness in descending order
    usort($population, function ($a, $b) {
        return $b['Fitness'] - $a['Fitness'];
    });

    //  Perform crossover
    $children = [];
    for ($i = 0; $i < count($population) - 1; $i++) {
        $parent1 = $population[$i];
        $parent2 = $population[$i + 1];

        $child = [
            'Venue_Name' => $parent1['Venue_Name'],
            'Capacity' => ($parent1['Capacity'] + $parent2['Capacity']) / 2,
            'Venue_Type' => $parent1['Venue_Type'],
            'Condition_Status' => $parent1['Condition_Status'],
            'Technical_Equipment' => $parent2['Technical_Equipment'],
            'Fitness' => 0,
        ];

        $child['Fitness'] = calculateFitness($child, $capacity);
        $children[] = $child;
    }

    // Add children to the population
    $population = array_merge($population, $children);

    // Perform mutation
    foreach ($population as &$venue) {
        if (rand(1, 100) <= 10) {
            $venue['Capacity'] += rand(-5, 5);
            $venue['Fitness'] = calculateFitness($venue, $capacity);
        }
    }

    // Select the top result
    usort($population, function ($a, $b) {
        return $b['Fitness'] - $a['Fitness'];
    });

    $bestVenue = $population[0];


    echo json_encode([
        'success' => true,
        'venue_name' => $bestVenue['Venue_Name'],
        'venue_id' => $bestVenue['Venue_ID'],
        'fitness' => $bestVenue['Fitness'],
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
