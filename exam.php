<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/course_completion.php';

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if ($course_id <= 0) {
    echo "<div class='container'>잘못된 접근입니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

$stmt = db()->prepare("
    SELECT 1
    FROM uedu_orders
    WHERE user_id=? AND course_id=? AND status='paid'
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
if (!$stmt->fetchColumn()) {
    echo "<div class='container'>수강 권한이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

$courseStmt = db()->prepare("SELECT title FROM uedu_courses WHERE id=?");
$courseStmt->execute([$course_id]);
$course = $courseStmt->fetch();

$exam = get_active_exam($course_id);
if (!$exam) {
    echo "<div class='container'>활성화된 시험이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

$questionStmt = db()->prepare("
    SELECT q.id, q.question_text, q.choice1, q.choice2, q.choice3, q.choice4, q.correct_answer, q.score
    FROM uedu_exam_questions eq
    JOIN uedu_questions q ON q.id = eq.question_id
    WHERE eq.exam_id=?
    ORDER BY eq.question_order ASC
");
$questionStmt->execute([$exam['id']]);
$questions = $questionStmt->fetchAll();

$submitted = false;
$score = 0;
$totalScore = 0;
$requiredScore = get_completion_settings($course_id)['exam_required_score'];
$passed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($questions as $question) {
        $qid = intval($question['id']);
        $answer = intval($_POST['answer'][$qid] ?? 0);
        $totalScore += intval($question['score']);
        if ($answer === intval($question['correct_answer'])) {
            $score += intval($question['score']);
        }
    }

    $passed = $score >= $requiredScore;
    $stmt = db()->prepare("
        INSERT INTO uedu_exam_results (exam_id, user_id, score, passed)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([intval($exam['id']), $user_id, $score, $passed ? 1 : 0]);
    $submitted = true;

    $evaluation = evaluate_completion($user_id, $course_id);
    sync_completion_status($user_id, $course_id, $evaluation);
}
?>

<div class="container">
    <h2 class="page-title"><?= htmlspecialchars($course['title'] ?? '') ?> - <?= htmlspecialchars($exam['title']) ?></h2>

    <?php if ($submitted): ?>
        <div class="board-view">
            <h3 style="margin-top:0;">시험 결과</h3>
            <p>점수: <strong><?= intval($score) ?>점</strong> / <?= intval($totalScore) ?>점</p>
            <p>합격 기준: <?= intval($requiredScore) ?>점</p>
            <p>
                결과:
                <?php if ($passed): ?>
                    <span style="color:green;font-weight:bold;">합격</span>
                <?php else: ?>
                    <span style="color:#e55;font-weight:bold;">불합격</span>
                <?php endif; ?>
            </p>
            <a class="btn btn-gray" href="classroom.php?course_id=<?= $course_id ?>">강의실로</a>
        </div>
    <?php else: ?>
        <form method="post">
            <?php foreach ($questions as $index => $question): ?>
                <div class="board-view" style="margin-bottom:16px;">
                    <div style="font-weight:bold; margin-bottom:8px;">
                        <?= $index + 1 ?>. <?= htmlspecialchars($question['question_text']) ?>
                        (<?= intval($question['score']) ?>점)
                    </div>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php $choice = htmlspecialchars($question["choice{$i}"]); ?>
                        <label style="display:block;margin:6px 0;">
                            <input type="radio" name="answer[<?= intval($question['id']) ?>]" value="<?= $i ?>" required>
                            <?= $i ?>) <?= $choice ?>
                        </label>
                    <?php endfor; ?>
                </div>
            <?php endforeach; ?>
            <button class="btn btn-green" type="submit">시험 제출</button>
            <a class="btn btn-gray" style="margin-left:8px;" href="classroom.php?course_id=<?= $course_id ?>">취소</a>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>