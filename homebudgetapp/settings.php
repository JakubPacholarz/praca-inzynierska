<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$userId = $_SESSION['user_id'];

$query = $db->prepare("SELECT username, photo, budget, share_code FROM users WHERE id = ?");
$query->execute([$userId]);
$user = $query->fetch(PDO::FETCH_ASSOC);

$username = $user['username'] ?? '';
$photo = $user['photo'] ?? '';
$currentBudget = $user['budget'] ?? 0;
$shareCode = $user['share_code'] ?? '';

if (!$shareCode) {
    $shareCode = bin2hex(random_bytes(16));
    $query = $db->prepare("UPDATE users SET share_code = ? WHERE id = ?");
    $query->execute([$shareCode, $userId]);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_income'])) {
    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'];

    if ($amount > 0 && !empty($type) && !empty($date)) {
        $query = $db->prepare("INSERT INTO income (user_id, type, description, amount, date) VALUES (?, ?, ?, ?, ?)");
        $query->execute([$userId, $type, $description, $amount, $date]);

        $currentBudget += $amount;
        $query = $db->prepare("UPDATE users SET budget = ? WHERE id = ?");
        $query->execute([$currentBudget, $userId]);

        header("Location: settings.php"); 
        exit;
    } else {
        $error = "Please fill in all fields with valid data.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $type = $_POST['type'];
    $description = $_POST['description'];
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'];

    if ($amount > 0 && !empty($type) && !empty($date)) {
        if ($type === 'Regular') {
            $query = $db->prepare("INSERT INTO regular_payments (user_id, amount, description, date) VALUES (?, ?, ?, ?)");
            $query->execute([$userId, $amount, $description, $date]);
        } elseif ($type === 'Irregular') {
            $query = $db->prepare("INSERT INTO irregular_payments (user_id, amount, description, date) VALUES (?, ?, ?, ?)");
            $query->execute([$userId, $amount, $description, $date]);
        }

        
        if ($date === date('Y-m-d')) {
            $currentBudget -= $amount;
            $query = $db->prepare("UPDATE users SET budget = ? WHERE id = ?");
            $query->execute([$currentBudget, $userId]);
        }

        $message = "Payment added successfully!";
    } else {
        $error = "Please fill in all fields with valid data.";
    }
}

$query = $db->prepare("SELECT * FROM income WHERE user_id = ?");
$query->execute([$userId]);
$incomes = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $db->prepare("SELECT * FROM regular_payments WHERE user_id = ?");
$query->execute([$userId]);
$payments = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="summary.php">Summary</a></li>
                <li class="nav-item"><a class="nav-link" href="savings.php">Savings</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="view_savings.php">View Savings</a></li>
            </ul>
            <span class="navbar-text">
                <?php if ($photo): ?>
                    <img src="<?= htmlspecialchars($photo) ?>" alt="Profile Photo" class="profile-photo">
                <?php endif; ?>
                <a href="profile.php" class="text-light"><?= htmlspecialchars($username) ?></a> | 
                <a href="logout.php" class="text-light">Logout</a>
              
            </span>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1 class="mb-4 text-center">Settings</h1>

    <!-- Add Income Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Add Income</h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="Regular">Regular</option>
                            <option value="Irregular">Irregular</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" name="amount" id="amount" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" required>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" name="add_income" class="btn btn-success">Add Income</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Payment Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Add Payment</h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="Regular">Regular</option>
                        
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" name="amount" id="amount" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" required>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" name="add_payment" class="btn btn-danger">Add Payment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>