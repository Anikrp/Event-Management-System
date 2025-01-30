<?php
session_start();
require_once 'includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
        $_SESSION['error'] = "All fields are required. Please fill in all the information.";
        header('Location: register.php');
        exit();
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header('Location: register.php');
        exit();
    }

    // Validate username (alphanumeric and underscore only)
    $username = trim($_POST['username']);
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $_SESSION['error'] = "Username must be 3-20 characters long and can only contain letters, numbers, and underscores.";
        header('Location: register.php');
        exit();
    }

    // Validate email
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: register.php');
        exit();
    }

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password length
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header('Location: register.php');
        exit();
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match. Please try again.";
        header('Location: register.php');
        exit();
    }

    $auth = new Auth();
    $result = $auth->register($username, $email, $password);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: register.php');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
