<?php
// actions/delete-income.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD']!=='POST') { echo json_encode(['success'=>false,'message'=>'Invalid method']); exit; }
if (!verifyCsrfToken($_POST['csrf_token']??'')) { echo json_encode(['success'=>false,'message'=>'Invalid token']); exit; }
$uid = currentUserId();
$id  = (int)($_POST['id']??0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
$stmt = $conn->prepare('DELETE FROM income WHERE id=? AND user_id=?');
$stmt->bind_param('ii',$id,$uid);
if ($stmt->execute()&&$stmt->affected_rows>0) { echo json_encode(['success'=>true,'message'=>'Income deleted.']); }
else { echo json_encode(['success'=>false,'message'=>'Not found.']); }
$stmt->close(); exit;
