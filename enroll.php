<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if ($course_id <= 0) {
    header('Location: ' . BASE_URL . '/courses.php');
    exit;
}

/* 이미 수강 중인지 확인 */
$stmt = db()->prepare("
    SELECT 1 FROM uedu_orders
    WHERE user_id=? AND course_id=? AND status='paid'
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
if ($stmt->fetchColumn()) {
    header('Location: ' . BASE_URL . '/classroom.php?course_id=' . $course_id);
    exit;
}

/* 무료 수강신청 = 주문 생성 */
$stmt = db()->prepare("
    INSERT INTO uedu_orders (user_id, course_id, amount, status, created_at)
    VALUES (?, ?, 0, 'paid', NOW())
");
$stmt->execute([$user_id, $course_id]);

header('Location: ' . BASE_URL . '/myroom.php');
exit;
