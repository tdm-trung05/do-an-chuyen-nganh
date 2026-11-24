<?php
/** MH01 – Trang chủ: danh mục, dòng sản phẩm, sản phẩm nổi bật, tin mới. */
require_once __DIR__ . '/includes/bootstrap.php';
$homeCategories = shop_categories();
$homeSeries     = shop_series();
$homeFeatured   = shop_featured(8);
page_begin('Trang chủ');
?>
<!-- Hero: tra cứu theo mã hàng (giải quyết vấn đề mua nhầm mã, mục 1.1.1) -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <h1>Đúng mã hàng, đúng màu mặt, đặt là có.</h1>
                <p class="lead">Tra cứu công tắc, ổ cắm, aptomat, tủ điện theo mã hàng, xem thông số rồi đặt hàng ngay, không cần gọi hỏi từng cửa hàng.</p>
                <form class="code-lookup" action="<?= url('san-pham.php') ?>" method="get">
                    <input type="search" name="q" class="form-control form-control-lg" placeholder="Nhập mã hàng, ví dụ 282434-C" aria-label="Mã hàng">
                    <button class="btn btn-accent btn-lg" type="submit">Tra mã hàng</button>
                </form>
                <p class="hero-hint">Thử nhanh: <a href="<?= url('san-pham.php?q=282434-C') ?>">282434-C</a><a href="<?= url('san-pham.php?q=KA4804KM') ?>">KA4804KM</a><a href="<?= url('san-pham.php?q=MCB-DEMO-2P-32A') ?>">MCB-DEMO-2P-32A</a></p>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="faceplate" aria-hidden="true">
                    <div class="module on"></div><div class="module"></div><div class="module socket"></div>
                    <div class="module"></div><div class="module socket"></div><div class="module on"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cam kết dịch vụ -->
<section class="benefits">
    <div class="container">
        <div class="row">
            <div class="col-6 col-lg-3"><div class="benefit"><i class="bi bi-boxes"></i><div><strong>Hiện tồn kho</strong><small>Biết còn hàng trước khi đặt</small></div></div></div>
            <div class="col-6 col-lg-3"><div class="benefit"><i class="bi bi-truck"></i><div><strong>Theo dõi đơn hàng</strong><small>Cập nhật từng trạng thái</small></div></div></div>
            <div class="col-6 col-lg-3"><div class="benefit"><i class="bi bi-patch-check"></i><div><strong>Bảo hành trực tuyến</strong><small>Gửi yêu cầu ngay trên web</small></div></div></div>
            <div class="col-6 col-lg-3"><div class="benefit"><i class="bi bi-file-earmark-text"></i><div><strong>Báo giá công trình</strong><small>Gửi danh sách, nhận giá</small></div></div></div>
        </div>
    </div>
</section>

<!-- Danh mục -->
<section class="section">
    <div class="container">
        <div class="section-head"><div><h2>Danh mục sản phẩm</h2><p>Chọn theo nhóm thiết bị bạn cần.</p></div></div>
        <div class="row g-3">
            <?php foreach ($homeCategories as $c): ?>
            <div class="col-6 col-md-4 col-lg">
                <a class="category-tile" href="<?= url('san-pham.php?danh-muc=' . $c['id']) ?>">
                    <i class="bi <?= e($c['icon']) ?>"></i><strong><?= e($c['name']) ?></strong><small><?= (int) $c['count'] ?> mã hàng</small>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Dòng sản phẩm (theo cách chia của sgm.com.vn) -->
<section class="section pt-0">
    <div class="container">
        <div class="section-head"><div><h2>Mua theo dòng sản phẩm</h2><p>Mỗi dòng có nhiều màu mặt và số module khác nhau.</p></div></div>
        <?php foreach ($homeSeries as $s): ?>
        <a class="series-chip" href="<?= url('san-pham.php?dong[]=' . (int) $s['id']) ?>"><?= e($s['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Sản phẩm nổi bật -->
<section class="section pt-0">
    <div class="container">
        <div class="section-head">
            <div><h2>Sản phẩm nổi bật</h2><p>Các mã hàng được đặt nhiều nhất.</p></div>
            <a href="<?= url('san-pham.php') ?>">Xem tất cả sản phẩm</a>
        </div>
        <div class="row g-3">
            <?php foreach ($homeFeatured as $p): ?>
            <div class="col-6 col-md-4 col-xl-3"><?php product_card($p); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Kêu gọi báo giá -->
<section class="section pt-0">
    <div class="container">
        <div class="cta-quote">
            <div><h3>Cần số lượng lớn cho công trình?</h3><p class="mb-0">Chọn các mã hàng, nhập số lượng và gửi yêu cầu. Nhân viên sẽ gửi lại đơn giá trong tài khoản của bạn.</p></div>
            <a class="btn btn-ink btn-lg" href="<?= url('bao-gia.php') ?>">Gửi yêu cầu báo giá</a>
        </div>
    </div>
</section>

<!-- Tin mới -->
<section class="section pt-0">
    <div class="container">
        <div class="section-head"><div><h2>Tin mới</h2></div><a href="<?= url('tin-tuc.php') ?>">Xem tất cả tin tức</a></div>
        <div class="row g-3">
            <?php foreach (news_latest(3) as $n): ?>
            <div class="col-md-4">
                <article class="news-card">
                    <a href="<?= url('tin-tuc-chi-tiet.php?id=' . $n['id']) ?>"><img src="<?= asset('images/placeholder-news.svg') ?>" alt="" loading="lazy"></a>
                    <div class="body">
                        <span class="small text-muted"><?= e($n['date']) ?></span>
                        <h3><a href="<?= url('tin-tuc-chi-tiet.php?id=' . $n['id']) ?>"><?= e($n['title']) ?></a></h3>
                        <p class="text-muted small mb-0"><?= e($n['summary']) ?></p>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php page_end(); ?>
