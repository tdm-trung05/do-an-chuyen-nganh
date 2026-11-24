<?php
/** MH03 – Chi tiết sản phẩm: hình, thông số, tài liệu kỹ thuật, đánh giá, thêm vào giỏ hoặc yêu cầu báo giá. */
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$p  = $id > 0 ? shop_product($id) : null;
if ($p === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$self = url('san-pham-chi-tiet.php?id=' . $p['id']);

// Gửi đánh giá (UC07): chỉ khách đã mua và nhận hàng, mỗi sản phẩm một lần
if (is_post() && ($_POST['action'] ?? '') === 'danh-gia') {
    $me = require_customer();
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif (review_state((int) $me['MaND'], $p['id']) !== 'duoc-danh-gia') {
        flash_set('danger', 'Chỉ khách đã nhận hàng mới đánh giá được, và mỗi sản phẩm đánh giá một lần.');
    } else {
        $stars = (int) ($_POST['so_sao'] ?? 0);
        $text  = trim((string) ($_POST['noi_dung'] ?? ''));
        if ($stars < 1 || $stars > 5) {
            flash_set('danger', 'Hãy chọn số sao từ 1 đến 5.');
        } elseif (mb_strlen($text, 'UTF-8') > 500) {
            flash_set('danger', 'Nhận xét tối đa 500 ký tự.');
        } else {
            try {
                db_exec('INSERT INTO DANHGIA (MaND, MaSP, SoSao, NoiDung) VALUES (?, ?, ?, ?)', [(int) $me['MaND'], $p['id'], $stars, $text === '' ? null : $text]);
                flash_set('success', 'Cảm ơn bạn đã đánh giá sản phẩm.');
            } catch (PDOException $ex) {
                // Bấm đúp / gửi lại: vi phạm UQ_DANHGIA_ND_SP (đã có đánh giá) không phải lỗi hệ thống
                if ((int) ($ex->errorInfo[1] ?? 0) === 1062) {
                    flash_set('info', 'Bạn đã đánh giá sản phẩm này rồi.');
                } else {
                    flash_set('danger', db_error_message($ex));
                }
            }
        }
    }
    redirect($self . '#danh-gia');
}

if (!is_post()) {
    db_exec('UPDATE SANPHAM SET LuotXem = LuotXem + 1 WHERE MaSP = ?', [$p['id']]);   // LuotXem tăng mỗi lần mở trang chi tiết
}
$images   = shop_images($p['id']);
if ($images[0] === asset('images/placeholder-product.svg')) {
    $images = [$p['image']];
}
$variants = shop_variants($p);
$specs    = shop_specs($p['id']);
$docs     = shop_documents($p);
$reviews  = shop_reviews($p['id']);
$related  = shop_related($p, 4);
$x        = shop_product_extra($p['id']);
$me       = auth_user();
$isCustomer = $me !== null && $me['role'] === 'KH';
$reviewState = $isCustomer ? review_state((int) $me['MaND'], $p['id']) : 'chua-dang-nhap';
$wished   = in_array($p['id'], wish_ids(), true);
$quote    = $p['price'] === null;
$inCart   = $isCustomer ? cart_qty((int) $me['MaND'], $p['id']) : 0;

$crumbs = ['Sản phẩm' => 'san-pham.php', $p['category'] => 'san-pham.php?danh-muc=' . $p['category_id'], $p['name'] => null];
page_begin($p['name'], $crumbs, 'cong-tac');
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="gallery-main"><img id="galleryMain" src="<?= e($images[0]) ?>" alt="<?= e($p['name']) ?>"></div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-gallery-thumb="<?= e($img) ?>" aria-label="Xem ảnh <?= $i + 1 ?>"><img src="<?= e($img) ?>" alt=""></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-7 product-info">
            <div class="small text-muted"><?= e($p['brand']) ?><?= $p['series'] !== '' ? ' · ' . e($p['series']) : '' ?></div>
            <h1 class="h3 mt-1"><?= e($p['name']) ?></h1>
            <p class="code-line mb-2">Mã hàng: <strong><?= e($p['code']) ?></strong>
                <?php if ($p['rating_n'] > 0): ?> · <?= stars($p['rating']) ?> <a href="#danh-gia" class="small" data-open-tab="#danh-gia"><?= (int) $p['rating_n'] ?> đánh giá</a><?php endif; ?></p>
            <?php if ($x['short'] !== ''): ?><p class="text-muted mb-2"><?= e($x['short']) ?></p><?php endif; ?>
            <div class="price price-big mb-1"><?= money($p['price']) ?><?php if ($p['old_price'] !== null): ?> <del class="text-muted fs-6"><?= money($p['old_price']) ?></del><?php if ($x['percent'] > 0): ?> <span class="badge text-bg-danger">-<?= e(rtrim(rtrim(number_format($x['percent'], 2, ',', ''), '0'), ',')) ?>%</span><?php endif; ?><?php endif; ?></div>
            <p class="stock stock-<?= stock_class($p) ?> small"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> <?= e(stock_label($p['stock'], $p['low_at'])) ?><?= $p['stock'] > 0 ? ' (' . (int) $p['stock'] . ' sản phẩm)' : '' ?></p>

            <?php if ($variants && count($variants) > 1): ?>
            <div class="mb-3">
                <div class="form-label">Mã hàng cùng kiểu (màu mặt, số module)</div>
                <div class="variant-list">
                    <?php foreach ($variants as $v): ?>
                    <a class="<?= (int) $v['id'] === $p['id'] ? 'active' : '' ?>" href="<?= url('san-pham-chi-tiet.php?id=' . (int) $v['id']) ?>"><?= e($v['code']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($quote): ?>
                <p class="text-muted">Sản phẩm này bán theo báo giá. Gửi yêu cầu để nhận giá theo số lượng.</p>
                <div class="d-flex gap-2 mb-4">
                    <a class="btn btn-ink btn-lg" href="<?= url('bao-gia.php?sp=' . $p['id']) ?>"><i class="bi bi-file-earmark-text"></i> Yêu cầu báo giá</a>
                    <button class="btn btn-icon btn-lg" type="button" data-wish data-product-id="<?= $p['id'] ?>" title="<?= $wished ? 'Bỏ yêu thích' : 'Yêu thích' ?>" aria-pressed="<?= $wished ? 'true' : 'false' ?>"><i class="bi <?= $wished ? 'bi-heart-fill' : 'bi-heart' ?>"></i></button>
                </div>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2 mb-2" data-qty-scope>
                    <?php if ($p['stock'] > 0 && $p['status'] === 'Đang kinh doanh'): ?>
                    <div class="qty-input"><button type="button" class="btn" aria-label="Giảm">−</button><input type="text" inputmode="numeric" value="1" data-max="<?= (int) $p['stock'] ?>" aria-label="Số lượng"><button type="button" class="btn" aria-label="Tăng">+</button></div>
                    <button class="btn btn-ink btn-lg flex-grow-1" type="button" data-add-to-cart data-product-id="<?= $p['id'] ?>"><i class="bi bi-cart-plus"></i> Thêm vào giỏ</button>
                    <?php else: ?>
                    <button class="btn btn-outline-ink btn-lg flex-grow-1" type="button" disabled><?= $p['status'] === 'Đang kinh doanh' ? 'Tạm hết hàng' : e($p['status']) ?></button>
                    <?php endif; ?>
                    <button class="btn btn-icon btn-lg" type="button" data-wish data-product-id="<?= $p['id'] ?>" title="<?= $wished ? 'Bỏ yêu thích' : 'Yêu thích' ?>" aria-pressed="<?= $wished ? 'true' : 'false' ?>"><i class="bi <?= $wished ? 'bi-heart-fill' : 'bi-heart' ?>"></i></button>
                </div>
                <?php if ($inCart > 0): ?><p class="small text-muted mb-3">Bạn đang có <?= $inCart ?> sản phẩm này trong <a href="<?= url('gio-hang.php') ?>">giỏ hàng</a>.</p><?php else: ?><div class="mb-3"></div><?php endif; ?>
            <?php endif; ?>

            <ul class="list-unstyled small text-muted mb-0">
                <li><i class="bi bi-patch-check"></i> <?= $p['warranty'] > 0 ? 'Bảo hành ' . $p['warranty'] . ' tháng kể từ ngày đơn hoàn tất' : 'Sản phẩm không có bảo hành' ?></li>
                <li><i class="bi bi-arrow-repeat"></i> Hàng lỗi khi nhận được đổi trả trong <?= (int) $p['return_days'] ?> ngày</li>
                <li><i class="bi bi-truck"></i> Giao hàng toàn quốc, phí giao từ <?= money((int) SHIPPING_FEE) ?> (nhân viên có thể điều chỉnh theo địa chỉ)</li>
                <?php if ($x['video'] !== ''): ?><li><i class="bi bi-play-circle"></i> <a href="<?= e($x['video']) ?>" target="_blank" rel="noopener noreferrer">Xem video giới thiệu</a></li><?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="panel mt-4">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-spec" type="button" role="tab">Thông số kỹ thuật</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button" role="tab">Mô tả</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-doc" type="button" role="tab">Tài liệu<?= $docs ? ' (' . count($docs) . ')' : '' ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#danh-gia" type="button" role="tab" id="tabReviewBtn">Đánh giá (<?= (int) $p['rating_n'] ?>)</button></li>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-spec" role="tabpanel">
                <table class="table spec-table mb-0" style="max-width:640px"><tbody>
                    <tr><th scope="row">Thương hiệu</th><td><?= e($p['brand']) ?></td></tr>
                    <?php if ($p['series'] !== ''): ?><tr><th scope="row">Dòng sản phẩm</th><td><?= e($p['series']) ?></td></tr><?php endif; ?>
                    <tr><th scope="row">Danh mục</th><td><?= e($p['category']) ?></td></tr>
                    <?php foreach ($specs as $row): ?><tr><th scope="row"><?= e($row[0]) ?></th><td><?= e($row[1]) ?></td></tr><?php endforeach; ?>
                    <?php if ($x['origin'] !== ''): ?><tr><th scope="row">Xuất xứ</th><td><?= e($x['origin']) ?></td></tr><?php endif; ?>
                    <?php if ($x['year'] !== null): ?><tr><th scope="row">Năm ra mắt</th><td><?= (int) $x['year'] ?></td></tr><?php endif; ?>
                    <?php if ($x['accessory'] !== ''): ?><tr><th scope="row">Phụ kiện đi kèm</th><td><?= e($x['accessory']) ?></td></tr><?php endif; ?>
                    <tr><th scope="row">Bảo hành</th><td><?= $p['warranty'] > 0 ? (int) $p['warranty'] . ' tháng' . ($x['wtype'] !== '' ? ' (' . e($x['wtype']) . ')' : '') : 'Không' ?></td></tr>
                    <?php if ($x['aftercare'] !== ''): ?><tr><th scope="row">Chính sách hậu mãi</th><td><?= nl2br(e($x['aftercare'])) ?></td></tr><?php endif; ?>
                </tbody></table>
            </div>
            <div class="tab-pane fade" id="tab-desc" role="tabpanel"><p class="mb-0"><?= $p['desc'] !== '' ? nl2br(e($p['desc'])) : 'Chưa có mô tả cho sản phẩm này.' ?></p></div>
            <div class="tab-pane fade" id="tab-doc" role="tabpanel">
                <?php if (!$docs): ?>
                <p class="text-muted mb-0">Chưa có tài liệu kỹ thuật.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($docs as $d): ?>
                    <li class="py-2 border-bottom"><i class="bi bi-file-earmark-pdf text-danger"></i>
                        <?php if ($d['url'] !== null): ?><a href="<?= e($d['url']) ?>" target="_blank" rel="noopener"><?= e($d['title']) ?></a> <span class="text-muted small">(<?= e($d['type']) ?><?= $d['size'] !== '' ? ', ' . e($d['size']) : '' ?>)</span>
                        <?php else: ?><?= e($d['title']) ?> <span class="text-muted small">(tệp chưa được tải lên)</span><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="danh-gia" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-3"><div class="rating-summary">
                        <?php if ($p['rating_n'] > 0): ?>
                        <div class="score"><?= e(number_format($p['rating_avg'], 1, ',', '')) ?></div><?= stars($p['rating']) ?><div class="small text-muted"><?= (int) $p['rating_n'] ?> đánh giá</div>
                        <?php else: ?><div class="small text-muted">Chưa có đánh giá</div><?php endif; ?>
                    </div></div>
                    <div class="col-md-9">
                        <?php foreach ($reviews as $r): ?>
                        <div class="review-item"><strong><?= e($r['HoTen']) ?></strong> <?= stars((int) $r['SoSao']) ?> <span class="small text-muted ms-2"><?= e(date('d/m/Y', strtotime($r['NgayDG']))) ?></span><?php if ($r['NoiDung'] !== null && $r['NoiDung'] !== ''): ?><p class="mb-0 mt-1"><?= nl2br(e($r['NoiDung'])) ?></p><?php endif; ?>
                            <?php if ($r['PhanHoi'] !== null && $r['PhanHoi'] !== ''): ?><div class="small ms-3 mt-2 ps-2 border-start"><strong><?= e(SITE_NAME) ?> phản hồi:</strong> <?= nl2br(e($r['PhanHoi'])) ?></div><?php endif; ?></div>
                        <?php endforeach; ?>
                        <?php if ($reviewState === 'duoc-danh-gia'): ?>
                        <form class="mt-3" action="<?= $self ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="danh-gia">
                            <div class="form-label">Viết đánh giá</div>
                            <select class="form-select w-auto mb-2" name="so_sao" aria-label="Số sao" required>
                                <option value="">Chọn số sao</option>
                                <?php for ($s = 5; $s >= 1; $s--): ?><option value="<?= $s ?>"><?= $s ?> sao</option><?php endfor; ?>
                            </select>
                            <textarea class="form-control mb-2" name="noi_dung" rows="3" maxlength="500" placeholder="Nhận xét của bạn (không bắt buộc)"></textarea>
                            <button type="submit" class="btn btn-ink">Gửi đánh giá</button>
                        </form>
                        <?php elseif ($reviewState === 'da-danh-gia'): ?>
                        <p class="small text-muted mt-3 mb-0">Bạn đã đánh giá sản phẩm này.</p>
                        <?php elseif ($reviewState === 'chua-dang-nhap'): ?>
                        <p class="small text-muted mt-3 mb-0"><a href="<?= url('dang-nhap.php?quay-lai=' . rawurlencode(request_uri())) ?>">Đăng nhập</a> để đánh giá (chỉ khách đã nhận hàng).</p>
                        <?php else: ?>
                        <p class="small text-muted mt-3 mb-0">Chỉ khách đã mua và nhận sản phẩm này mới viết được đánh giá.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($related): ?>
    <div class="section-head mt-5"><h2><?= $p['series'] !== '' ? 'Sản phẩm cùng dòng' : 'Sản phẩm liên quan' ?></h2></div>
    <div class="row g-3">
        <?php foreach ($related as $rp): ?><div class="col-6 col-lg-3"><?php product_card($rp); ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php page_end(); ?>
