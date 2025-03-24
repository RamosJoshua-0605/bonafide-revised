<?php
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';

require 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = null;

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facebook_messenger_link = $_POST['facebook_messenger_link'] ?? '';
    $cellphone_number = $_POST['cellphone_number'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? null;
    $nickname = $_POST['nickname'] ?? null;
    $age = $_POST['age'] ?? null;
    $sex = $_POST['sex'] ?? null;
    $birth_place = $_POST['birth_place'] ?? '';
    $birthday = $_POST['birthday'] ?? null;
    $height_ft = $_POST['height_ft'] ?? 0;
    $marital_status = $_POST['marital_status'] ?? null;
    $has_tattoo = $_POST['has_tattoo'] ?? null;
    $covid_vaccination_status = $_POST['covid_vaccination_status'] ?? null;
    $religion = $_POST['religion'] ?? null;
    $address = $_POST['address'] ?? '';

    // Handle profile picture upload
    $id_picture_reference = $user['id_picture_reference'];
    if (!empty($_FILES['id_picture_reference']['name'])) {
        $target_dir = "uploads/profile_pictures/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = basename($_FILES['id_picture_reference']['name']);
        $target_file = $target_dir . uniqid() . "_" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png"];

        if (!in_array($imageFileType, $allowed_types)) {
            $errors['id_picture_reference'] = "Only JPG, JPEG, and PNG files are allowed.";
        } elseif (!move_uploaded_file($_FILES['id_picture_reference']['tmp_name'], $target_file)) {
            $errors['id_picture_reference'] = "Failed to upload the profile picture.";
        } else {
            $id_picture_reference = $target_file;
        }
    }

    // Update user data if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    facebook_messenger_link = ?, cellphone_number = ?, first_name = ?, last_name = ?, 
                    middle_name = ?, nickname = ?, age = ?, sex = ?, address = ?, birth_place = ?, 
                    birthday = ?, height_ft = ?, marital_status = ?, has_tattoo = ?, 
                    covid_vaccination_status = ?, religion = ?, id_picture_reference = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $facebook_messenger_link, $cellphone_number, $first_name, $last_name, 
                $middle_name, $nickname, $age, $sex, $address, $birth_place, 
                $birthday, $height_ft, $marital_status, $has_tattoo, 
                $covid_vaccination_status, $religion, $id_picture_reference, $user_id
            ]);
            $success_message = "Profile updated successfully!";
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            $errors['database'] = "An error occurred while updating your profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    
<div id='content'>
<div class="container mt-5">
    <h2>Edit Profile</h2>
    
    <div class="mt-4">
        <h4>Edit Email and Password</h4>
        <a href="edit_account.php" class="btn btn-warning">Edit Account</a>
    </div>

    <div class="mt-4">
        <h4>Edit Certifications</h4>
        <a href="edit_certifications.php" class="btn btn-warning">Edit Certifications</a>
    </div>

    <div class="mt-4">
        <h4>Edit Work Experience</h4>
        <a href="edit_experience.php" class="btn btn-warning">Edit Work Experience</a>
    </div>

    <div class="mt-4">
        <h4>Edit Education</h4>
        <a href="education.php" class="btn btn-warning">Edit Education</a>
    </div>

    <br>
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="id_picture_reference" class="form-label">Profile Picture</label>
            <input type="file" class="form-control" name="id_picture_reference">
            <?php if ($user['id_picture_reference']): ?>
                <img src="<?= $user['id_picture_reference'] ?>" width="100" class="mt-2">
            <?php endif; ?>
        </div>
        
        <div class="mb-3">
            <label>Facebook Messenger Link</label>
            <input type="url" class="form-control" name="facebook_messenger_link" value="<?= htmlspecialchars($user['facebook_messenger_link']) ?>">
        </div>

        <div class="mb-3">
            <label>Cellphone Number</label>
            <input type="text" class="form-control" name="cellphone_number" value="<?= htmlspecialchars($user['cellphone_number']) ?>" required>
        </div>

        <div class="mb-3">
            <label>First Name</label>
            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Nickname</label>
            <input type="text" class="form-control" name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>">
        </div>

        <div class="mb-3">
            <label>Address</label>
            <textarea class="form-control" name="address"><?= htmlspecialchars($user['address']) ?></textarea>
        </div>

        <div class="mb-3">
            <label>Birth Place</label>
            <input type="text" class="form-control" name="birth_place" value="<?= htmlspecialchars($user['birth_place']) ?>">
        </div>

        <div class="mb-3">
            <label>Birthday</label>
            <input type="date" class="form-control" name="birthday" value="<?= $user['birthday'] ?>">
        </div>

        <div class="mb-3">
            <label>Height (feet)</label>
            <input type="number" class="form-control" name="height_ft" value="<?= htmlspecialchars($user['height_ft']) ?>">
        </div>

        <div class="mb-3">
            <label>Marital Status</label>
            <input type="text" class="form-control" name="marital_status" value="<?= htmlspecialchars($user['marital_status']) ?>">
        </div>

        <div class="mb-3">
            <label>Has Tattoo?</label>
            <select class="form-control" name="has_tattoo">
                <option value="Yes" <?= $user['has_tattoo'] == 'Yes' ? 'selected' : '' ?>>Yes</option>
                <option value="No" <?= $user['has_tattoo'] == 'No' ? 'selected' : '' ?>>No</option>
            </select>
        </div>

        <div class="mb-3">
            <label>COVID Vaccination Status</label>
            <select class="form-control" name="covid_vaccination_status">
                <option value="Vaccinated" <?= $user['covid_vaccination_status'] == 'Vaccinated' ? 'selected' : '' ?>>Vaccinated</option>
                <option value="Unvaccinated" <?= $user['covid_vaccination_status'] == 'Unvaccinated' ? 'selected' : '' ?>>Unvaccinated</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
            </div>
</body>
</html>
