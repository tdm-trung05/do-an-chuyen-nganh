<?php
/** MH04 – Danh sách yêu thích: xem, bỏ yêu thích, thêm vào giỏ. */
require_once __DIR__ . '/includes/bootstrap.php';
require_customer();   // chỉ khách hàng đã đăng nhập
$products = shop_products_by_ids(wish_ids());
page_begin('Sản phẩm yêu thích', ['Yêu thích' => null]);
?>
<div class="container">
    <?php page_title('Sản phẩm yêu thích', 'Lưu lại để xem sau, vẫn còn nguyên khi đăng nhập lại.'); ?>
    <div class="row g-3">
        <?php foreach ($products as $p): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php product_card($p, true); ?></div>
        <?php endforeach; ?>
    </div>
    <!-- Trạng thái rỗng: hiện khi chưa có (hoặc đã bỏ hết) sản phẩm yêu thích -->
    <div class="panel text-center mt-3<?= $products ? ' d-none' : '' ?>" id="wishEmpty">
        <p class="mb-3">Bạn chưa lưu sản phẩm nào. Bấm biểu tượng trái tim trên sản phẩm để lưu lại.</p>
        <a class="btn btn-ink" href="<?= url('san-pham.php') ?>">Xem sản phẩm</a>
    </div>
</div>
<?php page_end(); ?>
