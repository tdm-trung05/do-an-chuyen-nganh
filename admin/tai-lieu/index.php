<?php
/** Tài liệu kỹ thuật, catalogue PDF gắn với dòng sản phẩm hoặc mã hàng (bảng TAILIEU): tải lên, xóa. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('tai-lieu');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif (($_POST['hanh-dong'] ?? '') === 'xoa') {
        $res = admin_document_delete((int) (admin_int($_POST['id'] ?? '') ?? 0));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    } else {
        $target = (string) ($_POST['gan'] ?? '');
        $tid    = (int) (admin_int($_POST[$target === 'sp' ? 'sp' : 'dong'] ?? '') ?? 0);
        $res    = admin_document_add((string) ($_POST['ten'] ?? ''), $target, $tid, $_FILES['tep'] ?? null);
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(aurl('tai-lieu/index.php'));
}

admin_begin('Tài liệu kỹ thuật', 'tai-lieu', 'Catalogue và bản vẽ gắn với từng dòng sản phẩm hoặc mã hàng.');
$rows = [];
foreach (admin_documents() as $d) {
    $del = '<form method="post" onsubmit="return confirm(\'Xóa tài liệu này?\')">' . csrf_field()
        . '<input type="hidden" name="hanh-dong" value="xoa"><input type="hidden" name="id" value="' . (int) $d['MaTL'] . '">'
        . '<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Xóa</button></form>';
    $rows[] = [$d['MaTL'], $d['TenTL'], $d['TenDong'] ?: ($d['MaHang'] ? 'Mã hàng ' . $d['MaHang'] : '—'), $d['Loai'], $d['DungLuong'], raw($del)];
}
$series   = admin_series_list();
$products = db_all('SELECT MaSP, MaHang, TenSP FROM SANPHAM ORDER BY MaHang');
?>
<section class="card-box mb-3">
    <form method="post" enctype="multipart/form-data" class="table-toolbar flex-wrap">
        <?= csrf_field() ?>
        <input class="form-control" style="max-width:240px" name="ten" maxlength="200" placeholder="Tên tài liệu" required aria-label="Tên tài liệu">
        <select class="form-select" style="max-width:150px" name="gan" id="gan" aria-label="Gắn với"><option value="dong">Dòng sản phẩm</option><option value="sp">Mã hàng</option></select>
        <select class="form-select" style="max-width:220px" name="dong" id="g-dong" aria-label="Dòng sản phẩm"><?php foreach ($series as $s): ?><option value="<?= (int) $s['MaDong'] ?>"><?= e($s['TenDong']) ?></option><?php endforeach; ?></select>
        <select class="form-select d-none" style="max-width:260px" name="sp" id="g-sp" aria-label="Mã hàng"><?php foreach ($products as $p): ?><option value="<?= (int) $p['MaSP'] ?>"><?= e($p['MaHang'] . ' · ' . $p['TenSP']) ?></option><?php endforeach; ?></select>
        <input class="form-control" style="max-width:240px" type="file" name="tep" accept="application/pdf,.pdf" required aria-label="Tệp PDF (tối đa 10 MB)">
        <button class="btn btn-accent" type="submit"><i class="bi bi-upload"></i> Tải lên</button>
    </form>
</section>
<section class="card-box"><?php data_table(['ID', 'Tên tài liệu', 'Gắn với', 'Loại', 'Dung lượng', ''], $rows); ?></section>
<script>
(function () {
  var g = document.getElementById('gan');
  function sync() { document.getElementById('g-dong').classList.toggle('d-none', g.value !== 'dong'); document.getElementById('g-sp').classList.toggle('d-none', g.value !== 'sp'); }
  g.addEventListener('change', sync); sync();
})();
</script>
<?php admin_end(); ?>
