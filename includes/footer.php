<?php /** Chân trang khách hàng và nạp script. */ ?>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="brand brand-light mb-3" href="<?= url('index.php') ?>">
                    <img src="<?= asset('images/logo.svg') ?>" width="40" height="40" alt="">
                    <span class="brand-text"><strong><?= e(SITE_NAME) ?></strong><small><?= e(SITE_TAGLINE) ?></small></span>
                </a>
                <p class="mb-2">Tra cứu theo mã hàng, xem thông số, đặt hàng và theo dõi bảo hành trong một nơi.</p>
                <p class="mb-1"><i class="bi bi-geo-alt"></i> <?= e(SITE_ADDRESS) ?></p>
                <p class="mb-1"><i class="bi bi-telephone"></i> <?= e(SITE_HOTLINE) ?></p>
                <p class="mb-0"><i class="bi bi-envelope"></i> <?= e(SITE_EMAIL) ?></p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Sản phẩm</h6>
                <ul class="list-unstyled">
                    <?php foreach (array_slice(shop_categories(), 0, 5) as $c): ?>
                    <li><a href="<?= url('san-pham.php?danh-muc=' . $c['id']) ?>"><?= e($c['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6>Hỗ trợ khách hàng</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= url('chinh-sach.php#doi-tra') ?>">Chính sách đổi trả</a></li>
                    <li><a href="<?= url('chinh-sach.php#bao-hanh') ?>">Chính sách bảo hành</a></li>
                    <li><a href="<?= url('chinh-sach.php#giao-nhan') ?>">Giao nhận</a></li>
                    <li><a href="<?= url('chinh-sach.php#thanh-toan') ?>">Hình thức thanh toán</a></li>
                    <li><a href="<?= url('chinh-sach.php#bao-mat') ?>">Bảo mật thông tin</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6>Nhận tin và báo giá</h6>
                <form class="newsletter" action="<?= url('dang-ky-nhan-tin.php') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="quay-lai" value="<?= e(request_uri()) ?>">
                    <label class="visually-hidden" for="ft-email">Email</label>
                    <input id="ft-email" name="email" type="email" class="form-control" placeholder="Email của bạn" maxlength="150" required>
                    <button type="submit" class="btn btn-accent">Đăng ký</button>
                </form>
                <p class="small mt-3 mb-0 text-footer-muted">Hệ thống chỉ chạy trên trình duyệt, xem được trên máy tính và điện thoại.</p>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>© 2026 <?= e(SITE_NAME) ?>. Đồ án tốt nghiệp – Trường Đại học Công nghệ Sài Gòn.</span>
            <span>Dữ liệu sản phẩm chỉ dùng cho học tập.</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
