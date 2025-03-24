<?php
require 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php");
    exit;
}

function sendVerificationEmail($email, $verification_token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.freesmtpservers.com';
        $mail->SMTPAuth = false;
        $mail->Port = 25;

        $mail->setFrom('no-reply@bonafideplacement.site', 'Bonafide Placement');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Account';
        $mail->Body = "Click this link to verify your account: <a href='http://localhost/bonafide/applicant/verify.php?token=$verification_token'>Verify Account</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Fetch user details for the current login_id
$stmt = $pdo->prepare("SELECT email, verification_token, status FROM user_logins WHERE login_id = ?");
$stmt->execute([$_SESSION['login_id']]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'Inactive') {
    header("Location: dashboard.php");
    exit;
}

// If no token exists, generate a new one
if (empty($user['verification_token'])) {
    $new_token = bin2hex(random_bytes(16));
    $update_stmt = $pdo->prepare("UPDATE user_logins SET verification_token = ? WHERE login_id = ?");
    $update_stmt->execute([$new_token, $_SESSION['login_id']]);
    $user['verification_token'] = $new_token;
}

$success_message = null;
$error_message = null;

// Handle the resend verification email request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (sendVerificationEmail($user['email'], $user['verification_token'])) {
        $success_message = "A verification email has been sent to " . htmlspecialchars($user['email'], ENT_QUOTES) . ". Please check your inbox.";
    } else {
        $error_message = "Failed to send verification email. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Account Verification</h2>
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    <p>Your account is currently inactive. Please verify your email to activate your account.</p>
    <form method="POST" action="">
        <button type="submit" class="btn btn-primary">Resend Verification Email</button>
    </form>
</div>
</body>
</html>
