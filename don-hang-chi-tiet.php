<?php
/** MH08 – Chi tiết đơn hàng: tiến trình trạng thái, sản phẩm, địa chỉ, thanh toán, lịch sử. */
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_customer();   // chỉ khách hàng đã đăng nhập
$uid = (int) $me['MaND'];
admin_auto_cancel_overdue();   // hủy đơn chuyển khoản quá hạn, trả lại tồn kho
$id  = parse_order_id($_GET['ma'] ?? '');

// Hủy đơn từ trang chi tiết
if (is_post() && ($_POST['action'] ?? '') === 'huy') {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $r = cancel_order($uid, $id);
        flash_set($r['ok'] ? 'success' : 'danger', $r['msg']);
    }
    redirect(url('don-hang-chi-tiet.php?ma=' . $id));
}

$o = $id > 0 ? order_of_user($uid, $id) : null;
if ($o === null) {
    http_response_code(404);
    page_begin('Không tìm thấy đơn hàng', ['Đơn hàng của tôi' => 'don-hang.php', 'Không tìm thấy' => null]);
    echo '<div class="container py-5 text-center"><p class="lead">Không tìm thấy đơn hàng này trong tài khoản của bạn.</p><a class="btn btn-ink" href="' . url('don-hang.php') . '">Về danh sách đơn hàng</a></div>';
    page_end();
    exit;
}

$code      = order_code((int) $o['MaDH']);
$status    = $o['TrangThai'];
$transfer  = ($o['payment']['PhuongThuc'] ?? '') === 'Chuyển khoản';
$steps     = $transfer ? ['Chờ thanh toán', 'Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Hoàn tất'] : ['Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Hoàn tất'];
$current   = array_search($status, $steps, true);   // false nếu đã hủy
$goods     = 0;
foreach ($o['items'] as $it) { $goods += (int) $it['SoLuong'] * (int) $it['DonGia']; }
$completed = $status === 'Hoàn tất';
$returnOk  = false;   // còn trong thời hạn đổi trả hàng lỗi kể từ ngày hoàn tất
$deadline  = null;   // hạn muộn nhất còn hiệu lực trong số các sản phẩm của đơn
$returnLines = [];  // [tên sản phẩm => hạn (timestamp)] của từng sản phẩm còn trong thời hạn
if ($completed && !empty($o['NgayHoanTat'])) {
    // Mỗi sản phẩm có số ngày đổi trả riêng; tính theo từng sản phẩm như khi tạo yêu cầu bảo hành/đổi trả.
    $days = $o['items'] ? $o['items'] : [['SoNgayDoiTra' => RETURN_DAYS]];
    foreach ($days as $it) {
        $d = strtotime($o['NgayHoanTat'] . ' +' . (int) $it['SoNgayDoiTra'] . ' days');
        $deadline = $deadline === null ? $d : max($deadline, $d);
        if (time() <= $d) {
            $returnOk = true;
            $returnLines[(string) ($it['TenSP'] ?? '')] = $d;
        }
    }
}
$returnSame = count(array_unique($returnLines)) <= 1;

