<?php
require 'db.php'; // Include the database connection
include 'header.php';
include 'sidebar.php';

// Fetch overall metrics
$overallDurationStmt = $pdo->prepare("
    SELECT 
        AVG(time_to_fill) AS avg_time_to_fill,
        AVG(avg_total_duration) AS avg_hiring_process_duration,
        AVG(avg_duration_screened_to_interviewed) AS avg_screened_to_interviewed,
        AVG(avg_duration_interviewed_to_offered) AS avg_interviewed_to_offered,
        AVG(avg_duration_offered_to_hired) AS avg_offered_to_hired
    FROM job_metrics
");
$overallDurationStmt->execute();
$overallDurationMetrics = $overallDurationStmt->fetch(PDO::FETCH_ASSOC);

$metricsJson = json_encode($overallDurationMetrics);

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

<div id="content">
    <div class="container mt-5">
        <h1 class="mb-4">Job Metrics</h1>
        <div>
            <canvas id="hiringMetricsChart"></canvas> <!-- Chart Canvas -->
        </div>
    </div>
</div>

<script>
     const metrics = <?php echo $metricsJson; ?>;

    // Labels for each metric
    const labels = [
        "Time to Fill",
        "Hiring Process Duration",
        "Screened to Interviewed",
        "Interviewed to Offered",
        "Offered to Hired"
    ];

    const dataValues = [
        metrics.avg_time_to_fill,
        metrics.avg_hiring_process_duration,
        metrics.avg_screened_to_interviewed,
        metrics.avg_interviewed_to_offered,
        metrics.avg_offered_to_hired
    ];

    const ctx = document.getElementById('hiringMetricsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar', // Change to 'line' if preferred
        data: {
            labels: labels,
            datasets: [{
                label: "Average Stage Durations (Days)",
                data: dataValues,
                backgroundColor: "rgba(54, 162, 235, 0.5)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
