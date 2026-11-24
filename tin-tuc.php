<?php
/** MH18 – Danh sách tin tức (bảng TINTUC, chỉ bài đang đăng). */
require_once __DIR__ . '/includes/bootstrap.php';
$list = news_list(max(1, (int) ($_GET['trang'] ?? 1)));
page_begin('Tin tức', ['Tin tức' => null], 'tin-tuc');
?>
<div class="container">
    <?php page_title('Tin tức', 'Hướng dẫn chọn mua, so sánh dòng sản phẩm và chính sách.'); ?>
    <?php if (!$list['rows']): ?>
    <p class="text-muted py-4">Chưa có bài viết nào.</p>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($list['rows'] as $n): ?>
        <div class="col-md-6 col-lg-4"><article class="news-card">
            <a href="<?= url('tin-tuc-chi-tiet.php?id=' . $n['id']) ?>"><img src="<?= asset('images/placeholder-news.svg') ?>" alt="" loading="lazy"></a>
            <div class="body"><h3><a href="<?= url('tin-tuc-chi-tiet.php?id=' . $n['id']) ?>"><?= e($n['title']) ?></a></h3><p class="text-muted small"><?= e($n['summary']) ?></p><span class="small text-muted"><?= e($n['date']) ?></span></div>
        </article></div>
        <?php endforeach; ?>
    </div>
    <div class="mt-4"><?php pagination($list['page'], $list['pages']); ?></div>
    <?php endif; ?>
</div>
<?php page_end(); ?>
