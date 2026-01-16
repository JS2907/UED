<?php
require_once __DIR__ . '/config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 여부에 따른 헤더 파일 로드
if (isset($_SESSION['user_id'])) {
    require __DIR__ . '/header_auth.php';
} else {
    require __DIR__ . '/header_static.php';
}
?>

<section class="hero">
    <div class="hero-slider">
        <div class="slide-item" style="background-image: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop');"></div>
        <div class="slide-item" style="background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2069&auto=format&fit=crop');"></div>
        <div class="slide-item" style="background-image: url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=2015&auto=format&fit=crop');"></div>
    </div>

    <div class="hero-overlay"></div>
    
    <div class="container hero-content">
        <h2 class="fade-up-1">
            Global Leader in<br>
            Safety Education
        </h2>
        <p class="fade-up-2">
            체계적인 커리큘럼과 전문적인 교육 시스템으로<br>
            대한민국 안전 교육의 새로운 기준을 제시합니다.
        </p>
        <div class="hero-btns fade-up-3">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a class="btn btn-outline-white" href="<?= BASE_URL ?>/courses.php">교육과정 안내</a>
            <?php else: ?>
                <a class="btn btn-outline-white" href="<?= BASE_URL ?>/myroom.php">나의 강의실</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="scroll-down fade-up-3">
        <span>SCROLL DOWN</span>
        <div class="mouse-icon"></div>
    </div>
</section>

<section class="section" style="background-color: #fff;">
    <div class="container">
        <div class="section-header">
            <h3 class="section-title">UEDU VISION</h3>
            <p class="section-desc">우리는 최고의 교육 경험을 제공하기 위해 끊임없이 노력합니다.</p>
        </div>
        
        <div class="course-grid">
            <div class="board-view" style="text-align:center; border:none; box-shadow:none; background:#f9f9f9;">
                <h4 style="font-size:22px; margin-bottom:15px; color:var(--primary-navy);">Professional</h4>
                <p style="color:#666;">현업 전문가들이 검증한<br>실무 중심의 커리큘럼</p>
            </div>
            <div class="board-view" style="text-align:center; border:none; box-shadow:none; background:#f9f9f9;">
                <h4 style="font-size:22px; margin-bottom:15px; color:var(--primary-navy);">Systematic</h4>
                <p style="color:#666;">체계적인 학습 관리 시스템과<br>데이터 기반 성과 분석</p>
            </div>
            <div class="board-view" style="text-align:center; border:none; box-shadow:none; background:#f9f9f9;">
                <h4 style="font-size:22px; margin-bottom:15px; color:var(--primary-navy);">Anywhere</h4>
                <p style="color:#666;">언제 어디서나 학습 가능한<br>온라인/모바일 최적화 환경</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background-color: var(--bg-light);">
    <div class="container">
        <div class="section-header">
            <h3 class="section-title">POPULAR COURSES</h3>
            <p class="section-desc">현재 가장 인기 있는 교육 과정을 소개합니다.</p>
        </div>

        <?php
        require_once __DIR__ . '/db_conn.php';
        
        // 추천 강의 조회 (에러 방지용 try-catch 포함)
        $courses = [];
        try {
            $stmt = db()->query("
                SELECT * FROM uedu_courses 
                WHERE is_active = 1 AND is_featured = 1
                ORDER BY id DESC 
                LIMIT 3
            ");
            if ($stmt) $courses = $stmt->fetchAll();
        } catch (Exception $e) {
            // DB 컬럼이 없을 경우 무시 (빈 배열)
        }
        ?>

        <?php if(empty($courses)): ?>
            <div style="text-align:center; padding:60px 0; color:#999; border:1px dashed #ddd; border-radius:4px;">
                현재 등록된 추천 과정이 없습니다.
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $c): ?>
                    <div class="course-card" onclick="location.href='enroll.php?course_id=<?= $c['id'] ?>'">
                        <div class="card-img-wrap">
                            <?php if (!empty($c['thumbnail'])): ?>
                                <img src="<?= htmlspecialchars($c['thumbnail']) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
                            <?php else: ?>
                                <div class="no-img">NO IMAGE</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title"><?= htmlspecialchars($c['title']) ?></h4>
                            <p class="card-text" style="height:48px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                                <?= htmlspecialchars($c['description']) ?>
                            </p>
                            <div class="card-price">
                                <?= intval($c['price']) == 0 ? 'Free' : number_format($c['price']).' KRW' ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="text-align:center; margin-top:60px;">
            <a href="courses.php" class="btn btn-navy">전체 과정 보기</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layout_footer.php'; ?>