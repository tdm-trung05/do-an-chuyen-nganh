<?php
/** Quản lý dòng sản phẩm (Galion, Arteor, Mallia Senses...) của Legrand (bảng DONGSANPHAM). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('dong-sp');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $act = (string) ($_POST['hanh-dong'] ?? '');
        $sid = (int) (admin_int($_POST['id'] ?? '') ?? 0);
        if ($act === 'doi-ten') {
            $res = admin_series_rename($sid, (string) ($_POST['ten'] ?? ''));
        } elseif ($act === 'xoa') {
            $res = admin_series_delete($sid);
        } else {
            $res = admin_series_add((string) ($_POST['ten'] ?? ''));
        }
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(aurl('dong-san-pham/index.php'));
}

admin_begin('Dòng sản phẩm', 'dong-sp', 'Mỗi dòng có thể gắn catalogue riêng.');
$rows = [];
foreach (admin_series_list() as $s) {
    $rename = '<form method="post" class="d-flex gap-2">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="doi-ten"><input type="hidden" name="id" value="' . (int) $s['MaDong'] . '">'
        . '<input class="form-control form-control-sm" type="text" name="ten" maxlength="100" required value="' . e($s['TenDong']) . '" aria-label="Tên dòng sản phẩm">'
        . '<button class="btn btn-sm btn-outline-ink" type="submit">Đổi tên</button></form>';
    $del = '<form method="post" onsubmit="return confirm(\'Xóa dòng sản phẩm này?\')">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="xoa"><input type="hidden" name="id" value="' . (int) $s['MaDong'] . '">'
        . '<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Xóa</button></form>';
    $rows[] = [$s['MaDong'], raw($rename), (int) $s['Dem'], raw($del)];
}
?>
<section class="card-box mb-3">
    <form method="post" class="table-toolbar">
        <?= csrf_field() ?>
        <input class="form-control" style="max-width:340px" type="text" name="ten" maxlength="100" placeholder="Tên dòng sản phẩm mới" aria-label="Tên dòng sản phẩm mới" required>
        <button class="btn btn-accent" type="submit"><i class="bi bi-plus-lg"></i> Thêm dòng sản phẩm</button>
    </form>
</section>
<section class="card-box"><?php data_table(['ID', 'Tên dòng', 'Số mã hàng', ''], $rows, ['right' => [2]]); ?></section>
<?php admin_end(); ?>
