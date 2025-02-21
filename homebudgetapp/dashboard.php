<?php
session_start();
include 'db.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

$query = $db->prepare("SELECT * FROM users WHERE id = ?");
$query->execute([$userId]);
$user = $query->fetch(PDO::FETCH_ASSOC);


$query = $db->prepare("SELECT SUM(amount) AS total_regular_income FROM income WHERE user_id = ? AND type = 'regular' AND date <= CURRENT_DATE()");
$query->execute([$userId]);
$regularIncome = $query->fetch(PDO::FETCH_ASSOC)['total_regular_income'] ?? 0;


$query = $db->prepare("SELECT SUM(amount) AS total_irregular_income FROM income WHERE user_id = ? AND type = 'irregular' AND date <= CURRENT_DATE()");
$query->execute([$userId]);
$irregularIncome = $query->fetch(PDO::FETCH_ASSOC)['total_irregular_income'] ?? 0;


$currentBudget = ($user['budget'] ?? 0) + $regularIncome + $irregularIncome;

$query = $db->prepare("SELECT SUM(amount) AS total_spent FROM payments WHERE user_id = ? AND date <= CURRENT_DATE()");
$query->execute([$userId]);
$totalSpent = $query->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;


$query = $db->prepare("SELECT SUM(amount) AS total_invested FROM investments WHERE user_id = ?");
$query->execute([$userId]);
$totalInvested = $query->fetch(PDO::FETCH_ASSOC)['total_invested'] ?? 0;

$remainingBudget = $currentBudget - $totalSpent - $totalInvested;

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$query = $db->prepare("SELECT * FROM payments WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$query->execute([$userId, $month, $year]);
$payments = $query->fetchAll(PDO::FETCH_ASSOC);


$query = $db->prepare("SELECT * FROM regular_payments WHERE user_id = ?");
$query->execute([$userId]);
$regularPayments = $query->fetchAll(PDO::FETCH_ASSOC);


$query = $db->prepare("SELECT * FROM income WHERE user_id = ? AND type = 'irregular' AND MONTH(date) = ? AND YEAR(date) = ?");
$query->execute([$userId, $month, $year]);
$irregularIncomeEntries = $query->fetchAll(PDO::FETCH_ASSOC);


$query = $db->prepare("SELECT * FROM income WHERE user_id = ? AND type = 'regular'");
$query->execute([$userId]);
$regularIncomeEntries = $query->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'], $_POST['description'], $_POST['category'], $_POST['date'])) {
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];
    $category = $_POST['category'];
    $date = $_POST['date'];

    if ($amount > 0 && !empty($description) && !empty($category) && !empty($date)) {
     
        $query = $db->prepare("INSERT INTO payments (user_id, amount, description, category, date) VALUES (?, ?, ?, ?, ?)");
        $query->execute([$userId, $amount, $description, $category, $date]);
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Please fill in all fields correctly.";
    }
}


$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date('w', strtotime("$year-$month-01"));
$calendar = [];

for ($i = 0; $i < $firstDayOfMonth; $i++) {
    $calendar[] = '';
}

for ($day = 1; $day <= $daysInMonth; $day++) {
    $calendar[] = $day;
}

while (count($calendar) % 7 != 0) {
    $calendar[] = '';
}

function isPaymentDay($day, $payments) {
    foreach ($payments as $payment) {
        if (date('j', strtotime($payment['date'])) == $day) {
            return true;
        }
    }
    return false;
}

function isRegularPaymentDay($day, $regularPayments) {
    foreach ($regularPayments as $payment) {
        if (date('j', strtotime($payment['date'])) == $day) {
            return true;
        }
    }
    return false;
}

function isIrregularIncomeDay($day, $irregularIncome) {
    foreach ($irregularIncome as $income) {
        if (date('j', strtotime($income['date'])) == $day) {
            return true;
        }
    }
    return false;
}

