<?php
ob_start();
require 'db.php'; // Include database connection
require 'sidebar.php';
require 'header.php';
require 'auth.php';

// Fetch user_id from query parameter
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    die("Invalid user ID.");
}

// Fetch applicant details
$userQuery = $pdo->prepare("
    SELECT * FROM users WHERE user_id = :user_id
");
$userQuery->execute(['user_id' => $user_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Fetch related data
$certificationsQuery = $pdo->prepare("
    SELECT * FROM user_certifications WHERE user_id = :user_id
");
$certificationsQuery->execute(['user_id' => $user_id]);
$certifications = $certificationsQuery->fetchAll(PDO::FETCH_ASSOC);

$educationQuery = $pdo->prepare("
    SELECT * FROM user_education WHERE user_id = :user_id
");
$educationQuery->execute(['user_id' => $user_id]);
$education = $educationQuery->fetch(PDO::FETCH_ASSOC);

$workExperienceQuery = $pdo->prepare("
    SELECT * FROM user_work_experience WHERE user_id = :user_id
");
$workExperienceQuery->execute(['user_id' => $user_id]);
$workExperiences = $workExperienceQuery->fetchAll(PDO::FETCH_ASSOC);

$skillsQuery = $pdo->prepare("
    SELECT * FROM skills WHERE user_id = :user_id
");
$skillsQuery->execute(['user_id' => $user_id]);
$skills = $skillsQuery->fetchAll(PDO::FETCH_ASSOC);

$base_path = '../applicant/';

$resume_path = "#"; // Default fallback if no resume is found

if ($user_id) {
    // Prepare and execute the query
    $resumeQuery = $pdo->prepare("
        SELECT resume_reference FROM job_applications WHERE user_id = :user_id
    ");
    $resumeQuery->execute(['user_id' => $user_id]);
    $resume = $resumeQuery->fetch(PDO::FETCH_ASSOC);

    // Check if resume_reference exists and generate the path
    if ($resume && !empty($resume['resume_reference'])) {
        $resume_path = "../applicant/uploads/" . htmlspecialchars($resume['resume_reference']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>

        .profile-picture {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div id='content'>
<div class="container mt-1">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="user_management.php">Back to Applicants</a>
    </div>

    <!-- Applicant Information -->
    <div class="card mb-4">
        <div class="card-header">Applicant Information</div>
        <div class="card-body">
            <p><strong>ID Picture:<br></strong> <img src="<?= htmlspecialchars($base_path . $user['id_picture_reference']) ?>" alt="ID Picture" class="img-thumbnail" style="max-width: 150px;"></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($user['last_name']) ?>, <?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['middle_name']) ?></p>
            <p><strong>Nickname:</strong> <?= htmlspecialchars($user['nickname']) ?></p>
            <p><strong>Facebook Messenger Link:</strong> <a href="<?= htmlspecialchars($user['facebook_messenger_link']) ?>" target="_blank"><?= htmlspecialchars($user['facebook_messenger_link']) ?></a></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email_address']) ?></p>
            <p><strong>Cellphone:</strong> <?= htmlspecialchars($user['cellphone_number']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
            <p><strong>Birthday:</strong> <?= htmlspecialchars($user['birthday']) ?></p>
            <p><strong>Birth Place:</strong> <?= htmlspecialchars($user['birth_place']) ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>
            <p><strong>Sex:</strong> <?= htmlspecialchars($user['sex']) ?></p>
            <p><strong>Height:</strong> <?= htmlspecialchars($user['height_ft']) ?> ft</p>
            <p><strong>Marital Status:</strong> <?= htmlspecialchars($user['marital_status']) ?></p>
            <p><strong>Religion:</strong> <?= htmlspecialchars($user['religion']) ?></p>
            <p><strong>Tattoo:</strong> <?= $user['has_tattoo'] ? 'Yes' : 'No' ?></p>
            <p><strong>COVID-19 Vaccination:</strong> <?= htmlspecialchars($user['covid_vaccination_status']) ?></p>
            <!-- Resume Download Link -->
            <p><strong>Resume:</strong> 
                <a href="<?= $resume_path ?>" download>Download Resume</a>
            </p>
        </div>
    </div>

    <!-- Education Details -->
    <?php if ($education): ?>
        <div class="card mb-4">
            <div class="card-header">Education Details</div>
            <div class="card-body">
                <p><strong>Highest Educational Attainment:</strong> <?= htmlspecialchars($education['highest_educational_attainment']) ?></p>
                <p><strong>Junior High School:</strong> <?= htmlspecialchars($education['junior_high_school']) ?></p>
                <p><strong>Year Graduated Junior High School:</strong> <?= htmlspecialchars($education['year_graduated_junior_highschool']) ?></p>
                <p><strong>Senior High School:</strong> <?= htmlspecialchars($education['senior_high_school']) ?></p>
                <p><strong>Year Graduated Senior High School:</strong> <?= htmlspecialchars($education['year_graduated_senior_highschool']) ?></p>
                <p><strong>College:</strong> <?= htmlspecialchars($education['college']) ?></p>
                <p><strong>Year Graduated College:</strong> <?= htmlspecialchars($education['year_graduated_college']) ?></p>
                <p><strong>Course/Program:</strong> <?= htmlspecialchars($education['course_program']) ?></p>
                <p><strong>Postgraduate/Master's:</strong> <?= htmlspecialchars($education['postgrad_masters']) ?></p>
                <p><strong>Year Graduated Postgraduate/Master's:</strong> <?= htmlspecialchars($education['year_graduated_postgrad_masters']) ?></p>
                <p><strong>Other Details:</strong> 
                    <?php if (!empty($education['other_details'])): ?>
                        <?= htmlspecialchars($education['other_details']) ?>
                    <?php else: ?>
                        <span class="text-muted">No other details provided</span>
                    <?php endif; ?>
                </p>
                <p><strong>Diploma:</strong> 
                    <?php if (!empty($education['diploma'])): ?>
                        <a href="../applicant/<?= htmlspecialchars($education['diploma']) ?>" target="_blank">View Diploma</a>
                    <?php elseif ($education['no_diploma']): ?>
                        <span class="text-muted">No diploma provided</span>
                    <?php else: ?>
                        <span class="text-muted">Not uploaded</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">Education Details</div>
            <div class="card-body">
                <p class="text-muted">No education details available for this applicant.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Work Experience -->
    <?php if ($workExperiences): ?>
        <div class="card mb-4">
            <div class="card-header">Work Experience</div>
            <div class="card-body">
                <?php 
                // Group work experiences by category
                $workExperiencesByCategory = [];
                foreach ($workExperiences as $work) {
                    $category = $work['experience_category'] ?? 'Uncategorized';
                    $workExperiencesByCategory[$category][] = $work;
                }
                ?>

                <?php foreach ($workExperiencesByCategory as $category => $experiences): ?>
                    <h5 class="mt-3"><?= htmlspecialchars($category) ?></h5>
                    <hr>
                    <?php foreach ($experiences as $work): ?>
                        <p><strong>Company:</strong> <?= htmlspecialchars($work['company_name']) ?></p>
                        <p><strong>Role:</strong> <?= htmlspecialchars($work['role']) ?></p>
                        <p><strong>Years Worked:</strong> <?= htmlspecialchars($work['years_worked']) ?></p>
                        <hr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">Work Experience</div>
            <div class="card-body">
                <p class="text-muted">No work experience available for this applicant.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Certifications -->
    <?php if ($certifications): ?>
        <div class="card mb-4">
            <div class="card-header">Certifications</div>
            <div class="card-body">
                <?php foreach ($certifications as $certification): ?>
                    <p><strong>Certification Name:</strong> <?= htmlspecialchars($certification['certification_name']) ?></p>
                    <p><strong>Institute:</strong> <?= htmlspecialchars($certification['certification_institute']) ?></p>
                    <p><strong>Year Taken:</strong> <?= htmlspecialchars($certification['year_taken_certification']) ?></p>
                    <p><strong>Certificate:</strong> 
                        <?php if (!empty($certification['certificate_image_path'])): ?>
                            <a href="../applicant/<?= htmlspecialchars($certification['certificate_image_path']) ?>" target="_blank">View Certificate</a>
                        <?php else: ?>
                            <span class="text-muted">Not uploaded</span>
                        <?php endif; ?>
                    </p>
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">Certifications</div>
            <div class="card-body">
                <p class="text-muted">No certifications available for this applicant.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Skills -->
    <?php if ($skills): ?>
        <div class="card mb-4">
            <div class="card-header">Skills</div>
            <div class="card-body">
                <?php foreach ($skills as $skill): ?>
                    <p><strong>Skill Name:</strong> <?= htmlspecialchars($skill['skill_name']) ?></p>
                    <p><strong>Proficiency Level:</strong> <?= htmlspecialchars($skill['proficiency_level']) ?></p>
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">Skills</div>
            <div class="card-body">
                <p class="text-muted">No skills available for this applicant.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>
</body>
</html>
