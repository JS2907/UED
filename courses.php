<?php
require_once __DIR__ . '/config.php';

// 세션 확인
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_conn.php';

/* 로그인 여부에 따라 헤더 분기 처리 */
if (isset($_SESSION['user_id'])) {
    require __DIR__ . '/header_auth.php';
    $loggedIn = true;
    $user_id = $_SESSION['user_id'];
} else {
    require __DIR__ . '/header_static.php';
    $loggedIn = false;
    $user_id = 0;
}

/* [수정] 과정 목록 조회 (thumbnail 컬럼 추가 필수!) */
$stmt = db()->query("
    SELECT id, title, description, price, thumbnail
    FROM uedu_courses
    WHERE is_active = 1
    ORDER BY id DESC
");
$courses = $stmt->fetchAll();

/* 내가 이미 신청한 과정 상태 확인 */
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
    <h2 class="page-title">수강신청 (교육과정)</h2>

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
                <td>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <?php if (!empty($c['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars($c['thumbnail']) ?>" 
                                 style="width:80px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #eee;">
                        <?php else: ?>
                            <div style="width:80px; height:50px; background:#eee; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#ccc; font-size:10px;">
                                NO IMG
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <strong><?= htmlspecialchars($c['title']) ?></strong>
                        </div>
                    </div>
                </td>
                <td><small><?= nl2br(htmlspecialchars($c['description'] ?? '')) ?></small></td>
                <td>
                    <?= intval($c['price']) > 0 
                        ? number_format(intval($c['price'])) . '원' 
                        : '<span style="color:green">무료</span>' ?>
                </td>
                <td>
                    <?php if ($loggedIn): ?>
                        <?php if (($myOrders[$c['id']] ?? '') === 'paid'): ?>
                            <a class="btn btn-gray" href="<?= BASE_URL ?>/classroom.php?course_id=<?= $c['id'] ?>">강의실 입장</a>
                        <?php elseif (($myOrders[$c['id']] ?? '') === 'pending'): ?>
                            <a class="btn btn-gray" href="<?= BASE_URL ?>/enroll.php?course_id=<?= $c['id'] ?>">입금 확인중</a>
                        <?php else: ?>
                            <a class="btn btn-green" href="<?= BASE_URL ?>/enroll.php?course_id=<?= $c['id'] ?>">수강신청</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="btn btn-green" href="<?= BASE_URL ?>/login.php" onclick="return confirm('로그인이 필요합니다. 로그인 페이지로 이동할까요?');">수강신청</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>