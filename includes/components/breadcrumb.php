<?php /** Đường dẫn breadcrumb. Biến: $crumbs = ['Tên' => 'đường-dẫn' | null] */ ?>
<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Trang chủ</a></li>
                <?php foreach ($crumbs as $label => $link): ?>
                    <?php if ($link): ?>
                    <li class="breadcrumb-item"><a href="<?= url($link) ?>"><?= e($label) ?></a></li>
                    <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
</div>
