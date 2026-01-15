<?php
require_once __DIR__ . '/config.php';

// 세션이 없으면 기본 헤더, 있으면 로그인 헤더
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    require __DIR__ . '/header_auth.php';
} else {
    require __DIR__ . '/header_static.php';
}
?>

<div class="hero">
    <div class="container" style="margin:0 auto;">
        <h2>성장의 시작, UEDU</h2>
        <p>
            체계적인 커리큘럼과 실시간 학습 관리로<br>
            당신의 커리어를 한 단계 업그레이드하세요.
        </p>
        <div class="hero-btns">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a class="btn" href="<?= BASE_URL ?>/register.php">무료로 시작하기</a>
                <a class="btn btn-outline" href="<?= BASE_URL ?>/courses.php">강의 둘러보기</a>
            <?php else: ?>
                <a class="btn" href="<?= BASE_URL ?>/myroom.php">나의 강의실</a>
                <a class="btn btn-outline" href="<?= BASE_URL ?>/courses.php">새로운 과정 찾기</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <div style="text-align:center; margin-bottom:40px;">
        <h3 style="font-size:24px; margin-bottom:10px;">인기 교육 과정</h3>
        <p style="color:#666;">지금 가장 많은 수강생이 선택한 강의입니다.</p>
    </div>

    <?php
    require_once __DIR__ . '/db_conn.php';
    $stmt = db()->query("SELECT * FROM uedu_courses WHERE is_active=1 ORDER BY id DESC LIMIT 3");
    $courses = $stmt->fetchAll();
    ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        <?php foreach ($courses as $c): ?>
            <div class="board-view" style="padding:24px; transition: transform 0.2s; cursor:pointer;" 
                 onclick="location.href='courses.php'">
                <div style="height:140px; background:#f0f0f0; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; justify-content:center; color:#ccc;">
                    <span>NO IMAGE</span>
                </div>
                <h4 style="font-size:18px; margin-bottom:8px;"><?= htmlspecialchars($c['title']) ?></h4>
                <p style="color:#666; font-size:14px; height:44px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                    <?= htmlspecialchars($c['description']) ?>
                </p>
                <div style="margin-top:16px; text-align:right; font-weight:bold; color:var(--primary-color);">
                    <?= number_format($c['price']) ?>원
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>