<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';

// 회원 검색 및 목록 조회
$q = $_GET['q'] ?? '';
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
if ($q) {
    $where .= " AND (username LIKE ? OR name LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

// 전체 수
$stmt = db()->prepare("SELECT COUNT(*) FROM uedu_users $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// 목록
$stmt = db()->prepare("SELECT * FROM uedu_users $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

// 회원 수정/삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];
    $u_id = intval($_POST['user_id']);
    
    if ($mode === 'update') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        
        $sql = "UPDATE uedu_users SET name=?, phone=?, role=? WHERE id=?";
        $args = [$name, $phone, $role, $u_id];
        
        // 비밀번호 변경 요청이 있을 경우
        if (!empty($_POST['new_password'])) {
            $sql = "UPDATE uedu_users SET name=?, phone=?, role=?, password=? WHERE id=?";
            $args = [$name, $phone, $role, password_hash($_POST['new_password'], PASSWORD_DEFAULT), $u_id];
        }
        
        db()->prepare($sql)->execute($args);
    }
    header("Location: users.php?page=$page&q=" . urlencode($q));
    exit;
}
?>

<div class="admin-card">
    <h3 style="margin-top:0;">회원 관리</h3>
    
    <form method="GET" style="margin-bottom:20px; display:flex; gap:10px;">
        <input class="input" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="아이디 또는 이름 검색" style="max-width:300px;">
        <button class="btn btn-gray">검색</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>아이디</th>
                <th>이름</th>
                <th>연락처</th>
                <th>권한</th>
                <th>가입일</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($users as $u): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="mode" value="update">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><input class="input" name="name" value="<?= htmlspecialchars($u['name']) ?>" style="width:100px; padding:4px;"></td>
                    <td><input class="input" name="phone" value="<?= htmlspecialchars($u['phone']) ?>" style="width:120px; padding:4px;"></td>
                    <td>
                        <select name="role" style="padding:4px;">
                            <option value="student" <?= $u['role']=='student'?'selected':''?>>학생</option>
                            <option value="admin" <?= $u['role']=='admin'?'selected':''?>>관리자</option>
                        </select>
                    </td>
                    <td><?= substr($u['created_at'], 0, 10) ?></td>
                    <td>
                        <input class="input" name="new_password" placeholder="비번변경시입력" style="width:120px; padding:4px;">
                        <button class="btn btn-green" style="padding:4px 8px; font-size:12px;">저장</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top:20px; text-align:center;">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($q) ?>" class="btn <?= $i==$page ? 'btn-green':'btn-gray' ?>" style="padding:4px 8px;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php require __DIR__ . '/_admin_footer.php'; ?>