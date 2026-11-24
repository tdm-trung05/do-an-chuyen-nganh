<?php
/** Tiêu đề đầu trang nội dung. Dùng: page_title('Giỏ hàng', 'Chỉnh số lượng rồi bấm thanh toán') */
function page_title(string $title, string $sub = ''): void
{
    ?>
    <div class="page-title">
        <h1><?= e($title) ?></h1>
        <?php if ($sub): ?><p class="text-muted mb-0"><?= e($sub) ?></p><?php endif; ?>
    </div>
    <?php
}
