<?php
/** MH12 – Chi tiết đơn: kiểm tra thông tin, đối chiếu chuyển khoản, cập nhật trạng thái, phí giao, lịch sử. */
require_once __DIR__ . '/../../includes/bootstrap.php';
$staff = admin_guard('don-hang');
admin_auto_cancel_overdue();   // hủy đơn chuyển khoản quá hạn, trả lại tồn kho
$id    = parse_order_id($_GET['ma'] ?? '');
$here  = aurl('don-hang/chi-tiet.php?ma=' . $id);

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
        redirect($here);
    }
    $id     = parse_order_id($_POST['ma'] ?? '') ?: $id;
    $action = (string) ($_POST['hanh-dong'] ?? '');
    $note   = trim((string) ($_POST['ghi-chu'] ?? ''));
    $res    = ['ok' => false, 'msg' => 'Thao tác không hợp lệ.'];
    if ($action === 'thu-tien') {
        $res = admin_order_set_status($id, (int) $staff['MaND'], 'Chờ xác nhận', $note);
    } elseif ($action === 'trang-thai') {
        $res = admin_order_set_status($id, (int) $staff['MaND'], (string) ($_POST['trang-thai'] ?? ''), $note);
    } elseif ($action === 'phi-giao') {
        $fee = admin_int($_POST['phi-giao'] ?? '');
        $res = $fee === null ? ['ok' => false, 'msg' => 'Phí giao hàng phải là số không âm.'] : admin_order_set_fee($id, (int) $staff['MaND'], $fee);
    }
    flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    redirect(aurl('don-hang/chi-tiet.php?ma=' . $id));
}

$o = $id > 0 ? admin_order($id) : null;
if ($o === null) {
    flash_set('warning', 'Không tìm thấy đơn hàng.');
    redirect(aurl('don-hang/index.php'));
}
$status  = $o['TrangThai'];
$next    = admin_order_next($status);
$pay     = $o['payment'];
$paid    = $pay && $pay['TrangThai'] === 'Đã thanh toán';
// Chỉ đơn thanh toán khi nhận hàng, và chỉ trước khi xác nhận (đơn chuyển khoản đã chốt số tiền với khách).
$feeEdit = $status === 'Chờ xác nhận' && $pay && $pay['PhuongThuc'] === 'Thanh toán khi nhận hàng' && !$paid;
$toStatus = array_values(array_diff($next, ['Đã hủy']));
$canCancel = in_array('Đã hủy', $next, true);

