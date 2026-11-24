<?php
/** MH15 – Quản lý bảo hành, đổi trả (BB06). */
require_once __DIR__ . '/../../includes/bootstrap.php';
$me = admin_guard('bao-hanh');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $res = admin_warranty_respond((int) (admin_int($_POST['ma'] ?? '') ?? 0), (int) $me['MaND'], (string) ($_POST['ket-qua'] ?? ''), (string) ($_POST['phan-hoi'] ?? ''));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(request_uri());
}

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['tt'] ?? '');
$status = in_array($status, WARRANTY_STATUSES, true) ? $status : '';
$list   = admin_warranties($q, $status, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Bảo hành, đổi trả', 'bao-hanh', 'Tiếp nhận, kiểm tra và phản hồi: bảo hành, đổi sản phẩm hoặc từ chối kèm lý do.');
?>
<section class="card-box mb-3">
    <?php admin_filter_form('Tìm theo mã yêu cầu, mã đơn, sản phẩm, khách', $q, WARRANTY_STATUSES, $status); ?>
    <?php if (!$list['rows']): ?>
    <div class="body text-center text-muted py-4">Không có yêu cầu nào khớp bộ lọc.</div>
    <?php else: ?>
    <div class="table-responsive"><table class="table align-middle data-table mb-0">
        <thead><tr><th>Mã</th><th>Đơn hàng</th><th>Khách</th><th>Sản phẩm</th><th>Loại</th><th>Ngày gửi</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $w): ?>
            <tr><td class="fw-semibold"><?= e(warranty_code((int) $w['MaBH'])) ?></td><td><?= e(order_code((int) $w['MaDH'])) ?></td><td><?= e($w['HoTen']) ?></td><td><?= e($w['TenSP']) ?></td><td><?= e($w['LoaiYeuCau']) ?></td><td><?= e(date('d/m/Y', strtotime($w['NgayTao']))) ?></td><td><?= badge($w['TrangThai']) ?></td></tr>
            <tr><td colspan="7" class="small border-top-0 pt-0">
                <div class="text-muted">Mô tả lỗi: <?= e($w['MoTa']) ?></div>
                <?php if ($w['TrangThai'] === 'Mới'): ?>
                <form method="post" class="row g-2 mt-1">
                    <?= csrf_field() ?><input type="hidden" name="ma" value="<?= (int) $w['MaBH'] ?>">
                    <div class="col-md-3"><select name="ket-qua" class="form-select form-select-sm" aria-label="Kết quả"><option value="Đã bảo hành">Bảo hành</option><option value="Đã đổi sản phẩm">Đổi sản phẩm</option><option value="Từ chối">Từ chối</option></select></div>
                    <div class="col-md-7"><input name="phan-hoi" class="form-control form-control-sm" type="text" maxlength="500" placeholder="Lý do hoặc hướng dẫn gửi hàng (bắt buộc khi từ chối)" aria-label="Phản hồi cho khách"></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-ink w-100" type="submit">Gửi phản hồi</button></div>
                </form>
                <?php elseif ($w['PhanHoi']): ?>
                <div>Phản hồi: <?= e($w['PhanHoi']) ?></div>
                <?php endif; ?>
            </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> yêu cầu</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<?php admin_end(); ?>
