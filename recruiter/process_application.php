<?php
require 'db.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = $_POST['application_id'];
    $action = $_POST['action']; // Determines the stage of processing: reject, interview, offer, or deploy
    $remarks = $_POST['remarks'] ?? null;

    try {
        // Fetch application details
        $query = $pdo->prepare("
            SELECT job_post_id, status, applied_at 
            FROM job_applications 
            WHERE application_id = :application_id
        ");
        $query->execute(['application_id' => $application_id]);
        $application = $query->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            throw new Exception("Application not found.");
        }

        $job_post_id = $application['job_post_id'];
        $status = $application['status'];
        $applied_at = $application['applied_at'];

        // Handle each action
        if ($action === 'reject') {
            $reject_remarks = $_POST['reject_remarks'] ?? null; // Get the rejection remarks

            // Update application as rejected
            $query = $pdo->prepare("
                UPDATE job_applications 
                SET status = 'Rejected', rejected_at = NOW(), screened_at = NOW(), 
                    duration_applied_to_screened = TIMESTAMPDIFF(DAY, :applied_at, NOW()), 
                    total_duration = TIMESTAMPDIFF(DAY, :applied_at, NOW()), 
                    comments = :remarks 
                WHERE application_id = :application_id
            ");
            $query->execute([
                'applied_at' => $applied_at,
                'remarks' => $reject_remarks,
                'application_id' => $application_id
            ]);

            // Calculate average durations
            $avgQuery = $pdo->prepare("
            SELECT 
                AVG(duration_applied_to_screened) AS avg_duration_applied_to_screened,
                AVG(duration_screened_to_interviewed) AS avg_duration_screened_to_interviewed,
                AVG(duration_interviewed_to_offered) AS avg_duration_interviewed_to_offered,
                AVG(duration_offered_to_hired) AS avg_duration_offered_to_hired,
                AVG(total_duration) AS avg_total_duration
            FROM job_applications
            WHERE job_post_id = :job_post_id
            ");
            $avgQuery->execute(['job_post_id' => $job_post_id]);
            $averages = $avgQuery->fetch(PDO::FETCH_ASSOC);

            // Fetch required data for metrics calculations
            $query = $pdo->prepare("
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
            $query->execute(['job_post_id' => $job_post_id]);
            $metrics = $query->fetch(PDO::FETCH_ASSOC);
        
            $total_applicants = $metrics['total_applicants'];
            $successful_placements = $metrics['successful_placements'];
            $withdrawn_applicants = $metrics['withdrawn_applicants'];
            $rejected_applicants = $metrics['rejected_applicants'];
            $referral_applicants = $metrics['referral_applicants'];
            $social_media_applicants = $metrics['social_media_applicants'];
            $career_site_applicants = $metrics['career_site_applicants'];
        
            // Calculate additional metrics
            $applicant_to_hire_ratio = ($total_applicants > 0) ? ($successful_placements / $total_applicants) * 100 : 0;
            $dropout_rate = ($total_applicants > 0) ? (($withdrawn_applicants + $rejected_applicants) / $total_applicants) * 100 : 0;
            $referral_success_rate = ($referral_applicants > 0) ? ($successful_placements / $referral_applicants) * 100 : 0;
            $social_media_success_rate = ($social_media_applicants > 0) ? ($successful_placements / $social_media_applicants) * 100 : 0;
            $career_site_success_rate = ($career_site_applicants > 0) ? ($successful_placements / $career_site_applicants) * 100 : 0;
        
            // Update metrics
            $metricsQuery = $pdo->prepare("
                UPDATE job_metrics 
                SET 
                    successful_placements = :successful_placements,
                    applicant_to_hire_ratio = :applicant_to_hire_ratio,
                    dropout_rate = :dropout_rate,
                    referral_success_rate = :referral_success_rate,
                    social_media_success_rate = :social_media_success_rate,
                    career_site_success_rate = :career_site_success_rate
                WHERE job_post_id = :job_post_id
            ");
            $metricsQuery->execute([
                'successful_placements' => $successful_placements,
                'applicant_to_hire_ratio' => $applicant_to_hire_ratio,
                'dropout_rate' => $dropout_rate,
                'referral_success_rate' => $referral_success_rate,
                'social_media_success_rate' => $social_media_success_rate,
                'career_site_success_rate' => $career_site_success_rate,
                'job_post_id' => $job_post_id
            ]);

            // Update job_metrics with average durations
            $updateMetricsQuery = $pdo->prepare("
            UPDATE job_metrics 
            SET 
                avg_duration_applied_to_screened = :avg_duration_applied_to_screened,
                avg_duration_screened_to_interviewed = :avg_duration_screened_to_interviewed,
                avg_duration_interviewed_to_offered = :avg_duration_interviewed_to_offered,
                avg_duration_offered_to_hired = :avg_duration_offered_to_hired,
                avg_total_duration = :avg_total_duration
            WHERE job_post_id = :job_post_id
            ");
            $updateMetricsQuery->execute([
            'avg_duration_applied_to_screened' => $averages['avg_duration_applied_to_screened'] ?? 0,
            'avg_duration_screened_to_interviewed' => $averages['avg_duration_screened_to_interviewed'] ?? 0,
            'avg_duration_interviewed_to_offered' => $averages['avg_duration_interviewed_to_offered'] ?? 0,
            'avg_duration_offered_to_hired' => $averages['avg_duration_offered_to_hired'] ?? 0,
            'avg_total_duration' => $averages['avg_total_duration'] ?? 0,
            'job_post_id' => $job_post_id
            ]);

           // Retrieve user_id of the applicant
$userQuery = $pdo->prepare("SELECT user_id FROM job_applications WHERE application_id = :application_id");
$userQuery->execute(['application_id' => $application_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['user_id'];
    $notification_title = "Application Rejected";
    $notification_subject = "Your job application has been rejected.";
    $notification_link = "http://localhost/bonafide/applicant/application_details.php?application_id=" . $application_id;

    // Insert notification for the user
    $notificationQuery = $pdo->prepare("
        INSERT INTO notifications (user_id, title, subject, link, is_read, created_at)
        VALUES (:user_id, :title, :subject, :link, 0, NOW())
    ");
    $notificationQuery->execute([
        'user_id' => $user_id,
        'title' => $notification_title,
        'subject' => $notification_subject,
        'link' => $notification_link
    ]);
}
        } elseif ($action === 'interview') {
                // Schedule interview
                $interview_type = $_POST['interview_type'];
                $interview_date = $_POST['interview_date'];
                $meeting_link = $_POST['meeting_link'] ?? null;
                $recruiter_email = $_POST['recruiter_email'];
                $interview_time = $_POST['interview_time'];
        
        
                if ($exists) {
                    // Update the existing interview record
                    $query = $pdo->prepare("
                        UPDATE interview_details 
                        SET meeting_type = :interview_type, 
                            interview_date = :interview_date, 
                            meeting_link = :meeting_link, 
                            recruiter_email = :recruiter_email, 
                            interview_time = :interview_time, 
                            remarks = :remarks 
                        WHERE application_id = :application_id
                    ");
                } else {
                    // Insert a new interview record
                    $query = $pdo->prepare("
                        INSERT INTO interview_details 
                        (application_id, meeting_type, interview_date, meeting_link, recruiter_email, interview_time, remarks) 
                        VALUES (:application_id, :interview_type, :interview_date, :meeting_link, :recruiter_email, :interview_time, :remarks)
                    ");
                }
        
                // Execute the insert or update query
                $query->execute([
                    'application_id' => $application_id,
                    'interview_type' => $interview_type,
                    'interview_date' => $interview_date,
                    'meeting_link' => $meeting_link,
                    'recruiter_email' => $recruiter_email,
                    'interview_time' => $interview_time,
                    'remarks' => $remarks
                ]);
        
                // Fetch job_post_id and applied_at from the job_applications table
                $fetchJobDetails = $pdo->prepare("
                    SELECT job_post_id, applied_at 
                    FROM job_applications 
                    WHERE application_id = :application_id
                ");
                $fetchJobDetails->execute(['application_id' => $application_id]);
                $jobDetails = $fetchJobDetails->fetch(PDO::FETCH_ASSOC);
        
                if (!$jobDetails) {
                    throw new Exception("Job application not found for ID: $application_id.");
                }
        
                $job_post_id = $jobDetails['job_post_id'];
                $applied_at = $jobDetails['applied_at'];
        
                // Update application status
                $query = $pdo->prepare("
                    UPDATE job_applications 
                    SET status = 'Interviewed', 
                        screened_at = NOW(), 
                        duration_applied_to_screened = TIMESTAMPDIFF(DAY, :applied_at, NOW()) 
                    WHERE application_id = :application_id
                ");
                $query->execute([
                    'applied_at' => $applied_at,
                    'application_id' => $application_id
                ]);
        
                // Update metrics
                $metricsQuery = $pdo->prepare("
                    UPDATE job_metrics 
                    SET interviewed_applicants = interviewed_applicants + 1,
                        screened_applicants = screened_applicants + 1 
                    WHERE job_post_id = :job_post_id
                ");
                $metricsQuery->execute(['job_post_id' => $job_post_id]);
        
                // Retrieve user_id of the applicant
$userQuery = $pdo->prepare("SELECT user_id FROM job_applications WHERE application_id = :application_id");
$userQuery->execute(['application_id' => $application_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['user_id'];
    $notification_title = "Interview Scheduled";
    $notification_subject = "An interview has been scheduled for you application.";
    $notification_link = "http://localhost/bonafide/applicant/application_details.php?application_id=" . $application_id;

    // Insert notification for the user
    $notificationQuery = $pdo->prepare("
        INSERT INTO notifications (user_id, title, subject, link, is_read, created_at)
        VALUES (:user_id, :title, :subject, :link, 0, NOW())
    ");
    $notificationQuery->execute([
        'user_id' => $user_id,
        'title' => $notification_title,
        'subject' => $notification_subject,
        'link' => $notification_link
    ]);
}
        } elseif ($action === 'offer') {
            $remarks_offer = $_POST['remarks_offer'] ?? null; // Get the rejection remarks
            // Make an offer
            $salary = $_POST['salary'];
            $start_date = $_POST['start_date'];
            $benefits = $_POST['benefits'];

            // Insert offer details
            $query = $pdo->prepare("
                INSERT INTO offer_details 
                (application_id, salary, start_date, benefits, remarks) 
                VALUES (:application_id, :salary, :start_date, :benefits, :remarks)
            ");
            $query->execute([
                'application_id' => $application_id,
                'salary' => $salary,
                'start_date' => $start_date,
                'benefits' => $benefits,
                'remarks' => $remarks_offer
            ]);

            // Update application status
            $query = $pdo->prepare("
                UPDATE job_applications 
                SET status = 'Offered', offered_at = NOW(), 
                    duration_interviewed_to_offered = TIMESTAMPDIFF(DAY, screened_at, NOW()) 
                WHERE application_id = :application_id
            ");
            $query->execute(['application_id' => $application_id]);

            // Update metrics
            $metricsQuery = $pdo->prepare("
                UPDATE job_metrics 
                SET offered_applicants = offered_applicants + 1 
                WHERE job_post_id = :job_post_id
            ");
            $metricsQuery->execute(['job_post_id' => $job_post_id]);

            // Retrieve user_id of the applicant
$userQuery = $pdo->prepare("SELECT user_id FROM job_applications WHERE application_id = :application_id");
$userQuery->execute(['application_id' => $application_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['user_id'];
    $notification_title = "Job Offer";
    $notification_subject = "You have received a job offer.";
    $notification_link = "http://localhost/bonafide/applicant/application_details.php?application_id=" . $application_id;

    // Insert notification for the user
    $notificationQuery = $pdo->prepare("
        INSERT INTO notifications (user_id, title, subject, link, is_read, created_at)
        VALUES (:user_id, :title, :subject, :link, 0, NOW())
    ");
    $notificationQuery->execute([
        'user_id' => $user_id,
        'title' => $notification_title,
        'subject' => $notification_subject,
        'link' => $notification_link
    ]);
}
        } elseif ($action === 'deploy') {
            // Deploy the applicant
            $deployment_remarks = $_POST['deployment_remarks'] ?? null; // Get the rejection remarks

            $deployment_date = $_POST['deployment_date'];
        
            // Calculate average durations
            $avgQuery = $pdo->prepare("
            SELECT 
                AVG(duration_applied_to_screened) AS avg_duration_applied_to_screened,
                AVG(duration_screened_to_interviewed) AS avg_duration_screened_to_interviewed,
                AVG(duration_interviewed_to_offered) AS avg_duration_interviewed_to_offered,
                AVG(duration_offered_to_hired) AS avg_duration_offered_to_hired,
                AVG(total_duration) AS avg_total_duration
            FROM job_applications
            WHERE job_post_id = :job_post_id
            ");
            $avgQuery->execute(['job_post_id' => $job_post_id]);
            $averages = $avgQuery->fetch(PDO::FETCH_ASSOC);

            // Insert deployment details
            $query = $pdo->prepare("
                INSERT INTO deployment_details 
                (application_id, deployment_date, remarks) 
                VALUES (:application_id, :deployment_date, :remarks)
            ");
            $query->execute([
                'application_id' => $application_id,
                'deployment_date' => $deployment_date,
                'remarks' => $deployment_remarks
            ]);
        
            // Update application status
            $query = $pdo->prepare("
                UPDATE job_applications 
                SET status = 'Hired', deployed_at = NOW(), 
                    duration_offered_to_hired = TIMESTAMPDIFF(DAY, offered_at, NOW()), 
                    total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
                WHERE application_id = :application_id
            ");
            $query->execute(['application_id' => $application_id]);
        
            // Fetch required data for metrics calculations
            $query = $pdo->prepare("
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
            $query->execute(['job_post_id' => $job_post_id]);
            $metrics = $query->fetch(PDO::FETCH_ASSOC);
        
            $total_applicants = $metrics['total_applicants'];
            $successful_placements = $metrics['successful_placements'];
            $withdrawn_applicants = $metrics['withdrawn_applicants'];
            $rejected_applicants = $metrics['rejected_applicants'];
            $referral_applicants = $metrics['referral_applicants'];
            $social_media_applicants = $metrics['social_media_applicants'];
            $career_site_applicants = $metrics['career_site_applicants'];
        
            // Calculate additional metrics
            $applicant_to_hire_ratio = ($total_applicants > 0) ? ($successful_placements / $total_applicants) * 100 : 0;
            $dropout_rate = ($total_applicants > 0) ? (($withdrawn_applicants + $rejected_applicants) / $total_applicants) * 100 : 0;
            $referral_success_rate = ($referral_applicants > 0) ? ($successful_placements / $referral_applicants) * 100 : 0;
            $social_media_success_rate = ($social_media_applicants > 0) ? ($successful_placements / $social_media_applicants) * 100 : 0;
            $career_site_success_rate = ($career_site_applicants > 0) ? ($successful_placements / $career_site_applicants) * 100 : 0;
        
            // Update metrics
            $metricsQuery = $pdo->prepare("
                UPDATE job_metrics 
                SET 
                    successful_placements = :successful_placements,
                    applicant_to_hire_ratio = :applicant_to_hire_ratio,
                    dropout_rate = :dropout_rate,
                    referral_success_rate = :referral_success_rate,
                    social_media_success_rate = :social_media_success_rate,
                    career_site_success_rate = :career_site_success_rate
                WHERE job_post_id = :job_post_id
            ");
            $metricsQuery->execute([
                'successful_placements' => $successful_placements,
                'applicant_to_hire_ratio' => $applicant_to_hire_ratio,
                'dropout_rate' => $dropout_rate,
                'referral_success_rate' => $referral_success_rate,
                'social_media_success_rate' => $social_media_success_rate,
                'career_site_success_rate' => $career_site_success_rate,
                'job_post_id' => $job_post_id
            ]);

            // Update job_metrics with average durations
            $updateMetricsQuery = $pdo->prepare("
            UPDATE job_metrics 
            SET 
                avg_duration_applied_to_screened = :avg_duration_applied_to_screened,
                avg_duration_screened_to_interviewed = :avg_duration_screened_to_interviewed,
                avg_duration_interviewed_to_offered = :avg_duration_interviewed_to_offered,
                avg_duration_offered_to_hired = :avg_duration_offered_to_hired,
                avg_total_duration = :avg_total_duration
            WHERE job_post_id = :job_post_id
            ");
            $updateMetricsQuery->execute([
            'avg_duration_applied_to_screened' => $averages['avg_duration_applied_to_screened'] ?? 0,
            'avg_duration_screened_to_interviewed' => $averages['avg_duration_screened_to_interviewed'] ?? 0,
            'avg_duration_interviewed_to_offered' => $averages['avg_duration_interviewed_to_offered'] ?? 0,
            'avg_duration_offered_to_hired' => $averages['avg_duration_offered_to_hired'] ?? 0,
            'avg_total_duration' => $averages['avg_total_duration'] ?? 0,
            'job_post_id' => $job_post_id
            ]);

            // Retrieve user_id of the applicant
$userQuery = $pdo->prepare("SELECT user_id FROM job_applications WHERE application_id = :application_id");
$userQuery->execute(['application_id' => $application_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['user_id'];
    $notification_title = "You have been hired!";
    $notification_subject = "Your job application has been accepted.";
    $notification_link = "http://localhost/bonafide/applicant/application_details.php?application_id=" . $application_id;

    // Insert notification for the user
    $notificationQuery = $pdo->prepare("
        INSERT INTO notifications (user_id, title, subject, link, is_read, created_at)
        VALUES (:user_id, :title, :subject, :link, 0, NOW())
    ");
    $notificationQuery->execute([
        'user_id' => $user_id,
        'title' => $notification_title,
        'subject' => $notification_subject,
        'link' => $notification_link
    ]);
}
        } else {
            throw new Exception("Invalid action.");
        }        

        // Redirect with success
        header("Location: view_application_details.php?application_id=$application_id&success=1");
        exit();

    } catch (Exception $e) {
        // Handle errors
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>