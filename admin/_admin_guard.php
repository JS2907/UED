<?php
// /admin/_admin_guard.php

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

require_once __DIR__ . '/../db_conn.php';

$stmt = db()->prepare("
    SELECT id, username, role
    FROM uedu_users
    WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch();

if (!$me || ($me['role'] ?? 'student') !== 'admin') {
    echo "<div style='padding:20px;font-family:Arial;'>접근 권한이 없습니다.</div>";
    exit;
}
