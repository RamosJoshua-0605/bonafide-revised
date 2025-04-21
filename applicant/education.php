<?php
ob_start();
require 'db.php';
include 'header.php';
include 'sidebar.php';
require 'auth.php';

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$errors = [];
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $highest_educational_attainment = $_POST['highest_educational_attainment'] ?? null;
    $junior_high_school = $_POST['junior_high_school'] ?? ($_POST['als_high_school'] ?? null);
    $year_graduated_junior_highschool = $_POST['year_graduated_junior_highschool'] ?? ($_POST['year_graduated_als_high_school'] ?? null);
    $senior_high_school = $_POST['senior_high_school'] ?? null;
    $year_graduated_senior_highschool = $_POST['year_graduated_senior_highschool'] ?? null;
    $college = $_POST['college'] ?? null;
    $year_graduated_college = $_POST['year_graduated_college'] ?? null;
    $course_program = $_POST['course_program'] ?? null;
    $postgrad_masters = $_POST['postgrad_masters'] ?? null;
    $year_graduated_postgrad_masters = $_POST['year_graduated_postgrad_masters'] ?? null;
    $other_details = $_POST['other_details'] ?? null; // Define this variable to prevent the error.
    $diploma_file = $_FILES['diploma_file'] ?? null;
    $no_diploma = isset($_POST['no_diploma']) ? 1 : 0;

    $diploma_path = null;
    if (!$no_diploma && isset($diploma_file['tmp_name']) && is_uploaded_file($diploma_file['tmp_name'])) {
        // Check for upload errors
        if ($diploma_file['error'] !== UPLOAD_ERR_OK) {
            $errors['diploma'] = "File upload error: " . $diploma_file['error'];
        } else {
            // Set the upload directory
            $upload_dir = __DIR__ . '/uploads/diplomas/';
            $file_name = uniqid() . '_' . basename($diploma_file['name']);
            $diploma_path = $upload_dir . $file_name;

            // Ensure the upload directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Move the uploaded file to the target directory
            if (!move_uploaded_file($diploma_file['tmp_name'], $diploma_path)) {
                $errors['diploma'] = "Failed to upload diploma.";
            } else {
                // Save the relative path for database storage
                $diploma_path = 'uploads/diplomas/' . $file_name;
            }
        }
    }

    // Form validation
    if (empty($highest_educational_attainment)) {
        $errors['highest_educational_attainment'] = "Please select your highest educational attainment.";
    }

    if (in_array($highest_educational_attainment, ['ALS Graduate', 'High School Graduate (Old Curriculum)', 'Junior High School Graduate'])) {
        if (empty($junior_high_school) || empty($year_graduated_junior_highschool)) {
            $errors['junior_high'] = "School and graduation year details are required.";
        }
    }

    if ($highest_educational_attainment === 'Senior High School Graduate') {
        if (empty($senior_high_school) || empty($year_graduated_senior_highschool)) {
            $errors['senior_high'] = "Senior high school details are required.";
        }
    }

    if ($highest_educational_attainment === 'College Undergraduate') {
        if (empty($college) || empty($course_program)) {
            $errors['college_undergraduate'] = "College and course details are required.";
        }
    }

    if ($highest_educational_attainment === 'College Graduate') {
        if (empty($college) || empty($year_graduated_college) || empty($course_program)) {
            $errors['college_graduate'] = "College, course, and year graduated details are required.";
        }
    }

    if ($highest_educational_attainment === 'Masteral Degree') {
        if (empty($postgrad_masters) || empty($year_graduated_postgrad_masters)) {
            $errors['masteral'] = "Postgraduate school and year graduated details are required.";
        }
    }

    if ($highest_educational_attainment === 'Other') {
        if (empty($other_details)) {
            $errors['other_details'] = "Please provide details for your educational background.";
        }
    }

    // Validate year inputs
    function validateYear($year) {
        return is_numeric($year) && $year >= 1900 && $year <= 2099;
    }

    if (!empty($year_graduated_junior_highschool) && !validateYear($year_graduated_junior_highschool)) {
        $errors['junior_high_year'] = "Graduation year must be between 1900 and 2099.";
    }
    if (!empty($year_graduated_senior_highschool) && !validateYear($year_graduated_senior_highschool)) {
        $errors['senior_high_year'] = "Graduation year must be between 1900 and 2099.";
    }
    if (!empty($year_graduated_college) && !validateYear($year_graduated_college)) {
        $errors['college_year'] = "Graduation year must be between 1900 and 2099.";
    }
    if (!empty($year_graduated_postgrad_masters) && !validateYear($year_graduated_postgrad_masters)) {
        $errors['postgrad_year'] = "Graduation year must be between 1900 and 2099.";
    }

    // If no errors, save the data
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_education (
                    user_id, highest_educational_attainment, junior_high_school, year_graduated_junior_highschool,
                    senior_high_school, year_graduated_senior_highschool, college, year_graduated_college,
                    course_program, postgrad_masters, year_graduated_postgrad_masters, other_details,
                    diploma, no_diploma
                ) VALUES (
                    :user_id, :highest_educational_attainment, :junior_high_school, :year_graduated_junior_highschool,
                    :senior_high_school, :year_graduated_senior_highschool, :college, :year_graduated_college,
                    :course_program, :postgrad_masters, :year_graduated_postgrad_masters, :other_details,
                    :diploma, :no_diploma
                )
            ");

            $stmt->execute([
                ':user_id' => $user_id,
                ':highest_educational_attainment' => $highest_educational_attainment,
                ':junior_high_school' => $junior_high_school,
                ':year_graduated_junior_highschool' => $year_graduated_junior_highschool,
                ':senior_high_school' => $senior_high_school,
                ':year_graduated_senior_highschool' => $year_graduated_senior_highschool,
                ':college' => $college,
                ':year_graduated_college' => $year_graduated_college,
                ':course_program' => $course_program,
                ':postgrad_masters' => $postgrad_masters,
                ':year_graduated_postgrad_masters' => $year_graduated_postgrad_masters,
                ':other_details' => $other_details,
                ':diploma' => $diploma_path,
                ':no_diploma' => $no_diploma,
            ]);

            $success_message = "Educational information saved successfully!";
            header('Location: certification.php');
            exit;
        } catch (Exception $e) {
            $errors['database'] = "An error occurred while saving your education details: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
<div id='content'>
<div class="container mt-5">
    <h2>Educational Information</h2>
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <form method="POST" id="educationForm" enctype="multipart/form-data">
        <!-- Highest Educational Attainment -->
        <div class="mb-3">
            <label for="highest_educational_attainment" class="form-label">Highest Educational Attainment</label>
            <select class="form-select" id="highest_educational_attainment" name="highest_educational_attainment" required>
                <option value="" disabled selected>Select</option>
                <option value="ALS Graduate">ALS Graduate</option>
                <option value="High School Graduate (Old Curriculum)">High School Graduate (Old Curriculum)</option>
                <option value="Junior High School Graduate">Junior High School Graduate</option>
                <option value="Senior High School Graduate">Senior High School Graduate</option>
                <option value="College Undergraduate">College Undergraduate</option>
                <option value="College Graduate">College Graduate</option>
                <option value="Masteral Degree">Masteral Degree</option>
                <option value="Other">Other (Homeschooled, Not Formally Schooled, etc.)</option>
            </select>
            <small class="text-danger" id="highest_attainment_error"></small>
        </div>

        <!-- Dynamic Fields -->
        <div id="dynamicFields">
            <!-- Junior High School or Equivalent -->
            <div id="juniorHighFields" class="d-none">
                <label id="juniorHighLabel" class="form-label">Junior High School Details</label>
                <input type="text" class="form-control mb-2" name="junior_high_school" placeholder="School Attended">
                <input type="number" class="form-control" name="year_graduated_junior_highschool" placeholder="Year Graduated" min="1900" max="2099" step="1">
                <small class="text-danger" id="junior_high_error"></small>
            </div>

            <!-- Senior High School -->
            <div id="seniorHighFields" class="d-none">
                <label class="form-label">Senior High School Details</label>
                <input type="text" class="form-control mb-2" name="senior_high_school" placeholder="Senior High School Attended">
                <input type="number" class="form-control" name="year_graduated_senior_highschool" placeholder="Year Graduated" min="1900" max="2099" step="1">
                <small class="text-danger" id="senior_high_error"></small>
            </div>

            <!-- College -->
            <div id="collegeFields" class="d-none">
                <label class="form-label">College Details</label>
                <input type="text" class="form-control mb-2" name="college" placeholder="College Attended">
                <input type="number" class="form-control mb-2 d-none" id="year_graduated_college" name="year_graduated_college" placeholder="Year Graduated" min="1900" max="2099" step="1">
                <input type="text" class="form-control" name="course_program" placeholder="Course / Program">
                <small class="text-danger" id="college_error"></small>
            </div>

            <!-- Postgraduate -->
            <div id="postgradFields" class="d-none">
                <label class="form-label">Postgraduate Details</label>
                <input type="text" class="form-control mb-2" name="postgrad_masters" placeholder="Postgraduate School Attended">
                <input type="number" class="form-control" name="year_graduated_postgrad_masters" placeholder="Year Graduated" min="1900" max="2099" step="1">
                <small class="text-danger" id="postgrad_error"></small>
            </div>

            <!-- Other -->
            <div id="otherFields" class="d-none">
                <label class="form-label">Other Details</label>
                <textarea class="form-control" name="other_details" rows="3" placeholder="Specify if homeschooled or not formally schooled"></textarea>
                <small class="text-danger" id="other_error"></small>
            </div>
        </div>

        <!-- Upload Diploma -->
        <div class="mb-3">
            <label for="diploma_file" class="form-label">Upload Diploma (Optional)</label>
            <input type="file" class="form-control" id="diploma_file" name="diploma_file" accept=".jpg,.jpeg,.png,.pdf">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="no_diploma" name="no_diploma" value="1">
                <label class="form-check-label" for="no_diploma">
                    I do not have a diploma
                </label>
            </div>
            <small class="text-danger" id="diploma_error"></small>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary mt-3">Save</button>
    </form>
</div>
    </div>

<script>
$(document).ready(function () {
    const form = $('#educationForm');
    const juniorHighLabel = $('#juniorHighLabel');

    // Show or hide fields based on selected attainment and update labels dynamically
    $('#highest_educational_attainment').change(function () {
        const value = $(this).val();

        // Reset visibility and clear values
        $('#juniorHighFields, #seniorHighFields, #collegeFields, #postgradFields, #otherFields').addClass('d-none');
        $('input, textarea').not('[type="hidden"]').val('');

        // Update label dynamically for specific educational attainments
        if (value === 'ALS Graduate') {
            juniorHighLabel.text('ALS School Details');
            $('#juniorHighFields').removeClass('d-none');
        } else if (value === 'High School Graduate (Old Curriculum)') {
            juniorHighLabel.text('High School Details');
            $('#juniorHighFields').removeClass('d-none');
        } else if (value === 'Junior High School Graduate') {
            juniorHighLabel.text('Junior High School Details');
            $('#juniorHighFields').removeClass('d-none');
        } else if (value === 'Senior High School Graduate') {
            $('#juniorHighFields, #seniorHighFields').removeClass('d-none');
        } else if (value === 'College Undergraduate') {
            $('#collegeFields').removeClass('d-none');
        } else if (value === 'College Graduate') {
            $('#collegeFields').removeClass('d-none');
            $('#year_graduated_college').removeClass('d-none');
        } else if (value === 'Masteral Degree') {
            $('#juniorHighFields, #seniorHighFields, #collegeFields, #postgradFields').removeClass('d-none');
            $('#year_graduated_college').removeClass('d-none');
        } else if (value === 'Other') {
            $('#otherFields').removeClass('d-none');
        }
    });

    // Validate form on submit
    form.on('submit', function (e) {
        let isValid = true;
        const attainment = $('#highest_educational_attainment').val();

        // Clear previous errors
        $('.text-danger').text('');

        // Function to validate year
        function validateYear(yearInput, errorField, fieldName) {
            const year = $(yearInput).val();
            if (!year || year < 1900 || year > 2099) {
                $(errorField).text(`${fieldName} must be a valid year between 1900 and 2099.`);
                isValid = false;
            }
        }

        // Validate highest attainment selection
        if (!attainment) {
            $('#highest_attainment_error').text('Please select your highest educational attainment.');
            isValid = false;
        }

        // Validate specific fields based on attainment
        if (['ALS Graduate', 'High School Graduate (Old Curriculum)', 'Junior High School Graduate'].includes(attainment)) {
            if (!$('input[name="junior_high_school"]').val()) {
                $('#junior_high_error').text('School name is required.');
                isValid = false;
            }
            validateYear('input[name="year_graduated_junior_highschool"]', '#junior_high_error', 'Year Graduated');
        }

        if (attainment === 'Senior High School Graduate') {
            if (!$('input[name="senior_high_school"]').val()) {
                $('#senior_high_error').text('Senior high school name is required.');
                isValid = false;
            }
            validateYear('input[name="year_graduated_senior_highschool"]', '#senior_high_error', 'Year Graduated');
        }

        // Validate diploma upload or checkbox
        const diplomaFile = $('#diploma_file').val();
        const noDiplomaChecked = $('#no_diploma').is(':checked');
        if (!diplomaFile && !noDiplomaChecked) {
            $('#diploma_error').text('Please upload your diploma or check the "I do not have a diploma" box.');
            isValid = false;
        }

        if (!isValid) e.preventDefault();
    });
});
</script>
</body>
</html>
