<?php
/** MH24 – Quản lý đánh giá: xem đánh giá của khách, nhập phản hồi của cửa hàng. */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_guard('danh-gia');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $res = admin_review_reply((int) (admin_int($_POST['ma'] ?? '') ?? 0), (string) ($_POST['phan-hoi'] ?? ''));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(request_uri());
}

$q      = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['tt'] ?? '');
$filter = in_array($filter, ['Chưa phản hồi', 'Đã phản hồi'], true) ? $filter : '';
$list   = admin_reviews($q, $filter, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Đánh giá sản phẩm', 'danh-gia', 'Xem đánh giá của khách và nhập phản hồi của cửa hàng. Phản hồi hiện ngay dưới đánh giá ở trang sản phẩm.');
?>
<section class="card-box mb-3">
    <?php admin_filter_form('Tìm theo sản phẩm, mã hàng, khách, nội dung', $q, ['Chưa phản hồi', 'Đã phản hồi'], $filter); ?>
    <?php if (!$list['rows']): ?>
    <div class="body text-center text-muted py-4">Chưa có đánh giá nào khớp bộ lọc.</div>
    <?php else: ?>
    <div class="table-responsive"><table class="table align-middle data-table mb-0">
        <thead><tr><th>Ngày</th><th>Sản phẩm</th><th>Khách</th><th>Số sao</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $r): ?>
            <tr>
                <td><?= e(date('d/m/Y', strtotime($r['NgayDG']))) ?></td>
                <td><?= e($r['TenSP']) ?><br><small class="text-muted"><?= e($r['MaHang']) ?></small></td>
                <td><?= e($r['HoTen']) ?></td>
                <td><?= (int) $r['SoSao'] ?>/5</td>
                <td><?= badge($r['PhanHoi'] !== null ? 'Đã phản hồi' : 'Chưa phản hồi') ?></td>
            </tr>
            <tr><td colspan="5" class="small border-top-0 pt-0">
                <div class="text-muted">Nhận xét: <?= $r['NoiDung'] !== null && $r['NoiDung'] !== '' ? nl2br(e($r['NoiDung'])) : '(khách chỉ chấm sao)' ?></div>
                <form method="post" class="row g-2 mt-1">
                    <?= csrf_field() ?><input type="hidden" name="ma" value="<?= (int) $r['MaDG'] ?>">
                    <div class="col-md-10"><input name="phan-hoi" class="form-control form-control-sm" type="text" maxlength="500" value="<?= e((string) $r['PhanHoi']) ?>" placeholder="Phản hồi của cửa hàng (để trống rồi gửi là xóa phản hồi)" aria-label="Phản hồi của cửa hàng"></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-ink w-100" type="submit"><?= $r['PhanHoi'] !== null ? 'Cập nhật' : 'Gửi phản hồi' ?></button></div>
                </form>
            </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> đánh giá</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
