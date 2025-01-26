<?php
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$error = '';

// Capture query and pagination parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // Number of applicants per page
$offset = ($page - 1) * $limit;

// Query to fetch matching applicants with pagination
$sql = "
    SELECT DISTINCT 
        u.user_id, 
        u.facebook_messenger_link, 
        u.email_address, 
        u.cellphone_number, 
        u.first_name, 
        u.last_name, 
        u.nickname, 
        u.age, 
        u.sex, 
        u.id_picture_reference
    FROM users u
    LEFT JOIN user_certifications c ON u.user_id = c.user_id
    LEFT JOIN user_work_experience w ON u.user_id = w.user_id
    LEFT JOIN user_education e ON u.user_id = e.user_id
    WHERE 
        CONCAT(u.first_name, ' ', u.last_name, ' ', u.nickname) LIKE :query
        OR u.email_address LIKE :query
        OR u.cellphone_number LIKE :query
        OR c.certification_name LIKE :query
        OR c.certification_institute LIKE :query
        OR w.company_name LIKE :query
        OR w.role LIKE :query
        OR e.highest_educational_attainment LIKE :query
        OR e.course_program LIKE :query
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total applicants for pagination
$countSql = "
    SELECT COUNT(DISTINCT u.user_id) AS total
    FROM users u
    LEFT JOIN user_certifications c ON u.user_id = c.user_id
    LEFT JOIN user_work_experience w ON u.user_id = w.user_id
    LEFT JOIN user_education e ON u.user_id = e.user_id
    WHERE 
        CONCAT(u.first_name, ' ', u.last_name, ' ', u.nickname) LIKE :query
        OR u.email_address LIKE :query
        OR u.cellphone_number LIKE :query
        OR c.certification_name LIKE :query
        OR c.certification_institute LIKE :query
        OR w.company_name LIKE :query
        OR w.role LIKE :query
        OR e.highest_educational_attainment LIKE :query
        OR e.course_program LIKE :query
";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
$countStmt->execute();
$totalApplicants = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$totalPages = ceil($totalApplicants / $limit);

$base_path = '../applicant/';

