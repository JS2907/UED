<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';
require_once __DIR__ . '/../course_completion.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete'])) {
    $courseId = intval($_POST['course_id'] ?? 0);
    $userId = intval($_POST['user_id'] ?? 0);

    if ($courseId > 0 && $userId > 0) {
        upsert_completion_record($userId, $courseId, 'completed', 'manual');
        $message = '수료 처리가 완료되었습니다.';
    }
}

$enrollments = db()->query("
    SELECT o.user_id, o.course_id, u.username, c.title AS course_title
    FROM uedu_orders o
    JOIN uedu_users u ON u.id = o.user_id
    JOIN uedu_courses c ON c.id = o.course_id
    WHERE o.status='paid'
    ORDER BY o.course_id DESC, o.user_id DESC
")->fetchAll();

$pending = [];
$completed = [];

foreach ($enrollments as $row) {
    $courseId = intval($row['course_id']);
    $userId = intval($row['user_id']);
    $evaluation = evaluate_completion($userId, $courseId);
    $record = sync_completion_status($userId, $courseId, $evaluation);

    $data = [
        'course_id' => $courseId,
        'user_id' => $userId,
        'username' => $row['username'],
        'course_title' => $row['course_title'],
        'progress' => $evaluation['progress']['progress'],
        'exam_score' => $evaluation['exam_score'],
        'required_score' => $evaluation['required_score'],
        'exam_enabled' => $evaluation['exam_enabled'],
        'mode' => $evaluation['settings']['completion_mode'],
        'status' => $record['status'] ?? 'pending'
    ];

    if (($record['status'] ?? '') === 'completed') {
        $completed[] = $data;
    } elseif ($evaluation['meets_progress'] && $evaluation['meets_exam']) {
        $pending[] = $data;
    }
}
?>

<div class="admin-card">
  <h3 style="margin-top:0;">수료 관리</h3>
  <?php if ($message): ?>
    <p style="color:#2c7;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <div class="admin-card" style="margin-top:16px;">
    <h4 style="margin-top:0;">수료 대기 (수동 처리 대상)</h4>
    <?php if (empty($pending)): ?>
      <p class="muted">수료 대기 항목이 없습니다.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>수강생</th>
            <th>과정</th>
            <th>진도율</th>
            <th>시험점수</th>
            <th>관리</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($pending as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['course_title']) ?></td>
            <td><?= intval($row['progress']) ?>%</td>
            <td>
              <?php if ($row['exam_enabled']): ?>
                <?= $row['exam_score'] !== null ? intval($row['exam_score']) . '점' : '미응시' ?>
              <?php else: ?>
                <span class="muted">시험 없음</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="post">
                <input type="hidden" name="course_id" value="<?= intval($row['course_id']) ?>">
                <input type="hidden" name="user_id" value="<?= intval($row['user_id']) ?>">
                <button class="btn btn-green" type="submit" name="complete">수료 처리</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="admin-card" style="margin-top:16px;">
    <h4 style="margin-top:0;">수료 완료</h4>
    <?php if (empty($completed)): ?>
      <p class="muted">수료 완료된 수강생이 없습니다.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>수강생</th>
            <th>과정</th>
            <th>진도율</th>
            <th>시험점수</th>
            <th>처리방식</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($completed as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['course_title']) ?></td>
            <td><?= intval($row['progress']) ?>%</td>
            <td>
              <?php if ($row['exam_enabled']): ?>
                <?= $row['exam_score'] !== null ? intval($row['exam_score']) . '점' : '미응시' ?>
              <?php else: ?>
                <span class="muted">시험 없음</span>
              <?php endif; ?>
            </td>
            <td><?= $row['mode'] === 'manual' ? '수동' : '자동' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>