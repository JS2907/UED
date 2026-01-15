<?php
require __DIR__ . '/header_static.php';
require_once __DIR__ . '/db_conn.php';

/* 로그인 사용자 확인 (선택) */
$loggedIn = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? 0;

/* 과정 목록 */
$stmt = db()->query("
    SELECT id, title, description, price
    FROM uedu_courses
    ORDER BY id DESC
");
$courses = $stmt->fetchAll();

/* 내가 이미 신청한 과정 상태 */
$myOrders = [];
if ($loggedIn) {
    $stmt = db()->prepare("
        SELECT course_id, status
        FROM uedu_orders
        WHERE user_id=?
        ORDER BY id DESC
    ");
    $stmt->execute([$user_id]);
    foreach ($stmt->fetchAll() as $row) {
        $courseId = intval($row['course_id']);
        if (!isset($myOrders[$courseId])) {
            $myOrders[$courseId] = $row['status'];
        }
    }
}
?>

<div class="container">
    <h2 class="page-title">교육과정</h2>

    <table class="board-table">
        <thead>
            <tr>
                <th>과정명</th>
                <th>설명</th>
                <th>가격</th>
                <th>수강</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($courses as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['title']) ?></strong></td>
                <td><small><?= nl2br(htmlspecialchars($c['description'] ?? '')) ?></small></td>
                <td><?= intval($c['price']) ?>원</td>
                <td>
                    <?php if ($loggedIn && ($myOrders[$c['id']] ?? '') === 'paid'): ?>
                        <a class="btn btn-gray"
                           href="<?= BASE_URL ?>/classroom.php?course_id=<?= $c['id'] ?>">
                           강의실
                        </a>
                    <?php elseif ($loggedIn && ($myOrders[$c['id']] ?? '') === 'pending'): ?>
                        <a class="btn btn-gray"
                           href="<?= BASE_URL ?>/enroll.php?course_id=<?= $c['id'] ?>">
                           입금대기
                        </a>
                    <?php else: ?>
                        <a class="btn btn-green"
                           href="<?= BASE_URL ?>/enroll.php?course_id=<?= $c['id'] ?>">
                           수강신청
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
