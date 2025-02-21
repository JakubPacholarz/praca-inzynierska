<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$userId = $_SESSION['user_id'];
$query = $db->prepare("SELECT username, photo FROM users WHERE id = ?");
$query->execute([$userId]);
$user = $query->fetch(PDO::FETCH_ASSOC);
$username = $user['username'] ?? '';
$photo = $user['photo'] ?? '';

$query = $db->prepare("SELECT * FROM investments WHERE user_id = ?");
$query->execute([$userId]);
$investments = $query->fetchAll(PDO::FETCH_ASSOC);

$cryptoInvestments = [];
$stockInvestments = [];
foreach ($investments as $investment) {
    $typeParts = explode('-', $investment['type']);
    $category = strtolower($typeParts[0]);
    if ($category === 'crypto') {
        $cryptoInvestments[] = $investment;
    } elseif ($category === 'stock') {
        $stockInvestments[] = $investment;
    }
}

$query = $db->prepare("SELECT category, SUM(amount) as total FROM payments WHERE user_id = ? GROUP BY category");
$query->execute([$userId]);
$categorySpending = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT MONTH(date) as month, SUM(amount) as total FROM payments WHERE user_id = ? GROUP BY MONTH(date)");
$query->execute([$userId]);
$monthlySpending = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT MONTH(date) as month, SUM(amount) as total FROM income WHERE user_id = ? GROUP BY MONTH(date)");
$query->execute([$userId]);
$monthlyIncome = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT YEAR(date) as year, SUM(amount) as total_spent FROM payments WHERE user_id = ? GROUP BY YEAR(date)");
$query->execute([$userId]);
$yearlySpending = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT YEAR(date) as year, SUM(amount) as total_income FROM income WHERE user_id = ? GROUP BY YEAR(date)");
$query->execute([$userId]);
$yearlyIncome = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT YEAR(date) as year, SUM(amount) as total_invested FROM investments WHERE user_id = ? GROUP BY YEAR(date)");
$query->execute([$userId]);
$yearlyInvestments = $query->fetchAll(PDO::FETCH_ASSOC);


$query = $db->prepare("SELECT date, description, amount FROM payments WHERE user_id = ?");
$query->execute([$userId]);
$allPayments = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT date, description, amount FROM income WHERE user_id = ?");
$query->execute([$userId]);
$allIncome = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT date, type, amount, price_at_investment FROM investments WHERE user_id = ?");
$query->execute([$userId]);
$allInvestments = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .error {
            color: red;
        }
        .navbar-text img {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            margin-right: 10px;
        }
       
        .profile-photo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .dark-mode {
            background-color: #212529;
            color: #ffffff;
        }

        .dark-mode .card {
            background-color: #343a40;
            color: #ffffff;
        }

        .dark-mode .navbar {
            background-color: #1a1e21;
        }

        .dark-mode .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .dark-mode .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
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
                <?php if ($photo): ?>
                    <img src="<?= htmlspecialchars($photo) ?>" alt="Profile Photo" class="profile-photo">
                <?php endif; ?>
                <a href="profile.php"><?= htmlspecialchars($username) ?></a> | <a href="logout.php" class="text-light">Logout</a>
                
            </span>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1 class="text-center mb-4">Summary</h1>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6">
            <canvas id="categorySpendingChart"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="monthlySpendingChart"></canvas>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <canvas id="yearlySpendingIncomeChart"></canvas>
        </div>
    </div>

    <!-- Tables -->
    <div class="table-container">
        <h2 class="mt-5">Monthly Income and Payments</h2>
        <button class="btn btn-primary download-btn" onclick="downloadTable('incomeTable', 'income.csv')">Download Income</button>
        <table class="table table-bordered" id="incomeTable">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Income (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyIncome as $income): ?>
                <tr>
                    <td><?= date("F", mktime(0, 0, 0, $income['month'], 10)) ?></td>
                    <td><?= number_format($income['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="btn btn-primary download-btn" onclick="downloadTable('paymentsTable', 'payments.csv')">Download Payments</button>
        <table class="table table-bordered" id="paymentsTable">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Payments (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlySpending as $spending): ?>
                <tr>
                    <td><?= date("F", mktime(0, 0, 0, $spending['month'], 10)) ?></td>
                    <td><?= number_format($spending['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 class="mt-5">All Payments</h2>
        <button class="btn btn-primary download-btn" onclick="downloadTable('allPaymentsTable', 'all_payments.csv')">Download All Payments</button>
        <table class="table table-bordered" id="allPaymentsTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allPayments as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['date']) ?></td>
                    <td><?= htmlspecialchars($payment['description']) ?></td>
                    <td><?= number_format($payment['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 class="mt-5">All Income</h2>
        <button class="btn btn-primary download-btn" onclick="downloadTable('allIncomeTable', 'all_income.csv')">Download All Income</button>
        <table class="table table-bordered" id="allIncomeTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allIncome as $income): ?>
                <tr>
                    <td><?= htmlspecialchars($income['date']) ?></td>
                    <td><?= htmlspecialchars($income['description']) ?></td>
                    <td><?= number_format($income['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 class="mt-5">All Investments</h2>
        <button class="btn btn-primary download-btn" onclick="downloadTable('allInvestmentsTable', 'all_investments.csv')">Download All Investments</button>
        <table class="table table-bordered" id="allInvestmentsTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount (USD)</th>
                    <th>Price at Investment (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allInvestments as $investment): ?>
                <tr>
                    <td><?= htmlspecialchars($investment['date']) ?></td>
                    <td><?= htmlspecialchars($investment['type']) ?></td>
                    <td><?= number_format($investment['amount'], 2) ?></td>
                    <td><?= number_format($investment['price_at_investment'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>

    var ctxCategorySpending = document.getElementById('categorySpendingChart').getContext('2d');
    var categorySpendingChart = new Chart(ctxCategorySpending, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($categorySpending, 'category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($categorySpending, 'total')) ?>,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                            return label;
                        }
                    }
                }
            }
        }
    });

    var ctxMonthlySpending = document.getElementById('monthlySpendingChart').getContext('2d');
    var monthlySpendingChart = new Chart(ctxMonthlySpending, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($spending) { return date("F", mktime(0, 0, 0, $spending['month'], 10)); }, $monthlySpending)) ?>,
            datasets: [{
                label: 'Monthly Spending',
                data: <?= json_encode(array_column($monthlySpending, 'total')) ?>,
                backgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                            return label;
                        }
                    }
                }
            }
        }
    });

    var ctxYearlySpendingIncome = document.getElementById('yearlySpendingIncomeChart').getContext('2d');
    var yearlySpendingIncomeChart = new Chart(ctxYearlySpendingIncome, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($yearlySpending, 'year')) ?>,
            datasets: [{
                label: 'Yearly Spending',
                data: <?= json_encode(array_column($yearlySpending, 'total_spent')) ?>,
                borderColor: '#FF6384',
                fill: false,
                tension: 0.1
            }, {
                label: 'Yearly Income',
                data: <?= json_encode(array_column($yearlyIncome, 'total_income')) ?>,
                borderColor: '#4BC0C0',
                fill: false,
                tension: 0.1
            }, {
                label: 'Yearly Investments',
                data: <?= json_encode(array_column($yearlyInvestments, 'total_invested')) ?>,
                borderColor: '#FFCE56',
                fill: false,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
                            return label;
                        }
                    }
                }
            }
        }
    });

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