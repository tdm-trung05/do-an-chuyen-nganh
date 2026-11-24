<?php
/** Quản lý danh mục (hai cấp: bảng DANHMUC có MaDMCha). Thêm, sửa tên, đổi danh mục cha, xóa. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('danh-muc');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $id  = (int) (admin_int($_POST['id'] ?? '') ?? 0);
        $act = (string) ($_POST['hanh-dong'] ?? '');
        if ($act === 'cap-nhat' || $act === 'doi-ten') {
            $res = admin_category_update($id, (string) ($_POST['ten'] ?? ''), (int) (admin_int($_POST['cha'] ?? '') ?? 0));
        } elseif ($act === 'xoa') {
            $res = admin_category_delete($id);
        } else {
            $res = admin_category_add((string) ($_POST['ten'] ?? ''), (int) (admin_int($_POST['cha'] ?? '') ?? 0));
        }
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(aurl('danh-muc/index.php'));
}

$cats  = admin_categories();
$roots = array_filter($cats, static fn($c) => $c['MaDMCha'] === null);
admin_begin('Danh mục', 'danh-muc', 'Nhóm sản phẩm, có thể chia thành danh mục con (tối đa hai cấp).', '<button class="btn btn-accent" type="button" data-bs-toggle="modal" data-bs-target="#formModal"><i class="bi bi-plus-lg"></i> Thêm danh mục</button>');
$parentOptions = static function (int $self, ?int $selected) use ($roots): string {
    $h = '<option value="0">Không có (danh mục gốc)</option>';
    foreach ($roots as $r) {
        if ((int) $r['MaDM'] === $self) {
            continue;
        }
        $h .= '<option value="' . (int) $r['MaDM'] . '"' . ($selected === (int) $r['MaDM'] ? ' selected' : '') . '>' . e($r['TenDM']) . '</option>';
    }
    return $h;
};
$rows = [];
foreach ($cats as $c) {
    $id   = (int) $c['MaDM'];
    $edit = '<form method="post" class="d-flex gap-2 flex-wrap">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="cap-nhat"><input type="hidden" name="id" value="' . $id . '">'
        . '<input class="form-control form-control-sm" style="max-width:240px" type="text" name="ten" maxlength="100" required value="' . e($c['TenDM']) . '" aria-label="Tên danh mục">'
        . '<select class="form-select form-select-sm" style="max-width:220px" name="cha" aria-label="Danh mục cha">' . $parentOptions($id, $c['MaDMCha'] === null ? null : (int) $c['MaDMCha']) . '</select>'
        . '<button class="btn btn-sm btn-outline-ink" type="submit">Lưu</button></form>';
    $del = '<form method="post" onsubmit="return confirm(\'Xóa danh mục này?\')">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="xoa"><input type="hidden" name="id" value="' . $id . '">'
        . '<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Xóa</button></form>';
    $rows[] = [$id, raw(($c['Cha'] ? '— ' : '') . $edit), (int) $c['Dem'], raw($del)];
}
?>
<section class="card-box"><?php data_table(['ID', 'Tên và danh mục cha', 'Số sản phẩm', ''], $rows, ['right' => [2]]); ?></section>
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" class="modal-content">
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Thêm danh mục</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body"><div class="mb-3"><label class="form-label" for="tdm">Tên danh mục</label><input id="tdm" name="ten" class="form-control" type="text" maxlength="100" required></div>
        <div><label class="form-label" for="cha">Danh mục cha</label><select id="cha" name="cha" class="form-select"><?= $parentOptions(0, null) ?></select></div></div>
    <div class="modal-footer"><button class="btn btn-outline-ink" type="button" data-bs-dismiss="modal">Hủy</button><button class="btn btn-ink" type="submit">Lưu</button></div>
</form></div></div>
<?php admin_end(); ?>
