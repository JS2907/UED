<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';

$user_id  = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);
if ($course_id <= 0) {
    echo "<div class='container'>잘못된 접근입니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 수강권(결제완료) 확인 */
$stmt = db()->prepare("
    SELECT 1
    FROM uedu_orders
    WHERE user_id=? AND course_id=? AND status='paid'
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
if (!$stmt->fetchColumn()) {
    echo "<div class='container'>수강 권한이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 과정 정보 */
$stmt = db()->prepare("SELECT id, title, sequential_learning FROM uedu_courses WHERE id=?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    echo "<div class='container'>과정이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 커리큘럼 + 내 진도 (차시별) */
$stmt = db()->prepare("
    SELECT
        uc.chapter_order,
        c.id AS content_id,
        c.title AS content_title,
        c.duration,
        IFNULL(p.is_completed, 0) AS is_completed
    FROM uedu_curriculum uc
    JOIN uedu_contents c ON c.id = uc.content_id
    LEFT JOIN uedu_progress p
      ON p.user_id = ?
     AND p.course_id = ?
     AND p.content_id = c.id
    WHERE uc.course_id = ?
    ORDER BY uc.chapter_order ASC
");
$stmt->execute([$user_id, $course_id, $course_id]);
$list = $stmt->fetchAll();

/* 잠금 로직 계산 */
$canStudy = true;
$sequential = intval($course['sequential_learning']) === 1;
?>

<div class="container">
    <h2 class="page-title"><?= htmlspecialchars($course['title']) ?> 강의실</h2>

    <?php if (empty($list)): ?>
        <p style="color:#666;">등록된 차시가 없습니다. (관리자에서 커리큘럼을 구성하세요)</p>
    <?php else: ?>
        <table class="board-table">
            <thead>
                <tr>
                    <th>차시</th>
                    <th>과목</th>
                    <th>길이</th>
                    <th>상태</th>
                    <th>학습</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $row): ?>
                <?php
                    $completed = intval($row['is_completed']) === 1;
                    $locked = ($sequential && !$completed && !$canStudy);
                ?>
                <tr>
                    <td><?= intval($row['chapter_order']) ?>차시</td>
                    <td><?= htmlspecialchars($row['content_title']) ?></td>
                    <td><?= intval($row['duration']) ?>초</td>
                    <td>
                        <?= $completed
                            ? '<span style="color:green;">완료</span>'
                            : '<span style="color:#999;">미완료</span>' ?>
                    </td>
                    <td>
                        <?php if ($locked): ?>
                            <span style="color:#aaa;">이전 차시 완료 필요</span>
                        <?php else: ?>
                            <a class="btn btn-green"
                               href="watch.php?course_id=<?= $course_id ?>&content_id=<?= intval($row['content_id']) ?>">
                               학습하기
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                    if (!$completed) {
                        $canStudy = false;
                    }
                ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <a class="btn btn-gray" href="myroom.php">← 나의강의실</a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
