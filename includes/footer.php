<?php
// Add any footer content here if needed
?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Load page specific scripts based on current page -->
<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
switch ($currentPage) {
    case 'dashboard':
        echo '<script src="assets/js/dashboard.js"></script>';
        break;
    case 'reports':
        echo '<script src="assets/js/reports.js"></script>';
        break;
    // Add other page-specific scripts here
}
?>

</body>
</html>
