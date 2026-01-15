<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';

/* 과정 선택 */
$course_id = intval($_GET['course_id'] ?? 0);
$status = $_GET['status'] ?? '';

/* 영상 추가 */
if (isset($_POST['add_content'])) {
    $content_id = intval($_POST['content_id'] ?? 0);
    $order = intval($_POST['chapter_order'] ?? 1);

    if ($course_id > 0 && $content_id > 0 && $order > 0) {
        $maxOrderStmt = db()->prepare("
            SELECT COALESCE(MAX(chapter_order), 0)
            FROM uedu_curriculum
            WHERE course_id = ?
        ");
        $maxOrderStmt->execute([$course_id]);
        $maxOrder = intval($maxOrderStmt->fetchColumn());
        if ($order > $maxOrder + 1) {
            $order = $maxOrder + 1;
        }

        $existsStmt = db()->prepare("
            SELECT COUNT(*) FROM uedu_curriculum
            WHERE course_id = ? AND content_id = ?
        ");
        $existsStmt->execute([$course_id, $content_id]);
        $already = intval($existsStmt->fetchColumn()) > 0;

        if (!$already) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $shiftStmt = $pdo->prepare("
                    UPDATE uedu_curriculum
                    SET chapter_order = chapter_order + 1
                    WHERE course_id = ? AND chapter_order >= ?
                ");
                $shiftStmt->execute([$course_id, $order]);
                $shifted = $shiftStmt->rowCount() > 0;
                $pdo->prepare("
                    INSERT INTO uedu_curriculum (course_id, content_id, chapter_order)
                    VALUES (?, ?, ?)
                ")->execute([
                    $course_id,
                    $content_id,
                    $order
                ]);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                header("Location: curriculum.php?course_id=$course_id&status=invalid");
                exit;
            }
            $status = $shifted ? 'added_shifted' : 'added';
            header("Location: curriculum.php?course_id=$course_id&status=$status");
            exit;
        }
        header("Location: curriculum.php?course_id=$course_id&status=duplicate");
        exit;
    }
    header("Location: curriculum.php?course_id=$course_id&status=invalid");
    exit;
}

