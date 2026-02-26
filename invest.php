<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'investor') {
    header("Location: login.php");
    exit();
}

$investor_id = $_SESSION['user_id'];
$crop_id = intval($_POST['crop_id']);
$amount = floatval($_POST['amount']);

if ($amount <= 0) {
    die("Invalid investment amount.");
}

/* ============================= */
/* 1️⃣ GET INVESTOR WALLET BALANCE */
/* ============================= */
$walletStmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$walletStmt->bind_param("i", $investor_id);
$walletStmt->execute();
$walletResult = $walletStmt->get_result();
$walletData = $walletResult->fetch_assoc();

if (!$walletData) {
    die("User not found.");
}

$currentWallet = $walletData['wallet_balance'];

if ($amount > $currentWallet) {
    die("Insufficient wallet balance.");
}

/* ============================= */
/* 2️⃣ GET CROP REQUIRED AMOUNT */
/* ============================= */
$cropStmt = $conn->prepare("SELECT investment_required FROM crops WHERE id = ?");
$cropStmt->bind_param("i", $crop_id);
$cropStmt->execute();
$cropResult = $cropStmt->get_result();
$cropData = $cropResult->fetch_assoc();

if (!$cropData) {
    die("Crop not found.");
}

$requiredAmount = $cropData['investment_required'];

/* ============================= */
/* 3️⃣ CALCULATE TOTAL INVESTED */
/* ============================= */
$totalStmt = $conn->prepare("SELECT SUM(amount) AS total FROM investments WHERE crop_id = ?");
$totalStmt->bind_param("i", $crop_id);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalData = $totalResult->fetch_assoc();

$totalInvested = $totalData['total'] ?? 0;
$remainingAmount = $requiredAmount - $totalInvested;

if ($remainingAmount <= 0) {
    die("Crop already fully funded.");
}

if ($amount > $remainingAmount) {
    die("Only ₹" . $remainingAmount . " is remaining for this crop.");
}

/* ============================= */
/* 4️⃣ START TRANSACTION */
/* ============================= */
$conn->begin_transaction();

try {

    /* Insert Investment */
    $insertStmt = $conn->prepare("INSERT INTO investments (investor_id, crop_id, amount, investment_date)
                                  VALUES (?, ?, ?, NOW())");
    $insertStmt->bind_param("iid", $investor_id, $crop_id, $amount);
    $insertStmt->execute();

    /* Deduct Wallet */
    $updateWalletStmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $updateWalletStmt->bind_param("di", $amount, $investor_id);
    $updateWalletStmt->execute();

    /* Log Transaction */
    $transactionStmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference_id)
                                       VALUES (?, 'investment', ?, ?)");
    $transactionStmt->bind_param("idi", $investor_id, $amount, $crop_id);
    $transactionStmt->execute();

    /* Commit Transaction */
    $conn->commit();

    header("Location: investor_dashboard.php");
    exit();

} catch (Exception $e) {

    $conn->rollback();
    die("Investment failed. Please try again.");

}
?>