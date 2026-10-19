<?php
// ============================================================
// actions/delete-expense.php
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /daily-expense-management/expense-history.php');
    exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$uid = currentUserId();
$id  = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM expenses WHERE id=? AND user_id=?');
$stmt->bind_param('ii', $id, $uid);
header('Content-Type: application/json');
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Expense deleted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Record not found or access denied.']);
}
$stmt->close();
exit;
