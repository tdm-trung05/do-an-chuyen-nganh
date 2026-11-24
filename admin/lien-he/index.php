<?php
/** Tin nhắn liên hệ từ biểu mẫu trên website (bảng LIENHE): xem, tìm, đánh dấu đã xử lý. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('lien-he');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $res = admin_contact_toggle((int) (admin_int($_POST['id'] ?? '') ?? 0));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(request_uri());
}

$q      = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['tt'] ?? '');
$filter = in_array($filter, ['Chưa xử lý', 'Đã xử lý'], true) ? $filter : '';
$list   = admin_contacts($q, $filter, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Tin nhắn liên hệ', 'lien-he', 'Tin nhắn khách gửi từ trang Liên hệ. Liên hệ lại qua email hoặc điện thoại rồi đánh dấu đã xử lý.');
$rows = [];
foreach ($list['rows'] as $m) {
    $done = (int) $m['DaXuLy'] === 1;
    $rows[] = [
        date('d/m/Y H:i', strtotime($m['NgayGui'])),
        raw('<strong>' . e($m['HoTen']) . '</strong><br><small class="text-muted">' . e($m['Email']) . ($m['SoDienThoai'] ? ' · ' . e($m['SoDienThoai']) : '') . '</small>'),
        raw(nl2br(e($m['NoiDung']))),
        raw(badge($done ? 'Đã xử lý' : 'Chưa xử lý')),
        raw('<form method="post" class="d-inline">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $m['MaLH'] . '">'
            . '<button class="btn btn-sm btn-outline-ink" type="submit">' . ($done ? 'Mở lại' : 'Đã xử lý') . '</button></form>'),
    ];
}
?>
<section class="card-box">
    <?php admin_filter_form('Tìm theo tên, email, số điện thoại, nội dung', $q, ['Chưa xử lý', 'Đã xử lý'], $filter); ?>
    <?php if (!$rows): ?>
    <div class="body text-center text-muted py-4">Chưa có tin nhắn nào khớp bộ lọc.</div>
    <?php else: ?>
    <?php data_table(['Thời gian', 'Người gửi', 'Nội dung', 'Trạng thái', 'Thao tác'], $rows); ?>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> tin nhắn</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
