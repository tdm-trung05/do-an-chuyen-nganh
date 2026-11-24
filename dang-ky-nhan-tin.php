<?php
/** Đăng ký nhận tin (biểu mẫu ở chân trang): lưu email vào NHANTIN rồi quay lại trang đang xem. */
require_once __DIR__ . '/includes/bootstrap.php';

$back = safe_next(url(''));
if (!is_post()) {
    redirect($back);
}
if (!csrf_verify()) {
    flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
} else {
    $r = newsletter_subscribe((string) ($_POST['email'] ?? ''));
    flash_set($r['ok'] ? 'success' : 'danger', $r['msg']);
}
redirect($back);
