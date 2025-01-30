$(document).ready(function() {
    // Initialize Bootstrap modal
    const attendeesModal = new bootstrap.Modal(document.getElementById('attendeesModal'));
    
    // Load attendee counts
    function loadAttendeeCounts() {
        $('.download-csv').each(function() {
            const eventId = $(this).data('id');
            const countBadge = $(`#attendee-count-${eventId}`);
            
            $.get(`api/attendees.php?event_id=${eventId}&count=true`)
                .done(function(response) {
                    if (response.success) {
                        countBadge.text(response.count);
                        countBadge.removeClass('bg-info').addClass(
                            response.count === 0 ? 'bg-secondary' : 'bg-success'
                        );
                    }
                })
                .fail(function() {
                    countBadge.text('Error');
                    countBadge.removeClass('bg-info').addClass('bg-danger');
                });
        });
    }

    // Download CSV
    $('.download-csv').click(function() {
        const eventId = $(this).data('id');
        window.location.href = `api/reports.php?event_id=${eventId}&format=csv`;
    });

    // View Attendees
    $('.view-attendees').click(function() {
        const eventId = $(this).data('id');
        const tbody = $('#attendeesList');
        
        tbody.html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
        attendeesModal.show();

        $.get(`api/attendees.php?event_id=${eventId}`)
            .done(function(response) {
                if (response.success && response.attendees) {
                    if (response.attendees.length === 0) {
                        tbody.html('<tr><td colspan="4" class="text-center">No attendees registered yet</td></tr>');
                    } else {
                        let html = '';
                        response.attendees.forEach(function(attendee) {
                            html += `
                                <tr>
                                    <td>${attendee.name || 'N/A'}</td>
                                    <td>${attendee.email || 'N/A'}</td>
                                    <td>${attendee.phone || 'N/A'}</td>
                                    <td>${attendee.registration_date || 'N/A'}</td>
                                </tr>
                            `;
                        });
                        tbody.html(html);
                    }
                } else {
                    tbody.html('<tr><td colspan="4" class="text-center text-danger">Error loading attendees: ' + (response.message || 'Unknown error') + '</td></tr>');
                }
            })
            .fail(function(jqXHR) {
                tbody.html('<tr><td colspan="4" class="text-center text-danger">Failed to load attendees. Please try again.</td></tr>');
                console.error('Error:', jqXHR.responseText);
            });
    });

    let eventIdToDelete = null;
    let deleteForce = false;

    // Delete event handler
    $('.delete-event').click(function() {
        eventIdToDelete = $(this).data('id');
        deleteForce = false;
        deleteEvent();
    });

    // Confirm delete button handler
    $('#confirmDelete').click(function() {
        deleteForce = true;
        deleteEvent();
    });

    function deleteEvent() {
        // Show loading state
        const deleteButton = $('.delete-event[data-id="' + eventIdToDelete + '"]');
        const originalText = deleteButton.html();
        deleteButton.html('<i class="bi bi-hourglass-split"></i> Deleting...').prop('disabled', true);
        
        $.ajax({
            url: 'api/events.php',
            type: 'POST',
            contentType: 'application/x-www-form-urlencoded',
            data: {
                action: 'delete',
                event_id: eventIdToDelete,
                force: deleteForce ? 1 : 0
            },
            success: function(response) {
                console.log('Delete response:', response);
                
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (!response.success && response.hasAttendees && !deleteForce) {
                        // Show confirmation modal if event has attendees
                        $('#deleteConfirmBody').html(response.message);
                        $('#deleteConfirmModal').modal('show');
                    } else {
                        if (response.success) {
                            // Show success message and refresh
                            const successAlert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                response.message +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                '</div>');
                            $('.container').first().prepend(successAlert);
                            
                            // Remove the event row with animation
                            deleteButton.closest('tr').fadeOut(400, function() {
                                $(this).remove();
                                // Reload if no events left
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            // Show error message
                            const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                (response.message || 'Error deleting event') +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                '</div>');
                            $('.container').first().prepend(errorAlert);
                        }
                        $('#deleteConfirmModal').modal('hide');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        'Error occurred while processing the response' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('.container').first().prepend(errorAlert);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                // Show error message
                const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    'Error occurred while deleting the event. Please try again.' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('.container').first().prepend(errorAlert);
            },
            complete: function() {
                // Reset button state
                deleteButton.html(originalText).prop('disabled', false);
                if (deleteForce) {
                    $('#deleteConfirmModal').modal('hide');
                }
            }
        });
    }

    // Refresh button
    $('#refreshData').click(function() {
        loadAttendeeCounts();
    });

    // Initial load
    loadAttendeeCounts();
});
