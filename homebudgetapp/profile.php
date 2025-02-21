<?php
session_start();
include 'db.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';


$query = $db->prepare("SELECT * FROM users WHERE id = ?");
$query->execute([$userId]);
$user = $query->fetch(PDO::FETCH_ASSOC);

$username = $user['username'] ?? 'User';
$shareCode = $user['share_code'] ?? '';


if (!$shareCode) {
    $shareCode = bin2hex(random_bytes(16));
    $query = $db->prepare("UPDATE users SET share_code = ? WHERE id = ?");
    $query->execute([$shareCode, $userId]);
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $photo = $_FILES['photo'];
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($photo["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));


    $check = getimagesize($photo["tmp_name"]);
    if ($check !== false) {
      
        if ($photo["size"] > 5000000) {
            $error = "Sorry, your file is too large.";
        } else {
          
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            } else {
             
                if (move_uploaded_file($photo["tmp_name"], $targetFile)) {
                    
                    $query = $db->prepare("UPDATE users SET photo = ? WHERE id = ?");
                    $query->execute([$targetFile, $userId]);
                    $message = "The file " . htmlspecialchars(basename($photo["name"])) . " has been uploaded.";
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            }
        }
    } else {
        $error = "File is not an image.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photo'])) {
  
    $query = $db->prepare("SELECT photo FROM users WHERE id = ?");
    $query->execute([$userId]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['photo']) {
  
        if (unlink($user['photo'])) {
            
            $query = $db->prepare("UPDATE users SET photo = NULL WHERE id = ?");
            $query->execute([$userId]);
            $message = "Profile photo has been deleted.";
        } else {
            $error = "Sorry, there was an error deleting your photo.";
        }
    } else {
        $error = "No photo found to delete.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'], $_POST['description'], $_POST['category'], $_POST['day'])) {
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];
    $category = $_POST['category'];
    $day = intval($_POST['day']);

    if ($amount > 0 && !empty($description) && !empty($category) && $day > 0 && $day <= 31) {
        
        $query = $db->prepare("INSERT INTO regular_payments (user_id, amount, description, category, day) VALUES (?, ?, ?, ?, ?)");
        $query->execute([$userId, $amount, $description, $category, $day]);
        $message = "Regular payment has been set.";
    } else {
        $error = "Please fill in all fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
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
                <?php if (!empty($user['photo'])): ?>
                    <img src="<?= htmlspecialchars($user['photo']) ?>" alt="Profile Photo" class="profile-photo">
                <?php endif; ?>
                <a href="profile.php"><?= htmlspecialchars($user['username'] ?? 'User') ?></a> | <a href="logout.php" class="text-light">Logout</a>
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">Profile</h2>
            <?php if ($message): ?>
                <p class="alert alert-success"><?= htmlspecialchars($message) ?></p>
            <?php elseif ($error): ?>
                <p class="alert alert-danger"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <!-- Display user photo -->
            <?php if (isset($user['photo']) && $user['photo']): ?>
                <img src="<?= htmlspecialchars($user['photo']) ?>" alt="Profile Photo" class="img-thumbnail mb-3" style="max-width: 200px;">
                <!-- Delete photo form -->
                <form method="POST" action="profile.php">
                    <input type="hidden" name="delete_photo" value="1">
                    <button type="submit" class="btn btn-danger">Delete Photo</button>
                </form>
            <?php endif; ?>

            <!-- Photo upload form -->
            <form method="POST" action="profile.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="photo" class="form-label">Upload Photo:</label>
                    <input type="file" class="form-control" id="photo" name="photo" required>
                </div>
                <button type="submit" class="btn btn-primary">Upload Photo</button>
            </form>
        </div>
    </div>

    <!-- Share Code Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Your Share Code</h5>
            <p class="card-text">Share this code to let others view your savings:</p>
            <div class="alert alert-info" role="alert">
                <?= htmlspecialchars($shareCode) ?>
            </div>
        </div>
    </div>

    
    <form method="GET" action="https://mail.google.com/mail/">
        <div class="mb-3">
            <label for="recipient_email" class="form-label">Recipient Email:</label>
            <input type="email" class="form-control" id="recipient_email" name="to" required>
        </div>
        <input type="hidden" name="view" value="cm">
        <input type="hidden" name="su" value="Share Code from Home Budget App">
        <input type="hidden" name="body" value="<?= urlencode("This is my share code: $shareCode\n\nBest regards,\n$username") ?>">
        <button type="submit" class="btn btn-primary">Send Email</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>