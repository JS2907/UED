<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';

/* 과정 선택 */
$course_id = intval($_GET['course_id'] ?? 0);

/* 영상 추가 */
if (isset($_POST['add_content'])) {
    db()->prepare("
        INSERT INTO uedu_curriculum (course_id, content_id, chapter_order)
        VALUES (?, ?, ?)
    ")->execute([
        $course_id,
        intval($_POST['content_id']),
        intval($_POST['chapter_order'])
    ]);
    header("Location: curriculum.php?course_id=$course_id");
    exit;
}

/* 삭제 */
if (isset($_GET['remove'])) {
    db()->prepare("DELETE FROM uedu_curriculum WHERE id=?")
        ->execute([intval($_GET['remove'])]);
    header("Location: curriculum.php?course_id=$course_id");
    exit;
}

/* 과정 목록 */
$courses = db()->query("
    SELECT id, title FROM uedu_courses ORDER BY id DESC
")->fetchAll();

/* 영상 목록 */
$contents = db()->query("
    SELECT id, title FROM uedu_contents ORDER BY title
")->fetchAll();

/* 커리큘럼 */
$curriculum = [];
if ($course_id) {
    $stmt = db()->prepare("
        SELECT uc.id, uc.chapter_order, c.title
        FROM uedu_curriculum uc
        JOIN uedu_contents c ON c.id = uc.content_id
        WHERE uc.course_id = ?
        ORDER BY uc.chapter_order ASC
    ");
    $stmt->execute([$course_id]);
    $curriculum = $stmt->fetchAll();
}
?>

<div class="admin-card">
<h3>🧩 커리큘럼 구성</h3>

<form method="GET">
    <div class="muted">과정 선택</div>
    <select class="input" name="course_id" onchange="this.form.submit()">
        <option value="">-- 과정 선택 --</option>
        <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>"
                <?= $course_id==$c['id']?'selected':'' ?>>
                <?= htmlspecialchars($c['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($course_id): ?>

<hr>

<form method="POST" class="row">
    <div class="col">
        <div class="muted">영상</div>
        <select class="input" name="content_id" required>
            <?php foreach ($contents as $v): ?>
                <option value="<?= $v['id'] ?>">
                    <?= htmlspecialchars($v['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col">
        <div class="muted">순서</div>
        <input class="input" type="number" name="chapter_order" value="1">
    </div>
    <div class="col" style="display:flex;align-items:flex-end;">
        <button class="btn btn-green" name="add_content">추가</button>
    </div>
</form>

<table class="admin-table" style="margin-top:20px;">
<thead>
<tr><th>순서</th><th>영상</th><th>관리</th></tr>
</thead>
<tbody>
<?php foreach ($curriculum as $r): ?>
<tr>
    <td><?= $r['chapter_order'] ?></td>
    <td><?= htmlspecialchars($r['title']) ?></td>
    <td>
        <a class="btn btn-red"
           href="?course_id=<?= $course_id ?>&remove=<?= $r['id'] ?>"
           onclick="return confirm('삭제할까요?')">삭제</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>
