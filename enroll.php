<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if ($course_id <= 0) {
    header('Location: ' . BASE_URL . '/courses.php');
    exit;
}

$stmt = db()->prepare("
    SELECT id, title, price
    FROM uedu_courses
    WHERE id=?
    LIMIT 1
");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    header('Location: ' . BASE_URL . '/courses.php');
    exit;
}

/* 이미 수강 중인지 확인 */
$stmt = db()->prepare("
    SELECT id, status
    FROM uedu_orders
    WHERE user_id=? AND course_id=?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
$order = $stmt->fetch();
if ($order && $order['status'] === 'paid') {
    header('Location: ' . BASE_URL . '/classroom.php?course_id=' . $course_id);
    exit;
}

$price = intval($course['price']);

/* 무료 수강신청 = 즉시 완료 */
if ($price <= 0) {
    if (!$order) {
        $stmt = db()->prepare("
            INSERT INTO uedu_orders (user_id, course_id, amount, status, created_at)
            VALUES (?, ?, 0, 'paid', NOW())
        ");
        $stmt->execute([$user_id, $course_id]);
    }
    header('Location: ' . BASE_URL . '/myroom.php');
    exit;
}

/* 유료 과정: 입금대기 주문 생성 */
if (!$order) {
    $stmt = db()->prepare("
        INSERT INTO uedu_orders (user_id, course_id, amount, status, payment_key, created_at)
        VALUES (?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([$user_id, $course_id, $price, 'BANK_' . time()]);
}
?>

<div class="container">
    <h2 class="page-title">수강신청 - 입금 안내</h2>

    <div class="board-view">
        <h3 style="margin-top:0;"><?= htmlspecialchars($course['title']) ?></h3>
        <p>결제 방식은 <strong>계좌이체</strong>만 지원합니다.</p>

        <div style="background:#f9f9f9;padding:16px;border-radius:8px;margin:16px 0;">
            <div><strong>입금 계좌</strong></div>
            <div style="margin-top:6px;">국민은행 123-456-789012 (예금주: UEDU)</div>
            <div style="margin-top:6px;">입금 금액: <?= number_format($price) ?>원</div>
        </div>

        <p style="color:#666;">입금 완료 후 관리자가 확인하면 상태가 <strong>정상</strong>으로 변경됩니다.</p>
        <p style="color:#666;">현재 상태: <span class="badge">입금대기</span></p>

        <div style="margin-top:20px;">
            <a class="btn btn-gray" href="<?= BASE_URL ?>/myroom.php">나의강의실로</a>
            <a class="btn btn-green" style="margin-left:8px;" href="<?= BASE_URL ?>/courses.php">다른 과정 보기</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
