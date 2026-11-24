<?php
/** MH12 – Quản lý đơn hàng: danh sách có tìm kiếm, lọc trạng thái, phân trang (dữ liệu thật). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('don-hang');
admin_auto_cancel_overdue();   // đơn chuyển khoản quá 24 giờ chưa thanh toán tự hủy, cộng lại tồn kho

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['tt'] ?? '');
$status = in_array($status, ORDER_STATUSES, true) ? $status : '';
$list   = admin_orders($q, $status, max(1, (int) ($_GET['trang'] ?? 1)));
$counts = admin_order_counts();

admin_begin('Đơn hàng', 'don-hang', 'Xác nhận, chuyển trạng thái và theo dõi các đơn.');
$tabUrl = static function (string $s) use ($q): string {
    $p = array_filter(['q' => $q, 'tt' => $s], static fn($v) => $v !== '');
    return aurl('don-hang/index.php' . ($p ? '?' . http_build_query($p) : ''));
};
?>
<ul class="nav nav-pills gap-1 mb-3 flex-wrap">
    <li class="nav-item"><a class="btn btn-sm <?= $status === '' ? 'btn-ink' : 'btn-outline-ink' ?>" href="<?= e($tabUrl('')) ?>">Tất cả (<?= (int) array_sum($counts) ?>)</a></li>
    <?php foreach (ORDER_STATUSES as $s): ?>
    <li class="nav-item"><a class="btn btn-sm <?= $status === $s ? 'btn-ink' : 'btn-outline-ink' ?>" href="<?= e($tabUrl($s)) ?>"><?= e($s) ?> (<?= (int) $counts[$s] ?>)</a></li>
    <?php endforeach; ?>
</ul>
<section class="card-box">
    <form class="table-toolbar" method="get">
        <?php if ($status !== ''): ?><input type="hidden" name="tt" value="<?= e($status) ?>"><?php endif; ?>
        <div class="input-group" style="max-width:380px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Mã đơn, tên khách, email, số điện thoại" aria-label="Tìm đơn hàng">
        </div>
        <button class="btn btn-ink" type="submit">Tìm</button>
        <?php if ($q !== ''): ?><a class="btn btn-outline-ink" href="<?= e($tabUrl($status)) ?>">Xóa tìm</a><?php endif; ?>
    </form>
    <?php
    $rows = [];
    foreach ($list['rows'] as $o) {
        $pay = $o['PhuongThuc'] === 'Chuyển khoản' ? 'Chuyển khoản' : 'Khi nhận hàng';
        if ($o['TTThanhToan'] === 'Đã thanh toán') {
            $pay .= ' · đã thu';
        }
        $rows[] = [
            raw('<a class="fw-semibold" href="' . e(aurl('don-hang/chi-tiet.php?ma=' . (int) $o['MaDH'])) . '">' . e(order_code((int) $o['MaDH'])) . '</a>'),
            $o['NguoiNhan'],
            date('d/m/Y H:i', strtotime($o['NgayDat'])),
            (int) $o['SoSP'],
            $pay,
            money($o['TongTien']),
            raw(badge($o['TrangThai'])),
        ];
    }
    if (!$rows) {
        echo '<div class="body text-center text-muted py-4">Không có đơn hàng nào khớp bộ lọc.</div>';
    } else {
        data_table(['Mã đơn', 'Người nhận', 'Ngày đặt', 'Số SP', 'Thanh toán', 'Tổng tiền', 'Trạng thái'], $rows, ['right' => [3, 5]]);
    }
    ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> đơn</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
