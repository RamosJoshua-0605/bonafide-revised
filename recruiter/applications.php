<?php
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';
require 'auth.php';

// Constants
$itemsPerPage = 5;

// Fetch all job posts with application counts
$jobDetailsQuery = $pdo->prepare("
    SELECT jp.*, 
           (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_post_id = jp.job_post_id) AS application_count
    FROM job_posts jp
");
$jobDetailsQuery->execute();
$jobDetails = $jobDetailsQuery->fetchAll(PDO::FETCH_ASSOC);

// Function to fetch paginated applications
function getApplications($pdo, $jobPostId, $page, $itemsPerPage, $filter = '') {
    $offset = ($page - 1) * $itemsPerPage;

    // Modify the query to include the filter
    $query = "
        SELECT ja.application_id, ja.job_post_id, ja.user_id, ja.status, ja.work_experience, ja.applied_at, ja.screened_at, ja.interviewed_at, ja.offered_at, ja.deployed_at, ja.rejected_at, ja.withdrawn_at, ja.comments,
               u.first_name, u.last_name, u.age, u.email_address, u.facebook_messenger_link, u.cellphone_number,
               u.id_picture_reference, u.address, u.birthday, u.sex, u.height_ft, u.marital_status, u.religion,
               je.highest_educational_attainment
        FROM job_applications ja
        JOIN users u ON ja.user_id = u.user_id
        LEFT JOIN user_education je ON u.user_id = je.user_id
        WHERE ja.job_post_id = :job_post_id
    ";

    if (!empty($filter)) {
        $query .= " AND ja.status = :filter"; // Add filter condition
    }

    $query .= " LIMIT :itemsPerPage OFFSET :offset";

    $applicationsQuery = $pdo->prepare($query);
    $applicationsQuery->bindValue('job_post_id', $jobPostId, PDO::PARAM_INT);
    if (!empty($filter)) {
        $applicationsQuery->bindValue('filter', $filter, PDO::PARAM_STR);
    }
    $applicationsQuery->bindValue('itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
    $applicationsQuery->bindValue('offset', $offset, PDO::PARAM_INT);
    $applicationsQuery->execute();

    return $applicationsQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Function to calculate score
function calculateScore($user, $jobPost, $answers) {
    $score = 0;
    $ageRange = explode('-', $jobPost['preferred_age_range']);
    $ageMatch = $user['age'] >= $ageRange[0] && $user['age'] <= $ageRange[1];
    if ($ageMatch) $score += 25;
    if ($user['work_experience'] >= $jobPost['preferred_work_experience']) $score += 25;
    if ($user['highest_educational_attainment'] == $jobPost['preferred_educational_level']) $score += 25;
    foreach ($answers as $answer) if ($answer['is_correct'] == 1) $score += 25;
    return $score;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .badge-small {
            font-size: 0.75rem; /* Smaller font size */
            padding: 0.25em 0.5em; /* Smaller padding */
        }
    </style>
</head>
<body>
<div id="content">
    <div class="container mt-5">
    <h1 class="mb-4">Job Pipeline</h1>
    <div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Job Title</th>
                <th>Total Applicants</th>
                <th>Applicants in Shortlist</th>
                <th>Applicants in Screening</th>
                <th>Applicants in Interview</th>
                <th>Applicants Offered</th>
                <th>Applicants Deployed</th>
                <th>Applicants Rejected</th>
                <th>Applicants Withdrawn</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobDetails as $job): ?>
                <?php
                // Fetch counts for each stage
                $stageCountsQuery = $pdo->prepare("
                    SELECT 
                        COUNT(*) AS total_applicants,
                        SUM(CASE WHEN status IN ('Screened', 'Pending') THEN 1 ELSE 0 END) AS screening_count,                        SUM(CASE WHEN status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted_count,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
                        SUM(CASE WHEN status = 'Interviewed' THEN 1 ELSE 0 END) AS interview_count,
                        SUM(CASE WHEN status = 'Offered' THEN 1 ELSE 0 END) AS offered_count,
                        SUM(CASE WHEN status = 'Hired' THEN 1 ELSE 0 END) AS deployed_count,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
                        SUM(CASE WHEN status = 'Withdrawn' THEN 1 ELSE 0 END) AS withdrawn_count
                    FROM job_applications
                    WHERE job_post_id = :job_post_id
                ");
                $stageCountsQuery->execute(['job_post_id' => $job['job_post_id']]);
                $stageCounts = $stageCountsQuery->fetch(PDO::FETCH_ASSOC);
                ?>
                <tr>
                    <td><?= htmlspecialchars($job['job_title']) ?></td>
                    <td><?= $stageCounts['total_applicants'] ?? 0 ?></td>
                    <td><?= $stageCounts['shortlisted_count'] ?? 0 ?></td>
                    <td><?= $stageCounts['screening_count'] ?? 0 ?></td>
                    <td><?= $stageCounts['interview_count'] ?? 0 ?></td>
                    <td><?= $stageCounts['offered_count'] ?? 0 ?></td>
                    <td><?= $stageCounts['deployed_count'] ?? 0 ?></td>
                    <td><?= $stageCounts['rejected_count'] ?? 0 ?></td>
                    <td><?= $stageCounts['withdrawn_count'] ?? 0 ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
        <h1 class="mb-4">Job Applications</h1>

        <!-- Job Posts -->
        <?php foreach ($jobDetails as $job): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h2>
                        <?= htmlspecialchars($job['job_title']) ?>
                        <span class="badge bg-secondary badge-small"><?= $job['application_count'] ?> Applications</span>                    
                        <button class="btn btn-link float-end" data-bs-toggle="collapse" data-bs-target="#job-<?= $job['job_post_id'] ?>" aria-expanded="false" aria-controls="job-<?= $job['job_post_id'] ?>">Toggle</button>
                    </h2>
                </div>
                <div id="job-<?= $job['job_post_id'] ?>" class="collapse">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="filter-status-<?= $job['job_post_id'] ?>" class="form-label">Filter by Status</label>
                            <select id="filter-status-<?= $job['job_post_id'] ?>" class="form-select filter-status" data-job-id="<?= $job['job_post_id'] ?>">
                                <option value="" selected>All</option>
                                <option value="Shortlisted">Shortlisted</option>
                                <option value="Pending">Pending</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Screened">Screened</option>
                                <option value="Interviewed">Interviewed</option>
                                <option value="Offered">Offered</option>
                                <option value="Hired">Hired</option>
                                <option value="Withdrawn">Withdrawn</option>
                            </select>
                        </div>
                        <div id="applications-container-<?= $job['job_post_id'] ?>"></div>
                        <div id="pagination-container-<?= $job['job_post_id'] ?>" class="pagination-container mt-3"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
$(document).ready(function () {
    function loadApplications(jobPostId, page) {
        const selectedFilter = $(`#filter-status-${jobPostId}`).val(); // Get the selected filter for the specific job post

        $.ajax({
            url: 'fetch_applications.php',
            method: 'GET',
            data: { 
                job_post_id: jobPostId, 
                page: page, 
                filter: selectedFilter // Pass the filter to the server
            },
            success: function (response) {
                try {
                    const data = JSON.parse(response);
                    const container = $(`#applications-container-${jobPostId}`);
                    const pagination = $(`#pagination-container-${jobPostId}`);

                    // Clear existing content
                    container.empty();
                    pagination.empty();

                    if (data.applications.length > 0) {
                        // Group applications by status and add subheaders
                        let currentStatus = null;

                        data.applications.forEach(app => {
                            if (currentStatus !== app.status) {
                                // Add a subheader for the new status group
                                currentStatus = app.status;
                                container.append(`
                                    <h4 class="mt-4">${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}</h4>
                                    <hr>
                                `);
                            }

                            // Add the application card
                            container.append(`
                                <div class="border p-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>${app.first_name} ${app.last_name}</strong>
                                            <p>Email: ${app.email_address}</p>
                                            <p>Phone: ${app.cellphone_number}</p>
                                            <p>Address: ${app.address}</p>
                                            <p><strong>Score: ${app.score}</strong></p>
                                            <p><strong>Comments: ${app.comments ? app.comments : 'No comments'}</strong></p>
                                        </div>
                                        <div>
                                            <a href="view_application_details.php?application_id=${app.application_id}" class="btn btn-info btn-sm">View Application</a>
                                        </div>
                                    </div>
                                </div>
                            `);
                        });

                        // Populate pagination
                        for (let i = 1; i <= data.totalPages; i++) {
                            pagination.append(`
                                <button class="btn btn-sm btn-primary ${i === data.currentPage ? 'active' : ''}" 
                                        data-page="${i}" 
                                        data-job-id="${jobPostId}">
                                    ${i}
                                </button>
                            `);
                        }
                    } else {
                        container.append('<p>No applications found.</p>');
                    }
                } catch (err) {
                    console.error('Error parsing JSON:', err);
                }
            },
            error: function (error) {
                console.error('AJAX error:', error);
            }
        });
    }

    $(document).on('click', '.pagination-container button', function () {
        const jobPostId = $(this).data('job-id');
        const page = $(this).data('page');
        loadApplications(jobPostId, page);
    });

    <?php foreach ($jobDetails as $job): ?>
    loadApplications(<?= $job['job_post_id'] ?>, 1);
    <?php endforeach; ?>

    // Event listener for filter change
    $(document).on('change', '.filter-status', function () {
        const jobPostId = $(this).data('job-id'); // Get the job post ID from the dropdown
        const page = 1; // Reset to the first page when the filter changes
        loadApplications(jobPostId, page);
    });

     // Event listener for expanding/collapsing job details
     $(document).on('click', '[data-bs-toggle="collapse"]', function () {
        const target = $(this).data('bs-target');
        $(target).collapse('toggle');
    });
});
</script>
</body>
</html>
