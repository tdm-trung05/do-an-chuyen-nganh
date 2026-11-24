<?php
/**
 * Trang kiểm tra kết nối CSDL: http://localhost/thiet-bi-dien/kiem-tra-db.php
 * Chỉ dùng khi phát triển, nên xóa file này trước khi nộp/triển khai.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$error = null;
$info = $tables = $products = [];
try {
    $info['Máy chủ']   = DB_HOST;
    $info['CSDL']      = db_val('SELECT DATABASE()');
    $info['MySQL']     = db_val('SELECT VERSION()');
    $info['Số bảng']   = db_val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [DB_NAME]);
    $info['Số view']   = db_val("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ?", [DB_NAME]);

    foreach (['VAITRO', 'NGUOIDUNG', 'DANHMUC', 'DONGSANPHAM', 'SANPHAM', 'DONHANG', 'BAOGIA', 'TINTUC'] as $t) {
        $tables[$t] = db_val("SELECT COUNT(*) FROM `$t`");   // tên bảng lấy từ danh sách cố định ở trên
    }

    $products = db_all(
        'SELECT sp.MaSP, sp.MaHang, sp.TenSP, g.TenDong, sp.GiaNiemYet, sp.LienHeBaoGia
           FROM SANPHAM sp LEFT JOIN DONGSANPHAM g ON g.MaDong = sp.MaDong
          ORDER BY sp.MaSP LIMIT 5'
    );
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
?><!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Kiểm tra kết nối CSDL</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;max-width:760px;margin:2rem auto;padding:0 1rem;color:#222}
    .ok{color:#0a7d2c}.bad{color:#c0392b}
    table{border-collapse:collapse;width:100%;margin:.5rem 0 1.5rem}
    td,th{border:1px solid #ddd;padding:.4rem .6rem;text-align:left}
    th{background:#f5f5f5}
  </style>
</head>
<body>
<h1>Kiểm tra kết nối CSDL</h1>
<?php if ($error): ?>
  <p class="bad"><strong>Kết nối thất bại.</strong></p>
  <pre><?= e($error) ?></pre>
  <p>Kiểm tra: MySQL đã bật (WampServer xanh) · đã import <code>database/schema.sql</code> · <code>DB_NAME / DB_USER / DB_PASS</code> trong <code>config/config.php</code>.</p>
<?php else: ?>
  <p class="ok"><strong>Kết nối thành công.</strong></p>
  <table>
    <?php foreach ($info as $k => $v): ?><tr><th><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?>
  </table>

  <h2>Số dòng trong một số bảng</h2>
  <table>
    <tr><th>Bảng</th><th>Số dòng</th></tr>
    <?php foreach ($tables as $k => $v): ?><tr><td><?= e($k) ?></td><td><?= e($v) ?></td></tr><?php endforeach; ?>
  </table>

  <h2>5 sản phẩm đầu tiên</h2>
  <table>
    <tr><th>Mã hàng</th><th>Tên</th><th>Dòng</th><th>Giá niêm yết</th></tr>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= e($p['MaHang']) ?></td>
        <td><?= e($p['TenSP']) ?></td>
        <td><?= e((string) $p['TenDong']) ?></td>
        <td><?= $p['GiaNiemYet'] === null ? 'Liên hệ báo giá' : e(money($p['GiaNiemYet'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</body>
</html>
