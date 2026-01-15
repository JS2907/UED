<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';

/* 삭제 */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    db()->prepare("DELETE FROM uedu_contents WHERE id=?")->execute([$id]);
    header('Location: contents.php');
    exit;
}

/* 저장 */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_content'])) {
    $id = intval($_POST['id'] ?? 0);

    $data = [
        trim($_POST['title']),
        trim($_POST['video_url']),
        intval($_POST['duration'])
    ];

    if ($id > 0) {
        db()->prepare("
            UPDATE uedu_contents
            SET title=?, video_url=?, duration=?
            WHERE id=?
        ")->execute([...$data, $id]);
    } else {
        db()->prepare("
            INSERT INTO uedu_contents (title, video_url, duration)
            VALUES (?, ?, ?)
        ")->execute($data);
    }

    header('Location: contents.php');
    exit;
}

/* 수정용 로딩 */
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare("SELECT * FROM uedu_contents WHERE id=?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit = $stmt->fetch();
}

/* 목록 */
$list = db()->query("
    SELECT * FROM uedu_contents
    ORDER BY id DESC
")->fetchAll();
?>

<div class="admin-card">
<h3>📹 영상 콘텐츠 관리</h3>

<form method="POST" style="margin-bottom:20px;">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

    <div class="row">
        <div class="col">
            <div class="muted">제목</div>
            <input class="input" name="title" required
                   value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
        </div>
        <div class="col">
            <div class="muted">영상 URL</div>
            <input class="input" name="video_url" required
                   value="<?= htmlspecialchars($edit['video_url'] ?? '') ?>">
        </div>
        <div class="col">
            <div class="muted">길이(초)</div>
            <input class="input" type="number" name="duration"
                   value="<?= intval($edit['duration'] ?? 0) ?>">
        </div>
    </div>

    <div style="margin-top:10px;">
        <button class="btn btn-green" name="save_content">
            <?= $edit ? '수정' : '등록' ?>
        </button>
        <?php if ($edit): ?>
            <a class="btn btn-gray" href="contents.php">취소</a>
        <?php endif; ?>
    </div>
</form>

<table class="admin-table">
<thead>
<tr>
    <th>ID</th><th>제목</th><th>길이</th><th>관리</th>
</tr>
</thead>
<tbody>
<?php foreach ($list as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['title']) ?></td>
    <td><?= intval($r['duration']) ?>초</td>
    <td>
        <a class="btn btn-gray" href="?edit=<?= $r['id'] ?>">수정</a>
        <a class="btn btn-red" href="?delete=<?= $r['id'] ?>"
           onclick="return confirm('삭제할까요?')">삭제</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>
