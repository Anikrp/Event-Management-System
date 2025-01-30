<?php
session_start();

require_once 'includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required";
        header('Location: index.php');
        exit();
    }

    try {
        $auth = new Auth();
        $result = $auth->login($username, $password);

        if ($result['success']) {
            // Ensure session is working
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Session not properly initialized");
            }
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: index.php');
            exit();
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "Login failed. Please try again.";
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
