<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';

$user_id   = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if ($course_id <= 0) {
    echo "<div class='container'>잘못된 접근입니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 수강권 확인 */
$stmt = db()->prepare("
    SELECT title
    FROM uedu_courses c
    JOIN uedu_orders o ON o.course_id = c.id
    WHERE o.user_id=? AND o.course_id=? AND o.status='paid'
");
$stmt->execute([$user_id, $course_id]);
$course = $stmt->fetch();

if (!$course) {
    echo "<div class='container'>수강 권한이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 전체 차시 수 */
$stmt = db()->prepare("
    SELECT COUNT(*) FROM uedu_curriculum WHERE course_id=?
");
$stmt->execute([$course_id]);
$total = intval($stmt->fetchColumn());

/* 완료 차시 수 */
$stmt = db()->prepare("
    SELECT COUNT(*) 
    FROM uedu_curriculum uc
    JOIN uedu_progress p
      ON p.content_id=uc.content_id
     AND p.user_id=? AND p.course_id=? AND p.is_completed=1
    WHERE uc.course_id=?
");
$stmt->execute([$user_id, $course_id, $course_id]);
$done = intval($stmt->fetchColumn());

if ($total === 0 || $done < $total) {
    echo "<div class='container'>아직 수료 조건을 충족하지 못했습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}
?>

<div class="container" style="text-align:center;">
    <h2 class="page-title">수료증</h2>

    <div style="border:2px solid #333;padding:40px;margin-top:20px;">
        <h3><?= htmlspecialchars($course['title']) ?></h3>
        <p>본 과정의 모든 차시를 성실히 이수하였음을 증명합니다.</p>

        <p style="margin-top:30px;">
            수강생: <strong><?= htmlspecialchars($user['username']) ?></strong>
        </p>

        <p>발급일: <?= date('Y-m-d') ?></p>
    </div>

    <div style="margin-top:20px;">
        <a class="btn btn-gray" href="myroom.php">나의강의실</a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
