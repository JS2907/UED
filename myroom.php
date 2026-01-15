<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';

$user_id = $_SESSION['user_id'];

/*
 * 내가 결제 완료한(=수강 중인) 과정 목록
 * uedu_courses에는 thumbnail 없음 (주의)
 */
$stmt = db()->prepare("
    SELECT DISTINCT
        c.id,
        c.title,
        c.description
    FROM uedu_orders o
    JOIN uedu_courses c ON c.id = o.course_id
    WHERE o.user_id = ?
      AND o.status = 'paid'
    ORDER BY c.id DESC
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

$stmt = db()->prepare("
    SELECT DISTINCT
        c.id,
        c.title,
        c.description,
        o.created_at
    FROM uedu_orders o
    JOIN uedu_courses c ON c.id = o.course_id
    WHERE o.user_id = ?
      AND o.status = 'pending'
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$pendingCourses = $stmt->fetchAll();
?>

<div class="container">
    <h2 class="page-title">나의강의실</h2>
    <?php if (empty($courses) && empty($pendingCourses)): ?>
        <p style="color:#666;">구매(수강) 중인 과정이 없습니다.</p>
    <?php endif; ?>

    <?php if (!empty($pendingCourses)): ?>
        <h3 style="margin-top:0;">입금대기</h3>
        <table class="board-table" style="margin-bottom:24px;">
            <thead>
                <tr>
                    <th>과정명</th>
                    <th>설명</th>
                    <th>상태</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingCourses as $course): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($course['title']) ?></strong>
                    </td>
                    <td>
                        <small><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></small>
                    </td>
                    <td>
                        <span class="badge">입금대기</span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($courses)): ?>
        <table class="board-table">
            <thead>
                <tr>
                    <th>과정명</th>
                    <th>설명</th>
                    <th>강의실</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($course['title']) ?></strong>
                    </td>
                    <td>
                        <small><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></small>
                    </td>
                    <td>
                        <a class="btn btn-green"
                           href="<?= BASE_URL ?>/classroom.php?course_id=<?= intval($course['id']) ?>">
                           강의실 입장
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
