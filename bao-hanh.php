<?php
/** MH09 – Yêu cầu bảo hành, đổi trả (khách gửi và theo dõi). Dữ liệu bảng BAOHANH. */
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_customer();   // chỉ khách hàng đã đăng nhập
$uid = (int) $me['MaND'];

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        // Ô chọn có dạng "mã đơn:mã sản phẩm"
        $pick = explode(':', (string) ($_POST['dong'] ?? ''));
        $res  = warranty_create(
            $uid,
            (int) ($pick[0] ?? 0),
            (int) ($pick[1] ?? 0),
            (string) ($_POST['loai'] ?? ''),
            trim((string) ($_POST['mota'] ?? ''))
        );
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(url('bao-hanh.php'));
}

$items = warranty_eligible_items($uid);
$sent  = warranty_of_user($uid);
page_begin('Bảo hành, đổi trả', ['Tài khoản' => 'tai-khoan.php', 'Bảo hành, đổi trả' => null]);
?>
<div class="container">
    <?php page_title('Bảo hành, đổi trả', 'Chỉ nhận yêu cầu cho đơn đã hoàn tất và còn trong thời hạn.'); ?>
    <div class="row g-4">
        <div class="col-lg-3"><?php account_nav('bao-hanh'); ?></div>
        <div class="col-lg-9">
            <section class="panel">
                <h2 class="panel-title">Gửi yêu cầu mới</h2>
                <?php if (!$items): ?>
                <p class="text-muted mb-0">Bạn chưa có đơn hàng nào đã hoàn tất nên chưa gửi được yêu cầu.</p>
                <?php else: ?>
                <form action="<?= e(url('bao-hanh.php')) ?>" method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-md-6"><label class="form-label" for="dong">Sản phẩm trong đơn đã hoàn tất</label>
                        <select id="dong" name="dong" class="form-select" required>
                            <?php foreach ($items as $it): ?>
                            <option value="<?= (int) $it['MaDH'] ?>:<?= (int) $it['MaSP'] ?>"><?= e(order_code((int) $it['MaDH'])) ?> (<?= e(date('d/m/Y', strtotime($it['NgayHoanTat']))) ?>) – <?= e($it['TenSP']) ?> [<?= e($it['MaHang']) ?>]</option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label d-block">Loại yêu cầu</label>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="loai" id="l1" value="Bảo hành" checked><label class="form-check-label" for="l1">Bảo hành</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="loai" id="l2" value="Đổi trả"><label class="form-check-label" for="l2">Đổi trả (hàng lỗi khi nhận)</label></div>
                        <div class="alert alert-info small mt-2 mb-0"><i class="bi bi-info-circle"></i> Hệ thống so ngày hoàn tất đơn với thời hạn bảo hành. Hàng lỗi khi nhận được đổi trả trong <?= e(return_days_text()) ?>.</div>
                    </div>
                    <div class="col-12"><label class="form-label" for="mota">Mô tả lỗi</label><textarea id="mota" name="mota" class="form-control" rows="3" maxlength="2000" required></textarea></div>
                    <div class="col-12"><button class="btn btn-ink" type="submit">Gửi yêu cầu</button></div>
                </form>
                <?php endif; ?>
            </section>
            <section class="panel p-0 table-responsive">
                <div class="p-3 pb-0"><h2 class="panel-title">Yêu cầu đã gửi</h2></div>
                <?php if (!$sent): ?>
                <p class="text-muted p-3 mb-0">Bạn chưa gửi yêu cầu nào.</p>
                <?php else: ?>
                <table class="table align-middle mb-0">
                    <thead><tr><th>Mã</th><th>Đơn hàng</th><th>Sản phẩm</th><th>Loại</th><th>Ngày gửi</th><th>Trạng thái</th></tr></thead>
                    <tbody><?php foreach ($sent as $w): ?>
                        <tr><td><?= e(warranty_code((int) $w['MaBH'])) ?></td><td><?= e(order_code((int) $w['MaDH'])) ?></td><td><?= e($w['TenSP']) ?></td><td><?= e($w['LoaiYeuCau']) ?></td><td><?= e(date('d/m/Y', strtotime($w['NgayTao']))) ?></td>
                            <td><?= badge($w['TrangThai']) ?><?php if ($w['PhanHoi']): ?><div class="small text-muted mt-1"><?= e($w['PhanHoi']) ?></div><?php endif; ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
<?php page_end(); ?>
