<?php
session_start();
require_once '../includes/Auth.php';
require_once '../includes/Event.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please login to register']);
    exit();
}

$event = new Event();

// Handle unsupported methods first
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Handle GET requests for attendee listing and count
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['event_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        exit();
    }

    if (isset($_GET['count'])) {
        // Return only the count of attendees
        $count = $event->getAttendeeCount($_GET['event_id']);
        echo json_encode(['success' => true, 'count' => $count]);
        exit();
    } else {
        // Return list of attendees
        $attendees = $event->getAttendees($_GET['event_id']);
        echo json_encode(['success' => true, 'attendees' => $attendees]);
        exit();
    }
}

// Handle POST requests for registering attendees
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON input
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if (!$data) {
        http_response_code(400);
        error_log("Invalid JSON input: " . $jsonInput);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }

    if (!isset($data['event_id']) || !isset($data['attendees']) || !is_array($data['attendees'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit();
    }

    $eventData = $event->getEvent($data['event_id']);

    if (!$eventData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit();
    }

    // Check current capacity
    $currentCount = $event->getAttendeeCount($data['event_id']);
    $remainingSlots = $eventData['max_capacity'] - $currentCount;

    if (count($data['attendees']) > $remainingSlots) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Not enough slots available. Only ' . $remainingSlots . ' slots remaining'
        ]);
        exit();
    }

    // Validate each attendee's data
    foreach ($data['attendees'] as $attendee) {
        if (!isset($attendee['name']) || !isset($attendee['email']) || !isset($attendee['phone'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields for all attendees']);
            exit();
        }

        // Validate email format
        if (!filter_var($attendee['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email format: ' . $attendee['email']]);
            exit();
        }

        // Validate phone number format
        if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $attendee['phone'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid phone number format: ' . $attendee['phone']]);
            exit();
        }
    }

    try {
        // Begin transaction
        $event->beginTransaction();

        // Register each attendee
        $registeredCount = 0;
        foreach ($data['attendees'] as $attendee) {
            $result = $event->registerAttendee(
                $data['event_id'],
                $_SESSION['user_id'],
                $attendee['name'],
                $attendee['email'],
                $attendee['phone']
            );

            if ($result['success']) {
                $registeredCount++;
            } else {
                // If any registration fails, rollback all
                $event->rollback();
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to register attendee: ' . $attendee['name'] . '. ' . $result['message']
                ]);
                exit();
            }
        }

        // If all registrations successful, commit transaction
        $event->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully registered ' . $registeredCount . ' attendee(s)',
            'registered_count' => $registeredCount
        ]);
        exit();

    } catch (Exception $e) {
        $event->rollback();
        http_response_code(500);
        error_log("Registration error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.'
        ]);
        exit();
    }
}
