<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection error in User class: " . $e->getMessage());
            throw new Exception("Failed to connect to database");
        }
    }
    
    public function getUserById($id) {
        if (!$id || !is_numeric($id)) {
            error_log("Invalid user ID provided: " . print_r($id, true));
            return false;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, email, password 
                FROM users 
                WHERE id = :id
            ");
            
            if (!$stmt->execute([':id' => $id])) {
                error_log("Failed to execute user query for ID: " . $id);
                return false;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("No user found for ID: " . $id);
                return false;
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting user: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfile($userId, $data) {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Check if username is taken by another user
            if (isset($data['username'])) {
                $stmt = $this->conn->prepare("
                    SELECT id FROM users 
                    WHERE username = :username AND id != :userId
                ");
                $stmt->execute([
                    ':username' => $data['username'],
                    ':userId' => $userId
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $this->conn->rollBack();
                    return false;
                }
            }

            // Check if email is taken by another user
            if (isset($data['email'])) {
                $stmt = $this->conn->prepare("
                    SELECT id FROM users 
                    WHERE email = :email AND id != :userId
                ");
                $stmt->execute([
                    ':email' => $data['email'],
                    ':userId' => $userId
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $this->conn->rollBack();
                    return false;
                }
            }

            $updates = [];
            $params = [':id' => $userId];
            
            // Build update fields
            if (isset($data['username'])) {
                $updates[] = "username = :username";
                $params[':username'] = $data['username'];
            }
            
            if (isset($data['email'])) {
                $updates[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            
            if (isset($data['phone'])) {
                $updates[] = "phone = :phone";
                $params[':phone'] = $data['phone'];
            }
            
            if (isset($data['password'])) {
                $updates[] = "password = :password";
                $params[':password'] = $data['password'];
            }
            
            if (empty($updates)) {
                $this->conn->commit();
                return true; // Nothing to update
            }
            
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $countStmt = $this->conn->query("SELECT COUNT(*) FROM users");
            $total = $countStmt->fetchColumn();
            
            // Get users with pagination
            $stmt = $this->conn->prepare("
                SELECT id, username, email, is_admin, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'users' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Error getting users: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            // First delete all user's event registrations
            $stmt = $this->conn->prepare("DELETE FROM attendees WHERE user_id = :id");
            $stmt->execute([':id' => $id]);
            
            // Then delete the user
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    public function toggleAdminStatus($id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET is_admin = NOT is_admin 
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error toggling admin status: " . $e->getMessage());
            return false;
        }
    }
}
