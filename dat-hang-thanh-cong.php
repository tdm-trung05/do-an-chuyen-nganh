<?php
/** Kết quả đặt hàng: hiện mã đơn, kèm thông tin chuyển khoản nếu chọn chuyển khoản. */
require_once __DIR__ . '/includes/bootstrap.php';
$me    = require_customer();   // chỉ khách hàng đã đăng nhập
$order = order_of_user((int) $me['MaND'], parse_order_id($_GET['ma'] ?? ''));
if ($order === null) {
    flash_set('warning', 'Không tìm thấy đơn hàng.');
    redirect(url('don-hang.php'));
}
$code     = order_code((int) $order['MaDH']);
$transfer = ($order['payment']['PhuongThuc'] ?? '') === 'Chuyển khoản';
page_begin('Đặt hàng thành công', ['Đặt hàng thành công' => null]);
?>
<div class="container py-5">
    <div class="panel text-center mx-auto" style="max-width:640px">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
        <h1 class="h3 mt-2">Đặt hàng thành công</h1>
        <p class="mb-1">Mã đơn của bạn</p>
        <p class="h4 mb-1"><?= e($code) ?></p>
        <p class="mb-3"><?= badge($order['TrangThai']) ?> · Tổng tiền <strong><?= money((int) $order['TongTien']) ?></strong> <span class="text-muted small">(đã gồm phí giao)</span></p>
        <?php if ($transfer): ?>
        <div class="text-start bg-light rounded p-3 mb-3">
            <strong>Thông tin chuyển khoản</strong>
            <ul class="list-unstyled mb-0 small mt-2">
                <li>Ngân hàng: <?= e(defined('BANK_NAME') ? BANK_NAME : '[tên ngân hàng]') ?></li>
                <li>Số tài khoản: <?= e(defined('BANK_ACCOUNT') ? BANK_ACCOUNT : '[số tài khoản]') ?></li>
                <li>Chủ tài khoản: <?= e(defined('BANK_HOLDER') ? BANK_HOLDER : '[tên chủ tài khoản]') ?></li>
                <li>Số tiền: <?= money((int) $order['TongTien']) ?></li>
                <li>Nội dung: <strong><?= e($code) ?></strong></li>
            </ul>
            <?php if (!empty($order['HanThanhToan'])): ?>
            <p class="small text-danger mt-2 mb-0">Vui lòng chuyển khoản trước <?= e(date('H:i d/m/Y', strtotime($order['HanThanhToan']))) ?>, sau thời hạn này đơn sẽ tự hủy.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-muted small">Bạn thanh toán bằng tiền mặt khi nhận hàng. Nhân viên sẽ liên hệ xác nhận đơn.</p>
        <?php endif; ?>
        <div class="d-flex gap-2 justify-content-center">
            <a class="btn btn-ink" href="<?= url('don-hang-chi-tiet.php?ma=' . (int) $order['MaDH']) ?>">Xem đơn hàng</a>
            <a class="btn btn-outline-ink" href="<?= url('san-pham.php') ?>">Tiếp tục mua sắm</a>
        </div>
    </div>
</div>
<?php page_end(); ?>
