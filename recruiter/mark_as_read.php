<?php
require_once 'db.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = $_POST['id'];

    // Update the notification to mark it as read
    $query = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :id");
    $query->execute(['id' => $notification_id]);
}
?>