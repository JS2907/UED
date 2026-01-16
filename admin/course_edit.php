<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';

$id = intval($_GET['id'] ?? 0);

/* 초기값 */
$course = [
    'title' => '',
    'description' => '',
    'price' => 0,
    'sequential_learning' => 0,
    'prevent_skip' => 0,
    'is_active' => 0,
    'is_featured' => 0,
    'thumbnail' => '' // [추가]
];

/* 수정 시 데이터 로드 */
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM uedu_courses WHERE id=?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) $course = array_merge($course, $fetched);
}

/* 저장 로직 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = $_POST['description'] ?? '';
    $price = intval($_POST['price'] ?? 0);
    $seq   = isset($_POST['sequential_learning']) ? 1 : 0;
    $skip  = isset($_POST['prevent_skip']) ? 1 : 0;
    $active= isset($_POST['is_active']) ? 1 : 0;
    $featured = isset($_POST['is_featured']) ? 1 : 0;

    // [추가] 파일 업로드 처리
    $thumbnailPath = $course['thumbnail']; // 기존 값 유지
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['thumbnail']['tmp_name'];
        $fName   = $_FILES['thumbnail']['name'];
        $ext     = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
        
        // 이미지 확장자 체크
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // 저장 폴더: /assets/uploads/ (폴더가 없으면 생성해야 함)
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFileName = 'course_' . time() . '_' . rand(100,999) . '.' . $ext;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($tmpName, $destPath)) {
                $thumbnailPath = '/uedu/assets/uploads/' . $newFileName; // DB 저장용 웹 경로
            }
        }
    }

    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE uedu_courses
            SET title=?, description=?, price=?, sequential_learning=?, prevent_skip=?, 
                is_active=?, is_featured=?, thumbnail=?
            WHERE id=?
        ");
        $stmt->execute([$title, $desc, $price, $seq, $skip, $active, $featured, $thumbnailPath, $id]);
    } else {
        $stmt = db()->prepare("
            INSERT INTO uedu_courses
            (title, description, price, sequential_learning, prevent_skip, is_active, is_featured, thumbnail, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $desc, $price, $seq, $skip, $active, $featured, $thumbnailPath]);
    }

    header('Location: ' . BASE_URL . '/admin/courses.php');
    exit;
}
?>

<div class="admin-card">
    <h3><?= $id > 0 ? '강의 수정' : '강의 등록' ?></h3>

    <form method="POST" enctype="multipart/form-data" 
          action="<?= BASE_URL ?>/admin/course_edit.php<?= $id ? '?id='.$id : '' ?>">
        
        <div style="background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #eee;">
            <label style="display:flex; align-items:center; gap:10px; font-weight:bold; cursor:pointer; margin-bottom:10px;">
                <input type="checkbox" name="is_active" <?= intval($course['is_active']) ? 'checked' : '' ?>>
                <span style="color:var(--primary-color);">사이트에 노출 (판매 시작)</span>
            </label>
            <label style="display:flex; align-items:center; gap:10px; font-weight:bold; cursor:pointer;">
                <input type="checkbox" name="is_featured" <?= intval($course['is_featured']) ? 'checked' : '' ?>>
                <span style="color:#ff9800;">메인 페이지 '인기 과정' 섹션에 고정 노출</span>
            </label>
        </div>

        <div class="row">
            <div class="col">
                <div class="muted">강의명</div>
                <input class="input" name="title" required value="<?= htmlspecialchars($course['title']) ?>">
            </div>
            <div class="col">
                <div class="muted">가격(원)</div>
                <input class="input" type="number" name="price" min="0" value="<?= intval($course['price']) ?>">
            </div>
        </div>

        <div style="margin-top:12px;">
            <div class="muted">강의 표지 이미지 (썸네일)</div>
            <input class="input" type="file" name="thumbnail" accept="image/*">
            <?php if (!empty($course['thumbnail'])): ?>
                <div style="margin-top:10px;">
                    <span class="muted">현재 이미지:</span><br>
                    <img src="<?= $course['thumbnail'] ?>" alt="Thumbnail" style="height:100px; border-radius:4px; border:1px solid #ddd; margin-top:5px;">
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:12px;">
            <div class="muted">설명</div>
            <textarea class="textarea" name="description" rows="5"><?= htmlspecialchars($course['description']) ?></textarea>
        </div>

        <div style="margin-top:20px; display:flex; gap:20px; padding:15px; border:1px solid #eee; border-radius:8px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="sequential_learning" <?= intval($course['sequential_learning']) ? 'checked' : '' ?>>
                <span>순차 학습</span>
            </label>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="prevent_skip" <?= intval($course['prevent_skip']) ? 'checked' : '' ?>>
                <span>스킵 방지</span>
            </label>
        </div>

        <div style="margin-top:20px; display:flex; gap:8px;">
            <button class="btn btn-green" name="save_course">저장하기</button>
            <a class="btn btn-gray" href="/uedu/admin/courses.php">취소</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>