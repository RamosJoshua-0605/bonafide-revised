<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $application_id = $_POST['application_id'] ?? null;
    $requirements = $_POST['requirements'] ?? [];

    if (!$application_id) {
        die("Invalid application ID.");
    }

    // Clear existing checked requirements
    $deleteStmt = $pdo->prepare("DELETE FROM checked_requirements WHERE application_id = :application_id");
    $deleteStmt->execute(['application_id' => $application_id]);

    // Insert new checked requirements
    if (!empty($requirements)) {
        $insertStmt = $pdo->prepare("INSERT INTO checked_requirements (application_id, requirement) VALUES (:application_id, :requirement)");
        
        foreach ($requirements as $requirement) {
            $insertStmt->execute([
                'application_id' => $application_id,
                'requirement' => $requirement
            ]);
        }
    }

    // Redirect back to the application details page
    header("Location: view_application_details.php?application_id=" . $application_id);
    exit;
}
?>
