<?php
/** MH14 / UC18 – Trả lời yêu cầu báo giá: nhập đơn giá, hiệu lực, gửi lại. */
require_once __DIR__ . '/../../includes/bootstrap.php';
$me = admin_guard('bao-gia');

$id    = (int) (admin_int($_GET['ma'] ?? $_POST['ma'] ?? '') ?? 0);
$quote = $id > 0 ? admin_quote($id) : null;
if ($quote === null) {
    flash_set('danger', 'Không tìm thấy yêu cầu báo giá.');
    redirect(aurl('bao-gia/index.php'));
}

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif (($_POST['hanh-dong'] ?? '') === 'khong-bao-gia') {
        $res = admin_quote_decline($id, (int) $me['MaND'], (string) ($_POST['ly-do'] ?? ''));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    } else {
        $res = admin_quote_reply($id, (int) $me['MaND'], (array) ($_POST['gia'] ?? []), (string) ($_POST['han'] ?? ''));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(aurl('bao-gia/chi-tiet.php?ma=' . $id));
}

$open = $quote['TrangThai'] === 'Mới';
admin_begin('Báo giá ' . quote_code($id), 'bao-gia',
    $quote['HoTen'] . ' · ' . $quote['Email'] . ' · gửi ngày ' . date('d/m/Y', strtotime($quote['NgayGui'])),
    '<a class="btn btn-outline-ink" href="' . e(aurl('bao-gia/index.php')) . '"><i class="bi bi-arrow-left"></i> Danh sách</a>');
?>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="ma" value="<?= (int) $id ?>">
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card-box"><div class="head"><h2>Các dòng sản phẩm</h2><?= badge($quote['TrangThai']) ?></div>
            <div class="table-responsive"><table class="table align-middle mb-0 data-table">
                <thead><tr><th>Mã hàng</th><th>Sản phẩm</th><th class="text-end">SL</th><th style="width:180px">Đơn giá báo (₫)</th></tr></thead>
                <tbody>
                    <?php foreach ($quote['items'] as $it): ?>
                    <tr><td><?= e($it['MaHang']) ?></td><td><?= e($it['TenSP']) ?></td><td class="text-end"><?= (int) $it['SoLuong'] ?></td>
                        <td><?php if ($open): ?><input class="form-control" type="number" name="gia[<?= (int) $it['MaSP'] ?>]" min="0" step="1" aria-label="Đơn giá báo" value="<?= e((string) ($it['DonGiaBao'] ?? '')) ?>"><?php else: ?><?= $it['DonGiaBao'] === null ? '—' : e(money($it['DonGiaBao'])) ?><?php endif; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card-box mb-3"><div class="head"><h2>Ghi chú của khách</h2></div><div class="body small"><?= $quote['GhiChu'] ? e($quote['GhiChu']) : '<span class="text-muted">Không có ghi chú.</span>' ?></div></section>
        <?php if ($open): ?>
        <section class="card-box mb-3"><div class="head"><h2>Gửi báo giá</h2></div><div class="body">
            <label class="form-label" for="hh">Hiệu lực đến</label><input id="hh" name="han" class="form-control mb-3" type="date" min="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d', strtotime('+14 days'))) ?>">
            <button class="btn btn-ink w-100" type="submit">Gửi lại cho khách</button>
        </div></section>
        <section class="card-box"><div class="head"><h2>Không báo giá được</h2></div><div class="body">
            <label class="form-label" for="ld">Lý do (khách sẽ thấy)</label><input id="ld" name="ly-do" form="f-khong-bg" class="form-control mb-3" type="text" maxlength="255">
            <button class="btn btn-outline-danger w-100" type="submit" form="f-khong-bg">Báo không báo giá được</button>
        </div></section>
        <?php else: ?>
        <section class="card-box"><div class="head"><h2>Kết quả xử lý</h2></div><div class="body small">
            <div>Nhân viên xử lý: <?= e($quote['NhanVien'] ?? '—') ?></div>
            <?php if ($quote['NgayTraLoi']): ?><div>Trả lời lúc: <?= e(date('d/m/Y H:i', strtotime($quote['NgayTraLoi']))) ?></div><?php endif; ?>
            <?php if ($quote['HanHieuLuc']): ?><div>Hiệu lực đến: <?= e(date('d/m/Y', strtotime($quote['HanHieuLuc']))) ?></div><?php endif; ?>
            <?php if ($quote['LyDoKhongBG']): ?><div>Lý do: <?= e($quote['LyDoKhongBG']) ?></div><?php endif; ?>
        </div></section>
        <?php endif; ?>
    </div>
</div>
</form>
<form id="f-khong-bg" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="ma" value="<?= (int) $id ?>">
    <input type="hidden" name="hanh-dong" value="khong-bao-gia">
</form>
<?php admin_end(); ?>
