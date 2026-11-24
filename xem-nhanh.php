<?php
/** Nội dung hộp "Xem nhanh": trả về đoạn HTML của một sản phẩm (nạp bằng AJAX từ assets/js/main.js). */
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
$p = shop_product((int) ($_GET['id'] ?? 0));
if ($p === null) {
    http_response_code(404);
    echo '<div class="text-center text-muted py-5">Không tìm thấy sản phẩm.</div>';
    exit;
}
$specs  = array_slice(shop_specs($p['id']), 0, 5);
$quote  = $p['price'] === null;
$detail = url('san-pham-chi-tiet.php?id=' . $p['id']);
?>
<div class="row g-4">
    <div class="col-md-5">
        <div class="gallery-main"><img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>"></div>
    </div>
    <div class="col-md-7">
        <div class="meta text-muted small"><?= e($p['brand']) ?><?= $p['series'] !== '' ? ' · ' . e($p['series']) : '' ?></div>
        <h3 class="h4"><?= e($p['name']) ?></h3>
        <p class="text-muted mb-2">Mã hàng: <strong><?= e($p['code']) ?></strong></p>
        <?php if ($p['rating_n'] > 0): ?>
        <div class="mb-2"><?= stars($p['rating']) ?> <span class="small text-muted">(<?= (int) $p['rating_n'] ?> đánh giá)</span></div>
        <?php endif; ?>
        <div class="price fs-4 mb-1"><?= money($p['price']) ?></div>
        <p class="stock stock-<?= stock_class($p) ?> small"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> <?= e(stock_label($p['stock'], $p['low_at'])) ?><?= $p['stock'] > 0 ? ' (' . (int) $p['stock'] . ' sản phẩm)' : '' ?></p>
        <p class="small mb-2"><i class="bi bi-patch-check"></i> <?= $p['warranty'] > 0 ? 'Bảo hành ' . (int) $p['warranty'] . ' tháng' : 'Sản phẩm không có bảo hành' ?><?= $p['return_days'] > 0 ? ' · Đổi trả trong ' . (int) $p['return_days'] . ' ngày nếu lỗi' : '' ?></p>
        <?php if ($specs): ?>
        <table class="table table-sm spec-table">
            <tbody>
                <?php foreach ($specs as $row): ?>
                <tr><th scope="row"><?= e($row[0]) ?></th><td><?= e($row[1]) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php if ($quote): ?>
            <a class="btn btn-outline-ink" href="<?= url('bao-gia.php?sp=' . $p['id']) ?>"><i class="bi bi-file-earmark-text"></i> Yêu cầu báo giá</a>
        <?php elseif ($p['stock'] <= 0 || $p['status'] !== 'Đang kinh doanh'): ?>
            <button type="button" class="btn btn-outline-ink" disabled><?= $p['status'] !== 'Đang kinh doanh' ? e($p['status']) : 'Tạm hết hàng' ?></button>
        <?php else: ?>
            <div class="d-flex gap-2 flex-wrap" data-qty-scope>
                <div class="qty-input">
                    <button type="button" class="btn" aria-label="Giảm">−</button>
                    <input type="text" inputmode="numeric" value="1" data-max="<?= (int) $p['stock'] ?>" aria-label="Số lượng">
                    <button type="button" class="btn" aria-label="Tăng">+</button>
                </div>
                <button type="button" class="btn btn-ink flex-grow-1" data-add-to-cart data-product-id="<?= (int) $p['id'] ?>"><i class="bi bi-cart-plus"></i> Thêm vào giỏ</button>
            </div>
        <?php endif; ?>
        <a class="d-inline-block mt-3" href="<?= $detail ?>">Xem trang chi tiết đầy đủ</a>
    </div>
</div>
