<?php
require 'db.php';

// Get application_id and comments from the POST request
$application_id = $_POST['application_id'] ?? null;
$comments = $_POST['comments'] ?? null;

if (!$application_id || !$comments) {
    die("Invalid application ID or comments.");
}

// Update comments in the database
$query = $pdo->prepare("
    UPDATE job_applications
    SET comments = :comments
    WHERE application_id = :application_id
");
$query->execute([
    'comments' => $comments,
    'application_id' => $application_id
]);

// Redirect back to the application details page
header("Location: view_application_details.php?application_id=" . urlencode($application_id));
exit;
?>