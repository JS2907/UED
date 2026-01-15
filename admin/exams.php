<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';
require_once __DIR__ . '/../db_conn.php';
require_once __DIR__ . '/../exam_schema.php';

ensure_exam_schema();

$message = '';
$error = '';

function parse_question_csv(string $filePath): array {
    $rows = [];
    if (!is_readable($filePath)) {
        return $rows;
    }

    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return $rows;
    }

    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 6) {
            continue;
        }

        $question = trim($data[0] ?? '');
        $choice1 = trim($data[1] ?? '');
        $choice2 = trim($data[2] ?? '');
        $choice3 = trim($data[3] ?? '');
        $choice4 = trim($data[4] ?? '');
        $answerRaw = trim($data[5] ?? '');
        $scoreRaw = trim($data[6] ?? '');

        if ($question === '' || $choice1 === '' || $choice2 === '' || $choice3 === '' || $choice4 === '') {
            continue;
        }

        if (!is_numeric($answerRaw)) {
            if (stripos($answerRaw, 'answer') !== false || stripos($answerRaw, '정답') !== false) {
                continue;
            }
        }

        $answer = intval($answerRaw);
        if ($answer < 1 || $answer > 4) {
            continue;
        }

        $score = is_numeric($scoreRaw) ? max(1, intval($scoreRaw)) : 1;

        $rows[] = [
            'question' => $question,
            'choice1' => $choice1,
            'choice2' => $choice2,
            'choice3' => $choice3,
            'choice4' => $choice4,
            'answer' => $answer,
            'score' => $score
        ];
    }

    fclose($handle);
    return $rows;
}

