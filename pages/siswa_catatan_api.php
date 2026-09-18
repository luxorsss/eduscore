<?php
require_once '../config/koneksi.php';
header('Content-Type: application/json; charset=utf-8');

$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
if (!$class_id) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Kelas tidak valid.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nis, nama FROM students WHERE class_id = ? ORDER BY nama ASC");
$stmt->execute([$class_id]);

echo json_encode([
    'success'=>true,
    'students'=>$stmt->fetchAll(PDO::FETCH_ASSOC)
], JSON_UNESCAPED_UNICODE);
