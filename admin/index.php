<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';

$cntCourses = db()->query("SELECT COUNT(*) FROM uedu_courses")->fetchColumn();
$cntActive  = db()->query("SELECT COUNT(*) FROM uedu_courses WHERE is_active=1")->fetchColumn();
?>
<div class="admin-card">
  <h3 style="margin-top:0;">요약</h3>
  <div class="row">
    <div class="col admin-card">
      <div class="muted">전체 강의</div>
      <div style="font-size:28px;font-weight:700;"><?= intval($cntCourses) ?></div>
    </div>
    <div class="col admin-card">
      <div class="muted">판매/노출 중</div>
      <div style="font-size:28px;font-weight:700;"><?= intval($cntActive) ?></div>
    </div>
  </div>
  <div style="margin-top:16px;">
    <a class="btn btn-green" href="/uedu/admin/course_edit.php">+ 강의 등록</a>
    <a class="btn btn-gray" href="/uedu/admin/courses.php" style="margin-left:8px;">강의 목록</a>
  </div>
</div>
<?php require __DIR__ . '/_admin_footer.php'; ?>
