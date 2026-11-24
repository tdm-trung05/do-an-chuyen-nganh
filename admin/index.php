<?php
/** MH10 – Bảng điều khiển: số liệu tổng quan, đơn mới, cảnh báo tồn kho (hiển thị theo vai trò). */
require_once __DIR__ . '/../includes/bootstrap.php';
$u    = admin_guard('dashboard');
$role = $u['role'];
$isQT = $role === 'QT';
$canOrders = in_array($role, ['QT', 'BH'], true);
$canStock  = in_array($role, ['QT', 'TK'], true);
if ($canOrders) {
    admin_auto_cancel_overdue();
}
$st = admin_dashboard_stats();
admin_begin('Bảng điều khiển', 'dashboard', 'Tổng quan hoạt động ngày ' . date('d/m/Y'));

$growth = $st['revenue_last'] > 0 ? round(($st['revenue'] - $st['revenue_last']) / $st['revenue_last'] * 100) : null;
?>
<div class="row g-3 mb-3">
    <?php if ($isQT): ?>
    <div class="col-6 col-xl-3"><?php stat_card('bi-cash-coin', 'Doanh thu tháng', money($st['revenue']), $growth === null ? 'Chưa có số liệu tháng trước' : ($growth >= 0 ? '+' : '') . $growth . '% so với tháng trước'); ?></div>
    <?php endif; ?>
    <?php if ($canOrders): ?>
    <div class="col-6 col-xl-3"><?php stat_card('bi-box-seam', 'Đơn chờ xử lý', (string) $st['orders_wait'], $st['orders_pay'] . ' đơn chờ thanh toán'); ?></div>
    <div class="col-6 col-xl-3"><?php stat_card('bi-file-earmark-text', 'Báo giá mới', (string) $st['quotes_new']); ?></div>
    <?php endif; ?>
    <?php if ($canStock): ?>
    <div class="col-6 col-xl-3"><?php stat_card('bi-exclamation-triangle', 'Dưới ngưỡng tồn', (string) $st['stock_alert'], $st['stock_alert'] > 0 ? 'Cần nhập thêm' : 'Tồn kho ổn định'); ?></div>
    <?php endif; ?>
</div>
<div class="row g-3">
    <div class="col-xl-8">
        <?php if ($isQT):
            $days = admin_revenue_days(7);
            $max  = max(1, max($days));
            $maxD = array_search(max($days), $days, true);
        ?>
        <section class="card-box mb-3">
            <div class="head"><h2>Doanh thu 7 ngày gần nhất</h2><a class="small" href="<?= aurl('bao-cao/index.php') ?>">Xem báo cáo</a></div>
            <div class="body"><div class="chart-placeholder" role="img" aria-label="Biểu đồ cột doanh thu 7 ngày">
                <?php foreach ($days as $d => $v): ?><div class="bar<?= $d === $maxD && $v > 0 ? ' hi' : '' ?>" style="height:<?= max(2, (int) round($v / $max * 100)) ?>%" title="<?= e(date('d/m', strtotime($d)) . ': ' . money($v)) ?>"></div><?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-between small text-muted mt-1"><?php foreach (array_keys($days) as $d): ?><span><?= e(date('d/m', strtotime($d))) ?></span><?php endforeach; ?></div></div>
        </section>
        <?php endif; ?>
        <?php if ($canOrders): ?>
        <section class="card-box">
            <div class="head"><h2>Đơn hàng mới</h2><a class="small" href="<?= aurl('don-hang/index.php') ?>">Xem tất cả</a></div>
            <?php
            $rows = [];
            foreach (admin_recent_orders(5) as $o) {
                $rows[] = [raw('<a href="' . e(aurl('don-hang/chi-tiet.php?ma=' . (int) $o['MaDH'])) . '">' . e(order_code((int) $o['MaDH'])) . '</a>'), $o['NguoiNhan'], date('d/m/Y H:i', strtotime($o['NgayDat'])), money($o['TongTien']), raw(badge($o['TrangThai']))];
            }
            if ($rows) { data_table(['Mã đơn', 'Người nhận', 'Ngày đặt', 'Tổng tiền', 'Trạng thái'], $rows, ['right' => [3]]); } else { echo '<div class="body text-muted">Chưa có đơn hàng.</div>'; }
            ?>
        </section>
        <?php endif; ?>
    </div>
    <div class="col-xl-4">
        <?php if ($canStock): ?>
        <section class="card-box mb-3">
            <div class="head"><h2>Cảnh báo tồn kho</h2><a class="small" href="<?= aurl('kho/ton-kho.php') ?>">Xem tồn kho</a></div>
            <ul class="list-group list-group-flush">
                <?php foreach (admin_stock_alerts(6) as $s): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center"><span><strong class="small"><?= e($s['MaHang']) ?></strong><br><small class="text-muted"><?= e($s['TenSP']) ?></small></span><span><?= badge(admin_stock_label((int) $s['SoLuongTon'], (int) $s['NguongCanhBao'])) ?> <span class="ms-1"><?= (int) $s['SoLuongTon'] ?></span></span></li>
                <?php endforeach; ?>
                <?php if ($st['stock_alert'] === 0): ?><li class="list-group-item small text-muted">Không có sản phẩm nào dưới ngưỡng.</li><?php endif; ?>
            </ul>
        </section>
        <?php endif; ?>
        <section class="card-box">
            <div class="head"><h2>Việc cần làm</h2></div>
            <ul class="list-group list-group-flush small">
                <?php if ($canOrders): ?>
                <li class="list-group-item d-flex justify-content-between"><a href="<?= e(aurl('don-hang/index.php?tt=' . rawurlencode('Chờ thanh toán'))) ?>">Đối chiếu chuyển khoản</a> <span class="badge text-bg-warning"><?= (int) $st['orders_pay'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><a href="<?= e(aurl('don-hang/index.php?tt=' . rawurlencode('Chờ xác nhận'))) ?>">Xác nhận đơn mới</a> <span class="badge text-bg-secondary"><?= (int) ($st['orders_wait'] - $st['orders_pay']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><a href="<?= aurl('bao-gia/index.php') ?>">Trả lời báo giá</a> <span class="badge text-bg-secondary"><?= (int) $st['quotes_new'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><a href="<?= aurl('bao-hanh/index.php') ?>">Yêu cầu bảo hành mới</a> <span class="badge text-bg-secondary"><?= (int) $st['warranty_new'] ?></span></li>
                <?php endif; ?>
                <?php if ($canStock): ?>
                <li class="list-group-item d-flex justify-content-between"><a href="<?= aurl('kho/ton-kho.php') ?>">Sản phẩm cần nhập thêm</a> <span class="badge text-bg-warning"><?= (int) $st['stock_alert'] ?></span></li>
                <?php endif; ?>
            </ul>
        </section>
    </div>
</div>
<?php admin_end(); ?>
