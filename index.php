<?php
require_once __DIR__ . '/config.php';
require __DIR__ . '/header_static.php';
?>

<div class="container">
    <h2 class="page-title">온라인 교육 플랫폼 UEDU</h2>

    <p>
        체계적인 커리큘럼과 차시별 학습 관리로<br>
        전문 교육 과정을 제공합니다.
    </p>

    <div style="margin-top:20px;">
        <a class="btn btn-green" href="<?= BASE_URL ?>/register.php">회원가입</a>
        <a class="btn btn-gray" href="<?= BASE_URL ?>/login.php">로그인</a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
