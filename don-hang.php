<?php
/** MH08 – Đơn hàng của tôi: lịch sử đơn, theo dõi trạng thái, hủy đơn khi chưa xác nhận. */
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_customer();   // chỉ khách hàng đã đăng nhập
$uid = (int) $me['MaND'];
admin_auto_cancel_overdue();   // hủy đơn chuyển khoản quá hạn, trả lại tồn kho

// Hủy đơn (thủ tục sp_HuyDon cộng lại tồn kho và ghi lịch sử)
if (is_post() && ($_POST['action'] ?? '') === 'huy') {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $r = cancel_order($uid, (int) ($_POST['ma'] ?? 0));
        flash_set($r['ok'] ? 'success' : 'danger', $r['msg']);
    }
    redirect(safe_next(url('don-hang.php')));
}

$status = (string) ($_GET['trang-thai'] ?? '');
if (!in_array($status, ORDER_STATUSES, true)) { $status = ''; }
$list = orders_of_user($uid, $status, max(1, (int) ($_GET['trang'] ?? 1)), 10);

page_begin('Đơn hàng của tôi', ['Tài khoản' => 'tai-khoan.php', 'Đơn hàng của tôi' => null]);
?>
<div class="container">
    <?php page_title('Đơn hàng của tôi', 'Theo dõi trạng thái và hủy đơn khi chưa xác nhận.'); ?>
    <div class="row g-4">
        <div class="col-lg-3"><?php account_nav('don-hang'); ?></div>
        <div class="col-lg-9">
            <ul class="nav nav-pills mb-3 gap-1">
                <li class="nav-item"><a class="btn btn-sm <?= $status === '' ? 'btn-ink' : 'btn-outline-ink' ?>" href="<?= url('don-hang.php') ?>">Tất cả</a></li>
                <?php foreach (ORDER_STATUSES as $t): ?>
                <li class="nav-item"><a class="btn btn-sm <?= $status === $t ? 'btn-ink' : 'btn-outline-ink' ?>" href="<?= url('don-hang.php?trang-thai=' . rawurlencode($t)) ?>"><?= e($t) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php if (!$list['rows']): ?>
            <div class="panel text-center">
                <p class="mb-3"><?= $status === '' ? 'Bạn chưa có đơn hàng nào.' : 'Không có đơn hàng ở trạng thái “' . e($status) . '”.' ?></p>
                <a class="btn btn-ink" href="<?= url('san-pham.php') ?>">Xem sản phẩm</a>
            </div>
            <?php else: ?>
            <div class="panel p-0 table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Số SP</th><th>Thanh toán</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($list['rows'] as $o): $code = order_code((int) $o['MaDH']); ?>
                        <tr>
                            <td><a class="fw-semibold" href="<?= url('don-hang-chi-tiet.php?ma=' . (int) $o['MaDH']) ?>"><?= e($code) ?></a></td>
                            <td><?= e(date('d/m/Y', strtotime($o['NgayDat']))) ?></td>
                            <td><?= (int) $o['SoSP'] ?></td>
                            <td><?= e($o['PhuongThuc'] ?? '') ?></td>
                            <td class="text-end"><?= money((int) $o['TongTien']) ?></td>
                            <td><?= badge($o['TrangThai']) ?></td>
                            <td class="text-end">
                                <?php if (order_customer_can_cancel($o['TrangThai'])): ?>
                                <form method="post" action="<?= url('don-hang.php') ?>" data-confirm="Hủy đơn <?= e($code) ?>? Hàng trong đơn sẽ được trả lại kho.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="huy">
                                    <input type="hidden" name="ma" value="<?= (int) $o['MaDH'] ?>">
                                    <input type="hidden" name="quay-lai" value="<?= e(request_uri()) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hủy</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php pagination($list['page'], $list['pages']); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php page_end(); ?>
