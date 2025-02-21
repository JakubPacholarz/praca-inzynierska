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


$cryptoApiUrl = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,cardano,polkadot,solana,litecoin,binancecoin,ripple,dogecoin,tron&vs_currencies=usd";
$cryptoPrices = @json_decode(file_get_contents($cryptoApiUrl), true);
if ($cryptoPrices === null) {
    $cryptoPrices = [];
}


$stocksApiUrl = "https://www.alphavantage.co/query?function=BATCH_STOCK_QUOTES&symbols=AAPL,MSFT,GOOGL,AMZN,FB,TSLA,BRK.B,V,JPM,JNJ&apikey=YOUR_API_KEY";
$stocksPrices = @json_decode(file_get_contents($stocksApiUrl), true);
if ($stocksPrices === null || !isset($stocksPrices['Stock Quotes'])) {
    $stocksPrices = ['Stock Quotes' => []];
}


$query = $db->prepare("SELECT * FROM investments WHERE user_id = ?");
$query->execute([$userId]);
$investments = $query->fetchAll(PDO::FETCH_ASSOC);


$query = $db->prepare("SELECT * FROM actions WHERE user_id = ?");
$query->execute([$userId]);
$trades = $query->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['type'], $_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
        $type = trim($_POST['type']);
        $amount = trim($_POST['amount']);
        $currentPrice = 0;

        if ($amount > $remainingBudget) {
            $error = "You cannot invest more than your available budget.";
        } else {
            if (strpos($type, 'Crypto-') === 0) {
                $asset = strtolower(str_replace('Crypto-', '', $type));
                $currentPrice = $cryptoPrices[$asset]['usd'] ?? 0;
            } elseif (strpos($type, 'Stock-') === 0) {
                $asset = str_replace('Stock-', '', $type);
                foreach ($stocksPrices['Stock Quotes'] as $stock) {
                    if ($stock['1. symbol'] === $asset) {
                        $currentPrice = $stock['2. price'];
                        break;
                    }
                }
            }

    
            $query = $db->prepare("INSERT INTO investments (user_id, type, amount, price_at_investment, date) VALUES (?, ?, ?, ?, NOW())");
            $query->execute([$userId, $type, $amount, $currentPrice]);

          
            $remainingBudget -= $amount;
        }
    } elseif (isset($_POST['sell_investment_id'])) {
        $investmentId = $_POST['sell_investment_id'];
        $query = $db->prepare("SELECT * FROM investments WHERE id = ? AND user_id = ?");
        $query->execute([$investmentId, $userId]);
        $investment = $query->fetch(PDO::FETCH_ASSOC);

        if ($investment) {
            $typeParts = explode('-', $investment['type']);
            $category = strtolower($typeParts[0]);
            $asset = strtolower($typeParts[1] ?? '');
            $currentPrice = 0;

            if ($category === 'crypto') {
                $currentPrice = $cryptoPrices[$asset]['usd'] ?? 0;
            } elseif ($category === 'stock') {
                foreach ($stocksPrices['Stock Quotes'] as $stock) {
                    if ($stock['1. symbol'] === $asset) {
                        $currentPrice = $stock['2. price'];
                        break;
                    }
                }
            }

            $priceAtInvestment = $investment['price_at_investment'];
            $profitLoss = ($currentPrice - $priceAtInvestment) * $investment['amount'] / $priceAtInvestment;
            $remainingBudget += $investment['amount'] + $profitLoss;

        
            $query = $db->prepare("INSERT INTO actions (user_id, type, amount, profit_loss, date_bought, date_sold) VALUES (?, ?, ?, ?, ?, NOW())");
            $query->execute([$userId, $investment['type'], $investment['amount'], $profitLoss, $investment['date']]);

            $query = $db->prepare("INSERT INTO income (user_id, amount, type, date) VALUES (?, ?, 'irregular', NOW())");
            $query->execute([$userId, $profitLoss]);
     $query = $db->prepare("DELETE FROM investments WHERE id = ? AND user_id = ?");
            $query->execute([$investmentId, $userId]);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investments</title>
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
    <h1 class="text-center mb-4">Savings and Investments</h1>

    <!-- Live Prices for Cryptocurrencies -->
    <h2>Live Prices for Top 10 Cryptocurrencies</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Cryptocurrency</th>
                <th>Price (USD)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cryptoPrices as $crypto => $data): ?>
                <tr>
                    <td><?= ucfirst($crypto) ?></td>
                    <td>$<?= number_format($data['usd'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Investment Form -->
    <h2>Invest in an Asset</h2>
    <?php if (isset($error)): ?>
        <p class="alert alert-danger"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form action="savings.php" method="POST" class="mb-4">
        <div class="form-group">
            <label for="type">Select Asset:</label>
            <select name="type" id="type" class="form-control" required>
                <!-- Cryptocurrencies -->
                <optgroup label="Cryptocurrencies">
                    <?php foreach ($cryptoPrices as $asset => $data): ?>
                        <option value="Crypto-<?= ucfirst($asset) ?>"><?= ucfirst($asset) ?> (Crypto)</option>
                    <?php endforeach; ?>
                </optgroup>
                
                <!-- Stocks -->
                <optgroup label="Stocks">
                    <?php foreach ($stocksPrices['Stock Quotes'] as $stock): ?>
                        <option value="Stock-<?= $stock['1. symbol'] ?>"><?= $stock['1. symbol'] ?> (Stock)</option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Investment Amount (USD):</label>
            <input type="number" id="amount" name="amount" class="form-control" step="0.01" required>
        </div>
        <button type="submit" class="btn btn-primary">Invest</button>
    </form>

    <!-- Display User's Investments -->
    <h2>Your Investments</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount (USD)</th>
                <th>Price at Investment (USD)</th>
                <th>Current Price (USD)</th>
                <th>Change (%)</th>
                <th>Profit/Loss (USD)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($investments as $investment): 
                $typeParts = explode('-', $investment['type']);
                $category = strtolower($typeParts[0]);
                $asset = strtolower($typeParts[1] ?? '');
                $currentPrice = 0;

                if ($category === 'crypto') {
                    $currentPrice = $cryptoPrices[$asset]['usd'] ?? 0;
                } elseif ($category === 'stock') {
                    foreach ($stocksPrices['Stock Quotes'] as $stock) {
                        if ($stock['1. symbol'] === $asset) {
                            $currentPrice = $stock['2. price'];
                            break;
                        }
                    }
                }

                $priceAtInvestment = $investment['price_at_investment'];
                $change = $priceAtInvestment > 0 
                          ? (($currentPrice - $priceAtInvestment) / $priceAtInvestment) * 100 
                          : 0;
                $profitLoss = ($currentPrice - $priceAtInvestment) * $investment['amount'] / $priceAtInvestment;
            ?>
            <tr>
                <td><?= ucfirst($category) ?> - <?= ucfirst($asset) ?></td>
                <td><?= number_format($investment['amount'], 2) ?></td>
                <td><?= number_format($priceAtInvestment, 2) ?></td>
                <td><?= number_format($currentPrice, 2) ?></td>
                <td><?= number_format($change, 2) ?>%</td>
                <td><?= number_format($profitLoss, 2) ?></td>
                <td>
                    <form action="savings.php" method="POST" style="display:inline;">
                        <input type="hidden" name="sell_investment_id" value="<?= $investment['id'] ?>">
                        <button type="submit" class="btn btn-danger">Sell</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Display User's Trades -->
    <h2>Your Trades</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount (USD)</th>
                <th>Profit/Loss (USD)</th>
                <th>Date Bought</th>
                <th>Date Sold</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trades as $trade): ?>
            <tr>
                <td><?= htmlspecialchars($trade['type']) ?></td>
                <td><?= number_format($trade['amount'], 2) ?></td>
                <td><?= number_format($trade['profit_loss'], 2) ?></td>
                <td><?= htmlspecialchars($trade['date_bought']) ?></td>
                <td><?= htmlspecialchars($trade['date_sold']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>