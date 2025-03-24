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

// Fetch certifications
$stmt = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Handle updates
        if (!empty($_POST['certification_id'])) {
            $stmt = $pdo->prepare("UPDATE user_certifications SET certification_name = ?, certification_institute = ?, year_taken_certification = ? WHERE certification_id = ? AND user_id = ?");
            foreach ($_POST['certification_id'] as $index => $id) {
                $stmt->execute([
                    $_POST['certification_name'][$index],
                    $_POST['certification_institute'][$index],
                    $_POST['year_taken_certification'][$index],
                    $id,
                    $user_id
                ]);
            }
        }

        // Handle new certifications
        if (!empty($_POST['new_certification_name'])) {
            $stmt = $pdo->prepare("INSERT INTO user_certifications (user_id, certification_name, certification_institute, year_taken_certification) VALUES (?, ?, ?, ?)");
            foreach ($_POST['new_certification_name'] as $index => $name) {
                $stmt->execute([
                    $user_id,
                    $name,
                    $_POST['new_certification_institute'][$index],
                    $_POST['new_year_taken_certification'][$index]
                ]);
            }
        }

        // Handle deletions
        if (!empty($_POST['deleted_certifications'])) {
            $ids = explode(',', $_POST['deleted_certifications']);
            $stmt = $pdo->prepare("DELETE FROM user_certifications WHERE certification_id = ? AND user_id = ?");
            foreach ($ids as $id) {
                $stmt->execute([$id, $user_id]);
            }
        }

        $pdo->commit();
        $success_message = "Certifications updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database Error: " . $e->getMessage());
        $errors[] = "Error updating certifications.";
    }
}

// Fetch updated certifications after any changes
$stmt = $pdo->prepare("SELECT * FROM user_certifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Certifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        let deletedCertifications = [];

        function confirmDelete(button, id) {
            if (confirm("Are you sure you want to delete this certification?")) {
                deletedCertifications.push(id);
                button.closest('.certification-group').remove();
                document.getElementById('deleted_certifications').value = deletedCertifications.join(',');
            }
        }

        function addCertification() {
            const container = document.getElementById('certificationsContainer');
            const newGroup = document.createElement("div");
            newGroup.classList.add("certification-group", "border", "p-3", "mb-3");
            newGroup.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Certification Name</label>
                    <input type="text" class="form-control" name="new_certification_name[]" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Certification Institute</label>
                    <input type="text" class="form-control" name="new_certification_institute[]" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Year Taken</label>
                    <input type="number" class="form-control" name="new_year_taken_certification[]" min="1900" max="2100" required>
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
        <h2>Edit Certifications</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" id="deleted_certifications" name="deleted_certifications" value="">

            <div id="certificationsContainer">
                <?php foreach ($certifications as $cert): ?>
                    <div class="certification-group border p-3 mb-3">
                        <input type="hidden" name="certification_id[]" value="<?= $cert['certification_id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Certification Name</label>
                            <input type="text" class="form-control" name="certification_name[]" value="<?= htmlspecialchars($cert['certification_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Certification Institute</label>
                            <input type="text" class="form-control" name="certification_institute[]" value="<?= htmlspecialchars($cert['certification_institute']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Year Taken</label>
                            <input type="number" class="form-control" name="year_taken_certification[]" value="<?= htmlspecialchars($cert['year_taken_certification']) ?>" min="1900" max="2100" required>
                        </div>

                        <button type="button" class="btn btn-danger" onclick="confirmDelete(this, <?= $cert['certification_id'] ?>)">Delete</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-success" onclick="addCertification()">Add Certification</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>

        <a href="my_profile.php" class="btn btn-secondary mt-3">Back to Profile</a>
    </div>
</div>
</body>
</html>
