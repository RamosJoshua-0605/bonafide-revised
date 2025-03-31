<?php
ob_start(); 
require 'db.php';
require 'auth.php';
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
        jp.job_post_id, 
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

$job_post_id = $application['job_post_id'];

// Handle Withdraw Request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['withdraw'])) {
    try {
        $pdo->beginTransaction();

        // Update job_applications table (set withdrawn_at, status, and total_duration)
        $updateJobApplication = $pdo->prepare("
            UPDATE job_applications 
            SET 
                status = 'Withdrawn', 
                withdrawn_at = NOW(),
                total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) -- Calculates duration in days
            WHERE application_id = :application_id
        ");
        $updateJobApplication->execute(['application_id' => $application_id]);

        // Fetch updated job metrics data
        $metricsQuery = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_applicants,
                SUM(CASE WHEN status = 'Hired' THEN 1 ELSE 0 END) AS successful_placements,
                SUM(CASE WHEN status = 'Withdrawn' THEN 1 ELSE 0 END) AS withdrawn_applicants,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_applicants,
                jm.referral_applicants,
                jm.social_media_applicants,
                jm.career_site_applicants
            FROM job_applications ja
            JOIN job_metrics jm ON ja.job_post_id = jm.job_post_id
            WHERE ja.job_post_id = :job_post_id
        ");
        $metricsQuery->execute(['job_post_id' => $job_post_id]);
        $metrics = $metricsQuery->fetch(PDO::FETCH_ASSOC);

        $total_applicants = $metrics['total_applicants'];
        $successful_placements = $metrics['successful_placements'];
        $withdrawn_applicants = $metrics['withdrawn_applicants'];
        $rejected_applicants = $metrics['rejected_applicants'];
        $referral_applicants = $metrics['referral_applicants'];
        $social_media_applicants = $metrics['social_media_applicants'];
        $career_site_applicants = $metrics['career_site_applicants'];

        // Calculate new metrics
        $applicant_to_hire_ratio = ($total_applicants > 0) ? ($successful_placements / $total_applicants) * 100 : 0;
        $dropout_rate = ($total_applicants > 0) ? (($withdrawn_applicants + $rejected_applicants) / $total_applicants) * 100 : 0;
        $referral_success_rate = ($referral_applicants > 0) ? ($successful_placements / $referral_applicants) * 100 : 0;
        $social_media_success_rate = ($social_media_applicants > 0) ? ($successful_placements / $social_media_applicants) * 100 : 0;
        $career_site_success_rate = ($career_site_applicants > 0) ? ($successful_placements / $career_site_applicants) * 100 : 0;

        // Update job_metrics table
        $updateJobMetrics = $pdo->prepare("
            UPDATE job_metrics 
            SET 
                withdrawn_applicants = :withdrawn_applicants,
                applicant_to_hire_ratio = :applicant_to_hire_ratio,
                dropout_rate = :dropout_rate,
                referral_success_rate = :referral_success_rate,
                social_media_success_rate = :social_media_success_rate,
                career_site_success_rate = :career_site_success_rate
            WHERE job_post_id = :job_post_id
        ");
        $updateJobMetrics->execute([
            'withdrawn_applicants' => $withdrawn_applicants,
            'applicant_to_hire_ratio' => $applicant_to_hire_ratio,
            'dropout_rate' => $dropout_rate,
            'referral_success_rate' => $referral_success_rate,
            'social_media_success_rate' => $social_media_success_rate,
            'career_site_success_rate' => $career_site_success_rate,
            'job_post_id' => $job_post_id
        ]);

        // Fetch job post details (for notification message)
        $jobQuery = $pdo->prepare("SELECT job_post_id FROM job_applications WHERE application_id = :application_id");
        $jobQuery->execute(['application_id' => $application_id]);
        $job = $jobQuery->fetch(PDO::FETCH_ASSOC);
        $job_post_id = $job['job_post_id'];

        // Insert notification for recruiters
        $recruitersQuery = $pdo->prepare("SELECT login_id FROM user_logins WHERE role = 'Recruiter'");
        $recruitersQuery->execute();
        $recruiters = $recruitersQuery->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recruiters as $recruiter) {
            $notificationQuery = $pdo->prepare("
                INSERT INTO notifications (user_id, title, subject, link, is_read, created_at) 
                VALUES (:user_id, :title, :subject, :link, 0, NOW())
            ");
            $notificationQuery->execute([
                'user_id'  => $recruiter['login_id'],
                'title'    => 'Application Withdrawn',
                'subject'  => 'An applicant has withdrawn their application.',
                'link'     => 'view_application_details.php?application_id=' . $application_id
            ]);
        }
        
        $pdo->commit();
        // Set a session success message
        $_SESSION['withdraw_success'] = "Your application has been withdrawn successfully.";
        
        // Redirect to my_applications.php after successful withdrawal
        header("Location: my_applications.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error withdrawing application: " . $e->getMessage());
    }
}

// Fetch user work experience
$work_experience_query = $pdo->prepare("SELECT * FROM user_work_experience WHERE user_id = :user_id");
$work_experience_query->execute(['user_id' => $application['user_id']]);
$work_experiences = $work_experience_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch user certifications
$certifications_query = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = :user_id");
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
$questionnaire = $questionnaire_query->fetchAll(PDO::FETCH_ASSOC);

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
        .incorrect-answer { background-color: #f8d7da; }
    </style>
</head>
<body>
<div id="content">
    <div class="container mt-5">

    <div class="mb-3">
            <a href="my_applications.php">Back to Applications</a>
    </div>

        <h1>Application Details</h1>

        <!-- Success Message -->
        <?php if (!empty($withdraw_success)): ?>
            <div class="alert alert-success">Your application has been withdrawn successfully.</div>
        <?php endif; ?>

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
                <p><strong>ID Picture:</strong> <br> <img src="<?= htmlspecialchars($base_path.$application['id_picture_reference']) ?>" alt="ID Picture" class="img-thumbnail" style="max-width: 150px;"></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($application['last_name'] . ', ' . $application['first_name'] . ' ' . $application['middle_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($application['email_address']) ?></p>
                <p><strong>Cellphone:</strong> <?= htmlspecialchars($application['cellphone_number']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($application['address']) ?></p>
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
    <?php if ($questionnaire): ?>
        <div class="card mb-4">
            <div class="card-header">Pre-Qualification Assesment</div>
            <div class="card-body">
                <?php foreach ($questionnaire as $answer): ?>
                    <p><strong>Question:</strong> <?= htmlspecialchars($answer['question_text']) ?></p>
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

<?php
// Fetch comment from the database
$comment_query = $pdo->prepare("SELECT comments FROM job_applications WHERE application_id = :application_id");
$comment_query->execute(['application_id' => $application_id]);
$comment = $comment_query->fetch(PDO::FETCH_ASSOC);
?>

<?php if ($comment && !empty($comment['comment'])): ?>
    <div class="card mb-4">
        <div class="card-header">Recruiter Comment</div>
        <div class="card-body">
            <p><?= htmlspecialchars($comment['comment']) ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">No comments available for this application.</div>
<?php endif; ?>

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

        <!-- Withdraw Application -->
        <?php if ($application['status'] !== 'Withdrawn' && $application['status'] !== 'Rejected'): ?>
            <div class="card mb-4">
                <div class="card-header">Withdraw Application</div>
                <div class="card-body text-center">
                    <form method="post">
                        <input type="hidden" name="withdraw" value="1">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to withdraw your application?');">Withdraw Application</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                <strong>Status:</strong> Your application has been withdrawn or rejected.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
