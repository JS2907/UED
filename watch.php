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

/* 과정 옵션 */
$stmt = db()->prepare("SELECT title, sequential_learning FROM uedu_courses WHERE id=?");
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

/* 선수차시 제한: 옵션 ON이면 이전 차시가 모두 완료되어야 함 (우회 방지) */
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
        <video id="videoPlayer" controls width="100%">
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
const lastPos   = <?= $last_position ?>;

/* 마지막 위치로 이동 */
video.addEventListener('loadedmetadata', () => {
  if (lastPos > 0 && duration > 0 && lastPos < duration) {
    video.currentTime = lastPos;
  }
});

/* 5초마다 위치 저장 */
let t = null;
video.addEventListener('timeupdate', () => {
  if (t) return;
  t = setTimeout(() => {
    saveProgress(Math.floor(video.currentTime), 0);
    t = null;
  }, 5000);
});

/* 일시정지 시 저장 */
video.addEventListener('pause', () => {
  saveProgress(Math.floor(video.currentTime), 0);
});

/* 종료 시 완료 처리 + 강의실 복귀 */
video.addEventListener('ended', () => {
  saveProgress(duration || Math.floor(video.duration), 1).then(() => {
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
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
