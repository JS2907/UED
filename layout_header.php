<?php
// layout_header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$public_cache_pages = ['index.php', 'company.php', 'courses.php', 'board.php'];
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!isset($_SESSION['user_id']) && in_array($current_page, $public_cache_pages, true)) {
    header('Cache-Control: public, max-age=300, stale-while-revalidate=60');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    header('Vary: Accept-Encoding');
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>UEDU</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav>
    <a href="/index.php">홈</a>
    <a href="/board.php?type=notice">공지</a>
    <a href="/board.php?type=faq">FAQ</a>
    <a href="/login.php">로그인</a>
</nav>