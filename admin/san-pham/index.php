<?php
/** MH11 – Quản lý sản phẩm: danh sách, tìm, lọc, thêm/sửa (form.php), ẩn/hiện (bảng SANPHAM). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('san-pham');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $res = admin_product_toggle((int) (admin_int($_POST['id'] ?? '') ?? 0));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(request_uri());
}

$q      = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['tt'] ?? '');
$filter = in_array($filter, ADMIN_PRODUCT_FILTERS, true) ? $filter : '';
$list   = admin_products($q, $filter, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Sản phẩm', 'san-pham', 'Mỗi mã hàng là một sản phẩm. Sản phẩm không còn bán thì ẩn đi, không xóa.',
    admin_button('Thêm sản phẩm', 'san-pham/form.php'));
$rows = [];
foreach ($list['rows'] as $p) {
    $visible = (int) $p['TrangThai'] === 1;
    $price   = (int) $p['LienHeBaoGia'] === 1 || $p['GiaNiemYet'] === null
        ? raw('<span class="text-muted">Liên hệ báo giá</span>')
        : money($p['GiaBan'] ?? $p['GiaNiemYet']);
    $actions = '<a class="btn btn-sm btn-outline-ink" href="' . e(aurl('san-pham/form.php?ma=' . (int) $p['MaSP'])) . '" title="Xem và sửa"><i class="bi bi-pencil"></i></a> '
        . '<form method="post" class="d-inline">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $p['MaSP'] . '">'
        . '<button class="btn btn-sm btn-outline-ink" type="submit" title="' . ($visible ? 'Ẩn sản phẩm' : 'Hiện lại sản phẩm') . '"><i class="bi ' . ($visible ? 'bi-eye-slash' : 'bi-eye-fill') . '"></i></button></form>';
    $rows[] = [$p['MaHang'], $p['TenSP'], $p['TenDM'], $p['TenDong'] ?: '—', $price, (int) $p['ThoiGianBH'] . ' tháng',
        raw(badge($visible ? 'Đang bán' : 'Đã ẩn')), raw($actions)];
}
?>
<section class="card-box">
    <?php admin_filter_form('Tìm theo tên hoặc mã hàng', $q, ADMIN_PRODUCT_FILTERS, $filter); ?>
    <?php if (!$rows): ?>
    <div class="body text-center text-muted py-4">Không có sản phẩm nào khớp bộ lọc.</div>
    <?php else: ?>
    <?php data_table(['Mã hàng', 'Tên sản phẩm', 'Danh mục', 'Dòng', 'Giá bán', 'Bảo hành', 'Trạng thái', 'Thao tác'], $rows, ['right' => [4]]); ?>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> sản phẩm</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