/* 순서 변경 */
if (isset($_POST['update_order'])) {
    $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
    $new_order = intval($_POST['new_order'] ?? 0);

    if ($course_id > 0 && $curriculum_id > 0 && $new_order > 0) {
        $maxOrderStmt = db()->prepare("
            SELECT COALESCE(MAX(chapter_order), 0)
            FROM uedu_curriculum
            WHERE course_id = ?
        ");
        $maxOrderStmt->execute([$course_id]);
        $maxOrder = intval($maxOrderStmt->fetchColumn());
        if ($new_order > $maxOrder) {
            $new_order = $maxOrder;
        }

        $currentStmt = db()->prepare("
            SELECT chapter_order
            FROM uedu_curriculum
            WHERE id = ? AND course_id = ?
        ");
        $currentStmt->execute([$curriculum_id, $course_id]);
        $current_order = intval($currentStmt->fetchColumn());
        if ($current_order <= 0) {
            header("Location: curriculum.php?course_id=$course_id&status=invalid");
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($new_order !== $current_order) {
                if ($new_order < $current_order) {
                    $shiftStmt = $pdo->prepare("
                        UPDATE uedu_curriculum
                        SET chapter_order = chapter_order + 1
                        WHERE course_id = ? AND chapter_order >= ? AND chapter_order < ?
                    ");
                    $shiftStmt->execute([$course_id, $new_order, $current_order]);
                } else {
                    $shiftStmt = $pdo->prepare("
                        UPDATE uedu_curriculum
                        SET chapter_order = chapter_order - 1
                        WHERE course_id = ? AND chapter_order > ? AND chapter_order <= ?
                    ");
                    $shiftStmt->execute([$course_id, $current_order, $new_order]);
                }
            }

            $pdo->prepare("
                UPDATE uedu_curriculum
                SET chapter_order = ?
                WHERE id = ? AND course_id = ?
            ")->execute([$new_order, $curriculum_id, $course_id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            header("Location: curriculum.php?course_id=$course_id&status=invalid");
            exit;
        }

        header("Location: curriculum.php?course_id=$course_id&status=updated");
        exit;
    }

    header("Location: curriculum.php?course_id=$course_id&status=invalid");
    exit;
}

/* 자동 정렬 */
if (isset($_POST['resequence'])) {
    if ($course_id > 0) {
        $stmt = db()->prepare("
            SELECT id
            FROM uedu_curriculum
            WHERE course_id = ?
            ORDER BY chapter_order ASC, id ASC
        ");
        $stmt->execute([$course_id]);
        $rows = $stmt->fetchAll();

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $order = 1;
            $updateStmt = $pdo->prepare("
                UPDATE uedu_curriculum
                SET chapter_order = ?
                WHERE id = ? AND course_id = ?
            ");
            foreach ($rows as $row) {
                $updateStmt->execute([$order, intval($row['id']), $course_id]);
                $order++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            header("Location: curriculum.php?course_id=$course_id&status=invalid");
            exit;
        }
        header("Location: curriculum.php?course_id=$course_id&status=resequenced");
        exit;
    }
    header("Location: curriculum.php?course_id=$course_id&status=invalid");
    exit;
}

/* 삭제 */
if (isset($_GET['remove'])) {
    db()->prepare("DELETE FROM uedu_curriculum WHERE id=?")
        ->execute([intval($_GET['remove'])]);
    header("Location: curriculum.php?course_id=$course_id&status=removed");
    exit;
}

/* 과정 목록 */
$courses = db()->query("
    SELECT id, title FROM uedu_courses ORDER BY id DESC
")->fetchAll();

/* 커리큘럼 */
$curriculum = [];
$availableContents = [];
$nextOrder = 1;
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

    $orderStmt = db()->prepare("
        SELECT COALESCE(MAX(chapter_order), 0) FROM uedu_curriculum
        WHERE course_id = ?
    ");
    $orderStmt->execute([$course_id]);
    $nextOrder = intval($orderStmt->fetchColumn()) + 1;

    $availableContents = db()->prepare("
        SELECT c.id, c.title
        FROM uedu_contents c
        WHERE NOT EXISTS (
            SELECT 1 FROM uedu_curriculum uc
            WHERE uc.course_id = ? AND uc.content_id = c.id
        )
        ORDER BY c.title
    ");
    $availableContents->execute([$course_id]);
    $availableContents = $availableContents->fetchAll();
}
?>

<div class="admin-card">
<h3>🧩 커리큘럼 구성</h3>

<?php if ($status === 'added'): ?>
    <p class="badge on">차시가 추가되었습니다.</p>
<?php elseif ($status === 'removed'): ?>
    <p class="badge off">차시가 삭제되었습니다.</p>
<?php elseif ($status === 'duplicate'): ?>
    <p class="badge off">이미 등록된 영상입니다.</p>
<?php elseif ($status === 'updated'): ?>
    <p class="badge on">차시 순서가 변경되었습니다.</p>
<?php elseif ($status === 'resequenced'): ?>
    <p class="badge on">차시 순서를 자동으로 정렬했습니다.</p>
<?php elseif ($status === 'order_conflict'): ?>
    <p class="badge off">이미 사용 중인 순서입니다. 다른 번호를 선택하세요.</p>
<?php elseif ($status === 'invalid'): ?>
    <p class="badge off">요청이 올바르지 않습니다.</p>
<?php endif; ?>

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

<?php if (count($availableContents) > 0): ?>
    <form method="POST" class="row">
        <div class="col">
            <div class="muted">영상</div>
            <select class="input" name="content_id" required>
                <?php foreach ($availableContents as $v): ?>
                    <option value="<?= $v['id'] ?>">
                        <?= htmlspecialchars($v['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
            <div class="muted">순서</div>
            <input class="input" type="number" name="chapter_order" value="<?= $nextOrder ?>" min="1">
        </div>
        <div class="col" style="display:flex;align-items:flex-end;">
            <button class="btn btn-green" name="add_content">추가</button>
        </div>
    </form>
<?php else: ?>
    <p class="muted" style="margin-bottom:0;">추가할 수 있는 영상이 없습니다.</p>
<?php endif; ?>

<div style="margin-top:16px;">
    <form method="POST">
        <button class="btn btn-gray" name="resequence">순서 자동 정렬</button>
    </form>
</div>

<table class="admin-table" style="margin-top:12px;">
<thead>
<tr><th>순서</th><th>영상</th><th>관리</th></tr>
</thead>
<tbody>
<?php if (count($curriculum) === 0): ?>
    <tr>
        <td colspan="3" class="muted">등록된 차시가 없습니다.</td>
    </tr>
<?php else: ?>
    <?php foreach ($curriculum as $r): ?>
    <tr>
        <td>
            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="curriculum_id" value="<?= $r['id'] ?>">
                <input class="input" style="max-width:110px;" type="number" min="1"
                       name="new_order" value="<?= $r['chapter_order'] ?>">
                <button class="btn btn-gray" name="update_order">변경</button>
            </form>
        </td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td>
            <a class="btn btn-red"
               href="?course_id=<?= $course_id ?>&remove=<?= $r['id'] ?>"
               onclick="return confirm('삭제할까요?')">삭제</a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<?php else: ?>
<p class="muted">과정을 선택하면 차시를 구성할 수 있습니다.</p>
<?php endif; ?>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>
