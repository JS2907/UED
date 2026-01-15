<?php
require __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../db_conn.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  header('Location: /admin/courses.php');
  exit;
}

$stmt = db()->prepare("DELETE FROM uedu_courses WHERE id=?");
$stmt->execute([$id]);

header('Location: /admin/courses.php');
exit;
