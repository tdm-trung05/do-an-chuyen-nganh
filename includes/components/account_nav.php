<?php
/** Menu bên trái khu tài khoản của khách hàng. Dùng: account_nav('don-hang') */
function account_nav(string $active = ''): void
{
    $items = [
        'tai-khoan' => ['Thông tin cá nhân',  'bi-person',               'tai-khoan.php'],
        'dia-chi'   => ['Sổ địa chỉ',          'bi-geo-alt',              'tai-khoan.php#dia-chi'],
        'don-hang'  => ['Đơn hàng của tôi',    'bi-box-seam',             'don-hang.php'],
        'bao-gia'   => ['Báo giá đã nhận',     'bi-file-earmark-text',    'bao-gia.php#da-nhan'],
        'bao-hanh'  => ['Bảo hành, đổi trả',   'bi-patch-check',          'bao-hanh.php'],
        'yeu-thich' => ['Sản phẩm yêu thích',  'bi-heart',                'yeu-thich.php'],
    ];
    $me = auth_user();
    ?>
    <aside class="account-nav" aria-label="Menu tài khoản">
        <div class="account-head">
            <span class="avatar"><i class="bi bi-person-fill"></i></span>
            <div><strong><?= e($me['HoTen'] ?? '') ?></strong><br><small class="text-muted"><?= e($me['Email'] ?? '') ?></small></div>
        </div>
        <ul class="list-unstyled mb-0">
            <?php foreach ($items as $key => [$label, $icon, $href]): ?>
            <li><a class="<?= $active === $key ? 'active' : '' ?>" href="<?= url($href) ?>"><i class="bi <?= e($icon) ?>"></i> <?= e($label) ?></a></li>
            <?php endforeach; ?>
            <li>
                <form method="post" action="<?= url('dang-xuat.php') ?>">
                    <?= csrf_field() ?>
                    <button class="logout-btn" type="submit"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
                </form>
            </li>
        </ul>
    </aside>
    <?php
}
