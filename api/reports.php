<?php
session_start();
require_once '../includes/Auth.php';
require_once '../includes/Event.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

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

// Set chunk size for memory efficiency
$chunkSize = 1000;
$offset = 0;

// Generate CSV
$filename = sanitize_filename('event_attendees_' . $eventData['title'] . '_' . date('Y-m-d')) . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel handling
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
fputcsv($output, ['Name', 'Email', 'Phone', 'Registration Date']);

// Add event details
fputcsv($output, ['Event Details:', '', '', '']);
fputcsv($output, ['Title:', mb_convert_encoding($eventData['title'], 'UTF-8', 'auto'), '', '']);
fputcsv($output, ['Date:', $eventData['date'], '', '']);
fputcsv($output, ['Time:', $eventData['time'], '', '']);
fputcsv($output, ['Location:', mb_convert_encoding($eventData['location'], 'UTF-8', 'auto'), '', '']);
fputcsv($output, ['', '', '', '']); // Empty line for spacing

// Process attendees in chunks to handle large datasets
while (true) {
    $attendees = $event->getAttendeesChunk($_GET['event_id'], $offset, $chunkSize);
    if (empty($attendees)) {
        break;
    }

    foreach ($attendees as $attendee) {
        fputcsv($output, [
            mb_convert_encoding($attendee['name'], 'UTF-8', 'auto'),
            $attendee['email'],
            $attendee['phone'],
            $attendee['registration_date']
        ]);
    }

    $offset += $chunkSize;
    flush(); // Flush output buffer
}

fclose($output);

// Helper function to sanitize filename
function sanitize_filename($filename) {
    // Remove any character that isn't a word character, dash, space, or dot
    $filename = preg_replace('/[^\w\-\. ]/', '', $filename);
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    return $filename;
}
