<?php
ob_start();
require 'db.php'; // Include the database connection
include 'header.php';
include 'sidebar.php';
require 'auth.php';

// Fetch data for the reports
$totalMetricsQuery = $pdo->prepare("
    SELECT 
        jp.job_title AS job_post_title,
        COUNT(jm.job_post_id) AS total_closed_job_posts,
        AVG(TIMESTAMPDIFF(DAY, jm.created_at, jm.filled_date)) AS avg_time_to_fill,
        AVG(jm.avg_total_duration) AS avg_total_duration,
        AVG(jm.avg_duration_applied_to_screened) AS avg_duration_applied_to_screened,
        AVG(jm.avg_duration_screened_to_interviewed) AS avg_duration_screened_to_interviewed,
        AVG(jm.avg_duration_interviewed_to_offered) AS avg_duration_interviewed_to_offered,
        AVG(jm.avg_duration_offered_to_hired) AS avg_duration_offered_to_hired,
        
        SUM(jm.total_applicants) AS total_applicants,
        SUM(jm.screened_applicants) AS total_screened,
        SUM(jm.interviewed_applicants) AS total_interviewed,
        SUM(jm.offered_applicants) AS total_offered,
        SUM(jm.successful_placements) AS total_hired,
        SUM(jm.rejected_applicants) AS total_rejected,
        SUM(jm.withdrawn_applicants) AS total_withdrawn,

        -- Source effectiveness calculations
        SUM(jm.referral_applicants) AS total_referral_applicants,
        SUM(jm.social_media_applicants) AS total_social_media_applicants,
        SUM(jm.career_site_applicants) AS total_career_site_applicants,

        AVG(jm.referral_success_rate) AS avg_referral_success_rate,
        AVG(jm.social_media_success_rate) AS avg_social_media_success_rate,
        AVG(jm.career_site_success_rate) AS avg_career_site_success_rate,

        AVG(jm.applicant_to_hire_ratio) AS avg_applicant_to_hire_ratio,
        AVG(jm.dropout_rate) AS avg_dropout_rate

    FROM job_metrics jm
    JOIN job_posts jp ON jm.job_post_id = jp.job_post_id
    WHERE jp.status = 'Closed'
");
$totalMetricsQuery->execute();
$summaries = $totalMetricsQuery->fetch(PDO::FETCH_ASSOC);

$totaldata = json_encode($summaries);

// Pipeline Analysis
$dropoffQuery = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_applicants,
        
        -- Drop-off at each stage
        SUM(CASE WHEN applied_at IS NOT NULL AND screened_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_application,
        SUM(CASE WHEN screened_at IS NOT NULL AND interviewed_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_screening,
        SUM(CASE WHEN interviewed_at IS NOT NULL AND offered_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_interview,
        SUM(CASE WHEN offered_at IS NOT NULL AND deployed_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_offer,
        
        -- Rejected or Withdrawn
        SUM(CASE WHEN rejected_at IS NOT NULL THEN 1 ELSE 0 END) AS total_rejected,
        SUM(CASE WHEN withdrawn_at IS NOT NULL THEN 1 ELSE 0 END) AS total_withdrawn

    FROM job_applications
");
$dropoffQuery->execute();
$dropoff_summary = $dropoffQuery->fetch(PDO::FETCH_ASSOC);

// Convert PHP array to JSON for JavaScript usage
$dropoffJSON = json_encode($dropoff_summary);

// Query most sought after jobs
$top_jobsquery = $pdo->prepare("
    SELECT 
        jp.job_title, 
        jm.job_post_id, 
        jm.total_applicants 
    FROM job_metrics jm
    JOIN job_posts jp ON jm.job_post_id = jp.job_post_id
    ORDER BY jm.total_applicants DESC
    LIMIT 5
");
$top_jobsquery->execute();
$top_jobs = $top_jobsquery->fetchAll(PDO::FETCH_ASSOC);

// Query most sought after job qualifications
$query = $pdo->prepare("
    SELECT 
        TRIM(LOWER(uc.certification_name)) AS certification_name, 
        COUNT(DISTINCT u.user_id) AS hired_count
    FROM user_certifications uc
    JOIN users u ON uc.user_id = u.user_id
    JOIN job_applications ja ON u.user_id = ja.user_id
    WHERE ja.status = 'Hired'
    GROUP BY TRIM(LOWER(uc.certification_name))
    ORDER BY hired_count DESC
    LIMIT 5
");
$query->execute();
$top_certifications = $query->fetchAll(PDO::FETCH_ASSOC);

// Conversion Rates
$conversionRatesQuery = $pdo->prepare("
    SELECT 
        (COUNT(CASE WHEN status = 'Screened' THEN 1 END) * 100.0) / COUNT(*) AS screened_conversion_rate,
        (COUNT(CASE WHEN status = 'Interviewed' THEN 1 END) * 100.0) / COUNT(*) AS interviewed_conversion_rate,
        (COUNT(CASE WHEN status = 'Offered' THEN 1 END) * 100.0) / COUNT(*) AS offer_conversion_rate,
        (COUNT(CASE WHEN status = 'Hired' THEN 1 END) * 100.0) / COUNT(*) AS hire_conversion_rate
    FROM job_applications
");
$conversionRatesQuery->execute();
$conversionRates = $conversionRatesQuery->fetch(PDO::FETCH_ASSOC);

$conversionRatesJSON = json_encode($conversionRates);

// Referral Success Rate
$referralQuery = $pdo->prepare("
    SELECT COUNT(CASE WHEN status = 'Successful' THEN 1 END) * 100.0 / COUNT(*) AS referral_success_rate
    FROM referrals
");
$referralQuery->execute();
$referralRate = $referralQuery->fetch(PDO::FETCH_ASSOC);

$referralRateJSON = json_encode($referralRate);

// Average Interviews Per Hire
$interviewsPerHireQuery = $pdo->prepare("
    SELECT 
        jp.job_title,
        SUM(interview_count) / NULLIF(COUNT(DISTINCT hired_applications.application_id), 0) AS avg_interviews_per_hire
    FROM (
        SELECT 
            job_applications.application_id,
            job_applications.job_post_id,
            COUNT(interview_details.interview_id) AS interview_count
        FROM job_applications
        LEFT JOIN interview_details ON job_applications.application_id = interview_details.application_id
        WHERE job_applications.status = 'Hired'
        GROUP BY job_applications.application_id, job_applications.job_post_id
    ) AS hired_applications
    JOIN job_posts jp ON hired_applications.job_post_id = jp.job_post_id
    GROUP BY jp.job_title
");
$interviewsPerHireQuery->execute();
$interviewsPerHire = $interviewsPerHireQuery->fetchAll(PDO::FETCH_ASSOC);

$interviewsPerHireJSON = json_encode($interviewsPerHire);

// Top 5 Locations with Most Applications
$topLocationsQuery = $pdo->prepare("
    SELECT location, COUNT(*) AS application_count
    FROM job_posts
    JOIN job_applications ON job_posts.job_post_id = job_applications.job_post_id
    GROUP BY location
    ORDER BY application_count DESC
    LIMIT 5
");
$topLocationsQuery->execute();
$topLocations = $topLocationsQuery->fetchAll(PDO::FETCH_ASSOC);

// Average Salary Per Job Hire
$avgSalaryQuery = $pdo->prepare("
    SELECT jp.job_title, AVG(od.salary) AS avg_salary
    FROM job_applications ja
    JOIN offer_details od ON ja.application_id = od.application_id
    JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
    WHERE ja.status = 'Hired'
    GROUP BY jp.job_title
    ORDER BY avg_salary DESC
");
$avgSalaryQuery->execute();
$avgSalaries = $avgSalaryQuery->fetchAll(PDO::FETCH_ASSOC);

// Job-Specific Metrics for Each Job Post
$jobPostMetricsQuery = $pdo->prepare("
    SELECT 
        jp.job_title AS job_post_title,
        COUNT(jm.job_post_id) AS total_closed_job_posts,
        AVG(TIMESTAMPDIFF(DAY, jm.created_at, jm.filled_date)) AS avg_time_to_fill,
        AVG(jm.avg_total_duration) AS avg_total_duration,
        AVG(jm.avg_duration_applied_to_screened) AS avg_duration_applied_to_screened,
        AVG(jm.avg_duration_screened_to_interviewed) AS avg_duration_screened_to_interviewed,
        AVG(jm.avg_duration_interviewed_to_offered) AS avg_duration_interviewed_to_offered,
        AVG(jm.avg_duration_offered_to_hired) AS avg_duration_offered_to_hired,

        SUM(jm.total_applicants) AS total_applicants_for_job_post,
        SUM(jm.screened_applicants) AS total_screened_for_job_post,
        SUM(jm.interviewed_applicants) AS total_interviewed_for_job_post,
        SUM(jm.offered_applicants) AS total_offered_for_job_post,
        SUM(jm.successful_placements) AS total_hired_for_job_post,
        SUM(jm.rejected_applicants) AS total_rejected_for_job_post,
        SUM(jm.withdrawn_applicants) AS total_withdrawn_for_job_post,

        -- Source effectiveness
        SUM(jm.referral_applicants) AS total_referral_applicants_for_job_post,
        SUM(jm.social_media_applicants) AS total_social_media_applicants_for_job_post,
        SUM(jm.career_site_applicants) AS total_career_site_applicants_for_job_post,

        AVG(jm.referral_success_rate) AS avg_referral_success_rate_for_job_post,
        AVG(jm.social_media_success_rate) AS avg_social_media_success_rate_for_job_post,
        AVG(jm.career_site_success_rate) AS avg_career_site_success_rate_for_job_post,

        AVG(jm.applicant_to_hire_ratio) AS avg_applicant_to_hire_ratio_for_job_post,
        AVG(jm.dropout_rate) AS avg_dropout_rate_for_job_post
    FROM job_metrics jm
    JOIN job_posts jp ON jm.job_post_id = jp.job_post_id
    WHERE jp.status = 'Closed'
    GROUP BY jp.job_title
");
$jobPostMetricsQuery->execute();
$jobPostMetrics = $jobPostMetricsQuery->fetchAll(PDO::FETCH_ASSOC);

$jobPostMetricsJSON = json_encode($jobPostMetrics);

// Drop-Off Rates Per Job Post
$dropoffPerJobPostQuery = $pdo->prepare("
    SELECT 
        jp.job_title AS job_post_title,
        COUNT(ja.application_id) AS total_applicants_for_job_post,

        -- Drop-off at each stage
        SUM(CASE WHEN ja.applied_at IS NOT NULL AND ja.screened_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_application_for_job_post,
        SUM(CASE WHEN ja.screened_at IS NOT NULL AND ja.interviewed_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_screening_for_job_post,
        SUM(CASE WHEN ja.interviewed_at IS NOT NULL AND ja.offered_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_interview_for_job_post,
        SUM(CASE WHEN ja.offered_at IS NOT NULL AND ja.deployed_at IS NULL THEN 1 ELSE 0 END) AS drop_off_after_offer_for_job_post,

        -- Rejected or Withdrawn
        SUM(CASE WHEN ja.rejected_at IS NOT NULL THEN 1 ELSE 0 END) AS total_rejected_for_job_post,
        SUM(CASE WHEN ja.withdrawn_at IS NOT NULL THEN 1 ELSE 0 END) AS total_withdrawn_for_job_post
    FROM job_applications ja
    JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
    GROUP BY jp.job_title
");
$dropoffPerJobPostQuery->execute();
$dropoffPerJobPost = $dropoffPerJobPostQuery->fetchAll(PDO::FETCH_ASSOC);

$dropoffPerJobPostJSON = json_encode($dropoffPerJobPost);

// Conversion Rates Per Job Post
$conversionRatesPerJobPostQuery = $pdo->prepare("
    SELECT 
        jp.job_title AS job_post_title,
        (COUNT(CASE WHEN ja.status = 'Screened' THEN 1 END) * 100.0) / COUNT(*) AS screened_conversion_rate_for_job_post,
        (COUNT(CASE WHEN ja.status = 'Interviewed' THEN 1 END) * 100.0) / COUNT(*) AS interviewed_conversion_rate_for_job_post,
        (COUNT(CASE WHEN ja.status = 'Offered' THEN 1 END) * 100.0) / COUNT(*) AS offer_conversion_rate_for_job_post,
        (COUNT(CASE WHEN ja.status = 'Hired' THEN 1 END) * 100.0) / COUNT(*) AS hire_conversion_rate_for_job_post
    FROM job_applications ja
    JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
    GROUP BY jp.job_title
");
$conversionRatesPerJobPostQuery->execute();
$conversionRatesPerJobPost = $conversionRatesPerJobPostQuery->fetchAll(PDO::FETCH_ASSOC);

$conversionRatesPerJobPostJSON = json_encode($conversionRatesPerJobPost);

// Interviews Per Hire Per Job Post
$interviewsPerHirePerJobPostQuery = $pdo->prepare("
    SELECT 
        jp.job_title AS job_post_title,
        SUM(interview_count) / NULLIF(COUNT(DISTINCT hired_applications.application_id), 0) AS avg_interviews_per_hire_for_job_post
    FROM (
        SELECT 
            ja.application_id,
            ja.job_post_id,
            COUNT(id.interview_id) AS interview_count
        FROM job_applications ja
        LEFT JOIN interview_details id ON ja.application_id = id.application_id
        WHERE ja.status = 'Hired'
        GROUP BY ja.application_id, ja.job_post_id
    ) AS hired_applications
    JOIN job_posts jp ON hired_applications.job_post_id = jp.job_post_id
    GROUP BY jp.job_title
");
$interviewsPerHirePerJobPostQuery->execute();
$interviewsPerHirePerJobPost = $interviewsPerHirePerJobPostQuery->fetchAll(PDO::FETCH_ASSOC);

$interviewsPerHirePerJobPostJSON = json_encode($interviewsPerHirePerJobPost);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Analytics</title>

    <!-- Load Chart.js and jQuery UI -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    
    <!-- Bootstrap for accordion -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* Accordion Styling */
        .accordion-item {
            border: none;
            border-radius: 5px;
            margin-bottom: 10px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }
        .accordion-button {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e9ecef;
        }
        .accordion-body {
            text-align: center;
        }

        /* Chart Container */
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 100%;
            height: 350px; /* Increased for better visibility */
            margin: auto;
            padding: 15px;
            border-radius: 5px;
            background: #ffffff;
        }

        /* Increase size for Pie and Doughnut charts */
        .pie-doughnut-container {
            height: 500px !important; /* Increase height */
            width: 500px !important;  /* Increase width */
            max-width: 100%;
            margin: auto;
        }

        /* Responsive Grid Layout */
        .grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        hr {
            border: 2px solid #000000;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr; /* Single column on small screens */
            }
        }
    </style>
</head>

<body>
<div id="content">
    <div class="container mt-4">
        <div class="accordion" id="overallMetricsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOverallMetrics">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOverallMetrics" aria-expanded="true" aria-controls="collapseOverallMetrics">
                        Overall Metrics
                    </button>
                </h2>
                <div id="collapseOverallMetrics" class="accordion-collapse collapse show" aria-labelledby="headingOverallMetrics" data-bs-parent="#overallMetricsAccordion">
                    <div class="accordion-body">
                        <h2 id="totalApplicantsHeader">Total Applicants: </h2>

                        <!-- Most Sought After Jobs -->
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">Most Sought After Jobs</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($top_jobs as $job): ?>
                                        <li class="list-group-item">
                                            <?= htmlspecialchars($job['job_title']) ?> - <?= $job['total_applicants'] ?> Applicants
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Most Sought After Qualifications -->
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">Most Sought After Qualifications</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($top_certifications as $cert): ?>
                                        <li class="list-group-item">
                                            <?= htmlspecialchars(ucwords($cert['certification_name'])) ?> - <?= $cert['hired_count'] ?> Hires
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Top 5 Locations -->
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">Top 5 Locations With Most Applications</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($topLocations as $location): ?>
                                        <li class="list-group-item">
                                            <?= htmlspecialchars($location['location']) ?> - <?= $location['application_count'] ?> Applications
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Average Salary Per Job Hire -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">Average Salary Per Job Hire</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($avgSalaries as $salary): ?>
                                        <li class="list-group-item">
                                            <?= htmlspecialchars($salary['job_title']) ?> - PHP<?= number_format($salary['avg_salary'], 2) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Accordion for Charts -->
                        <div class="accordion" id="chartsAccordion">
                            <!-- Chart 1: Average Duration -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartOne">
                                        Average Duration & Applicants
                                    </button>
                                </h2>
                                <div id="chartOne" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container">
                                            <canvas id="avgDurationApplicantsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 2: Source Effectiveness -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartTwo">
                                        Source Effectiveness
                                    </button>
                                </h2>
                                <div id="chartTwo" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container pie-doughnut-container">
                                            <canvas id="sourceEffectivenessChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 3: Dropout vs Hire Ratio -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartThree">
                                        Dropout vs Hire Ratio
                                    </button>
                                </h2>
                                <div id="chartThree" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container pie-doughnut-container">
                                            <canvas id="dropoutHireRatioChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 4: Drop-Off Rates -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartFour">
                                        Drop-Off Rates
                                    </button>
                                </h2>
                                <div id="chartFour" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container">
                                            <canvas id="dropOffRatesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 5: Rejected vs Withdrawn -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartFive">
                                        Rejected vs Withdrawn Applicants
                                    </button>
                                </h2>
                                <div id="chartFive" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container pie-doughnut-container">
                                            <canvas id="rejectedWithdrawnChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 6: Conversion Rates -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartSix">
                                        Conversion Rates
                                    </button>
                                </h2>
                                <div id="chartSix" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container">
                                            <canvas id="conversionRatesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 7: Referral Success -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartSeven">
                                        Referral Success Rate
                                    </button>
                                </h2>
                                <div id="chartSeven" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container pie-doughnut-container">
                                            <canvas id="referralSuccessChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart 8: Interviews Per Hire -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#chartEight">
                                        Interviews Per Hire
                                    </button>
                                </h2>
                                <div id="chartEight" class="accordion-collapse collapse show">
                                    <div class="accordion-body text-center">
                                        <div class="chart-container">
                                            <canvas id="interviewsPerHireChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- End of Accordion for Charts -->
                    </div>
                </div>
            </div>
        </div> <!-- End of Overall Metrics Accordion -->
    </div>

    <div class="container mt-4">
        <h2>Job Specific Metrics</h2>

        <div class="accordion" id="jobMetricsAccordion">
            <?php foreach ($jobPostMetrics as $index => $job): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                            <?= htmlspecialchars($job['job_post_title']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#jobMetricsAccordion">
                        <div class="accordion-body">
                            <h3>Job-Specific Metrics</h3>
                            <div class="chart-container">
                                <canvas id="jobMetricsChart<?= $index ?>"></canvas>
                            </div>
                            <hr>

                            <h3>Drop-Off Rates</h3>
                            <div class="chart-container">
                                <canvas id="dropoffChart<?= $index ?>"></canvas>
                            </div>
                            <hr>

                            <h3>Conversion Rates</h3>
                            <div class="chart-container">
                                <canvas id="conversionChart<?= $index ?>"></canvas>
                            </div>
                            <hr>

                            <h3>Interviews Per Hire</h3>
                            <div class="chart-container">
                                <canvas id="interviewsChart<?= $index ?>"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>

<script>
window.onload = function() {
    // Parse PHP JSON data
    const totaldata = <?php echo $totaldata; ?>;
    const dropoffData = <?php echo $dropoffJSON; ?>;
    const conversionRates = <?php echo $conversionRatesJSON; ?>;
    const referralRate = <?php echo $referralRateJSON; ?>;
    const interviewsPerHire = <?php echo $interviewsPerHireJSON; ?>;

    document.getElementById('totalApplicantsHeader').innerText = "Total Applicants: " + totaldata.total_applicants;

    // Common Chart Options (to maintain compact size)
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false, // Ensures charts fill the container
        plugins: {
            legend: { display: false },
            title: { display: true }
        }
    };

    // Bar Chart: Average Total Duration & Total Applicants
    const avgDurationApplicantsCtx = document.getElementById('avgDurationApplicantsChart').getContext('2d');
    new Chart(avgDurationApplicantsCtx, {
        type: 'bar',
        data: {
            labels: [
                'Avg Time to Fill (Days)',
                'Avg Total Duration',
                'Avg Applied to Screened',
                'Avg Screened to Interviewed',
                'Avg Interviewed to Offered',
                'Avg Offered to Hired',
            ],
            datasets: [{
                label: 'Recruitment Metrics',
                data: [
                    totaldata.avg_time_to_fill,
                    totaldata.avg_total_duration,
                    totaldata.avg_duration_applied_to_screened,
                    totaldata.avg_duration_screened_to_interviewed,
                    totaldata.avg_duration_interviewed_to_offered,
                    totaldata.avg_duration_offered_to_hired,
                ],
                backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#8e44ad', '#ff5733', '#33ff57']
            }]
        },
        options: {
            responsive: true,
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Average Duration'
                }
            }
        }
    });

    // Pie Chart: Source Effectiveness
    const sourceEffectivenessCtx = document.getElementById('sourceEffectivenessChart').getContext('2d');
    new Chart(sourceEffectivenessCtx, {
        type: 'pie',
        data: {
            labels: ['Referrals', 'Social Media', 'Career Site'],
            datasets: [{
                label: 'Source Effectiveness',
                data: [
                    totaldata.total_referral_applicants,
                    totaldata.total_social_media_applicants,
                    totaldata.total_career_site_applicants
                ],
                backgroundColor: ['#e74c3c', '#f1c40f', '#8e44ad']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Ensures charts fit the container
            aspectRatio: 1, // Keeps pie/doughnut charts proportional
            plugins: {
                title: {
                    display: true,
                    text: 'Source Effectiveness'
                }
            }
        }
    });

    // Doughnut Chart: Dropout Rate & Applicant-to-Hire Ratio
    const dropoutHireRatioCtx = document.getElementById('dropoutHireRatioChart').getContext('2d');
    new Chart(dropoutHireRatioCtx, {
        type: 'doughnut',
        data: {
            labels: ['Dropout Rate', 'Applicant to Hire Ratio'],
            datasets: [{
                data: [
                    totaldata.avg_dropout_rate,
                    totaldata.avg_applicant_to_hire_ratio
                ],
                backgroundColor: ['#ff5733', '#33ff57']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Ensures charts fit the container
            aspectRatio: 1, // Keeps pie/doughnut charts proportional
            plugins: {
                title: {
                    display: true,
                    text: 'Dropout Rate & Applicant-to-Hire Ratio'
                }
            }
        }
    });

    // Bar Chart: Drop-Off Rates at Each Stage
    const dropOffRatesCtx = document.getElementById('dropOffRatesChart').getContext('2d');
    new Chart(dropOffRatesCtx, {
        type: 'bar',
        data: {
            labels: [
                'Drop-off After Application',
                'Drop-off After Screening',
                'Drop-off After Interview',
                'Drop-off After Offer'
            ],
            datasets: [{
                label: 'Drop-Off Count',
                data: [
                    dropoffData.drop_off_after_application,
                    dropoffData.drop_off_after_screening,
                    dropoffData.drop_off_after_interview,
                    dropoffData.drop_off_after_offer
                ],
                backgroundColor: ['#e74c3c', '#f1c40f', '#3498db', '#2ecc71']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Drop-Off Rates by Stage'
                }
            }
        }
    });

    // Pie Chart: Rejected vs Withdrawn Applicants
    const rejectedWithdrawnCtx = document.getElementById('rejectedWithdrawnChart').getContext('2d');
    new Chart(rejectedWithdrawnCtx, {
        type: 'pie',
        data: {
            labels: ['Rejected Applicants', 'Withdrawn Applicants'],
            datasets: [{
                data: [
                    dropoffData.total_rejected,
                    dropoffData.total_withdrawn
                ],
                backgroundColor: ['#ff5733', '#33ff57']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Ensures charts fit the container
            aspectRatio: 1, // Keeps pie/doughnut charts proportional
            plugins: {
                title: {
                    display: true,
                    text: 'Rejected vs Withdrawn Applicants'
                }
            }
        }
    });

    // Conversion Rates 
    const conversionRatesCtx = document.getElementById('conversionRatesChart').getContext('2d');
    new Chart(conversionRatesCtx, {
        type: 'bar',
        data: {
            labels: ['Screened', 'Interviewed', 'Offered', 'Hired'],
            datasets: [{
                label: 'Conversion Rate (%)',
                data: [
                    conversionRates.screened_conversion_rate,
                    conversionRates.interviewed_conversion_rate,
                    conversionRates.offer_conversion_rate,
                    conversionRates.hire_conversion_rate
                ],
                backgroundColor: ['#3498db', '#f1c40f', '#8e44ad', '#2ecc71']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Applicant Conversion Rates'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + "%";
                        }
                    }
                }
            }
        }
    });

    // Calculate unsuccessful referrals
    const unsuccessfulRate = 100 - referralRate.referral_success_rate;

    // Doughnut Chart: Referral Success Rate
    const referralCtx = document.getElementById('referralSuccessChart').getContext('2d');
    new Chart(referralCtx, {
        type: 'doughnut',
        data: {
            labels: ['Successful Referrals', 'Unsuccessful Referrals'],
            datasets: [{
                data: [referralRate.referral_success_rate, unsuccessfulRate],
                backgroundColor: ['#2ecc71', '#e74c3c']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Ensures charts fit the container
            aspectRatio: 1, // Keeps pie/doughnut charts proportional
            plugins: {
                title: {
                    display: true,
                    text: 'Referral Success Rate'
                }
            }
        }
    });

    // Extract job titles and average interview counts
    const jobTitles = interviewsPerHire.map(item => item.job_title);
    const avgInterviews = interviewsPerHire.map(item => item.avg_interviews_per_hire);

    // Bar Chart: Average Interviews Per Hire by Job Title
    const interviewsCtx = document.getElementById('interviewsPerHireChart').getContext('2d');
    new Chart(interviewsCtx, {
        type: 'bar',
        data: {
            labels: jobTitles,
            datasets: [{
                label: 'Avg Interviews Per Hire',
                data: avgInterviews,
                backgroundColor: '#3498db'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Average Interviews Per Hire by Job Title'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    <?php foreach ($jobPostMetrics as $index => $job): ?>
        new Chart(document.getElementById("jobMetricsChart<?= $index ?>").getContext("2d"), {
            type: "bar",
            data: {
                labels: ["Closed Jobs", "Avg Time to Fill", "Avg Duration", "Total Applicants", "Total Hired"],
                datasets: [{
                    label: "<?= htmlspecialchars($job['job_post_title']) ?> Metrics",
                    data: [
                        <?= $job['total_closed_job_posts'] ?>,
                        <?= $job['avg_time_to_fill'] ?>,
                        <?= $job['avg_total_duration'] ?>,
                        <?= $job['total_applicants_for_job_post'] ?>,
                        <?= $job['total_hired_for_job_post'] ?>,
                    ],
                    backgroundColor: ["#007bff", "#28a745", "#17a2b8", "#ffc107", "#dc3545", "#6c757d"]
                }]
            },
            options: { responsive: true, maintainAspectRatio: false}
        });
    <?php endforeach; ?>

    <?php foreach ($dropoffPerJobPost as $index => $job): ?>
        new Chart(document.getElementById("dropoffChart<?= $index ?>").getContext("2d"), {
            type: "pie",
            data: {
                labels: ["Application", "Screening", "Interview", "Offer", "Rejected", "Withdrawn"],
                datasets: [{
                    label: "Drop-Off Rates",
                    data: [
                        <?= $job['drop_off_after_application_for_job_post'] ?>,
                        <?= $job['drop_off_after_screening_for_job_post'] ?>,
                        <?= $job['drop_off_after_interview_for_job_post'] ?>,
                        <?= $job['drop_off_after_offer_for_job_post'] ?>,
                        <?= $job['total_rejected_for_job_post'] ?>,
                        <?= $job['total_withdrawn_for_job_post'] ?>
                    ],
                    backgroundColor: ["#007bff", "#28a745", "#17a2b8", "#ffc107", "#dc3545", "#6c757d"]
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    <?php endforeach; ?>

    <?php foreach ($conversionRatesPerJobPost as $index => $job): ?>
        new Chart(document.getElementById("conversionChart<?= $index ?>").getContext("2d"), {
            type: "bar",
            data: {
                labels: ["Screened", "Interviewed", "Offer", "Hired"],
                datasets: [{
                    label: "Conversion Rates (%)",
                    data: [
                        <?= round($job['screened_conversion_rate_for_job_post'], 2) ?>,
                        <?= round($job['interviewed_conversion_rate_for_job_post'], 2) ?>,
                        <?= round($job['offer_conversion_rate_for_job_post'], 2) ?>,
                        <?= round($job['hire_conversion_rate_for_job_post'], 2) ?>
                    ],
                    backgroundColor: ["#007bff", "#28a745", "#17a2b8", "#ffc107"]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
    <?php endforeach; ?>

    <?php foreach ($interviewsPerHirePerJobPost as $index => $job): ?>
        new Chart(document.getElementById("interviewsChart<?= $index ?>").getContext("2d"), {
            type: "bar",
            data: {
                labels: ["Avg Interviews Per Hire"],
                datasets: [{
                    label: "Interviews Per Hire",
                    data: [<?= round($job['avg_interviews_per_hire_for_job_post'], 2) ?>],
                    backgroundColor: ["#dc3545"]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    <?php endforeach; ?>
};
</script>
</html>