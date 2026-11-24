<?php
/** MH13 – Danh sách phiếu nhập kho (BB04), xem chi tiết từng phiếu. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('phieu-nhap');

$q    = trim((string) ($_GET['q'] ?? ''));
$from = admin_date($_GET['tu'] ?? '');
$to   = admin_date($_GET['den'] ?? '');
$list = admin_purchases($q, $from, $to, max(1, (int) ($_GET['trang'] ?? 1)));
$open = admin_int($_GET['xem'] ?? '') ?? 0;

admin_begin('Phiếu nhập kho', 'phieu-nhap', 'Mỗi phiếu lưu nhà cung cấp, người lập và các dòng sản phẩm.', admin_button('Lập phiếu nhập', 'kho/nhap-kho.php'));
?>
<section class="card-box">
    <form class="table-toolbar" method="get">
        <div class="input-group" style="max-width:320px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Mã phiếu hoặc nhà cung cấp" aria-label="Tìm phiếu nhập">
        </div>
        <input type="date" name="tu" value="<?= e($from) ?>" class="form-control w-auto" aria-label="Từ ngày">
        <input type="date" name="den" value="<?= e($to) ?>" class="form-control w-auto" aria-label="Đến ngày">
        <button class="btn btn-ink" type="submit">Lọc</button>
        <?php if ($q !== '' || $from !== '' || $to !== ''): ?><a class="btn btn-outline-ink" href="<?= e(aurl('kho/phieu-nhap.php')) ?>">Xóa lọc</a><?php endif; ?>
    </form>
    <div class="table-responsive"><table class="table align-middle data-table mb-0">
        <thead><tr><th>Mã phiếu</th><th>Nhà cung cấp</th><th>Người lập</th><th>Ngày nhập</th><th class="text-end">Số SP</th><th class="text-end">Tổng tiền</th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $p): $isOpen = $open === (int) $p['MaPN']; ?>
            <tr>
                <td><a class="fw-semibold" href="<?= e(request_uri_with(['xem' => $isOpen ? null : (int) $p['MaPN']])) ?>"><?= e(purchase_code((int) $p['MaPN'])) ?> <i class="bi bi-chevron-<?= $isOpen ? 'up' : 'down' ?> small"></i></a></td>
                <td><?= e($p['TenNCC']) ?></td><td><?= e($p['HoTen']) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($p['NgayNhap']))) ?></td>
                <td class="text-end"><?= (int) $p['SoSP'] ?></td><td class="text-end"><?= money($p['TongTien']) ?></td>
            </tr>
            <?php if ($isOpen): ?>
            <tr><td colspan="6" class="bg-light">
                <table class="table table-sm mb-0"><thead><tr><th>Mã hàng</th><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Giá nhập</th><th class="text-end">Thành tiền</th></tr></thead><tbody>
                <?php foreach (admin_purchase_items((int) $p['MaPN']) as $it): ?>
                    <tr><td><?= e($it['MaHang']) ?></td><td><?= e($it['TenSP']) ?></td><td class="text-end"><?= (int) $it['SoLuong'] ?></td><td class="text-end"><?= money($it['GiaNhap']) ?></td><td class="text-end"><?= money($it['SoLuong'] * $it['GiaNhap']) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </td></tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$list['rows']): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có phiếu nhập nào.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> phiếu</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
