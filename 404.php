<?php
/** Trang lỗi 404. Apache chuyển các URL không tồn tại về đây qua .htaccess (mod_rewrite) */
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
page_begin('Không tìm thấy trang');
?>
<div class="container py-5 text-center">
    <h1 class="display-4">404</h1>
    <p class="lead">Không tìm thấy trang bạn cần. Hãy thử tra theo mã hàng hoặc quay về trang chủ.</p>
    <a class="btn btn-ink" href="<?= url('index.php') ?>">Về trang chủ</a>
</div>
<?php page_end(); ?>
