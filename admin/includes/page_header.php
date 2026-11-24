<?php /** Tiêu đề trang quản trị. Biến: $adminTitle, $adminSub, $adminAction */ ?>
<div class="admin-page-head">
    <div>
        <h1><?= e($adminTitle) ?></h1>
        <?php if (!empty($adminSub)): ?><p class="text-muted mb-0"><?= e($adminSub) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($adminAction)): ?><div><?= $adminAction ?></div><?php endif; ?>
</div>
