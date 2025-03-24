<?php
require_once 'db.php'; // Include the database connection

$login_id = $_SESSION['login_id']; // Get the logged-in user's ID

// Fetch notifications for the logged-in user
$query = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC");
$query->execute(['user_id' => $login_id]);
$notifications = $query->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unread_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonafide Placement Trainology Placement Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<header class="bg-primary text-white py-3 fixed-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0">Bonafide Placement Trainology Placement Services</h1>
        <div class="d-flex align-items-center">
            <!-- Notification Bell -->
            <div class="dropdown me-3 position-relative">
                <button class="btn btn-outline-light position-relative" type="button" id="bellDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unread_count ?>
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bellDropdown">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <li>
                                <a class="dropdown-item <?= $notification['is_read'] ? '' : 'fw-bold' ?>" href="<?= htmlspecialchars($notification['link']) ?>" data-id="<?= $notification['notification_id'] ?>">
                                    <strong><?= htmlspecialchars($notification['title']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($notification['subject']) ?></small>
                                </a>
                                <div class="dropdown-divider"></div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="#">No new notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Logout Button -->
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</header>

<style>
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh; /* Ensures full height */
        padding-top: 60px; /* Prevents overlap with header */
    }

    #content {
        margin-left: 200px;
        padding: 20px;
        transition: margin-left 0.3s ease;
        margin-top: 20px; /* Prevents overlap with header */
        padding-bottom: 60px; /* Adds bottom spacing */
    }

    #sidebar.collapsed + #content {
        margin-left: 70px;
    }

    @media (max-width: 768px) {
        #content {
            margin-left: 70px;
            margin-top: 20px;
            padding-bottom: 60px; /* Maintain bottom spacing on small screens */
        }
    }
</style>

<script>
$(document).ready(function() {
    $('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        var notificationId = $(this).data('id');
        var link = $(this).attr('href');

        $.ajax({
            url: 'mark_as_read.php',
            method: 'POST',
            data: { id: notificationId },
            success: function(response) {
                window.location.href = link;
            },
            error: function(xhr, status, error) {
                console.error('Error marking notification as read:', error);
            }
        });
    });
});
</script>
</body>
</html>