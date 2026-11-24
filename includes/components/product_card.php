<?php
/**
 * Thẻ sản phẩm dùng ở trang chủ, danh sách, yêu thích...
 * Sản phẩm chưa niêm yết giá (price = null) hiện nút "Yêu cầu báo giá" thay vì "Thêm vào giỏ" (mục 1.2).
 * $p lấy từ shop_search() / shop_products_by_ids() (xem includes/shop.php).
 * Nút giỏ hàng và yêu thích gọi AJAX qua assets/js/main.js (data-add-to-cart, data-wish).
 */
function product_card(array $p, bool $showRemoveWish = false): void
{
    $quote     = $p['price'] === null;
    $stockLabel = stock_label((int) $p['stock'], (int) $p['low_at']);
    $wished    = $showRemoveWish || in_array((int) $p['id'], wish_ids(), true);
    $detailUrl = url('san-pham-chi-tiet.php?id=' . (int) $p['id']);
    ?>
    <article class="product-card h-100">
        <div class="thumb">
            <a href="<?= $detailUrl ?>">
                <img src="<?= e($p['image'] ?? asset('images/placeholder-product.svg')) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            </a>
            <?php if (!empty($p['tag'])): ?><span class="tag"><?= e($p['tag']) ?></span><?php endif; ?>
            <div class="thumb-actions">
                <button type="button" class="btn btn-icon" data-wish data-product-id="<?= (int) $p['id'] ?>"<?= $showRemoveWish ? ' data-remove-card' : '' ?> title="<?= $wished ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' ?>" aria-pressed="<?= $wished ? 'true' : 'false' ?>"><i class="bi <?= $wished ? 'bi-heart-fill' : 'bi-heart' ?>"></i></button>
                <button type="button" class="btn btn-icon" title="Xem nhanh" data-bs-toggle="modal" data-bs-target="#quickViewModal" data-product-id="<?= (int) $p['id'] ?>"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="body">
            <div class="meta"><?= e($p['brand']) ?><?= $p['series'] ? ' · ' . e($p['series']) : '' ?></div>
            <h3 class="title"><a href="<?= $detailUrl ?>"><?= e($p['name']) ?></a></h3>
            <div class="code">Mã hàng: <strong><?= e($p['code']) ?></strong></div>
            <?php if ($p['rating'] > 0): ?><div class="mb-1"><?= stars((int) $p['rating']) ?></div><?php endif; ?>
            <div class="price-row">
                <?php if ($quote): ?>
                    <span class="price price-quote">Liên hệ báo giá</span>
                <?php else: ?>
                    <span class="price"><?= money($p['price']) ?></span>
                    <?php if (!empty($p['old_price'])): ?><del class="old-price"><?= money($p['old_price']) ?></del><?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="stock stock-<?= stock_class($p) ?>"><i class="bi bi-circle-fill"></i> <?= e($stockLabel) ?></div>
        </div>
        <div class="foot">
            <?php if ($quote): ?>
                <a class="btn btn-outline-ink w-100" href="<?= url('bao-gia.php?sp=' . (int) $p['id']) ?>"><i class="bi bi-file-earmark-text"></i> Yêu cầu báo giá</a>
            <?php elseif ($p['stock'] <= 0 || $p['status'] !== 'Đang kinh doanh'): ?>
                <button class="btn btn-outline-ink w-100" type="button" disabled><?= $p['status'] !== 'Đang kinh doanh' ? e($p['status']) : 'Tạm hết hàng' ?></button>
            <?php else: ?>
                <button class="btn btn-ink w-100" type="button" data-add-to-cart data-product-id="<?= (int) $p['id'] ?>"><i class="bi bi-cart-plus"></i> Thêm vào giỏ</button>
            <?php endif; ?>
        </div>
    </article>
    <?php
}
