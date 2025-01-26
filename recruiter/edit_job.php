<?php
require 'db.php'; // Include database connection

// Redirect if not logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success_message = null;

// Fetch the job ID from the URL
$job_id = $_GET['job_post_id'] ?? null;

if (!$job_id) {
    $errors[] = "No job ID provided.";
    die();
}

// Fetch job details
try {
    $stmt = $pdo->prepare("SELECT * FROM job_posts WHERE job_post_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        $errors[] = "Job not found.";
        die();
    }

    // Fetch associated requirements
    $stmt = $pdo->prepare("SELECT * FROM job_requirements WHERE job_post_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $job_requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch associated questions
    $stmt = $pdo->prepare("SELECT * FROM questionnaires WHERE job_post_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $job_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $preferred_age_range = $job['preferred_age_range'] ?? '';

    if (strpos($preferred_age_range, '-') !== false) {
        list($min_age, $max_age) = explode('-', $preferred_age_range);
        $job['min_age'] = trim($min_age);
        $job['max_age'] = trim($max_age);
    } else {
        $job['min_age'] = '';
        $job['max_age'] = '';
    }

} catch (Exception $e) {
    $errors[] = "Error fetching job details: " . $e->getMessage();
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch user ID from session
    $login_id = $_SESSION['login_id'];

    // Retrieve form data
    $job_title = $_POST['job_title'] ?? '';
    $partner_company = $_POST['partner_company'] ?? '';
    $location = $_POST['location'] ?? '';
    $min_salary = $_POST['min_salary'] ?? '';
    $max_salary = $_POST['max_salary'] ?? '';
    $description = $_POST['description'] ?? '';
    $openings = $_POST['openings'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    
    $preferred_educational_level = $_POST['preferred_educational_level'] ?? '';
    $preferred_work_experience = $_POST['preferred_work_experience'] ?? '';
    $status = $_POST['status'] ?? 'open';
    $min_age = $_POST['min_age'] ?? '';
    $max_age = $_POST['max_age'] ?? '';

    if ($min_age && $max_age) {
        $preferred_age_range = $min_age . ' - ' . $max_age;
    }

    $requirements = $_POST['requirements'] ?? [];
    $questions = $_POST['questions'] ?? [];

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update job post
            $stmt = $pdo->prepare("
                UPDATE job_posts
                SET job_title = :job_title, partner_company = :partner_company, location = :location,
                    min_salary = :min_salary, max_salary = :max_salary, description = :description,
                    openings = :openings, deadline = :deadline, status = :status,
                    preferred_educational_level = :preferred_educational_level, preferred_age_range = :preferred_age_range,
                    preferred_work_experience = :preferred_work_experience
                WHERE job_post_id = :job_id
            ");
            $stmt->execute([
                ':job_title' => $job_title,
                ':partner_company' => $partner_company,
                ':location' => $location,
                ':min_salary' => $min_salary,
                ':max_salary' => $max_salary,
                ':description' => $description,
                ':openings' => $openings,
                ':deadline' => $deadline,
                ':status' => $status,
                ':preferred_educational_level' => $preferred_educational_level,
                ':preferred_age_range' => $preferred_age_range,
                ':preferred_work_experience' => $preferred_work_experience,
                ':job_id' => $job_id,
            ]);

            // Update requirements
            $stmt = $pdo->prepare("DELETE FROM job_requirements WHERE job_post_id = :job_id");
            $stmt->execute([':job_id' => $job_id]);
            if (!empty($requirements)) {
                $stmt = $pdo->prepare("INSERT INTO job_requirements (job_post_id, requirement_name) VALUES (:job_post_id, :requirement_name)");
                foreach ($requirements as $requirement) {
                    $stmt->execute([':job_post_id' => $job_id, ':requirement_name' => $requirement]);
                }
            }

            // Update questions
            $stmt = $pdo->prepare("DELETE FROM questionnaires WHERE job_post_id = :job_id");
            $stmt->execute([':job_id' => $job_id]);
            if (!empty($questions)) {
                $stmt = $pdo->prepare("
                    INSERT INTO questionnaires (job_post_id, question_text, dealbreaker, correct_answer)
                    VALUES (:job_post_id, :question_text, :dealbreaker, :correct_answer)
                ");
                foreach ($questions as $question) {
                    $stmt->execute([
                        ':job_post_id' => $job_id,
                        ':question_text' => $question['question_text'],
                        ':dealbreaker' => $question['dealbreaker'] === 'yes' ? 1 : 0,
                        ':correct_answer' => $question['correct_answer'] === 'yes' ? 1 : 0,
                    ]);
                }
            }

            $pdo->commit();
            $success_message = "Job post successfully updated!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error updating job post: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .is-invalid {
            border-color: #dc3545;
        }
        .salary-container, .age-container {
            display: flex;
            gap: 10px;
        }
        .salary-container input, .age-container input {
            width: 48%;
        }
        .form-control, .form-select {
            width: 100%;
        }
        .invalid-feedback {
            display: none;
        }
        .requirement-item, .question-item {
            margin-bottom: 10px;
        }
        .requirement-item input, .question-item input {
            margin-bottom: 5px;
        }

    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Edit Job Post</h2>

    <!-- Display success or error messages -->
    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)) : ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error) : ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Job Edit Form -->
    <form id="editJobForm" action="" method="POST" novalidate>
        <!-- Job Title -->
        <div class="mb-3">
            <label for="job_title" class="form-label">Job Title</label>
            <input type="text" class="form-control" id="job_title" name="job_title" value="<?= htmlspecialchars($job['job_title'] ?? '') ?>" required>
            <div class="invalid-feedback">Job title is required.</div>
        </div>

        <!-- Partner Company -->
        <div class="mb-3">
            <label for="partner_company" class="form-label">Partner Company</label>
            <input type="text" class="form-control" id="partner_company" name="partner_company" value="<?= htmlspecialchars($job['partner_company'] ?? '') ?>" required>
            <div class="invalid-feedback">Partner company is required.</div>
        </div>

        <!-- Location -->
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($job['location'] ?? '') ?>" required>
            <div class="invalid-feedback">Location is required.</div>
        </div>

        <!-- Salary Range -->
        <div class="salary-container mb-3">
            <div>
                <label for="min_salary" class="form-label">Min Salary</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="min_salary" name="min_salary" value="<?= htmlspecialchars($job['min_salary'] ?? '') ?>" required>
                    <div class="invalid-feedback">Min salary is required.</div>
                </div>
            </div>
            <div>
                <label for="max_salary" class="form-label">Max Salary</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="max_salary" name="max_salary" value="<?= htmlspecialchars($job['max_salary'] ?? '') ?>" required>
                    <div class="invalid-feedback">Max salary is required.</div>
                </div>
            </div>
            <div id="salaryError" class="invalid-feedback" style="display:none;">Minimum salary must not exceed maximum salary.</div>
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" required><?= htmlspecialchars($job['description'] ?? '') ?></textarea>
            <div class="invalid-feedback">Description is required.</div>
        </div>

        <!-- Openings -->
        <div class="mb-3">
            <label for="openings" class="form-label">Openings</label>
            <input type="number" class="form-control" id="openings" name="openings" value="<?= htmlspecialchars($job['openings'] ?? '') ?>" required>
            <div class="invalid-feedback">Number of openings is required.</div>
        </div>

        <!-- Deadline -->
        <div class="mb-3">
            <label for="deadline" class="form-label">Deadline</label>
            <input type="date" class="form-control" id="deadline" name="deadline" value="<?= htmlspecialchars($job['deadline'] ?? '') ?>" required>
            <div class="invalid-feedback">Deadline is required.</div>
        </div>

        <!-- Preferred Educational Level -->
        <div class="mb-3">
            <label for="preferred_educational_level" class="form-label">Preferred Educational Level</label>
            <select class="form-select" id="preferred_educational_level" name="preferred_educational_level" required>
                <option value="" disabled <?= empty($job['preferred_educational_level']) ? 'selected' : '' ?>>--Choose an Option--</option>
                <?php
                $education_levels = [
                    "ALS Graduate", "High School Graduate", "Junior High School Graduate", 
                    "Senior High School Graduate", "College Graduate", "Bachelor's Degree", 
                    "Masteral Degree", "Doctorate Degree"
                ];
                foreach ($education_levels as $level) {
                    $selected = ($job['preferred_educational_level'] ?? '') === $level ? 'selected' : '';
                    echo "<option value=\"$level\" $selected>$level</option>";
                }
                ?>
            </select>
            <div class="invalid-feedback">Preferred educational level is required.</div>
        </div>

        <!-- Min and Max Age -->
        <div class="age-container mb-3">
            <div>
                <label for="min_age" class="form-label">Min Age</label>
                <input type="number" class="form-control" id="min_age" name="min_age" value="<?= htmlspecialchars($job['min_age'] ?? '') ?>" required>
                <div class="invalid-feedback">Min age is required.</div>
            </div>
            <div>
                <label for="max_age" class="form-label">Max Age</label>
                <input type="number" class="form-control" id="max_age" name="max_age" value="<?= htmlspecialchars($job['max_age'] ?? '') ?>" required>
                <div class="invalid-feedback">Max age is required.</div>
            </div>
            <div id="ageError" class="invalid-feedback" style="display:none;">Minimum age must not exceed maximum age.</div>
        </div>

        <!-- Preferred Work Experience -->
        <div class="mb-3">
            <label for="preferred_work_experience" class="form-label">Preferred Work Experience</label>
            <input type="text" class="form-control" id="preferred_work_experience" name="preferred_work_experience" value="<?= htmlspecialchars($job['preferred_work_experience'] ?? '') ?>" required>
            <div class="invalid-feedback">Work experience is required.</div>
        </div>

        <!-- Job Status -->
        <div class="mb-3">
            <label for="status" class="form-label">Job Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="pending" <?= ($job['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="open" <?= ($job['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
            </select>
            <div class="invalid-feedback">Job status is required.</div>
        </div>

        <!-- Checkbox to include Requirements -->
        <input type="checkbox" id="includeRequirement" checked> Include Requirements Section

         <!-- Requirements Section -->
         <div class="mb-3" id="requirementsSection"  style="display: block;">
            <label for="requirements" class="form-label">Job Requirements</label>
            <div id="requirementContainer">
                <?php if (!empty($job_requirements)): ?>
                    <?php foreach ($job_requirements as $requirement): ?>
                        <div class="requirement-item">
                            <input type="text" class="form-control mt-2" name="requirements[]" value="<?= htmlspecialchars($requirement['requirement_name']) ?>" required>
                            <div class="invalid-feedback">Requirement is required.</div>
                            <button type="button" class="btn btn-danger mt-2 removeRequirement">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-success mb-2" id="addRequirement">Add New Requirement</button>
        </div>

        <!-- Checkbox to include Questionnaire -->
        <input type="checkbox" id="includeQuestionnaire" checked> Include Questions Section

        <!-- Questions Section -->
        <div class="mb-3" id="questionsSection" style="display: block;">
            <label for="questions" class="form-label">Questions</label>
            <div id="questionContainer">
                <?php if (!empty($job_questions)): ?>
                    <?php foreach ($job_questions as $index => $question): ?>
                        <div class="question-item">
                            <input type="text" class="form-control mt-2" name="questions[<?= $index ?>][question_text]" value="<?= htmlspecialchars($question['question_text']) ?>" required>
                            <label>Dealbreaker:</label>
                            <div>
                                <input type="radio" name="questions[<?= $index ?>][dealbreaker]" value="yes" <?= $question['dealbreaker'] ? 'checked' : '' ?>> Yes
                                <input type="radio" name="questions[<?= $index ?>][dealbreaker]" value="no" <?= !$question['dealbreaker'] ? 'checked' : '' ?>> No
                            </div>
                            <label>Correct Answer:</label>
                            <div>
                                <input type="radio" name="questions[<?= $index ?>][correct_answer]" value="yes" <?= $question['correct_answer'] ? 'checked' : '' ?>> Yes
                                <input type="radio" name="questions[<?= $index ?>][correct_answer]" value="no" <?= !$question['correct_answer'] ? 'checked' : '' ?>> No
                            </div>
                            <div class="invalid-feedback">This question is required.</div>
                            <button type="button" class="btn btn-danger mt-2 removeQuestion">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-success mb-2" id="addQuestion">Add Question</button>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById('editJobForm');
        const maxAgeInput = document.getElementById('max_age');
        const minAgeInput = document.getElementById('min_age');

        if (maxAgeInput && minAgeInput) {
            maxAgeInput.addEventListener('input', validateAge);
            minAgeInput.addEventListener('input', validateAge);
        }

        document.getElementById('max_age').addEventListener('input', validateAge);
        document.getElementById('min_age').addEventListener('input', validateAge);

        // Function to show or hide error messages for salary
        function validateSalary() {
            const minSalary = parseFloat(document.getElementById('min_salary').value);
            const maxSalary = parseFloat(document.getElementById('max_salary').value);
            const salaryError = document.getElementById('salaryError');

            if (minSalary && maxSalary && minSalary > maxSalary) {
                document.getElementById('min_salary').classList.add('is-invalid');
                document.getElementById('max_salary').classList.add('is-invalid');
                salaryError.style.display = 'block';
                return false;
            } else {
                document.getElementById('min_salary').classList.remove('is-invalid');
                document.getElementById('max_salary').classList.remove('is-invalid');
                salaryError.style.display = 'none';
                return true;
            }
        }

        // Function to show or hide error messages for age
        function validateAge() {
            const minAge = parseInt(document.getElementById('min_age').value, 10);
            const maxAge = parseInt(document.getElementById('max_age').value, 10);
            const ageError = document.getElementById('ageError');

            if (minAge && maxAge && minAge > maxAge) {
                document.getElementById('min_age').classList.add('is-invalid');
                document.getElementById('max_age').classList.add('is-invalid');
                ageError.style.display = 'block';
                return false;
            } else {
                document.getElementById('min_age').classList.remove('is-invalid');
                document.getElementById('max_age').classList.remove('is-invalid');
                ageError.style.display = 'none';
                return true;
            }
        }

        // Function to validate dynamic inputs like requirements and questions
        function validateDynamicSections(sectionSelector, inputSelector) {
            let isValid = true;
            document.querySelectorAll(sectionSelector).forEach(section => {
                section.querySelectorAll(inputSelector).forEach(input => {
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            });
            return isValid;
        }

        // Show/Hide Requirement Section based on checkbox state
        document.getElementById('includeRequirement').addEventListener('change', function () {
            const requirementSection = document.getElementById('requirementContainer');
            if (this.checked) {
                requirementContainer.style.display = 'block'; // Show the section if checkbox is checked
            } else {
                requirementContainer.style.display = 'none'; // Hide the section if checkbox is unchecked
                document.getElementById('requirementContainer').innerHTML = ''; // Remove any added requirements
            }
        });

        // Show/Hide Requirement Section based on checkbox state
        document.getElementById('includeRequirement').addEventListener('change', function () {
            const requirementSection = document.getElementById('requirementsSection');
            if (this.checked) {
                requirementSection.style.display = 'block'; // Show the section if checkbox is checked
            } else {
                requirementSection.style.display = 'none'; // Hide the section if checkbox is unchecked
                document.getElementById('requirementContainer').innerHTML = ''; // Remove any added requirements
            }
        });

        // Show/Hide Questionnaire Section based on checkbox state
        document.getElementById('includeQuestionnaire').addEventListener('change', function () {
            const questionSection = document.getElementById('questionsSection');
            if (this.checked) {
                questionSection.style.display = 'block'; // Show the section if checkbox is checked
            } else {
                questionSection.style.display = 'none'; // Hide the section if checkbox is unchecked
                document.getElementById('questionContainer').innerHTML = ''; // Remove any added questions
            }
        });

         // Add new requirement
        document.getElementById('addRequirement').addEventListener('click', function () {
            const requirementContainer = document.getElementById('requirementContainer');
            const requirementCount = document.querySelectorAll('.requirement-item').length + 1;
            const newRequirement = document.createElement('div');
            newRequirement.classList.add('requirement-item');
            newRequirement.innerHTML = `
                <label>Requirement[${requirementCount}]</label>
                <input type="text" class="form-control" name="requirements[]" placeholder="Enter a requirement" required>
                <div class="invalid-feedback">Requirement is required.</div>
                <button type="button" class="btn btn-danger mt-2 removeRequirement">Remove</button>
            `;
            requirementContainer.appendChild(newRequirement);
            newRequirement.querySelector('.removeRequirement').addEventListener('click', function () {
                requirementContainer.removeChild(newRequirement);
            });
        });

        // Add new question
        document.getElementById('addQuestion').addEventListener('click', function () {
            const questionCount = document.querySelectorAll('.question-item').length + 1;
            const questionContainer = document.getElementById('questionContainer');
            const newQuestion = document.createElement('div');
            newQuestion.classList.add('question-item');
            newQuestion.innerHTML = `
                <label>Question[${questionCount}]</label>
                <input type="text" class="form-control" name="questions[${questionCount - 1}][question_text]" placeholder="Enter a question" required>
                <label>Dealbreaker:</label>
                <div>
                    <input type="radio" name="questions[${questionCount - 1}][dealbreaker]" value="yes" required> Yes
                    <input type="radio" name="questions[${questionCount - 1}][dealbreaker]" value="no"> No
                </div>
                <label>Correct Answer:</label>
                <div>
                    <input type="radio" name="questions[${questionCount - 1}][correct_answer]" value="yes"> Yes
                    <input type="radio" name="questions[${questionCount - 1}][correct_answer]" value="no"> No
                </div>
                <div class="invalid-feedback">This question is required.</div>
                <button type="button" class="btn btn-danger mt-2 removeQuestion">Remove</button>
            `;
            questionContainer.appendChild(newQuestion);
            newQuestion.querySelector('.removeQuestion').addEventListener('click', function () {
                questionContainer.removeChild(newQuestion);
            });
        });

        // Form Validation on Submit
        form.addEventListener('submit', function (event) {
            let isValid = true;

            // Validate Salary and Age
            isValid &= validateSalary();
            isValid &= validateAge();

            // Validate Dynamic Sections
            isValid &= validateDynamicSections('.requirement-item', 'input');
            isValid &= validateDynamicSections('.question-item', 'input');

            // Validate other inputs
            form.querySelectorAll('input, select, textarea').forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
            }
        });
    });
</script>

</body>
</html>
