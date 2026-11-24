<?php
/** MH07 – Đăng nhập khách hàng (UC03). */
require_once __DIR__ . '/includes/bootstrap.php';

// Đã đăng nhập thì không cần vào lại trang này.
if (auth_user() !== null) {
    redirect(safe_next(url('index.php')));
}

$error = '';
$email = '';
if (is_post()) {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!csrf_verify()) {
        $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $r = auth_attempt($email, (string) ($_POST['mat_khau'] ?? ''), 'khach');   // tài khoản nhân viên chỉ đăng nhập ở admin/dang-nhap.php
        if ($r['ok']) {
            auth_login($r['user']['MaND'], $r['user']['role'], !empty($_POST['nho']));
            flash_set('success', 'Xin chào, ' . $r['user']['HoTen'] . '!');
            redirect(safe_next(url('index.php')));
        }
        $error = $r['error'];
    }
}

page_begin('Đăng nhập', ['Đăng nhập' => null]);
?>
<div class="container">
    <div class="auth-wrap">
        <div class="panel">
            <h1 class="h4 mb-1">Đăng nhập</h1>
            <p class="text-muted">Đăng nhập để đặt hàng, theo dõi đơn và yêu cầu báo giá.</p>
            <?php if ($error !== ''): ?>
            <div class="alert alert-danger small" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <form action="<?= url('dang-nhap.php') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="quay-lai" value="<?= e(safe_next_raw()) ?>">
                <div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" name="email" class="form-control" type="email" maxlength="100" value="<?= e($email) ?>" autocomplete="username" required autofocus></div>
                <div class="mb-3"><label class="form-label" for="mk">Mật khẩu</label><input id="mk" name="mat_khau" class="form-control" type="password" autocomplete="current-password" required></div>
                <div class="form-text mb-3">Tài khoản bị khóa tạm <?= (int) LOGIN_LOCK_MINUTES ?> phút sau <?= (int) LOGIN_MAX_ATTEMPTS ?> lần nhập sai liên tiếp.</div>
                <div class="d-flex justify-content-between mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="nho" name="nho" value="1"><label class="form-check-label" for="nho">Ghi nhớ đăng nhập</label></div><a href="<?= url('quen-mat-khau.php') ?>">Quên mật khẩu?</a></div>
                <button class="btn btn-ink w-100" type="submit">Đăng nhập</button>
            </form>
            <p class="text-center mt-3 mb-0">Chưa có tài khoản? <a href="<?= url('dang-ky.php' . (safe_next_raw() !== '' ? '?quay-lai=' . rawurlencode(safe_next_raw()) : '')) ?>">Đăng ký</a></p>
        </div>
    </div>
</div>
<?php page_end(); ?>
