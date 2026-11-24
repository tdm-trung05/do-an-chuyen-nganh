<?php
/** MH18 – Nội dung bài viết (bảng TINTUC). */
require_once __DIR__ . '/includes/bootstrap.php';
$n = news_one((int) ($_GET['id'] ?? 0));
if ($n === null) {
    require __DIR__ . '/404.php';
    exit;
}
page_begin($n['title'], ['Tin tức' => 'tin-tuc.php', $n['title'] => null], 'tin-tuc');
?>
<div class="container py-4">
    <div class="row g-5">
        <article class="col-lg-8">
            <h1 class="h2 mt-2"><?= e($n['title']) ?></h1>
            <p class="text-muted"><?= e($n['date']) ?> · Đăng bởi <?= e($n['author']) ?></p>
            <img class="w-100 rounded mb-4" src="<?= asset('images/placeholder-news.svg') ?>" alt="">
            <div class="article-body"><?= news_render_body($n['body']) ?></div>
        </article>
        <aside class="col-lg-4">
            <div class="panel"><h2 class="panel-title">Bài viết khác</h2>
                <?php $others = news_others($n['id']); ?>
                <?php if (!$others): ?><p class="text-muted small mb-0">Chưa có bài khác.</p><?php else: ?>
                <ul class="list-unstyled mb-0"><?php foreach ($others as $o): ?><li class="py-2 border-bottom"><a href="<?= url('tin-tuc-chi-tiet.php?id=' . $o['id']) ?>"><?= e($o['title']) ?></a><div class="small text-muted"><?= e($o['date']) ?></div></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
<?php page_end(); ?>
