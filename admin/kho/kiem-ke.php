<?php
/** MH13 / UC15 – Kiểm kê: nhập số đếm thực tế, nếu lệch thì lập phiếu điều chỉnh kèm lý do. */
require_once __DIR__ . '/../../includes/bootstrap.php';
$staff = admin_guard('kiem-ke');

$q = trim((string) ($_GET['q'] ?? ''));
if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $lines = [];
        foreach ((array) ($_POST['sp'] ?? []) as $i => $sp) {
            $cell = static function (string $k) use ($i): string {   // chỉ nhận chuỗi; giá trị mảng giả mạo coi như rỗng
                $v = is_array($_POST[$k] ?? null) ? ($_POST[$k][$i] ?? '') : '';
                return is_scalar($v) ? (string) $v : '';
            };
            $lines[] = ['sp' => is_scalar($sp) ? (string) $sp : '', 'ht' => $cell('ht'), 'tt' => $cell('tt'), 'ly' => $cell('ly')];
        }
        $res = admin_stocktake_save((int) $staff['MaND'], $lines);
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
        if ($res['ok']) {
            redirect(aurl('kho/kiem-ke.php'));
        }
        $posted = [];   // giữ lại số đã nhập khi lỗi
        foreach ($lines as $l) {
            $posted[(int) $l['sp']] = $l;
        }
    }
}
$posted = $posted ?? [];

$like  = trim($q) === '' ? null : like_escape($q);
$stock = db_all(
    'SELECT s.MaSP, s.MaHang, s.TenSP, t.SoLuongTon FROM SANPHAM s JOIN TONKHO t ON t.MaSP = s.MaSP'
    . ($like ? ' WHERE s.TenSP LIKE ? OR s.MaHang LIKE ?' : '') . ' ORDER BY s.MaHang',
    $like ? [$like, $like] : []
);

admin_begin('Kiểm kê và điều chỉnh tồn', 'kiem-ke', 'Nhập số lượng đếm thực tế. Nếu lệch, nhập lý do để lập phiếu điều chỉnh. Dòng để trống sẽ không được kiểm.');
?>
<form class="table-toolbar mb-3" method="get">
    <div class="input-group" style="max-width:340px"><span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Tìm theo tên hoặc mã hàng" aria-label="Tìm sản phẩm kiểm kê"></div>
    <button class="btn btn-ink" type="submit">Tìm</button>
    <?php if ($q !== ''): ?><a class="btn btn-outline-ink" href="<?= e(aurl('kho/kiem-ke.php')) ?>">Xóa tìm</a><?php endif; ?>
</form>
<form method="post">
    <?= csrf_field() ?>
    <section class="card-box mb-3">
        <div class="table-responsive"><table class="table align-middle mb-0 data-table" id="countTable">
            <thead><tr><th>Mã hàng</th><th>Sản phẩm</th><th class="text-end">Tồn hệ thống</th><th style="width:150px">Đếm thực tế</th><th class="text-end">Chênh lệch</th><th>Lý do</th></tr></thead>
            <tbody>
            <?php foreach ($stock as $s): $p = $posted[(int) $s['MaSP']] ?? ['tt' => '', 'ly' => '']; ?>
                <tr data-ht="<?= (int) $s['SoLuongTon'] ?>">
                    <td><?= e($s['MaHang']) ?><input type="hidden" name="sp[]" value="<?= (int) $s['MaSP'] ?>"><input type="hidden" name="ht[]" value="<?= (int) $s['SoLuongTon'] ?>"></td>
                    <td><?= e($s['TenSP']) ?></td>
                    <td class="text-end"><?= (int) $s['SoLuongTon'] ?></td>
                    <td><input class="form-control cnt" type="number" min="0" step="1" name="tt[]" value="<?= e($p['tt']) ?>" aria-label="Đếm thực tế của <?= e($s['MaHang']) ?>"></td>
                    <td class="text-end diff">–</td>
                    <td><input class="form-control reason" type="text" maxlength="255" name="ly[]" value="<?= e($p['ly']) ?>" placeholder="Bắt buộc nếu lệch" aria-label="Lý do lệch của <?= e($s['MaHang']) ?>"></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$stock): ?><tr><td colspan="6" class="text-center text-muted py-4">Không có sản phẩm nào.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
    <?php if ($stock): ?><div class="text-end mb-4"><button class="btn btn-ink" type="submit" onclick="return confirm('Lưu phiếu kiểm kê? Các sản phẩm lệch sẽ được điều chỉnh tồn theo số đếm.');">Lưu phiếu điều chỉnh</button></div><?php endif; ?>
</form>
<?php $recent = admin_stocktakes(); if ($recent): ?>
<section class="card-box"><div class="head"><h2>Phiếu kiểm kê gần đây</h2></div>
    <?php
    $rows = [];
    foreach ($recent as $k) {
        $rows[] = ['KK' . str_pad((string) $k['MaKK'], 5, '0', STR_PAD_LEFT), date('d/m/Y H:i', strtotime($k['NgayKK'])), $k['HoTen'], (int) $k['SoSP'], (int) $k['SoLech']];
    }
    data_table(['Mã phiếu', 'Ngày kiểm', 'Người lập', 'Số SP đã kiểm', 'Số SP lệch'], $rows, ['right' => [3, 4]]);
    ?>
</section>
<?php endif; ?>
<script>
(function () {
  document.querySelectorAll('#countTable tbody tr[data-ht]').forEach(function (tr) {
    var ht = parseInt(tr.dataset.ht, 10), cnt = tr.querySelector('.cnt'), diff = tr.querySelector('.diff'), reason = tr.querySelector('.reason');
    function upd() {
      if (cnt.value === '') { diff.textContent = '–'; diff.className = 'text-end diff'; reason.required = false; return; }
      var d = parseInt(cnt.value, 10) - ht;
      diff.textContent = d === 0 ? '0' : (d > 0 ? '+' + d : '−' + Math.abs(d));
      diff.className = 'text-end diff' + (d !== 0 ? ' text-danger fw-semibold' : '');
      reason.required = d !== 0;
    }
    cnt.addEventListener('input', upd); upd();
  });
})();
</script>
<?php admin_end(); ?>
