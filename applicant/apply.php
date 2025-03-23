<?php
require 'db.php';
include 'header.php';
include 'sidebar.php';

$successMessage = ""; // Initialize the message variable

if (!isset($_GET['job_post_id']) || empty($_GET['job_post_id'])) {
    die("Job post ID is required.");
}

$job_post_id = $_GET['job_post_id'];

// Fetch job details
$jobQuery = $pdo->prepare("SELECT * FROM job_posts WHERE job_post_id = :job_post_id");
$jobQuery->execute(['job_post_id' => $job_post_id]);
$jobDetails = $jobQuery->fetch(PDO::FETCH_ASSOC);


if (!$jobDetails) {
    die("Job post not found.");
}

// Fetch job requirements
$requirementsQuery = $pdo->prepare("SELECT * FROM job_requirements WHERE job_post_id = :job_post_id");
$requirementsQuery->execute(['job_post_id' => $job_post_id]);
$requirements = $requirementsQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch questionnaire
$questionnaireQuery = $pdo->prepare("SELECT * FROM questionnaires WHERE job_post_id = :job_post_id");
$questionnaireQuery->execute(['job_post_id' => $job_post_id]);
$questions = $questionnaireQuery->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id']; 
    $resume_reference = $_FILES['resume']['name'];
    $status = 'Pending';
    $sourcing = $_POST['sourcing'];
    $work_experience = $_POST['work_experience'];

    // Fetch user details
    $userQuery = $pdo->prepare("SELECT age FROM users WHERE user_id = :user_id");
    $userQuery->execute(['user_id' => $user_id]);
    $userDetails = $userQuery->fetch(PDO::FETCH_ASSOC);

    // Fetch user's education
    $educationQuery = $pdo->prepare("SELECT highest_educational_attainment FROM user_education WHERE user_id = :user_id");
    $educationQuery->execute(['user_id' => $user_id]);
    $userEducation = $educationQuery->fetch(PDO::FETCH_ASSOC);
 
    if (!$userDetails || !$userEducation) {
        die("User details or education information not found.");
    }

    $ageMatch = true;
    $experienceMatch = true;
    $educationMatch = true;
    
    // Check preferred age
    if (!empty($jobDetails['preferred_age_range'])) {
        $ageMatch = $userDetails['age'] >= explode('-', $jobDetails['preferred_age_range'])[0] &&
        $userDetails['age'] <= explode('-', $jobDetails['preferred_age_range'])[1];
    }
    

    // Check preferred work experience
    if (!empty($jobDetails['preferred_work_experience'])) {
        $experienceMatch = $work_experience >= (int)$jobDetails['preferred_work_experience'];
    }
    
    // Check preferred educational level
    if (!empty($jobDetails['preferred_educational_level'])) {
        $bachelors = 'Bachelor\s Degree';
        $educationLevels = ['ALS Graduate','High School Graduate','Junior High School Graduate','Senior High School Graduate','College Graduate',$bachelors,'Masteral Degree','Doctorate Degree'];
        $jobEducationIndex = array_search($jobDetails['preferred_educational_level'], $educationLevels);
        $userEducationIndex = array_search($userEducation['highest_educational_attainment'], $educationLevels);
        $educationMatch = $userEducationIndex >= $jobEducationIndex;    
    }
    
    // Determine application status
    if ($ageMatch && $experienceMatch && $educationMatch) {
        $status = 'Shortlisted';
    }
    
    // Save resume file
    move_uploaded_file($_FILES['resume']['tmp_name'], 'uploads/' . $resume_reference);

    // Insert into job_applications
    $applicationQuery = $pdo->prepare("
        INSERT INTO job_applications (job_post_id, user_id, resume_reference, work_experience, status, applied_at) 
        VALUES (:job_post_id, :user_id, :resume_reference, :work_experience, :status, NOW())
    ");
    $applicationQuery->execute([
        'job_post_id' => $job_post_id,
        'user_id' => $user_id,
        'resume_reference' => $resume_reference,
        'work_experience' => $work_experience,
        'status' => $status
    ]);
    $application_id = $pdo->lastInsertId();

    // Insert questionnaire answers with correctness evaluation
    if (!empty($questions)) {
        foreach ($_POST['answers'] as $question_id => $answer) {
            // Fetch the correct answer from the database
            $correctAnswerQuery = $pdo->prepare("
                SELECT correct_answer 
                FROM questionnaires 
                WHERE question_id = :question_id
            ");
            $correctAnswerQuery->execute(['question_id' => $question_id]);
            $correctAnswer = $correctAnswerQuery->fetchColumn();
    
            // Determine if the provided answer matches the correct answer
            $is_correct = (int)$answer === (int)$correctAnswer ? 1 : 0;
    
            // Insert the answer into the database
            $answerQuery = $pdo->prepare("
                INSERT INTO questionnaire_answers (application_id, question_id, answer_text, is_correct) 
                VALUES (:application_id, :question_id, :answer_text, :is_correct)
            ");
            $answerQuery->execute([
                'application_id' => $application_id,
                'question_id' => $question_id,
                'answer_text' => $answer, // Save the raw answer as provided by the user
                'is_correct' => $is_correct
            ]);
        }
    }

        // Map user selection to the corresponding column in the database
        $sourcing_field_map = [
            'Referral' => 'referral_applicants',
            'Social Media' => 'social_media_applicants',
            'Career Website' => 'career_site_applicants',
        ];

        if (!isset($sourcing_field_map[$sourcing])) {
            die("Invalid sourcing type.");
        }

        $sourcing_field = $sourcing_field_map[$sourcing];

        // Update the job_metrics table
        $metricsQuery = $pdo->prepare("
            UPDATE job_metrics 
            SET total_applicants = COALESCE(total_applicants, 0) + 1, 
                $sourcing_field = COALESCE($sourcing_field, 0) + 1
            WHERE job_post_id = :job_post_id
        ");

        try {
            $metricsQuery->execute(['job_post_id' => $job_post_id]);
        } catch (PDOException $e) {
            echo "Error updating metrics: " . $e->getMessage();
        }
 
        // Store success message
    $successMessage = "<div class='alert alert-success'>Application submitted successfully! You will be redirected shortly</div>";

    // Redirect after 3 seconds using JavaScript
    echo "<script>
        setTimeout(function() {
            window.location.href = 'job_posts.php';
        }, 3000);
    </script>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?= htmlspecialchars($jobDetails['job_title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div id='content'>
<div class="container mt-5">
    <div class="mb-3">
            <a href="job_posts.php">Back to Job Posts</a>
    </div>

    <!-- Display the success message inside the container -->
    <?= $successMessage ?>
    
    <h1 class="mb-4">Apply for <?= htmlspecialchars($jobDetails['job_title']) ?></h1>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($jobDetails['job_title']) ?></h5>
            <h6 class="card-subtitle text-muted"><?= htmlspecialchars($jobDetails['partner_company']) ?></h6>
            <p class="mt-3"><?= nl2br(htmlspecialchars($jobDetails['description'])) ?></p>
            <p>
                <strong>Location:</strong> <?= htmlspecialchars($jobDetails['location']) ?><br>
                <strong>Salary Range:</strong> ₱<?= number_format($jobDetails['min_salary']) ?> - ₱<?= number_format($jobDetails['max_salary']) ?><br>
                <strong>Openings:</strong> <?= htmlspecialchars($jobDetails['openings']) ?><br>
            </p>
        </div>
    </div>

    <!-- Job Requirements -->
    <?php if (!empty($requirements)): ?>
        <h4>Job Requirements</h4>
        <ul>
            <?php foreach ($requirements as $req): ?>
                <li><?= htmlspecialchars($req['requirement_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

     <!-- Questionnaire -->
     <?php if (!empty($questions)): ?>
        <h4 class="mt-4">Questionnaire</h4>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $q): ?>
                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars($q['question_text']) ?> <?= $q['dealbreaker'] ? '<span class="text-danger">(Dealbreaker)</span>' : '' ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="answers[<?= $q['question_id'] ?>]" value="1" required>
                            <label class="form-check-label">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="answers[<?= $q['question_id'] ?>]" value="0" required>
                            <label class="form-check-label">No</label>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="mb-3">
            <label for="work_experience" class="form-label">Experience</label>
            <input type="number" class="form-control" name="work_experience" id="work_experience" required>
            <div class="invalid-feedback">Role is required.</div>
        </div>

        <div class="mb-3">
            <label for="sourcing" class="form-label">How did you hear about this job?</label>
            <select name="sourcing" id="sourcing" class="form-select" required>
                <option value="">Select an option</option>
                <option value="Referral">Referral</option>
                <option value="Social Media">Social Media</option>
                <option value="Career Website">Career Website</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="resume" class="form-label">Upload Resume</label>
            <input type="file" name="resume" id="resume" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
</div>
            </div>
</body>
</html>