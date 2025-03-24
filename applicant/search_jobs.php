<?php
ob_start();
require 'db.php'; // Ensure this file establishes the $pdo connection

require 'auth.php';

$user_id = $_SESSION['user_id']; // Get user ID from session

// Get search query if available
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$conditions = ["status = 'open'"];
$params = [];

// If query is numeric, filter by salary range
if (is_numeric($query)) {
    $conditions[] = "(min_salary <= :salary AND max_salary >= :salary)";
    $params[':salary'] = (int) $query;
} else {
    // Add text search filters only if the query is not purely numeric
    if (!empty($query)) {
        $searchConditions = [
            "job_title LIKE :query",
            "partner_company LIKE :query",
            "location LIKE :query",
            "description LIKE :query",
            "preferred_educational_level LIKE :query",
            "preferred_age_range LIKE :query",
            "preferred_work_experience LIKE :query"
        ];
        
        $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        $params[':query'] = '%' . $query . '%';
    }
}

// Final SQL query with filtering
$sql = "SELECT * FROM job_posts WHERE " . implode(" AND ", $conditions);
$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$jobPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter out jobs where the user has applied (unless withdrawn)
$filteredJobPosts = [];

foreach ($jobPosts as $job) {
    $job_post_id = $job['job_post_id'];

    // Get the application status (if exists)
    $stmt = $pdo->prepare("SELECT status FROM job_applications WHERE job_post_id = ? AND user_id = ?");
    $stmt->execute([$job_post_id, $user_id]);
    $application_status = $stmt->fetchColumn();

    // Only hide jobs where the user applied and status is NOT 'Withdrawn'
    if (!$application_status || strtolower($application_status) === 'withdrawn') {
        $filteredJobPosts[] = $job;
    }
}

// Display job posts
if (count($filteredJobPosts) > 0): ?>
    <div class="row">
        <div id="job-posts">
            <div class="row">
                <?php foreach ($filteredJobPosts as $job): ?>
                    <div class="col-md-6 col-lg-4 mb-4 job-post">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($job['job_title']) ?></h5>
                                <?php if (!empty($job['partner_company'])): ?>
                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($job['partner_company']) ?></h6>
                                <?php endif; ?>

                                <p class="card-text">
                                    <?php if (!empty($job['location'])): ?>
                                        <strong>Location:</strong> <?= htmlspecialchars($job['location']) ?><br>
                                    <?php endif; ?>
                                    <?php if ($job['min_salary'] > 0 && $job['max_salary'] > 0): ?>
                                        <strong>Salary Range:</strong> ₱<?= number_format($job['min_salary']) ?> - ₱<?= number_format($job['max_salary']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($job['preferred_educational_level'])): ?>
                                        <strong>Preferred Education Level:</strong> <?= htmlspecialchars($job['preferred_educational_level']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($job['preferred_work_experience'])): ?>
                                        <strong>Preferred Work Experience:</strong> <?= htmlspecialchars($job['preferred_work_experience']) ?><br>
                                    <?php endif; ?>
                                </p>
                                <div>
                                    <p class="description mb-2" id="description-<?= $job['job_post_id'] ?>">
                                        <?= nl2br(htmlspecialchars($job['description'])) ?>
                                    </p>
                                    <?php if (strlen($job['description']) > 100): ?>
                                        <button class="btn btn-link p-0 text-decoration-none toggle-description" 
                                                data-id="<?= $job['job_post_id'] ?>" 
                                                style="font-size: 14px;">
                                            View More <i class="bi bi-chevron-down"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <a href="apply.php?job_post_id=<?= $job['job_post_id'] ?>" 
                                class="btn btn-primary mt-3">
                                Apply Now
                                </a>                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">No open job posts available at the moment.</div>
<?php endif; ?>
