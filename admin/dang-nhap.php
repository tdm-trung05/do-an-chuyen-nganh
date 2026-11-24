<?php
/** Đăng nhập khu quản trị (quản trị viên, nhân viên bán hàng, thủ kho). */
require_once __DIR__ . '/../includes/bootstrap.php';

$adminPrefix = BASE_URL . '/admin/';

if (auth_is_staff(auth_user())) {
    redirect(safe_next(aurl('index.php'), $adminPrefix));
}

$adminTitle = 'Đăng nhập';
$error = '';
$email = '';
if (is_post()) {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!csrf_verify()) {
        $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $r = auth_attempt($email, (string) ($_POST['mat_khau'] ?? ''), 'quantri');
        if ($r['ok']) {
            auth_login($r['user']['MaND'], $r['user']['role'], false);
            redirect(safe_next(aurl('index.php'), $adminPrefix));
        }
        $error = $r['error'];
    }
}
no_cache_headers();
require ROOT_PATH . '/admin/includes/head.php';
?>
<div class="login-admin">
    <div class="panel">
        <a class="brand mb-3" href="<?= aurl('index.php') ?>"><img src="<?= asset('images/logo.svg') ?>" width="40" height="40" alt=""><span class="brand-text"><strong><?= e(SITE_NAME) ?></strong><small>Khu quản trị</small></span></a>
        <h1 class="h5">Đăng nhập nhân viên</h1>
        <?= flash_render() ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-danger small" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <form action="<?= aurl('dang-nhap.php') ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="quay-lai" value="<?= e(safe_next_raw($adminPrefix)) ?>">
            <div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" name="email" class="form-control" type="email" maxlength="100" value="<?= e($email) ?>" autocomplete="username" required autofocus></div>
            <div class="mb-3"><label class="form-label" for="mk">Mật khẩu</label><input id="mk" name="mat_khau" class="form-control" type="password" autocomplete="current-password" required></div>
            <button class="btn btn-ink w-100" type="submit">Đăng nhập</button>
        </form>
        <p class="small text-muted mt-3 mb-0">Khách hàng đăng nhập tại <a href="<?= url('dang-nhap.php') ?>">trang bán hàng</a>.</p>
    </div>
</div>
<?php require ROOT_PATH . '/admin/includes/footer.php'; ?>