page_begin('Chi tiết đơn hàng', ['Đơn hàng của tôi' => 'don-hang.php', $code => null]);
?>
<div class="container">
    <?php page_title('Đơn hàng ' . $code, 'Đặt ngày ' . date('d/m/Y H:i', strtotime($o['NgayDat'])) . ' · ' . ($o['payment']['PhuongThuc'] ?? '')); ?>
    <div class="row g-4">
        <div class="col-lg-3"><?php account_nav('don-hang'); ?></div>
        <div class="col-lg-9">
            <section class="panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h2 class="panel-title mb-0">Tiến trình đơn hàng</h2>
                    <?= badge($status) ?>
                </div>
                <?php if ($current === false): ?>
                <p class="text-danger mt-3 mb-0">Đơn hàng đã bị hủy.</p>
                <?php else: ?>
                <div class="order-track">
                    <?php foreach ($steps as $i => $s): ?>
                    <div class="st<?= $i <= $current ? ' done' : '' ?>"><div class="dot"><?= $i <= $current ? '<i class="bi bi-check-lg"></i>' : '' ?></div><?= e($s) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php if ($status === 'Chờ thanh toán' && !empty($o['HanThanhToan'])): ?>
                <p class="small text-danger mb-0">Vui lòng chuyển khoản <?= money((int) $o['TongTien']) ?> với nội dung <strong><?= e($code) ?></strong> trước <?= e(date('H:i d/m/Y', strtotime($o['HanThanhToan']))) ?>.</p>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (order_customer_can_cancel($status)): ?>
                <form method="post" action="<?= url('don-hang-chi-tiet.php?ma=' . (int) $o['MaDH']) ?>" class="mt-3" data-confirm="Hủy đơn <?= e($code) ?>? Hàng trong đơn sẽ được trả lại kho.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="huy">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Hủy đơn hàng</button>
                </form>
                <?php elseif ($status === 'Đã xác nhận'): ?>
                <p class="small text-muted mt-3 mb-0">Đơn đã được xác nhận. Cần hủy hoặc thay đổi, vui lòng liên hệ hotline <?= e(SITE_HOTLINE) ?>.</p>
                <?php endif; ?>
            </section>
            <section class="panel p-0 table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($o['items'] as $it): ?>
                        <tr>
                            <td><a class="fw-semibold" href="<?= url('san-pham-chi-tiet.php?id=' . (int) $it['MaSP']) ?>"><?= e($it['TenSP']) ?></a><div class="small text-muted">Mã hàng: <?= e($it['MaHang']) ?></div></td>
                            <td class="text-end"><?= money((int) $it['DonGia']) ?></td>
                            <td class="text-center"><?= (int) $it['SoLuong'] ?></td>
                            <td class="text-end"><?= money((int) $it['DonGia'] * (int) $it['SoLuong']) ?></td>
                            <td class="text-end"><?php if ($completed): ?><a class="btn btn-sm btn-outline-ink" href="<?= url('bao-hanh.php') ?>">Bảo hành, đổi trả</a><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end text-muted">Tiền hàng</td><td class="text-end"><?= money($goods) ?></td><td></td></tr>
                        <tr><td colspan="3" class="text-end text-muted">Phí giao hàng</td><td class="text-end"><?= (int) $o['PhiGiao'] > 0 ? money((int) $o['PhiGiao']) : 'Miễn phí' ?></td><td></td></tr>
                        <tr><th colspan="3" class="text-end">Tổng cộng</th><th class="text-end price"><?= money((int) $o['TongTien']) ?></th><th></th></tr>
                    </tfoot>
                </table>
            </section>
            <?php if ($completed && $deadline !== null): ?>
            <p class="small text-muted mb-1"><?php if (!$returnOk): ?>Đã hết thời hạn đổi trả hàng lỗi; bảo hành vẫn áp dụng theo từng sản phẩm.<?php elseif ($returnSame): ?>Hàng lỗi khi nhận được đổi trả đến hết ngày <?= date('d/m/Y', reset($returnLines)) ?>.<?php else: ?>Hạn đổi trả hàng lỗi tính theo từng sản phẩm:<?php endif; ?></p>
            <?php if ($returnOk && !$returnSame): ?>
            <ul class="small text-muted"><?php foreach ($returnLines as $nm => $ts): ?><li><?= e($nm) ?>: đến hết ngày <?= date('d/m/Y', $ts) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <?php endif; ?>
            <div class="row g-3 mt-0">
                <div class="col-md-6"><section class="panel h-100"><h2 class="panel-title">Địa chỉ nhận hàng</h2><strong><?= e($o['NguoiNhan']) ?></strong> · <?= e($o['SoDienThoaiNhan']) ?><br><span class="text-muted"><?= e($o['DiaChiGiao']) ?></span></section></div>
                <div class="col-md-6"><section class="panel h-100"><h2 class="panel-title">Thanh toán</h2><?= e($o['payment']['PhuongThuc'] ?? '') ?><br><?php $paid = ($o['payment']['TrangThai'] ?? '') === 'Đã thanh toán'; ?><span class="badge <?= $paid ? 'text-bg-success' : 'text-bg-warning' ?>"><?= $paid ? 'Đã thanh toán' : 'Chưa thanh toán' ?></span><?php if ($paid && !empty($o['payment']['ThoiGian'])): ?> <span class="small text-muted"><?= e(date('d/m/Y H:i', strtotime($o['payment']['ThoiGian']))) ?></span><?php endif; ?></section></div>
            </div>
            <?php if ($o['history']): ?>
            <section class="panel mt-3">
                <h2 class="panel-title">Lịch sử đơn hàng</h2>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($o['history'] as $h): ?>
                    <li class="py-1 border-bottom"><span class="text-muted"><?= e(date('d/m/Y H:i', strtotime($h['ThoiGian']))) ?></span> · <?= $h['TrangThaiCu'] ? e($h['TrangThaiCu']) . ' → ' : '' ?><strong><?= e($h['TrangThaiMoi']) ?></strong><?= $h['GhiChu'] ? ' · ' . e($h['GhiChu']) : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php page_end(); ?>
