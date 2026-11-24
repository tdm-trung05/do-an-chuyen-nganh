<?php
/** MH11 – Thêm / sửa sản phẩm: thông tin chung, thông số theo danh mục, giá, bảo hành, trạng thái, ảnh chính (bảng SANPHAM, CT_THONGSO, HINHANH). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('san-pham');

$id = (int) (admin_int($_GET['ma'] ?? '') ?? 0);
$p  = $id > 0 ? admin_product($id) : null;
if ($id > 0 && $p === null) {
    flash_set('danger', 'Không tìm thấy sản phẩm.');
    redirect(aurl('san-pham/index.php'));
}
$cats   = admin_categories();
$series = admin_series_list();

// Giá trị hiện trên form: lấy từ sản phẩm đang sửa, hoặc mặc định khi thêm mới.
$old = [
    'ma'   => $p ? (string) $p['MaHang'] : '',
    'ten'  => $p ? (string) $p['TenSP'] : '',
    'dm'   => $p ? (int) $p['MaDM'] : (int) (admin_int($_GET['dm'] ?? '') ?? ($cats[0]['MaDM'] ?? 0)),
    'dong' => $p ? (int) ($p['MaDong'] ?? 0) : 0,
    'mota' => $p ? (string) ($p['MoTa'] ?? '') : '',
    'gia'  => $p && $p['GiaNiemYet'] !== null ? (string) (int) $p['GiaNiemYet'] : '',
    'gb'   => $p && $p['GiaBan'] !== null ? (string) (int) $p['GiaBan'] : '',
    'bg'   => $p ? (int) $p['LienHeBaoGia'] === 1 : false,
    'bh'   => $p ? (string) (int) $p['ThoiGianBH'] : '12',
    'tt'   => $p ? (string) $p['TinhTrangKD'] : 'Đang kinh doanh',
    'hien' => $p ? (int) $p['TrangThai'] === 1 : true,
    'xx'   => $p ? (string) ($p['XuatXu'] ?? '') : '',
    'nam'  => $p ? (string) ($p['NamRaMat'] ?? '') : '',
    'pk'   => $p ? (string) ($p['PhuKien'] ?? '') : '',
    'vid'  => $p ? (string) ($p['VideoUrl'] ?? '') : '',
    'mtn'  => $p ? (string) ($p['MoTaNgan'] ?? '') : '',
    'htbh' => $p ? (string) ($p['HinhThucBH'] ?? '') : '',
    'cshm' => $p ? (string) ($p['ChinhSachHauMai'] ?? '') : '',
    'ts'   => [],
];

if (is_post()) {
    // Tệp quá lớn so với post_max_size của PHP: PHP bỏ hết $_POST nên không có mã CSRF để kiểm tra.
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash_set('danger', 'Dữ liệu gửi lên quá lớn (ảnh tối đa 2 MB). Hãy chọn ảnh nhỏ hơn.');
        redirect(request_uri());
    }
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
        redirect(request_uri());
    }
    $old = [
        'ma'   => trim((string) ($_POST['ma'] ?? '')),
        'ten'  => trim((string) ($_POST['ten'] ?? '')),
        'dm'   => (int) (admin_int($_POST['dm'] ?? '') ?? 0),
        'dong' => (int) (admin_int($_POST['dong'] ?? '') ?? 0),
        'mota' => trim((string) ($_POST['mota'] ?? '')),
        'gia'  => trim((string) ($_POST['gia'] ?? '')),
        'gb'   => trim((string) ($_POST['gb'] ?? '')),
        'bg'   => isset($_POST['bg']),
        'bh'   => trim((string) ($_POST['bh'] ?? '')),
        'tt'   => (string) ($_POST['tt'] ?? 'Đang kinh doanh'),
        'hien' => isset($_POST['hien']),
        'xx'   => trim((string) ($_POST['xx'] ?? '')),
        'nam'  => trim((string) ($_POST['nam'] ?? '')),
        'pk'   => trim((string) ($_POST['pk'] ?? '')),
        'vid'  => trim((string) ($_POST['vid'] ?? '')),
        'mtn'  => trim((string) ($_POST['mtn'] ?? '')),
        'htbh' => trim((string) ($_POST['htbh'] ?? '')),
        'cshm' => trim((string) ($_POST['cshm'] ?? '')),
        'ts'   => is_array($_POST['ts'] ?? null) ? $_POST['ts'] : [],
    ];
    // Nút "Lưu" gửi hanh-dong=luu; đổi danh mục chỉ nạp lại form để hiện đúng bộ thông số, chưa lưu gì.
    if (($_POST['hanh-dong'] ?? '') === 'luu') {
        $res = admin_product_save($id, $old, $_FILES['anh'] ?? null);
        if ($res['ok']) {
            flash_set('success', $res['msg']);
            redirect(aurl('san-pham/form.php?ma=' . $res['id']));
        }
        flash_set('danger', $res['msg']);
    }
}

$dm    = (int) $old['dm'];
$specs = $dm > 0 ? admin_specs_for($dm, $id) : [];
foreach ($specs as &$sp) {
    if (isset($old['ts'][$sp['MaTS']]) && is_scalar($old['ts'][$sp['MaTS']])) {
        $sp['GiaTri'] = (string) $old['ts'][$sp['MaTS']];
    }
}
unset($sp);

admin_begin($p ? 'Sản phẩm ' . $p['MaHang'] : 'Thêm sản phẩm', 'san-pham', 'Mã hàng của nhà sản xuất, ví dụ 282434-C. Sản phẩm mới có tồn kho 0, hãy lập phiếu nhập để có hàng bán.');
?>
<form method="post" enctype="multipart/form-data" id="productForm" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="hanh-dong" id="hanhDong" value="luu">
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card-box mb-3"><div class="head"><h2>Thông tin chung</h2></div><div class="body row g-3">
            <div class="col-md-4"><label class="form-label" for="ma">Mã hàng</label><input id="ma" name="ma" class="form-control" type="text" maxlength="50" required value="<?= e($old['ma']) ?>"></div>
            <div class="col-md-8"><label class="form-label" for="ten">Tên sản phẩm</label><input id="ten" name="ten" class="form-control" type="text" maxlength="200" required value="<?= e($old['ten']) ?>"></div>
            <div class="col-md-6"><label class="form-label" for="dm">Danh mục</label>
                <select id="dm" name="dm" class="form-select" onchange="document.getElementById('hanhDong').value='doi-dm'; this.form.submit();">
                    <?php foreach ($cats as $c): ?><option value="<?= (int) $c['MaDM'] ?>"<?= (int) $c['MaDM'] === $dm ? ' selected' : '' ?>><?= e(($c['Cha'] ? $c['Cha'] . ' › ' : '') . $c['TenDM']) ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">Đổi danh mục sẽ nạp lại bộ thông số; thông số của danh mục cũ bị bỏ khi lưu.</div></div>
            <div class="col-md-6"><label class="form-label" for="dong">Dòng sản phẩm</label>
                <select id="dong" name="dong" class="form-select"><option value="0">Không có</option><?php foreach ($series as $s): ?><option value="<?= (int) $s['MaDong'] ?>"<?= (int) $old['dong'] === (int) $s['MaDong'] ? ' selected' : '' ?>><?= e($s['TenDong']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label" for="mtn">Mô tả ngắn</label><input id="mtn" name="mtn" class="form-control" type="text" maxlength="500" value="<?= e($old['mtn']) ?>"></div>
            <div class="col-12"><label class="form-label" for="mota">Mô tả</label><textarea id="mota" name="mota" class="form-control" rows="5"><?= e($old['mota']) ?></textarea></div>
        </div></section>
        <section class="card-box mb-3"><div class="head"><h2>Thông tin bổ sung</h2><small class="text-muted">Không bắt buộc</small></div><div class="body row g-3">
            <div class="col-md-8"><label class="form-label" for="xx">Xuất xứ</label><input id="xx" name="xx" class="form-control" type="text" maxlength="150" value="<?= e($old['xx']) ?>"></div>
            <div class="col-md-4"><label class="form-label" for="nam">Năm ra mắt</label><input id="nam" name="nam" class="form-control" type="text" inputmode="numeric" maxlength="4" value="<?= e($old['nam']) ?>"></div>
            <div class="col-12"><label class="form-label" for="pk">Phụ kiện đi kèm</label><input id="pk" name="pk" class="form-control" type="text" maxlength="1000" value="<?= e($old['pk']) ?>"></div>
            <div class="col-12"><label class="form-label" for="vid">Đường dẫn video giới thiệu</label><input id="vid" name="vid" class="form-control" type="url" maxlength="255" placeholder="https://" value="<?= e($old['vid']) ?>"></div>
            <div class="col-12"><label class="form-label" for="htbh">Hình thức bảo hành</label><input id="htbh" name="htbh" class="form-control" type="text" maxlength="100" value="<?= e($old['htbh']) ?>"></div>
            <div class="col-12"><label class="form-label" for="cshm">Chính sách hậu mãi</label><textarea id="cshm" name="cshm" class="form-control" rows="3" maxlength="2000"><?= e($old['cshm']) ?></textarea></div>
        </div></section>
        <section class="card-box mb-3"><div class="head"><h2>Thông số kỹ thuật</h2><small class="text-muted">Danh sách thông số đổi theo danh mục; để trống thì không hiện ở trang sản phẩm</small></div><div class="body">
            <?php if (!$specs): ?><p class="text-muted mb-0">Danh mục này chưa có loại thông số nào.</p><?php endif; ?>
            <div class="row g-3">
                <?php foreach ($specs as $sp): ?>
                <div class="col-md-6"><label class="form-label" for="ts<?= (int) $sp['MaTS'] ?>"><?= e($sp['TenTS'] . ($sp['DonVi'] ? ' (' . $sp['DonVi'] . ')' : '')) ?></label><input id="ts<?= (int) $sp['MaTS'] ?>" name="ts[<?= (int) $sp['MaTS'] ?>]" class="form-control" type="text" maxlength="100" value="<?= e((string) ($sp['GiaTri'] ?? '')) ?>"></div>
                <?php endforeach; ?>
            </div>
        </div></section>
    </div>
    <div class="col-lg-4">
        <section class="card-box mb-3"><div class="head"><h2>Ảnh chính</h2></div><div class="body">
            <?php if ($p && !empty($p['Anh'])): ?><img class="img-fluid rounded border mb-2" src="<?= e(product_image($p['Anh'])) ?>" alt="Ảnh hiện tại của <?= e($p['TenSP']) ?>" style="max-height:180px"><?php endif; ?>
            <label class="form-label" for="anh"><?= $p ? 'Thay ảnh chính' : 'Chọn ảnh' ?></label>
            <input id="anh" name="anh" class="form-control" type="file" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">JPG, PNG hoặc WebP, tối đa 2 MB, mỗi cạnh từ 100 đến 6000 điểm ảnh. Ảnh cũ vẫn giữ làm ảnh phụ.</div>
        </div></section>
        <section class="card-box mb-3"><div class="head"><h2>Giá và bảo hành</h2></div><div class="body">
            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="bg" name="bg" value="1"<?= $old['bg'] ? ' checked' : '' ?>><label class="form-check-label" for="bg">Liên hệ báo giá (chưa niêm yết)</label></div>
            <div id="priceBox">
                <div class="mb-3"><label class="form-label" for="gia">Giá niêm yết (₫)</label><input id="gia" name="gia" class="form-control" type="text" inputmode="numeric" value="<?= e($old['gia']) ?>"></div>
                <div class="mb-3"><label class="form-label" for="gb">Giá bán (₫)</label><input id="gb" name="gb" class="form-control" type="text" inputmode="numeric" value="<?= e($old['gb']) ?>"><div class="form-text">Để trống thì bằng giá niêm yết; không được cao hơn giá niêm yết.</div></div>
            </div>
            <div class="mb-0"><label class="form-label" for="bh">Thời hạn bảo hành (tháng)</label><input id="bh" name="bh" class="form-control" type="text" inputmode="numeric" value="<?= e($old['bh']) ?>"></div>
        </div></section>
        <section class="card-box mb-3"><div class="head"><h2>Trạng thái</h2></div><div class="body">
            <div class="mb-3"><label class="form-label" for="tt">Tình trạng kinh doanh</label>
                <select id="tt" name="tt" class="form-select"><?php foreach (['Đang kinh doanh', 'Ngừng kinh doanh', 'Hàng sắp về'] as $t): ?><option<?= $old['tt'] === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="hien" name="hien" value="1"<?= $old['hien'] ? ' checked' : '' ?>><label class="form-check-label" for="hien">Hiện trên website (tắt là ẩn sản phẩm)</label></div>
        </div></section>
    </div>
</div>
<button class="btn btn-ink" type="submit"><i class="bi bi-check-lg"></i> <?= $p ? 'Lưu thay đổi' : 'Thêm sản phẩm' ?></button>
<a class="btn btn-outline-ink" href="<?= e(aurl('san-pham/index.php')) ?>"><i class="bi bi-arrow-left"></i> Danh sách</a>
</form>
<script>
// Bật "Liên hệ báo giá" thì ẩn ô giá (sản phẩm báo giá không lưu giá).
(function () {
    var sw = document.getElementById('bg'), box = document.getElementById('priceBox');
    function sync() { box.hidden = sw.checked; }
    sw.addEventListener('change', sync);
    sync();
})();
</script>
<?php admin_end(); ?>
