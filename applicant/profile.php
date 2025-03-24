<?php
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';

// Check if user_id is set
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php"); // Redirect to profile page
    exit();
}

// Set log file path
$log_file = __DIR__ . '/logs/error_log.txt'; // Ensure this directory exists
if (!file_exists($log_file)) {
    mkdir(dirname($log_file), 0777, true);
    touch($log_file);
}
ini_set('log_errors', 1);
ini_set('error_log', $log_file);

// Enable error reporting and log all errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display, only log

$errors = [];
$success_message = null;

function generateReferralCode()
{
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST Data: " . print_r($_POST, true)); // Log POST data

    if (isset($_POST['step']) && $_POST['step'] == 2) {
        // Step 2: Insert data into the database
        $facebook_messenger_link = $_POST['facebook_messenger_link'] ?? '';
        $cellphone_number = $_POST['cellphone_number'] ?? '';
        $cellphone = '+63' . $cellphone_number;
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? null;
        $nickname = $_POST['nickname'] ?? null;
        $age = $_POST['age'] ?? null;
        $sex = $_POST['sex'] ?? null;
        $birthplace = $_POST['birthplace'] ?? '';
        $birthday = $_POST['birthday'] ?? null;
        $height_ft = $_POST['height_ft'] ?? 0;
        $height_in = $_POST['height_in'] ?? 0;
        $marital_status = $_POST['marital_status'] ?? null;
        $has_tattoo = $_POST['has_tattoo'] ?? null;
        $covid_vaccination_status = $_POST['covid_vaccination_status'] ?? null;
        $religion = $_POST['religion'] ?? null;

        $region = $_POST['region_text'] ?? '';
        $province = $_POST['province_text'] ?? '';
        $city = $_POST['city_text'] ?? '';
        $barangay = $_POST['barangay_text'] ?? '';
        $street = $_POST['street_text'] ?? '';
        $address_combined = "$street, $barangay, $city, $province, $region";

        // Combine height as feet.inches format
        $height_combined = $height_ft . '.' . $height_in;

        // Handle profile picture upload
        $id_picture_reference = null;
        if (!empty($_FILES['id_picture_reference']['name'])) {
            $target_dir = "uploads/profile_pictures/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
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

        // Get email from `user_logins`
        try {
            $stmt = $pdo->prepare("SELECT email FROM user_logins WHERE login_id = ?");
            $stmt->execute([$_SESSION['login_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $email_address = $user['email'];
            } else {
                $errors['database'] = "Failed to fetch user email.";
                error_log("Database Error: Failed to fetch email for login_id = " . $_SESSION['login_id']);
            }
        } catch (Exception $e) {
            error_log("Error fetching email: " . $e->getMessage());
        }

        if (empty($errors)) {
            $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email_address = ?");
            $check_stmt->execute([$email_address]);
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction(); // Start transaction

                // Generate referral code
                $referral_code = generateReferralCode();

                // Insert into `users` table
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        facebook_messenger_link, cellphone_number, first_name, last_name, middle_name, 
                        nickname, age, sex, address, birth_place, birthday, height_ft, 
                        marital_status, has_tattoo, covid_vaccination_status, email_address, referral_code, 
                        id_picture_reference, religion
                    ) VALUES (
                        :facebook_messenger_link, :cellphone, :first_name, :last_name, :middle_name, 
                        :nickname, :age, :sex, :address_combined, :birthplace, :birthday, :height_combined, 
                        :marital_status, :has_tattoo, :covid_vaccination_status, :email_address, :referral_code, 
                        :id_picture_reference, :religion
                    )
                ");
                $stmt->execute(compact(
                    'facebook_messenger_link', 'cellphone', 'first_name', 'last_name',
                    'middle_name', 'nickname', 'age', 'sex', 'address_combined', 'birthplace',
                    'birthday', 'height_combined', 'marital_status', 'has_tattoo',
                    'covid_vaccination_status', 'email_address', 'referral_code',
                    'id_picture_reference', 'religion'
                ));

                // Fetch `user_id` and update `user_logins`
                $user_id = $pdo->lastInsertId();
                $update_stmt = $pdo->prepare("UPDATE user_logins SET user_id = ? WHERE login_id = ?");
                $update_stmt->execute([$user_id, $_SESSION['login_id']]);

                $pdo->commit(); // Commit transaction

                $success_message = "Profile created successfully!";
                $_SESSION['user_id'] = $user_id;
                header("Location: education.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack(); // Rollback on failure
                error_log("Database Error: " . $e->getMessage());
                $errors['database'] = "An error occurred while saving your profile.";
            }
        }
    }
}

// Log session data
error_log("Session Data: " . print_r($_SESSION, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .is-invalid {
            border: 2px solid red;
        }
        .is-invalid + .invalid-feedback {
            display: block;
        }
        .invalid-feedback {
            display: none;
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div id='content'>
<div class="container mt-5">
    <h2>Profile Setup</h2>
    <form method="POST" action="" enctype="multipart/form-data" id="profileForm" novalidate>
        <!-- Step 1 -->
        <div class="step active" id="step-1">
            <input type="hidden" name="step" value="1">
            <div class="mb-3">
                <label for="id_picture_reference" class="form-label">Profile Picture</label>
                <input type="file" class="form-control" id="id_picture_reference" name="id_picture_reference" accept="image/*">
                <div class="invalid-feedback">Please upload a valid image file (JPG, JPEG, PNG).</div>
            </div>
            <div class="mb-3">
                <label for="facebook_messenger_link" class="form-label">Facebook Messenger Link</label>
                <input type="url" class="form-control" id="facebook_messenger_link" name="facebook_messenger_link" required>
                <div class="invalid-feedback">Please provide a valid Facebook Messenger link.</div>
            </div>
            <div class="mb-3">
                <label for="cellphone_number" class="form-label">Cellphone Number</label>
                <div class="input-group">
                    <span class="input-group-text">+63</span>
                    <input type="text" class="form-control" id="cellphone_number" name="cellphone_number" pattern="\d{10}" maxlength="10" required>
                    <div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>
                </div>
            </div>
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" pattern="[A-Za-z\s]+" required>
                <div class="invalid-feedback">First name should contain only letters and spaces.</div>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" pattern="[A-Za-z\s]+" required>
                <div class="invalid-feedback">Last name should contain only letters and spaces.</div>
            </div>
            <div class="mb-3">
                <label for="middle_name" class="form-label">Middle Name</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name" pattern="[A-Za-z\s]+">
                <div class="invalid-feedback">Middle name should contain only letters and spaces.</div>
            </div>
            <div class="mb-3">
                <label for="nickname" class="form-label">Nickname</label>
                <input type="text" class="form-control" id="nickname" name="nickname" pattern="[A-Za-z\s]+">
                <div class="invalid-feedback">Nickname should contain only letters and spaces.</div>
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="number" class="form-control" id="age" name="age" min="1" max="120" required>
                <div class="invalid-feedback">Please enter a valid age.</div>
            </div>
            <div class="mb-3">
                <label for="sex" class="form-label">Sex</label>
                <select class="form-select" id="sex" name="sex" required>
                    <option value="" disabled selected>Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <div class="invalid-feedback">Please select a valid option.</div>
            </div>
            <button type="button" class="btn btn-primary" id="next-button">Next</button>
        </div>

        <!-- Step 2 -->
        <div class="step" id="step-2">
            <input type="hidden" name="step" value="2">
            
            <div class="mb-3">
                <h5>Address</h3>
                <div class="mb-3" style="border: 1px solid black; padding: 20px;">
                    <div class="mb-3">
                        <label for="region" class="form-label">Region *</label>
                        <select name="region" class="form-control form-control-md" id="region" required></select>
                        <input type="hidden" class="form-control" name="region_text" id="region-text">
                    </div>

                    <div class="mb-3">
                        <label for="province" class="form-label">Province *</label>
                        <select name="province" class="form-control form-control-md" id="province" required></select>
                        <input type="hidden" class="form-control" name="province_text" id="province-text">
                    </div>

                    <div class="mb-3">
                        <label for="city" class="form-label">City / Municipality *</label>
                        <select name="city" class="form-control form-control-md" id="city" required></select>
                        <input type="hidden" class="form-control" name="city_text" id="city-text">
                    </div>

                    <div class="mb-3">
                        <label for="barangay" class="form-label">Barangay *</label>
                        <select name="barangay" class="form-control form-control-md" id="barangay" required></select>
                        <input type="hidden" class="form-control" name="barangay_text" id="barangay-text">
                    </div>

                    <div class="mb-3">
                        <label for="street-text" class="form-label">Street (Optional)</label>
                        <input type="text" class="form-control" id="street-text" name="street_text">
                    </div>

                    <div class="invalid-feedback">Please provide your address.</div>
                </div>
            </div>
            <div class="mb-3">
                <label for="birthplace" class="form-label">Birthplace</label>
                <input type="text" class="form-control" id="birthplace" name="birthplace" required>
                <div class="invalid-feedback">Please enter a valid birthplace.</div>
            </div>
            <div class="mb-3">
                <label for="birthday" class="form-label">Birthday</label>
                <input type="date" class="form-control" id="birthday" name="birthday" required>
                <div class="invalid-feedback">Please select a valid date.</div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="height_ft" class="form-label">Height (Feet)</label>
                    <input type="number" class="form-control" id="height_ft" name="height_ft" min="0" required>
                    <div class="invalid-feedback">Please enter height in feet.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="height_in" class="form-label">Height (Inches)</label>
                    <input type="number" class="form-control" id="height_in" name="height_in" min="0" max="11" required>
                    <div class="invalid-feedback">Please enter height in inches (0-11).</div>
                </div>
            </div>
            <div class="mb-3">
                <label for="marital_status" class="form-label">Marital Status</label>
                <select class="form-select" id="marital_status" name="marital_status" required>
                    <option value="" disabled selected>Select</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Divorced">Divorced</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                </select>
                <div class="invalid-feedback">Please select a valid option.</div>
            </div>
            <div class="mb-3">
                <label for="has_tattoo" class="form-label">Do you have any visible Tattoo/s?</label>
                <select class="form-select" id="has_tattoo" name="has_tattoo" required>
                    <option value="" disabled selected>Select</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                <div class="invalid-feedback">Please select a valid option.</div>
            </div>
            <div class="mb-3">
                <label for="covid_vaccination_status" class="form-label">COVID Vaccination Status</label>
                <select class="form-select" id="covid_vaccination_status" name="covid_vaccination_status" required>
                    <option value="" disabled selected>Select</option>
                    <option value="Vaccinated">Vaccinated</option>
                    <option value="Unvaccinated">Unvaccinated</option>
                </select>
                <div class="invalid-feedback">Please select a valid option.</div>
            </div>
            <div class="mb-3">
                <label for="religion" class="form-label">Religion</label>
                <input type="text" class="form-control" id="religion" name="religion" pattern="[A-Za-z\s]+">
                <div class="invalid-feedback">Religion should contain only letters and spaces.</div>
            </div>
            <button type="button" class="btn btn-secondary" id="back-button">Back</button>
            <button type="submit" class="btn btn-success">Submit</button>
        </div>

    </form>
</div>
    </div>

<script src="ph-address-selector.js"></script>

<script>
$(document).ready(function () {
    function validateInputs(selector) {
        let isValid = true;
        $(selector).each(function () {
            const $input = $(this);
            const $feedback = $input.next(".invalid-feedback");

            if (!$input[0].checkValidity()) {
                $input.addClass("is-invalid");
                $feedback.show();
                isValid = false;
            } else {
                $input.removeClass("is-invalid");
                $feedback.hide();
            }
        });
        return isValid;
    }

    $("#next-button").click(function () {
        if (validateInputs("#step-1 :input")) {
            $("#step-1").removeClass("active");
            $("#step-2").addClass("active");
        }
    });

    $("#back-button").click(function () {
        $("#step-2").removeClass("active");
        $("#step-1").addClass("active");
    });

    $("#profileForm").on("submit", function (e) {
        if (!validateInputs("#step-2 :input")) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
