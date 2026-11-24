<?php
/** Loại thông số theo danh mục (bảng THONGSO): thêm, sửa, xóa. Ví dụ: ổ cắm có số chấu, aptomat có số cực. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('thong-so');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $id  = (int) (admin_int($_POST['id'] ?? '') ?? 0);
        $act = (string) ($_POST['hanh-dong'] ?? '');
        if ($act === 'cap-nhat') {
            $res = admin_spec_update($id, (string) ($_POST['ten'] ?? ''), (string) ($_POST['don-vi'] ?? ''), (string) ($_POST['nhom'] ?? ''));
        } elseif ($act === 'xoa') {
            $res = admin_spec_delete($id);
        } else {
            $res = admin_spec_add((int) (admin_int($_POST['dm'] ?? '') ?? 0), (string) ($_POST['ten'] ?? ''), (string) ($_POST['don-vi'] ?? ''), (string) ($_POST['nhom'] ?? ''));
        }
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(aurl('thong-so/index.php'));
}

admin_begin('Thông số', 'thong-so', 'Mỗi danh mục có bộ thông số riêng, giá trị lưu theo từng sản phẩm.');
$cats = admin_categories();
$rows = [];
foreach (admin_spec_types() as $t) {
    $id   = (int) $t['MaTS'];
    $edit = '<form method="post" class="d-flex gap-2 flex-wrap">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="cap-nhat"><input type="hidden" name="id" value="' . $id . '">'
        . '<input class="form-control form-control-sm" style="max-width:200px" name="ten" maxlength="100" required value="' . e($t['TenTS']) . '" aria-label="Tên thông số">'
        . '<input class="form-control form-control-sm" style="max-width:90px" name="don-vi" maxlength="20" value="' . e((string) $t['DonVi']) . '" placeholder="Đơn vị" aria-label="Đơn vị">'
        . '<input class="form-control form-control-sm" style="max-width:200px" name="nhom" maxlength="50" value="' . e((string) $t['NhomTS']) . '" placeholder="Nhóm" aria-label="Nhóm">'
        . '<button class="btn btn-sm btn-outline-ink" type="submit">Lưu</button></form>';
    $del  = '<form method="post" onsubmit="return confirm(\'Xóa thông số này?\')">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="xoa"><input type="hidden" name="id" value="' . $id . '">'
        . '<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button></form>';
    $rows[] = [$id, raw($edit), $t['TenDM'], (int) $t['Dem'], raw($del)];
}
?>
<section class="card-box mb-3">
    <form method="post" class="table-toolbar flex-wrap">
        <?= csrf_field() ?>
        <select class="form-select" style="max-width:220px" name="dm" required aria-label="Danh mục áp dụng"><option value="">— Danh mục áp dụng —</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['MaDM'] ?>"><?= e(($c['Cha'] ? $c['Cha'] . ' › ' : '') . $c['TenDM']) ?></option><?php endforeach; ?></select>
        <input class="form-control" style="max-width:200px" name="ten" maxlength="100" placeholder="Tên thông số" required aria-label="Tên thông số">
        <input class="form-control" style="max-width:100px" name="don-vi" maxlength="20" placeholder="Đơn vị" aria-label="Đơn vị">
        <input class="form-control" style="max-width:200px" name="nhom" maxlength="50" placeholder="Nhóm (không bắt buộc)" aria-label="Nhóm">
        <button class="btn btn-accent" type="submit"><i class="bi bi-plus-lg"></i> Thêm thông số</button>
    </form>
</section>
<section class="card-box"><?php data_table(['ID', 'Tên, đơn vị, nhóm', 'Danh mục áp dụng', 'SP đã nhập', ''], $rows, ['right' => [3]]); ?></section>
<?php admin_end(); ?>
