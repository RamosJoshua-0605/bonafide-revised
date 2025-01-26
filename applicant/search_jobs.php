<?php
require 'db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT * FROM job_posts WHERE status = 'open'";

$conditions = [];
$params = [];

// Add text-based conditions
if (!empty($query)) {
    $conditions[] = "job_title LIKE :query";
    $conditions[] = "partner_company LIKE :query";
    $conditions[] = "location LIKE :query";
    $conditions[] = "description LIKE :query";
    $conditions[] = "preferred_educational_level LIKE :query";
    $conditions[] = "preferred_age_range LIKE :query";
    $conditions[] = "preferred_work_experience LIKE :query";
    $params[':query'] = '%' . $query . '%';
}

// Add numeric-based conditions for salary if the query is numeric
if (is_numeric($query)) {
    $conditions[] = "(:salary BETWEEN min_salary AND max_salary)";
    $params[':salary'] = (int) $query;
}

// Combine conditions if any exist
if (!empty($conditions)) {
    $sql .= " AND (" . implode(" OR ", $conditions) . ")";
}

$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();

$jobPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($jobPosts) > 0): ?>
    <div class="row">
        <?php foreach ($jobPosts as $job): ?>
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
                            <?php if (!($job['min_salary'] == 0.00) && !($job['max_salary'] == 0.00)): ?>
                                <strong>Salary Range:</strong> ₱<?= number_format($job['min_salary']) ?> - ₱<?= number_format($job['max_salary']) ?><br>
                            <?php endif; ?>
                        </p>
                        <div>
                            <p class="description mb-2"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                        </div>
                        <a href="apply.php?job_post_id=<?= $job['job_post_id'] ?>" class="btn btn-primary mt-3">Apply Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">No job posts match your search criteria.</div>
<?php endif; ?>
