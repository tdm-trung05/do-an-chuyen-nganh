<?php
/** MH14 – Danh sách yêu cầu báo giá (BB07). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('bao-gia');

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['tt'] ?? '');
$status = in_array($status, QUOTE_STATUSES, true) ? $status : '';
$list   = admin_quotes($q, $status, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Yêu cầu báo giá', 'bao-gia', 'Mở yêu cầu, nhập đơn giá từng dòng và thời hạn hiệu lực rồi gửi lại cho khách.');
$rows = [];
foreach ($list['rows'] as $b) {
    $rows[] = [
        raw('<a class="fw-semibold" href="' . e(aurl('bao-gia/chi-tiet.php?ma=' . (int) $b['MaBG'])) . '">' . e(quote_code((int) $b['MaBG'])) . '</a>'),
        $b['HoTen'],
        date('d/m/Y', strtotime($b['NgayGui'])),
        (int) $b['SoSP'],
        $b['HanHieuLuc'] ? date('d/m/Y', strtotime($b['HanHieuLuc'])) : '—',
        raw(badge($b['TrangThai'])),
    ];
}
?>
<section class="card-box">
    <?php admin_filter_form('Tìm theo mã hoặc tên khách', $q, QUOTE_STATUSES, $status); ?>
    <?php if (!$rows): ?>
    <div class="body text-center text-muted py-4">Không có yêu cầu báo giá nào khớp bộ lọc.</div>
    <?php else: ?>
    <?php data_table(['Mã', 'Khách hàng', 'Ngày gửi', 'Số SP', 'Hiệu lực đến', 'Trạng thái'], $rows, ['right' => [3]]); ?>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> yêu cầu</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
