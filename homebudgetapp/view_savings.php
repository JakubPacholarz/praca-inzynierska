<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_code'])) {
    $viewCode = trim($_POST['view_code']);

    $query = $db->prepare("SELECT * FROM users WHERE share_code = ?");
    $query->execute([$viewCode]);
    $sharedUser = $query->fetch(PDO::FETCH_ASSOC);

    if ($sharedUser) {
        if (!isset($_SESSION['shared_users'])) {
            $_SESSION['shared_users'] = [];
        }
        $_SESSION['shared_users'][$sharedUser['id']] = $sharedUser;
    }
}

$sharedUsers = $_SESSION['shared_users'] ?? [];
$selectedUserId = $_GET['user_id'] ?? null;
$selectedUser = $selectedUserId ? $sharedUsers[$selectedUserId] : null;

if ($selectedUser) {
    $sharedUserId = $selectedUser['id'];

    $query = $db->prepare("SELECT * FROM investments WHERE user_id = ?");
    $query->execute([$sharedUserId]);
    $savings = $query->fetchAll(PDO::FETCH_ASSOC);

    $query = $db->prepare("SELECT * FROM payments WHERE user_id = ?");
    $query->execute([$sharedUserId]);
    $payments = $query->fetchAll(PDO::FETCH_ASSOC);

    
    $query = $db->prepare("SELECT * FROM regular_payments WHERE user_id = ?");
    $query->execute([$sharedUserId]);
    $regularPayments = $query->fetchAll(PDO::FETCH_ASSOC);

    $query = $db->prepare("SELECT * FROM income WHERE user_id = ? AND type = 'regular'");
    $query->execute([$sharedUserId]);
    $regularIncome = $query->fetchAll(PDO::FETCH_ASSOC);
    $query = $db->prepare("SELECT MONTH(date) as month, YEAR(date) as year, SUM(amount) as total FROM payments WHERE user_id = ? GROUP BY YEAR(date), MONTH(date)");
    $query->execute([$sharedUserId]);
    $monthlyPaymentsSummary = $query->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Savings and Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Home Budget App</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="summary.php">Summary</a></li>
                <li class="nav-item"><a class="nav-link" href="savings.php">Savings</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="view_savings.php">View savings</a></li>
            </ul>
            <span class="navbar-text">
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="profile.php"><?= htmlspecialchars($_SESSION['username']) ?></a> | <a href="logout.php" class="text-light">Logout</a>
                
                <?php endif; ?>
            </span>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1 class="text-center mb-4">View Savings and Payments</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="view_savings.php" method="POST">
                <div class="mb-3">
                    <label for="view_code" class="form-label">Enter Share Code:</label>
                    <input type="text" class="form-control" id="view_code" name="view_code" required>
                </div>
                <button type="submit" class="btn btn-primary">View Savings and Payments</button>
            </form>
        </div>
    </div>

    <!-- Display list of user codes -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">User Codes</h2>
            <ul class="list-group">
                <?php foreach ($sharedUsers as $user): ?>
                    <li class="list-group-item">
                        <a href="view_savings.php?user_id=<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Display savings and payments if a user is selected -->
    <?php if ($selectedUser): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Savings for <?= htmlspecialchars($selectedUser['username']) ?></h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Investment Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savings as $saving): ?>
                            <tr>
                                <td><?= htmlspecialchars($saving['type']) ?></td>
                                <td>$<?= number_format($saving['amount'], 2) ?></td>
                                <td><?= isset($saving['date']) ? htmlspecialchars($saving['date']) : 'N/A' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Regular Payments for <?= htmlspecialchars($selectedUser['username']) ?></h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regularPayments as $payment): ?>
                            <tr>
                                <td>$<?= number_format($payment['amount'], 2) ?></td>
                                <td><?= isset($payment['date']) ? htmlspecialchars($payment['date']) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($payment['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Regular Income for <?= htmlspecialchars($selectedUser['username']) ?></h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regularIncome as $income): ?>
                            <tr>
                                <td>$<?= number_format($income['amount'], 2) ?></td>
                                <td><?= isset($income['date']) ? htmlspecialchars($income['date']) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($income['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Payments for <?= htmlspecialchars($selectedUser['username']) ?></h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>$<?= number_format($payment['amount'], 2) ?></td>
                                <td><?= isset($payment['date']) ? htmlspecialchars($payment['date']) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($payment['description']) ?></td>
                                <td><?= htmlspecialchars($payment['category']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Monthly Payments Summary for <?= htmlspecialchars($selectedUser['username']) ?></h2>
                <button class="btn btn-primary mb-3" onclick="downloadTable('monthlyPaymentsSummaryTable-<?= $selectedUserId ?>', 'monthly_payments_summary_<?= $selectedUserId ?>.csv')">Download Summary</button>
                <table class="table table-bordered" id="monthlyPaymentsSummaryTable-<?= $selectedUserId ?>">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Total Payments (USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyPaymentsSummary as $summary): ?>
                            <tr>
                                <td><?= date("F", mktime(0, 0, 0, $summary['month'], 10)) ?></td>
                                <td><?= htmlspecialchars($summary['year']) ?></td>
                                <td>$<?= number_format($summary['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function downloadTable(tableId, filename) {
        var csv = [];
        var rows = document.querySelectorAll(`#${tableId} tr`);
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (var j = 0; j < cols.length; j++) 
                row.push(cols[j].innerText);
            
            csv.push(row.join(","));        
        }
        var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        var downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>