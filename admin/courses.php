<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';

// 등록된 모든 과정 조회
$stmt = db()->query("
    SELECT * FROM uedu_courses 
    ORDER BY id DESC
");
$courses = $stmt->fetchAll();
?>

<div class="admin-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0;">교육과정 관리</h3>
        <a href="course_edit.php" class="btn btn-green">+ 과정 등록</a>
    </div>

    <?php if (empty($courses)): ?>
        <p class="muted" style="padding:40px; text-align:center;">
            등록된 과정이 없습니다. 우측 상단의 '+ 과정 등록' 버튼을 눌러보세요.
        </p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>과정명</th>
                    <th>수강료</th>
                    <th>옵션</th>
                    <th style="width:150px;">등록일</th>
                    <th style="width:140px;">관리</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $c): ?>
                <tr>
                    <td><?= intval($c['id']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($c['title']) ?></strong>
                        <div class="muted" style="font-size:12px; margin-top:4px;">
                            <?= mb_strimwidth(htmlspecialchars($c['description']), 0, 50, '...') ?>
                        </div>
                    </td>
                    <td>
                        <?= intval($c['price']) == 0 ? '무료' : number_format($c['price']).'원' ?>
                    </td>
                    <td>
                        <?php if(intval($c['sequential_learning'] ?? 0)): ?>
                            <span class="badge on">순차학습</span>
                        <?php endif; ?>
                        <?php if(intval($c['prevent_skip'] ?? 0)): ?>
                            <span class="badge on">스킵방지</span>
                        <?php endif; ?>
                    </td>
                    <td><?= substr($c['created_at'], 0, 10) ?></td>
                    <td>
                        <a href="course_edit.php?id=<?= $c['id'] ?>" class="btn btn-gray" style="padding:4px 8px; font-size:12px;">수정</a>
                        <a href="course_delete.php?id=<?= $c['id'] ?>" 
                           class="btn btn-red" 
                           style="padding:4px 8px; font-size:12px;"
                           onclick="return confirm('정말 삭제하시겠습니까? \n삭제 시 관련 수강생 데이터가 꼬일 수 있습니다.')">삭제</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>