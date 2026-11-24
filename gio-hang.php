<?php
/** MH05 – Giỏ hàng: chỉnh số lượng, xóa sản phẩm, sang bước thanh toán. */
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_customer();   // giỏ hàng gắn với tài khoản khách hàng (bảng GIOHANG theo MaND)
$items    = cart_items((int) $me['MaND']);
$subtotal = 0;
$hasProblem = false;
foreach ($items as $row) {
    $subtotal += $row['line'];
    if ($row['problem'] !== '') { $hasProblem = true; }
}
page_begin('Giỏ hàng', ['Giỏ hàng' => null]);
?>
<div class="container">
    <?php page_title('Giỏ hàng', 'Kiểm tra số lượng rồi bấm thanh toán.'); ?>
    <?php if (!$items): ?>
    <div class="panel text-center">
        <i class="bi bi-cart3" style="font-size:2.5rem"></i>
        <p class="mt-2 mb-3">Giỏ hàng của bạn đang trống.</p>
        <a class="btn btn-ink" href="<?= url('san-pham.php') ?>">Xem sản phẩm</a>
    </div>
    <?php else: ?>
    <form action="<?= url('gio-hang-xu-ly.php') ?>" method="post" id="cartForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cap-nhat">
        <!-- Nút mặc định khi nhấn Enter trong ô số lượng (tránh kích hoạt nút xóa đầu tiên) -->
        <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Cập nhật</button>
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if ($hasProblem): ?>
                <div class="alert alert-warning small" role="alert">Một số sản phẩm không đặt hàng được (xem ghi chú đỏ bên dưới). Hãy chỉnh số lượng hoặc xóa chúng khỏi giỏ để thanh toán.</div>
                <?php endif; ?>
                <div class="panel p-0 table-responsive">
                    <table class="table cart-table mb-0">
                        <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">Số lượng</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $row): $p = $row['product']; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img class="cart-item-img" src="<?= e($p['image']) ?>" alt="">
                                        <div>
                                            <a class="fw-semibold" href="<?= url('san-pham-chi-tiet.php?id=' . $p['id']) ?>"><?= e($p['name']) ?></a>
                                            <div class="small text-muted">Mã hàng: <?= e($p['code']) ?></div>
                                            <?php if ($row['problem'] !== ''): ?><div class="small text-danger"><?= e($row['problem']) ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?= money($p['price']) ?></td>
                                <td class="text-center"><div class="qty-input"><button type="button" class="btn" aria-label="Giảm">−</button><input type="text" inputmode="numeric" name="so_luong[<?= $p['id'] ?>]" value="<?= (int) $row['qty'] ?>" data-max="<?= max(1, (int) $p['stock']) ?>" aria-label="Số lượng <?= e($p['name']) ?>"><button type="button" class="btn" aria-label="Tăng">+</button></div></td>
                                <td class="text-end fw-semibold"><?= money($row['line']) ?></td>
                                <td class="text-end"><button class="btn btn-icon" type="submit" name="xoa" value="<?= $p['id'] ?>" title="Xóa khỏi giỏ" aria-label="Xóa <?= e($p['name']) ?> khỏi giỏ"><i class="bi bi-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= url('san-pham.php') ?>"><i class="bi bi-arrow-left"></i> Tiếp tục xem sản phẩm</a>
                    <button class="btn btn-outline-ink" type="submit">Cập nhật giỏ hàng</button>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel summary-box">
                    <h2 class="panel-title">Tóm tắt đơn hàng</h2>
                    <div class="summary-row"><span>Tạm tính</span><span><?= money($subtotal) ?></span></div>
                    <div class="summary-row"><span>Phí giao hàng</span><span><?= money((int) SHIPPING_FEE) ?></span></div>
                    <div class="summary-row total"><span>Tổng cộng</span><span class="price"><?= money($subtotal + (int) SHIPPING_FEE) ?></span></div>
                    <?php if ($hasProblem): ?>
                    <button class="btn btn-accent w-100 mt-3" type="button" disabled>Thanh toán</button>
                    <?php else: ?>
                    <a class="btn btn-accent w-100 mt-3" href="<?= url('thanh-toan.php') ?>">Thanh toán</a>
                    <?php endif; ?>
                    <p class="small text-muted mt-3 mb-0">Sản phẩm “Liên hệ báo giá” không thêm vào giỏ được, hãy dùng <a href="<?= url('bao-gia.php') ?>">yêu cầu báo giá</a>.</p>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php page_end(); ?>
