<?php
require 'db.php';

if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $query = isset($_POST['query']) ? trim($_POST['query']) : '';

    // Fetch jobs by status and search query
    $sql = "SELECT * FROM job_posts WHERE status = :status AND (job_title LIKE :query OR partner_company LIKE :query)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $status, 'query' => "%$query%"]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($jobs as $job) {
        echo "
        <div class='card mb-3'>
            <div class='card-body'>
                <h5 class='card-title'>{$job['job_title']}</h5>
                <p class='card-text'>{$job['description']}</p>
                <p><strong>Company:</strong> {$job['partner_company']}</p>
                <p><strong>Location:</strong> {$job['location']}</p>
                <p><strong>Salary:</strong> {$job['min_salary']} - {$job['max_salary']}</p>

                <!-- Status Dropdown -->
                <label for='status-{$job['job_post_id']}'>Update Status:</label>
                <select class='form-select status-dropdown' id='status-{$job['job_post_id']}' data-id='{$job['job_post_id']}'>
                    <option value='Pending' " . ($job['status'] == 'Pending' ? 'selected' : '') . ">Pending</option>
                    <option value='Open' " . ($job['status'] == 'Open' ? 'selected' : '') . ">Open</option>
                    <option value='Closed' " . ($job['status'] == 'Closed' ? 'selected' : '') . ">Closed</option>
                </select>

                <button class='btn btn-primary view-more mt-3' data-id='{$job['job_post_id']}'>View More</button>
                <a href='edit_job.php?job_post_id={$job['job_post_id']}' class='btn btn-secondary mt-3'>Edit</a>
            </div>
        </div>
        ";
    }
    exit();
}

if (isset($_POST['job_id']) && !isset($_POST['update_job_status'])) {
    $jobId = $_POST['job_id'];

    // Fetch job details
    $sql = "SELECT * FROM job_posts WHERE job_post_id = :job_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['job_id' => $jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch job metrics
    $metricsSql = "SELECT * FROM job_metrics WHERE job_post_id = :job_id";
    $metricsStmt = $pdo->prepare($metricsSql);
    $metricsStmt->execute(['job_id' => $jobId]);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);

    echo "
    <h5>{$job['job_title']} (ID: {$job['job_post_id']})</h5>
    <p>{$job['description']}</p>
    <p><strong>Company:</strong> {$job['partner_company']}</p>
    <p><strong>Location:</strong> {$job['location']}</p>
    <p><strong>Salary:</strong> {$job['min_salary']} - {$job['max_salary']}</p>
    <h6>Summary:</h6>
    <ul>
        <li><strong>Total Applicants:</strong> {$metrics['total_applicants']}</li>
        <li><strong>Successful Placements:</strong> {$metrics['successful_placements']}</li>
        <li><strong>Dropout Rate:</strong> {$metrics['dropout_rate']}%</li>
    </ul>
    ";
    exit();
}

if (isset($_POST['update_job_status'])) {
    $jobId = (int)$_POST['job_id'];
    $newStatus = $_POST['new_status'];

    // Update job status
    $sql = "UPDATE job_posts SET status = :new_status WHERE job_post_id = :job_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['new_status' => $newStatus, 'job_id' => $jobId]);

    if ($stmt->rowCount() > 0) {
        echo "Success";
    } else {
        echo "Error updating the job status.";     
    }
    exit();
}
?>
