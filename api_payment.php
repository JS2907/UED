<?php
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

try {
    // 1. 이미 신청한 내역이 있는지 확인
    $check = db()->prepare("SELECT id FROM uedu_orders WHERE user_id = ? AND course_id = ?");
    $check->execute([$user_id, $data['course_id']]);
    if($check->fetch()) {
        echo json_encode(['success' => false, 'message' => '이미 신청한 강의입니다.']);
        exit;
    }

    // 2. 'pending' 상태로 주문 생성
    // payment_key는 무통장 입금이므로 임의의 식별자를 넣습니다.
    $stmt = db()->prepare("INSERT INTO uedu_orders (user_id, course_id, amount, status, payment_key) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->execute([$user_id, $data['course_id'], $data['amount'], 'BANK_' . time()]);␊
    ␊
    // 3. 진도율 테이블 초기화 (미리 만들어두되 수강은 watch.php에서 막힘)␊
    $stmt = db()->prepare("INSERT INTO uedu_progress (user_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $data['course_id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>