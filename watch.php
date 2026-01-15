<?php
require __DIR__ . '/header_auth.php';
require_once __DIR__ . '/db_conn.php';

$user_id    = $_SESSION['user_id'];
$course_id  = intval($_GET['course_id'] ?? 0);
$content_id = intval($_GET['content_id'] ?? 0);

if ($course_id <= 0 || $content_id <= 0) {
    echo "<div class='container'>잘못된 접근입니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 수강권 확인 */
$stmt = db()->prepare("
    SELECT 1 FROM uedu_orders
    WHERE user_id=? AND course_id=? AND status='paid'
    LIMIT 1
");
$stmt->execute([$user_id, $course_id]);
if (!$stmt->fetchColumn()) {
    echo "<div class='container'>수강 권한이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 과정 옵션 (prevent_skip 추가 조회) */
$stmt = db()->prepare("SELECT title, sequential_learning, prevent_skip FROM uedu_courses WHERE id=?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    echo "<div class='container'>과정이 없습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 이 콘텐츠가 해당 과정 커리큘럼에 포함되는지 + 현재 차시 순서 */
$stmt = db()->prepare("
    SELECT uc.chapter_order, c.title, c.video_url, c.duration
    FROM uedu_curriculum uc
    JOIN uedu_contents c ON c.id = uc.content_id
    WHERE uc.course_id=? AND uc.content_id=?
    LIMIT 1
");
$stmt->execute([$course_id, $content_id]);
$content = $stmt->fetch();

if (!$content) {
    echo "<div class='container'>이 차시는 해당 과정에 포함되어 있지 않습니다.</div>";
    require __DIR__ . '/layout_footer.php';
    exit;
}

/* 선수차시 제한: 옵션 ON이면 이전 차시가 모두 완료되어야 함 */
if (intval($course['sequential_learning']) === 1) {
    $myOrder = intval($content['chapter_order']);

    $stmt = db()->prepare("
        SELECT COUNT(*) AS need_cnt
        FROM uedu_curriculum uc
        WHERE uc.course_id=? AND uc.chapter_order < ?
    ");
    $stmt->execute([$course_id, $myOrder]);
    $need = intval($stmt->fetchColumn());

    $stmt = db()->prepare("
        SELECT COUNT(*) AS done_cnt
        FROM uedu_curriculum uc
        JOIN uedu_progress p
          ON p.user_id=? AND p.course_id=? AND p.content_id=uc.content_id AND p.is_completed=1
        WHERE uc.course_id=? AND uc.chapter_order < ?
    ");
    $stmt->execute([$user_id, $course_id, $course_id, $myOrder]);
    $done = intval($stmt->fetchColumn());

    if ($need > 0 && $done < $need) {
        echo "<div class='container'>이전 차시를 먼저 완료해야 합니다.</div>";
        echo "<div class='container' style='margin-top:10px;'><a class='btn btn-gray' href='classroom.php?course_id={$course_id}'>강의실로</a></div>";
        require __DIR__ . '/layout_footer.php';
        exit;
    }
}

/* 내 진도 불러오기 */
$stmt = db()->prepare("
    SELECT last_position, is_completed
    FROM uedu_progress
    WHERE user_id=? AND course_id=? AND content_id=?
    LIMIT 1
");
$stmt->execute([$user_id, $course_id, $content_id]);
$progress = $stmt->fetch();

$last_position = intval($progress['last_position'] ?? 0);
$is_completed  = intval($progress['is_completed'] ?? 0);
?>

<div class="container">
    <h2 class="page-title"><?= htmlspecialchars($course['title']) ?> - <?= htmlspecialchars($content['title']) ?></h2>

    <div class="video-wrapper" style="max-width:900px;margin:0 auto;">
        <video id="videoPlayer" controls width="100%" controlsList="nodownload">
            <source src="<?= htmlspecialchars($content['video_url']) ?>" type="video/mp4">
            브라우저가 video 태그를 지원하지 않습니다.
        </video>
    </div>

    <div style="margin-top:12px;text-align:center;">
        <?= $is_completed ? "<span style='color:green;font-weight:bold;'>✔ 완료</span>" : "<span style='color:#666;'>▶ 학습 중</span>" ?>
    </div>

    <div style="margin-top:20px;text-align:center;">
        <a class="btn btn-gray" href="classroom.php?course_id=<?= $course_id ?>">← 강의실로</a>
    </div>
</div>

<script>
const video = document.getElementById('videoPlayer');
const courseId  = <?= $course_id ?>;
const contentId = <?= $content_id ?>;
const duration  = <?= intval($content['duration']) ?>;

// DB 설정값 가져오기 (1이면 true, 0이면 false)
const preventSkip = <?= intval($course['prevent_skip'] ?? 0) === 1 ? 'true' : 'false' ?>;

// 서버에 저장된 마지막 시청 위치
let maxWatchedTime = <?= $last_position ?>; 
let isCompleted = <?= $is_completed ?>;

/* 1. 로드 시 마지막 위치로 이동 */
video.addEventListener('loadedmetadata', () => {
  if (maxWatchedTime > 0 && maxWatchedTime < duration) {
    video.currentTime = maxWatchedTime;
  }
});

/* 2. 스킵(Seeking) 방지 로직 */
video.addEventListener('seeking', () => {
    // 1. 이미 완료했거나, 2. 스킵 방지 옵션이 꺼져있으면 허용
    if (isCompleted || !preventSkip) return;

    // 이동하려는 시간이 지금까지 본 최대 시간 + 1초(오차 허용)보다 크면 차단
    if (video.currentTime > maxWatchedTime + 1) {
        alert("이 과정은 학습하지 않은 구간으로 건너뛸 수 없습니다.");
        video.currentTime = maxWatchedTime; // 강제로 되돌림
    }
});

/* 3. 재생 중 최대 시청 위치 갱신 */
video.addEventListener('timeupdate', () => {
    if (!video.seeking && video.currentTime > maxWatchedTime) {
        maxWatchedTime = video.currentTime;
    }
});

/* 4. 5초마다 진도 저장 */
let t = null;
video.addEventListener('timeupdate', () => {
  if (t) return;
  t = setTimeout(() => {
    // 시청 중에는 완료(1)로 보내지 않고 위치만 저장
    saveProgress(Math.floor(maxWatchedTime), 0);
    t = null;
  }, 5000);
});

/* 일시정지 시 저장 */
video.addEventListener('pause', () => {
  saveProgress(Math.floor(video.currentTime), 0);
});

/* 5. 영상 종료 시 완료 처리 + 강의실 복귀 */
video.addEventListener('ended', () => {
  isCompleted = 1;
  saveProgress(duration || Math.floor(video.duration), 1).then(() => {
    alert("학습을 완료했습니다.");
    location.href = 'classroom.php?course_id=' + courseId;
  });
});

function saveProgress(position, completed) {
  return fetch('api_progress.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      course_id: courseId,
      content_id: contentId,
      position: position,
      completed: completed
    })
  }).catch(() => {});
}

/* (선택) 마우스 우클릭 방지 (다운로드 등 방지 목적) */
document.addEventListener('contextmenu', event => event.preventDefault());
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>