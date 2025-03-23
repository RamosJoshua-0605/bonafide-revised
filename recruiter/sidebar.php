<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">

<div class="d-flex flex-column bg-light position-fixed" id="sidebar" style="width: 200px; height: 100vh; transition: all 0.3s; top: 60px;">
    <div class="d-flex justify-content-center py-2">
        <button id="toggleSidebar" class="btn btn-sm" 
            style="position: absolute; left: 15px; top: 20px; width: 40px; height: 40px; font-size: 2.0rem;">≡</button>
    </div>
    <ul class="nav nav-pills flex-column mb-auto mt-5 px-2">
        <!-- Dashboard -->
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-dark py-3 border-bottom collapsed-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                <i class="bi bi-speedometer2 fs-4 icon"></i> <span class="menu-text ms-2">Dashboard</span>
            </a>
        </li>
        <!-- Job Postings -->
        <li>
            <a href="view_jobs.php" class="nav-link text-dark py-3 border-bottom collapsed-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="Job Postings">
                <i class="bi bi-briefcase fs-4 icon"></i> <span class="menu-text ms-2">Job Postings</span>
            </a>
        </li>
        <!-- Applications -->
        <li>
            <a href="applications.php" class="nav-link text-dark py-3 border-bottom collapsed-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="Applications">
                <i class="bi bi-file-earmark-text fs-4 icon"></i> <span class="menu-text ms-2">Applications</span>
            </a>
        </li>
        <!-- User Management -->
        <li>
            <a href="user_management.php" class="nav-link text-dark py-3 border-bottom collapsed-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="User Management">
                <i class="bi bi-person-badge fs-4 icon"></i> <span class="menu-text ms-2">User Management</span>
            </a>
        </li>
        <!-- Candidate Referrals -->
        <li>
            <a href="referrals.php" class="nav-link text-dark py-3 border-bottom collapsed-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="Candidate Referrals">
                <i class="bi bi-people fs-4 icon"></i> <span class="menu-text ms-2">Candidate Referrals</span>
            </a>
        </li>
        <!-- Reports -->
        <li>
            <a href="reports.php" class="nav-link text-dark py-3 collapsed-tooltip" data-bs-toggle="tooltip" data-bs-placement="right" title="Reports">
                <i class="bi bi-bar-chart fs-4 icon"></i> <span class="menu-text ms-2">Reports</span>
            </a>
        </li>
    </ul>
</div>

<script>
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const menuTexts = document.querySelectorAll('.menu-text');
    const collapsedTooltips = document.querySelectorAll('.collapsed-tooltip');

    // Initialize Bootstrap tooltips for icons
    const tooltips = Array.from(collapsedTooltips).map(el => new bootstrap.Tooltip(el));

    toggleSidebar.addEventListener('click', function () {
        const content = document.getElementById('content'); // Added content reference

        if (sidebar.style.width === '70px') {
            sidebar.style.width = '200px';
            menuTexts.forEach(text => text.style.display = 'inline');
            sidebar.classList.remove('collapsed');
            tooltips.forEach(tooltip => tooltip.disable());
            content.style.marginLeft = '200px'; // Added this line to adjust content
        } else {
            sidebar.style.width = '70px';
            menuTexts.forEach(text => text.style.display = 'none');
            sidebar.classList.add('collapsed');
            tooltips.forEach(tooltip => tooltip.enable());
            content.style.marginLeft = '70px'; // Added this line to adjust content
        }
    });

    // Collapse sidebar automatically on smaller screens
    function handleResize() {
        if (window.innerWidth < 768) {
            sidebar.style.width = '70px';
            menuTexts.forEach(text => text.style.display = 'none');
            sidebar.classList.add('collapsed');
            tooltips.forEach(tooltip => tooltip.enable());
        } else {
            sidebar.style.width = '200px';
            menuTexts.forEach(text => text.style.display = 'inline');
            sidebar.classList.remove('collapsed');
            tooltips.forEach(tooltip => tooltip.disable());
        }
    }

    window.addEventListener('resize', handleResize);
    window.addEventListener('DOMContentLoaded', handleResize);
</script>

<style>
    #sidebar {
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        z-index: 1000;
    }

    #sidebar ul li a {
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    #sidebar.collapsed ul li a {
        justify-content: center; /* Center icons and content when collapsed */
    }

    #sidebar ul li a .icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .menu-text {
        display: inline;
        transition: opacity 0.3s ease;
    }

    #sidebar.collapsed .menu-text {
        display: none;
    }

    #sidebar ul li a:hover {
        background-color: #f8f9fa;
    }

    .collapsed-tooltip {
        display: flex;
        align-items: center;
    }

    /* Improved collapse button styling */
    .btn#toggleSidebar {
        z-index: 999;
        font-size: 1.2rem;
        width: 40px;
        height: 40px;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #sidebar ul li a .fs-4 {
        font-size: 1.5rem !important;
    }

    /* Sidebar spacing adjustments */
    .d-flex.justify-content-center.py-2 {
        margin-top: 10px;
    }

    body {
        margin: 0;
        padding: 0;
    }

    #content {
        margin-left: 200px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }

    #sidebar.collapsed + #content {
        margin-left: 70px;
    }

    @media (max-width: 768px) {
        #sidebar {
            width: 70px;
        }

        .menu-text {
            display: none;
        }

        #content {
            margin-left: 70px;
        }
    }
</style>
