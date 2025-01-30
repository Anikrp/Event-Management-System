<?php
require_once 'includes/header.php';
require_once 'includes/Event.php';

$event = new Event();
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

$searchResults = $event->search($search, $type, $page, $limit);
?>

<div class="container mt-4">
    <h2>Search Events and Attendees</h2>
    
    <form method="GET" action="search.php" class="mb-4">
        <div class="row">
            <div class="col-md-8">
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search for events or attendees...">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-control">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="events" <?php echo $type === 'events' ? 'selected' : ''; ?>>Events</option>
                    <option value="attendees" <?php echo $type === 'attendees' ? 'selected' : ''; ?>>Attendees</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </div>
    </form>

    <?php if (!empty($search)): ?>
        <div class="search-results">
            <?php if (empty($searchResults['data'])): ?>
                <div class="alert alert-info">No results found for "<?php echo htmlspecialchars($search); ?>"</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($searchResults['data'] as $result): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <?php if ($result['type'] === 'event'): ?>
                                        <h5 class="card-title">
                                            <i class="fas fa-calendar-alt"></i>
                                            <a href="event_details.php?id=<?php echo $result['id']; ?>">
                                                <?php echo htmlspecialchars($result['title']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($result['location']); ?><br>
                                                <i class="fas fa-clock"></i> <?php echo htmlspecialchars($result['date'] . ' ' . $result['time']); ?>
                                            </small>
                                        </p>
                                    <?php else: ?>
                                        <h5 class="card-title">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($result['name']); ?>
                                        </h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($result['email']); ?><br>
                                                <i class="fas fa-calendar-check"></i> Attending: 
                                                <a href="event_details.php?id=<?php echo $result['event_id']; ?>">
                                                    <?php echo htmlspecialchars($result['event_title']); ?>
                                                </a>
                                            </small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($searchResults['total_pages'] > 1): ?>
                    <nav aria-label="Search results pagination">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $searchResults['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?q=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
