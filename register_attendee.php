<?php
session_start();
require_once 'includes/Auth.php';
require_once 'includes/Event.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['event_id'])) {
    header('Location: dashboard.php');
    exit();
}

$event = new Event();
$eventData = $event->getEvent($_GET['event_id'], $_SESSION['user_id']);

if (!$eventData) {
    header('Location: dashboard.php?error=not_found');
    exit();
}

// Get current attendee count
$currentCount = $event->getAttendeeCount($_GET['event_id']);
$remainingSlots = $eventData['max_capacity'] - $currentCount;

if ($remainingSlots <= 0) {
    header('Location: dashboard.php?error=event_full');
    exit();
}

// Get user data for pre-filling
$userData = $auth->getUserData($_SESSION['user_id']);

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Register for Event</h4>
                    <span class="badge bg-info">
                       Total Capacity <?php echo $eventData['max_capacity']; ?> 
                    </span>
                    <span class="badge bg-info">
                        <?php echo $remainingSlots; ?> slots remaining
                    </span>
                </div>
                <div class="card-body">
                    <div class="event-details mb-4">
                        <h5><?php echo htmlspecialchars($eventData['title']); ?></h5>
                        <p class="text-muted">
                            <i class="bi bi-calendar"></i> <?php echo htmlspecialchars($eventData['date']); ?> at <?php echo htmlspecialchars($eventData['time']); ?><br>
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($eventData['location']); ?>
                        </p>
                    </div>

                    <form id="registrationForm">
                        <input type="hidden" name="event_id" value="<?php echo $_GET['event_id']; ?>">
                        
                        <div id="attendees-container">
                            <!-- First attendee (pre-filled with user data) -->
                            <div class="attendee-form mb-4">
                                <h6 class="mb-3">Attendee <?php echo $currentCount + 1; ?></h6>
                                <div class="mb-3">
                                    <label for="name_1" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name_1" name="attendees[0][name]" 
                                            required>
                                </div>
                                <div class="mb-3">
                                    <label for="email_1" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email_1" name="attendees[0][email]" 
                                            required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone_1" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone_1" name="attendees[0][phone]" 
                                           pattern="[0-9+\-\s()]{10,15}" title="Phone number should be 10-15 digits" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="button" id="addAttendeeBtn" class="btn btn-outline-primary" 
                                    <?php echo $remainingSlots <= 1 ? 'disabled' : ''; ?>>
                                <i class="bi bi-plus-circle"></i> Add Another Attendee
                            </button>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Register
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

<!-- Add jQuery and SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize with current attendee count from database
    let attendeeCount = <?php echo $currentCount; ?>;
    const maxAttendees = <?php echo $eventData['max_capacity']; ?>;
    const remainingSlots = <?php echo $remainingSlots; ?>;
    const container = $('#attendees-container');
    const addBtn = $('#addAttendeeBtn');
    
    // Add new attendee form
    $('#addAttendeeBtn').on('click', function() {
        if (attendeeCount >= maxAttendees) {
            return;
        }
        
        attendeeCount++;
        const newForm = `
            <div class="attendee-form mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Attendee ${attendeeCount}</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-attendee">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="mb-3">
                    <label for="name_${attendeeCount}" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name_${attendeeCount}" 
                           name="attendees[${attendeeCount-1}][name]" required>
                </div>
                <div class="mb-3">
                    <label for="email_${attendeeCount}" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email_${attendeeCount}" 
                           name="attendees[${attendeeCount-1}][email]" required>
                </div>
                <div class="mb-3">
                    <label for="phone_${attendeeCount}" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone_${attendeeCount}" 
                           name="attendees[${attendeeCount-1}][phone]" 
                           pattern="[0-9+\\-\\s()]{10,15}" 
                           title="Phone number should be 10-15 digits" required>
                </div>
            </div>
        `;
        
        container.append(newForm);
        
        if (attendeeCount >= maxAttendees) {
            addBtn.prop('disabled', true);
        }

        // Update form indices after removal
        updateFormIndices();
    });
    
    // Remove attendee form
    container.on('click', '.remove-attendee', function() {
        $(this).closest('.attendee-form').remove();
        attendeeCount--;
        addBtn.prop('disabled', false);
        
        // Update form indices after removal
        updateFormIndices();
    });

    // Update form indices to ensure they are sequential
    function updateFormIndices() {
        $('.attendee-form').each(function(index) {
            $(this).find('h6').text('Attendee ' + (index + 1));
            $(this).find('input').each(function() {
                const oldName = $(this).attr('name');
                if (oldName) {
                    const newName = oldName.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    // Handle form submission
    $('#registrationForm').on('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true)
                .html('<i class="bi bi-hourglass"></i> Registering...');
        
        try {
            const formData = new FormData(this);
            const jsonData = {
                event_id: formData.get('event_id'),
                attendees: []
            };
            
            // Group attendee data
            const attendees = {};
            for (const [key, value] of formData.entries()) {
                const matches = key.match(/attendees\[(\d+)\]\[(\w+)\]/);
                if (matches) {
                    const [_, index, field] = matches;
                    if (!attendees[index]) attendees[index] = {};
                    attendees[index][field] = value.trim();
                }
            }
            
            // Convert to array and remove empty slots
            jsonData.attendees = Object.values(attendees).filter(Boolean);

            // Validate we have at least one attendee
            if (jsonData.attendees.length === 0) {
                throw new Error('Please add at least one attendee');
            }

            // Validate all required fields
            for (const attendee of jsonData.attendees) {
                if (!attendee.name || !attendee.email || !attendee.phone) {
                    throw new Error('Please fill in all fields for each attendee');
                }
            }
            
            const response = await fetch('api/attendees.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            });
            
            let result;
            try {
                result = await response.json();
            } catch (e) {
                throw new Error('Invalid response from server');
            }
            
            if (!response.ok) {
                throw new Error(result.message || `HTTP error! status: ${response.status}`);
            }
            
            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message || 'Registration successful!',
                    confirmButtonText: 'Go to Dashboard'
                });
                window.location.href = 'dashboard.php';
            } else {
                throw new Error(result.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Registration error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: error.message || 'An error occurred during registration'
            });
        } finally {
            submitBtn.prop('disabled', false)
                    .html('<i class="bi bi-check-circle"></i> Register');
        }
    });
});
</script>
</body>
</html>
