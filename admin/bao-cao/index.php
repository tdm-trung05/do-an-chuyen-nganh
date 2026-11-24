<?php
/** Báo biểu BB01–BB07 (mục 3.4). Mỗi báo biểu chỉ hiện cho đúng vai trò; số liệu lấy từ các view vw_BB01…07. */
require_once __DIR__ . '/../../includes/bootstrap.php';
$u       = admin_guard('bao-cao');
$allowed = admin_reports_for($u['role']);
$code    = strtoupper((string) ($_GET['bb'] ?? ''));
if (!isset($allowed[$code])) {
    $code = (string) array_key_first($allowed);
}
$def  = $allowed[$code];
$to   = admin_date($_GET['den'] ?? '', date('Y-m-d'));
$from = admin_date($_GET['tu'] ?? '', date('Y-m-01'));
$err  = '';
if ($from > $to) {
    $err  = 'Ngày bắt đầu phải trước ngày kết thúc.';
    [$from, $to] = [$to, $from];
}
$ky  = ($_GET['ky'] ?? '') === 'thang' ? 'thang' : 'ngay';
$rep = admin_report_run($code, $from, $to, $ky);
if (isset($_GET['xuat'])) {
    admin_report_csv($code, $rep);
}
admin_begin('Báo cáo', 'bao-cao', 'Số liệu doanh thu và tồn kho khớp với đơn hàng, phiếu nhập.');
$tab = static fn(string $c): string => e(aurl('bao-cao/index.php?' . http_build_query(['bb' => $c, 'tu' => $_GET['tu'] ?? '', 'den' => $_GET['den'] ?? '', 'ky' => $_GET['ky'] ?? ''])));
?>
<div class="row g-3 mb-3">
    <?php foreach ($allowed as $c => $r): ?>
    <div class="col-md-6 col-xl-4">
        <a class="card-box d-block p-3 text-decoration-none h-100<?= $c === $code ? ' border-primary' : '' ?>" href="<?= $tab($c) ?>">
            <strong class="text-dark"><?= e($c) ?> · <?= e($r[0]) ?></strong>
            <p class="small text-muted my-1"><?= e($r[1]) ?></p>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php if ($err): ?><div class="alert alert-warning small"><?= e($err) ?> Đã tự đổi chỗ hai ngày.</div><?php endif; ?>
<section class="card-box mb-3">
    <div class="head"><h2><?= e($code) ?> · <?= e($def[0]) ?></h2>
        <form class="d-flex gap-2 flex-wrap" method="get">
            <input type="hidden" name="bb" value="<?= e($code) ?>">
            <?php if ($def[3] === 'range'): ?>
            <input class="form-control form-control-sm" type="date" name="tu" value="<?= e($from) ?>" aria-label="Từ ngày">
            <input class="form-control form-control-sm" type="date" name="den" value="<?= e($to) ?>" aria-label="Đến ngày">
            <?php if ($code === 'BB01'): ?>
            <select class="form-select form-select-sm w-auto" name="ky" aria-label="Kỳ thống kê">
                <option value="ngay"<?= $ky === 'ngay' ? ' selected' : '' ?>>Theo ngày</option>
                <option value="thang"<?= $ky === 'thang' ? ' selected' : '' ?>>Theo tháng</option>
            </select>
            <?php endif; ?>
            <button class="btn btn-sm btn-ink" type="submit">Xem</button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-ink" type="submit" name="xuat" value="1"><i class="bi bi-download"></i> Xuất file</button>
        </form>
    </div>
    <?php if ($code === 'BB01' && $rep['rows']):
        $max = max(1, max(array_column($rep['rows'], 2))); ?>
    <div class="body"><div class="chart-placeholder" role="img" aria-label="Biểu đồ doanh thu theo <?= $ky === 'thang' ? 'tháng' : 'ngày' ?>">
        <?php foreach (array_slice($rep['rows'], -31) as $r): ?><div class="bar" style="height:<?= max(2, (int) round($r[2] / $max * 100)) ?>%" title="<?= e($r[0] . ': ' . money($r[2])) ?>"></div><?php endforeach; ?>
    </div></div>
    <?php endif; ?>
    <?php
    if (!$rep['rows']) {
        echo '<div class="body text-center text-muted py-4">Không có dữ liệu trong kỳ đã chọn.</div>';
    } else {
        $fmt = static function (array $row) use ($rep): array {
            $out = [];
            foreach ($row as $i => $cell) {
                $out[] = in_array($i, $rep['money'], true) ? money($cell) : (isset($rep['badge']) && $rep['badge'] === $i ? raw(badge((string) $cell)) : $cell);
            }
            return $out;
        };
        $rows = array_map($fmt, $rep['rows']);
        if (!empty($rep['total'])) {
            $t = $fmt($rep['total']);
            $rows[] = array_map(static fn($c) => raw('<strong>' . ($c instanceof RawHtml ? (string) $c : e($c)) . '</strong>'), $t);
        }
        data_table($rep['headers'], $rows, ['right' => $rep['right']]);
    }
    ?>
</section>
<?php admin_end(); ?>
