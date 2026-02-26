<?php
session_start();
$conn = new mysqli("localhost", "root", "", "agroinvest");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$investor_id = $_SESSION['user_id'];

$sql = "SELECT investments.*, crops.crop_name, users.name AS farmer_name 
        FROM investments
        JOIN crops ON investments.crop_id = crops.id
        JOIN users ON crops.farmer_id = users.id
        WHERE investments.investor_id = $investor_id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Investments</title>
    <style>
        body {
            font-family: Arial;
            background-color: #f4f6f9;
        }
        .container {
            width: 80%;
            margin: auto;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1 {
            background: green;
            color: white;
            padding: 15px;
        }
    </style>
</head>
<body>

<h1>My Investments</h1>

<div class="container">

<?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div class='card'>";
        echo "<p><strong>Crop:</strong> " . $row['crop_name'] . "</p>";
        echo "<p><strong>Farmer:</strong> " . $row['farmer_name'] . "</p>";
        echo "<p><strong>Amount Invested:</strong> ₹" . $row['amount'] . "</p>";
        echo "<p><strong>Date:</strong> " . $row['investment_date'] . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>No investments yet.</p>";
}
?>

</div>

</body>
</html>