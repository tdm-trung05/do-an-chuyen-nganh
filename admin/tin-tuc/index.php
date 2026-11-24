<?php
/** MH19 – Quản lý tin tức: danh sách, tìm, lọc đã đăng / nháp (bảng TINTUC). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('tin-tuc');

$q      = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['tt'] ?? '');
$filter = in_array($filter, ['Đã đăng', 'Nháp'], true) ? $filter : '';
$list   = admin_news_list($q, $filter, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Tin tức', 'tin-tuc', '', admin_button('Viết bài mới', 'tin-tuc/form.php'));
$rows = [];
foreach ($list['rows'] as $n) {
    $rows[] = [
        $n['MaTin'],
        raw('<a class="fw-semibold" href="' . e(aurl('tin-tuc/form.php?ma=' . (int) $n['MaTin'])) . '">' . e($n['TieuDe']) . '</a>'),
        $n['HoTen'],
        date('d/m/Y', strtotime($n['NgayDang'])),
        raw(badge((int) $n['TrangThai'] === 1 ? 'Đã đăng' : 'Nháp')),
    ];
}
?>
<section class="card-box">
    <?php admin_filter_form('Tìm theo tiêu đề', $q, ['Đã đăng', 'Nháp'], $filter); ?>
    <?php if (!$rows): ?>
    <div class="body text-center text-muted py-4">Chưa có bài viết nào khớp bộ lọc.</div>
    <?php else: ?>
    <?php data_table(['ID', 'Tiêu đề', 'Tác giả', 'Ngày đăng', 'Trạng thái'], $rows); ?>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> bài</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
