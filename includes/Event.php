<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create($title, $description, $date, $time, $location, $maxCapacity, $userId) {
        try {
            $sql = "INSERT INTO events (title, description, date, time, location, max_capacity, user_id) 
                    VALUES (:title, :description, :date, :time, :location, :maxCapacity, :userId)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            $stmt->bindParam(':time', $time, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':maxCapacity', $maxCapacity, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            
            $stmt->execute();
            return ['success' => true, 'message' => 'Event created successfully'];
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating event'];
        }
    }
    
    public function update($id, $title, $description, $date, $time, $location, $maxCapacity) {
        try {
            // Validate input
            if (!$id || !$title || !$description || !$date || !$time || !$location || !$maxCapacity) {
                return ["success" => false, "message" => "All fields are required"];
            }

            // Validate date format
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                return ["success" => false, "message" => "Invalid date format. Please use YYYY-MM-DD format."];
            }

            // Validate and convert time format
            // Try 24-hour format first (HH:mm)
            $timeObj = DateTime::createFromFormat('H:i', $time);
            if (!$timeObj) {
                // Try 12-hour format (hh:mm AM/PM)
                $timeObj = DateTime::createFromFormat('h:i A', $time);
                if (!$timeObj) {
                    $timeObj = DateTime::createFromFormat('h:i a', $time);
                }
            }
            
            if (!$timeObj) {
                return ["success" => false, "message" => "Invalid time format. Please use either HH:mm (24-hour) or hh:mm AM/PM format."];
            }

            // Convert to 24-hour format for storage
            $time = $timeObj->format('H:i');

            $sql = "UPDATE events 
                   SET title = :title, 
                       description = :description, 
                       date = :date, 
                       time = :time, 
                       location = :location, 
                       max_capacity = :maxCapacity 
                   WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            $stmt->bindParam(':time', $time, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':maxCapacity', $maxCapacity, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ["success" => true, "message" => "Event updated successfully"];
            } else {
                return ["success" => false, "message" => "Failed to update event"];
            }
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            return ["success" => false, "message" => "Database error occurred"];
        } catch (Exception $e) {
            error_log("Error updating event: " . $e->getMessage());
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function delete($id, $force = false) {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Check for attendees
            $sql = "SELECT COUNT(*) as count FROM attendees WHERE event_id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $attendeeCount = (int)$result['count'];
            
            if ($attendeeCount > 0 && !$force) {
                $this->conn->rollBack();
                return [
                    'success' => false, 
                    'message' => 'This event has ' . $attendeeCount . ' registered attendees. Are you sure you want to delete it?',
                    'hasAttendees' => true,
                    'attendeeCount' => $attendeeCount
                ];
            }
            
            // Delete attendees first if they exist
            if ($attendeeCount > 0) {
                $sql = "DELETE FROM attendees WHERE event_id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete attendees");
                }
            }
            
            // Then delete the event
            $sql = "DELETE FROM events WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete event");
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true, 
                'message' => 'Event deleted successfully' . ($attendeeCount > 0 ? ' along with ' . $attendeeCount . ' attendees' : '')
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Database error deleting event: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error deleting event: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getEvent($id, $userId = null) {
        try {
            $sql = "SELECT * FROM events WHERE id = :id";
            $params = [':id' => $id];
            
            if ($userId !== null) {
                $sql .= " AND user_id = :userId";
                $params[':userId'] = $userId;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting event: " . $e->getMessage());
            return null;
        }
    }
    
    public function getEvents($page = 1, $perPage = 10, $sort = 'date', $order = 'ASC', $search = '', $userId = null) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM events";
            $params = [];
            $conditions = [];
            
            if (!empty($search)) {
                $conditions[] = "(title LIKE :search OR description LIKE :search OR location LIKE :search)";
                $searchTerm = "%$search%";
                $params[':search'] = $searchTerm;
            }
            
            if ($userId !== null) {
                $conditions[] = "user_id = :userId";
                $params[':userId'] = $userId;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY $sort $order LIMIT :offset, :perPage";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $stmt = $this->conn->query("SELECT FOUND_ROWS()");
            $total = $stmt->fetchColumn();
            
            return [
                'events' => $events,
                'total' => $total,
                'pages' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log("Error getting events: " . $e->getMessage());
            return ['events' => [], 'total' => 0, 'pages' => 0];
        }
    }
    
    public function getAttendeeCount($eventId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM attendees WHERE event_id = :eventId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error getting attendee count: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getAttendees($eventId) {
        try {
            $sql = "SELECT a.*, u.username 
                   FROM attendees a 
                   LEFT JOIN users u ON a.user_id = u.id 
                   WHERE a.event_id = :eventId 
                   ORDER BY a.registration_date DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting attendees: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAttendeesChunk($eventId, $offset, $limit) {
        try {
            $sql = "SELECT a.name, a.email, a.phone, a.registration_date
                   FROM attendees a 
                   WHERE a.event_id = :eventId 
                   ORDER BY a.registration_date DESC
                   LIMIT :offset, :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting attendees chunk: " . $e->getMessage());
            return [];
        }
    }
    
    public function isAttendeeRegistered($eventId, $userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM attendees WHERE event_id = :eventId AND user_id = :userId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking attendee registration: " . $e->getMessage());
            return false;
        }
    }
    
    public function registerAttendee($eventId, $userId, $name, $email, $phone) {
        try {
            // Check if email is already registered for this event
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM attendees WHERE event_id = ? AND email = ?");
            $stmt->execute([$eventId, $email]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'This email is already registered for this event'];
            }

            // Check event capacity
            $currentCount = $this->getAttendeeCount($eventId);
            $eventData = $this->getEvent($eventId);
            
            if ($currentCount >= $eventData['max_capacity']) {
                return ['success' => false, 'message' => 'Event has reached maximum capacity'];
            }

            // Register the attendee
            $sql = "INSERT INTO attendees (event_id, user_id, name, email, phone, registration_date) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$eventId, $userId, $name, $email, $phone]);

            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            error_log("Error registering attendee: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getUserRegistrations($userId) {
        try {
            $sql = "SELECT e.*, a.registration_date 
                   FROM events e 
                   INNER JOIN attendees a ON e.id = a.event_id 
                   WHERE a.user_id = :userId 
                   ORDER BY e.date ASC, e.time ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user registrations: " . $e->getMessage());
            return [];
        }
    }
    
    public function generateAttendeeReport($event_id) {
        try {
            $stmt = $this->conn->prepare("SELECT name, email, phone, registration_date 
                                        FROM attendees WHERE event_id = :event_id");
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function createEvent($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO events (title, description, date, time, location, max_capacity, created_by)
                VALUES (:title, :description, :date, :time, :location, :max_capacity, :created_by)
            ");

            return $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':date' => $data['date'],
                ':time' => $data['time'],
                ':location' => $data['location'],
                ':max_capacity' => $data['max_capacity'],
                ':created_by' => $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            return false;
        }
    }

    public function getEventById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM events WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting event: " . $e->getMessage());
            return false;
        }
    }

    public function getEventsList($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            $whereClause = "";
            
            if (!empty($search)) {
                $whereClause = "WHERE title LIKE :search OR description LIKE :search OR location LIKE :search";
                $params[':search'] = "%$search%";
            }

            // Get total count
            $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM events $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Get events
            $stmt = $this->conn->prepare("
                SELECT * FROM events 
                $whereClause
                ORDER BY date ASC, time ASC 
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'events' => $events,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Error getting events: " . $e->getMessage());
            return false;
        }
    }

    public function getUserEventRegistrations($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT e.*, a.registration_date 
                FROM events e 
                INNER JOIN attendees a ON e.id = a.event_id 
                WHERE a.user_id = :user_id 
                ORDER BY e.date ASC, e.time ASC
            ");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user registrations: " . $e->getMessage());
            return [];
        }
    }

    public function getEventAttendees($eventId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, u.username, u.email 
                FROM attendees a 
                INNER JOIN users u ON a.user_id = u.id 
                WHERE a.event_id = :event_id 
                ORDER BY a.registration_date ASC
            ");
            $stmt->execute([':event_id' => $eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting attendees: " . $e->getMessage());
            return [];
        }
    }

    public function getEventAttendeeCount($eventId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM attendees WHERE event_id = :event_id
            ");
            $stmt->execute([':event_id' => $eventId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting attendee count: " . $e->getMessage());
            return 0;
        }
    }

    public function isEventAtCapacity($eventId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as current_count, e.max_capacity 
                FROM attendees a 
                INNER JOIN events e ON a.event_id = e.id 
                WHERE a.event_id = :event_id
                GROUP BY e.max_capacity
            ");
            $stmt->execute([':event_id' => $eventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['current_count'] >= $result['max_capacity'];
        } catch (PDOException $e) {
            error_log("Error checking if event is full: " . $e->getMessage());
            return true; // Safer to return true if there's an error
        }
    }

    public function isUserAlreadyRegistered($eventId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM attendees 
                WHERE event_id = :event_id AND user_id = :user_id
            ");
            $stmt->execute([
                ':event_id' => $eventId,
                ':user_id' => $userId
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking user registration: " . $e->getMessage());
            return true; // Safer to return true if there's an error
        }
    }

    public function registerForEvent($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO attendees (event_id, user_id, name, email, phone, registration_date)
                VALUES (:event_id, :user_id, :name, :email, :phone, NOW())
            ");
            
            return $stmt->execute([
                ':event_id' => $data['event_id'],
                ':user_id' => $data['user_id'],
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone']
            ]);
        } catch (PDOException $e) {
            error_log("Error registering attendee: " . $e->getMessage());
            return false;
        }
    }

    public function cancelEventRegistration($eventId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM attendees 
                WHERE event_id = :event_id AND user_id = :user_id
            ");
            return $stmt->execute([
                ':event_id' => $eventId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error canceling registration: " . $e->getMessage());
            return false;
        }
    }

    public function beginTransaction() {
        try {
            $this->conn->beginTransaction();
        } catch (PDOException $e) {
            error_log("Error starting transaction: " . $e->getMessage());
            throw $e;
        }
    }

    public function commit() {
        try {
            $this->conn->commit();
        } catch (PDOException $e) {
            error_log("Error committing transaction: " . $e->getMessage());
            throw $e;
        }
    }

    public function rollback() {
        try {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
        } catch (PDOException $e) {
            error_log("Error rolling back transaction: " . $e->getMessage());
            throw $e;
        }
    }

    public function isUserRegistered($eventId, $userId) {
        try {
            $sql = "SELECT COUNT(*) FROM attendees WHERE event_id = :eventId AND user_id = :userId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking user registration: " . $e->getMessage());
            return false;
        }
    }

    public function cancelRegistration($eventId, $userId) {
        try {
            $sql = "DELETE FROM attendees WHERE event_id = :eventId AND user_id = :userId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error canceling registration: " . $e->getMessage());
            return false;
        }
    }

    public function search($query, $type = 'all', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            $results = [];
            $total = 0;

            // Clean and prepare search query
            $searchTerm = "%" . trim($query) . "%";
            
            if ($type === 'all' || $type === 'events') {
                // Search in events
                $eventSql = "SELECT 
                                e.*, 
                                'event' as type
                            FROM events e
                            WHERE 
                                e.title LIKE :search 
                                OR e.description LIKE :search 
                                OR e.location LIKE :search";
                
                if ($type === 'all') {
                    $eventSql .= " LIMIT " . ($limit / 2) . " OFFSET " . ($offset / 2);
                } else {
                    $eventSql .= " LIMIT :limit OFFSET :offset";
                }

                $stmt = $this->conn->prepare($eventSql);
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
                
                if ($type !== 'all') {
                    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                }
                
                $stmt->execute();
                $eventResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $results = array_merge($results, $eventResults);

                // Get total event count
                $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM events WHERE title LIKE :search OR description LIKE :search OR location LIKE :search");
                $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
                $countStmt->execute();
                $total += $countStmt->fetchColumn();
            }

            if ($type === 'all' || $type === 'attendees') {
                // Search in attendees
                $attendeeSql = "SELECT 
                                    a.*,
                                    e.title as event_title,
                                    'attendee' as type
                                FROM attendees a
                                JOIN events e ON a.event_id = e.id
                                WHERE 
                                    a.name LIKE :search 
                                    OR a.email LIKE :search 
                                    OR a.phone LIKE :search";

                if ($type === 'all') {
                    $attendeeSql .= " LIMIT " . ($limit / 2) . " OFFSET " . ($offset / 2);
                } else {
                    $attendeeSql .= " LIMIT :limit OFFSET :offset";
                }

                $stmt = $this->conn->prepare($attendeeSql);
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
                
                if ($type !== 'all') {
                    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                }
                
                $stmt->execute();
                $attendeeResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $results = array_merge($results, $attendeeResults);

                // Get total attendee count
                $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM attendees WHERE name LIKE :search OR email LIKE :search OR phone LIKE :search");
                $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
                $countStmt->execute();
                $total += $countStmt->fetchColumn();
            }

            // Calculate total pages
            $totalPages = ceil($total / $limit);

            return [
                'data' => $results,
                'total' => $total,
                'total_pages' => $totalPages,
                'current_page' => $page
            ];

        } catch (PDOException $e) {
            error_log("Error searching: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'total_pages' => 0,
                'current_page' => $page
            ];
        }
    }
}
?>
