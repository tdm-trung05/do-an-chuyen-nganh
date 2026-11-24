<?php
/** MH07 – Quên mật khẩu: theo báo cáo, khách liên hệ cửa hàng để quản trị viên đặt lại (admin/tai-khoan). */
require_once __DIR__ . '/includes/bootstrap.php';
page_begin('Quên mật khẩu', ['Đăng nhập' => 'dang-nhap.php', 'Quên mật khẩu' => null]);
?>
<div class="container">
    <div class="auth-wrap">
        <div class="panel">
            <h1 class="h4 mb-1">Quên mật khẩu</h1>
            <p class="text-muted">Website chưa gửi email đặt lại mật khẩu. Hãy liên hệ cửa hàng để quản trị viên đặt lại mật khẩu cho bạn.</p>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="bi bi-telephone"></i> Hotline: <strong><?= e(SITE_HOTLINE) ?></strong></li>
                <li class="mb-2"><i class="bi bi-envelope"></i> <?= e(SITE_EMAIL) ?></li>
            </ul>
            <a class="btn btn-ink w-100" href="<?= url('lien-he.php') ?>">Gửi tin nhắn liên hệ</a>
            <a class="btn btn-link w-100 mt-1" href="<?= url('dang-nhap.php') ?>">Quay lại đăng nhập</a>
        </div>
    </div>
</div>
<?php page_end(); ?>
