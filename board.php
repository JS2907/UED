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
 * 8. 게시글 목록 (페이징 및 검색 추가)
 *********************************************************/
$page = intval($_GET['page'] ?? 1);
$search = $_GET['search'] ?? '';
$limit = 15;
$offset = ($page - 1) * $limit;

// 검색 조건
$whereSql = "WHERE b.type = ?";
$params = [$type];
if ($search) {
    $whereSql .= " AND (b.title LIKE ? OR b.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 전체 개수
$countStmt = db()->prepare("SELECT COUNT(*) FROM uedu_boards b $whereSql");
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $limit);

// 목록 조회
$sql = "
    SELECT b.*, u.username, u.name
    FROM uedu_boards b
    LEFT JOIN uedu_users u ON b.user_id = u.id
    $whereSql
    ORDER BY b.id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();
?>

<div class="container">
    <h2 class="page-title">고객센터 - <?= htmlspecialchars($type_name) ?></h2>
    
    <form method="GET" style="text-align:right; margin-bottom:10px;">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="검색어 입력" class="input" style="width:200px; display:inline-block;">
        <button class="btn btn-gray">검색</button>
    </form>

    <table class="board-table">
        <tbody>
        <?php if(empty($list)): ?>
            <tr><td colspan="4" style="text-align:center; padding:30px;">게시글이 없습니다.</td></tr>
        <?php else: ?>
            <?php foreach($list as $row): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($row['title']) ?></strong>
                    <?php if($row['created_at'] > date('Y-m-d H:i:s', strtotime('-1 day'))): ?>
                        <span style="color:red; font-size:11px;">N</span>
                    <?php endif; ?>
                    </td>
                <td>
                    <?= htmlspecialchars($row['username']) ?> 
                    <?= !empty($row['name']) ? '('.htmlspecialchars($row['name']).')' : '' ?>
                </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:center; margin-top:20px;">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <a href="?type=<?= $type ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>" 
               class="btn <?= $i==$page ? 'btn-green':'btn-gray' ?>" style="padding:5px 10px;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
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
