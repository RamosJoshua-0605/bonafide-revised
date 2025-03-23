<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonafide Placement Trainology Placement Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<header class="bg-primary text-white py-3 fixed-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0">Bonafide Placement Trainology Placement Services</h1>
        <div class="d-flex align-items-center">
            <!-- Notification Bell -->
            <div class="dropdown me-3">
                <button class="btn btn-outline-light" type="button" id="bellDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="bellDropdown">
                    <li><a class="dropdown-item" href="#">No new notifications</a></li>
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
</body>
</html>