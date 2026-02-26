<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'investor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get Wallet Balance */
$walletQuery = "SELECT wallet_balance FROM users WHERE id = $user_id";
$walletResult = mysqli_query($conn, $walletQuery);
$walletData = mysqli_fetch_assoc($walletResult);
$wallet = $walletData['wallet_balance'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Investor Dashboard</title>
    <style>
        body { font-family: Arial; background:#f4f6f9; margin:0; }
        .header {
            background:#2e7d32;
            color:white;
            padding:15px 30px;
            display:flex;
            justify-content:space-between;
        }
        .container { padding:30px; }
        .card {
            background:white;
            padding:20px;
            margin-bottom:20px;
            border-radius:8px;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
        }
        img {
            width:200px;
            height:150px;
            object-fit:cover;
            border-radius:6px;
            margin-top:10px;
        }
        button {
            padding:8px 15px;
            background:#2e7d32;
            color:white;
            border:none;
            border-radius:4px;
            cursor:pointer;
        }
        button:hover { background:#1b5e20; }
        .progress-bar {
            background:#ddd;
            height:12px;
            border-radius:10px;
            overflow:hidden;
            margin-top:10px;
        }
        .progress {
            background:#4caf50;
            height:100%;
        }
        .funded { color:green; font-weight:bold; }
    </style>
</head>
<body>

<div class="header">
    <h3>Welcome, <?php echo $_SESSION['name']; ?> 👋</h3>
    <div>
        Wallet: ₹<?php echo $wallet; ?> |
        <a href="logout.php" style="color:white;">Logout</a>
    </div>
</div>

<div class="container">
<h2>Available Crops</h2>

<?php
$query = "SELECT crops.*, users.name AS farmer_name
          FROM crops
          JOIN users ON crops.farmer_id = users.id";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {

    $crop_id = $row['id'];

    // Total invested
    $sumQuery = "SELECT SUM(amount) as total FROM investments WHERE crop_id = $crop_id";
    $sumResult = mysqli_query($conn, $sumQuery);
    $sumData = mysqli_fetch_assoc($sumResult);

    $totalInvested = $sumData['total'] ? $sumData['total'] : 0;
    $requiredAmount = $row['investment_required'];
    $remainingAmount = $requiredAmount - $totalInvested;

    $progressPercent = ($requiredAmount > 0) ?
        min(100, ($totalInvested / $requiredAmount) * 100) : 0;
?>

<div class="card">

    <p><strong>Crop:</strong> <?php echo $row['crop_name']; ?></p>
    <p><strong>Farmer:</strong> <?php echo $row['farmer_name']; ?></p>
    <p><strong>Required:</strong> ₹<?php echo $requiredAmount; ?></p>
    <p><strong>Description:</strong> <?php echo $row['description']; ?></p>

    <?php if (!empty($row['image'])): ?>
        <img src="uploads/<?php echo $row['image']; ?>">
    <?php endif; ?>

    <p><strong>Total Invested:</strong> ₹<?php echo $totalInvested; ?></p>
    <p><strong>Remaining:</strong> ₹<?php echo $remainingAmount; ?></p>

    <div class="progress-bar">
        <div class="progress" style="width: <?php echo $progressPercent; ?>%;"></div>
    </div>
    <small><?php echo round($progressPercent); ?>% Funded</small>

    <?php if ($remainingAmount > 0): ?>
        <form action="invest.php" method="POST">
            <input type="hidden" name="crop_id" value="<?php echo $crop_id; ?>">
            <input type="number"
                   name="amount"
                   max="<?php echo min($remainingAmount, $wallet); ?>"
                   placeholder="Enter Amount"
                   required>
            <br><br>
            <button type="submit">Invest</button>
        </form>
    <?php else: ?>
        <p class="funded">✅ Fully Funded</p>
    <?php endif; ?>

</div>

<?php } ?>

</div>
</body>
</html>