function insert_questions(int $bankId, array $rows): int {
    if (empty($rows)) {
        return 0;
    }

    $stmt = db()->prepare("
        INSERT INTO uedu_questions
            (bank_id, question_text, choice1, choice2, choice3, choice4, correct_answer, score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $count = 0;
    foreach ($rows as $row) {
        $stmt->execute([
            $bankId,
            $row['question'],
            $row['choice1'],
            $row['choice2'],
            $row['choice3'],
            $row['choice4'],
            $row['answer'],
            $row['score']
        ]);
        $count++;
    }

    return $count;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_bank'])) {
        $title = trim($_POST['bank_title'] ?? '');
        $description = trim($_POST['bank_description'] ?? '');
        $file = $_FILES['bank_file'] ?? null;

        if ($title === '') {
            $error = '문제은행명을 입력하세요.';
        } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $error = '엑셀(CSV) 파일을 업로드하세요.';
        } else {
            $stmt = db()->prepare("
                INSERT INTO uedu_question_banks (title, description)
                VALUES (?, ?)
            ");
            $stmt->execute([$title, $description]);
            $bankId = intval(db()->lastInsertId());

            $rows = parse_question_csv($file['tmp_name']);
            $inserted = insert_questions($bankId, $rows);
            $message = "문제은행이 등록되었습니다. (문항 {$inserted}개)";
        }
    }

    if (isset($_POST['append_questions'])) {
        $bankId = intval($_POST['bank_id'] ?? 0);
        $file = $_FILES['append_file'] ?? null;
        if ($bankId <= 0) {
            $error = '문제은행을 선택하세요.';
        } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $error = '엑셀(CSV) 파일을 업로드하세요.';
        } else {
            $rows = parse_question_csv($file['tmp_name']);
            $inserted = insert_questions($bankId, $rows);
            $message = "문항이 추가되었습니다. (문항 {$inserted}개)";
        }
    }

    if (isset($_POST['create_exam'])) {
        $courseId = intval($_POST['course_id'] ?? 0);
        $bankId = intval($_POST['exam_bank_id'] ?? 0);
        $title = trim($_POST['exam_title'] ?? '');
        $questionCount = max(1, intval($_POST['question_count'] ?? 1));
        $passScore = max(0, intval($_POST['pass_score'] ?? 0));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($courseId <= 0 || $bankId <= 0 || $title === '') {
            $error = '시험 정보를 모두 입력하세요.';
        } else {
            $stmt = db()->prepare("
                INSERT INTO uedu_exams (course_id, title, bank_id, question_count, pass_score, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$courseId, $title, $bankId, $questionCount, $passScore, $isActive]);
            $examId = intval(db()->lastInsertId());

            $countStmt = db()->prepare("SELECT COUNT(*) FROM uedu_questions WHERE bank_id=?");
            $countStmt->execute([$bankId]);
            $available = intval($countStmt->fetchColumn());
            $finalCount = min($questionCount, $available);

            $questionStmt = db()->prepare("
                SELECT id
                FROM uedu_questions
                WHERE bank_id=?
                ORDER BY RAND()
                LIMIT {$finalCount}
            ");
            $questionStmt->execute([$bankId]);
            $questionIds = $questionStmt->fetchAll();

            $insert = db()->prepare("
                INSERT INTO uedu_exam_questions (exam_id, question_id, question_order)
                VALUES (?, ?, ?)
            ");

            $order = 1;
            foreach ($questionIds as $q) {
                $insert->execute([$examId, intval($q['id']), $order]);
                $order++;
            }

            $message = "시험이 생성되었습니다. (출제 {$finalCount}문항)";
        }
    }
}

$banks = db()->query("
    SELECT b.id, b.title, b.description,
        (SELECT COUNT(*) FROM uedu_questions q WHERE q.bank_id=b.id) AS question_count
    FROM uedu_question_banks b
    ORDER BY b.id DESC
")->fetchAll();

$courses = db()->query("SELECT id, title FROM uedu_courses ORDER BY id DESC")->fetchAll();

$exams = db()->query("
    SELECT e.id, e.title, e.question_count, e.pass_score, e.is_active,
           c.title AS course_title, b.title AS bank_title
    FROM uedu_exams e
    JOIN uedu_courses c ON c.id = e.course_id
    JOIN uedu_question_banks b ON b.id = e.bank_id
    ORDER BY e.id DESC
")->fetchAll();
?>

<div class="admin-card">
  <h3 style="margin-top:0;">시험/평가 관리</h3>

  <?php if ($message): ?>
    <p style="color:#2c7;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p style="color:#e55;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <div class="row">
    <div class="col admin-card">
      <h4 style="margin-top:0;">문제은행 등록</h4>
      <form method="post" enctype="multipart/form-data">
        <div class="muted">문제은행명</div>
        <input class="input" type="text" name="bank_title" required>
        <div class="muted" style="margin-top:8px;">설명</div>
        <textarea class="textarea" name="bank_description"></textarea>
        <div class="muted" style="margin-top:8px;">엑셀(CSV) 업로드</div>
        <input class="input" type="file" name="bank_file" accept=".csv">
        <p class="muted" style="margin-top:6px;">
          CSV 컬럼: 문제, 보기1, 보기2, 보기3, 보기4, 정답(1~4), 배점(선택)
        </p>
        <button class="btn btn-green" name="create_bank" type="submit">문제은행 생성</button>
      </form>
    </div>

    <div class="col admin-card">
      <h4 style="margin-top:0;">기존 문제은행에 문항 추가</h4>
      <form method="post" enctype="multipart/form-data">
        <div class="muted">문제은행 선택</div>
        <select class="input" name="bank_id" required>
          <option value="">-- 선택 --</option>
          <?php foreach ($banks as $bank): ?>
            <option value="<?= $bank['id'] ?>">
              <?= htmlspecialchars($bank['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="muted" style="margin-top:8px;">엑셀(CSV) 업로드</div>
        <input class="input" type="file" name="append_file" accept=".csv">
        <button class="btn btn-gray" name="append_questions" type="submit">문항 추가</button>
      </form>
    </div>
  </div>

  <div class="admin-card" style="margin-top:20px;">
    <h4 style="margin-top:0;">시험 구성</h4>
    <form method="post" class="row">
      <div class="col">
        <div class="muted">과정</div>
        <select class="input" name="course_id" required>
          <option value="">-- 과정 선택 --</option>
          <?php foreach ($courses as $course): ?>
            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <div class="muted">시험명</div>
        <input class="input" type="text" name="exam_title" required>
      </div>
      <div class="col">
        <div class="muted">문제은행</div>
        <select class="input" name="exam_bank_id" required>
          <option value="">-- 선택 --</option>
          <?php foreach ($banks as $bank): ?>
            <option value="<?= $bank['id'] ?>">
              <?= htmlspecialchars($bank['title']) ?> (<?= intval($bank['question_count']) ?>문항)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <div class="muted">출제 문항 수</div>
        <input class="input" type="number" name="question_count" value="10" min="1">
      </div>
      <div class="col">
        <div class="muted">합격 점수</div>
        <input class="input" type="number" name="pass_score" value="60" min="0">
      </div>
      <div class="col" style="display:flex;align-items:flex-end;">
        <label style="display:flex;gap:6px;align-items:center;">
          <input type="checkbox" name="is_active" checked>
          <span class="muted">활성화</span>
        </label>
      </div>
      <div class="col" style="display:flex;align-items:flex-end;">
        <button class="btn btn-green" name="create_exam" type="submit">시험 생성</button>
      </div>
    </form>
  </div>

  <div class="admin-card" style="margin-top:20px;">
    <h4 style="margin-top:0;">등록된 시험</h4>
    <?php if (empty($exams)): ?>
      <p class="muted">등록된 시험이 없습니다.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>과정</th>
            <th>시험명</th>
            <th>문제은행</th>
            <th>문항수</th>
            <th>합격점수</th>
            <th>상태</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($exams as $exam): ?>
            <tr>
              <td><?= htmlspecialchars($exam['course_title']) ?></td>
              <td><?= htmlspecialchars($exam['title']) ?></td>
              <td><?= htmlspecialchars($exam['bank_title']) ?></td>
              <td><?= intval($exam['question_count']) ?>문항</td>
              <td><?= intval($exam['pass_score']) ?>점</td>
              <td>
                <?php if (intval($exam['is_active']) === 1): ?>
                  <span class="badge on">활성</span>
                <?php else: ?>
                  <span class="badge">비활성</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>