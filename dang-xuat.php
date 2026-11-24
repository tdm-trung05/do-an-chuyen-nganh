<?php
/** Đăng xuất khách hàng. Chỉ nhận POST kèm token CSRF (form "Đăng xuất" ở menu tài khoản). */
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_post() || !csrf_verify()) {
    redirect(url('index.php'));
}
auth_logout();
flash_set('success', 'Bạn đã đăng xuất.');
redirect(url('index.php'));
