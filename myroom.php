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
?>

<div class="container">
    <h2 class="page-title">나의강의실</h2>

    <?php if (empty($courses)): ?>
        <p style="color:#666;">구매(수강) 중인 과정이 없습니다.</p>
    <?php else: ?>
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
