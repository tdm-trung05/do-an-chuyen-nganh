<?php
/**
 * Bộ lọc danh sách sản phẩm: danh mục, dòng, khoảng giá, tình trạng.
 * $f là các tham số đang lọc (thường là $_GET); các ô được chọn lại theo đó.
 */
function filter_sidebar(array $f = []): void
{
    $cats    = shop_categories();
    $series  = shop_series();
    $curCat  = (int) ($f['danh-muc'] ?? 0);
    $selDong = int_list($f['dong'] ?? []);
    ?>
    <aside class="filter-panel" aria-label="Bộ lọc sản phẩm">
        <form action="<?= url('san-pham.php') ?>" method="get">
            <?php if (!empty($f['q'])): ?><input type="hidden" name="q" value="<?= e((string) $f['q']) ?>"><?php endif; ?>
            <?php if (!empty($f['sort'])): ?><input type="hidden" name="sort" value="<?= e((string) $f['sort']) ?>"><?php endif; ?>
            <section class="filter-group">
                <h6>Danh mục</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= url('san-pham.php') ?>"<?= $curCat === 0 ? ' class="fw-bold"' : '' ?>>Tất cả</a></li>
                    <?php foreach ($cats as $c): ?>
                    <li>
                        <a href="<?= url('san-pham.php?danh-muc=' . $c['id']) ?>"<?= $curCat === $c['id'] ? ' class="fw-bold"' : '' ?>><?= e($c['name']) ?> <span class="text-muted">(<?= (int) $c['count'] ?>)</span></a>
                        <?php if ($c['children'] && ($curCat === $c['id'] || in_array($curCat, array_column($c['children'], 'id'), true))): ?>
                        <ul class="list-unstyled ms-3">
                            <?php foreach ($c['children'] as $ch): ?>
                            <li><a href="<?= url('san-pham.php?danh-muc=' . $ch['id']) ?>"<?= $curCat === $ch['id'] ? ' class="fw-bold"' : '' ?>><?= e($ch['name']) ?> <span class="text-muted">(<?= (int) $ch['count'] ?>)</span></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($curCat > 0): ?><input type="hidden" name="danh-muc" value="<?= $curCat ?>"><?php endif; ?>
            </section>
            <section class="filter-group">
                <h6>Dòng sản phẩm</h6>
                <?php foreach ($series as $s): ?>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="dong[]" value="<?= (int) $s['id'] ?>" id="dong<?= (int) $s['id'] ?>"<?= in_array($s['id'], $selDong, true) ? ' checked' : '' ?>><label class="form-check-label" for="dong<?= (int) $s['id'] ?>"><?= e($s['name']) ?></label></div>
                <?php endforeach; ?>
            </section>
            <section class="filter-group">
                <h6>Khoảng giá</h6>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" class="form-control form-control-sm" name="gia_tu" placeholder="Từ" min="0" value="<?= e((string) ($f['gia_tu'] ?? '')) ?>" aria-label="Giá từ">
                    <span>–</span>
                    <input type="number" class="form-control form-control-sm" name="gia_den" placeholder="Đến" min="0" value="<?= e((string) ($f['gia_den'] ?? '')) ?>" aria-label="Giá đến">
                </div>
            </section>
            <section class="filter-group">
                <h6>Tình trạng</h6>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="con_hang" value="1" id="conHang"<?= !empty($f['con_hang']) ? ' checked' : '' ?>><label class="form-check-label" for="conHang">Chỉ hiện hàng còn</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="bao_gia" value="1" id="baoGia"<?= !empty($f['bao_gia']) ? ' checked' : '' ?>><label class="form-check-label" for="baoGia">Sản phẩm cần báo giá</label></div>
            </section>
            <div class="d-grid gap-2">
                <button class="btn btn-ink" type="submit">Áp dụng bộ lọc</button>
                <a class="btn btn-link" href="<?= url('san-pham.php') ?>">Xóa bộ lọc</a>
            </div>
        </form>
    </aside>
    <?php
}
