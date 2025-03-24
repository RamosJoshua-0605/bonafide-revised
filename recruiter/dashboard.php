<?php 
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';

require 'auth.php';

// Fetch events from the database
$events = [];

// Fetch interview details
$interviewQuery = $pdo->query("SELECT interview_date AS date, 'Interview' AS type, interview_id AS id FROM interview_details");
$interviewEvents = $interviewQuery->fetchAll(PDO::FETCH_ASSOC);
$events = array_merge($events, $interviewEvents);

// Fetch offer details
$offerQuery = $pdo->query("SELECT start_date AS date, 'Offer' AS type, offer_id AS id FROM offer_details");
$offerEvents = $offerQuery->fetchAll(PDO::FETCH_ASSOC);
$events = array_merge($events, $offerEvents);

// Fetch deployment details
$deploymentQuery = $pdo->query("SELECT deployment_date AS date, 'Deployment' AS type, deployment_id AS id FROM deployment_details");
$deploymentEvents = $deploymentQuery->fetchAll(PDO::FETCH_ASSOC);
$events = array_merge($events, $deploymentEvents);

// Fetch job post deadlines
$jobPostQuery = $pdo->query("SELECT deadline AS date, 'Job Post Deadline' AS type, job_post_id AS id, job_title FROM job_posts");
$jobPostEvents = $jobPostQuery->fetchAll(PDO::FETCH_ASSOC);
$events = array_merge($events, $jobPostEvents);

// Fetch total number of applicants and their stages
$applicantStagesQuery = $pdo->query("
    SELECT 
        COUNT(*) AS total_applicants,
        SUM(CASE WHEN status = 'Applied' THEN 1 ELSE 0 END) AS applied,
        SUM(CASE WHEN status = 'Interviewed' THEN 1 ELSE 0 END) AS interview,
        SUM(CASE WHEN status = 'Offered' THEN 1 ELSE 0 END) AS offer,
        SUM(CASE WHEN status = 'Hired' THEN 1 ELSE 0 END) AS deployed
    FROM job_applications
");
$applicantStages = $applicantStagesQuery->fetch(PDO::FETCH_ASSOC);
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
            <div class="row">
                <div class="col-md-12">
                    <h2>Dashboard</h2>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-header">Total Applicants</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $applicantStages['total_applicants'] ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-secondary mb-3">
                                <div class="card-header">Applied</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $applicantStages['applied'] ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-header">Interviewed</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $applicantStages['interview'] ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-header">Offered</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $applicantStages['offer'] ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-header">Hired</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $applicantStages['deployed'] ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">Interviews</div>
                                <div class="card-body">
                                    <?php if (count($interviewEvents) > 0): ?>
                                        <?php foreach ($interviewEvents as $event): ?>
                                        <a href="view_interview.php?id=<?= $event['id'] ?>" class="d-block mb-2">
                                            <?= $event['date'] ?> - <?= $event['type'] ?>
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
                                        <a href="view_offer.php?id=<?= $event['id'] ?>" class="d-block mb-2">
                                            <?= $event['date'] ?> - <?= $event['type'] ?>
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
                                        <a href="view_deployment.php?id=<?= $event['id'] ?>" class="d-block mb-2">
                                            <?= $event['date'] ?> - <?= $event['type'] ?>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No upcoming deployments.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">Job Post Deadlines</div>
                                <div class="card-body">
                                    <?php if (count($jobPostEvents) > 0): ?>
                                        <?php foreach ($jobPostEvents as $event): ?>
                                        <a href="edit_job.php?job_post_id=<?= $event['id'] ?>" class="d-block mb-2">
                                            <?= $event['date'] ?> - <?= $event['type'] ?> - <?= $event['job_title'] ?>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No upcoming job post deadlines.</p>
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