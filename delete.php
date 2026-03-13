<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?msg=" . urlencode("Invalid student id."));
    exit;
}

$stmt = db()->prepare("SELECT profile_image FROM students WHERE student_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row && !empty($row['profile_image'])) {
    $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $row['profile_image'];
    if (is_file($path)) {
        @unlink($path);
    }
}

$stmt = db()->prepare("DELETE FROM students WHERE student_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: index.php?msg=" . urlencode("Student deleted successfully."));
exit;

