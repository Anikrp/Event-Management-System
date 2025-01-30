<?php
session_start();
require_once 'includes/Auth.php';
require_once 'includes/Event.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

$event = new Event();
$events = $event->getEvents($page, 10, $sort, $order, $search, $_SESSION['user_id']);

// Set page title
$pageTitle = "Event Management System - Dashboard";

// Include header
require_once 'includes/header.php';
?>

<div class="container mt-4">
   
    
    

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="bi bi-calendar2-event me-2"></i>Events</h2>
        <div class="d-flex gap-3">
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" name="search" placeholder="Search events..." 
                    value="<?php echo htmlspecialchars($search); ?>" aria-label="Search">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-sort-down me-1"></i> Sort by
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item <?php echo ($sort == 'date' && $order == 'ASC') ? 'active' : ''; ?>" 
                        href="?sort=date&order=ASC"><i class="bi bi-calendar-up me-2"></i>Date (Ascending)</a></li>
                    <li><a class="dropdown-item <?php echo ($sort == 'date' && $order == 'DESC') ? 'active' : ''; ?>" 
                        href="?sort=date&order=DESC"><i class="bi bi-calendar-down me-2"></i>Date (Descending)</a></li>
                    <li><a class="dropdown-item <?php echo ($sort == 'title' && $order == 'ASC') ? 'active' : ''; ?>" 
                        href="?sort=title&order=ASC"><i class="bi bi-sort-alpha-down me-2"></i>Title (A-Z)</a></li>
                    <li><a class="dropdown-item <?php echo ($sort == 'title' && $order == 'DESC') ? 'active' : ''; ?>" 
                        href="?sort=title&order=DESC"><i class="bi bi-sort-alpha-up me-2"></i>Title (Z-A)</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php if (!empty($search)): ?>
    <div class="alert alert-info d-flex align-items-center">
        <i class="bi bi-search me-2"></i>
        <div>Search results for: "<strong><?php echo htmlspecialchars($search); ?></strong>"</div>
        <a href="dashboard.php" class="btn btn-sm btn-outline-info ms-auto">Clear search</a>
    </div>
    <?php endif; ?>

    <!-- Event Cards -->
    <div class="row g-4">
        <?php if (empty($events['events'])): ?>
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center">
                <i class="bi bi-info-circle me-2"></i>
                <div>
                    No events found. <?php echo !empty($search) ? 'Try a different search term.' : ''; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($events['events'] as $event): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title text-primary mb-0"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <span class="badge bg-primary rounded-pill">
                                <i class="bi bi-calendar-event"></i> <?php echo date('M d', strtotime($event['date'])); ?>
                            </span>
                        </div>
                        <p class="card-text event-description text-muted mb-4"><?php echo htmlspecialchars($event['description']); ?></p>
                        
                        <div class="event-details mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-clock text-primary me-2"></i>
                                <span><?php echo date('h:i A', strtotime($event['time'])); ?></span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-geo-alt text-primary me-2"></i>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                            
                            <?php
                                $attendeeCount = (new Event())->getAttendeeCount($event['id']);
                                $remainingCapacity = $event['max_capacity'] - $attendeeCount;
                                $capacityPercentage = ($attendeeCount / $event['max_capacity']) * 100;
                            ?>
                            
                            <div class="capacity-info border rounded p-3 bg-light">
                                <h6 class="text-primary mb-3"><i class="bi bi-people-fill me-2"></i>Capacity Information</h6>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar <?php echo $capacityPercentage >= 90 ? 'bg-danger' : ($capacityPercentage >= 70 ? 'bg-warning' : 'bg-success'); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $capacityPercentage; ?>%" 
                                         aria-valuenow="<?php echo $capacityPercentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="row text-center g-2">
                                    <div class="col-4">
                                        <div class="capacity-stat">
                                            <small class="text-muted d-block">Attendees</small>
                                            <strong class="text-success"><?php echo $attendeeCount; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="capacity-stat">
                                            <small class="text-muted d-block">Available</small>
                                            <strong class="text-primary"><?php echo $remainingCapacity; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="capacity-stat">
                                            <small class="text-muted d-block">Total</small>
                                            <strong><?php echo $event['max_capacity']; ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="d-flex gap-2">
                            <a href="register_attendee.php?event_id=<?php echo $event['id']; ?>" 
                               class="btn btn-primary flex-grow-1 <?php echo $remainingCapacity <= 0 ? 'disabled' : ''; ?>">
                                <i class="bi bi-person-plus-fill me-1"></i>
                                <?php echo $remainingCapacity <= 0 ? 'Event Full' : 'Register Attendee'; ?>
                            </a>
                           
                                <button class="btn btn-outline-primary edit-event" data-id="<?php echo $event['id']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($events['total'] > 0): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php 
            $total_pages = ceil($events['total'] / 10);
            $range = 2; // Number of pages before and after current page
            
            // Previous page
            if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif;

            // First page
            if ($page > $range + 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">1</a>
                </li>
                <?php if ($page > $range + 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif;
            endif;

            // Page numbers
            for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++): ?>
                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor;

            // Last page
            if ($page < $total_pages - $range): ?>
                <?php if ($page < $total_pages - $range - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"><?php echo $total_pages; ?></a>
                </li>
            <?php endif;

            // Next page
            if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createEventForm">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="time" name="time" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label for="max_capacity" class="form-label">Maximum Capacity</label>
                        <input type="number" class="form-control" id="max_capacity" name="max_capacity" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveEvent">Save Event</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editEventForm">
                    <input type="hidden" id="edit_event_id" name="id">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="edit_date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="edit_time" name="time" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="edit_location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_max_capacity" class="form-label">Maximum Capacity</label>
                        <input type="number" class="form-control" id="edit_max_capacity" name="max_capacity" required min="1">
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateEvent">Update Event</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
