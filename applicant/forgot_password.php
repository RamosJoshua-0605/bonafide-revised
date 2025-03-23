<?php
require 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

function sendResetEmail($email, $reset_token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@bonafideplacement.site';
        $mail->Password = 'Bonafide_01';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('no-reply@bonafideplacement.site', 'Bonafide Placement');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body = "Click this link to reset your password: <a href='http://localhost/bonafide/applicant/reset_password.php?token=$reset_token'>Reset Password</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$errors = [];
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM user_logins WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $reset_token = bin2hex(random_bytes(16));
            $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update_stmt = $pdo->prepare("UPDATE user_logins SET reset_token = ?, reset_token_expiration = ? WHERE email = ?");
            $update_stmt->execute([$reset_token, $expiration, $email]);

            if (sendResetEmail($email, $reset_token)) {
                $success_message = "A password reset link has been sent to your email.";
            } else {
                $errors['email'] = "Failed to send reset email.";
            }
        } else {
            $errors['email'] = "No account found with this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Forgot Password</h2>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Enter Your Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <small class="text-danger"><?php echo $errors['email'] ?? ''; ?></small>
        </div>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
    </form>

    <div class="mb-3">
            <a href="index.php">Back to Login</a>
    </div>
</div>
</body>
</html>
