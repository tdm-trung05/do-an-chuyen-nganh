<?php
/**
 * Menu bên trái khu quản trị. Danh sách mục và vai trò nằm ở admin_menu() (admin/includes/functions.php);
 * ở đây chỉ lọc theo vai trò của người đang đăng nhập.
 */
$myRole = $adminUser['role'] ?? '';
$menu = [];
foreach (admin_menu() as $group => $items) {
    foreach ($items as $key => $item) {
        if (in_array($myRole, explode(' ', $item[3]), true)) {
            $menu[$group][$key] = $item;      // chỉ hiện mục mà vai trò hiện tại được vào
        }
    }
}
?>
<aside class="admin-sidebar" id="adminSidebar">
    <a class="brand brand-light px-3 py-3" href="<?= aurl('index.php') ?>">
        <img src="<?= asset('images/logo.svg') ?>" width="36" height="36" alt="">
        <span class="brand-text"><strong><?= e(SITE_NAME) ?></strong><small>Khu quản trị</small></span>
    </a>
    <nav aria-label="Menu quản trị">
        <?php foreach ($menu as $group => $items): ?>
        <div class="menu-group"><?= e($group) ?></div>
        <ul class="list-unstyled mb-0">
            <?php foreach ($items as $key => [$label, $icon, $href, $roles]): ?>
            <li>
                <a class="<?= ($adminActive ?? '') === $key ? 'active' : '' ?>" href="<?= aurl($href) ?>">
                    <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
                    <?php if ($roles !== 'QT BH TK'): ?><small class="roles"><?= e(str_replace(' ', '·', $roles)) ?></small><?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot"><a href="<?= url('index.php') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Xem website</a></div>
</aside>
