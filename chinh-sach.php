<?php
/** Chính sách: đổi trả, bảo hành, giao nhận, thanh toán, bảo mật (theo sgm.com.vn và legrandfr.com). */
require_once __DIR__ . '/includes/bootstrap.php';
page_begin('Chính sách', ['Chính sách' => null]);
?>
<div class="container">
    <?php page_title('Chính sách'); ?>
    <div class="row g-4">
        <div class="col-lg-3"><nav class="policy-nav panel p-2" aria-label="Mục chính sách">
            <a href="#doi-tra">Đổi trả</a><a href="#bao-hanh">Bảo hành</a><a href="#giao-nhan">Giao nhận</a><a href="#thanh-toan">Thanh toán</a><a href="#bao-mat">Bảo mật thông tin</a>
        </nav></div>
        <div class="col-lg-9 article-body">
            <section class="panel" id="doi-tra"><h2 class="h4">Chính sách đổi trả</h2><p>Hàng lỗi khi nhận được đổi trả trong <?= e(return_days_text()) ?> kể từ ngày đơn hoàn tất. Gửi yêu cầu trực tuyến tại mục <a href="<?= url('bao-hanh.php') ?>">Bảo hành, đổi trả</a>.</p></section>
            <section class="panel" id="bao-hanh"><h2 class="h4">Chính sách bảo hành</h2><p>Thời hạn bảo hành khác nhau theo từng sản phẩm, tính từ ngày đơn hoàn tất. Nhân viên tiếp nhận, kiểm tra và phản hồi: bảo hành, đổi sản phẩm hoặc từ chối kèm lý do.</p></section>
            <section class="panel" id="giao-nhan"><h2 class="h4">Giao nhận</h2><p>Phí giao hàng mặc định áp dụng cho mỗi đơn và hiển thị rõ khi thanh toán; nhân viên có thể điều chỉnh khi xác nhận đơn nếu cần. Khi giao cho đơn vị vận chuyển, đơn chuyển sang “Đang giao”.</p></section>
            <section class="panel" id="thanh-toan"><h2 class="h4">Hình thức thanh toán</h2><p>Hai cách: thanh toán khi nhận hàng và chuyển khoản. Đơn chuyển khoản ở trạng thái “Chờ thanh toán” đến khi nhân viên đối chiếu tiền và xác nhận.</p></section>
            <section class="panel" id="bao-mat"><h2 class="h4">Bảo mật thông tin</h2><p>Mật khẩu được lưu dạng băm. Thông tin cá nhân chỉ dùng để xử lý đơn hàng, báo giá và bảo hành.</p></section>
        </div>
    </div>
</div>
<?php page_end(); ?>
