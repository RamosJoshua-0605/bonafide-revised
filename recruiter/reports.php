<?php
require 'db.php'; // Include the database connection

// Fetch overall metrics
$overallStmt = $pdo->prepare("
    SELECT 
        AVG(time_to_fill) AS avg_time_to_fill,
        AVG(avg_total_duration) AS avg_hiring_process_duration,
        AVG(avg_duration_applied_to_screened) AS avg_applied_to_screened,
        AVG(avg_duration_screened_to_interviewed) AS avg_screened_to_interviewed,
        AVG(avg_duration_interviewed_to_offered) AS avg_interviewed_to_offered,
        AVG(avg_duration_offered_to_hired) AS avg_offered_to_hired,
        SUM(referral_applicants) AS total_referral,
        SUM(social_media_applicants) AS total_social_media,
        SUM(career_site_applicants) AS total_career_site,
        AVG(referral_success_rate) AS avg_referral_success,
        AVG(social_media_success_rate) AS avg_social_media_success,
        AVG(career_site_success_rate) AS avg_career_site_success
    FROM job_metrics
");
$overallStmt->execute();
$overallMetrics = $overallStmt->fetch(PDO::FETCH_ASSOC);

// Fetch hiring pipeline drop-off analysis
$pipelineStmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN applied_at IS NOT NULL AND screened_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_screening,
        SUM(CASE WHEN screened_at IS NOT NULL AND interviewed_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_interview,
        SUM(CASE WHEN interviewed_at IS NOT NULL AND offered_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_offer,
        SUM(CASE WHEN offered_at IS NOT NULL AND deployed_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_hiring
    FROM job_applications
");
$pipelineStmt->execute();
$pipelineData = $pipelineStmt->fetch(PDO::FETCH_ASSOC);

// Fetch most sought-after qualifications
$qualificationStmt = $pdo->prepare("
    SELECT 
        uw.role, 
        COUNT(uw.role) AS role_count,
        uc.certification_name,
        COUNT(uc.certification_name) AS cert_count
    FROM job_applications ja
    LEFT JOIN user_work_experience uw ON ja.user_id = uw.user_id
    LEFT JOIN user_certifications uc ON ja.user_id = uc.user_id
    WHERE ja.status = 'Hired'
    GROUP BY uw.role, uc.certification_name
    ORDER BY role_count DESC, cert_count DESC
    LIMIT 5
");
$qualificationStmt->execute();
$qualifications = $qualificationStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch most sought-after job posts
$mostSoughtAfterStmt = $pdo->prepare("
    SELECT 
        jp.job_post_id,
        jp.job_title,
        COUNT(ja.application_id) AS total_applications
    FROM job_posts jp
    JOIN job_applications ja ON jp.job_post_id = ja.job_post_id
    GROUP BY jp.job_post_id, jp.job_title
    ORDER BY total_applications DESC
    LIMIT 5
");
$mostSoughtAfterStmt->execute();
$mostSoughtAfterJobs = $mostSoughtAfterStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch job post specific metrics from job_metrics table
$jobPostMetricsStmt = $pdo->prepare("
    SELECT 
        jm.job_post_id,
        jp.job_title,
        jm.time_to_fill,
        jm.total_applicants,
        jm.screened_applicants,
        jm.interviewed_applicants,
        jm.offered_applicants,
        jm.successful_placements,
        jm.rejected_applicants,
        jm.withdrawn_applicants,
        jm.referral_applicants,
        jm.social_media_applicants,
        jm.career_site_applicants,
        jm.applicant_to_hire_ratio,
        jm.dropout_rate,
        jm.referral_success_rate,
        jm.social_media_success_rate,
        jm.career_site_success_rate,
        jm.avg_duration_applied_to_screened,
        jm.avg_duration_screened_to_interviewed,
        jm.avg_duration_interviewed_to_offered,
        jm.avg_duration_offered_to_hired,
        jm.avg_total_duration
    FROM job_metrics jm
    JOIN job_posts jp ON jm.job_post_id = jp.job_post_id
");
$jobPostMetricsStmt->execute();
$specificjobPostMetrics = $jobPostMetricsStmt->fetchAll(PDO::FETCH_ASSOC);

$jobQualificationStmt = $pdo->prepare("
    SELECT 
        uw.role, 
        COUNT(uw.role) AS role_count,
        uc.certification_name,
        COUNT(uc.certification_name) AS cert_count
    FROM job_applications ja
    LEFT JOIN user_work_experience uw ON ja.user_id = uw.user_id
    LEFT JOIN user_certifications uc ON ja.user_id = uc.user_id
    WHERE ja.status = 'Hired' AND ja.job_post_id = :job_post_id
    GROUP BY uw.role, uc.certification_name
    ORDER BY role_count DESC, cert_count DESC
    LIMIT 5
");
$jobQualificationStmt->bindParam(':job_post_id', $jobId, PDO::PARAM_INT); // Pass the specific job ID dynamically
$jobQualificationStmt->execute();
$specificQualifications = $jobQualificationStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch source effectiveness for a specific job post
$sourceEffectivenessStmt = $pdo->prepare("
    SELECT 
        referral_applicants, 
        referral_success_rate, 
        social_media_applicants, 
        social_media_success_rate, 
        career_site_applicants, 
        career_site_success_rate
    FROM job_metrics
    WHERE job_post_id = :job_post_id
");

// Fetch drop-off data specific to a job post
$jobPipelineStmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN applied_at IS NOT NULL AND screened_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_screening,
        SUM(CASE WHEN screened_at IS NOT NULL AND interviewed_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_interview,
        SUM(CASE WHEN interviewed_at IS NOT NULL AND offered_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_offer,
        SUM(CASE WHEN offered_at IS NOT NULL AND deployed_at IS NULL AND status IN ('Withdrawn', 'Rejected') THEN 1 ELSE 0 END) AS dropped_hiring
    FROM job_applications
    WHERE job_post_id = :job_post_id
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Metrics and Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        h1, h3 {
            color: #343a40;
        }
        .accordion-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .accordion-button {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .accordion-body {
            background-color: #fff;
        }
        .chart-container {
            max-width: 600px;
            margin: 20px auto;
        }
        ul {
            list-style-type: square;
            padding-left: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Job Metrics and Analytics</h1>
    
    <!-- Overall Summary -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Overall Summary</h3>
            <p><strong>Average Time to Fill:</strong> <?= round($overallMetrics['avg_time_to_fill'], 2) ?> days</p>
            <p><strong>Average Duration of Hiring Process:</strong> <?= round($overallMetrics['avg_hiring_process_duration'], 2) ?> days</p>
            <ul>
                <li>Applied to Screened: <?= round($overallMetrics['avg_applied_to_screened'], 2) ?> days</li>
                <li>Screened to Interviewed: <?= round($overallMetrics['avg_screened_to_interviewed'], 2) ?> days</li>
                <li>Interviewed to Offered: <?= round($overallMetrics['avg_interviewed_to_offered'], 2) ?> days</li>
                <li>Offered to Hired: <?= round($overallMetrics['avg_offered_to_hired'], 2) ?> days</li>
            </ul>
            <h4>Source Effectiveness</h4>
            <h4>Overall Source Effectiveness</h4>
                <div class="chart-container mb-3">
                    <canvas id="overallSourceEffectivenessChart"></canvas>
                </div>
            <ul>
                <li>Referrals: <?= $overallMetrics['total_referral'] ?> applicants (Success Rate: <?= round($overallMetrics['avg_referral_success'], 2) ?>%)</li>
                <li>Social Media: <?= $overallMetrics['total_social_media'] ?> applicants (Success Rate: <?= round($overallMetrics['avg_social_media_success'], 2) ?>%)</li>
                <li>Career Site: <?= $overallMetrics['total_career_site'] ?> applicants (Success Rate: <?= round($overallMetrics['avg_career_site_success'], 2) ?>%)</li>
            </ul>
        </div>
    </div>

    <!-- Hiring Pipeline Analysis -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Hiring Pipeline Analysis</h3>
            <div class="chart-container">
                <canvas id="pipelineChart"></canvas>
            </div>
        </div>
    </div>

        <!-- Dropoff Analysis -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Drop-off Points</h3>
            <div class="chart-container">
                <canvas id="dropoffChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Sought-After Qualifications -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Most Sought-After Qualifications</h3>
            <ul>
                <?php foreach ($qualifications as $qualification): ?>
                    <li><?= $qualification['role'] ?> (<?= $qualification['role_count'] ?>), Certification: <?= $qualification['certification_name'] ?> (<?= $qualification['cert_count'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Most Sought-After Job Posts -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Most Sought-After Job Posts</h3>
            <ul>
                <?php foreach ($mostSoughtAfterJobs as $job): ?>
                    <li><?= $job['job_title'] ?> (Total Applications: <?= $job['total_applications'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

<!-- Job Post Breakdown -->
<div class="accordion" id="jobPostAccordion">
    <?php foreach ($specificjobPostMetrics as $index => $jobPost): ?>
        <?php
            $jobId = $jobPost['job_post_id'];
            $jobQualificationStmt->bindParam(':job_post_id', $jobId, PDO::PARAM_INT);
            $jobQualificationStmt->execute();
            $specificQualifications = $jobQualificationStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch source effectiveness for this job post
            $sourceEffectivenessStmt->bindParam(':job_post_id', $jobId, PDO::PARAM_INT);
            $sourceEffectivenessStmt->execute();
            $sourceEffectiveness = $sourceEffectivenessStmt->fetch(PDO::FETCH_ASSOC);

            // Fetch drop-off points
            $jobPipelineStmt->bindParam(':job_post_id', $jobId, PDO::PARAM_INT);
            $jobPipelineStmt->execute();
            $jobPipelineData = $jobPipelineStmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <div class="accordion-item">
            <h2 class="accordion-header" id="heading<?= $index ?>">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>" onclick="loadCharts(<?= $index ?>)">
                    <?= htmlspecialchars($jobPost['job_title']) ?>
                </button>
            </h2>
            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#jobPostAccordion">
                <div class="accordion-body">
                    <!-- Pipeline Visualization Table -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Time to Fill (days)</td>
                                <td><?= $jobPost['time_to_fill'] ?></td>
                            </tr>
                            <tr>
                                <td>Total Applications</td>
                                <td><?= $jobPost['total_applicants'] ?></td>
                            </tr>
                            <tr>
                                <td>Screened</td>
                                <td><?= $jobPost['screened_applicants'] ?></td>
                            </tr>
                            <tr>
                                <td>Interviewed</td>
                                <td><?= $jobPost['interviewed_applicants'] ?></td>
                            </tr>
                            <tr>
                                <td>Offered</td>
                                <td><?= $jobPost['offered_applicants'] ?></td>
                            </tr>
                            <tr>
                                <td>Successful Placements</td>
                                <td><?= $jobPost['successful_placements'] ?></td>
                            </tr>
                            <tr>
                                <td>Rejected</td>
                                <td><?= $jobPost['rejected_applicants'] ?></td>
                            </tr>
                            <tr>
                                <td>Withdrawn</td>
                                <td><?= $jobPost['withdrawn_applicants'] ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Most Sought-After Qualifications -->
                    <div class="mb-3">
                        <h4>Most Sought-After Qualifications</h4>
                        <ul>
                            <?php foreach ($specificQualifications as $qualification): ?>
                                <li><?= htmlspecialchars($qualification['role']) ?> (<?= $qualification['role_count'] ?>), Certification: <?= htmlspecialchars($qualification['certification_name']) ?> (<?= $qualification['cert_count'] ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Source Effectiveness Chart -->
                    <div class="mb-3">
                        <h4>Source Effectiveness</h4>
                        <canvas id="sourceEffectivenessChart<?= $index ?>"></canvas>
                    </div>
                    <div class="mb-3">
                        <ul>
                            <li><strong>Referrals:</strong> <?= $sourceEffectiveness['referral_applicants'] ?> applicants (Success Rate: <?= round($sourceEffectiveness['referral_success_rate'], 2) ?>%)</li>
                            <li><strong>Social Media:</strong> <?= $sourceEffectiveness['social_media_applicants'] ?> applicants (Success Rate: <?= round($sourceEffectiveness['social_media_success_rate'], 2) ?>%)</li>
                            <li><strong>Career Site:</strong> <?= $sourceEffectiveness['career_site_applicants'] ?> applicants (Success Rate: <?= round($sourceEffectiveness['career_site_success_rate'], 2) ?>%)</li>
                        </ul>
                    </div>

                    <!-- Chart -->
                    <div class="chart-container">
                        <h4>Applicant Pipeline Breakdown</h4>
                        <canvas id="chart<?= $index ?>"></canvas>
                    </div>

                    <!-- Chart -->
                    <div class="chart-container">
                        <h4>Drop-off Analysis</h4>
                        <?php print_r($jobPost); ?>
                        <br><br><br>
                        <?php print_r($jobPipelineData); ?>
                        <canvas id="jobdropoffChart<?= $index ?>"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    const pipelineChartCtx = document.getElementById('pipelineChart').getContext('2d');
    const dropoffChartCtx = document.getElementById('dropoffChart').getContext('2d');

    const overallSourceEffectivenessCtx = document.getElementById('overallSourceEffectivenessChart').getContext('2d');

    const dropOffData = <?= json_encode([
        'dropped_screening' => $jobPipelineData['dropped_screening'] ?? 0,
        'dropped_interview' => $jobPipelineData['dropped_interview'] ?? 0,
        'dropped_offer' => $jobPipelineData['dropped_offer'] ?? 0,
        'dropped_hiring' => $jobPipelineData['dropped_hiring'] ?? 0
    ]) ?>;

    new Chart(overallSourceEffectivenessCtx, {
        type: 'bar',
        data: {
            labels: ['Referrals', 'Social Media', 'Career Site'],
            datasets: [
                {
                    label: 'Total Applicants',
                    data: [
                        <?= $overallMetrics['total_referral'] ?>,
                        <?= $overallMetrics['total_social_media'] ?>,
                        <?= $overallMetrics['total_career_site'] ?>
                    ],
                    backgroundColor: ['#007bff', '#ffc107', '#28a745']
                },
                {
                    label: 'Average Success Rate (%)',
                    data: [
                        <?= round($overallMetrics['avg_referral_success'], 2) ?>,
                        <?= round($overallMetrics['avg_social_media_success'], 2) ?>,
                        <?= round($overallMetrics['avg_career_site_success'], 2) ?>
                    ],
                    backgroundColor: ['#6c757d', '#fd7e14', '#20c997'],
                    type: 'line', // Overlay success rate as a line chart
                    yAxisID: 'ySuccessRate'
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Sources'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Applicants'
                    }
                },
                ySuccessRate: {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Success Rate (%)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    
    new Chart(pipelineChartCtx, {
        type: 'bar',
        data: {
            labels: ['Screened', 'Interviewed', 'Offered', 'Hired'], // Chart segment labels
            datasets: [{
                data: [
                    <?= $jobPost['screened_applicants'] ?>,
                    <?= $jobPost['interviewed_applicants'] ?>,
                    <?= $jobPost['offered_applicants'] ?>,
                    <?= $jobPost['successful_placements'] ?>
                ],
                backgroundColor: ['#007bff', '#ffc107', '#17a2b8', '#28a745'], // Colors for segments
                hoverBackgroundColor: ['#0056b3', '#e0a800', '#117a8b', '#218838'], // Colors on hover
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Applicant Pipeline Breakdown', // Chart title
                    font: {
                        size: 18
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const dataIndex = tooltipItem.dataIndex;
                            const label = tooltipItem.label || '';
                            const value = tooltipItem.raw || 0;
                            return `${label}: ${value} applicants`;
                        }
                    }
                }
            }
        }
    });

    new Chart(dropoffChartCtx, {
    type: 'bar',
    data: {
        labels: ['Dropped at Screening', 'Dropped at Interview', 'Dropped at Offer', 'Dropped at Hiring'],
        datasets: [{
            label: 'Drop-off Points',
            data: [
                dropOffData.dropped_screening,
                dropOffData.dropped_interview,
                dropOffData.dropped_offer,
                dropOffData.dropped_hiring
            ],
            backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: { enabled: true }
        },
        scales: { y: { beginAtZero: true } }
    }
});

function loadCharts(index) {
    // Get the canvas context for the charts
    const chartCtx = document.getElementById('chart' + index).getContext('2d');
    const sourceEffectivenessChartCtx = document.getElementById('sourceEffectivenessChart' + index).getContext('2d');
    const jobdropoffChartCtx = document.getElementById('jobdropoffChart' + index).getContext('2d');

    // Source Effectiveness Chart
    new Chart(sourceEffectivenessChartCtx, {
        type: 'bar',
        data: {
            labels: ['Referrals', 'Social Media', 'Career Site'],
            datasets: [{
                label: 'Applicants',
                data: [
                    <?= $sourceEffectiveness['referral_applicants'] ?>,
                    <?= $sourceEffectiveness['social_media_applicants'] ?>,
                    <?= $sourceEffectiveness['career_site_applicants'] ?>
                ],
                backgroundColor: ['#007bff', '#ffc107', '#28a745']
            }]
        },
        options: {
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                x: { title: { display: true, text: 'Sources' } },
                y: { title: { display: true, text: 'Number of Applicants' } }
            }
        }
    });

    // Pipeline Chart
    new Chart(chartCtx, {
        type: 'bar',
        data: {
            labels: ['Screened', 'Interviewed', 'Offered', 'Hired'],
            datasets: [{
                label: 'Applicants',
                data: [
                    <?= $jobPost['screened_applicants'] ?>,
                    <?= $jobPost['interviewed_applicants'] ?>,
                    <?= $jobPost['offered_applicants'] ?>,
                    <?= $jobPost['successful_placements'] ?>
                ],
                backgroundColor: ['#007bff', '#ffc107', '#17a2b8', '#28a745']
            }]
        },
        options: {
            plugins: { legend: { display: true }, tooltip: { enabled: true } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Drop-off Chart
    new Chart(jobdropoffChartCtx, {
        type: 'bar',
        data: {
            labels: ['Dropped at Screening', 'Dropped at Interview', 'Dropped at Offer', 'Dropped at Hiring'],
            datasets: [{
                label: 'Drop-off Points',
                data: [
                    <?= $jobPipelineData['dropped_screening'] ?? 0 ?>,
                    <?= $jobPipelineData['dropped_interview'] ?? 0 ?>,
                    <?= $jobPipelineData['dropped_offer'] ?? 0 ?>,
                    <?= $jobPipelineData['dropped_hiring'] ?? 0 ?>
                ],
                backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: { enabled: true }
            },
            scales: { y: { beginAtZero: true } }
        }
    });

    console.log("Drop-off Chart Data:", {
    dropped_screening: <?= $jobPipelineData['dropped_screening'] ?? 0 ?>,
    dropped_interview: <?= $jobPipelineData['dropped_interview'] ?? 0 ?>,
    dropped_offer: <?= $jobPipelineData['dropped_offer'] ?? 0 ?>,
    dropped_hiring: <?= $jobPipelineData['dropped_hiring'] ?? 0 ?>
});

}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
