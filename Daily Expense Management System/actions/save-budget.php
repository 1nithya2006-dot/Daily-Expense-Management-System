<?php
// actions/save-budget.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: /daily-expense-management/budget.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token']??'')) { $_SESSION['flash_error']='Invalid token.'; header('Location: /daily-expense-management/budget.php'); exit; }
$uid    = currentUserId();
$amount = $_POST['amount'] ?? '';
$month  = (int)($_POST['month'] ?? 0);
$year   = (int)($_POST['year']  ?? 0);
if ($amount===''||!is_numeric($amount)||(float)$amount<=0||$month<1||$month>12||$year<2000) {
    $_SESSION['flash_error']='Invalid input.'; header('Location: /daily-expense-management/budget.php?month='.$year.'-'.str_pad($month,2,'0',STR_PAD_LEFT)); exit;
}
$amount = round((float)$amount,2);
$stmt = $conn->prepare('INSERT INTO budgets (user_id,month,year,amount) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)');
$stmt->bind_param('iiid',$uid,$month,$year,$amount);
if ($stmt->execute()) { $_SESSION['flash']='Budget saved!'; } else { $_SESSION['flash_error']='Failed to save budget.'; }
$stmt->close();
header('Location: /daily-expense-management/budget.php?month='.$year.'-'.str_pad($month,2,'0',STR_PAD_LEFT)); exit;
