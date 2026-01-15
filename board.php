<?php
/*********************************************************
 * 1. type 정의
 *********************************************************/
$type = $_GET['type'] ?? 'notice';
$allowed = ['notice', 'faq', 'qna', 'bill'];
if (!in_array($type, $allowed)) {
    $type = 'notice';
}

/*********************************************************
 * 2. type 이름
 *********************************************************/
$type_names = [
    'notice' => '공지사항',
    'faq'    => 'FAQ',
    'qna'    => '1:1문의',
    'bill'   => '계산서요청'
];
$type_name = $type_names[$type];

/*********************************************************
 * 3. 세션
 *********************************************************/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*********************************************************
 * 4. FAQ 캐시 (DB, 헤더 이전)
 *********************************************************/
if ($type === 'faq' && !isset($_SESSION['user_id'])) {
    $cacheFile = __DIR__ . '/cache/faq.html';
    if (is_file($cacheFile)) {
        readfile($cacheFile);
        exit;
    }
    ob_start();
}

/*********************************************************
 * 5. 헤더 분기
 *********************************************************/
if (in_array($type, ['notice', 'faq'])) {
    require __DIR__ . '/header_static.php';
} else {
    require __DIR__ . '/header_auth.php';
}

/*********************************************************
 * 6. DB 로딩
 *********************************************************/
require_once __DIR__ . '/db_conn.php';

/*********************************************************
 * 7. 게시글 저장 (qna / bill)
 *********************************************************/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_board']) &&
    in_array($type, ['qna', 'bill']) &&
    isset($_SESSION['user_id'])
) {
    $stmt = db()->prepare("
        INSERT INTO uedu_boards
        (type, title, content, user_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $type,
        $_POST['title'],
        $_POST['content'],
        $_SESSION['user_id']
    ]);

    header("Location: board.php?type={$type}");
    exit;
}

/*********************************************************
 * 8. 게시글 목록
 *********************************************************/
$stmt = db()->prepare("
    SELECT b.*, u.username
    FROM uedu_boards b
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.type = ?
    ORDER BY b.id DESC
    LIMIT 50
");
$stmt->execute([$type]);
$list = $stmt->fetchAll();
?>

<!-- ================= HTML ================= -->

<div class="container">
    <h2 class="page-title">고객센터 - <?= htmlspecialchars($type_name) ?></h2>

    <div style="margin-bottom:20px;">
        <a href="board.php?type=notice" class="btn <?= $type=='notice'?'btn-green':'btn-gray'?>">공지사항</a>
        <a href="board.php?type=faq" class="btn <?= $type=='faq'?'btn-green':'btn-gray'?>">FAQ</a>
        <a href="board.php?type=qna" class="btn <?= $type=='qna'?'btn-green':'btn-gray'?>">1:1문의</a>
        <a href="board.php?type=bill" class="btn <?= $type=='bill'?'btn-green':'btn-gray'?>">계산서요청</a>
    </div>

    <?php if(in_array($type, ['qna', 'bill']) && isset($_SESSION['user_id'])): ?>
    <div style="background:#f9f9f9; padding:15px; border-radius:5px; margin-bottom:30px;">
        <h4>문의하기</h4>
        <form method="POST">
            <input type="text" name="title" placeholder="제목" required style="width:100%; margin-bottom:10px;">
            <textarea name="content" placeholder="내용을 입력하세요" rows="3" style="width:100%;"></textarea>
            <button type="submit" name="save_board" class="btn">등록</button>
        </form>
    </div>
    <?php endif; ?>

    <table class="board-table">
        <thead>
            <tr>
                <th>제목</th>
                <th>작성자</th>
                <th>작성일</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($list as $row): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                    <small><?= nl2br(htmlspecialchars($row['content'])) ?></small>

                    <?php if(!empty($row['answer'])): ?>
                        <div style="background:#eef; padding:10px; margin-top:5px; border-radius:5px;">
                            └ <strong>[답변]</strong>
                            <?= nl2br(htmlspecialchars($row['answer'])) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                <td><?= substr($row['created_at'], 0, 10) ?></td>
                <td>
                    <?= !empty($row['answer'])
                        ? '<span style="color:green">답변완료</span>'
                        : '<span style="color:#999">대기중</span>' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require __DIR__ . '/layout_footer.php';

/*********************************************************
 * 9. FAQ 캐시 저장
 *********************************************************/
if ($type === 'faq' && isset($cacheFile)) {
    file_put_contents($cacheFile, ob_get_contents());
    ob_end_flush();
}
