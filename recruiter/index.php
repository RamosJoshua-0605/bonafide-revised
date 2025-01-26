<?php
require 'db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email address.";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }

    if (empty($errors)) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM user_logins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if the account is inactive
            if ($user['status'] === 'Inactive') {
                $errors['login'] = "Please contact an admin to activate your account.";
                exit; // Ensure no further execution
            }

            // Check if the user role is 'Applicant'
            if ($user['role'] == 'Applicant') {
                $errors['login'] = "Applicants cannot login from this page.";
            } else {
                // Update the last login timestamp
                $update_stmt = $pdo->prepare("UPDATE user_logins SET last_login = NOW() WHERE login_id = ?");
                $update_stmt->execute([$user['login_id']]);

                // Set session variables
                $_SESSION['login_id'] = $user['login_id'];
                
                header("Location: dashboard.php"); // Redirect to dashboard
                exit;
            } 
        } else {
            $errors['login'] = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Login</h2>
    <?php if (!empty($errors['login'])): ?>
        <div class="alert alert-danger"><?php echo $errors['login']; ?></div>
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
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
            <small class="text-danger"><?php echo $errors['password'] ?? ''; ?></small>
        </div>
        <div class="mb-3">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Login</button>
        </div>
    </form>
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
</script>
</body>
</html>
