<?php 
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';

require 'auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id']; // Retrieve user ID from session

$events = [];

try {
    // Fetch interview details for the logged-in user
    $interviewQuery = $pdo->prepare("
        SELECT i.interview_date AS date, 'Interview' AS type, i.interview_id AS id, ja.application_id
        FROM interview_details i
        JOIN job_applications ja ON i.application_id = ja.application_id
        WHERE ja.user_id = :user_id AND ja.status NOT IN ('Withdrawn', 'Rejected', 'Hired')
    ");
    $interviewQuery->execute(['user_id' => $user_id]);
    $interviewEvents = $interviewQuery->fetchAll(PDO::FETCH_ASSOC);
    $events = array_merge($events, $interviewEvents);

    // Fetch offer details for the logged-in user
    $offerQuery = $pdo->prepare("
        SELECT o.start_date AS date, 'Offer' AS type, o.offer_id AS id, ja.application_id
        FROM offer_details o
        JOIN job_applications ja ON o.application_id = ja.application_id
        WHERE ja.user_id = :user_id AND ja.status NOT IN ('Withdrawn', 'Rejected', 'Hired')
    ");
    $offerQuery->execute(['user_id' => $user_id]);
    $offerEvents = $offerQuery->fetchAll(PDO::FETCH_ASSOC);
    $events = array_merge($events, $offerEvents);

    // Fetch deployment details for the logged-in user
    $deploymentQuery = $pdo->prepare("
        SELECT d.deployment_date AS date, 'Deployment' AS type, d.deployment_id AS id, ja.application_id
        FROM deployment_details d
        JOIN job_applications ja ON d.application_id = ja.application_id
        WHERE ja.user_id = :user_id AND ja.status NOT IN ('Withdrawn', 'Rejected', 'Hired')
    ");
    $deploymentQuery->execute(['user_id' => $user_id]);
    $deploymentEvents = $deploymentQuery->fetchAll(PDO::FETCH_ASSOC);
    $events = array_merge($events, $deploymentEvents);

    // Fetch all job applications for the logged-in user with job details
    $jobApplicationsQuery = $pdo->prepare("
        SELECT 
            ja.application_id, 
            jp.job_title, 
            jp.partner_company AS company, 
            ja.status, 
            ja.applied_at,
            jp.location,
            jp.min_salary,
            jp.max_salary,
            jp.deadline
        FROM job_applications ja
        JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
        WHERE ja.user_id = :user_id
    ");
    $jobApplicationsQuery->execute(['user_id' => $user_id]);
    $jobApplications = $jobApplicationsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div id='content'>
        <div class="container mt-5">

            <!-- Job Applications Section -->
            <div class="row mt-5">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">Your Job Applications</div>
                        <div class="card-body">
                            <?php if (count($jobApplications) > 0): ?>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th>Salary Range</th>
                                            <th>Application Status</th>
                                            <th>Applied Date</th>
                                            <th>Application Deadline</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobApplications as $application): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($application['job_title']) ?></td>
                                                <td><?= htmlspecialchars($application['company']) ?></td>
                                                <td><?= htmlspecialchars($application['location']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($application['min_salary']) ?> - <?= htmlspecialchars($application['max_salary']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($application['status']) ?></td>
                                                <td><?= htmlspecialchars($application['applied_at']) ?></td>
                                                <td><?= htmlspecialchars($application['deadline']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>You have no job applications yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Job Applications Section -->

            <div class="row">
                <div class="col-md-12">
                    <h2>Dashboard</h2>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">Interviews</div>
                                <div class="card-body">
                                    <?php if (count($interviewEvents) > 0): ?>
                                        <?php foreach ($interviewEvents as $event): ?>
                                        <a href="application_details.php?application_id=<?= htmlspecialchars($event['application_id']) ?>" class="d-block mb-2">
                                            <?= htmlspecialchars($event['date']) ?> - <?= htmlspecialchars($event['type']) ?>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No upcoming interviews.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">Offers</div>
                                <div class="card-body">
                                    <?php if (count($offerEvents) > 0): ?>
                                        <?php foreach ($offerEvents as $event): ?>
                                        <a href="application_details.php?application_id=<?= htmlspecialchars($event['application_id']) ?>" class="d-block mb-2">
                                            <?= htmlspecialchars($event['date']) ?> - <?= htmlspecialchars($event['type']) ?>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No upcoming offers.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">Deployments</div>
                                <div class="card-body">
                                    <?php if (count($deploymentEvents) > 0): ?>
                                        <?php foreach ($deploymentEvents as $event): ?>
                                        <a href="application_details.php?application_id=<?= htmlspecialchars($event['application_id']) ?>" class="d-block mb-2">
                                            <?= htmlspecialchars($event['date']) ?> - <?= htmlspecialchars($event['type']) ?>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No upcoming deployments.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
