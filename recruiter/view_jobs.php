<?php
    // view_jobs.php
    require 'db.php';
    include 'header.php';
    include 'sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div id="content">
<div class="container mt-5">
    <h1>Job Posts</h1>

    <div class="container mt-3">
        <div id="notification" class="alert d-none" role="alert"></div>
    </div>

    <!-- Search Bar -->
    <div class="mb-4">
        <input type="text" id="searchQuery" class="form-control" placeholder="Search job posts by title or company">
    </div>

    <a href="job_postings.php" class="btn btn-primary" style="margin-bottom: 20px;">Create New Job Posting</a>

    <!-- Tabs for Job Post Status -->
    <ul class="nav nav-tabs" id="jobTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="open-tab" data-bs-toggle="tab" data-bs-target="#open" type="button" role="tab">Open</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="closed-tab" data-bs-toggle="tab" data-bs-target="#closed" type="button" role="tab">Closed</button>
        </li>
    </ul>

    <div class="tab-content mt-4">
        <!-- Job Cards for Each Status -->
        <div class="tab-pane fade show active" id="pending" role="tabpanel"></div>
        <div class="tab-pane fade" id="open" role="tabpanel"></div>
        <div class="tab-pane fade" id="closed" role="tabpanel"></div>
    </div>
</div>
</div>

<!-- Modal for Job Details -->
<div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobDetailsLabel">Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="jobDetailsContent">
                <!-- Job details and metrics will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function fetchJobs(status, query = '') {
        $.ajax({
            url: 'fetch_jobs.php',
            method: 'POST',
            data: { status: status, query: query },
            success: function (data) {
                $(`#${status}`).html(data);
            }
        });
    }

    function showNotification(message, type) {
        const notification = $('#notification');
        notification
            .removeClass('d-none alert-success alert-danger')
            .addClass(`alert-${type}`)
            .text(message)
            .fadeIn();

        // Hide the notification after 3 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.addClass('d-none'));
        }, 3000);
    }

    // Fetch jobs for each tab
    fetchJobs('pending');
    fetchJobs('open');
    fetchJobs('closed');

    $(document).on('change', '.status-dropdown', function () {
        const jobId = $(this).data('id');
        const newStatus = $(this).val();
        const activeTab = $('.nav-link.active').attr('id').split('-')[0]; // Get the active tab status

        $.ajax({
            url: 'fetch_jobs.php',
            type: 'POST',
            data: { update_job_status: true, job_id: jobId, new_status: newStatus },
            success: function (response) {
                if (response.trim() === 'Success') {
                    showNotification('Job status updated successfully.', 'success');
                    fetchJobs(activeTab); // Refresh the active tab
                } else {
                    showNotification('Error: ' + response, 'danger');
                }
            }
        });
    });

    // Handle tab click
    $('.nav-link').on('click', function() {
        const status = $(this).attr('id').split('-')[0];
        fetchJobs(status);
    });

    // Handle search
    $('#searchQuery').on('keyup', function() {
        const query = $(this).val();
        $('.tab-pane.active').each(function() {
            const status = $(this).attr('id');
            fetchJobs(status, query);
        });
    });

    // View More Details
    $(document).on('click', '.view-more', function() {
        const jobId = $(this).data('id');
        $.ajax({
            url: 'fetch_jobs.php',
            method: 'POST',
            data: { job_id: jobId },
            success: function(data) {
                $('#jobDetailsContent').html(data);
                $('#jobDetailsModal').modal('show');
            }
        });
    });
});
</script>
</body>
</html>
