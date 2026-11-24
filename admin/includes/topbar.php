<?php /** Thanh trên cùng khu quản trị: nút mở menu, ô tìm nhanh, thông báo, tài khoản. */ ?>
<header class="admin-topbar">
    <button class="btn btn-icon d-lg-none" type="button" id="sidebarToggle" aria-label="Mở menu"><i class="bi bi-list"></i></button>
    <?php $tbRole = $adminUser['role'] ?? ''; $tbOrders = in_array($tbRole, ['QT', 'BH'], true); $tbStock = in_array($tbRole, ['QT', 'TK'], true); ?>
    <?php if ($tbOrders): ?>
    <form class="input-group topbar-search d-none d-md-flex" method="get" action="<?= e(aurl('don-hang/index.php')) ?>" role="search">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" name="q" class="form-control" placeholder="Tìm đơn hàng theo mã, tên khách, số điện thoại" aria-label="Tìm nhanh đơn hàng">
    </form>
    <?php endif; ?>
    <div class="ms-auto d-flex align-items-center gap-2">
        <?php if ($tbStock): $alerts = admin_stock_alert_count(); ?>
        <a class="btn btn-icon position-relative" href="<?= aurl('kho/ton-kho.php?tt=' . rawurlencode('Cần chú ý')) ?>" title="Cảnh báo tồn kho"><i class="bi bi-bell"></i><?php if ($alerts > 0): ?><span class="count-dot"><?= (int) $alerts ?></span><?php endif; ?></a>
        <?php endif; ?>
        <div class="dropdown">
            <button class="btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar-sm"><i class="bi bi-person-fill"></i></span>
                <span class="d-none d-md-block text-start lh-sm"><strong class="small"><?= e($adminUser['HoTen'] ?? '') ?></strong><br><small class="text-muted"><?= e($adminUser['TenVT'] ?? '') ?> · <?= e($adminUser['Email'] ?? '') ?></small></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if (($adminUser['role'] ?? '') === 'QT'): ?>
                <li><a class="dropdown-item" href="<?= aurl('tai-khoan/index.php') ?>">Tài khoản, phân quyền</a></li>
                <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li>
                    <form method="post" action="<?= aurl('dang-xuat.php') ?>">
                        <?= csrf_field() ?>
                        <button class="dropdown-item" type="submit">Đăng xuất</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
