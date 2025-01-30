<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/Auth.php';
require_once 'includes/User.php';

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize error array
$errors = [];

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = "Please login to access this page";
    // header('Location: index.php');
    // exit();
}

// Get user data
try {
    $user = new User();
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (!$userId) {
        throw new Exception("No user ID found in session");
    }
 
    $userData = $user->getUserById($userId);
    
    if (!$userData) {
        throw new Exception("Could not find user data for ID: " . $userId);
    }

} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid request";
        header('Location: profile.php');
        exit();
    }

    // Using htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $username = trim(htmlspecialchars($_POST['username']));
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Prepare update data
    $updateData = [
        'username' => $username,
        'email' => $email
    ];

    // Password change validation
    if (!empty($newPassword) || !empty($confirmPassword) || !empty($currentPassword)) {
        if (empty($currentPassword)) {
            $errors[] = "Current password is required to change password";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match";
        } elseif (!password_verify($currentPassword, $userData['password'])) {
            $errors[] = "Current password is incorrect";
        } else {
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
    }

    // If no errors, proceed with update
    if (empty($errors)) {
        if ($user->updateProfile($userId, $updateData)) {
            $_SESSION['success'] = "Profile updated successfully";
            header('Location: profile.php');
            exit();
        } else {
            $errors[] = "Failed to update profile. Username or email might already be in use.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Set page title
$pageTitle = "My Profile - Event Management System";

// Include header
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-circle"></i> My Profile</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                                echo htmlspecialchars($_SESSION['success']);
                                unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="profile.php" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <!-- Username field -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                            <div class="invalid-feedback">Username is required</div>
                        </div>

                        <!-- Email field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <hr class="my-4">

                        <!-- Password Change Section -->
                        <h5 class="mb-3">Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Required only if changing password</div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                            <div class="form-text">Leave blank to keep current password (minimum 8 characters)</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <!-- Submit buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Changes
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Password confirmation validation
function validatePassword() {
    var newPassword = document.getElementById('new_password')
    var confirmPassword = document.getElementById('confirm_password')
    var currentPassword = document.getElementById('current_password')
    
    if (newPassword.value) {
        currentPassword.required = true
        confirmPassword.required = true
        
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match')
        } else {
            confirmPassword.setCustomValidity('')
        }
    } else {
        currentPassword.required = false
        confirmPassword.required = false
        confirmPassword.setCustomValidity('')
    }
}

document.getElementById('new_password').addEventListener('input', validatePassword)
document.getElementById('confirm_password').addEventListener('input', validatePassword)
</script>

<?php require_once 'includes/footer.php'; ?>