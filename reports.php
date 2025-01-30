<?php
session_start();
require_once 'includes/Auth.php';
require_once 'includes/Event.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$event = new Event();
$events = $event->getEvents(1, 100, 'date', 'DESC', '', $_SESSION['user_id']); // Get user's events for reporting

// Include header
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text"></i> Event Reports</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" id="refreshData">
                <i class="bi bi-arrow-clockwise"></i> Refresh Data
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
           
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events['events'])): ?>
                        <tr>
                            <td colspan="6" class="text-center">No events found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($events['events'] as $evt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evt['title']); ?></td>
                                <td>
                                    <i class="bi bi-calendar"></i> <?php echo htmlspecialchars($evt['date']); ?><br>
                                    <small class="text-muted"><i class="bi bi-clock"></i> <?php echo htmlspecialchars($evt['time']); ?></small>
                                </td>
                                <td><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($evt['location']); ?></td>
                                <td><?php echo htmlspecialchars($evt['max_capacity']); ?></td>
                                <td>
                                    <span class="badge bg-info" id="attendee-count-<?php echo $evt['id']; ?>">
                                        Loading...
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary download-csv" data-id="<?php echo $evt['id']; ?>">
                                            <i class="bi bi-download"></i> Download CSV
                                        </button>
                                        <button class="btn btn-sm btn-info view-attendees" data-id="<?php echo $evt['id']; ?>">
                                            <i class="bi bi-people"></i> View Attendees
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-event" data-id="<?php echo $evt['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-danger"></i> Delete Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="deleteConfirmBody">
                Are you sure you want to delete this event?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete Event</button>
            </div>
        </div>
    </div>
</div>

<!-- Attendees Modal -->
<div class="modal fade" id="attendeesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-people"></i> Event Attendees
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody id="attendeesList">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
