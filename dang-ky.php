<?php
/** MH07 – Đăng ký tài khoản khách hàng (UC03). */
require_once __DIR__ . '/includes/bootstrap.php';

if (auth_user() !== null) {
    redirect(url('index.php'));
}

$f      = ['ho_ten' => '', 'email' => '', 'sdt' => ''];
$errors = [];

if (is_post()) {
    $f['ho_ten'] = trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST['ho_ten'] ?? '')));
    $f['email']  = strtolower(trim((string) ($_POST['email'] ?? '')));
    $sdtRaw      = trim((string) ($_POST['sdt'] ?? ''));
    $sdt         = normalize_phone($sdtRaw);   // +84901234567 -> 0901234567
    $f['sdt']    = $sdt !== '' ? $sdt : $sdtRaw;
    $mk1         = (string) ($_POST['mat_khau'] ?? '');
    $mk2         = (string) ($_POST['mat_khau2'] ?? '');

    if (!csrf_verify()) {
        $errors['_'] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        if ($f['ho_ten'] === '' || mb_strlen($f['ho_ten'], 'UTF-8') > 100) {
            $errors['ho_ten'] = 'Vui lòng nhập họ và tên (tối đa 100 ký tự).';
        }
        if (strlen($f['email']) > 100 || !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        if (!preg_match('/^0\d{9,10}$/', $f['sdt'])) {
            $errors['sdt'] = 'Số điện thoại không hợp lệ (10 hoặc 11 chữ số, bắt đầu bằng 0).';
        }
        if (strlen($mk1) < 8) {
            $errors['mat_khau'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
        } elseif (strlen($mk1) > 72) {
            $errors['mat_khau'] = 'Mật khẩu tối đa 72 ký tự.';
        }
        if (!isset($errors['mat_khau']) && $mk1 !== $mk2) {
            $errors['mat_khau2'] = 'Mật khẩu nhập lại không khớp.';
        }
        if (!isset($errors['email']) && db_val('SELECT 1 FROM NGUOIDUNG WHERE Email = ?', [$f['email']])) {
            $errors['email'] = 'Email này đã được đăng ký. Hãy đăng nhập hoặc dùng email khác.';
        }

        if (!$errors) {
            $maVT = db_val('SELECT MaVT FROM VAITRO WHERE TenVT = ?', ['Khách hàng']);
            if ($maVT === null) {
                $errors['_'] = 'Hệ thống chưa có vai trò "Khách hàng". Hãy import lại database/schema.sql.';
            } else {
                try {
                    db_exec(
                        'INSERT INTO NGUOIDUNG (HoTen, Email, SoDienThoai, MatKhauBam, MaVT) VALUES (?, ?, ?, ?, ?)',
                        [$f['ho_ten'], $f['email'], $f['sdt'], password_hash($mk1, PASSWORD_DEFAULT), (int) $maVT]
                    );
                    $newId = (int) db()->lastInsertId();
                    auth_login($newId, 'KH', false);
                    flash_set('success', 'Đăng ký thành công. Chào mừng ' . $f['ho_ten'] . '!');
                    redirect(safe_next(url('index.php')));
                } catch (PDOException $ex) {
                    if ($ex->getCode() === '23000') {      // trùng email do hai người đăng ký cùng lúc
                        $errors['email'] = 'Email này đã được đăng ký. Hãy đăng nhập hoặc dùng email khác.';
                    } else {
                        throw $ex;
                    }
                }
            }
        }
    }
}

/** Lớp CSS và thông báo lỗi cho từng ô nhập. */
function field_class(array $errors, string $key): string
{
    return 'form-control' . (isset($errors[$key]) ? ' is-invalid' : '');
}
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="invalid-feedback">' . e($errors[$key]) . '</div>' : '';
}

page_begin('Đăng ký', ['Đăng ký' => null]);
?>
<div class="container">
    <div class="auth-wrap">
        <div class="panel">
            <h1 class="h4 mb-1">Tạo tài khoản</h1>
            <p class="text-muted">Chỉ mất một phút. Bạn vẫn xem sản phẩm được khi chưa có tài khoản.</p>
            <?php if (isset($errors['_'])): ?>
            <div class="alert alert-danger small" role="alert"><?= e($errors['_']) ?></div>
            <?php endif; ?>
            <form action="<?= url('dang-ky.php') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="quay-lai" value="<?= e(safe_next_raw()) ?>">
                <div class="mb-3"><label class="form-label" for="ht">Họ và tên</label><input id="ht" name="ho_ten" class="<?= field_class($errors, 'ho_ten') ?>" type="text" maxlength="100" value="<?= e($f['ho_ten']) ?>" autocomplete="name" required><?= field_error($errors, 'ho_ten') ?></div>
                <div class="mb-3"><label class="form-label" for="em">Email</label><input id="em" name="email" class="<?= field_class($errors, 'email') ?>" type="email" maxlength="100" value="<?= e($f['email']) ?>" autocomplete="email" required><?= field_error($errors, 'email') ?></div>
                <div class="mb-3"><label class="form-label" for="dt">Số điện thoại</label><input id="dt" name="sdt" class="<?= field_class($errors, 'sdt') ?>" type="tel" maxlength="15" value="<?= e($f['sdt']) ?>" autocomplete="tel" required><?= field_error($errors, 'sdt') ?></div>
                <div class="mb-3"><label class="form-label" for="mk1">Mật khẩu</label><input id="mk1" name="mat_khau" class="<?= field_class($errors, 'mat_khau') ?>" type="password" autocomplete="new-password" required><?= field_error($errors, 'mat_khau') ?><div class="form-text">Ít nhất 8 ký tự.</div></div>
                <div class="mb-3"><label class="form-label" for="mk2">Nhập lại mật khẩu</label><input id="mk2" name="mat_khau2" class="<?= field_class($errors, 'mat_khau2') ?>" type="password" autocomplete="new-password" required><?= field_error($errors, 'mat_khau2') ?></div>
                <button class="btn btn-ink w-100" type="submit">Đăng ký</button>
            </form>
            <p class="text-center mt-3 mb-0">Đã có tài khoản? <a href="<?= url('dang-nhap.php' . (safe_next_raw() !== '' ? '?quay-lai=' . rawurlencode(safe_next_raw()) : '')) ?>">Đăng nhập</a></p>
        </div>
    </div>
</div>
<?php page_end(); ?>
