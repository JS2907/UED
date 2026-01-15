<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_conn.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $password === '') {
        $error = '모든 항목을 입력하세요.';
    } elseif ($password !== $password2) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        $stmt = db()->prepare("SELECT 1 FROM uedu_users WHERE username=?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = '이미 사용 중인 아이디입니다.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("
                INSERT INTO uedu_users (username, password, role, created_at)
                VALUES (?, ?, 'student', NOW())
            ");
            $stmt->execute([$username, $hash]);
            $success = '회원가입이 완료되었습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<div class="container" style="max-width:420px;">
    <h2 class="page-title">회원가입</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
        <a class="btn btn-green" href="<?= BASE_URL ?>/login.php">로그인</a>
    <?php else: ?>
        <form method="POST">
            <input class="input" name="username" placeholder="아이디" required>
            <input class="input" type="password" name="password" placeholder="비밀번호" required style="margin-top:10px;">
            <input class="input" type="password" name="password2" placeholder="비밀번호 확인" required style="margin-top:10px;">
            <button class="btn btn-green" style="margin-top:15px;width:100%;">회원가입</button>
        </form>
    <?php endif; ?>

    <div style="margin-top:15px;text-align:center;">
        <a href="<?= BASE_URL ?>/login.php">이미 계정이 있으신가요?</a>
    </div>
</div>

</body>
</html>