// Email sending logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_applicants'])) {
    $selectedApplicants = $_POST['selected_applicants'];
    $subject = $_POST['subject'] ?? 'Default Subject'; // Default subject if not set
    $body = $_POST['body'] ?? 'Default Body'; // Default body if not set

    // Process file attachments
    $attachments = [];
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $attachments = $_FILES['attachments'];
    }

    try {
        foreach ($selectedApplicants as $userId) {
            $emailStmt = $pdo->prepare("SELECT email_address FROM users WHERE user_id = :user_id");
            $emailStmt->execute(['user_id' => $userId]);
            $email = $emailStmt->fetchColumn();

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.mailersend.net';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'MS_34u9ve@trial-vywj2lprq3147oqz.mlsender.net';
                    $mail->Password = '0eiJXVs59vuXSJzt';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('MS_34u9ve@trial-vywj2lprq3147oqz.mlsender.net', 'Bonafide Trainology And Placement Services');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;

                    // Handle file attachments
                    if (!empty($attachments)) {
                        foreach ($attachments['tmp_name'] as $key => $tmpName) {
                            $mail->addAttachment($tmpName, $attachments['name'][$key]);
                        }
                    }

                    $mail->send();
                } catch (Exception $e) {
                    $error .= "Failed to send email to $email. Error: {$mail->ErrorInfo}<br>";
                }
            } else {
                $error .= "Failed to send email to $email. Error: {$mail->ErrorInfo}<br>";
            }
        }
    } catch (Exception $e) {
        $error .= "Error while sending emails: " . $e->getMessage() . "<br>";
    }

    if (empty($error)) {
        header('Location: user_management.php?message=Emails sent successfully');
    } else {
        header('Location: user_management.php?message=' . urlencode('Some emails failed to send. Check logs.'));
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Search Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>    
</head>
<body>

<div class="container mt-5">
    <!-- Displaying search results message -->
    <?php if (!empty($query)): ?>
        <h2>Displaying results for: "<?= htmlspecialchars($query) ?>"</h2>
    <?php endif; ?>

    <div>
        <p> <?php echo $error?> </p>
    </div>

    <!-- Applicants List -->
    <?php if (count($applicants) > 0): ?>
    <form id="applicantsForm" method="POST" action="search_applicants.php" enctype="multipart/form-data">
    <div class="row">
            <?php foreach ($applicants as $applicant): ?>
                <?php
                $imgSrc = (file_exists($base_path . $applicant['id_picture_reference']) && is_readable($base_path . $applicant['id_picture_reference'])) 
                    ? $base_path . $applicant['id_picture_reference'] 
                    : '../applicant/uploads/profile_pictures/default.png';
                ?>

                <div class="col-md-3 mb-4">
                    <div class="card">
                        <input type="checkbox" name="selected_applicants[]" value="<?= htmlspecialchars($applicant['user_id']) ?>" id="checkbox_<?= htmlspecialchars($applicant['user_id'])?>">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="card-img-top" alt="Applicant Picture" style="width: 100%; height: 200px; object-fit: scale-down;">                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) ?></h5>
                            <p class="card-text">
                                <strong>Age:</strong> <?= htmlspecialchars($applicant['age']) ?><br>
                                <strong>Sex:</strong> <?= htmlspecialchars($applicant['sex']) ?><br>
                                <strong>Messenger:</strong> <a href="<?= htmlspecialchars($applicant['facebook_messenger_link']) ?>" target="_blank"><?= htmlspecialchars($applicant['facebook_messenger_link']) ?></a><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($applicant['cellphone_number']) ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($applicant['email_address']) ?>
                            </p>

                            <!-- Query for Certifications -->
                            <?php
                                if (!empty($query)) {
                                    $certificationsQuery = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = :user_id AND (certification_name LIKE :query OR certification_institute LIKE :query)");
                                    $certificationsQuery->execute(['user_id' => $applicant['user_id'], 'query' => "%$query%"]);
                                    $certifications = $certificationsQuery->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    $certificationsQuery = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = :user_id");
                                    $certificationsQuery->execute(['user_id' => $applicant['user_id']]);
                                    $certifications = $certificationsQuery->fetchAll(PDO::FETCH_ASSOC);
                                }
                            ?>
                            <?php if (count($certifications) > 0): ?>
                                <h6>Certifications:</h6>
                                <ul>
                                    <?php foreach ($certifications as $certification): ?>
                                        <li>
                                            <?= htmlspecialchars($certification['certification_name']) ?> (<?= htmlspecialchars($certification['year_taken_certification']) ?>)
                                            from <?= htmlspecialchars($certification['certification_institute']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Query for Work Experience -->
                            <?php
                                if (!empty($query)) {
                                    $workExperienceQuery = $pdo->prepare("SELECT * FROM user_work_experience WHERE user_id = :user_id AND (company_name LIKE :query OR role LIKE :query)");
                                    $workExperienceQuery->execute(['user_id' => $applicant['user_id'], 'query' => "%$query%"]);
                                    $workExperiences = $workExperienceQuery->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    $workExperienceQuery = $pdo->prepare("SELECT * FROM user_work_experience WHERE user_id = :user_id");
                                    $workExperienceQuery->execute(['user_id' => $applicant['user_id']]);
                                    $workExperiences = $workExperienceQuery->fetchAll(PDO::FETCH_ASSOC);
                                }
                            ?>
                            <?php if (count($workExperiences) > 0): ?>
                                <h6>Work Experience:</h6>
                                <ul>
                                    <?php foreach ($workExperiences as $workExperience): ?>
                                        <li>
                                            <?= htmlspecialchars($workExperience['company_name']) ?> - <?= htmlspecialchars($workExperience['role']) ?> (<?= htmlspecialchars($workExperience['years_worked']) ?> years)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Query for Education -->
                            <?php
                                if (!empty($query)) {
                                    $educationQuery = $pdo->prepare("SELECT * FROM user_education WHERE user_id = :user_id AND (highest_educational_attainment LIKE :query OR course_program LIKE :query)");
                                    $educationQuery->execute(['user_id' => $applicant['user_id'], 'query' => "%$query%"]);
                                    $educations = $educationQuery->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    $educationQuery = $pdo->prepare("SELECT * FROM user_education WHERE user_id = :user_id");
                                    $educationQuery->execute(['user_id' => $applicant['user_id']]);
                                    $educations = $educationQuery->fetchAll(PDO::FETCH_ASSOC);
                                }
                            ?>
                            <?php if (count($educations) > 0): ?>
                                <h6>Education:</h6>
                                <ul>
                                    <?php foreach ($educations as $education): ?>
                                        <li>
                                            <?= htmlspecialchars($education['highest_educational_attainment']) ?> (<?= htmlspecialchars($education['year_graduated_college']) ?>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <a href="view_applicant_details.php?user_id=<?= $applicant['user_id'] ?>" class="btn btn-primary">View More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal for Sending Email -->
        <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="emailModalLabel">Send Email</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Email Subject -->
                            <div class="mb-3">
                                <label for="subject" class="form-label">Email Subject</label>
                                <input type="text" name="subject" id="subject" class="form-control" placeholder="Enter the subject" required>
                            </div>

                            <!-- Email Body -->
                            <div class="mb-3">
                                <label for="body" class="form-label">Email Body</label>
                                <textarea name="body" id="body" class="form-control" rows="4" placeholder="Enter the email body" required></textarea>
                            </div>

                            <!-- Attach Files -->
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Attach Files (Optional)</label>
                                <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                                <small class="form-text text-muted">You can attach multiple files.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="submitEmail">Send</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
</form>
        <!-- Trigger Modal -->
        <button type="button" class="btn btn-success mt-3" id="openModalButton" data-bs-toggle="modal" data-bs-target="#emailModal">Send Email</button>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?q=<?= urlencode($query) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info">No applicants found.</div>
    <?php endif; ?>
</div>
</body>
</html>
