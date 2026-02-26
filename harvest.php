<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$crop_id = intval($_POST['crop_id']);

$investments = mysqli_query($conn,
    "SELECT * FROM investments WHERE crop_id=$crop_id AND status='Active'");

while($inv = mysqli_fetch_assoc($investments)) {

    $investor_id = $inv['investor_id'];
    $amount = $inv['amount'];

    $profit = ($amount * 20) / 100;
    $totalReturn = $amount + $profit;

    // Add money back to wallet
    mysqli_query($conn,
        "UPDATE users
         SET wallet_balance = wallet_balance + $totalReturn
         WHERE id = $investor_id");

    // Mark investment completed
    mysqli_query($conn,
        "UPDATE investments
         SET status='Completed'
         WHERE id = ".$inv['id']);
}

header("Location: admin_dashboard.php");
exit();
?>