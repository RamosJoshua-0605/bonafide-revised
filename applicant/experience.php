<?php
require 'db.php'; // Include database connection
include 'header.php';
include 'sidebar.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch user ID from the session
    $user_id = $_SESSION['user_id'];

    // Retrieve form data
    $company_names = $_POST['company_name'] ?? [];
    $roles = $_POST['role'] ?? [];
    $years_worked = $_POST['years_worked'] ?? [];

    // Validate inputs
    if (empty($company_names) || empty($roles) || empty($years_worked)) {
        $errors[] = "All fields are required. Please fill out the form completely.";
    }

    if (empty($errors)) {
        try {
            // Begin a transaction
            $pdo->beginTransaction();

            // Prepare the SQL statement
            $stmt = $pdo->prepare("
                INSERT INTO user_work_experience (
                    user_id, company_name, role, years_worked
                ) VALUES (
                    :user_id, :company_name, :role, :years_worked
                )
            ");

            // Loop through work experiences and insert them into the database
            for ($i = 0; $i < count($company_names); $i++) {
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':company_name' => $company_names[$i],
                    ':role' => $roles[$i],
                    ':years_worked' => $years_worked[$i],
                ]);
            }

            // Commit the transaction
            $pdo->commit();
            $success_message = "Work experiences added successfully!";
            header("Location: dashboard.php"); // Redirect to the next page
            exit;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $pdo->rollBack();
            error_log("Database Error: " . $e->getMessage());
            $errors[] = "An error occurred while saving work experiences. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Work Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .is-invalid {
            border-color: #dc3545;
        }
    </style>
</head>
<body>
<div id='content'>
    <div class="container mt-5">
        <h2>Add Work Experience</h2>
        <form id="experienceForm" action="" method="POST">
            <div id="experiencesContainer">
                <div class="experience-group">
                    <div class="mb-3">
                        <label for="company_name[]" class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company_name[]" required>
                        <div class="invalid-feedback">Company name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="role[]" class="form-label">Role</label>
                        <input type="text" class="form-control" name="role[]" required>
                        <div class="invalid-feedback">Role is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="years_worked[]" class="form-label">Years Worked</label>
                        <input type="number" class="form-control" name="years_worked[]" min="0" max="50" required>
                        <div class="invalid-feedback">Please enter a valid number of years worked.</div>
                    </div>
                    <hr>
                </div>
            </div>
            <button type="button" id="addExperience" class="btn btn-success">Add Another Work Experience</button>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="dashboard.php" class="btn btn-secondary">Skip</a>
            <div class="mt-3 text-danger" id="errorMessage" style="display: none;">Please add at least one work experience before submitting.</div>
        </form>
    </div>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("experienceForm");
    const addExperienceBtn = document.getElementById("addExperience");
    const experiencesContainer = document.getElementById("experiencesContainer");
    const errorMessage = document.getElementById("errorMessage");

    // Disable default HTML5 validation
    form.setAttribute("novalidate", true);

    // Add new experience fields
    addExperienceBtn.addEventListener("click", function () {
        const newGroup = document.createElement("div");
        newGroup.classList.add("experience-group", "border", "p-3", "mb-3");
        newGroup.innerHTML = `
            <div class="mb-3">
                <label for="company_name[]" class="form-label">Company Name</label>
                <input type="text" class="form-control" name="company_name[]" required>
                <div class="invalid-feedback">Company name is required.</div>
            </div>
            <div class="mb-3">
                <label for="role[]" class="form-label">Role</label>
                <input type="text" class="form-control" name="role[]" required>
                <div class="invalid-feedback">Role is required.</div>
            </div>
            <div class="mb-3">
                <label for="years_worked[]" class="form-label">Years Worked</label>
                <input type="number" class="form-control" name="years_worked[]" min="0" max="50" required>
                <div class="invalid-feedback">Please enter a valid number of years worked.</div>
            </div>
            <button type="button" class="btn btn-danger removeExperience">Remove Work Experience</button>
            <hr>
        `;
        experiencesContainer.appendChild(newGroup);
    });

    // Remove experience group
    experiencesContainer.addEventListener("click", function (e) {
        if (e.target.classList.contains("removeExperience")) {
            e.target.closest(".experience-group").remove();
        }
    });

    // Custom form validation
    form.addEventListener("submit", function (e) {
        const experienceGroups = document.querySelectorAll(".experience-group");
        let isValid = true;

        // Check if there are no experience groups
        if (experienceGroups.length === 0) {
            errorMessage.style.display = "block";
            isValid = false;
        } else {
            errorMessage.style.display = "none";
        }

        // Validate each input field in all groups
        experienceGroups.forEach(group => {
            const inputs = group.querySelectorAll("input");
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add("is-invalid");
                    input.nextElementSibling.style.display = "block";
                    isValid = false;
                } else {
                    input.classList.remove("is-invalid");
                    input.nextElementSibling.style.display = "none";
                }
            });
        });

        if (!isValid) {
            e.preventDefault(); // Prevent form submission if validation fails
        }
    });
});
</script>
</body>
</html>
