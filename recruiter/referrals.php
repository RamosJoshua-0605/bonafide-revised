<?php
require 'db.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php");
    exit();
}

$login_id = $_SESSION['login_id'];

// Fetch referrals with correct user details for referrer and referred users
$stmt = $pdo->prepare("
    SELECT 
        r.referral_id, 
        r.referrer_id, 
        ul_referred.user_id AS referred_user_id, 
        r.referral_date, 
        r.status, 
        rp.total_points,
        referrer.first_name AS referrer_first_name,
        referrer.last_name AS referrer_last_name,
        referrer.email_address AS referrer_email,
        referrer.cellphone_number AS referrer_phone,
        referred.first_name AS referred_first_name,
        referred.last_name AS referred_last_name,
        referred.email_address AS referred_email,
        referred.cellphone_number AS referred_phone
    FROM referrals r
    LEFT JOIN referral_points rp ON r.referrer_id = rp.user_id
    LEFT JOIN users referrer ON r.referrer_id = referrer.user_id
    LEFT JOIN user_logins ul_referred ON r.referred_user_id = ul_referred.login_id
    LEFT JOIN users referred ON ul_referred.user_id = referred.user_id
");
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all points
$pointsStmt = $pdo->prepare("SELECT user_id, total_points FROM referral_points");
$pointsStmt->execute();
$points = $pointsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch all users with detailed information
$userStmt = $pdo->prepare("
    SELECT 
        user_id, 
        CONCAT(first_name, ' ', last_name) AS full_name,
        facebook_messenger_link, email_address, cellphone_number
    FROM users
");
$userStmt->execute();
$users = [];
while ($row = $userStmt->fetch(PDO::FETCH_ASSOC)) {
    $users[$row['user_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Referrals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/d3@7.8.4/dist/d3.min.js"></script>
    <style>
        #connection-web {
            width: 100%;
            height: 600px;
            border: 1px solid #ccc;
        }
        circle {
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Referral Connections</h1>

    <div class="mb-4">
        <h3>All Referrals</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Referrer</th>
                    <th>Referred User</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Points</th>
                    <th>Referrer Contact</th>
                    <th>Referred Contact</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($referrals as $referral): ?>
                    <tr>
                        <td><?= htmlspecialchars($referral['referrer_first_name'] . ' ' . $referral['referrer_last_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($referral['referred_first_name'] . ' ' . $referral['referred_last_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($referral['referral_date']) ?></td>
                        <td><?= htmlspecialchars($referral['status']) ?></td>
                        <td><?= htmlspecialchars($referral['total_points'] ?? 0) ?></td>
                        <td>
                            <?= htmlspecialchars($referral['referrer_email'] ?? 'N/A') ?><br>
                            <?= htmlspecialchars($referral['referrer_phone'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($referral['referred_email'] ?? 'N/A') ?><br>
                            <?= htmlspecialchars($referral['referred_phone'] ?? 'N/A') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4">
        <h3>Connection Web</h3>
        <div id="connection-web"></div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Create referral data for D3 visualization
    const validNodeIds = new Set([
        <?php foreach ($users as $id => $user): ?>
        <?= $id ?>,
        <?php endforeach; ?>
    ]);

    const data = {
        nodes: [
            <?php foreach ($users as $id => $user): ?>
            {id: <?= $id ?>, name: "<?= htmlspecialchars($user['full_name']) ?>"},
            <?php endforeach; ?>
        ],
        links: [
            <?php foreach ($referrals as $referral): ?>
            <?php if (isset($users[$referral['referrer_id']]) && isset($users[$referral['referred_user_id']])): ?>
            {
                source: <?= $referral['referrer_id'] ?>,
                target: <?= $referral['referred_user_id'] ?>
            },
            <?php endif; ?>
            <?php endforeach; ?>
        ]
    };

    const width = document.getElementById("connection-web").clientWidth;
    const height = document.getElementById("connection-web").clientHeight;

    const svg = d3.select("#connection-web")
        .append("svg")
        .attr("width", width)
        .attr("height", height);

    const simulation = d3.forceSimulation(data.nodes)
        .force("link", d3.forceLink(data.links).id(d => d.id).distance(150))
        .force("charge", d3.forceManyBody().strength(-400))
        .force("center", d3.forceCenter(width / 2, height / 2));

    const link = svg.append("g")
        .selectAll("line")
        .data(data.links)
        .enter().append("line")
        .attr("stroke-width", 2)
        .attr("stroke", "#999");

    const node = svg.append("g")
        .selectAll("circle")
        .data(data.nodes)
        .enter().append("circle")
        .attr("r", 10)
        .attr("fill", "#007bff")
        .call(d3.drag()
            .on("start", (event, d) => {
                if (!event.active) simulation.alphaTarget(0.3).restart();
                d.fx = d.x;
                d.fy = d.y;
            })
            .on("drag", (event, d) => {
                d.fx = event.x;
                d.fy = event.y;
            })
            .on("end", (event, d) => {
                if (!event.active) simulation.alphaTarget(0);
                d.fx = null;
                d.fy = null;
            })
        );

    node.append("title")
        .text(d => d.name);

    const label = svg.append("g")
        .selectAll("text")
        .data(data.nodes)
        .enter().append("text")
        .attr("dy", -15)
        .attr("text-anchor", "middle")
        .text(d => d.name);

    simulation.on("tick", () => {
        link.attr("x1", d => d.source.x)
            .attr("y1", d => d.source.y)
            .attr("x2", d => d.target.x)
            .attr("y2", d => d.target.y);

        node.attr("cx", d => d.x)
            .attr("cy", d => d.y);

        label.attr("x", d => d.x)
            .attr("y", d => d.y);
    });
});
</script>
</body>
</html>
