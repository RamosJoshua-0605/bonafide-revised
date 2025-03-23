<?php 
require 'db.php';
include 'header.php';
include 'sidebar.php';

// Fetch all open job posts
$sql = "SELECT * FROM job_posts WHERE status = 'open'";
$stmt = $pdo->query($sql);
$jobPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Job Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .description {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .description.full {
            white-space: normal;
        }
    </style>
</head>
<body>
    <div id='content'>
    <div class="container mt-5">
        <h1 class="mb-4">Available Job Posts</h1>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" id="search" class="form-control" placeholder="Search for jobs by title, company, location, etc.">
        </div>

        <!-- Job Posts Container -->
        <div id="job-posts">
            <?php if (count($jobPosts) > 0): ?>
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
                                    <a href="apply.php?job_post_id=<?= $job['job_post_id'] ?>" class="btn btn-primary mt-3">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No open job posts available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButtons = document.querySelectorAll('.toggle-description');

            toggleButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const descriptionId = button.getAttribute('data-id');
                    const description = document.getElementById(`description-${descriptionId}`);
                    const isFull = description.classList.toggle('full');

                    // Update button text and icon
                    if (isFull) {
                        button.innerHTML = 'View Less <i class="bi bi-chevron-up"></i>';
                    } else {
                        button.innerHTML = 'View More <i class="bi bi-chevron-down"></i>';
                    }
                });
            });

            // Search functionality
            const searchInput = document.getElementById('search');
            const jobPostsContainer = document.getElementById('job-posts');

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase();

                fetch('search_jobs.php?q=' + encodeURIComponent(query))
                    .then(response => response.text())
                    .then(html => {
                        jobPostsContainer.innerHTML = html;
                    });
            });
        });
    </script>
</body>
</html>
