<?php
require 'db.php'; // Include your database configuration file
include 'header.php';
include 'auth.php';

// Fetch user details
if (isset($_GET['login_id'])) {
    $login_id = $_GET['login_id'];
    $stmt = $pdo->prepare("SELECT * FROM user_logins WHERE login_id = :login_id");
    $stmt->execute(['login_id' => $login_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update user details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $status = $_POST['status'];

    // Check if email is unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_logins WHERE email = :email AND login_id != :login_id");
    $stmt->execute(['email' => $email, 'login_id' => $login_id]);
    $emailExists = $stmt->fetchColumn();

    if ($emailExists) {
        $error = "Email already exists.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Fetch login_id using a getter
        $stmt = $pdo->prepare("SELECT login_id FROM user_logins WHERE login_id = :login_id");
        $stmt->execute(['login_id' => $login_id]);
        $loginId = $stmt->fetchColumn();

        // Update user details
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE user_logins SET email = :email, password_hash = :password, status = :status WHERE login_id = :login_id");
        $stmt->execute([
            'email' => $email,
            'password' => $hashedPassword,
            'status' => $status,
            'login_id' => $login_id
        ]);
        $success = "User updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <script>
        function togglePasswordVisibility(fieldId, iconId) {
            var passwordField = document.getElementById(fieldId);
            var toggleIcon = document.getElementById(iconId);
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        <h1 class="mb-4">Edit User</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password', 'toggleIconPassword')">
                        <i class="bi bi-eye" id="toggleIconPassword"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('confirm_password', 'toggleIconConfirmPassword')">
                        <i class="bi bi-eye" id="toggleIconConfirmPassword"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select class="form-control" id="status" name="status">
                    <option value="active" <?php if ($user['status'] == 'active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if ($user['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                    <option value="banned" <?php if ($user['status'] == 'banned') echo 'selected'; ?>>Banned</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update User</button>
        </form>
    </div>
</body>
</html>