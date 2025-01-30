<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/Auth.php';
require_once '../includes/Event.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$event = new Event();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $eventData = $event->getEvent($_GET['id']);
            if ($eventData) {
                echo json_encode(['success' => true, 'event' => $eventData]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Event not found']);
            }
        } else {
            // List events with search and pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';
            $order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
            
            $events = $event->getEvents($page, 10, $sort, $order, $search);
            echo json_encode(['success' => true, 'events' => $events]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        if (isset($data['action']) && $data['action'] === 'delete') {
            if (!isset($data['event_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                exit();
            }

            // Verify event exists and belongs to user
            $eventData = $event->getEvent($data['event_id'], $_SESSION['user_id']);
            if (!$eventData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Event not found or unauthorized']);
                exit();
            }

            $force = isset($data['force']) && ($data['force'] === '1' || $data['force'] === true);
            try {
                error_log("Attempting to delete event {$data['event_id']} with force=" . ($force ? 'true' : 'false'));
                $result = $event->delete($data['event_id'], $force);
                error_log("Delete result: " . json_encode($result));
                
                if (is_array($result)) {
                    if (!$result['success'] && isset($result['hasAttendees']) && $result['hasAttendees']) {
                        http_response_code(200); // This is not an error, just needs confirmation
                    } else if (!$result['success']) {
                        http_response_code(500);
                    }
                    echo json_encode($result);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Invalid response from delete operation']);
                }
            } catch (Exception $e) {
                error_log("Error in delete operation: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error deleting event: ' . $e->getMessage()]);
            }
            exit();
        }

        // Validate required fields
        $requiredFields = ['title', 'description', 'date', 'time', 'location', 'max_capacity'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit();
            }
        }

        try {
            $result = $event->create(
                filter_var($data['title'], FILTER_SANITIZE_STRING),
                filter_var($data['description'], FILTER_SANITIZE_STRING),
                filter_var($data['date'], FILTER_SANITIZE_STRING),
                filter_var($data['time'], FILTER_SANITIZE_STRING),
                filter_var($data['location'], FILTER_SANITIZE_STRING),
                filter_var($data['max_capacity'], FILTER_VALIDATE_INT),
                $_SESSION['user_id']
            );

            if ($result['success']) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Event created successfully']);
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            error_log("Error creating event: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error creating event: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request data']);
            exit();
        }

        try {
            // Verify event exists and belongs to user
            $eventData = $event->getEvent($_GET['id']);
            if (!$eventData) {
                throw new Exception('Event not found');
            }
            
            if ($eventData['user_id'] != $_SESSION['user_id'] && !$auth->isAdmin()) {
                throw new Exception('Unauthorized to edit this event');
            }

            // Validate required fields
            $requiredFields = ['title', 'description', 'date', 'time', 'location', 'max_capacity'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $result = $event->update(
                $_GET['id'],
                filter_var($data['title'], FILTER_SANITIZE_STRING),
                filter_var($data['description'], FILTER_SANITIZE_STRING),
                filter_var($data['date'], FILTER_SANITIZE_STRING),
                filter_var($data['time'], FILTER_SANITIZE_STRING),
                filter_var($data['location'], FILTER_SANITIZE_STRING),
                filter_var($data['max_capacity'], FILTER_VALIDATE_INT)
            );

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            error_log("Error updating event: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
