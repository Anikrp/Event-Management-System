<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/Auth.php';
$auth = new Auth();

// Get the current page name
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = "Event Management System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Event Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hover-shadow {
            transition: all 0.3s ease;
        }
        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .event-description {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 4.5em;
        }
        .capacity-stat {
            padding: 0.5rem;
            border-radius: 0.25rem;
            background-color: white;
        }
        .capacity-stat strong {
            font-size: 1.25rem;
        }
        .progress {
            background-color: #e9ecef;
            border-radius: 0.5rem;
        }
        .progress-bar {
            transition: width 0.6s ease;
            border-radius: 0.5rem;
        }
        .card {
            border: none;
            transition: all 0.3s ease;
        }
        .btn-group .btn {
            padding: 0.5rem 1rem;
        }
        .dropdown-item.active {
            background-color: #0d6efd;
            color: white;
        }
        .dropdown-item:hover {
            background-color: #e9ecef;
        }
        .dropdown-item.active:hover {
            background-color: #0b5ed7;
        }
        .alert {
            border-left: 4px solid;
        }
        .alert-info {
            border-left-color: #0dcaf0;
        }
        .navbar .nav-link.active {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        .navbar .nav-link:hover {
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand">
                <i class="bi bi-calendar-event"></i> Event Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" 
                           href="reports.php">
                            <i class="bi bi-file-earmark-text"></i> Reports
                        </a>
                    </li>
                    
                    
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_event.php' ? 'active' : ''; ?>" 
                               href="create_event.php">
                                <i class="bi bi-plus-circle"></i> Create Event
                            </a>
                        </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">
                            <i class="bi bi-search"></i> Search
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> 
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="bi bi-person"></i> Profile
                                    </a>
                                </li>
                              
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
