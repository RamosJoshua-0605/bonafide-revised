<?php
require 'db.php';

$errors = [];
$success_message = null;
$reset_token = $_GET['token'] ?? '';

if (!$reset_token) {
    die("Invalid request.");
}

// Check if token is valid
$stmt = $pdo->prepare("SELECT user_id, reset_token_expiration FROM user_logins WHERE reset_token = ?");
$stmt->execute([$reset_token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_token_expiration']) < time()) {
    die("Reset link expired or invalid.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Update password and clear reset token
        $update_stmt = $pdo->prepare("UPDATE user_logins SET password_hash = ?, reset_token = NULL, reset_token_expiration = NULL WHERE reset_token = ?");
        $update_stmt->execute([$password_hash, $reset_token]);

        $success_message = "Password reset successful! You can now <a href='index.php'>log in</a>.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Reset Password</h2>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required>
                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['password'] ?? ''; ?></small>
        </div>
        
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <button type="button" class="btn btn-outline-secondary" id="toggleConfirm">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['confirm_password'] ?? ''; ?></small>
        </div>
        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>

    <br>
    <div class="mb-3">
            <a href="index.php">Back to Login</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });

    document.getElementById('toggleConfirm').addEventListener('click', function () {
        const passwordField = document.getElementById('confirm_password');
        const toggleIcon = document.getElementById('toggleIcon');
        const type = passwordField.type === 'confirm_password' ? 'text' : 'confirm_password';
        passwordField.type = type;
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
</script>
</div>
</body>
</html>
