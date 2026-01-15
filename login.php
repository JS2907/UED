<?php
$BASE_URL = '/uedu';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_conn.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("
        SELECT id, password
        FROM uedu_users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: {$BASE_URL}/myroom.php");
        exit;
    } else {
        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/style.css">
</head>
<body>

<div class="container" style="max-width:400px;">
    <h2 class="page-title">로그인</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input class="input" name="username" placeholder="아이디" required>
        <input class="input" type="password" name="password" placeholder="비밀번호" required style="margin-top:10px;">
        <button class="btn btn-green" style="margin-top:15px;width:100%;">로그인</button>
        <a href="/uedu/register.php">회원가입</a>
    </form>
</div>

</body>
</html>
