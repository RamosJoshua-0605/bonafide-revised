<?php
require 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applicants</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Applicant Profiles</h1>

    <!-- Search Bar -->
    <div class="mb-4">
        <input type="text" id="search" class="form-control" placeholder="Search applicants by name, email, or phone number">
    </div>

    <?php
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
    if ($message): ?>
        <div class="alert alert-success">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- <button type="button" class="btn btn-success mt-3" id="openModalButton" data-bs-toggle="modal" data-bs-target="#emailModal">Send Email</button> -->


    <!-- Applicants Container -->
    <div id="applicants-container">
        <!-- Applicant cards will be loaded here dynamically -->
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('search');
        const applicantsContainer = document.getElementById('applicants-container');

        const fetchApplicants = (query = '', page = 1) => {
            fetch(`search_applicants.php?q=${encodeURIComponent(query)}&page=${page}`)
                .then(response => response.text())
                .then(html => {
                    applicantsContainer.innerHTML = html;
                });
        };

        // Initial load
        fetchApplicants();

        // Search input event
        searchInput.addEventListener('input', () => {
            fetchApplicants(searchInput.value.trim());
        });

        // Delegate pagination links
        applicantsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link')) {
                e.preventDefault();
                const url = new URL(e.target.href);
                const query = url.searchParams.get('q');
                const page = url.searchParams.get('page');
                fetchApplicants(query, page);
            }
        });
    });
</script>
</body>
</html>
