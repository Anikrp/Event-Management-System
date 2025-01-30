$(document).ready(function() {
    // Create Event
    $('#saveEvent').click(function() {
        const form = $('#createEventForm')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => data[key] = value);

        $.ajax({
            url: 'api/events.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert('Event created successfully!');
                    $('#createEventModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error creating event';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {}
                alert(errorMessage);
            }
        });
    });

    // Edit Event
    $('.edit-event').click(function() {
        const eventId = $(this).data('id');
        
        $.ajax({
            url: `api/events.php?id=${eventId}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    populateEditForm(response.event);
                    $('#editEventModal').modal('show');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error fetching event details');
            }
        });
    });

    // Update Event
    $('#updateEvent').click(function() {
        const form = $('#editEventForm')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => data[key] = value);

        const eventId = $('#edit_event_id').val();

        $.ajax({
            url: `api/events.php?id=${eventId}`,
            type: 'PUT',
            data: JSON.stringify(data),
            contentType: 'application/json',
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert('Event updated successfully!');
                    $('#editEventModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error updating event';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {}
                alert(errorMessage);
            }
        });
    });

    // View Event Handler
    $('.view-event').click(function() {
        const eventId = $(this).data('id');
        
        $.ajax({
            url: `api/events.php?id=${eventId}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const event = response.event;
                    
                    // Update modal content
                    $('#view_title').text(event.title);
                    $('#view_date').text(event.date);
                    $('#view_time').text(event.time);
                    $('#view_location').text(event.location);
                    $('#view_description').text(event.description);
                    $('#view_max_capacity').text(event.max_capacity);
                    
                    // Get current attendee count
                    $.get(`api/attendees.php?event_id=${eventId}&count=true`, function(countResponse) {
                        if (countResponse.success) {
                            const count = countResponse.count;
                            const percentage = (count / event.max_capacity) * 100;
                            
                            $('#view_attendee_count').text(count);
                            $('#capacity_bar').css('width', `${percentage}%`);
                            
                            if (count >= event.max_capacity) {
                                $('#capacity_bar').removeClass('bg-primary').addClass('bg-danger');
                                $('#registration_status').html('<div class="alert alert-warning">This event is full</div>');
                                $('#register_button').addClass('disabled');
                            } else {
                                $('#capacity_bar').removeClass('bg-danger').addClass('bg-primary');
                                $('#registration_status').html(`<div class="alert alert-info">${event.max_capacity - count} spots remaining</div>`);
                                $('#register_button').removeClass('disabled');
                            }
                            
                            // Update registration button link
                            $('#register_button').attr('href', `register_attendee.php?event_id=${eventId}`);
                        }
                    });
                    
                    // Show modal
                    $('#viewEventModal').modal('show');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error fetching event details');
            }
        });
    });

    // Delete Event
    $('.delete-event').click(function() {
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }

        const eventId = $(this).data('id');
        const button = $(this);
        
        // Show loading state
        const originalText = button.html();
        button.html('<i class="bi bi-hourglass-split"></i> Deleting...').prop('disabled', true);
        
        $.ajax({
            url: 'api/events.php',
            type: 'POST',
            data: {
                action: 'delete',
                event_id: eventId,
                force: false
            },
            success: function(response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (!response.success && response.hasAttendees) {
                        if (confirm(response.message)) {
                            // If user confirms, delete with force
                            deleteEventWithForce(eventId);
                        } else {
                            button.html(originalText).prop('disabled', false);
                        }
                    } else if (response.success) {
                        // Show success message
                        const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>');
                        $('.container').first().prepend(alert);
                        
                        // Remove the row with animation
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                            if ($('tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert('Error: ' + response.message);
                        button.html(originalText).prop('disabled', false);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Error processing response');
                    button.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', {status, error, response: xhr.responseText});
                alert('Error deleting event');
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    function deleteEventWithForce(eventId) {
        $.ajax({
            url: 'api/events.php',
            type: 'POST',
            data: {
                action: 'delete',
                event_id: eventId,
                force: true
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error deleting event');
            }
        });
    }

    // Search form
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const searchTerm = $(this).find('input[name="search"]').val();
        window.location.href = `dashboard.php?search=${encodeURIComponent(searchTerm)}`;
    });

    // Clear form when modal is hidden
    $('#editEventModal').on('hidden.bs.modal', function() {
        $('#editEventForm')[0].reset();
    });

    // Handle export attendees
    $('.export-attendees').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const url = btn.attr('href');
        
        // Show loading state
        const originalHtml = btn.html();
        btn.prop('disabled', true)
           .html('<i class="bi bi-hourglass"></i>');
        
        // Download file
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Export failed');
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.headers.get('content-disposition')
                    ? response.headers.get('content-disposition').split('filename=')[1].replace(/"/g, '')
                    : 'attendees.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Export error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Export Failed',
                    text: 'Failed to export attendee list. Please try again.'
                });
            })
            .finally(() => {
                // Reset button state
                btn.prop('disabled', false)
                   .html(originalHtml);
            });
    });

    // Helper function to show event details
    function showEventDetails(event) {
        const modalHtml = `
            <div class="modal fade" id="viewEventModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${event.title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Description:</strong> ${event.description}</p>
                            <p><strong>Date:</strong> ${event.date}</p>
                            <p><strong>Time:</strong> ${event.time}</p>
                            <p><strong>Location:</strong> ${event.location}</p>
                            <p><strong>Maximum Capacity:</strong> ${event.max_capacity}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`;

        // Remove existing modal if any
        $('#viewEventModal').remove();
        
        // Add new modal to body and show it
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal($('#viewEventModal'));
        modal.show();
    }

    // Helper function to populate edit form
    function populateEditForm(event) {
        $('#edit_event_id').val(event.id);
        $('#edit_title').val(event.title);
        $('#edit_description').val(event.description);
        $('#edit_date').val(event.date);
        $('#edit_time').val(event.time);
        $('#edit_location').val(event.location);
        $('#edit_max_capacity').val(event.max_capacity);
    }
});
