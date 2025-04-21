<?php
require 'db.php'; // Include database connection
require 'header.php';
require 'sidebar.php';

// Fetch job posts
$jobPostsQuery = $pdo->query("
    SELECT 
        job_post_id, 
        job_title, 
        partner_company, 
        location, 
        min_salary, 
        max_salary, 
        description, 
        openings, 
        deadline, 
        status 
    FROM job_posts
");
$jobPosts = $jobPostsQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch applicants for each job post
$applicantsQuery = $pdo->query("
    SELECT 
        ja.application_id,
        ja.job_post_id,
        ja.user_id,
        ja.status,
        ja.applied_at,
        ja.screened_at,
        ja.interviewed_at,
        ja.offered_at,
        ja.deployed_at,
        ja.rejected_at,
        ja.withdrawn_at,
        ja.duration_applied_to_screened,
        ja.duration_screened_to_interviewed,
        ja.duration_interviewed_to_offered,
        ja.duration_offered_to_hired,
        ja.total_duration,
        u.first_name,
        u.last_name,
        jp.job_title
    FROM job_applications ja
    INNER JOIN users u ON ja.user_id = u.user_id
    INNER JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
    ORDER BY jp.job_title, ja.applied_at
");
$applicants = $applicantsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Tracking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div id='content'>
<div class="container mt-4">
    <h1>Applicant Tracking</h1>

    <!-- Search Bar and Filter -->
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="searchBar" class="form-control" placeholder="Search by applicant name...">
        </div>
        <div class="col-md-6">
            <select id="stageFilter" class="form-select">
                <option value="">Filter by stage</option>
                <option value="Applied">Applied</option>
                <option value="Screened">Screened</option>
                <option value="Interviewed">Interviewed</option>
                <option value="Offered">Offered</option>
                <option value="Deployed">Deployed</option>
                <option value="Rejected">Rejected</option>
                <option value="Withdrawn">Withdrawn</option>
            </select>
        </div>
    </div>

    <?php if ($jobPosts): ?>
        <?php foreach ($jobPosts as $jobPost): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars($jobPost['job_title']) ?> (<?= htmlspecialchars($jobPost['partner_company']) ?>)</h5>
                </div>
                <div class="card-body">
                    <p><strong>Location:</strong> <?= htmlspecialchars($jobPost['location']) ?></p>
                    <p><strong>Salary Range:</strong> <?= htmlspecialchars($jobPost['min_salary']) ?> - <?= htmlspecialchars($jobPost['max_salary']) ?></p>
                    <p><strong>Openings:</strong> <?= htmlspecialchars($jobPost['openings']) ?></p>
                    <p><strong>Deadline:</strong> <?= htmlspecialchars($jobPost['deadline']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($jobPost['status']) ?></p>
                    <hr>
                    <h6>Applicants</h6>
                    <table class="table table-bordered" id="applicantTable">
                        <thead>
                            <tr>
                                <th>Applicant Name</th>
                                <th>Status</th>
                                <th>Applied At</th>
                                <th>Screened At</th>
                                <th>Interviewed At</th>
                                <th>Offered At</th>
                                <th>Deployed At</th>
                                <th>Rejected At</th>
                                <th>Withdrawn At</th>
                                <th>Total Duration (Days)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hasApplicants = false;
                            foreach ($applicants as $applicant): 
                                if ($applicant['job_post_id'] == $jobPost['job_post_id']):
                                    $hasApplicants = true;

                                    // Calculate durations for each stage
                                    $durationAppliedToScreened = !empty($applicant['applied_at']) && !empty($applicant['screened_at']) 
                                        ? (new DateTime($applicant['screened_at']))->diff(new DateTime($applicant['applied_at']))->days . ' days' 
                                        : 'N/A';

                                    $durationScreenedToInterviewed = !empty($applicant['screened_at']) && !empty($applicant['interviewed_at']) 
                                        ? (new DateTime($applicant['interviewed_at']))->diff(new DateTime($applicant['screened_at']))->days . ' days' 
                                        : 'N/A';

                                    $durationInterviewedToOffered = !empty($applicant['interviewed_at']) && !empty($applicant['offered_at']) 
                                        ? (new DateTime($applicant['offered_at']))->diff(new DateTime($applicant['interviewed_at']))->days . ' days' 
                                        : 'N/A';

                                    $durationOfferedToDeployed = !empty($applicant['offered_at']) && !empty($applicant['deployed_at']) 
                                        ? (new DateTime($applicant['deployed_at']))->diff(new DateTime($applicant['offered_at']))->days . ' days' 
                                        : 'N/A';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td>
                                    <td><?= htmlspecialchars($applicant['status']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($applicant['applied_at'] ?? 'N/A') ?><br>
                                        <span class="text-muted fs-6">Duration: <?= htmlspecialchars($durationAppliedToScreened) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($applicant['screened_at'] ?? 'N/A') ?><br>
                                        <span class="text-muted fs-6">Duration: <?= htmlspecialchars($durationScreenedToInterviewed) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($applicant['interviewed_at'] ?? 'N/A') ?><br>
                                        <span class="text-muted fs-6">Duration: <?= htmlspecialchars($durationInterviewedToOffered) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($applicant['offered_at'] ?? 'N/A') ?><br>
                                        <span class="text-muted fs-6">Duration: <?= htmlspecialchars($durationOfferedToDeployed) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($applicant['deployed_at'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($applicant['rejected_at'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($applicant['withdrawn_at'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($applicant['total_duration'] ?? 'N/A') ?> days</td>
                                    <td>
                                        <a href="view_application_details.php?application_id=<?= htmlspecialchars($applicant['application_id']) ?>" class="btn btn-primary btn-sm">View Application</a>
                                    </td>
                                </tr>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <?php if (!$hasApplicants): ?>
                                <tr>
                                    <td colspan="12" class="text-center">No applicants for this job post.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No job posts available.</p>
    <?php endif; ?>
</div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('searchBar').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#applicantTable tbody tr');

        rows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            row.style.display = name.includes(searchValue) ? '' : 'none';
        });
    });

    document.getElementById('stageFilter').addEventListener('change', function () {
        const filterValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#applicantTable tbody tr');

        rows.forEach(row => {
            const stage = row.cells[1].textContent.toLowerCase();
            row.style.display = filterValue === '' || stage.includes(filterValue) ? '' : 'none';
        });
    });
</script>
</body>
</html>