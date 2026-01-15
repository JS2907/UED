<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

require_once __DIR__ . '/db_conn.php';

$stmt = db()->prepare("
    SELECT id, username, role
    FROM uedu_users
    WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>UEDU</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <h1 class="logo">
            <a href="<?= BASE_URL ?>/index.php">UEDU</a>
        </h1>
        <nav class="gnb">
            <a href="<?= BASE_URL ?>/myroom.php">나의강의실</a>
            <a href="<?= BASE_URL ?>/board.php?type=qna">1:1문의</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/index.php">관리자</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/logout.php">로그아웃</a>
        </nav>
        <div class="user-info">
            <?= htmlspecialchars($user['username']) ?> 님
        </div>
    </div>
</header>

<main class="site-content">