function isRegularIncomeDay($date, $regularIncome) {
    foreach ($regularIncome as $income) {
        if (date('j', strtotime($income['date'])) == $date) {
            return true;
        }
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <style>
        .calendar {
            margin-top: 30px;
        }
        .calendar td, .calendar th {
            text-align: center;
            padding: 10px;
        }
        .calendar .today {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }
        .calendar .payment-day {
            background-color: #28a745;
            color: #fff;
        }
        .calendar .regular-payment-day {
            background-color: #ffc107;
            color: #fff;
        }
        .calendar .irregular-income-day {
            background-color: #17a2b8;
            color: #fff;
        }
        .calendar .regular-income-day {
            background-color: #6f42c1;
            color: #fff;
            animation: happyBackground 1s infinite;
        }
        @keyframes happyBackground {
            0% { background-color: #6f42c1; }
            50% { background-color: #ff69b4; }
            100% { background-color: #6f42c1; }
        }
        .navbar-text img {
            border-radius: 50%;
            width: 30px;
            height: 30px;
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
                <?php if (isset($user['photo']) && $user['photo']): ?>
                    <img src="<?= htmlspecialchars($user['photo']) ?>" alt="Profile Photo">
                <?php endif; ?>
                <a href="profile.php"><?= htmlspecialchars($username) ?></a> | <a href="logout.php" class="text-light">Logout</a>
                
            </span>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1 class="text-center mb-4">Welcome, <?= htmlspecialchars($username) ?>!</h1>
    <div class="row">
        <!-- Budget Summary -->
        <div class="col-md-4">
    <div class="card text-white bg-primary mb-3">
        <div class="card-header">Total Budget</div>
        <div class="card-body">
            <h5 class="card-title">$<?= number_format($currentBudget / 2, 2) ?></h5>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-white bg-success mb-3">
        <div class="card-header">Remaining Budget</div>
        <div class="card-body">
            <h5 class="card-title">$<?= number_format(($remainingBudget - $totalSpent - $totalInvested)/2, 2) ?></h5>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-white bg-danger mb-3">
        <div class="card-header">Total Spent</div>
        <div class="card-body">
            <h5 class="card-title">$<?= number_format($totalSpent, 2) ?></h5>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card text-white bg-warning mb-3">
        <div class="card-header">Total Invested</div>
        <div class="card-body">
            <h5 class="card-title">$<?= number_format($totalInvested , 2) ?></h5>
        </div>
    </div>
</div>




    <!-- Calendar Navigation -->
    <div class="d-flex justify-content-between mb-3">
        <a href="?month=<?= $month == 1 ? 12 : $month - 1 ?>&year=<?= $month == 1 ? $year - 1 : $year ?>" class="btn btn-secondary">&laquo; Previous</a>
        <h2 class="text-center"><?= date('F Y', strtotime("$year-$month-01")) ?></h2>
        <a href="?month=<?= $month == 12 ? 1 : $month + 1 ?>&year=<?= $month == 12 ? $year + 1 : $year ?>" class="btn btn-secondary">Next &raquo;</a>
    </div>

    <!-- Calendar -->
    <div class="calendar">
        <h2 class="text-center">Payment Calendar</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < count($calendar); $i++): ?>
                    <?php if ($i % 7 == 0): ?>
                        <tr>
                    <?php endif; ?>
                    <td class="<?= isPaymentDay($calendar[$i], $payments) ? 'payment-day' : '' ?> 
                    <?= isRegularPaymentDay($calendar[$i], $regularPayments) ? 'regular-payment-day' : '' ?>
                     <?= isIrregularIncomeDay($calendar[$i], $irregularIncomeEntries) ? 'irregular-income-day' : '' ?> 
                     <?= isRegularIncomeDay($calendar[$i], $regularIncomeEntries) ? 'regular-income-day' : '' ?>"
                        data-bs-toggle="modal" data-bs-target="#detailsModal" data-day="<?= $calendar[$i] ?>">
                        <?= $calendar[$i] ?>
                    </td>
                    <?php if ($i % 7 == 6): ?>
                        </tr>
                    <?php endif; ?>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <!-- Payments Table -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">Payments</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($payment['date']))) ?></td>
                            <td>$<?= number_format($payment['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($payment['description']) ?></td>
                            <td><?= htmlspecialchars($payment['category']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Payment Form -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Add Payment</h5>
            <?php if (isset($error)): ?>
                <p class="alert alert-danger"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST" action="dashboard.php">
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount:</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <input type="text" class="form-control" id="description" name="description" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category:</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="Groceries">Groceries</option>
                        <option value="Rent">Rent</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Dining Out">Dining Out</option>
                        <option value="Healthcare">Healthcare</option>
                        <option value="Education">Education</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Payment</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Day Details -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalContent"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var detailsModal = document.getElementById('detailsModal');
        detailsModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var day = button.getAttribute('data-day');
            var modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = '';

            <?php
            $allEntries = array_merge($payments, $regularPayments, $irregularIncomeEntries, $regularIncomeEntries);
            foreach ($allEntries as $entry) {
                $entryDate = date('j', strtotime($entry['date'] ?? $entry['day']));
                $entryType = isset($entry['amount']) ? 'Payment' : (isset($entry['type']) ? ucfirst($entry['type']) . ' Income' : 'Regular Payment');
                $entryAmount = $entry['amount'] ?? '';
                $entryDescription = $entry['description'] ?? '';
                $entryCategory = $entry['category'] ?? '';
                echo "if (day == $entryDate) {
                    modalContent.innerHTML += '<p><strong>$entryType:</strong> $$entryAmount - $entryDescription ($entryCategory)</p>';
                }";
            }
            ?>

            var hasRegularIncome = <?= json_encode(array_reduce($regularIncomeEntries, function($carry, $entry) use ($day) {
                return $carry || (date('j', strtotime($entry['date'])) == $day);
            }, false)) ?>;
            if (hasRegularIncome) {
                modalContent.classList.add('happyBackground');
            } else {
                modalContent.classList.remove('happyBackground');
            }
        });
    });
</script>

</body>
</html>