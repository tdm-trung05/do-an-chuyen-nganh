<?php
/** MH02 – Danh sách sản phẩm: tìm theo tên hoặc mã hàng, bộ lọc, sắp xếp, phân trang, xem nhanh. */
require_once __DIR__ . '/includes/bootstrap.php';

$filters = [
    'q'        => trim((string) ($_GET['q'] ?? '')),
    'danh-muc' => (int) ($_GET['danh-muc'] ?? 0),
    'dong'     => (array) ($_GET['dong'] ?? []),   // chấp nhận cả ?dong=3 lẫn ?dong[]=3
    'gia_tu'   => (string) ($_GET['gia_tu'] ?? ''),
    'gia_den'  => (string) ($_GET['gia_den'] ?? ''),
    'con_hang' => !empty($_GET['con_hang']),
    'bao_gia'  => !empty($_GET['bao_gia']),
    'sort'     => (string) ($_GET['sort'] ?? 'moi'),
];
$page   = max(1, (int) ($_GET['trang'] ?? 1));
$result = shop_search($filters, $page, PAGE_SIZE);

// Tra theo mã hàng: gõ đúng một mã hàng thì mở thẳng trang chi tiết (trang 1, không lọc thêm)
if ($filters['q'] !== '' && $page === 1 && $result['total'] === 1 && !$filters['danh-muc'] && !$filters['dong']) {
    $only = $result['rows'][0];
    if (mb_strtolower($only['code'], 'UTF-8') === mb_strtolower($filters['q'], 'UTF-8')) {
        redirect(url('san-pham-chi-tiet.php?id=' . $only['id']));
    }
}

// Tiêu đề và các nhãn bộ lọc đang áp dụng
$catName = $filters['danh-muc'] > 0 ? shop_category_name($filters['danh-muc']) : null;
$heading = $filters['q'] !== '' ? 'Kết quả cho “' . $filters['q'] . '”' : ($catName ?? 'Tất cả sản phẩm');

$chips = [];   // [nhãn => địa chỉ bỏ bộ lọc đó]
$without = static function (array $drop) use ($filters): string {
    $q = $_GET;
    unset($q['trang']);
    foreach ($drop as $k) { unset($q[$k]); }
    return url('san-pham.php' . ($q ? '?' . http_build_query($q) : ''));
};
if ($filters['q'] !== '')        { $chips['Từ khóa: ' . $filters['q']] = $without(['q']); }
if ($catName !== null)           { $chips[$catName] = $without(['danh-muc']); }
foreach (shop_series() as $s)    { if (in_array($s['id'], int_list($filters['dong']), true)) {
    $q = $_GET; $q['dong'] = array_values(array_diff(int_list($filters['dong']), [$s['id']])); unset($q['trang']);
    if (!$q['dong']) { unset($q['dong']); }
    $chips[$s['name']] = url('san-pham.php' . ($q ? '?' . http_build_query($q) : ''));
} }
if ($filters['gia_tu'] !== '' || $filters['gia_den'] !== '') {
    $chips['Giá ' . ($filters['gia_tu'] !== '' ? money((int) $filters['gia_tu']) : '0 ₫') . ' – ' . ($filters['gia_den'] !== '' ? money((int) $filters['gia_den']) : '…')] = $without(['gia_tu', 'gia_den']);
}
if ($filters['con_hang'])        { $chips['Còn hàng'] = $without(['con_hang']); }
if ($filters['bao_gia'])         { $chips['Cần báo giá'] = $without(['bao_gia']); }

$crumbs = ['Sản phẩm' => $catName !== null || $filters['q'] !== '' ? 'san-pham.php' : null];
if ($catName !== null) { $crumbs[$catName] = null; }
elseif ($filters['q'] !== '') { $crumbs['Tìm kiếm'] = null; }

$sorts = ['moi' => 'Mới nhất', 'gia-tang' => 'Giá tăng dần', 'gia-giam' => 'Giá giảm dần', 'ban-chay' => 'Bán chạy', 'xem-nhieu' => 'Xem nhiều', 'ma' => 'Mã hàng A–Z'];
$sortNow = isset($sorts[$filters['sort']]) ? $filters['sort'] : 'moi';

// Giữ các bộ lọc khi đổi cách sắp xếp (form sắp xếp gửi lại toàn bộ tham số trừ sort và trang)
$sortHidden = [];
$flat = $_GET;
unset($flat['sort'], $flat['trang']);
foreach (explode('&', http_build_query($flat)) as $pair) {
    if ($pair !== '') {
        [$hk, $hv] = array_pad(explode('=', $pair, 2), 2, '');
        $sortHidden[] = [urldecode($hk), urldecode($hv)];
    }
}

page_begin('Sản phẩm', $crumbs, 'cong-tac');
?>
<div class="container">
    <?php page_title($heading, 'Tìm theo tên hoặc mã hàng, lọc theo danh mục, dòng sản phẩm, khoảng giá và tồn kho.'); ?>
    <div class="row g-4">
        <div class="col-lg-3">
            <button class="btn btn-outline-ink w-100 d-lg-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse"><i class="bi bi-funnel"></i> Bộ lọc</button>
            <div class="collapse d-lg-block" id="filterCollapse"><?php filter_sidebar($filters); ?></div>
        </div>
        <div class="col-lg-9">
            <div class="list-toolbar">
                <div>
                    <strong><?= (int) $result['total'] ?></strong> sản phẩm
                    <span class="ms-2">
                        <?php foreach ($chips as $label => $href): ?>
                        <a class="chip-active text-decoration-none" href="<?= e($href) ?>" title="Bỏ bộ lọc này"><?= e($label) ?> <i class="bi bi-x"></i></a>
                        <?php endforeach; ?>
                    </span>
                </div>
                <form class="d-flex align-items-center gap-2" method="get" action="<?= url('san-pham.php') ?>">
                    <?php foreach ($sortHidden as [$hk, $hv]): ?>
                    <input type="hidden" name="<?= e($hk) ?>" value="<?= e($hv) ?>">
                    <?php endforeach; ?>
                    <label class="small text-muted" for="sort">Sắp xếp</label>
                    <select id="sort" name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <?php foreach ($sorts as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= $k === $sortNow ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-sm btn-outline-ink" type="submit">Sắp xếp</button></noscript>
                </form>
            </div>
            <?php if (!$result['rows']): ?>
            <div class="panel text-center">
                <p class="mb-2">Không có sản phẩm phù hợp.</p>
                <p class="small text-muted mb-3">Thử bỏ bớt bộ lọc hoặc kiểm tra lại mã hàng.</p>
                <a class="btn btn-ink" href="<?= url('san-pham.php') ?>">Xem tất cả sản phẩm</a>
                <a class="btn btn-outline-ink" href="<?= url('bao-gia.php') ?>">Gửi yêu cầu báo giá</a>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($result['rows'] as $p): ?>
                <div class="col-6 col-md-4"><?php product_card($p); ?></div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?php pagination($result['page'], $result['pages']); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php page_end(); ?>
