<?php
session_start();
require_once '../includes/Auth.php';
require_once '../includes/Event.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Check if event ID is provided
if (!isset($_GET['event_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit();
}

$event = new Event();
$eventData = $event->getEvent($_GET['event_id']);

if (!$eventData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit();
}

// Set headers for CSV download
$filename = 'event_attendees_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $eventData['title']) . '_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output handle
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV headers
fputcsv($output, ['Event Details']);
fputcsv($output, ['Title', $eventData['title']]);
fputcsv($output, ['Date', $eventData['date']]);
fputcsv($output, ['Time', $eventData['time']]);
fputcsv($output, ['Location', $eventData['location']]);
fputcsv($output, ['Maximum Capacity', $eventData['max_capacity']]);
fputcsv($output, []); // Empty line for spacing

// Attendee list headers
fputcsv($output, ['Attendee Information']);
fputcsv($output, ['Name', 'Email', 'Phone', 'Registration Date']);

// Get attendees in chunks to handle large datasets
$offset = 0;
$limit = 1000; // Process 1000 records at a time

while (true) {
    $attendees = $event->getAttendeesChunk($_GET['event_id'], $offset, $limit);
    if (empty($attendees)) {
        break;
    }

    foreach ($attendees as $attendee) {
        fputcsv($output, [
            $attendee['name'],
            $attendee['email'],
            $attendee['phone'],
            $attendee['registration_date']
        ]);
    }

    $offset += $limit;
    flush(); // Flush output buffer
}

fclose($output);
exit();
