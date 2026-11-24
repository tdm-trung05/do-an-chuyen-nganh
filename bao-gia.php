<?php
/** MH17 – Yêu cầu báo giá: chọn sản phẩm và số lượng, gửi yêu cầu, xem và trả lời báo giá đã nhận (bảng BAOGIA). */
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_customer();   // chỉ khách hàng đã đăng nhập
$uid = (int) $me['MaND'];

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif (($_POST['hanh_dong'] ?? '') === 'tra-loi') {
        $qid = (int) ($_POST['ma'] ?? 0);
        if (($_POST['ket_qua'] ?? '') === 'chap-nhan') {
            $res = quote_accept($uid, $qid, (int) ($_POST['dia_chi'] ?? 0), ($_POST['pttt'] ?? '') === 'ck' ? 'Chuyển khoản' : 'Thanh toán khi nhận hàng');
            if ($res['ok']) {
                flash_set('success', $res['msg']);
                redirect(url('dat-hang-thanh-cong.php?ma=' . $res['id']));
            }
        } else {
            $res = quote_reject($uid, $qid);
        }
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
        redirect(url('bao-gia.php#da-nhan'));   // sau khi trả lời báo giá, mở đúng tab "Báo giá đã nhận"
    } else {
        $lines = [];
        foreach ((array) ($_POST['sp'] ?? []) as $i => $pid) {
            $lines[(int) $pid] = ($lines[(int) $pid] ?? 0) + (int) (((array) ($_POST['sl'] ?? []))[$i] ?? 0);
        }
        $res = quote_create($uid, $lines, trim((string) ($_POST['ghi_chu'] ?? '')));
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(url('bao-gia.php'));
}

