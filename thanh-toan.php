<?php
/** MH06 – Thanh toán: địa chỉ giao, phương thức (khi nhận hàng hoặc chuyển khoản). Đặt hàng qua thủ tục sp_DatHang. */
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_customer();   // chỉ khách hàng đã đăng nhập
$uid = (int) $me['MaND'];
admin_auto_cancel_overdue();   // hủy đơn chuyển khoản quá hạn, trả lại tồn kho

$items = cart_items($uid);
if (!$items) {
    flash_set('info', 'Giỏ hàng đang trống, hãy chọn sản phẩm trước khi thanh toán.');
    redirect(url('gio-hang.php'));
}
foreach ($items as $row) {
    if ($row['problem'] !== '') {
        flash_set('warning', 'Giỏ hàng có sản phẩm chưa đặt được: ' . $row['product']['name'] . ' – ' . $row['problem']);
        redirect(url('gio-hang.php'));
    }
}
$subtotal = array_sum(array_column($items, 'line'));

$addresses = user_addresses($uid);
$errors = [];
$old = [
    'dia_chi' => $addresses ? (string) $addresses[0]['MaDC'] : 'moi',
    'pttt'    => 'cod',
    'nn'      => $me['HoTen'],
    'sdt'     => $me['SoDienThoai'] ?? '',
    'dc'      => '',
    'ghi_chu' => '',
];

if (is_post()) {
    foreach (['dia_chi', 'pttt', 'nn', 'sdt', 'dc', 'ghi_chu'] as $k) {
        $v = $_POST[$k] ?? $old[$k];
        $old[$k] = is_scalar($v) ? trim((string) $v) : (string) $old[$k];   // giá trị dạng mảng giả mạo bị bỏ qua
    }
    if (!csrf_verify()) {
        $errors[] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    }
    if (!in_array($old['pttt'], ['cod', 'ck'], true)) {
        $errors[] = 'Hãy chọn phương thức thanh toán.';
    }
    if (mb_strlen($old['ghi_chu'], 'UTF-8') > 200) {
        $errors[] = 'Ghi chú tối đa 200 ký tự.';
    }

    $addressId = 0;
    if ($old['dia_chi'] === 'moi' || !$addresses) {
        $phone = normalize_phone($old['sdt']);
        if (mb_strlen($old['nn'], 'UTF-8') < 2 || mb_strlen($old['nn'], 'UTF-8') > 100) { $errors[] = 'Hãy nhập tên người nhận (2–100 ký tự).'; }
        if ($phone === '')                                                             { $errors[] = 'Số điện thoại không hợp lệ (ví dụ 0901234567).'; }
        if (mb_strlen($old['dc'], 'UTF-8') < 8 || mb_strlen($old['dc'], 'UTF-8') > 255) { $errors[] = 'Hãy nhập địa chỉ chi tiết (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).'; }
        if (!$errors) {
            // Địa chỉ đầu tiên của khách tự thành mặc định
            db_exec(
                'INSERT INTO DIACHI (MaND, NguoiNhan, SoDienThoai, DiaChiChiTiet, MacDinh) VALUES (?, ?, ?, ?, ?)',
                [$uid, $old['nn'], $phone, $old['dc'], $addresses ? 0 : 1]
            );
            $addressId = (int) db()->lastInsertId();
            // Nếu đặt hàng lỗi bên dưới, form hiện lại với địa chỉ vừa lưu (tránh lưu trùng khi thử lại)
            $addresses = user_addresses($uid);
            $old['dia_chi'] = (string) $addressId;
        }
    } else {
        $addressId = (int) $old['dia_chi'];
        $own = array_filter($addresses, static fn($a) => (int) $a['MaDC'] === $addressId);
        if (!$own) { $errors[] = 'Địa chỉ giao hàng không hợp lệ.'; }
    }

    if (!$errors && $addressId > 0) {
        $r = place_order($uid, $addressId, $old['pttt'] === 'ck' ? 'Chuyển khoản' : 'Thanh toán khi nhận hàng', $old['ghi_chu']);
        if ($r['ok']) {
            redirect(url('dat-hang-thanh-cong.php?ma=' . $r['id']));
        }
        $errors[] = $r['msg'];
    }
}

