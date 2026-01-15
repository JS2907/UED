<?php
// /admin/_admin_header.php
require_once __DIR__ . '/_admin_guard.php'; // guard에서 session start 및 $me 설정
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>LMS Admin System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/uedu/assets/admin_style.css">
</head>
<body class="admin-body">

  <aside class="admin-sidebar">
    <div class="admin-logo">
      <a href="/uedu/admin/index.php">KSSE Admin</a>
    </div>
    <nav class="admin-menu">
      <a href="/uedu/admin/index.php" 
         class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
         대시보드
      </a>
      <a href="/uedu/admin/users.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
         회원 관리
      </a>
      <a href="/uedu/admin/courses.php"
         class="<?= in_array(basename($_SERVER['PHP_SELF']), ['courses.php', 'course_edit.php']) ? 'active' : '' ?>">
         교육과정 관리
      </a>
      <a href="/uedu/admin/contents.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'contents.php' ? 'active' : '' ?>">
         영상 콘텐츠
      </a>
      <a href="/uedu/admin/curriculum.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'curriculum.php' ? 'active' : '' ?>">
         커리큘럼 구성
      </a>
      <a href="/uedu/admin/exams.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : '' ?>">
         시험/평가
      </a>
      <a href="/uedu/admin/orders.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
         주문/결제
      </a>
      <a href="/uedu/admin/completions.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'completions.php' ? 'active' : '' ?>">
         수료/발급
      </a>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="admin-header-bar">
      <div style="font-weight:600; font-size:16px; color:#333;">
        <?= htmlspecialchars($me['name'] ?? $me['username']) ?> 관리자님, 환영합니다.
      </div>
      <div>
        <a class="btn btn-gray" href="/uedu/index.php" target="_blank">사용자 홈</a>
        <a class="btn btn-gray" href="/uedu/logout.php" style="margin-left:8px;">로그아웃</a>
      </div>
    </header>

    <div class="admin-content-wrap">