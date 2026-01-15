<?php
require __DIR__ . '/_admin_guard.php';
require __DIR__ . '/_admin_header.php';

$status = $_GET['status'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id > 0) {
        $stmt = db()->prepare("SELECT status FROM uedu_orders WHERE id=?");
        $stmt->execute([$order_id]);
        $current = $stmt->fetchColumn();

        if ($current === 'pending') {
            $stmt = db()->prepare("UPDATE uedu_orders SET status='paid' WHERE id=?");
            $stmt->execute([$order_id]);
            header('Location: orders.php?status=confirmed');
            exit;
        }
        header('Location: orders.php?status=invalid');
        exit;
    }
    header('Location: orders.php?status=invalid');
    exit;
}

if ($status === 'confirmed') {
    $message = '입금 확인 처리되었습니다.';
} elseif ($status === 'invalid') {
    $message = '처리할 주문을 찾지 못했습니다.';
}

$stmt = db()->query("
    SELECT
        o.id,
        o.amount,
        o.status,
        o.created_at,
        u.username,
        c.title AS course_title
    FROM uedu_orders o
    JOIN uedu_users u ON u.id = o.user_id
    JOIN uedu_courses c ON c.id = o.course_id
    ORDER BY (o.status = 'pending') DESC, o.created_at DESC, o.id DESC
");
$orders = $stmt->fetchAll();
?>

<div class="admin-card">
  <h3 style="margin-top:0;">주문/결제 관리</h3>
  <?php if ($message): ?>
    <p style="color:#2c7;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
    <p class="muted">주문 내역이 없습니다.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>주문번호</th>
          <th>수강생</th>
          <th>과정</th>
          <th>금액</th>
          <th>상태</th>
          <th>신청일</th>
          <th>관리</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td>#<?= intval($order['id']) ?></td>
          <td><?= htmlspecialchars($order['username']) ?></td>
          <td><?= htmlspecialchars($order['course_title']) ?></td>
          <td><?= number_format(intval($order['amount'])) ?>원</td>
          <td>
            <?php if ($order['status'] === 'paid'): ?>
              <span class="badge on">정상</span>
            <?php else: ?>
              <span class="badge">입금대기</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
          <td>
            <?php if ($order['status'] === 'pending'): ?>
              <form method="post" style="margin:0;">
                <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
                <button class="btn btn-green" type="submit">입금확인</button>
              </form>
            <?php else: ?>
              <span class="muted">처리완료</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_admin_footer.php'; ?>