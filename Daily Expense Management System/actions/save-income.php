<?php
// actions/save-income.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /daily-expense-management/income.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $_SESSION['flash_error'] = 'Invalid token.'; header('Location: /daily-expense-management/income.php'); exit; }
$uid         = currentUserId();
$title       = trim($_POST['title']       ?? '');
$amount      = $_POST['amount']           ?? '';
$category    = trim($_POST['category']    ?? '');
$income_date = trim($_POST['income_date'] ?? '');
$notes       = trim($_POST['notes']       ?? '') ?: null;
$validCats   = ['Salary','Allowance','Freelance','Gift','Other'];
$errors = [];
if (!$title)                                                         $errors[] = 'Title required.';
if ($amount===''||!is_numeric($amount)||(float)$amount<=0)           $errors[] = 'Enter valid amount.';
if (!in_array($category,$validCats,true))                            $errors[] = 'Select valid category.';
if (!$income_date||!strtotime($income_date))                         $errors[] = 'Enter valid date.';
if ($errors) { $_SESSION['flash_error']=implode(' ',$errors); header('Location: /daily-expense-management/income.php'); exit; }
$amount      = round((float)$amount,2);
$income_date = date('Y-m-d',strtotime($income_date));
$stmt = $conn->prepare('INSERT INTO income (user_id,title,amount,category,income_date,notes) VALUES (?,?,?,?,?,?)');
$stmt->bind_param('isdsss',$uid,$title,$amount,$category,$income_date,$notes);
if ($stmt->execute()) { $_SESSION['flash']='Income added!'; } else { $_SESSION['flash_error']='Failed to save.'; }
$stmt->close();
header('Location: /daily-expense-management/income.php'); exit;
