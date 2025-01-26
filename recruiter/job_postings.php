<?php
require 'db.php'; // Include database connection

// include 'header.php';
// include 'sidebar.php';

// Start session to get logged-in user's ID
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success_message = null;

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

    // Age range
    $min_age = $_POST['min_age'] ?? '';
    $max_age = $_POST['max_age'] ?? '';
    $preferred_age_range = '';

    if ($min_age && $max_age) {
        $preferred_age_range = $min_age . ' - ' . $max_age;  // Concatenate min and max age
    }

    // Requirements and questions
    $requirements = $_POST['requirements'] ?? [];
    $questions = $_POST['questions'] ?? [];

    // debug
    // print_r($questions);
    // exit;

    // Validate inputs
    if (empty($job_title) || empty($partner_company) || empty($location) || empty($description)) {
        $errors[] = "All required fields must be filled out.";
    }

    if ($max_salary < $min_salary) {
        $errors[] = "Max salary cannot be lower than Min salary.";
    }
    

    // Insert job post data if no errors
    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert job post
            $stmt = $pdo->prepare("
                INSERT INTO job_posts (
                    job_title, partner_company, location, min_salary, max_salary, description, openings,
                    created_by, deadline, status, preferred_educational_level, preferred_age_range, preferred_work_experience
                ) VALUES (
                    :job_title, :partner_company, :location, :min_salary, :max_salary, :description, :openings,
                    :created_by, :deadline, :status, :preferred_educational_level, :preferred_age_range, :preferred_work_experience
                )
            ");
            $stmt->execute([
                ':job_title' => $job_title,
                ':partner_company' => $partner_company,
                ':location' => $location,
                ':min_salary' => $min_salary,
                ':max_salary' => $max_salary,
                ':description' => $description,
                ':openings' => $openings,
                ':created_by' => $login_id,
                ':deadline' => $deadline,
                ':status' => $status,
                ':preferred_educational_level' => $preferred_educational_level,
                ':preferred_age_range' => $preferred_age_range,
                ':preferred_work_experience' => $preferred_work_experience
            ]);
            
            $job_post_id = $pdo->lastInsertId(); // Get the last inserted job_post_id

            // Insert job metrics entry (job_post_id and created_at)
            $stmt = $pdo->prepare("
                INSERT INTO job_metrics (job_post_id, created_at)
                VALUES (:job_post_id, NOW())
            ");
            $stmt->execute([':job_post_id' => $job_post_id]);

            // Insert job requirements
            if (!empty($requirements)) {
                $stmt = $pdo->prepare("INSERT INTO job_requirements (job_post_id, requirement_name) VALUES (:job_post_id, :requirement_name)");
                foreach ($requirements as $requirement) {
                    $stmt->execute([':job_post_id' => $job_post_id, ':requirement_name' => $requirement]);
                }
            }

            // Insert job questionnaire items
            if (!empty($questions)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO questionnaires (job_post_id, question_text, dealbreaker, correct_answer) 
                        VALUES (:job_post_id, :question_text, :dealbreaker, :correct_answer)
                    ");
                    foreach ($questions as $question) {
                        // Convert "yes"/"no" to 1/0 for TINYINT fields
                        $dealbreaker = isset($question['dealbreaker']) && $question['dealbreaker'] === 'yes' ? 1 : 0;
                        $correct_answer = isset($question['correct_answer']) && $question['correct_answer'] === 'yes' ? 1 : 0;

                        // Debugging output to verify data before insertion
                        error_log("Inserting question: " . print_r([
                            'job_post_id' => $job_post_id,
                            'question_text' => $question['question_text'],
                            'dealbreaker' => $dealbreaker,
                            'correct_answer' => $correct_answer,
                        ], true));

                        // Execute the prepared statement
                        $stmt->execute([
                            ':job_post_id' => $job_post_id,
                            ':question_text' => $question['question_text'],
                            ':dealbreaker' => $dealbreaker,
                            ':correct_answer' => $correct_answer,
                        ]);
                    }
                } catch (Exception $e) {
                    $errors[] = "Error inserting questions: " . $e->getMessage();
                }
            }

            // Commit transaction
            $pdo->commit();

            // Set success message
            $success_message = "Job post successfully created!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .is-invalid {
            border-color: #dc3545;
        }
        .salary-container {
            display: flex;
            gap: 10px;
        }
        .salary-container input {
            width: 48%;
        }

        .age-container {
            display: flex;
            gap: 10px;
        }
        .age-container input {
            width: 48%;
        }

        .form-control, .form-select {
            width: 100%;
        }
        .invalid-feedback {
            display: none;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Create Job Post</h2>

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

    <!-- Job Post Form -->
    <form id="jobPostForm" action="" method="POST" novalidate>
        <!-- Job Post Details -->
        <div class="mb-3">
            <label for="job_title" class="form-label">Job Title</label>
            <input type="text" class="form-control" id="job_title" name="job_title" required>
            <div class="invalid-feedback">Job title is required.</div>
        </div>
        <div class="mb-3">
            <label for="partner_company" class="form-label">Partner Company</label>
            <input type="text" class="form-control" id="partner_company" name="partner_company" required>
            <div class="invalid-feedback">Partner company is required.</div>
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" required>
            <div class="invalid-feedback">Location is required.</div>
        </div>

        <!-- Min & Max Salary (Side by Side) -->
        <div class="salary-container mb-3">
            <div>
                <label for="min_salary" class="form-label">Min Salary</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="min_salary" name="min_salary">
                    <div class="invalid-feedback">Min salary is required.</div>
                </div>
            </div>
            <div>
                <label for="max_salary" class="form-label">Max Salary</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="max_salary" name="max_salary">
                    <div class="invalid-feedback">Max salary is required.</div>
                </div>
            </div>
            <div id="salaryError" class="invalid-feedback" style="display:none;">Minimum salary must not exceed maximum salary.</div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" required></textarea>
            <div class="invalid-feedback">Description is required.</div>
        </div>
        <div class="mb-3">
            <label for="openings" class="form-label">Openings</label>
            <input type="number" class="form-control" id="openings" name="openings" required>
            <div class="invalid-feedback">Number of openings is required.</div>
        </div>

        <!-- Deadline (No past dates) -->
        <div class="mb-3">
            <label for="deadline" class="form-label">Deadline</label>
            <input type="date" class="form-control" id="deadline" name="deadline" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            <div class="invalid-feedback">Deadline is required.</div>
        </div>

        <!-- Preferred Educational Level (Dropdown) -->
        <div class="mb-3">
            <label for="preferred_educational_level" class="form-label">Preferred Educational Level</label>
            <select class="form-select" id="preferred_educational_level" name="preferred_educational_level">
                <option value data-isdefault="true">--Choose an Option--</option>
                <option value="ALS Graduate">ALS Graduate</option>
                <option value="High School Graduate">High School Graduate</option>
                <option value="Junior High School Graduate">Junior High School Graduate</option>
                <option value="Senior High School Graduate">Senior High School Graduate</option>
                <option value="College Graduate">College Graduate</option>
                <option value="Bachelor's Degree">Bachelor's Degree</option>
                <option value="Masteral Degree">Masteral Degree</option>
                <option value="Doctorate Degree">Doctorate Degree</option>
            </select>
            <div class="invalid-feedback">Preferred educational level is required.</div>
        </div>

        <!-- Min and Max Age -->
        <div class="age-container mb-3">
            <div>
                <label for="min_age" class="form-label">Min Age</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="min_age" name="min_age">
                    <div class="invalid-feedback">Min age is required.</div>
                </div>
            </div>
            <div>
                <label for="max_age" class="form-label">Max Age</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="max_age" name="max_age">
                    <div class="invalid-feedback">Max age is required.</div>
                </div>
            </div>
            <div id="ageError" class="invalid-feedback" style="display:none;">Minimum age must not exceed maximum age.</div>
        </div>

        <!-- Preferred Work Experience -->
        <div class="mb-3">
            <label for="preferred_work_experience" class="form-label">Preferred Work Experience</label>
            <input type="text" class="form-control" id="preferred_work_experience" name="preferred_work_experience">
            <div class="invalid-feedback">Work experience is required.</div>
        </div>

        <!-- Job Status (Pending or Open) -->
        <div class="mb-3">
            <label for="status" class="form-label">Job Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="pending">Pending</option>
                <option value="open">Open</option>
            </select>
            <div class="invalid-feedback">Job status is required.</div>
        </div>
        
        <!-- Checkbox to include Requirements -->
        <input type="checkbox" id="includeRequirement"> Include Requirements Section

        <!-- Requirements Section -->
        <div class="mb-3" id="requirementsSection"  style="display: none;">
            <label for="requirements" class="form-label">Job Requirements</label>
            <div id="requirementContainer">
                <!-- <div class="requirement-item">
                    <label>Requirement[1]</label>
                    <input type="text" class="form-control" name="requirements[]" placeholder="Enter a requirement" required> -->
                    <div class="invalid-feedback">Requirement is required.</div>
                <!-- </div> -->
            </div>
            <button type="button" class="btn btn-info mt-2" id="addRequirement">Add Requirement</button>
        </div>

        <!-- Checkbox to include Questionnaire -->
        <input type="checkbox" id="includeQuestionnaire"> Include Questions Section

        <!-- Questions Section -->
        <div class="mb-3" id="questionsSection" style="display: none;">
            <label for="questions" class="form-label">Questions</label>
            <div id="questionContainer">
                <!-- <div class="question-item">
                    <label>Question[1]</label>
                    <input type="text" class="form-control" name="questions[0][question_text]" placeholder="Enter a question" required>
                    <label>Dealbreaker:</label>
                    <div>
                        <input type="radio" id="dealbreaker_yes" name="questions[0][dealbreaker]" value="yes" required> Yes
                        <input type="radio" id="dealbreaker_no" name="questions[0][dealbreaker]" value="no"> No
                    </div>
                    <label>Correct Answer:</label>
                    <div>
                        <input type="radio" id="correct_answer_yes" name="questions[0][correct_answer]" value="yes"> Yes
                        <input type="radio" id="correct_answer_no" name="questions[0][correct_answer]" value="no"> No
                    </div> -->
                    <div class="invalid-feedback">This question is required.</div>
                <!-- </div> -->
            </div>
            <button type="button" class="btn btn-info mt-2" id="addQuestion">Add Question</button>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

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

        /// Form validation
        const form = document.getElementById('jobPostForm');
        form.addEventListener('submit', function (event) {
            let valid = true;
            
            // Validate if min salary is less than max salary
            const minSalary = parseFloat(document.getElementById('min_salary').value);
            const maxSalary = parseFloat(document.getElementById('max_salary').value);
            if (minSalary && maxSalary && minSalary > maxSalary) {
                valid = false;
                document.getElementById('min_salary').classList.add('is-invalid');
                document.getElementById('max_salary').classList.add('is-invalid');
                salaryError.style.display = 'block';  // Show age validation error
            } else {
                document.getElementById('min_salary').classList.remove('is-invalid');
                document.getElementById('max_salary').classList.remove('is-invalid');
                salaryError.style.display = 'none';  // Hide age validation error
            }

            // Get the values for min and max age
            const minAge = parseInt(document.getElementById('min_age').value, 10);
            const maxAge = parseInt(document.getElementById('max_age').value, 10);
            const ageError = document.getElementById('ageError');
            
            // Reset the error visibility before checking the condition
            ageError.style.display = 'none';
            document.getElementById('min_age').classList.remove('is-invalid');
            document.getElementById('max_age').classList.remove('is-invalid');

            // Validate if min age is less than max age
            if (minAge && maxAge && minAge > maxAge) {
                valid = false;
                document.getElementById('min_age').classList.add('is-invalid');
                document.getElementById('max_age').classList.add('is-invalid');
                ageError.style.display = 'block';  // Show age validation error
            }

            form.querySelectorAll('input, select, textarea').forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    valid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                event.preventDefault();
            }
        });
    });
</script>
</body>
</html>
