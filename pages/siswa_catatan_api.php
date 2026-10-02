<?php
session_start();
require_once '../config/auth.php';

check_login();

header('Content-Type: application/json; charset=utf-8');

$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);

if (!$class_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Kelas tidak valid.'
    ]);
    exit;
}

if (!$is_admin && (int)$wali_kelas['id'] !== $class_id) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses ditolak: Anda bukan wali kelas dari kelas ini.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nama
    FROM students
    WHERE class_id = ?
    ORDER BY nama ASC
");

$stmt->execute([$class_id]);

echo json_encode([
    'success' => true,
    'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)
], JSON_UNESCAPED_UNICODE);