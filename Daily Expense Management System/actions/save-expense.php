<?php
// ============================================================
// actions/save-expense.php
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /daily-expense-management/add-expense.php');
    exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: /daily-expense-management/add-expense.php');
    exit;
}

$uid            = currentUserId();
$title          = trim($_POST['title']          ?? '');
$amount         = $_POST['amount']              ?? '';
$category       = trim($_POST['category']       ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$expense_date   = trim($_POST['expense_date']   ?? '');
$notes          = trim($_POST['notes']          ?? '') ?: null;

$validCategories = ['Food','Transport','Shopping','Education','Medical','Bills','Entertainment','Travel','Other'];
$validMethods    = ['Cash','UPI','Card','Bank Transfer'];

$errors = [];
if (!$title)                                                           $errors[] = 'Title is required.';
if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0)    $errors[] = 'Enter a valid positive amount.';
if (!in_array($category, $validCategories, true))                      $errors[] = 'Select a valid category.';
if (!in_array($payment_method, $validMethods, true))                   $errors[] = 'Select a valid payment method.';
if (!$expense_date || !strtotime($expense_date))                       $errors[] = 'Enter a valid date.';

if ($errors) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: /daily-expense-management/add-expense.php');
    exit;
}

$amount       = round((float)$amount, 2);
$expense_date = date('Y-m-d', strtotime($expense_date));

$stmt = $conn->prepare(
    'INSERT INTO expenses (user_id, title, amount, category, payment_method, expense_date, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('isdssss', $uid, $title, $amount, $category, $payment_method, $expense_date, $notes);

if ($stmt->execute()) {
    $_SESSION['flash'] = 'Expense saved successfully!';
} else {
    $_SESSION['flash_error'] = 'Failed to save expense. Please try again.';
}
$stmt->close();
header('Location: /daily-expense-management/add-expense.php');
exit;