page_begin('Thanh toán', ['Giỏ hàng' => 'gio-hang.php', 'Thanh toán' => null]);
?>
<div class="container">
    <?php page_title('Thanh toán'); ?>
    <div class="checkout-steps">
        <span class="step done"><span class="n"><i class="bi bi-check"></i></span> Giỏ hàng</span><span class="sep"></span>
        <span class="step current"><span class="n">2</span> Giao hàng và thanh toán</span><span class="sep"></span>
        <span class="step"><span class="n">3</span> Hoàn tất</span>
    </div>
    <?php if ($errors): ?>
    <div class="alert alert-danger small" role="alert"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form action="<?= url('thanh-toan.php') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <section class="panel">
                    <h2 class="panel-title">1. Địa chỉ giao hàng</h2>
                    <?php if ($addresses): ?>
                    <div class="row g-3">
                        <?php foreach ($addresses as $a): ?>
                        <div class="col-md-6">
                            <label class="choice-card w-100 h-100">
                                <input class="form-check-input mt-1" type="radio" name="dia_chi" value="<?= (int) $a['MaDC'] ?>" <?= $old['dia_chi'] === (string) $a['MaDC'] ? 'checked' : '' ?>>
                                <span><strong><?= e($a['NguoiNhan']) ?></strong> · <?= e($a['SoDienThoai']) ?><?= (int) $a['MacDinh'] === 1 ? ' <span class="badge text-bg-dark">Mặc định</span>' : '' ?><br><span class="small text-muted"><?= e($a['DiaChiChiTiet']) ?></span></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <div class="col-md-6">
                            <label class="choice-card w-100 h-100">
                                <input class="form-check-input mt-1" type="radio" name="dia_chi" value="moi" <?= $old['dia_chi'] === 'moi' ? 'checked' : '' ?>>
                                <span><strong>+ Giao đến địa chỉ mới</strong><br><span class="small text-muted">Địa chỉ sẽ được lưu vào sổ địa chỉ.</span></span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div id="newAddress" class="<?= $addresses ? 'mt-3' : '' ?>"<?= $addresses && $old['dia_chi'] !== 'moi' ? ' hidden' : '' ?>>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="nn">Người nhận</label><input id="nn" name="nn" class="form-control" type="text" maxlength="100" value="<?= e($old['nn']) ?>"></div>
                            <div class="col-md-6"><label class="form-label" for="sdt">Số điện thoại</label><input id="sdt" name="sdt" class="form-control" type="tel" maxlength="15" value="<?= e($old['sdt']) ?>"></div>
                            <div class="col-12"><label class="form-label" for="dc">Địa chỉ chi tiết</label><input id="dc" name="dc" class="form-control" type="text" maxlength="255" value="<?= e($old['dc']) ?>" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"></div>
                        </div>
                    </div>
                </section>
                <section class="panel">
                    <h2 class="panel-title">2. Phương thức thanh toán</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="choice-card w-100"><input class="form-check-input mt-1" type="radio" name="pttt" value="cod" <?= $old['pttt'] !== 'ck' ? 'checked' : '' ?>><span><strong>Thanh toán khi nhận hàng</strong><br><span class="small text-muted">Trả tiền mặt cho người giao hàng.</span></span></label></div>
                        <div class="col-md-6"><label class="choice-card w-100"><input class="form-check-input mt-1" type="radio" name="pttt" value="ck" <?= $old['pttt'] === 'ck' ? 'checked' : '' ?>><span><strong>Chuyển khoản</strong><br><span class="small text-muted">Đơn ở trạng thái “Chờ thanh toán” đến khi nhân viên xác nhận (giữ tối đa <?= (int) PAYMENT_HOLD_HOURS ?> giờ).</span></span></label></div>
                    </div>
                </section>
                <section class="panel">
                    <h2 class="panel-title">3. Ghi chú</h2>
                    <textarea class="form-control" name="ghi_chu" rows="3" maxlength="200" placeholder="Ghi chú cho người giao hàng (không bắt buộc)"><?= e($old['ghi_chu']) ?></textarea>
                </section>
            </div>
            <div class="col-lg-4">
                <div class="panel summary-box">
                    <h2 class="panel-title">Đơn hàng của bạn</h2>
                    <?php foreach ($items as $row): ?>
                    <div class="summary-row small"><span><?= e($row['product']['name']) ?> × <?= (int) $row['qty'] ?></span><span><?= money($row['line']) ?></span></div>
                    <?php endforeach; ?>
                    <div class="summary-row"><span>Phí giao hàng</span><span><?= money((int) SHIPPING_FEE) ?></span></div>
                    <div class="summary-row total"><span>Tổng cộng</span><span class="price"><?= money($subtotal + (int) SHIPPING_FEE) ?></span></div>
                    <button class="btn btn-accent w-100 mt-3" type="submit">Đặt hàng</button>
                    <a class="btn btn-link w-100 mt-1" href="<?= url('gio-hang.php') ?>">Quay lại giỏ hàng</a>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
// Ẩn/hiện ô nhập địa chỉ mới theo lựa chọn địa chỉ
(function () {
  var box = document.getElementById('newAddress');
  var radios = document.querySelectorAll('input[name="dia_chi"]');
  if (!box || !radios.length) { return; }
  function sync() {
    var v = document.querySelector('input[name="dia_chi"]:checked');
    box.hidden = !(v && v.value === 'moi');
  }
  radios.forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
<?php page_end(); ?>
