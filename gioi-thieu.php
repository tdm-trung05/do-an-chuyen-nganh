<?php
/** Giới thiệu hệ thống và cửa hàng. */
require_once __DIR__ . '/includes/bootstrap.php';
page_begin('Giới thiệu', ['Giới thiệu' => null], 'gioi-thieu');
?>
<div class="container">
    <?php page_title('Giới thiệu'); ?>
    <div class="row g-4">
        <div class="col-lg-8"><section class="panel article-body">
            <p><?= e(SITE_NAME) ?> là website bán thiết bị điện gia dụng gồm công tắc, ổ cắm, aptomat, tủ điện và hộp âm sàn, lắp trong nhà ở và công trình dân dụng.</p>
            <h2>Vì sao có website này</h2>
            <p>Cùng một kiểu sản phẩm có nhiều mã theo màu mặt, số module hay dòng sản phẩm nên rất dễ mua nhầm. Website cho tra cứu theo mã, xem thông số rồi đặt hàng luôn.</p>
            <h2>Bạn làm được gì</h2>
            <p>Tìm và lọc sản phẩm, đặt hàng và theo dõi đơn, gửi yêu cầu báo giá, gửi yêu cầu bảo hành hoặc đổi trả trực tuyến.</p>
        </section></div>
        <div class="col-lg-4"><section class="panel"><h2 class="panel-title">Đề tài đồ án</h2><p class="small mb-1">Xây dựng hệ thống quản lý và kinh doanh thiết bị điện gia dụng trực tuyến.</p><p class="small text-muted mb-0">Người hướng dẫn: ThS. Trịnh Thanh Duy<br>Sinh viên: Trương Đặng Minh Trung, Lê Đinh Văn Ron</p></section></div>
    </div>
</div>
<?php page_end(); ?>
