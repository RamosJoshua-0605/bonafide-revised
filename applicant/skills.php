<?php
ob_start();
require 'db.php'; // Include database connection
include 'header.php';
include 'sidebar.php';
require 'auth.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$errors = [];
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch user ID from the session
    $user_id = $_SESSION['user_id'];

    // Retrieve form data
    $skill_names = $_POST['skill_name'] ?? [];
    $skill_categories = $_POST['skill_category'] ?? [];
    $proficiency_levels = $_POST['proficiency_level'] ?? [];

    // Validate inputs
    if (empty($skill_names) || empty($skill_categories) || empty($proficiency_levels)) {
        $errors[] = "All fields are required. Please fill out the form completely.";
    }

    if (empty($errors)) {
        try {
            // Begin a transaction
            $pdo->beginTransaction();

            // Prepare the SQL statement
            $stmt = $pdo->prepare("
                INSERT INTO skills (
                    user_id, skill_name, skill_category, proficiency_level
                ) VALUES (
                    :user_id, :skill_name, :skill_category, :proficiency_level
                )
            ");

            // Loop through skills and insert them into the database
            for ($i = 0; $i < count($skill_names); $i++) {
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':skill_name' => $skill_names[$i],
                    ':skill_category' => $skill_categories[$i],
                    ':proficiency_level' => $proficiency_levels[$i],
                ]);
            }

            // Commit the transaction
            $pdo->commit();
            $success_message = "Skills added successfully!";
            header("Location: experience.php"); // Redirect to the next page
            exit;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $pdo->rollBack();
            error_log("Database Error: " . $e->getMessage());
            $errors[] = "An error occurred while saving skills. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Skills</title>
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
        <h2>Add Skills</h2>
        <form id="skillsForm" action="" method="POST">
            <div id="skillsContainer">
                <div class="skill-group">
                    <div class="mb-3">
                        <label for="skill_name[]" class="form-label">Skill Name</label>
                        <input type="text" class="form-control" name="skill_name[]" required>
                        <div class="invalid-feedback">Skill name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="skill_category[]" class="form-label">Skill Category</label>
                        <select class="form-select" name="skill_category[]" required>
                            <option value="" disabled selected>Select a category</option>
                            <option value="Technical">Technical</option>
                            <option value="Soft Skills">Soft Skills</option>
                            <option value="Management">Management</option>
                            <option value="Creative">Creative</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Skill category is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="proficiency_level[]" class="form-label">Proficiency Level</label>
                        <select class="form-select" name="proficiency_level[]" required>
                            <option value="" disabled selected>Select proficiency level</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Expert">Expert</option>
                        </select>
                        <div class="invalid-feedback">Proficiency level is required.</div>
                    </div>
                    <button type="button" class="btn btn-danger removeSkill">Remove Skill</button>
                    <hr>
                </div>
            </div>
            <button type="button" id="addSkill" class="btn btn-success">Add Another Skill</button>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="experience.php" class="btn btn-secondary">Skip</a>
            <div class="mt-3 text-danger" id="errorMessage" style="display: none;">Please add at least one skill before submitting.</div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("skillsForm");
    const addSkillBtn = document.getElementById("addSkill");
    const skillsContainer = document.getElementById("skillsContainer");
    const errorMessage = document.getElementById("errorMessage");

    // Disable default HTML5 validation
    form.setAttribute("novalidate", true);

    // Add new skill fields
    addSkillBtn.addEventListener("click", function () {
        const newGroup = document.createElement("div");
        newGroup.classList.add("skill-group", "border", "p-3", "mb-3");
        newGroup.innerHTML = `
            <div class="mb-3">
                <label for="skill_name[]" class="form-label">Skill Name</label>
                <input type="text" class="form-control" name="skill_name[]" required>
                <div class="invalid-feedback">Skill name is required.</div>
            </div>
            <div class="mb-3">
                <label for="skill_category[]" class="form-label">Skill Category</label>
                <select class="form-select" name="skill_category[]" required>
                    <option value="" disabled selected>Select a category</option>
                    <option value="Technical">Technical</option>
                    <option value="Soft Skills">Soft Skills</option>
                    <option value="Management">Management</option>
                    <option value="Creative">Creative</option>
                    <option value="Other">Other</option>
                </select>
                <div class="invalid-feedback">Skill category is required.</div>
            </div>
            <div class="mb-3">
                <label for="proficiency_level[]" class="form-label">Proficiency Level</label>
                <select class="form-select" name="proficiency_level[]" required>
                    <option value="" disabled selected>Select proficiency level</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                    <option value="Expert">Expert</option>
                </select>
                <div class="invalid-feedback">Proficiency level is required.</div>
            </div>
            <button type="button" class="btn btn-danger removeSkill">Remove Skill</button>
            <hr>
        `;
        skillsContainer.appendChild(newGroup);
    });

    // Remove skill group
    skillsContainer.addEventListener("click", function (e) {
        if (e.target.classList.contains("removeSkill")) {
            e.target.closest(".skill-group").remove();
        }
    });

    // Custom form validation
    form.addEventListener("submit", function (e) {
        const skillGroups = document.querySelectorAll(".skill-group");
        let isValid = true;

        // Check if there are no skill groups
        if (skillGroups.length === 0) {
            errorMessage.style.display = "block";
            isValid = false;
        } else {
            errorMessage.style.display = "none";
        }

        // Validate each input field in all groups
        skillGroups.forEach(group => {
            const inputs = group.querySelectorAll("input, select");
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