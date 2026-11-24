<?php
/**
 * Đầu trang khách hàng: thanh tiện ích, logo, ô tìm theo tên/mã hàng,
 * yêu thích – báo giá – giỏ hàng – tài khoản, menu danh mục nhiều tầng (theo sgm.com.vn).
 */
$cartCount     = cart_count();            // tổng số lượng trong giỏ của khách đang đăng nhập
$wishCount     = count(wish_ids());
$navCategories = shop_categories();       // danh mục gốc kèm danh mục con
$navSeries     = shop_series();           // dòng sản phẩm
?>
<header class="site-header">
    <div class="utility-bar d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex gap-4">
                <span><i class="bi bi-telephone"></i> Hotline: <strong><?= e(SITE_HOTLINE) ?></strong></span>
                <?php if (SITE_ZALO !== ''): ?><span><i class="bi bi-chat-dots"></i> Zalo: <?= e(SITE_ZALO) ?></span><?php endif; ?>
                <span><i class="bi bi-envelope"></i> <?= e(SITE_EMAIL) ?></span>
            </div>
            <div class="d-flex gap-4">
                <a href="<?= url('don-hang.php') ?>"><i class="bi bi-truck"></i> Tra cứu đơn hàng</a>
                <a href="<?= url('bao-hanh.php') ?>"><i class="bi bi-patch-check"></i> Bảo hành – đổi trả</a>
                <a href="<?= url('chinh-sach.php') ?>">Chính sách</a>
            </div>
        </div>
    </div>

    <div class="main-bar">
        <div class="container d-flex align-items-center gap-3">
            <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Mở menu">
                <i class="bi bi-list fs-4"></i>
            </button>

            <a class="brand" href="<?= url('index.php') ?>" aria-label="<?= e(SITE_NAME) ?> – trang chủ">
                <img src="<?= asset('images/logo.svg') ?>" width="40" height="40" alt="">
                <span class="brand-text"><strong><?= e(SITE_NAME) ?></strong><small><?= e(SITE_TAGLINE) ?></small></span>
            </a>

            <form class="search-form flex-grow-1 d-none d-md-flex" action="<?= url('san-pham.php') ?>" method="get" role="search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="Tìm theo tên hoặc mã hàng, ví dụ 282434-C" aria-label="Tìm sản phẩm">
                <button class="btn btn-accent" type="submit">Tìm</button>
            </form>

            <nav class="header-actions ms-auto" aria-label="Tiện ích">
                <a class="action" href="<?= url('yeu-thich.php') ?>" title="Yêu thích">
                    <i class="bi bi-heart"></i><span class="count wish-count"<?= $wishCount > 0 ? '' : ' hidden' ?>><?= (int) $wishCount ?></span><span class="label d-none d-xl-block">Yêu thích</span>
                </a>
                <a class="action" href="<?= url('bao-gia.php') ?>" title="Yêu cầu báo giá">
                    <i class="bi bi-file-earmark-text"></i><span class="label d-none d-xl-block">Báo giá</span>
                </a>
                <a class="action" href="<?= url('gio-hang.php') ?>" title="Giỏ hàng">
                    <i class="bi bi-cart3"></i><span class="count cart-count"<?= $cartCount > 0 ? '' : ' hidden' ?>><?= (int) $cartCount ?></span><span class="label d-none d-xl-block">Giỏ hàng</span>
                </a>
                <?php $hdrUser = auth_user(); ?>
                <div class="dropdown">
                    <a class="action" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i><span class="label d-none d-xl-block"><?= $hdrUser ? e(mb_strimwidth($hdrUser['HoTen'], 0, 14, '…', 'UTF-8')) : 'Tài khoản' ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($hdrUser === null): ?>
                        <li><a class="dropdown-item" href="<?= url('dang-nhap.php') ?>">Đăng nhập</a></li>
                        <li><a class="dropdown-item" href="<?= url('dang-ky.php') ?>">Đăng ký</a></li>
                        <?php else: ?>
                        <li><span class="dropdown-item-text lh-sm"><strong><?= e($hdrUser['HoTen']) ?></strong><br><small class="text-muted"><?= e($hdrUser['Email']) ?></small></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($hdrUser['role'] === 'KH'): ?>
                        <li><a class="dropdown-item" href="<?= url('tai-khoan.php') ?>">Thông tin cá nhân</a></li>
                        <li><a class="dropdown-item" href="<?= url('don-hang.php') ?>">Đơn hàng của tôi</a></li>
                        <li><a class="dropdown-item" href="<?= url('bao-gia.php#da-nhan') ?>">Báo giá đã nhận</a></li>
                        <li><a class="dropdown-item" href="<?= url('bao-hanh.php') ?>">Bảo hành, đổi trả</a></li>
                        <?php else: ?>
                        <li><a class="dropdown-item" href="<?= aurl('index.php') ?>">Vào khu quản trị</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= url('dang-xuat.php') ?>">
                                <?= csrf_field() ?>
                                <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
                            </form>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </div>

    <!-- Menu danh mục nhiều tầng -->
    <nav class="category-nav d-none d-lg-block" aria-label="Danh mục sản phẩm">
        <div class="container">
            <ul class="nav">
                <?php foreach ($navCategories as $i => $c):
                    $seriesGroups = [];
                    if ($i === 0) {   // danh mục đầu tiên có menu nhiều tầng: dòng sản phẩm và danh mục con
                        if ($navSeries) { $seriesGroups['Dòng sản phẩm'] = $navSeries; }
                    }
                    $isMega = $i === 0 && ($seriesGroups || $c['children']);
                ?>
                <?php if ($isMega): ?>
                <li class="nav-item dropdown mega">
                    <a class="nav-link dropdown-toggle<?= is_active($navKey ?? '', 'cong-tac') ?>" href="<?= url('san-pham.php?danh-muc=' . $c['id']) ?>" data-bs-toggle="dropdown" data-bs-auto-close="outside"><?= e($c['name']) ?></a>
                    <div class="dropdown-menu mega-menu">
                        <div class="row g-4">
                            <?php foreach ($seriesGroups as $groupName => $list): ?>
                            <div class="col-md-4">
                                <h6><?= e($groupName) ?></h6>
                                <ul class="list-unstyled">
                                    <?php foreach ($list as $s): ?>
                                    <li><a href="<?= url('san-pham.php?dong[]=' . (int) $s['id']) ?>"><?= e($s['name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                            <?php if ($c['children']): ?>
                            <div class="col-md-4">
                                <h6>Phân loại</h6>
                                <ul class="list-unstyled">
                                    <?php foreach ($c['children'] as $ch): ?>
                                    <li><a href="<?= url('san-pham.php?danh-muc=' . $ch['id']) ?>"><?= e($ch['name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('san-pham.php?danh-muc=' . $c['id']) ?>"><?= e($c['name']) ?></a></li>
                <?php endif; ?>
                <?php endforeach; ?>
                <li class="nav-item"><a class="nav-link<?= is_active($navKey ?? '', 'bao-gia') ?>" href="<?= url('bao-gia.php') ?>">Yêu cầu báo giá</a></li>
                <li class="nav-item"><a class="nav-link<?= is_active($navKey ?? '', 'tin-tuc') ?>" href="<?= url('tin-tuc.php') ?>">Tin tức</a></li>
                <li class="nav-item"><a class="nav-link<?= is_active($navKey ?? '', 'gioi-thieu') ?>" href="<?= url('gioi-thieu.php') ?>">Giới thiệu</a></li>
                <li class="nav-item"><a class="nav-link<?= is_active($navKey ?? '', 'lien-he') ?>" href="<?= url('lien-he.php') ?>">Liên hệ</a></li>
            </ul>
        </div>
    </nav>

    <!-- Menu trên điện thoại -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileNavLabel">Danh mục</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
        </div>
        <div class="offcanvas-body">
            <form class="search-form mb-3" action="<?= url('san-pham.php') ?>" method="get" role="search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="Tên hoặc mã hàng" aria-label="Tìm sản phẩm">
            </form>
            <ul class="list-unstyled mobile-menu">
                <?php foreach ($navCategories as $c): ?>
                <li><a href="<?= url('san-pham.php?danh-muc=' . $c['id']) ?>"><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= url('bao-gia.php') ?>"><i class="bi bi-file-earmark-text"></i> Yêu cầu báo giá</a></li>
                <li><a href="<?= url('tin-tuc.php') ?>"><i class="bi bi-newspaper"></i> Tin tức</a></li>
                <li><a href="<?= url('lien-he.php') ?>"><i class="bi bi-geo-alt"></i> Liên hệ</a></li>
            </ul>
        </div>
    </div>
</header>
