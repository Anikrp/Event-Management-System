<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($username, $email, $password) {
        try {
            // Check username separately first
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => false, "message" => "Username is already taken. Please choose a different username."];
            }
            
            // Then check email separately
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => false, "message" => "Email address is already registered. Please use a different email or try logging in."];
            }
            
            // Validate password strength
            if (strlen($password) < 8) {
                return ["success" => false, "message" => "Password must be at least 8 characters long."];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            
            return ["success" => true, "message" => "Registration successful! You can now login with your credentials."];
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ["success" => false, "message" => "Registration failed due to a database error. Please try again later."];
        }
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    return ["success" => true, "message" => "Login successful"];
                }
            }
            
            return ["success" => false, "message" => "Invalid username or password"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Login failed: " . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ["success" => true, "message" => "Logout successful"];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    public function getUserData($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, is_admin FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() === 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error getting user data: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile($userId, $username, $email, $currentPassword = null, $newPassword = null) {
        try {
            // Start with basic profile update
            $updates = ["username = ?", "email = ?"];
            $params = [$username, $email];
            
            // If password change is requested
            if ($currentPassword && $newPassword) {
                // Verify current password
                $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!password_verify($currentPassword, $user['password'])) {
                    return ["success" => false, "message" => "Current password is incorrect"];
                }
                
                // Add password update
                $updates[] = "password = ?";
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            
            // Add userId to params
            $params[] = $userId;
            
            // Update user data
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // Update session data
            $_SESSION['username'] = $username;
            
            return ["success" => true, "message" => "Profile updated successfully"];
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return ["success" => false, "message" => "Error updating profile"];
        }
    }
}
?>
