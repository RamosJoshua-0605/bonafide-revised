<?php
ob_start();
require 'db.php';
require 'header.php';
require 'sidebar.php';

require 'auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user info
$stmt = $pdo->prepare("SELECT email, password_hash FROM user_logins WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$errors = [];
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate old password
    if (!password_verify($old_password, $user['password_hash'])) {
        $errors['old_password'] = "Old password is incorrect.";
    }

    // Validate email
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } elseif ($new_email !== $user['email']) {
        // Check if email is already in use
        $stmt = $pdo->prepare("SELECT user_id FROM user_logins WHERE email = ?");
        $stmt->execute([$new_email]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "Email is already in use.";
        }
    }

    // Validate new password if provided
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors['password'] = "Password must be at least 6 characters.";
        }
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "Passwords do not match.";
        }
    }

    if (empty($errors)) {
        // Update email if changed
        if ($new_email !== $user['email']) {
            $stmt = $pdo->prepare("UPDATE user_logins SET email = ? WHERE user_id = ?");
            $stmt->execute([$new_email, $user_id]);
            $_SESSION['email'] = $new_email;
            $success_message = "Email updated successfully!";
        }

        // Update password if provided
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE user_logins SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$password_hash, $user_id]);
            $success_message = "Password updated successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div id='content'>
<div class="container mt-5">

    <div class="mb-3">
            <a href="my_profile.php">Back to Profile</a>
    </div>
    <h2>Update Account</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">New Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>" required>
            <small class="text-danger"><?php echo $errors['email'] ?? ''; ?></small>
        </div>

        <div class="mb-3">
            <label for="old_password" class="form-label">Old Password</label>
            <div class="input-group">
            <input type="password" class="form-control" id="old_password" name="old_password" required>
                <button type="button" class="btn btn-outline-secondary" id="toggleOld">
                    <i class="bi bi-eye" id="toggleIconPassword"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['old_password'] ?? ''; ?></small>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label">New Password (optional)</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password">
                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                    <i class="bi bi-eye" id="toggleIconPassword"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['password'] ?? ''; ?></small>
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                    <i class="bi bi-eye" id="toggleIconConfirmPassword"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['confirm_password'] ?? ''; ?></small>
        </div>

        <button type="submit" class="btn btn-primary">Update Account</button>
    </form>
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

    document.getElementById('toggleOld').addEventListener('click', function () {
        const confirmPasswordField = document.getElementById('old_password');
        const toggleIcon = document.getElementById('toggleOld');
        const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
        confirmPasswordField.type = type;
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>
