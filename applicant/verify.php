<?php
require 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT * FROM user_logins WHERE verification_token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();

        // Update the user's status to Active and clear the verification token
        $pdo->prepare("UPDATE user_logins SET status = 'Active', verification_token = NULL WHERE login_id = ?")
            ->execute([$user['login_id']]);

        // Check if the user was referred
        $referral_stmt = $pdo->prepare("
            SELECT r.referrer_id, r.referrer_user_role
            FROM referrals r
            WHERE r.referred_user_id = ? AND r.status = 'Pending'
        ");
        $referral_stmt->execute([$user['login_id']]);

        if ($referral_stmt->rowCount() > 0) {
            $referral = $referral_stmt->fetch();
            $referrer_id = $referral['referrer_id'];

            // Update the referral status to Successful
            $pdo->prepare("UPDATE referrals SET status = 'Successful' WHERE referred_user_id = ?")
                ->execute([$user['login_id']]);

            // Increment the referrer's points in referral_points
            $points_stmt = $pdo->prepare("
                INSERT INTO referral_points (user_id, total_points, last_updated)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE total_points = total_points + 1, last_updated = NOW()
            ");
            $points_stmt->execute([$referrer_id]);
        }

        // Set the login_id in the session
        $_SESSION['login_id'] = $user['login_id'];

        // Redirect to the profile setup page
        header("Location: /bonafide/applicant/profile.php");
        exit;
    } else {
        echo "Invalid verification link.";
    }
} else {
    echo "No token provided.";
}
?>
