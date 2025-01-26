<?php
require 'db.php';

$jobPostId = isset($_GET['job_post_id']) ? (int)$_GET['job_post_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 5;

if ($jobPostId > 0 && $page > 0) {
    $offset = ($page - 1) * $itemsPerPage;

    // Fetch the total number of applications for the job post
    $totalApplicationsQuery = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM job_applications
        WHERE job_post_id = :job_post_id
    ");
    $totalApplicationsQuery->execute(['job_post_id' => $jobPostId]);
    $totalApplications = $totalApplicationsQuery->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate total pages
    $totalPages = ceil($totalApplications / $itemsPerPage);

    // Fetch job post details for scoring preferences
    $jobPostQuery = $pdo->prepare("
        SELECT preferred_age_range, preferred_work_experience, preferred_educational_level 
        FROM job_posts 
        WHERE job_post_id = :job_post_id
    ");
    $jobPostQuery->execute(['job_post_id' => $jobPostId]);
    $jobPost = $jobPostQuery->fetch(PDO::FETCH_ASSOC);

    $hasPreferences = !empty($jobPost['preferred_age_range']) 
                    && !empty($jobPost['preferred_work_experience']) 
                    && !empty($jobPost['preferred_educational_level']);

    // Fetch paginated applications
    $applicationsQuery = $pdo->prepare("
        SELECT ja.application_id, ja.job_post_id, ja.user_id, ja.status, ja.work_experience, ja.applied_at, 
               u.first_name, u.last_name, u.age, u.email_address, u.cellphone_number, u.address,
               je.highest_educational_attainment
        FROM job_applications ja
        JOIN users u ON ja.user_id = u.user_id
        LEFT JOIN user_education je ON u.user_id = je.user_id
        WHERE ja.job_post_id = :job_post_id
        LIMIT :itemsPerPage OFFSET :offset
    ");
    $applicationsQuery->bindValue('job_post_id', $jobPostId, PDO::PARAM_INT);
    $applicationsQuery->bindValue('itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
    $applicationsQuery->bindValue('offset', $offset, PDO::PARAM_INT);
    $applicationsQuery->execute();
    $applications = $applicationsQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($applications as &$application) {
        $application['score'] = 0; // Default score

        // Skip score calculation if preferences are missing
        if (!$hasPreferences) {
            continue;
        }

        $score = 0;

        // Age Score
        if (!empty($jobPost['preferred_age_range'])) {
            $ageRange = explode('-', $jobPost['preferred_age_range']);
            if (isset($ageRange[0], $ageRange[1]) 
                && $application['age'] >= $ageRange[0] 
                && $application['age'] <= $ageRange[1]) {
                $score += 25;
            }
        }

        // Work Experience Score
        if (!empty($jobPost['preferred_work_experience']) 
            && $application['work_experience'] >= $jobPost['preferred_work_experience']) {
            $score += 25;
        }

        // Education Score
        if (!empty($jobPost['preferred_educational_level']) 
            && $application['highest_educational_attainment'] === $jobPost['preferred_educational_level']) {
            $score += 25;
        }

        // Questionnaire Score
        $questionnaireQuery = $pdo->prepare("
            SELECT qa.is_correct 
            FROM questionnaire_answers qa
            JOIN questionnaires q ON qa.question_id = q.question_id
            WHERE qa.application_id = :application_id
        ");
        $questionnaireQuery->execute(['application_id' => $application['application_id']]);
        $answers = $questionnaireQuery->fetchAll(PDO::FETCH_ASSOC);

        if ($answers) {
            $questionWeight = 25 / count($answers);
            foreach ($answers as $answer) {
                if ($answer['is_correct']) {
                    $score += $questionWeight;
                }
            }
        }
        $application['score'] = round($score, 2); // Round to 2 decimal places
    }

    echo json_encode([
        'applications' => $applications,
        'totalPages' => $totalPages,
        'currentPage' => $page,
    ]);
} else {
    echo json_encode(['error' => 'Invalid job_post_id or page']);
}
?>
