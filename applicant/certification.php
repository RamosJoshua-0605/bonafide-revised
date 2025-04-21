<?php
ob_start();
require 'db.php'; // Include database connection
include 'header.php';
include 'sidebar.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
} else if (!isset($_SESSION['login_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch user ID from the session
    $user_id = $_SESSION['user_id'];

    // Retrieve form data
    $certification_names = $_POST['certification_name'] ?? [];
    $certification_institutes = $_POST['certification_institute'] ?? [];
    $years_taken = $_POST['year_taken_certification'] ?? [];
    $certification_categories = $_POST['certification_category'] ?? [];
    $certificate_files = $_FILES['certificate_file'] ?? [];

    // Validate inputs
    if (empty($certification_names) || empty($certification_institutes) || empty($years_taken) || empty($certification_categories)) {
        $errors[] = "All fields are required. Please fill out the form completely.";
    }

    if (empty($errors)) {
        try {
            // Begin a transaction
            $pdo->beginTransaction();

            // Prepare the SQL statement
            $stmt = $pdo->prepare("
                INSERT INTO user_certifications (
                    user_id, certification_name, certification_institute, year_taken_certification, certification_category, certificate_image_path
                ) VALUES (
                    :user_id, :certification_name, :certification_institute, :year_taken_certification, :certification_category, :certificate_image_path
                )
            ");

            // Loop through certifications and insert them into the database
            for ($i = 0; $i < count($certification_names); $i++) {
                // Handle file upload
                $file_path = null;
                if (isset($certificate_files['tmp_name'][$i]) && is_uploaded_file($certificate_files['tmp_name'][$i])) {
                    // Check for upload errors
                    if ($certificate_files['error'][$i] !== UPLOAD_ERR_OK) {
                        throw new Exception("File upload error: " . $certificate_files['error'][$i]);
                    }

                    // Set the upload directory relative to the current script
                    $upload_dir = __DIR__ . '/uploads/certificates/';
                    $file_name = uniqid() . '_' . basename($certificate_files['name'][$i]);
                    $file_path = $upload_dir . $file_name;

                    // Ensure the upload directory exists
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    // Move the uploaded file to the target directory
                    if (!move_uploaded_file($certificate_files['tmp_name'][$i], $file_path)) {
                        throw new Exception("Failed to upload file: " . $certificate_files['name'][$i]);
                    }

                    // Save the relative path for database storage
                    $file_path = 'uploads/certificates/' . $file_name;
                }

                // Execute the query
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':certification_name' => $certification_names[$i],
                    ':certification_institute' => $certification_institutes[$i],
                    ':year_taken_certification' => $years_taken[$i],
                    ':certification_category' => $certification_categories[$i],
                    ':certificate_image_path' => $file_path,
                ]);
            }

            // Commit the transaction
            $pdo->commit();
            $success_message = "Certifications added successfully!";
            header("Location: skills.php");
            exit;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $pdo->rollBack();
            error_log("Database Error: " . $e->getMessage());
            $errors[] = "An error occurred while saving certifications. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Certifications</title>
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
        <h2>Add Certifications</h2>
        <form id="certificationForm" action="" method="POST" enctype="multipart/form-data">        <div id="certificationsContainer">
    <div class="certification-group">
        <div class="mb-3">
            <label for="certification_name[]" class="form-label">Certification Name</label>
            <input type="text" class="form-control" name="certification_name[]" required>
            <div class="invalid-feedback">Certification name is required.</div>
        </div>
        <div class="mb-3">
            <label for="certification_institute[]" class="form-label">Certification Institute</label>
            <input type="text" class="form-control" name="certification_institute[]" required>
            <div class="invalid-feedback">Certification institute is required.</div>
        </div>
        <div class="mb-3">
            <label for="year_taken_certification[]" class="form-label">Year Taken</label>
            <input type="number" class="form-control" name="year_taken_certification[]" min="1900" max="2100" required>
            <div class="invalid-feedback">Year taken is required and must be valid.</div>
        </div>
        <div class="mb-3">
            <label for="certification_category[]" class="form-label">Certification Category</label>
            <select class="form-select" name="certification_category[]" required>
                <option value="" disabled selected>Select an industry</option>
                <option value="Agriculture">Agriculture</option>
                <option value="Arts, Media, and Entertainment">Arts, Media, and Entertainment</option>
                <option value="Building and Construction">Building and Construction</option>
                <option value="Business and Finance">Business and Finance</option>
                <option value="Education">Education</option>
                <option value="Engineering and Architecture">Engineering and Architecture</option>
                <option value="Fashion and Interior Design">Fashion and Interior Design</option>
                <option value="Health Science and Medical Technology">Health Science and Medical Technology</option>
                <option value="Hospitality">Hospitality</option>
                <option value="Manufacturing and Product Development">Manufacturing and Product Development</option>
                <option value="Marketing, Sales, and Services">Marketing, Sales, and Services</option>
                <option value="Public Services">Public Services</option>
                <option value="Transportation">Transportation</option>
            </select>
            <div class="invalid-feedback">Certification category is required.</div>
        </div>
        <div class="mb-3">
            <label for="certificate_file[]" class="form-label">Upload Certification (Image or PDF)</label>
            <input type="file" class="form-control" name="certificate_file[]" accept=".jpg,.jpeg,.png,.pdf" required>
            <div class="invalid-feedback">Please upload a valid image or PDF file.</div>
        </div>
        <hr>
    </div>
</div>
            <button type="button" id="addCertification" class="btn btn-success">Add Another Certification</button>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="experience.php" class="btn btn-secondary">Skip</a>
            <div class="mt-3 text-danger" id="errorMessage" style="display: none;">Please add at least one certification before submitting.</div>
        </form>
    </div>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("certificationForm");
    const addCertificationBtn = document.getElementById("addCertification");
    const certificationsContainer = document.getElementById("certificationsContainer");
    const errorMessage = document.getElementById("errorMessage");

    // Disable default HTML5 validation
    form.setAttribute("novalidate", true);

    // Add new certification fields
    addCertificationBtn.addEventListener("click", function () {
        const newGroup = document.createElement("div");
        newGroup.classList.add("certification-group", "border", "p-3", "mb-3");
        newGroup.innerHTML = `
           <div class="mb-3">
            <label for="certification_name[]" class="form-label">Certification Name</label>
            <input type="text" class="form-control" name="certification_name[]" required>
            <div class="invalid-feedback">Certification name is required.</div>
        </div>
        <div class="mb-3">
            <label for="certification_institute[]" class="form-label">Certification Institute</label>
            <input type="text" class="form-control" name="certification_institute[]" required>
            <div class="invalid-feedback">Certification institute is required.</div>
        </div>
        <div class="mb-3">
            <label for="year_taken_certification[]" class="form-label">Year Taken</label>
            <input type="number" class="form-control" name="year_taken_certification[]" min="1900" max="2100" required>
            <div class="invalid-feedback">Year taken is required and must be valid.</div>
        </div>
        <div class="mb-3">
            <label for="certification_category[]" class="form-label">Certification Category</label>
            <select class="form-select" name="certification_category[]" required>
                <option value="" disabled selected>Select an industry</option>
                <option value="Agriculture">Agriculture</option>
                <option value="Arts, Media, and Entertainment">Arts, Media, and Entertainment</option>
                <option value="Building and Construction">Building and Construction</option>
                <option value="Business and Finance">Business and Finance</option>
                <option value="Education">Education</option>
                <option value="Engineering and Architecture">Engineering and Architecture</option>
                <option value="Fashion and Interior Design">Fashion and Interior Design</option>
                <option value="Health Science and Medical Technology">Health Science and Medical Technology</option>
                <option value="Hospitality">Hospitality</option>
                <option value="Manufacturing and Product Development">Manufacturing and Product Development</option>
                <option value="Marketing, Sales, and Services">Marketing, Sales, and Services</option>
                <option value="Public Services">Public Services</option>
                <option value="Transportation">Transportation</option>
            </select>
            <div class="invalid-feedback">Certification category is required.</div>
        </div>
        <div class="mb-3">
            <label for="certificate_file[]" class="form-label">Upload Certification (Image or PDF)</label>
            <input type="file" class="form-control" name="certificate_file[]" accept=".jpg,.jpeg,.png,.pdf" required>
            <div class="invalid-feedback">Please upload a valid image or PDF file.</div>
        </div>
        <hr>
        `;
        certificationsContainer.appendChild(newGroup);
    });

    // Remove certification group
    certificationsContainer.addEventListener("click", function (e) {
        if (e.target.classList.contains("removeCertification")) {
            e.target.closest(".certification-group").remove();
        }
    });

    // Custom form validation
    form.addEventListener("submit", function (e) {
        const certificationGroups = document.querySelectorAll(".certification-group");
        let isValid = true;

        // Check if there are no certification groups
        if (certificationGroups.length === 0) {
            errorMessage.style.display = "block";
            isValid = false;
        } else {
            errorMessage.style.display = "none";
        }

        form.addEventListener("submit", function (e) {
    const certificationGroups = document.querySelectorAll(".certification-group");
    let isValid = true;

    // Check if there are no certification groups
    if (certificationGroups.length === 0) {
        errorMessage.style.display = "block";
        isValid = false;
    } else {
        errorMessage.style.display = "none";
    }

    // Validate each input field in all groups
    certificationGroups.forEach(group => {
        const inputs = group.querySelectorAll("input, select");
        inputs.forEach(input => {
            if (input.name === "year_taken_certification[]") {
                const year = parseInt(input.value, 10);
                if (isNaN(year) || year < 1900 || year > 2100) {
                    input.classList.add("is-invalid");
                    input.nextElementSibling.style.display = "block"; // Show invalid feedback
                    isValid = false;
                } else {
                    input.classList.remove("is-invalid");
                    input.nextElementSibling.style.display = "none"; // Hide invalid feedback
                }
            } else if (input.name === "certificate_file[]" && input.files.length === 0) {
                input.classList.add("is-invalid");
                input.nextElementSibling.style.display = "block"; // Show invalid feedback
                isValid = false;
            } else if (input.name === "certification_category[]" && input.value === "") {
                input.classList.add("is-invalid");
                input.nextElementSibling.style.display = "block"; // Show invalid feedback
                isValid = false;
            } else if (!input.value.trim()) {
                input.classList.add("is-invalid");
                input.nextElementSibling.style.display = "block"; // Show invalid feedback
                isValid = false;
            } else {
                input.classList.remove("is-invalid");
                input.nextElementSibling.style.display = "none"; // Hide invalid feedback
            }
        });
    });

    if (!isValid) {
        e.preventDefault(); // Prevent form submission if validation fails
    }
});

    // Remove red border and invalid feedback when input is valid
    certificationsContainer.addEventListener("input", function (event) {
        if (event.target.classList.contains("form-control")) {
            if (event.target.name === "year_taken_certification[]") {
                const year = parseInt(event.target.value, 10);
                if (!isNaN(year) && year >= 1900 && year <= 2100) {
                    event.target.classList.remove("is-invalid");
                    event.target.nextElementSibling.style.display = "none"; // Hide invalid feedback
                }
            } else if (event.target.value.trim()) {
                event.target.classList.remove("is-invalid");
                event.target.nextElementSibling.style.display = "none"; // Hide invalid feedback
            }
        }
    });
});});
</script>

</body>
</html>