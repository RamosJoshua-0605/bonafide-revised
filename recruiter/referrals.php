<?php
ob_start();
require 'db.php'; 
include 'header.php';
include 'sidebar.php';
require 'auth.php';

if (!isset($_SESSION['login_id'])) {
    header("Location: index.php");
    exit();
}

$login_id = $_SESSION['login_id'];

// Fetch referrals with user details
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
        referred.first_name AS referred_first_name,
        referred.last_name AS referred_last_name
    FROM referrals r
    LEFT JOIN referral_points rp ON r.referrer_id = rp.user_id
    LEFT JOIN users referrer ON r.referrer_id = referrer.user_id
    LEFT JOIN user_logins ul_referred ON r.referred_user_id = ul_referred.login_id
    LEFT JOIN users referred ON ul_referred.user_id = referred.user_id
");
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users
$userStmt = $pdo->prepare("SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name FROM users");
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
    <title>Referral Connections</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/d3@7.8.4/dist/d3.min.js"></script>
    <style>
        #connection-web {
            width: 100%;
            height: 600px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        circle {
            cursor: pointer;
            fill: #007bff;
            stroke: #fff;
            stroke-width: 2px;
        }
        text {
            font-size: 12px;
            font-family: Arial, sans-serif;
            fill: #333;
        }
    </style>
</head>
<body>
    <div id='content'>
<div class="container mt-5">
    <h1 class="mb-4">Referral Connections</h1>

    <!-- Referral Table -->
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
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Connection Web -->
    <div class="mb-4">
        <h3>Connection Web</h3>
        <div id="connection-web"></div>
    </div>
</div>
                </div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const data = {
        nodes: [
            <?php foreach ($users as $id => $user): ?>
            {id: <?= $id ?>, name: "<?= htmlspecialchars($user['full_name']) ?>"},
            <?php endforeach; ?>
        ],
        links: [
            <?php foreach ($referrals as $referral): ?>
            { source: <?= $referral['referrer_id'] ?>, target: <?= $referral['referred_user_id'] ?> },
            <?php endforeach; ?>
        ]
    };

    const width = document.getElementById("connection-web").clientWidth;
    const height = document.getElementById("connection-web").clientHeight;

    const svg = d3.select("#connection-web")
        .append("svg")
        .attr("width", width)
        .attr("height", height)
        .call(d3.zoom().on("zoom", (event) => {
            container.attr("transform", event.transform);
        }))
        .append("g");

    const container = svg.append("g");

    const simulation = d3.forceSimulation(data.nodes)
        .force("link", d3.forceLink(data.links).id(d => d.id).distance(120))
        .force("charge", d3.forceManyBody().strength(-500))
        .force("center", d3.forceCenter(width / 2, height / 2));

    const link = container.append("g")
        .selectAll("line")
        .data(data.links)
        .enter().append("line")
        .attr("stroke-width", 2)
        .attr("stroke", "#999");

    const node = container.append("g")
        .selectAll("circle")
        .data(data.nodes)
        .enter().append("circle")
        .attr("r", 12)
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

    node.append("title").text(d => d.name);

    const label = container.append("g")
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
