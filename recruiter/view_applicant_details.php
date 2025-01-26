<?php
require 'db.php'; // Include database connection

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

$base_path = '../applicant/';
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
<div class="container mt-5">
    <h1>Applicant Details</h1>
    <div class="card mb-4">
        <div class="card-header">Personal Information</div>
        <div class="card-body">
            <p><strong>ID Picture:<br></strong> <img src="<?= htmlspecialchars($base_path.$user['id_picture_reference']) ?>" alt="ID Picture" class="img-thumbnail" style="max-width: 150px;"></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
            <p><strong>Nickname:</strong> <?= htmlspecialchars($user['nickname']) ?></p>
            <p><strong>Birthday:</strong> <?= htmlspecialchars($user['birthday']) ?></p>
            <p><strong>Birth Place:</strong> <?= htmlspecialchars($user['birth_place']) ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>
            <p><strong>Sex:</strong> <?= htmlspecialchars($user['sex']) ?></p>
            <p><strong>Height (ft):</strong> <?= htmlspecialchars($user['height_ft']) ?></p>
            <p><strong>Marital Status:</strong> <?= htmlspecialchars($user['marital_status']) ?></p>
            <p><strong>Religion:</strong> <?= htmlspecialchars($user['religion']) ?></p>
            <p><strong>Has Tattoo:</strong> <?= htmlspecialchars($user['has_tattoo'] ? 'Yes' : 'No') ?></p>
            <p><strong>COVID Vaccination Status:</strong> <?= htmlspecialchars($user['covid_vaccination_status']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($user['cellphone_number']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email_address']) ?></p>
            <p><strong>Referral Code:</strong> <?= htmlspecialchars($user['referral_code']) ?></p>
        </div>
    </div>

    <!-- Education -->
    <?php if ($education): ?>
        <div class="card mb-4">
            <div class="card-header">Education</div>
            <div class="card-body">
                <p><strong>Highest Attainment:</strong> <?= htmlspecialchars($education['highest_educational_attainment']) ?></p>
                <p><strong>College:</strong> <?= htmlspecialchars($education['college']) ?> </p>
                <p><strong>Year Graduated:</strong> <?= htmlspecialchars($education['year_graduated_college']) ?></p>
                <p><strong>Course:</strong> <?= htmlspecialchars($education['course_program']) ?></p>
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
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Work Experience -->
    <?php if ($workExperiences): ?>
        <div class="card mb-4">
            <div class="card-header">Work Experience</div>
            <div class="card-body">
                <?php foreach ($workExperiences as $work): ?>
                    <p><strong>Company:</strong> <?= htmlspecialchars($work['company_name']) ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($work['role']) ?></p>
                    <p><strong>Years Worked:</strong> <?= htmlspecialchars($work['years_worked']) ?></p>
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
