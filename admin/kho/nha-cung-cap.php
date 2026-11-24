<?php
/** MH13 – Quản lý nhà cung cấp (bảng NHACUNGCAP): thêm, sửa, xóa khi chưa có phiếu nhập. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('nha-cung-cap');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
        redirect(aurl('kho/nha-cung-cap.php'));
    }
    $id = (int) (admin_int($_POST['id'] ?? '') ?? 0);
    if (($_POST['hanh-dong'] ?? '') === 'xoa') {
        $res = admin_supplier_delete($id);
    } else {
        $res = admin_supplier_save($id, (string) ($_POST['ten'] ?? ''), (string) ($_POST['sdt'] ?? ''), (string) ($_POST['dia-chi'] ?? ''));
    }
    flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    redirect(aurl('kho/nha-cung-cap.php' . (!$res['ok'] && ($_POST['hanh-dong'] ?? '') !== 'xoa' && $id ? '?sua=' . $id : '')));
}

$suppliers = admin_suppliers();
$editId    = (int) (admin_int($_GET['sua'] ?? '') ?? 0);
$edit      = ['MaNCC' => 0, 'TenNCC' => '', 'SoDienThoai' => '', 'DiaChi' => ''];
foreach ($suppliers as $s) {
    if ((int) $s['MaNCC'] === $editId) {
        $edit = $s;
    }
}
admin_begin('Nhà cung cấp', 'nha-cung-cap', 'Nhà cung cấp dùng khi lập phiếu nhập kho.');
?>
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card-box">
            <div class="table-responsive"><table class="table align-middle data-table mb-0">
                <thead><tr><th>ID</th><th>Tên nhà cung cấp</th><th>Số điện thoại</th><th>Địa chỉ</th><th class="text-end">Số phiếu</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td><?= (int) $s['MaNCC'] ?></td><td><?= e($s['TenNCC']) ?></td><td><?= e($s['SoDienThoai']) ?></td><td><?= e($s['DiaChi']) ?></td>
                        <td class="text-end"><?= (int) $s['SoPhieu'] ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-ink" href="<?= e(aurl('kho/nha-cung-cap.php?sua=' . (int) $s['MaNCC'])) ?>" title="Sửa"><i class="bi bi-pencil"></i></a>
                            <?php if ((int) $s['SoPhieu'] === 0): ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Xóa nhà cung cấp này?');">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['MaNCC'] ?>"><input type="hidden" name="hanh-dong" value="xoa">
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Xóa"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$suppliers): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có nhà cung cấp nào.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card-box"><div class="head"><h2><?= $edit['MaNCC'] ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp' ?></h2></div><div class="body">
            <form method="post">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['MaNCC'] ?>">
                <div class="mb-3"><label class="form-label" for="ten">Tên nhà cung cấp</label><input id="ten" name="ten" class="form-control" maxlength="200" required value="<?= e($edit['TenNCC']) ?>"></div>
                <div class="mb-3"><label class="form-label" for="sdt">Số điện thoại</label><input id="sdt" name="sdt" class="form-control" maxlength="15" value="<?= e($edit['SoDienThoai']) ?>"></div>
                <div class="mb-3"><label class="form-label" for="dc">Địa chỉ</label><input id="dc" name="dia-chi" class="form-control" maxlength="255" value="<?= e($edit['DiaChi']) ?>"></div>
                <div class="d-grid gap-2">
                    <button class="btn btn-ink" type="submit"><?= $edit['MaNCC'] ? 'Lưu thay đổi' : 'Thêm nhà cung cấp' ?></button>
                    <?php if ($edit['MaNCC']): ?><a class="btn btn-outline-ink" href="<?= e(aurl('kho/nha-cung-cap.php')) ?>">Hủy sửa</a><?php endif; ?>
                </div>
            </form>
        </div></section>
    </div>
</div>
<?php admin_end(); ?>
