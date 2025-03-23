<?php
require 'db.php';
include 'header.php';
include 'sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = null;

// Fetch existing work experiences
$stmt = $pdo->prepare("SELECT * FROM user_work_experience WHERE user_id = ?");
$stmt->execute([$user_id]);
$work_experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Handle updates
        if (!empty($_POST['experience_id'])) {
            $stmt = $pdo->prepare("
                UPDATE user_work_experience 
                SET company_name = ?, role = ?, years_worked = ? 
                WHERE experience_id = ? AND user_id = ?
            ");
            foreach ($_POST['experience_id'] as $index => $id) {
                $stmt->execute([
                    $_POST['company_name'][$index],
                    $_POST['role'][$index],
                    $_POST['years_worked'][$index],
                    $id,
                    $user_id
                ]);
            }
        }

        // Handle new experiences
        if (!empty($_POST['new_company_name'])) {
            $stmt = $pdo->prepare("
                INSERT INTO user_work_experience (user_id, company_name, role, years_worked) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($_POST['new_company_name'] as $index => $name) {
                $stmt->execute([
                    $user_id,
                    $name,
                    $_POST['new_role'][$index],
                    $_POST['new_years_worked'][$index]
                ]);
            }
        }

        // Handle deletions
        if (!empty($_POST['deleted_experiences'])) {
            $ids = explode(',', $_POST['deleted_experiences']);
            $stmt = $pdo->prepare("DELETE FROM user_work_experience WHERE experience_id = ? AND user_id = ?");
            foreach ($ids as $id) {
                $stmt->execute([$id, $user_id]);
            }
        }

        $pdo->commit();
        $success_message = "Work experiences updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database Error: " . $e->getMessage());
        $errors[] = "Error updating work experiences.";
    }
}

// Fetch the latest work experiences after any updates
$stmt = $pdo->prepare("SELECT * FROM user_work_experience WHERE user_id = ?");
$stmt->execute([$user_id]);
$work_experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Work Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        let deletedExperiences = [];

        function confirmDelete(button, id) {
            if (confirm("Are you sure you want to delete this work experience?")) {
                deletedExperiences.push(id);
                button.closest('.experience-group').remove();
                document.getElementById('deleted_experiences').value = deletedExperiences.join(',');
            }
        }

        function addExperience() {
            const container = document.getElementById('experiencesContainer');
            const newGroup = document.createElement("div");
            newGroup.classList.add("experience-group", "border", "p-3", "mb-3");
            newGroup.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" class="form-control" name="new_company_name[]" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" name="new_role[]" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Years Worked</label>
                    <input type="number" class="form-control" name="new_years_worked[]" min="0" max="90" required>
                </div>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(newGroup);
        }
    </script>
</head>
<body>
<div id="content">
    <div class="container mt-5">

    <div class="mb-3">
            <a href="my_profile.php">Back to Profile</a>
    </div>
    
        <h2>Edit Work Experience</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" id="deleted_experiences" name="deleted_experiences" value="">

            <div id="experiencesContainer">
                <?php foreach ($work_experiences as $exp): ?>
                    <div class="experience-group border p-3 mb-3">
                        <input type="hidden" name="experience_id[]" value="<?= $exp['experience_id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name[]" value="<?= htmlspecialchars($exp['company_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" name="role[]" value="<?= htmlspecialchars($exp['role']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Years Worked</label>
                            <input type="number" class="form-control" name="years_worked[]" value="<?= htmlspecialchars($exp['years_worked']) ?>" min="0" max="90" required>
                        </div>

                        <button type="button" class="btn btn-danger" onclick="confirmDelete(this, <?= $exp['experience_id'] ?>)">Delete</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-success" onclick="addExperience()">Add Work Experience</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>

        <a href="my_profile.php" class="btn btn-secondary mt-3">Back to Profile</a>
    </div>
</div>
</body>
</html>
