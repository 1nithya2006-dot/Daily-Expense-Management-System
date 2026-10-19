<?php
// ============================================================
// actions/update-expense.php
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /daily-expense-management/expense-history.php');
    exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid request token.';
    header('Location: /daily-expense-management/expense-history.php');
    exit;
}

$uid            = currentUserId();
$id             = (int)($_POST['id'] ?? 0);
$title          = trim($_POST['title']          ?? '');
$amount         = $_POST['amount']              ?? '';
$category       = trim($_POST['category']       ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$expense_date   = trim($_POST['expense_date']   ?? '');
$notes          = trim($_POST['notes']          ?? '') ?: null;

$validCategories = ['Food','Transport','Shopping','Education','Medical','Bills','Entertainment','Travel','Other'];
$validMethods    = ['Cash','UPI','Card','Bank Transfer'];

$errors = [];
if (!$id)                                                              $errors[] = 'Invalid expense ID.';
if (!$title)                                                           $errors[] = 'Title is required.';
if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0)    $errors[] = 'Enter a valid positive amount.';
if (!in_array($category, $validCategories, true))                      $errors[] = 'Select a valid category.';
if (!in_array($payment_method, $validMethods, true))                   $errors[] = 'Select a valid payment method.';
if (!$expense_date || !strtotime($expense_date))                       $errors[] = 'Enter a valid date.';

if ($errors) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: /daily-expense-management/expense-history.php');
    exit;
}

$amount       = round((float)$amount, 2);
$expense_date = date('Y-m-d', strtotime($expense_date));

// Verify ownership
$chk = $conn->prepare('SELECT id FROM expenses WHERE id=? AND user_id=? LIMIT 1');
$chk->bind_param('ii', $id, $uid);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    $_SESSION['flash_error'] = 'Record not found or access denied.';
    header('Location: /daily-expense-management/expense-history.php');
    exit;
}
$chk->close();

$stmt = $conn->prepare(
    'UPDATE expenses SET title=?, amount=?, category=?, payment_method=?, expense_date=?, notes=? WHERE id=? AND user_id=?'
);
$stmt->bind_param('sdssssii', $title, $amount, $category, $payment_method, $expense_date, $notes, $id, $uid);
if ($stmt->execute()) {
    $_SESSION['flash'] = 'Expense updated successfully!';
} else {
    $_SESSION['flash_error'] = 'Failed to update expense.';
}
$stmt->close();
header('Location: /daily-expense-management/expense-history.php');
exit;
