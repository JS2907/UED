<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';

$id = intval($_GET['id'] ?? 0);

/* 기본값 */
$course = [
    'title' => '',
    'description' => '',
    'price' => 0,
    'sequential_learning' => 0,
    'prevent_skip' => 0 // [추가] 기본값
];

/* 수정 모드 */
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM uedu_courses WHERE id=?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();

    if (!$fetched) {
        echo "<div class='admin-card'>존재하지 않는 과정입니다.</div>";
        require __DIR__ . '/_admin_footer.php';
        exit;
    }
    // DB에 컬럼이 없는 경우를 대비해 병합
    $course = array_merge($course, $fetched);
}

/* 저장 처리 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = $_POST['description'] ?? '';
    $price = intval($_POST['price'] ?? 0);
    $seq   = isset($_POST['sequential_learning']) ? 1 : 0;
    $skip  = isset($_POST['prevent_skip']) ? 1 : 0; // [추가] 폼 값 수신

    if ($id > 0) {
        // [수정] prevent_skip 추가
        $stmt = db()->prepare("
            UPDATE uedu_courses
            SET title=?, description=?, price=?, sequential_learning=?, prevent_skip=?
            WHERE id=?
        ");
        $stmt->execute([$title, $desc, $price, $seq, $skip, $id]);
    } else {
        // [수정] prevent_skip 추가
        $stmt = db()->prepare("
            INSERT INTO uedu_courses
            (title, description, price, sequential_learning, prevent_skip, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $desc, $price, $seq, $skip]);
    }

    header('Location: ' . BASE_URL . '/admin/courses.php');
    exit;
}
?>

<div class="admin-card">
    <h3><?= $id > 0 ? '강의 수정' : '강의 등록' ?></h3>

    <form method="POST"
          action="<?= BASE_URL ?>/admin/course_edit.php<?= $id ? '?id='.$id : '' ?>">

        <div class="row">
            <div class="col">
                <div class="muted">강의명</div>
                <input class="input" name="title" required
                       value="<?= htmlspecialchars($course['title']) ?>">
            </div>
            <div class="col">
                <div class="muted">가격(원)</div>
                <input class="input" type="number" name="price" min="0"
                       value="<?= intval($course['price']) ?>">
            </div>
        </div>

        <div style="margin-top:12px;">
            <div class="muted">설명</div>
            <textarea class="textarea"
                      name="description"><?= htmlspecialchars($course['description']) ?></textarea>
        </div>

        <div style="margin-top:12px; display:flex; gap:20px;">
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="sequential_learning"
                    <?= intval($course['sequential_learning']) === 1 ? 'checked' : '' ?>>
                차시별 순차 학습 (이전 차시 완료 필수)
            </label>
            
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="prevent_skip"
                    <?= intval($course['prevent_skip'] ?? 0) === 1 ? 'checked' : '' ?>>
                스킵 방지 (안 본 구간 건너뛰기 금지)
            </label>
        </div>

        <div style="margin-top:16px;display:flex;gap:8px;">
            <button class="btn btn-green" name="save_course">저장</button>
            <a class="btn btn-gray" href="/uedu/admin/courses.php">취소</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>
