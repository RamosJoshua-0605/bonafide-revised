<?php
ob_start();
require 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referral_code = $_POST['referral_code'] ?? null;

    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email address.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Validate referral code (if provided)
    if (!empty($referral_code)) {
        $referrer_stmt = $pdo->prepare("
            SELECT u.user_id, ul.role 
            FROM users u
            INNER JOIN user_logins ul ON u.user_id = ul.user_id
            WHERE u.referral_code = ?
        ");
        $referrer_stmt->execute([$referral_code]);
        if ($referrer_stmt->rowCount() === 0) {
            $errors['referral_code'] = "Invalid referral code.";
        } else {
            $referrer = $referrer_stmt->fetch();
            $referrer_id = $referrer['user_id'];
            $referrer_role = $referrer['role'];
        }
    }

    if (empty($errors)) {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM user_logins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "Email already registered.";
        } else {
            // Insert user and send verification email
            $verification_token = bin2hex(random_bytes(16));
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $last_login = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("INSERT INTO user_logins (email, password_hash, verification_token, role, status, last_login) VALUES (?, ?, ?, 'Applicant', 'Active', ?)");
            $stmt->execute([$email, $password_hash, $verification_token, $last_login]);
            $referred_user_id = $pdo->lastInsertId();

            $_SESSION['login_id'] = $referred_user_id;
            
            // Handle referrals if the referral code was valid
            if (!empty($referral_code) && isset($referrer_id)) {
                $pdo->prepare("
                    INSERT INTO referrals (referrer_id, referred_user_id, referrer_user_role, referral_date, status) 
                    VALUES (?, ?, ?, NOW(), 'Pending')
                ")->execute([$referrer_id, $referred_user_id, $referrer_role]);
            }

            // if (sendVerificationEmail($email, $verification_token)) {
            //     $_SESSION['success'] = "Registration successful! Please check your email to verify your account.";
            //     $_SESSION['verification_email'] = $email; // Store email for resending verification
            //     header("Location: registration.php");
            //     exit;
            // } else {
            //     $errors['email'] = "Email could not be sent. Please try again later.";
            // }
        }
    }

    header("Location: dashboard.php");
}

// if (isset($_GET['resend_verification']) && isset($_SESSION['verification_email'])) {
//     $email = $_SESSION['verification_email'];

//     // Generate a new verification token
//     $new_verification_token = bin2hex(random_bytes(16));

//     // Update the database with the new token
//     $update_stmt = $pdo->prepare("UPDATE user_logins SET verification_token = ? WHERE email = ?");
//     $update_stmt->execute([$new_verification_token, $email]);

//     if ($update_stmt->rowCount() > 0) {
//         // Send the new verification email
//         if (sendVerificationEmail($email, $new_verification_token)) {
//             $_SESSION['success'] = "Verification email resent successfully.";
//             header("Location: registration.php");
//             exit;
//         } else {
//             $_SESSION['error'] = "Failed to send verification email. Please try again later.";
//         }
//     } else {
//         $_SESSION['error'] = "Failed to generate a new verification token.";
//     }
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Register</h2>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <p>Didn't receive an email? <a href="?resend_verification=true" class="btn btn-link">Resend Verification Email</a></p>
        </div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required>
            <small class="text-danger"><?php echo $errors['email'] ?? ''; ?></small>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required>
                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                    <i class="bi bi-eye" id="toggleIconPassword"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['password'] ?? ''; ?></small>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                    <i class="bi bi-eye" id="toggleIconConfirmPassword"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['confirm_password'] ?? ''; ?></small>
        </div>
        <div class="mb-3">
            <label for="referral_code" class="form-label">Referral Code (optional)</label>
            <input type="text" class="form-control" id="referral_code" name="referral_code" value="<?php echo htmlspecialchars($_POST['referral_code'] ?? '', ENT_QUOTES); ?>">
            <small class="text-danger"><?php echo $errors['referral_code'] ?? ''; ?></small>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    <div class="mt-3">
        <p>Already have an account? <a href="index.php">Login here</a>.</p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIconPassword');
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
        const confirmPasswordField = document.getElementById('confirm_password');
        const toggleIcon = document.getElementById('toggleIconConfirmPassword');
        const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
        confirmPasswordField.type = type;
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>