admin_begin(
    'Đơn ' . order_code($id),
    'don-hang',
    'Đặt ngày ' . date('d/m/Y H:i', strtotime($o['NgayDat'])) . ' · ' . ($pay['PhuongThuc'] ?? 'chưa có thanh toán'),
    '<a class="btn btn-outline-ink" href="' . e(aurl('don-hang/index.php')) . '"><i class="bi bi-arrow-left"></i> Danh sách đơn</a>'
);
?>
<div class="row g-3">
    <div class="col-xl-8">
        <section class="card-box mb-3"><div class="head"><h2>Sản phẩm trong đơn</h2><?= badge($status) ?></div>
            <?php
            $rows = [];
            foreach ($o['items'] as $r) {
                $rows[] = [$r['MaHang'], $r['TenSP'], money($r['DonGia']), (int) $r['SoLuong'], money($r['DonGia'] * $r['SoLuong'])];
            }
            data_table(['Mã hàng', 'Sản phẩm', 'Đơn giá', 'SL', 'Thành tiền'], $rows, ['right' => [2, 3, 4]]);
            ?>
            <div class="card-foot text-end">
                <span class="text-muted">Tiền hàng:</span> <strong><?= money($o['hang']) ?></strong> &nbsp;
                <span class="text-muted">Phí giao:</span> <strong><?= money($o['PhiGiao']) ?></strong> &nbsp;
                <span class="text-muted">Tổng:</span> <strong class="price"><?= money($o['TongTien']) ?></strong>
            </div>
        </section>
        <section class="card-box"><div class="head"><h2>Lịch sử trạng thái</h2></div><div class="body">
            <ul class="list-unstyled mb-0 small">
                <?php foreach (array_reverse($o['history']) as $h): ?>
                <li class="mb-2">
                    <?= badge($h['TrangThaiMoi']) ?> <?= e(date('d/m/Y H:i', strtotime($h['ThoiGian']))) ?> · <?= e($h['HoTen'] ?? 'Hệ thống') ?>
                    <?php if ($h['GhiChu'] !== null && $h['GhiChu'] !== ''): ?><div class="text-muted ms-1"><?= e($h['GhiChu']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div></section>
    </div>
    <div class="col-xl-4">
        <section class="card-box mb-3"><div class="head"><h2>Thanh toán</h2></div><div class="body small">
            <?php if ($pay): ?>
            <div><?= e($pay['PhuongThuc']) ?> · <strong><?= money($pay['SoTien']) ?></strong></div>
            <div class="mb-2"><?= $paid ? '<span class="badge text-bg-success">Đã thanh toán</span> ' . e(date('d/m/Y H:i', strtotime($pay['ThoiGian']))) : '<span class="badge text-bg-warning">Chưa thanh toán</span>' ?></div>
            <?php endif; ?>
            <?php if ($status === 'Chờ thanh toán' && $o['HanThanhToan']): ?>
            <div class="text-muted mb-2">Hạn chuyển khoản: <?= e(date('d/m/Y H:i', strtotime($o['HanThanhToan']))) ?>. Quá hạn đơn tự hủy.</div>
            <form method="post" onsubmit="return confirm('Xác nhận đã nhận đủ tiền chuyển khoản cho đơn này?');">
                <?= csrf_field() ?><input type="hidden" name="ma" value="<?= (int) $id ?>"><input type="hidden" name="hanh-dong" value="thu-tien">
                <button class="btn btn-ink w-100" type="submit">Xác nhận đã nhận tiền</button>
            </form>
            <?php endif; ?>
        </div></section>

        <?php if ($next): ?>
        <section class="card-box mb-3"><div class="head"><h2>Cập nhật trạng thái</h2></div><div class="body">
            <?php if ($toStatus && !($status === 'Chờ thanh toán')): ?>
            <form method="post" class="mb-3">
                <?= csrf_field() ?><input type="hidden" name="ma" value="<?= (int) $id ?>"><input type="hidden" name="hanh-dong" value="trang-thai">
                <label class="form-label" for="tt">Chuyển sang</label>
                <select id="tt" name="trang-thai" class="form-select mb-2"><?php foreach ($toStatus as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select>
                <label class="form-label" for="gc">Ghi chú (tùy chọn)</label>
                <input id="gc" name="ghi-chu" class="form-control mb-2" maxlength="250">
                <button class="btn btn-ink w-100" type="submit">Lưu trạng thái</button>
            </form>
            <?php endif; ?>
            <?php if ($canCancel): ?>
            <form method="post" onsubmit="return confirm('Hủy đơn này? Tồn kho sẽ được cộng lại.');">
                <?= csrf_field() ?><input type="hidden" name="ma" value="<?= (int) $id ?>"><input type="hidden" name="hanh-dong" value="trang-thai"><input type="hidden" name="trang-thai" value="Đã hủy">
                <label class="form-label" for="ly">Lý do hủy đơn</label>
                <input id="ly" name="ghi-chu" class="form-control mb-2" maxlength="250" required>
                <button class="btn btn-outline-danger w-100" type="submit">Hủy đơn</button>
            </form>
            <?php endif; ?>
            <p class="small text-muted mt-2 mb-0">Đơn chỉ chuyển theo thứ tự. Hủy đơn sẽ cộng lại tồn kho.</p>
        </div></section>
        <?php endif; ?>

        <?php if ($feeEdit): ?>
        <section class="card-box mb-3"><div class="head"><h2>Phí giao hàng</h2></div><div class="body">
            <form method="post">
                <?= csrf_field() ?><input type="hidden" name="ma" value="<?= (int) $id ?>"><input type="hidden" name="hanh-dong" value="phi-giao">
                <label class="form-label" for="pg">Phí giao hàng (₫)</label>
                <input id="pg" name="phi-giao" class="form-control mb-2" type="number" min="0" step="1" value="<?= (int) $o['PhiGiao'] ?>">
                <button class="btn btn-outline-ink w-100" type="submit">Cập nhật phí giao</button>
            </form>
        </div></section>
        <?php endif; ?>

        <section class="card-box"><div class="head"><h2>Người nhận</h2></div><div class="body small">
            <strong><?= e($o['NguoiNhan']) ?></strong><br><?= e($o['SoDienThoaiNhan']) ?><br><?= e($o['DiaChiGiao']) ?>
            <hr>
            <span class="text-muted">Tài khoản đặt:</span> <?= e($o['HoTen']) ?><br><?= e($o['Email']) ?>
        </div></section>
    </div>
</div>
<?php admin_end(); ?>