$products = quote_pick_products();
$preSp     = (int) ($_GET['sp'] ?? 0);   // từ nút "Yêu cầu báo giá" ở trang chi tiết / thẻ sản phẩm / xem nhanh
$quotes   = quotes_of_user($uid);
$addresses = user_addresses($uid);
page_begin('Yêu cầu báo giá', ['Yêu cầu báo giá' => null], 'bao-gia');
?>
<div class="container">
    <?php page_title('Yêu cầu báo giá', 'Dành cho sản phẩm chưa niêm yết giá hoặc đơn số lượng lớn cho công trình.'); ?>
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#gui-moi" type="button" role="tab">Gửi yêu cầu mới</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#da-nhan" type="button" role="tab">Báo giá đã nhận (<?= count($quotes) ?>)</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="gui-moi" role="tabpanel">
            <form action="<?= e(url('bao-gia.php')) ?>" method="post" class="row g-4">
                <?= csrf_field() ?>
                <div class="col-lg-8">
                    <section class="panel p-0 table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Sản phẩm</th><th style="width:140px">Số lượng</th></tr></thead>
                            <tbody>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <tr>
                                    <td><select class="form-select" name="sp[]" aria-label="Sản phẩm <?= $i + 1 ?>">
                                        <option value="0">— Chọn sản phẩm —</option>
                                        <?php foreach ($products as $p): ?><option value="<?= (int) $p['MaSP'] ?>"<?= $i === 0 && $preSp === (int) $p['MaSP'] ? ' selected' : '' ?>><?= e($p['MaHang']) ?> – <?= e($p['TenSP']) ?></option><?php endforeach; ?>
                                    </select></td>
                                    <td><input class="form-control" type="number" name="sl[]" min="1" max="9999" value="1" aria-label="Số lượng"></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                        <div class="p-3 border-top small text-muted">Dòng để trống ("Chọn sản phẩm") sẽ được bỏ qua. Chọn trùng một sản phẩm thì số lượng được cộng lại.</div>
                    </section>
                </div>
                <div class="col-lg-4">
                    <section class="panel">
                        <h2 class="panel-title">Thông tin công trình</h2>
                        <div class="mb-3"><label class="form-label" for="cg">Ghi chú công trình</label><textarea id="cg" name="ghi_chu" class="form-control" rows="4" maxlength="255" placeholder="Địa điểm, tiến độ, yêu cầu đặc biệt"></textarea></div>
                        <button class="btn btn-accent w-100" type="submit">Gửi yêu cầu báo giá</button>
                        <p class="small text-muted mt-2 mb-0">Nhân viên sẽ nhập đơn giá và gửi lại trong tài khoản của bạn.</p>
                    </section>
                </div>
            </form>
        </div>
        <div class="tab-pane fade" id="da-nhan" role="tabpanel">
            <?php if (!$quotes): ?>
            <div class="panel text-muted">Bạn chưa gửi yêu cầu báo giá nào.</div>
            <?php endif; ?>
            <?php foreach ($quotes as $q): ?>
            <section class="panel p-0 mb-3">
                <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="fw-semibold"><?= e(quote_code((int) $q['MaBG'])) ?></span>
                        <span class="text-muted small ms-2">gửi <?= e(date('d/m/Y', strtotime($q['NgayGui']))) ?> · <?= (int) $q['SoSP'] ?> sản phẩm<?= $q['HanHieuLuc'] ? ' · hiệu lực đến ' . e(date('d/m/Y', strtotime($q['HanHieuLuc']))) : '' ?></span>
                    </div>
                    <div><?= badge($q['TrangThai']) ?><?php if ($q['MaDH']): ?> <a class="small ms-1" href="<?= e(url('don-hang-chi-tiet.php?ma=' . (int) $q['MaDH'])) ?>"><?= e(order_code((int) $q['MaDH'])) ?></a><?php endif; ?></div>
                </div>
                <?php if ($q['LyDoKhongBG']): ?><div class="px-3 pb-2 small text-muted">Lý do: <?= e($q['LyDoKhongBG']) ?></div><?php endif; ?>
                <div class="table-responsive border-top">
                    <table class="table align-middle mb-0 small">
                        <thead><tr><th>Mã hàng</th><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Đơn giá báo</th></tr></thead>
                        <tbody><?php foreach (quote_items((int) $q['MaBG']) as $it): ?>
                            <tr><td><?= e($it['MaHang']) ?></td><td><?= e($it['TenSP']) ?></td><td class="text-end"><?= (int) $it['SoLuong'] ?></td><td class="text-end"><?= $it['DonGiaBao'] === null ? '<span class="text-muted">Chờ báo giá</span>' : e(money($it['DonGiaBao'])) ?></td></tr>
                        <?php endforeach; ?></tbody>
                    </table>
                </div>
                <?php if ($q['TrangThai'] === 'Đã báo giá'): ?>
                <form class="p-3 border-top" action="<?= e(url('bao-gia.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hanh_dong" value="tra-loi">
                    <input type="hidden" name="ma" value="<?= (int) $q['MaBG'] ?>">
                    <?php if ($addresses): ?>
                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <label class="form-label small mb-1" for="dc<?= (int) $q['MaBG'] ?>">Giao đến</label>
                            <select class="form-select form-select-sm" id="dc<?= (int) $q['MaBG'] ?>" name="dia_chi">
                                <?php foreach ($addresses as $a): ?><option value="<?= (int) $a['MaDC'] ?>"><?= e($a['NguoiNhan']) ?> – <?= e($a['DiaChiChiTiet']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1" for="tt<?= (int) $q['MaBG'] ?>">Thanh toán</label>
                            <select class="form-select form-select-sm" id="tt<?= (int) $q['MaBG'] ?>" name="pttt">
                                <option value="cod">Khi nhận hàng</option>
                                <option value="ck">Chuyển khoản (giữ <?= (int) PAYMENT_HOLD_HOURS ?> giờ)</option>
                            </select>
                        </div>
                    </div>
                    <p class="small text-muted mb-2">Chấp nhận sẽ tạo đơn hàng theo đúng đơn giá đã báo, cộng phí giao <?= e(money((int) SHIPPING_FEE)) ?>, và trừ tồn kho.</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-ink" type="submit" name="ket_qua" value="chap-nhan">Chấp nhận và đặt hàng</button>
                        <button class="btn btn-sm btn-outline-ink" type="submit" name="ket_qua" value="tu-choi">Từ chối</button>
                    </div>
                    <?php else: ?>
                    <p class="small mb-2">Bạn cần có địa chỉ giao hàng để chấp nhận báo giá. <a href="<?= e(url('tai-khoan.php')) ?>">Thêm địa chỉ trong tài khoản</a>.</p>
                    <button class="btn btn-sm btn-outline-ink" type="submit" name="ket_qua" value="tu-choi">Từ chối</button>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php page_end(); ?>
