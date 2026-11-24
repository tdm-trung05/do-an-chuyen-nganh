<?php
/** MH08 – Tài khoản: thông tin cá nhân, đổi mật khẩu và sổ địa chỉ. */
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_customer();   // chỉ khách hàng đã đăng nhập
$uid = (int) $me['MaND'];

$errors = ['thong-tin' => '', 'mat-khau' => '', 'dia-chi' => ''];
$old    = ['ho_ten' => $me['HoTen'], 'sdt' => $me['SoDienThoai'] ?? '', 'nn' => '', 'dsdt' => '', 'dc' => ''];

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
        redirect(url('tai-khoan.php'));
    }

    if ($action === 'thong-tin') {
        $old['ho_ten'] = trim((string) ($_POST['ho_ten'] ?? ''));
        $old['sdt']    = trim((string) ($_POST['sdt'] ?? ''));
        $phone = normalize_phone($old['sdt']);
        if ($old['ho_ten'] === '' || mb_strlen($old['ho_ten'], 'UTF-8') > 100) {
            $errors['thong-tin'] = 'Họ và tên không được để trống và tối đa 100 ký tự.';
        } elseif ($phone === '') {
            $errors['thong-tin'] = 'Số điện thoại không hợp lệ (ví dụ 0901234567).';
        } else {
            db_exec('UPDATE NGUOIDUNG SET HoTen = ?, SoDienThoai = ? WHERE MaND = ?', [$old['ho_ten'], $phone, $uid]);
            flash_set('success', 'Đã lưu thông tin cá nhân.');
            redirect(url('tai-khoan.php'));
        }
    } elseif ($action === 'mat-khau') {
        $cur = (string) ($_POST['mk0'] ?? '');
        $n1  = (string) ($_POST['mk1'] ?? '');
        $n2  = (string) ($_POST['mk2'] ?? '');
        $hash = (string) db_val('SELECT MatKhauBam FROM NGUOIDUNG WHERE MaND = ?', [$uid]);
        if (!password_verify($cur, $hash)) {
            $errors['mat-khau'] = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($n1) < 8 || strlen($n1) > 72) {
            $errors['mat-khau'] = 'Mật khẩu mới dài từ 8 đến 72 ký tự.';
        } elseif ($n1 !== $n2) {
            $errors['mat-khau'] = 'Mật khẩu nhập lại không khớp.';
        } else {
            db_exec('UPDATE NGUOIDUNG SET MatKhauBam = ? WHERE MaND = ?', [password_hash($n1, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);   // đổi mã phiên sau khi đổi mật khẩu
            unset($_SESSION['pwf']);       // phiên này tiếp tục; các phiên khác của tài khoản bị đăng xuất
            flash_set('success', 'Đã đổi mật khẩu.');
            redirect(url('tai-khoan.php'));
        }
    } elseif ($action === 'them-dia-chi') {
        $old['nn']   = trim((string) ($_POST['nn'] ?? ''));
        $old['dsdt'] = trim((string) ($_POST['dsdt'] ?? ''));
        $old['dc']   = trim((string) ($_POST['dc'] ?? ''));
        $phone = normalize_phone($old['dsdt']);
        if (mb_strlen($old['nn'], 'UTF-8') < 2 || mb_strlen($old['nn'], 'UTF-8') > 100) {
            $errors['dia-chi'] = 'Hãy nhập tên người nhận (2–100 ký tự).';
        } elseif ($phone === '') {
            $errors['dia-chi'] = 'Số điện thoại người nhận không hợp lệ.';
        } elseif (mb_strlen($old['dc'], 'UTF-8') < 8 || mb_strlen($old['dc'], 'UTF-8') > 255) {
            $errors['dia-chi'] = 'Hãy nhập địa chỉ chi tiết (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).';
        } else {
            $first = !db_val('SELECT 1 FROM DIACHI WHERE MaND = ? LIMIT 1', [$uid]);
            db_exec('INSERT INTO DIACHI (MaND, NguoiNhan, SoDienThoai, DiaChiChiTiet, MacDinh) VALUES (?, ?, ?, ?, ?)', [$uid, $old['nn'], $phone, $old['dc'], $first ? 1 : 0]);
            flash_set('success', 'Đã thêm địa chỉ.');
            redirect(url('tai-khoan.php#dia-chi'));
        }
    } elseif ($action === 'xoa-dia-chi' || $action === 'mac-dinh') {
        $aid = (int) ($_POST['ma'] ?? 0);
        $row = db_one('SELECT MaDC, MacDinh FROM DIACHI WHERE MaDC = ? AND MaND = ?', [$aid, $uid]);
        if ($row === null) {
            flash_set('danger', 'Không tìm thấy địa chỉ.');
        } elseif ($action === 'mac-dinh') {
            db_exec('UPDATE DIACHI SET MacDinh = (MaDC = ?) WHERE MaND = ?', [$aid, $uid]);
            flash_set('success', 'Đã đặt làm địa chỉ mặc định.');
        } else {
            db_exec('DELETE FROM DIACHI WHERE MaDC = ? AND MaND = ?', [$aid, $uid]);   // đơn cũ vẫn giữ bản sao địa chỉ
            if ((int) $row['MacDinh'] === 1) {   // xóa địa chỉ mặc định thì chuyển mặc định cho địa chỉ còn lại đầu tiên
                db_exec('UPDATE DIACHI SET MacDinh = 1 WHERE MaND = ? ORDER BY MaDC LIMIT 1', [$uid]);
            }
            flash_set('success', 'Đã xóa địa chỉ.');
        }
        redirect(url('tai-khoan.php#dia-chi'));
    }
}

$addresses = user_addresses($uid);
page_begin('Tài khoản của tôi', ['Tài khoản' => null]);
?>
<div class="container">
    <?php page_title('Tài khoản của tôi'); ?>
    <div class="row g-4">
        <div class="col-lg-3"><?php account_nav('tai-khoan'); ?></div>
        <div class="col-lg-9">
            <section class="panel">
                <h2 class="panel-title">Thông tin cá nhân</h2>
                <?php if ($errors['thong-tin'] !== ''): ?><div class="alert alert-danger small"><?= e($errors['thong-tin']) ?></div><?php endif; ?>
                <form action="<?= url('tai-khoan.php') ?>" method="post" class="row g-3" novalidate>
                    <?= csrf_field() ?><input type="hidden" name="action" value="thong-tin">
                    <div class="col-md-6"><label class="form-label" for="ht">Họ và tên</label><input id="ht" name="ho_ten" class="form-control" type="text" maxlength="100" value="<?= e($old['ho_ten']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="dt">Số điện thoại</label><input id="dt" name="sdt" class="form-control" type="tel" maxlength="15" value="<?= e($old['sdt']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="em">Email</label><input id="em" class="form-control" type="email" value="<?= e($me['Email']) ?>" readonly></div>
                    <div class="col-12"><button class="btn btn-ink" type="submit">Lưu thay đổi</button></div>
                </form>
            </section>
            <section class="panel">
                <h2 class="panel-title">Đổi mật khẩu</h2>
                <?php if ($errors['mat-khau'] !== ''): ?><div class="alert alert-danger small"><?= e($errors['mat-khau']) ?></div><?php endif; ?>
                <form action="<?= url('tai-khoan.php') ?>" method="post" class="row g-3" novalidate>
                    <?= csrf_field() ?><input type="hidden" name="action" value="mat-khau">
                    <div class="col-md-4"><label class="form-label" for="m0">Mật khẩu hiện tại</label><input id="m0" name="mk0" class="form-control" type="password" autocomplete="current-password" required></div>
                    <div class="col-md-4"><label class="form-label" for="m1">Mật khẩu mới</label><input id="m1" name="mk1" class="form-control" type="password" autocomplete="new-password" required></div>
                    <div class="col-md-4"><label class="form-label" for="m2">Nhập lại mật khẩu mới</label><input id="m2" name="mk2" class="form-control" type="password" autocomplete="new-password" required></div>
                    <div class="col-12"><button class="btn btn-outline-ink" type="submit">Đổi mật khẩu</button></div>
                </form>
            </section>
            <section class="panel" id="dia-chi">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="panel-title mb-0">Sổ địa chỉ</h2><button class="btn btn-outline-ink btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addAddress">+ Thêm địa chỉ</button></div>
                <?php if ($errors['dia-chi'] !== ''): ?><div class="alert alert-danger small"><?= e($errors['dia-chi']) ?></div><?php endif; ?>
                <div class="collapse<?= $errors['dia-chi'] !== '' ? ' show' : '' ?> mb-3" id="addAddress">
                    <form action="<?= url('tai-khoan.php') ?>" method="post" class="row g-3" novalidate>
                        <?= csrf_field() ?><input type="hidden" name="action" value="them-dia-chi">
                        <div class="col-md-6"><label class="form-label" for="a-nn">Người nhận</label><input id="a-nn" name="nn" class="form-control" type="text" maxlength="100" value="<?= e($old['nn']) ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="a-sdt">Số điện thoại</label><input id="a-sdt" name="dsdt" class="form-control" type="tel" maxlength="15" value="<?= e($old['dsdt']) ?>"></div>
                        <div class="col-12"><label class="form-label" for="a-dc">Địa chỉ chi tiết</label><input id="a-dc" name="dc" class="form-control" type="text" maxlength="255" value="<?= e($old['dc']) ?>"></div>
                        <div class="col-12"><button class="btn btn-ink" type="submit">Lưu địa chỉ</button></div>
                    </form>
                </div>
                <?php if (!$addresses): ?><p class="text-muted mb-0">Chưa có địa chỉ nào. Địa chỉ cũng được lưu tự động khi bạn đặt hàng.</p><?php endif; ?>
                <div class="row g-3">
                    <?php foreach ($addresses as $a): $isDef = (int) $a['MacDinh'] === 1; ?>
                    <div class="col-md-6"><div class="address-card<?= $isDef ? ' default' : '' ?>">
                        <?php if ($isDef): ?><span class="badge text-bg-dark mb-2">Mặc định</span><?php endif; ?>
                        <div><strong><?= e($a['NguoiNhan']) ?></strong> · <?= e($a['SoDienThoai']) ?></div>
                        <div class="small text-muted mb-2"><?= e($a['DiaChiChiTiet']) ?></div>
                        <div class="d-flex gap-3">
                            <?php if (!$isDef): ?>
                            <form method="post" action="<?= url('tai-khoan.php') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="mac-dinh"><input type="hidden" name="ma" value="<?= (int) $a['MaDC'] ?>"><button class="btn btn-link btn-sm p-0" type="submit">Đặt mặc định</button></form>
                            <?php endif; ?>
                            <form method="post" action="<?= url('tai-khoan.php') ?>" data-confirm="Xóa địa chỉ này? Các đơn cũ vẫn giữ nguyên địa chỉ giao."><?= csrf_field() ?><input type="hidden" name="action" value="xoa-dia-chi"><input type="hidden" name="ma" value="<?= (int) $a['MaDC'] ?>"><button class="btn btn-link btn-sm p-0 text-danger" type="submit">Xóa</button></form>
                        </div>
                    </div></div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?php page_end(); ?>
