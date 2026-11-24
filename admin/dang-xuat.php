<?php
/** Đăng xuất khu quản trị. Chỉ nhận POST kèm token CSRF. */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post() || !csrf_verify()) {
    redirect(aurl('index.php'));
}
auth_logout();
flash_set('success', 'Bạn đã đăng xuất.');
redirect(aurl('dang-nhap.php'));
