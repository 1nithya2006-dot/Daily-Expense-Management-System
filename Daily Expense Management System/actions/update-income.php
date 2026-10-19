<?php
// ============================================================
// actions/update-income.php
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /daily-expense-management/income.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $_SESSION['flash_error'] = 'Invalid token.'; header('Location: /daily-expense-management/income.php'); exit; }
$uid         = currentUserId();
$id          = (int)($_POST['id']         ?? 0);
$title       = trim($_POST['title']       ?? '');
$amount      = $_POST['amount']           ?? '';
$category    = trim($_POST['category']    ?? '');
$income_date = trim($_POST['income_date'] ?? '');
$notes       = trim($_POST['notes']       ?? '') ?: null;
$validCats   = ['Salary','Allowance','Freelance','Gift','Other'];
$errors = [];
if (!$id)                                                            $errors[] = 'Invalid ID.';
if (!$title)                                                         $errors[] = 'Title required.';
if ($amount===''||!is_numeric($amount)||(float)$amount<=0)           $errors[] = 'Enter valid amount.';
if (!in_array($category,$validCats,true))                            $errors[] = 'Select valid category.';
if (!$income_date||!strtotime($income_date))                         $errors[] = 'Enter valid date.';
if ($errors) { $_SESSION['flash_error'] = implode(' ',$errors); header('Location: /daily-expense-management/income.php'); exit; }
$amount      = round((float)$amount,2);
$income_date = date('Y-m-d',strtotime($income_date));
$chk = $conn->prepare('SELECT id FROM income WHERE id=? AND user_id=? LIMIT 1');
$chk->bind_param('ii',$id,$uid); $chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) { $chk->close(); $_SESSION['flash_error']='Not found.'; header('Location: /daily-expense-management/income.php'); exit; }
$chk->close();
$stmt = $conn->prepare('UPDATE income SET title=?,amount=?,category=?,income_date=?,notes=? WHERE id=? AND user_id=?');
$stmt->bind_param('sdsssii',$title,$amount,$category,$income_date,$notes,$id,$uid);
if ($stmt->execute()) { $_SESSION['flash'] = 'Income updated!'; } else { $_SESSION['flash_error'] = 'Failed to update.'; }
$stmt->close();
header('Location: /daily-expense-management/income.php'); exit;
