<?php
/** MH13 / UC14 – Lập phiếu nhập: chọn nhà cung cấp, thêm sản phẩm, số lượng, giá nhập; lưu xong cộng tồn kho. */
require_once __DIR__ . '/../../includes/bootstrap.php';
$staff = admin_guard('phieu-nhap');

$suppliers = admin_suppliers();
$products  = admin_products_pick();
$old = ['ncc' => 0, 'ngay' => date('Y-m-d'), 'lines' => []];

if (is_post()) {
    $old['ncc']   = (int) ($_POST['ncc'] ?? 0);
    $old['ngay']  = (string) ($_POST['ngay'] ?? '');
    $lines = [];
    foreach ((array) ($_POST['sp'] ?? []) as $i => $sp) {
        $sl  = is_array($_POST['sl'] ?? null)  ? ($_POST['sl'][$i] ?? '')  : '';
        $gia = is_array($_POST['gia'] ?? null) ? ($_POST['gia'][$i] ?? '') : '';
        $lines[] = [
            'sp'  => is_scalar($sp)  ? $sp  : '',
            'sl'  => is_scalar($sl)  ? $sl  : '',
            'gia' => is_scalar($gia) ? $gia : '',
        ];
    }
    $old['lines'] = $lines;
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $res = admin_purchase_create((int) $staff['MaND'], $old['ncc'], $old['ngay'], $lines);
        if ($res['ok']) {
            flash_set('success', $res['msg']);
            redirect(aurl('kho/phieu-nhap.php?xem=' . $res['id']));
        }
        flash_set('danger', $res['msg']);
    }
}
if (!$old['lines']) {
    $old['lines'] = [['sp' => '', 'sl' => '1', 'gia' => '0']];
}

admin_begin('Lập phiếu nhập kho', 'phieu-nhap', 'Số lượng phải lớn hơn 0 và giá nhập không âm.');
$opts = '<option value="">— Chọn sản phẩm —</option>';
foreach ($products as $p) {
    $opts .= '<option value="' . (int) $p['MaSP'] . '">' . e($p['MaHang'] . ' – ' . $p['TenSP'] . ' (tồn ' . $p['Ton'] . ')') . '</option>';
}
?>
<form method="post" id="importForm">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <section class="card-box"><div class="head"><h2>Sản phẩm nhập</h2><button class="btn btn-sm btn-outline-ink" type="button" id="addRow">+ Thêm dòng</button></div>
                <div class="table-responsive"><table class="table align-middle mb-0 data-table">
                    <thead><tr><th>Sản phẩm (mã hàng)</th><th style="width:120px">Số lượng</th><th style="width:160px">Giá nhập (₫)</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                    <tbody id="lines">
                    <?php foreach ($old['lines'] as $l): ?>
                        <tr>
                            <td><select class="form-select" name="sp[]" aria-label="Sản phẩm"><?= str_replace('value="' . (int) $l['sp'] . '"', 'value="' . (int) $l['sp'] . '" selected', $opts) ?></select></td>
                            <td><input class="form-control" type="number" min="1" step="1" name="sl[]" value="<?= e($l['sl']) ?>" aria-label="Số lượng"></td>
                            <td><input class="form-control" type="number" min="0" step="1" name="gia[]" value="<?= e($l['gia']) ?>" aria-label="Giá nhập"></td>
                            <td class="text-end line-total">0 ₫</td>
                            <td class="text-end"><button class="btn btn-icon del-row" type="button" title="Xóa dòng"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <div class="card-foot text-end">Tổng tiền: <strong class="price" id="grandTotal">0 ₫</strong></div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card-box mb-3"><div class="head"><h2>Thông tin phiếu</h2></div><div class="body">
                <div class="mb-3"><label class="form-label" for="ncc">Nhà cung cấp</label>
                    <select id="ncc" name="ncc" class="form-select" required>
                        <option value="">— Chọn nhà cung cấp —</option>
                        <?php foreach ($suppliers as $s): ?><option value="<?= (int) $s['MaNCC'] ?>"<?= (int) $s['MaNCC'] === $old['ncc'] ? ' selected' : '' ?>><?= e($s['TenNCC']) ?></option><?php endforeach; ?>
                    </select>
                    <?php if (!$suppliers): ?><div class="form-text text-danger">Chưa có nhà cung cấp. <?php if (($staff['role'] ?? '') === 'QT'): ?><a href="<?= e(aurl('kho/nha-cung-cap.php')) ?>">Thêm nhà cung cấp</a> trước.<?php else: ?>Hãy nhờ quản trị viên thêm nhà cung cấp trước.<?php endif; ?></div><?php endif; ?>
                </div>
                <div class="mb-0"><label class="form-label" for="ng">Ngày nhập</label><input id="ng" name="ngay" class="form-control" type="date" max="<?= date('Y-m-d') ?>" value="<?= e($old['ngay']) ?>" required></div>
            </div></section>
            <div class="d-grid gap-2"><button class="btn btn-ink" type="submit">Lưu phiếu và cộng tồn</button><a class="btn btn-outline-ink" href="<?= aurl('kho/phieu-nhap.php') ?>">Hủy</a></div>
        </div>
    </div>
</form>
<template id="rowTpl"><tr>
    <td><select class="form-select" name="sp[]" aria-label="Sản phẩm"><?= $opts ?></select></td>
    <td><input class="form-control" type="number" min="1" step="1" name="sl[]" value="1" aria-label="Số lượng"></td>
    <td><input class="form-control" type="number" min="0" step="1" name="gia[]" value="0" aria-label="Giá nhập"></td>
    <td class="text-end line-total">0 ₫</td>
    <td class="text-end"><button class="btn btn-icon del-row" type="button" title="Xóa dòng"><i class="bi bi-trash"></i></button></td>
</tr></template>
<script>
(function () {
  var body = document.getElementById('lines'), tpl = document.getElementById('rowTpl');
  function fmt(n) { return n.toLocaleString('vi-VN') + ' ₫'; }
  function recalc() {
    var total = 0;
    body.querySelectorAll('tr').forEach(function (tr) {
      var sl = parseInt(tr.querySelector('[name="sl[]"]').value, 10) || 0;
      var gia = parseInt(tr.querySelector('[name="gia[]"]').value, 10) || 0;
      var t = sl > 0 && gia >= 0 ? sl * gia : 0;
      tr.querySelector('.line-total').textContent = fmt(t);
      total += t;
    });
    document.getElementById('grandTotal').textContent = fmt(total);
  }
  document.getElementById('addRow').addEventListener('click', function () {
    body.appendChild(tpl.content.firstElementChild.cloneNode(true));
    recalc();
  });
  body.addEventListener('input', recalc);
  body.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.del-row');
    if (!btn) return;
    if (body.querySelectorAll('tr').length > 1) { btn.closest('tr').remove(); } else {
      var tr = btn.closest('tr'); tr.querySelector('select').value = ''; tr.querySelector('[name="sl[]"]').value = 1; tr.querySelector('[name="gia[]"]').value = 0;
    }
    recalc();
  });
  recalc();
})();
</script>
<?php admin_end(); ?>
