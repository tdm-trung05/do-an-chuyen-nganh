<?php
/** MH13 – Tồn kho và cảnh báo sản phẩm dưới ngưỡng (BB03). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('ton-kho');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $sp = admin_int($_POST['sp'] ?? '');
        $ng = admin_int($_POST['nguong'] ?? '');
        $res = ($sp === null || $ng === null) ? ['ok' => false, 'msg' => 'Ngưỡng cảnh báo phải là số nguyên không âm.'] : admin_stock_set_threshold($sp, $ng);
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(request_uri());
}

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['tt'] ?? '');
$status = in_array($status, ['Còn hàng', 'Sắp hết', 'Hết hàng', 'Cần chú ý'], true) ? $status : '';
$list   = admin_stock_list($q, $status, max(1, (int) ($_GET['trang'] ?? 1)));
$sum    = admin_stock_summary();

admin_begin('Tồn kho', 'ton-kho', 'Số lượng tồn tăng theo phiếu nhập, giảm theo đơn hàng.', admin_button('Lập phiếu nhập', 'kho/nhap-kho.php'));
?>
<div class="row g-3 mb-3">
    <div class="col-md-4"><?php stat_card('bi-boxes', 'Tổng mã hàng', (string) $sum['total']); ?></div>
    <div class="col-md-4"><?php stat_card('bi-exclamation-triangle', 'Dưới ngưỡng cảnh báo', (string) $sum['low']); ?></div>
    <div class="col-md-4"><?php stat_card('bi-x-circle', 'Hết hàng', (string) $sum['out']); ?></div>
</div>
<section class="card-box">
    <?php admin_filter_form('Tìm theo tên hoặc mã hàng', $q, ['Còn hàng', 'Sắp hết', 'Hết hàng', 'Cần chú ý'], $status); ?>
    <div class="table-responsive"><table class="table align-middle data-table mb-0">
        <thead><tr><th>Mã hàng</th><th>Sản phẩm</th><th class="text-end">Tồn</th><th style="width:200px">Ngưỡng cảnh báo</th><th>Tình trạng</th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $s): ?>
            <tr>
                <td><?= e($s['MaHang']) ?></td><td><?= e($s['TenSP']) ?></td>
                <td class="text-end"><?= (int) $s['SoLuongTon'] ?></td>
                <td>
                    <form method="post" class="d-flex gap-1">
                        <?= csrf_field() ?><input type="hidden" name="sp" value="<?= (int) $s['MaSP'] ?>">
                        <input class="form-control form-control-sm" type="number" min="0" name="nguong" value="<?= (int) $s['NguongCanhBao'] ?>" aria-label="Ngưỡng cảnh báo của <?= e($s['MaHang']) ?>">
                        <button class="btn btn-sm btn-outline-ink" type="submit" title="Lưu ngưỡng"><i class="bi bi-check-lg"></i></button>
                    </form>
                </td>
                <td><?= badge(admin_stock_label((int) $s['SoLuongTon'], (int) $s['NguongCanhBao'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$list['rows']): ?><tr><td colspan="5" class="text-center text-muted py-4">Không có sản phẩm nào khớp bộ lọc.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> sản phẩm</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
