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

    // [수정] name, role 정보까지 함께 조회
    $stmt = db()->prepare("
        SELECT id, username, password, name, role
        FROM uedu_users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // [수정] 세션에 주요 정보 캐싱 (DB 접속 최소화 목적)
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['role']      = $user['role'];
        
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

<div class="container" style="max-width:400px; margin-top:100px;">
    <div class="login-box">
        <h2 class="page-title" style="text-align:center; margin-bottom:20px;">LOGIN</h2>

        <?php if ($error): ?>
            <p style="color:#ff4444; text-align:center; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input class="input" name="username" placeholder="ID" required>
            <input class="input" type="password" name="password" placeholder="PASSWORD" required style="margin-top:15px;">
            <button class="btn btn-green" style="margin-top:25px; width:100%;">로그인</button>
            <div style="text-align:center; margin-top:15px;">
                <a href="/uedu/register.php" style="color:#888; font-size:13px;">계정이 없으신가요? 회원가입</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>