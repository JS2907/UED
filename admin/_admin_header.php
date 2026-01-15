<?php
// /admin/_admin_header.php
// _admin_guard.php에서 $me를 가져옵니다.
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>UEDU Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/assets/style.css">
  <style>
    .admin-wrap{max-width:1100px;margin:0 auto;padding:20px;}
    .admin-nav{display:flex;gap:10px;align-items:center;margin-bottom:20px;}
    .admin-nav a{padding:8px 12px;border-radius:6px;text-decoration:none;background:#eee;color:#222;}
    .admin-nav a.active{background:#2c7; color:#fff;}
    .admin-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
    .admin-card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:16px;}
    .admin-table{width:100%;border-collapse:collapse;}
    .admin-table th,.admin-table td{border-bottom:1px solid #eee;padding:10px;text-align:left;vertical-align:top;}
    .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#eee;}
    .badge.on{background:#2c7;color:#fff;}
    .badge.off{background:#aaa;color:#fff;}
    .row{display:flex;gap:12px;flex-wrap:wrap;}
    .col{flex:1;min-width:280px;}
    .input{width:90%;padding:10px;border:1px solid #ccc;border-radius:8px;}
    .textarea{width:90%;padding:10px;border:1px solid #ccc;border-radius:8px;min-height:110px;}
    .btn{display:inline-block;padding:10px 14px;border-radius:8px;border:none;cursor:pointer;text-decoration:none;margin-top:5px;}
    .btn-green{background:#2c7;color:#fff;}
    .btn-gray{background:#ddd;color:#222;}
    .btn-red{background:#e55;color:#fff;}
    .muted{color:#777;font-size:13px;}
  </style>
</head>
<body>
<div class="admin-wrap">
  <div class="admin-top">
    <div>
      <h2 style="margin:0;">관리자</h2>
      <div class="muted"><?= htmlspecialchars($me['username']) ?> 님</div>
    </div>
    <div>
      <a class="btn btn-gray" href="/uedu/index.php">사용자 사이트</a>
      <a class="btn btn-gray" href="/uedu/logout.php">로그아웃</a>
    </div>
  </div>

<nav class="admin-nav">
  <a href="/uedu/admin/index.php"
     class="<?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">
     대시보드
  </a>

  <a href="/uedu/admin/contents.php"
     class="<?= (basename($_SERVER['PHP_SELF']) === 'contents.php') ? 'active' : '' ?>">
     영상관리
  </a>

  <a href="/uedu/admin/curriculum.php"
     class="<?= (basename($_SERVER['PHP_SELF']) === 'curriculum.php') ? 'active' : '' ?>">
     커리큘럼
  </a>

  <a href="/uedu/admin/exams.php"
     class="<?= (basename($_SERVER['PHP_SELF']) === 'exams.php') ? 'active' : '' ?>">
     시험/평가
  </a>
  <a href="/uedu/admin/orders.php"
     class="<?= (basename($_SERVER['PHP_SELF']) === 'orders.php') ? 'active' : '' ?>">
     주문/결제
  </a>
  <a href="/uedu/admin/completions.php"
     class="<?= (basename($_SERVER['PHP_SELF']) === 'completions.php') ? 'active' : '' ?>">
     수료관리
  </a>
</nav>