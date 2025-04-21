<?php
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';

require 'auth.php';

// Get user_id from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("User not found.");
}

// Fetch job applications of the applicant
$query = $pdo->prepare("
    SELECT 
        ja.application_id,
        ja.job_post_id,
        ja.status,
        ja.applied_at,
        jp.job_title,
        jp.partner_company, 
        jp.location,
        jp.min_salary,
        jp.max_salary
    FROM job_applications ja
    JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
    WHERE ja.user_id = :user_id
    ORDER BY ja.applied_at DESC
");
$query->execute(['user_id' => $user_id]);
$applications = $query->fetchAll(PDO::FETCH_ASSOC);

// Group applications by status
$groupedApplications = [
    'rejected' => [],
    'withdrawn' => [],
    'screened' => [],
    'pending' => [],
    'shortlisted' => [],
    'interviewed' => [],
    'offered' => [],
    'hired' => [],
];

foreach ($applications as $app) {
    if ($app['status'] === 'Rejected') {
        $groupedApplications['Rejected'][] = $app;
    } elseif ($app['status'] === 'Withdrawn') {
        $groupedApplications['Withdrawn'][] = $app;
    } elseif ($app['status'] === 'Interviewed') {
        $groupedApplications['Interviewed'][] = $app;
    } elseif ($app['status'] === 'Offered') {
        $groupedApplications['Offered'][] = $app;
    } elseif ($app['status'] === 'Hired') {
        $groupedApplications['Hired'][] = $app;
    } elseif ($app['status'] === 'Screened') {
        $groupedApplications['Screened'][] = $app;
    } elseif ($app['status'] === 'Shortlisted') {
        $groupedApplications['Shortlisted'][] = $app;
    } elseif ($app['status'] === 'Pending') {
        $groupedApplications['Pending'][] = $app;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>

<div id='content'>
    <div class="container mt-5">
        <h2 class="mb-4">My Job Applications</h2>

        <?php if (!empty($_SESSION['withdraw_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['withdraw_success'] ?></div>
            <?php unset($_SESSION['withdraw_success']); ?>
        <?php endif; ?>

        <?php if (empty($applications)): ?>
            <p class="alert alert-warning">You have not applied to any jobs yet.</p>
        <?php else: ?>
            <!-- Tabs for Job Application Status -->
            <ul class="nav nav-tabs" id="jobTabs" role="tablist">
                <?php $firstTab = true; ?>
                <?php foreach ($groupedApplications as $status => $apps): ?>
                    <?php if (!empty($apps)): ?>
                        <li class="nav-item">
                            <button class="nav-link <?= $firstTab ? 'active' : '' ?>" 
                                    id="<?= $status ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#<?= $status ?>" 
                                    type="button" 
                                    role="tab">
                                <?= ucfirst($status) ?>
                            </button>
                        </li>
                        <?php $firstTab = false; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Job Application Cards -->
            <div class="tab-content mt-4">
                <?php $firstPane = true; ?>
                <?php foreach ($groupedApplications as $status => $apps): ?>
                    <?php if (!empty($apps)): ?>
                        <div class="tab-pane fade <?= $firstPane ? 'show active' : '' ?>" id="<?= $status ?>" role="tabpanel">
                            <div class="row">
                                <?php foreach ($apps as $app): ?>
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($app['job_title']) ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($app['partner_company']) ?></h6>
                                                <p class="card-text">
                                                    <strong>Location:</strong> <?= htmlspecialchars($app['location']) ?><br>
                                                    <strong>Salary:</strong> 
                                                    <?= $app['min_salary'] && $app['max_salary'] ? 
                                                        "₱" . htmlspecialchars($app['min_salary']) . " - ₱" . htmlspecialchars($app['max_salary']) 
                                                        : "Not Specified" ?>
                                                </p>

                                                <!-- Show relevant details for each stage -->
                                                <!-- <?php if ($status === 'Interviewed'): ?>
                                                    <p><strong>Interview:</strong> Scheduled on <?= date("M d, Y", strtotime($app['interview_date'])) ?> (<?= htmlspecialchars($app['meeting_type']) ?>)</p>
                                                <?php elseif ($status === 'Offered'): ?>
                                                    <p><strong>Offer:</strong> ₱<?= htmlspecialchars($app['offered_salary']) ?> (Start: <?= date("M d, Y", strtotime($app['offer_start_date'])) ?>)</p>
                                                <?php elseif ($status === 'Hired'): ?>
                                                    <p><strong>Deployment:</strong> <?= date("M d, Y", strtotime($app['deployment_date'])) ?></p>
                                                <?php elseif ($status === 'Rejected' || $status === 'Withdrawn'): ?>
                                                    <p class="text-danger"><strong>Status:</strong> <?= $status ?></p>
                                                <?php endif; ?> -->

                                                <a href="application_details.php?application_id=<?= $app['application_id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $firstPane = false; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>