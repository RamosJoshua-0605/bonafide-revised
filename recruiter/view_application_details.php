<?php
require 'db.php';
include 'header.php';
include 'sidebar.php';

// Get application_id from the URL
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    die("Invalid application ID.");
}

$base_path = '../applicant/';

// Fetch application details
$query = $pdo->prepare("
    SELECT 
        ja.*, 
        u.*, 
        jp.job_title, 
        jp.partner_company, 
        jp.description, 
        jp.location, 
        jp.openings, 
        jp.deadline, 
        jp.min_salary, 
        jp.max_salary, 
        jp.preferred_educational_level, 
        jp.preferred_age_range, 
        jp.preferred_work_experience
    FROM job_applications ja
    JOIN users u ON ja.user_id = u.user_id
    JOIN job_posts jp ON ja.job_post_id = jp.job_post_id
    WHERE ja.application_id = :application_id
");
$query->execute(['application_id' => $application_id]);
$application = $query->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    die("Application not found.");
}

// Fetch user work experience
$work_experience_query = $pdo->prepare("
    SELECT * FROM user_work_experience WHERE user_id = :user_id
");
$work_experience_query->execute(['user_id' => $application['user_id']]);
$work_experiences = $work_experience_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch user certifications
$certifications_query = $pdo->prepare("
    SELECT * FROM user_certifications WHERE user_id = :user_id
");
$certifications_query->execute(['user_id' => $application['user_id']]);
$certifications = $certifications_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch questionnaire and answers
$questionnaire_query = $pdo->prepare("
    SELECT qa.*, q.question_text, q.correct_answer, q.dealbreaker 
    FROM questionnaire_answers qa
    JOIN questionnaires q ON qa.question_id = q.question_id
    WHERE qa.application_id = :application_id
");
$questionnaire_query->execute(['application_id' => $application_id]);
$questionnaire_answers = $questionnaire_query->fetchAll(PDO::FETCH_ASSOC);

$job_requirements_query = $pdo->prepare("
    SELECT requirement_name FROM job_requirements WHERE job_post_id = :job_post_id
");
$job_requirements_query->execute(['job_post_id' => $application['job_post_id']]);
$job_requirements = $job_requirements_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch checked requirements
$checked_requirements_query = $pdo->prepare("
    SELECT * FROM checked_requirements WHERE application_id = :application_id
");
$checked_requirements_query->execute(['application_id' => $application_id]);
$checked_requirements = array_column($checked_requirements_query->fetchAll(PDO::FETCH_ASSOC), 'requirement');

// Fetch interview details
$interview_query = $pdo->prepare("SELECT * FROM interview_details WHERE application_id = :application_id");
$interview_query->execute(['application_id' => $application_id]);
$interview = $interview_query->fetch(PDO::FETCH_ASSOC);

// Fetch offer details
$offer_query = $pdo->prepare("SELECT * FROM offer_details WHERE application_id = :application_id");
$offer_query->execute(['application_id' => $application_id]);
$offer = $offer_query->fetch(PDO::FETCH_ASSOC);

// Fetch deployment details
$deployment_query = $pdo->prepare("SELECT * FROM deployment_details WHERE application_id = :application_id");
$deployment_query->execute(['application_id' => $application_id]);
$deployment = $deployment_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .incorrect-answer {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
<div id="content">
<div class="container mt-5">
    <div class="mb-3">
        <a href="applications.php">Back to Applications</a>
    </div>
    <h1>Application Details</h1>
   
    <!-- Job Application -->
    <div class="card mb-4">
        <div class="card-header">Job Application</div>
        <div class="card-body">
            <p><strong>Job Title:</strong> <?= htmlspecialchars($application['job_title']) ?></p>
            <p><strong>Partner Company:</strong> <?= htmlspecialchars($application['partner_company']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($application['description']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($application['location']) ?></p>
            <p><strong>Openings:</strong> <?= htmlspecialchars($application['openings']) ?></p>
            <p><strong>Deadline:</strong> <?= htmlspecialchars($application['deadline']) ?></p>
            <?php if (!empty($application['min_salary']) && !empty($application['max_salary'])): ?>
                <p><strong>Salary Range:</strong> <?= htmlspecialchars($application['min_salary']) ?> - <?= htmlspecialchars($application['max_salary']) ?></p>
            <?php endif; ?>
            <p><strong>Preferred Educational Level:</strong> <?= htmlspecialchars($application['preferred_educational_level']) ?></p>
            <p><strong>Preferred Age Range:</strong> <?= htmlspecialchars($application['preferred_age_range']) ?></p>
            <p><strong>Preferred Work Experience:</strong> <?= htmlspecialchars($application['preferred_work_experience']) ?> years</p>
        </div>
    </div>

    <!-- Applicant Information -->
    <div class="card mb-4">
        <div class="card-header">Applicant Information</div>
        <div class="card-body">
            <p><strong>ID Picture:<br></strong> <img src="<?= htmlspecialchars($base_path.$application['id_picture_reference']) ?>" alt="ID Picture" class="img-thumbnail" style="max-width: 150px;"></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($application['last_name']) ?>, <?= htmlspecialchars($application['first_name']) ?> <?= htmlspecialchars($application['middle_name']) ?></p>
            <p><strong>Nickname:</strong> <?= htmlspecialchars($application['nickname']) ?></p>
            <p><strong>Facebook Messenger Link:</strong> <a href="<?= htmlspecialchars($application['facebook_messenger_link']) ?>" target="_blank"><?= htmlspecialchars($application['facebook_messenger_link']) ?></a></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($application['email_address']) ?></p>
            <p><strong>Cellphone:</strong> <?= htmlspecialchars($application['cellphone_number']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($application['address']) ?></p>
            <p><strong>Birthday:</strong> <?= htmlspecialchars($application['birthday']) ?></p>
            <p><strong>Birth Place:</strong> <?= htmlspecialchars($application['birth_place']) ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($application['age']) ?></p>
            <p><strong>Sex:</strong> <?= htmlspecialchars($application['sex']) ?></p>
            <p><strong>Height:</strong> <?= htmlspecialchars($application['height_ft']) ?> ft</p>
            <p><strong>Marital Status:</strong> <?= htmlspecialchars($application['marital_status']) ?></p>
            <p><strong>Religion:</strong> <?= htmlspecialchars($application['religion']) ?></p>
            <p><strong>Tattoo:</strong> <?= $application['has_tattoo'] ? 'Yes' : 'No' ?></p>
            <p><strong>COVID-19 Vaccination:</strong> <?= htmlspecialchars($application['covid_vaccination_status']) ?></p>
        </div>
    </div>

    <div class="card mb-4">
    <div class="card-header">Job Requirements Checklist</div>
    <div class="card-body">
        <form id="requirements-form" method="post" action="save_requirements.php">
            <input type="hidden" name="application_id" value="<?= htmlspecialchars($application['application_id']) ?>">

            <?php foreach ($job_requirements as $req): ?>
                <?php 
                    $isChecked = in_array($req['requirement_name'], $checked_requirements);
                ?>
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        name="requirements[]" 
                        value="<?= htmlspecialchars($req['requirement_name']) ?>" 
                        class="form-check-input requirement-checkbox"
                        <?= $isChecked ? 'checked' : '' ?>
                        onchange="document.getElementById('requirements-form').submit();"
                    >
                    <label class="form-check-label"><?= htmlspecialchars($req['requirement_name']) ?></label>
                </div>
            <?php endforeach; ?>
        </form>
    </div>
</div>



    <!-- Work Experience -->
    <?php if ($work_experiences): ?>
        <div class="card mb-4">
            <div class="card-header">Work Experience</div>
            <div class="card-body">
                <?php foreach ($work_experiences as $experience): ?>
                    <p><strong>Company:</strong> <?= htmlspecialchars($experience['company_name']) ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($experience['role']) ?></p>
                    <p><strong>Years Worked:</strong> <?= htmlspecialchars($experience['years_worked']) ?></p>
                    <hr>
                <?php endforeach; ?>
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

    <!-- Questionnaire -->
    <?php if ($questionnaire_answers): ?>
        <div class="card mb-4">
            <div class="card-header">Questionnaire</div>
            <div class="card-body">
                <?php foreach ($questionnaire_answers as $answer): ?>
                    <p><strong>Question:</strong> <?= htmlspecialchars($answer['question_text']) ?></p>
                    <p><strong>Correct Answer:</strong> <?= $answer['correct_answer'] ? 'Yes' : 'No' ?></p>
                    <p class="<?= $answer['is_correct'] ? '' : 'incorrect-answer' ?>">
                        <strong>Applicant Answer:</strong> <?= $answer['answer_text'] == 1 ? 'Yes' : 'No' ?>
                    </p>
                    <?php if ($answer['dealbreaker']): ?>
                        <p class="text-danger"><em>This is a dealbreaker question.</em></p>
                    <?php endif; ?>
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Process Application -->
<div class="card mb-4">
    
    <div class="card-header">Process Application</div>
    <div class="card-body">
        <form method="post" action="process_application.php">
            <input type="hidden" name="application_id" value="<?= htmlspecialchars($application['application_id']) ?>">

            <div class="mb-3">
                <label class="form-label"><strong>Current Status:</strong></label>
                <p id="current-status" class="form-control-static"><?= htmlspecialchars($application['status']) ?></p>
            </div>

            <!-- Interview Details -->
            <?php if ($interview): ?>
                <div class="card mb-4">
                    <div class="card-header">Interview Details</div>
                    <div class="card-body">
                        <p><strong>Meeting Type:</strong> <?= htmlspecialchars($interview['meeting_type']) ?></p>
                        <p><strong>Interview Date:</strong> <?= date("M d, Y", strtotime($interview['interview_date'])) ?></p>
                        <p><strong>Interview Notes:</strong> <?= htmlspecialchars($interview['remarks']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Offer Details -->
            <?php if ($offer): ?>
                <div class="card mb-4">
                    <div class="card-header">Job Offer</div>
                    <div class="card-body">
                        <p><strong>Salary Offered:</strong> ₱<?= htmlspecialchars($offer['salary']) ?></p>
                        <p><strong>Start Date:</strong> <?= date("M d, Y", strtotime($offer['start_date'])) ?></p>
                        <p><strong>Additional Benefits:</strong> <?= htmlspecialchars($offer['benefits']) ?></p>
                        <p><strong>Offer Status:</strong> <?= htmlspecialchars($offer['remarks']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Deployment Details -->
            <?php if ($deployment): ?>
                <div class="card mb-4">
                    <div class="card-header">Deployment Details</div>
                    <div class="card-body">
                        <p><strong>Deployment Date:</strong> <?= date("M d, Y", strtotime($deployment['deployment_date'])) ?></p>
                        <p><strong>Additional Notes:</strong> <?= htmlspecialchars($deployment['remarks']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Status Selection -->
            <div class="mb-3">
                <label for="status" class="form-label">Update Status</label>
                <select name="action" id="action" class="form-select" required>
                    <option value="" disabled selected>Select an action</option>
                    <option value="interview">Schedule Interview</option>
                    <option value="offer">Make an Offer</option>
                    <option value="deploy">Deploy Applicant</option>
                    <option value="reject">Reject Applicant</option>
                </select>
            </div>

            <!-- Interview Fields -->
            <div id="interview-fields" style="display: none;">
                <div class="mb-3">
                    <label for="interview_type" class="form-label">Interview Type</label>
                    <select name="interview_type" id="interview_type" class="form-select">
                        <option value="face-to-face">Face-to-Face</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="interview_date" class="form-label">Interview Date</label>
                    <input type="date" name="interview_date" id="interview_date" class="form-control">
                </div>
                <div class="mb-3" id="meeting-link-group" style="display: none;">
                    <label for="meeting_link" class="form-label">Meeting Link</label>
                    <input type="url" name="meeting_link" id="meeting_link" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="recruiter_email" class="form-label">Recruiter Email</label>
                    <input type="email" name="recruiter_email" id="recruiter_email" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="interview_time" class="form-label">Interview Time</label>
                    <input type="time" name="interview_time" id="interview_time" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control"></textarea>
                </div>
            </div>

            <!-- Offer Fields -->
            <div id="offer-fields" style="display: none;">
                <div class="mb-3">
                    <label for="salary" class="form-label">Proposed Salary</label>
                    <input type="number" name="salary" id="salary" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="benefits" class="form-label">Benefits</label>
                    <textarea name="benefits" id="benefits" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label for="remarks_offer" class="form-label">Remarks</label>
                    <textarea name="remarks_offer" id="remarks_offer" class="form-control"></textarea>
                </div>
            </div>

            <!-- Deployment Fields -->
            <div id="deploy-fields" style="display: none;">
                <div class="mb-3">
                    <label for="deployment_date" class="form-label">Deployment Date</label>
                    <input type="date" name="deployment_date" id="deployment_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="deployment_remarks" class="form-label">Remarks</label>
                    <textarea name="deployment_remarks" id="deployment_remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <!-- reject Fields -->
            <div id="reject-fields" style="display: none;">
                <div class="mb-3">
                    <label for="reject_remarks" class="form-label">Remarks</label>
                    <textarea name="reject_remarks" id="reject_remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>
                    </div>
<script>
    // Select required DOM elements
    const statusField = document.getElementById('action');
    const interviewFields = document.getElementById('interview-fields');
    const offerFields = document.getElementById('offer-fields');
    const deployFields = document.getElementById('deploy-fields');
    const rejectFields = document.getElementById('reject-fields');
    const meetingLinkGroup = document.getElementById('meeting-link-group');
    const interviewTypeField = document.getElementById('interview_type');
    const submitBtn = document.querySelector('button[type="submit"]');

    /**
     * Function to toggle visibility of fields based on selected action.
     */
    function updateFieldVisibility() {
        const selectedValue = statusField.value;

        // Show or hide fields based on the selected action
        if (interviewFields) interviewFields.style.display = (selectedValue === 'interview') ? 'block' : 'none';
        if (offerFields) offerFields.style.display = (selectedValue === 'offer') ? 'block' : 'none';
        if (deployFields) deployFields.style.display = (selectedValue === 'deploy') ? 'block' : 'none';
        if (rejectFields) rejectFields.style.display = (selectedValue === 'reject') ? 'block' : 'none';

        // Ensure deployment date is only required when 'deploy' is selected
        const deployDateField = document.getElementById('deployment_date');
        if (deployFields.style.display === 'block' && deployDateField) {
            deployDateField.required = true; // Make it required only when visible
        } else if (deployDateField) {
            deployDateField.required = false; // Remove required attribute when not visible
        }

        // Show submit button only if a valid action is selected
        if (submitBtn) {
            submitBtn.style.display = selectedValue ? 'inline-block' : 'none';
        }
    }

    /**
     * Function to toggle visibility of meeting link based on interview type.
     */
    function updateMeetingLinkVisibility() {
        if (meetingLinkGroup) {
            meetingLinkGroup.style.display = (interviewTypeField.value === 'online') ? 'block' : 'none';
        }
    }

    // Attach event listeners
    statusField.addEventListener('change', updateFieldVisibility);
    if (interviewTypeField) {
        interviewTypeField.addEventListener('change', updateMeetingLinkVisibility);
    }

    // Initialize visibility on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateFieldVisibility();
        if (interviewTypeField) {
            updateMeetingLinkVisibility();
        }
    });
</script>

</div>
</body>
</html>
