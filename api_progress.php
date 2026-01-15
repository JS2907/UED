<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$user_id   = $_SESSION['user_id'];
$course_id = intval($input['course_id'] ?? 0);
$content_id = intval($input['content_id'] ?? 0);
$position  = intval($input['position'] ?? 0);
$completed = intval($input['completed'] ?? 0) ? 1 : 0;

if ($course_id <= 0 || $content_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '잘못된 요청']);
    exit;
}

require_once __DIR__ . '/db_conn.php';

/* 기존 진도 확인 */
$stmt = db()->prepare("
    SELECT id
    FROM uedu_progress
    WHERE user_id=? AND course_id=? AND content_id=?
    LIMIT 1
");
$stmt->execute([$user_id, $course_id, $content_id]);
$exists = $stmt->fetch();

/* 저장 */
if ($exists) {
    $stmt = db()->prepare("
        UPDATE uedu_progress
        SET last_position=?,
            is_completed=?,
            updated_at=NOW()
        WHERE user_id=? AND course_id=? AND content_id=?
    ");
    $stmt->execute([$position, $completed, $user_id, $course_id, $content_id]);
} else {
    $stmt = db()->prepare("
        INSERT INTO uedu_progress
        (user_id, course_id, content_id, last_position, is_completed, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $course_id, $content_id, $position, $completed]);
}

echo json_encode([
    'success' => true,
    'course_id' => $course_id,
    'content_id' => $content_id,
    'position' => $position,
    'completed' => $completed
]);
