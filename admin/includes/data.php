<?php
/**
 * Lớp dữ liệu khu quản trị (giai đoạn 3): đơn hàng, kho, bảng điều khiển, báo biểu.
 * Mọi truy vấn dùng prepared statement qua includes/db.php; các thao tác nhiều bước chạy trong một giao dịch.
 * Quy tắc chuyển trạng thái đơn khớp với trigger trg_DONHANG_ChuyenTrangThai và thủ tục sp_HuyDon trong schema.sql.
 */

/* ============================== Tiện ích chung ============================== */

/** Đọc ngày dạng Y-m-d từ chuỗi; sai định dạng thì trả về $default. */
function admin_date($raw, string $default = ''): string
{
    $raw = trim((string) $raw);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return $raw;
    }
    return $default;
}

/** Số nguyên không âm từ chuỗi; không hợp lệ trả về null. */
function admin_int($raw): ?int
{
    $raw = trim((string) $raw);
    return preg_match('/^\d{1,9}$/', $raw) ? (int) $raw : null;
}

/** Trang hiện tại và các giá trị phân trang. */
function admin_paging(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

/** Form lọc nhỏ dùng chung: ô tìm + (tùy chọn) lọc trạng thái, gửi bằng GET. */
function admin_filter_form(string $placeholder, string $q, array $statuses = [], string $current = '', string $statusName = 'tt'): void
{
    ?>
    <form class="table-toolbar" method="get">
        <div class="input-group" style="max-width:340px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="<?= e($placeholder) ?>" aria-label="<?= e($placeholder) ?>">
        </div>
        <?php if ($statuses): ?>
        <select name="<?= e($statusName) ?>" class="form-select w-auto" aria-label="Lọc theo trạng thái">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($statuses as $s): ?><option<?= $s === $current ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button class="btn btn-ink" type="submit">Lọc</button>
        <?php if ($q !== '' || $current !== ''): ?><a class="btn btn-outline-ink" href="<?= e(strtok(request_uri(), '?')) ?>">Xóa lọc</a><?php endif; ?>
    </form>
    <?php
}

/* ================================== Đơn hàng ================================ */

/** Các trạng thái mà đơn đang ở $status được phép chuyển tới (khớp trigger). */
function admin_order_next(string $status): array
{
    $map = [
        'Chờ thanh toán' => ['Chờ xác nhận', 'Đã hủy'],
        'Chờ xác nhận'   => ['Đã xác nhận', 'Đã hủy'],
        'Đã xác nhận'    => ['Đang giao', 'Đã hủy'],
        'Đang giao'      => ['Hoàn tất'],
    ];
    return $map[$status] ?? [];
}

/** Tự hủy đơn chuyển khoản quá hạn 24 giờ (thủ tục sp_HuyDonQuaHan); lỗi thì bỏ qua. */
function admin_auto_cancel_overdue(): void
{
    try {
        $st = db()->prepare('CALL sp_HuyDonQuaHan()');
        $st->execute();
        $st->closeCursor();
    } catch (PDOException $e) {
        error_log('[DB] sp_HuyDonQuaHan: ' . $e->getMessage());
    }
}

/** Số đơn theo từng trạng thái (cho các nút lọc). */
function admin_order_counts(): array
{
    $out = array_fill_keys(ORDER_STATUSES, 0);
    foreach (db_all('SELECT TrangThai, COUNT(*) AS n FROM DONHANG GROUP BY TrangThai') as $r) {
        $out[$r['TrangThai']] = (int) $r['n'];
    }
    return $out;
}

/** Danh sách đơn có tìm kiếm (mã đơn, tên người nhận, email, số điện thoại), lọc trạng thái, phân trang. */
function admin_orders(string $q, string $status, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($status !== '' && in_array($status, ORDER_STATUSES, true)) {
        $where   .= ' AND d.TrangThai = ?';
        $params[] = $status;
    }
    $q = trim($q);
    if ($q !== '') {
        $like   = like_escape($q);
        $where .= ' AND (d.NguoiNhan LIKE ? OR u.HoTen LIKE ? OR u.Email LIKE ? OR d.SoDienThoaiNhan LIKE ?';
        array_push($params, $like, $like, $like, $like);
        $id = parse_order_id($q);
        if ($id > 0 && preg_match('/^(DH)?\s*\d+$/i', $q)) {
            $where   .= ' OR d.MaDH = ?';
            $params[] = $id;
        }
        $where .= ')';
    }
    $total = (int) db_val("SELECT COUNT(*) FROM DONHANG d JOIN NGUOIDUNG u ON u.MaND = d.MaND WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT d.MaDH, d.NgayDat, d.TrangThai, d.TongTien, d.NguoiNhan, u.HoTen,
                (SELECT COALESCE(SUM(c.SoLuong), 0) FROM CT_DONHANG c WHERE c.MaDH = d.MaDH) AS SoSP,
                (SELECT t.PhuongThuc FROM THANHTOAN t WHERE t.MaDH = d.MaDH ORDER BY t.MaTT LIMIT 1) AS PhuongThuc,
                (SELECT t.TrangThai FROM THANHTOAN t WHERE t.MaDH = d.MaDH ORDER BY t.MaTT LIMIT 1) AS TTThanhToan
           FROM DONHANG d JOIN NGUOIDUNG u ON u.MaND = d.MaND
          WHERE $where ORDER BY d.NgayDat DESC, d.MaDH DESC
          LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Đơn hàng đầy đủ cho nhân viên (khách, sản phẩm, thanh toán, lịch sử kèm tên người thao tác). */
function admin_order(int $id): ?array
{
    $o = db_one(
        'SELECT d.*, u.HoTen, u.Email, u.SoDienThoai AS SdtTaiKhoan
           FROM DONHANG d JOIN NGUOIDUNG u ON u.MaND = d.MaND WHERE d.MaDH = ?',
        [$id]
    );
    if ($o === null) {
        return null;
    }
    $o['items'] = db_all(
        'SELECT c.MaSP, c.SoLuong, c.DonGia, s.MaHang, s.TenSP
           FROM CT_DONHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaDH = ? ORDER BY c.MaSP',
        [$id]
    );
    $o['payment'] = db_one('SELECT * FROM THANHTOAN WHERE MaDH = ? ORDER BY MaTT LIMIT 1', [$id]);
    $o['history'] = db_all(
        'SELECT l.TrangThaiCu, l.TrangThaiMoi, l.ThoiGian, l.GhiChu, u.HoTen
           FROM LICHSU_DONHANG l LEFT JOIN NGUOIDUNG u ON u.MaND = l.MaND
          WHERE l.MaDH = ? ORDER BY l.ThoiGian, l.MaLS',
        [$id]
    );
    $o['hang'] = 0;
    foreach ($o['items'] as $it) {
        $o['hang'] += (int) $it['SoLuong'] * (int) $it['DonGia'];
    }
    return $o;
}

/** Ghi một dòng lịch sử đơn hàng. */
function admin_order_log(int $orderId, int $staffId, ?string $old, string $new, string $note): void
{
    db_exec(
        'INSERT INTO LICHSU_DONHANG (MaDH, MaND, TrangThaiCu, TrangThaiMoi, ThoiGian, GhiChu) VALUES (?, ?, ?, ?, NOW(), ?)',
        [$orderId, $staffId, $old, $new, $note === '' ? null : mb_substr($note, 0, 250, 'UTF-8')]
    );
}

/**
 * Đổi trạng thái đơn.
 * - "Đã hủy": gọi sp_HuyDon (cộng lại tồn kho, ghi lịch sử).
 * - "Chờ thanh toán" → "Chờ xác nhận": coi như đã nhận chuyển khoản, đánh dấu thanh toán.
 * - "Hoàn tất": đơn thanh toán khi nhận hàng được đánh dấu đã thanh toán.
 * @return array{ok:bool,msg:string}
 */
function admin_order_set_status(int $id, int $staffId, string $new, string $note = ''): array
{
    $cur = db_val('SELECT TrangThai FROM DONHANG WHERE MaDH = ?', [$id]);
    if ($cur === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy đơn hàng.'];
    }
    if (!in_array($new, admin_order_next($cur), true)) {
        return ['ok' => false, 'msg' => 'Không thể chuyển đơn từ "' . $cur . '" sang "' . $new . '".'];
    }
    $note = trim($note);
    if ($new === 'Đã hủy') {
        if ($note === '') {
            return ['ok' => false, 'msg' => 'Hãy nhập lý do hủy đơn.'];
        }
        try {
            $st = db()->prepare('CALL sp_HuyDon(?, ?, ?)');
            $st->execute([$id, $staffId, mb_substr($note, 0, 250, 'UTF-8')]);
            $st->closeCursor();
        } catch (PDOException $e) {
            return ['ok' => false, 'msg' => db_error_message($e)];
        }
        return ['ok' => true, 'msg' => 'Đã hủy ' . order_code($id) . ' và cộng lại tồn kho.'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $cur = db_val('SELECT TrangThai FROM DONHANG WHERE MaDH = ? FOR UPDATE', [$id]);   // khóa dòng, chống hai người đổi cùng lúc
        if (!in_array($new, admin_order_next((string) $cur), true)) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Đơn vừa được người khác cập nhật (hiện là "' . $cur . '"). Hãy tải lại trang.'];
        }
        db_exec('UPDATE DONHANG SET TrangThai = ?, HanThanhToan = IF(?, NULL, HanThanhToan) WHERE MaDH = ?',
            [$new, $cur === 'Chờ thanh toán' ? 1 : 0, $id]);
        if ($cur === 'Chờ thanh toán' && $new === 'Chờ xác nhận') {
            db_exec("UPDATE THANHTOAN SET TrangThai = 'Đã thanh toán', ThoiGian = NOW() WHERE MaDH = ? AND TrangThai = 'Chưa thanh toán'", [$id]);
            $note = $note !== '' ? $note : 'Đã nhận tiền chuyển khoản';
        }
        if ($new === 'Hoàn tất') {
            db_exec("UPDATE THANHTOAN SET TrangThai = 'Đã thanh toán', ThoiGian = NOW() WHERE MaDH = ? AND TrangThai = 'Chưa thanh toán'", [$id]);
        }
        admin_order_log($id, $staffId, $cur, $new, $note);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => db_error_message($e)];
    }
    return ['ok' => true, 'msg' => order_code($id) . ' chuyển sang "' . $new . '".'];
}

/**
 * Cập nhật phí giao hàng (mặc định lấy từ SHIPPING_FEE khi đặt hàng, nhân viên chỉnh lại nếu cần). Tính lại tổng tiền và số tiền cần thanh toán.
 * Chỉ sửa được với đơn thanh toán khi nhận hàng và đang \"Chờ xác nhận\" (theo UC12, báo cáo 2.3.1.2).
 * @return array{ok:bool,msg:string}
 */
function admin_order_set_fee(int $id, int $staffId, int $fee): array
{
    if ($fee < 0 || $fee > 100000000) {
        return ['ok' => false, 'msg' => 'Phí giao hàng không hợp lệ.'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $o = db_one('SELECT TrangThai, PhiGiao FROM DONHANG WHERE MaDH = ? FOR UPDATE', [$id]);
        if ($o === null) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Không tìm thấy đơn hàng.'];
        }
        if ($o['TrangThai'] !== 'Chờ xác nhận') {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Chỉ sửa được phí giao khi đơn đang "Chờ xác nhận" (hiện là "' . $o['TrangThai'] . '").'];
        }
        $pay = db_one('SELECT PhuongThuc, TrangThai FROM THANHTOAN WHERE MaDH = ? ORDER BY MaTT LIMIT 1 FOR UPDATE', [$id]);
        if (!$pay || $pay['PhuongThuc'] !== 'Thanh toán khi nhận hàng') {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Đơn chuyển khoản đã chốt số tiền với khách nên không đổi phí giao.'];
        }
        if ($pay && $pay['TrangThai'] === 'Đã thanh toán') {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Khoản thanh toán đã được xác nhận nên không đổi phí giao được.'];
        }
        if ((int) $o['PhiGiao'] === $fee) {
            $pdo->rollBack();
            return ['ok' => true, 'msg' => 'Phí giao hàng không thay đổi.'];
        }
        $hang = (int) db_val('SELECT COALESCE(SUM(SoLuong * DonGia), 0) FROM CT_DONHANG WHERE MaDH = ?', [$id]);
        db_exec('UPDATE DONHANG SET PhiGiao = ?, TongTien = ? WHERE MaDH = ?', [$fee, $hang + $fee, $id]);
        db_exec('UPDATE THANHTOAN SET SoTien = ? WHERE MaDH = ?', [$hang + $fee, $id]);
        admin_order_log($id, $staffId, $o['TrangThai'], $o['TrangThai'], 'Cập nhật phí giao hàng: ' . money($o['PhiGiao']) . ' → ' . money($fee));
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => db_error_message($e)];
    }
    return ['ok' => true, 'msg' => 'Đã cập nhật phí giao hàng, tổng tiền mới ' . money($hang + $fee) . '.'];
}

/* =================================== Kho hàng =============================== */

/** Tình trạng tồn kho: hết hàng, sắp hết (dưới ngưỡng cảnh báo), còn hàng. */
function admin_stock_label(int $qty, int $threshold): string
{
    return $qty <= 0 ? 'Hết hàng' : ($qty < $threshold ? 'Sắp hết' : 'Còn hàng');
}

/** Số liệu tổng hợp tồn kho. */
function admin_stock_summary(): array
{
    $r = db_one(
        'SELECT COUNT(*) AS tong,
                COALESCE(SUM(t.SoLuongTon > 0 AND t.SoLuongTon < t.NguongCanhBao), 0) AS saphet,
                COALESCE(SUM(t.SoLuongTon = 0), 0) AS hethang
           FROM SANPHAM s JOIN TONKHO t ON t.MaSP = s.MaSP'
    );
    return ['total' => (int) $r['tong'], 'low' => (int) $r['saphet'], 'out' => (int) $r['hethang']];
}

/**
 * Điều kiện sản phẩm đáng cảnh báo tồn kho: đang hiển thị, đang kinh doanh, bán trực tiếp (không theo báo giá),
 * và nếu tồn bằng 0 thì phải từng nhập kho (sản phẩm mới tạo chưa nhập hàng không tính là "hết hàng").
 * Cần có bí danh s (SANPHAM) và t (TONKHO).
 */
const STOCK_ALERT_SQL = "s.TrangThai = 1 AND s.TinhTrangKD = 'Đang kinh doanh' AND s.LienHeBaoGia = 0
    AND ((t.SoLuongTon > 0 AND t.SoLuongTon < t.NguongCanhBao)
         OR (t.SoLuongTon = 0 AND EXISTS (SELECT 1 FROM CT_PHIEUNHAP p WHERE p.MaSP = s.MaSP)))";

/** Số sản phẩm cần chú ý (hết hàng hoặc dưới ngưỡng), dùng cho chuông thông báo. */
function admin_stock_alert_count(): int
{
    return (int) db_val('SELECT COUNT(*) FROM SANPHAM s JOIN TONKHO t ON t.MaSP = s.MaSP WHERE ' . STOCK_ALERT_SQL);
}

/** Danh sách tồn kho có tìm kiếm, lọc tình trạng, phân trang. */
function admin_stock_list(string $q, string $status, int $page, int $perPage = 20): array
{
    $where  = '1=1';
    $params = [];
    $q = trim($q);
    if ($q !== '') {
        $like     = like_escape($q);
        $where   .= ' AND (s.TenSP LIKE ? OR s.MaHang LIKE ?)';
        array_push($params, $like, $like);
    }
    if ($status === 'Cần chú ý') {   // cùng điều kiện với số trên chuông thông báo
        $where .= ' AND ' . STOCK_ALERT_SQL;
    } elseif ($status === 'Hết hàng') {
        $where .= ' AND t.SoLuongTon = 0';
    } elseif ($status === 'Sắp hết') {
        $where .= ' AND t.SoLuongTon > 0 AND t.SoLuongTon < t.NguongCanhBao';
    } elseif ($status === 'Còn hàng') {
        $where .= ' AND t.SoLuongTon >= t.NguongCanhBao AND t.SoLuongTon > 0';
    }
    $from  = 'FROM SANPHAM s JOIN TONKHO t ON t.MaSP = s.MaSP';
    $total = (int) db_val("SELECT COUNT(*) $from WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT s.MaSP, s.MaHang, s.TenSP, t.SoLuongTon, t.NguongCanhBao $from WHERE $where
          ORDER BY (t.SoLuongTon = 0) DESC, (t.SoLuongTon < t.NguongCanhBao) DESC, s.MaHang
          LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Sửa ngưỡng cảnh báo của một sản phẩm. */
function admin_stock_set_threshold(int $productId, int $threshold): array
{
    if ($threshold < 0 || $threshold > 100000) {
        return ['ok' => false, 'msg' => 'Ngưỡng cảnh báo phải là số không âm.'];
    }
    if (db_val('SELECT 1 FROM TONKHO WHERE MaSP = ?', [$productId]) === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy sản phẩm trong kho.'];
    }
    db_exec('UPDATE TONKHO SET NguongCanhBao = ? WHERE MaSP = ?', [$threshold, $productId]);
    return ['ok' => true, 'msg' => 'Đã lưu ngưỡng cảnh báo.'];
}

/** Sản phẩm để chọn trong phiếu nhập và kiểm kê. */
function admin_products_pick(): array
{
    return db_all('SELECT s.MaSP, s.MaHang, s.TenSP, COALESCE(t.SoLuongTon, 0) AS Ton FROM SANPHAM s LEFT JOIN TONKHO t ON t.MaSP = s.MaSP ORDER BY s.MaHang');
}

/** Nhà cung cấp kèm số phiếu đã nhập. */
function admin_suppliers(): array
{
    return db_all('SELECT n.*, (SELECT COUNT(*) FROM PHIEUNHAP p WHERE p.MaNCC = n.MaNCC) AS SoPhieu FROM NHACUNGCAP n ORDER BY n.TenNCC');
}

/** Thêm hoặc sửa nhà cung cấp ($id = 0 là thêm mới). */
function admin_supplier_save(int $id, string $name, string $phone, string $address): array
{
    $name = trim($name);
    $phone = trim($phone);
    $address = trim($address);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 200) {
        return ['ok' => false, 'msg' => 'Tên nhà cung cấp là bắt buộc (tối đa 200 ký tự).'];
    }
    if ($phone !== '' && !preg_match('/^[0-9 .+()-]{6,15}$/', $phone)) {
        return ['ok' => false, 'msg' => 'Số điện thoại không hợp lệ.'];
    }
    if (mb_strlen($address, 'UTF-8') > 255) {
        return ['ok' => false, 'msg' => 'Địa chỉ tối đa 255 ký tự.'];
    }
    if ($id > 0) {
        if (db_val('SELECT 1 FROM NHACUNGCAP WHERE MaNCC = ?', [$id]) === null) {
            return ['ok' => false, 'msg' => 'Không tìm thấy nhà cung cấp.'];
        }
        db_exec('UPDATE NHACUNGCAP SET TenNCC = ?, SoDienThoai = ?, DiaChi = ? WHERE MaNCC = ?',
            [$name, $phone === '' ? null : $phone, $address === '' ? null : $address, $id]);
        return ['ok' => true, 'msg' => 'Đã cập nhật nhà cung cấp.'];
    }
    db_exec('INSERT INTO NHACUNGCAP (TenNCC, SoDienThoai, DiaChi) VALUES (?, ?, ?)',
        [$name, $phone === '' ? null : $phone, $address === '' ? null : $address]);
    return ['ok' => true, 'msg' => 'Đã thêm nhà cung cấp.'];
}

/** Xóa nhà cung cấp chưa có phiếu nhập nào. */
function admin_supplier_delete(int $id): array
{
    if ((int) db_val('SELECT COUNT(*) FROM PHIEUNHAP WHERE MaNCC = ?', [$id]) > 0) {
        return ['ok' => false, 'msg' => 'Nhà cung cấp này đã có phiếu nhập nên không xóa được.'];
    }
    $n = db_exec('DELETE FROM NHACUNGCAP WHERE MaNCC = ?', [$id]);
    return $n > 0 ? ['ok' => true, 'msg' => 'Đã xóa nhà cung cấp.'] : ['ok' => false, 'msg' => 'Không tìm thấy nhà cung cấp.'];
}

/**
 * Lập phiếu nhập và cộng tồn kho trong MỘT giao dịch (UC14).
 * @param array $lines danh sách ['sp' => mã SP, 'sl' => số lượng, 'gia' => giá nhập]; dòng trùng sản phẩm được gộp.
 * @return array{ok:bool,msg:string,id:int}
 */
function admin_purchase_create(int $staffId, int $supplierId, string $date, array $lines): array
{
    $fail = static fn(string $m): array => ['ok' => false, 'msg' => $m, 'id' => 0];
    if (db_val('SELECT 1 FROM NHACUNGCAP WHERE MaNCC = ?', [$supplierId]) === null) {
        return $fail('Hãy chọn nhà cung cấp.');
    }
    $date = admin_date($date);
    if ($date === '' || $date > date('Y-m-d')) {
        return $fail('Ngày nhập không hợp lệ (không được ở tương lai).');
    }
    $merged = [];
    foreach ($lines as $l) {
        $sp = admin_int($l['sp'] ?? '');
        if ($sp === null || $sp === 0) {
            continue;   // dòng trống
        }
        $sl  = admin_int($l['sl'] ?? '');
        $gia = admin_int($l['gia'] ?? '');
        if ($sl === null || $sl < 1) {
            return $fail('Số lượng nhập phải là số nguyên lớn hơn 0.');
        }
        if ($gia === null) {
            return $fail('Giá nhập phải là số không âm.');
        }
        if (!isset($merged[$sp])) {
            $merged[$sp] = ['sl' => 0, 'gia' => $gia, 'tien' => 0];
        }
        $merged[$sp]['sl']  += $sl;
        $merged[$sp]['tien'] += $sl * $gia;
        $merged[$sp]['gia']  = (int) round($merged[$sp]['tien'] / $merged[$sp]['sl']);   // dòng trùng: lấy giá bình quân
    }
    if (!$merged) {
        return $fail('Phiếu nhập cần ít nhất một sản phẩm.');
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $ids = array_keys($merged);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        if ((int) db_val("SELECT COUNT(*) FROM SANPHAM WHERE MaSP IN ($in)", $ids) !== count($ids)) {
            $pdo->rollBack();
            return $fail('Có sản phẩm không tồn tại trong danh mục.');
        }
        $tong = array_sum(array_column($merged, 'tien'));
        $when = $date === date('Y-m-d') ? date('Y-m-d H:i:s') : $date . ' 08:00:00';
        db_exec('INSERT INTO PHIEUNHAP (MaNCC, MaND, NgayNhap, TongTien) VALUES (?, ?, ?, ?)', [$supplierId, $staffId, $when, $tong]);
        $pn = (int) $pdo->lastInsertId();
        foreach ($merged as $sp => $l) {
            // Giá nhập lưu theo đơn giá: dòng gộp có tổng tiền chính xác khi đơn giá chia hết; làm tròn nếu không.
            db_exec('INSERT INTO CT_PHIEUNHAP (MaPN, MaSP, SoLuong, GiaNhap) VALUES (?, ?, ?, ?)', [$pn, $sp, $l['sl'], $l['gia']]);
            db_exec('INSERT INTO TONKHO (MaSP, SoLuongTon, NguongCanhBao) VALUES (?, ?, 10)
                     ON DUPLICATE KEY UPDATE SoLuongTon = SoLuongTon + VALUES(SoLuongTon)', [$sp, $l['sl']]);
        }
        $tongLuu = (int) db_val('SELECT SUM(SoLuong * GiaNhap) FROM CT_PHIEUNHAP WHERE MaPN = ?', [$pn]);
        db_exec('UPDATE PHIEUNHAP SET TongTien = ? WHERE MaPN = ?', [$tongLuu, $pn]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[DB] purchase: ' . $e->getMessage());
        return $fail('Không lưu được phiếu nhập do lỗi hệ thống. Vui lòng thử lại.');
    }
    return ['ok' => true, 'msg' => 'Đã lập phiếu nhập PN' . str_pad((string) $pn, 5, '0', STR_PAD_LEFT) . ' và cộng tồn kho.', 'id' => $pn];
}

/** Mã phiếu nhập hiển thị: 7 => PN00007. */
function purchase_code(int $id): string
{
    return 'PN' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

/** Danh sách phiếu nhập có tìm kiếm (mã phiếu, nhà cung cấp) và lọc kỳ. */
function admin_purchases(string $q, string $from, string $to, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    $q = trim($q);
    if ($q !== '') {
        $like   = like_escape($q);
        $where .= ' AND (n.TenNCC LIKE ?';
        $params[] = $like;
        if (preg_match('/^(PN)?\s*\d+$/i', $q)) {
            $where   .= ' OR p.MaPN = ?';
            $params[] = parse_order_id($q);
        }
        $where .= ')';
    }
    if ($from !== '') {
        $where   .= ' AND p.NgayNhap >= ?';
        $params[] = $from . ' 00:00:00';
    }
    if ($to !== '') {
        $where   .= ' AND p.NgayNhap <= ?';
        $params[] = $to . ' 23:59:59';
    }
    $base  = 'FROM PHIEUNHAP p JOIN NHACUNGCAP n ON n.MaNCC = p.MaNCC JOIN NGUOIDUNG u ON u.MaND = p.MaND';
    $total = (int) db_val("SELECT COUNT(*) $base WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT p.MaPN, p.NgayNhap, p.TongTien, n.TenNCC, u.HoTen,
                (SELECT COUNT(*) FROM CT_PHIEUNHAP c WHERE c.MaPN = p.MaPN) AS SoSP
           $base WHERE $where ORDER BY p.NgayNhap DESC, p.MaPN DESC
          LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Các dòng của một phiếu nhập. */
function admin_purchase_items(int $id): array
{
    return db_all(
        'SELECT c.MaSP, c.SoLuong, c.GiaNhap, s.MaHang, s.TenSP FROM CT_PHIEUNHAP c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaPN = ? ORDER BY s.MaHang',
        [$id]
    );
}

/**
 * Lưu phiếu kiểm kê và điều chỉnh tồn (UC15) trong một giao dịch.
 * @param array $lines mỗi phần tử: ['sp' => mã SP, 'ht' => tồn hệ thống lúc hiển thị, 'tt' => số đếm thực tế ('' = bỏ qua), 'ly' => lý do]
 * @return array{ok:bool,msg:string}
 */
function admin_stocktake_save(int $staffId, array $lines): array
{
    $todo = [];
    foreach ($lines as $l) {
        $sp = admin_int($l['sp'] ?? '');
        $tt = trim((string) ($l['tt'] ?? ''));
        if ($sp === null || $tt === '') {
            continue;   // không đếm sản phẩm này
        }
        $tt = admin_int($tt);
        $ht = admin_int($l['ht'] ?? '');
        if ($tt === null || $ht === null) {
            return ['ok' => false, 'msg' => 'Số đếm thực tế phải là số nguyên không âm.'];
        }
        $todo[$sp] = ['ht' => $ht, 'tt' => $tt, 'ly' => trim((string) ($l['ly'] ?? ''))];
    }
    if (!$todo) {
        return ['ok' => false, 'msg' => 'Chưa nhập số đếm thực tế cho sản phẩm nào.'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        db_exec('INSERT INTO PHIEUKIEMKE (MaND, NgayKK) VALUES (?, NOW())', [$staffId]);
        $kk = (int) $pdo->lastInsertId();
        $lech = 0;
        foreach ($todo as $sp => $l) {
            $row = db_one('SELECT SoLuongTon FROM TONKHO WHERE MaSP = ? FOR UPDATE', [$sp]);
            if ($row === null) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'Có sản phẩm chưa có dòng tồn kho.'];
            }
            if ((int) $row['SoLuongTon'] !== $l['ht']) {
                $pdo->rollBack();
                $ma = db_val('SELECT MaHang FROM SANPHAM WHERE MaSP = ?', [$sp]);
                return ['ok' => false, 'msg' => 'Tồn kho của ' . $ma . ' vừa thay đổi (đơn hàng hoặc phiếu nhập mới). Hãy tải lại trang rồi đếm lại.'];
            }
            if ($l['tt'] !== $l['ht'] && $l['ly'] === '') {
                $pdo->rollBack();
                $ma = db_val('SELECT MaHang FROM SANPHAM WHERE MaSP = ?', [$sp]);
                return ['ok' => false, 'msg' => 'Sản phẩm ' . $ma . ' bị lệch số lượng, hãy nhập lý do điều chỉnh.'];
            }
            db_exec('INSERT INTO CT_PHIEUKIEMKE (MaKK, MaSP, SoLuongHeThong, SoLuongThucTe, LyDo) VALUES (?, ?, ?, ?, ?)',
                [$kk, $sp, $l['ht'], $l['tt'], $l['ly'] === '' ? null : mb_substr($l['ly'], 0, 255, 'UTF-8')]);
            if ($l['tt'] !== $l['ht']) {
                db_exec('UPDATE TONKHO SET SoLuongTon = ? WHERE MaSP = ?', [$l['tt'], $sp]);
                $lech++;
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[DB] stocktake: ' . $e->getMessage());
        return ['ok' => false, 'msg' => 'Không lưu được phiếu kiểm kê do lỗi hệ thống. Vui lòng thử lại.'];
    }
    return ['ok' => true, 'msg' => 'Đã lưu phiếu kiểm kê KK' . str_pad((string) $kk, 5, '0', STR_PAD_LEFT) . ': ' . count($todo) . ' sản phẩm, ' . $lech . ' sản phẩm được điều chỉnh tồn.'];
}

/** Các phiếu kiểm kê gần đây. */
function admin_stocktakes(int $limit = 8): array
{
    return db_all(
        'SELECT k.MaKK, k.NgayKK, u.HoTen, COUNT(c.MaSP) AS SoSP, COALESCE(SUM(c.SoLuongHeThong <> c.SoLuongThucTe), 0) AS SoLech
           FROM PHIEUKIEMKE k JOIN NGUOIDUNG u ON u.MaND = k.MaND LEFT JOIN CT_PHIEUKIEMKE c ON c.MaKK = k.MaKK
          GROUP BY k.MaKK, k.NgayKK, u.HoTen ORDER BY k.NgayKK DESC, k.MaKK DESC LIMIT ' . (int) $limit
    );
}

/* ================================ Bảng điều khiển ============================ */

/** Số liệu tổng quan cho bảng điều khiển. */
function admin_dashboard_stats(): array
{
    $thisM = (float) db_val("SELECT COALESCE(SUM(TongTien), 0) FROM DONHANG WHERE TrangThai = 'Hoàn tất' AND NgayHoanTat >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $lastM = (float) db_val("SELECT COALESCE(SUM(TongTien), 0) FROM DONHANG WHERE TrangThai = 'Hoàn tất'
                              AND NgayHoanTat >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND NgayHoanTat < DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $counts = admin_order_counts();
    return [
        'revenue'      => $thisM,
        'revenue_last' => $lastM,
        'orders_wait'  => $counts['Chờ thanh toán'] + $counts['Chờ xác nhận'],
        'orders_pay'   => $counts['Chờ thanh toán'],
        'orders_ship'  => $counts['Đã xác nhận'] + $counts['Đang giao'],
        'quotes_new'   => (int) db_val("SELECT COUNT(*) FROM BAOGIA WHERE TrangThai = 'Mới'"),
        'warranty_new' => (int) db_val("SELECT COUNT(*) FROM BAOHANH WHERE TrangThai = 'Mới'"),
        'stock_alert'  => admin_stock_alert_count(),
    ];
}

/** Doanh thu theo ngày trong $days ngày gần nhất (kể cả ngày bằng 0). */
function admin_revenue_days(int $days = 7): array
{
    $map = [];
    foreach (db_all("SELECT DATE(NgayHoanTat) AS Ngay, SUM(TongTien) AS DoanhThu FROM DONHANG
                      WHERE TrangThai = 'Hoàn tất' AND NgayHoanTat >= CURDATE() - INTERVAL ? DAY GROUP BY DATE(NgayHoanTat)", [$days - 1]) as $r) {
        $map[$r['Ngay']] = (float) $r['DoanhThu'];
    }
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $out[$d] = $map[$d] ?? 0.0;
    }
    return $out;
}

/** Sản phẩm dưới ngưỡng hoặc hết hàng, nặng nhất trước. */
function admin_stock_alerts(int $limit = 6): array
{
    return db_all(
        'SELECT s.MaHang, s.TenSP, t.SoLuongTon, t.NguongCanhBao FROM SANPHAM s JOIN TONKHO t ON t.MaSP = s.MaSP
          WHERE ' . STOCK_ALERT_SQL . ' ORDER BY t.SoLuongTon, s.MaHang LIMIT ' . (int) $limit
    );
}

/** Đơn mới nhất. */
function admin_recent_orders(int $limit = 5): array
{
    return db_all(
        'SELECT d.MaDH, d.NgayDat, d.TongTien, d.TrangThai, d.NguoiNhan FROM DONHANG d ORDER BY d.NgayDat DESC, d.MaDH DESC LIMIT ' . (int) $limit
    );
}

/* ================================== Báo biểu ================================ */

/** Danh sách báo biểu: mã => [tên, mô tả, vai trò được xem, kiểu lọc]. */
function admin_reports(): array
{
    return [
        'BB01' => ['Doanh thu theo ngày, tháng', 'Tổng tiền các đơn hoàn tất theo kỳ',                'QT',    'range'],
        'BB02' => ['Sản phẩm bán chạy',          'Sản phẩm xếp theo số lượng bán',                     'QT',    'range'],
        'BB03' => ['Tồn kho và cảnh báo',        'Số tồn, sản phẩm dưới ngưỡng',                       'QT TK', 'none'],
        'BB04' => ['Phiếu nhập theo kỳ',         'Danh sách và tổng tiền nhập',                        'QT TK', 'range'],
        'BB05' => ['Đơn hàng theo trạng thái',   'Số đơn ở từng trạng thái (theo ngày đặt)',           'QT BH', 'range'],
        'BB06' => ['Bảo hành, đổi trả theo kỳ',  'Số yêu cầu và kết quả xử lý (theo ngày tạo, nhóm theo tháng)',       'QT BH', 'range'],
        'BB07' => ['Yêu cầu báo giá theo kỳ',    'Số yêu cầu, số được chấp nhận, số hết hạn (theo ngày gửi, nhóm theo tháng)', 'QT BH', 'range'],
    ];
}

/** Các báo biểu mà vai trò $role được xem. */
function admin_reports_for(string $role): array
{
    return array_filter(admin_reports(), static fn(array $r): bool => in_array($role, explode(' ', $r[2]), true));
}

/**
 * Chạy một báo biểu, trả về ['headers' => [...], 'rows' => [[...]], 'right' => [chỉ số cột số], 'note' => string].
 * Mọi số liệu lấy từ view vw_BB01…07 hoặc truy vấn tương đương có lọc kỳ.
 */
function admin_report_run(string $code, string $from, string $to, string $ky = 'ngay'): array
{
    $fromT = $from . ' 00:00:00';
    $toT   = $to . ' 23:59:59';
    switch ($code) {
        case 'BB01':
            if ($ky === 'thang') {
                $rows = db_all("SELECT DATE_FORMAT(Ngay,'%Y-%m') AS K, SUM(SoDonHoanTat) AS N, SUM(DoanhThu) AS T
                                  FROM vw_BB01_DoanhThuNgay WHERE Ngay BETWEEN ? AND ? GROUP BY K ORDER BY K", [$from, $to]);
                $label = 'Tháng';
                $fmtK  = static fn($k) => date('m/Y', strtotime($k . '-01'));
            } else {
                $rows = db_all('SELECT Ngay AS K, SoDonHoanTat AS N, DoanhThu AS T FROM vw_BB01_DoanhThuNgay WHERE Ngay BETWEEN ? AND ? ORDER BY Ngay', [$from, $to]);
                $label = 'Ngày';
                $fmtK  = static fn($k) => date('d/m/Y', strtotime($k));
            }
            $out = array_map(static fn($r) => [$fmtK($r['K']), (int) $r['N'], (float) $r['T']], $rows);
            return ['headers' => [$label, 'Số đơn hoàn tất', 'Doanh thu (₫)'], 'rows' => $out, 'right' => [1, 2], 'money' => [2],
                    'total' => ['Tổng', array_sum(array_column($out, 1)), array_sum(array_column($out, 2))]];
        case 'BB02':
            $rows = db_all(
                "SELECT RANK() OVER (ORDER BY SUM(c.SoLuong) DESC) AS Hang, s.MaHang, s.TenSP, SUM(c.SoLuong) AS SoLuongBan, SUM(c.SoLuong * c.DonGia) AS TienHang
                   FROM CT_DONHANG c JOIN DONHANG d ON d.MaDH = c.MaDH AND d.TrangThai = 'Hoàn tất' AND d.NgayHoanTat BETWEEN ? AND ?
                   JOIN SANPHAM s ON s.MaSP = c.MaSP GROUP BY s.MaSP, s.MaHang, s.TenSP ORDER BY SoLuongBan DESC, s.MaHang LIMIT 50",
                [$fromT, $toT]
            );
            $out = array_map(static fn($r) => [(int) $r['Hang'], $r['MaHang'], $r['TenSP'], (int) $r['SoLuongBan'], (float) $r['TienHang']], $rows);
            return ['headers' => ['Hạng', 'Mã hàng', 'Sản phẩm', 'Đã bán', 'Tiền hàng (₫)'],
                    'rows' => $out, 'right' => [3, 4], 'money' => [4],
                    'total' => ['', '', 'Tổng', array_sum(array_column($out, 3)), array_sum(array_column($out, 4))]];
        case 'BB03':
            $rows = db_all('SELECT MaHang, TenSP, SoLuongTon, NguongCanhBao, TinhTrang FROM vw_BB03_TonKho ORDER BY (TinhTrang = \'Cảnh báo\') DESC, SoLuongTon, MaHang');
            $low = count(array_filter($rows, static fn($r) => $r['TinhTrang'] === 'Cảnh báo'));
            return ['headers' => ['Mã hàng', 'Sản phẩm', 'Tồn', 'Ngưỡng cảnh báo', 'Tình trạng'],
                    'rows' => array_map(static fn($r) => [$r['MaHang'], $r['TenSP'], (int) $r['SoLuongTon'], (int) $r['NguongCanhBao'], $r['TinhTrang']], $rows),
                    'right' => [2, 3], 'money' => [],
                    'total' => ['Tổng', count($rows) . ' sản phẩm', '', '', $low . ' dưới ngưỡng']];
        case 'BB04':
            $rows = db_all('SELECT MaPN, NgayNhap, NhaCungCap, NguoiLap, TongTien FROM vw_BB04_PhieuNhap WHERE NgayNhap BETWEEN ? AND ? ORDER BY NgayNhap DESC', [$fromT, $toT]);
            $out  = array_map(static fn($r) => [purchase_code((int) $r['MaPN']), date('d/m/Y H:i', strtotime($r['NgayNhap'])), $r['NhaCungCap'], $r['NguoiLap'], (float) $r['TongTien']], $rows);
            return ['headers' => ['Mã phiếu', 'Ngày nhập', 'Nhà cung cấp', 'Người lập', 'Tổng tiền (₫)'], 'rows' => $out, 'right' => [4], 'money' => [4],
                    'total' => ['Tổng', '', '', '', array_sum(array_column($out, 4))]];
        case 'BB05':
            $rows = db_all('SELECT TrangThai, SUM(SoDon) AS SoDon FROM vw_BB05_DonHangTheoTrangThai WHERE Ngay BETWEEN ? AND ? GROUP BY TrangThai', [$from, $to]);
            $map  = array_column($rows, 'SoDon', 'TrangThai');
            $out  = [];
            foreach (ORDER_STATUSES as $s) {
                $out[] = [$s, (int) ($map[$s] ?? 0)];
            }
            return ['headers' => ['Trạng thái', 'Số đơn'], 'rows' => $out, 'right' => [1], 'money' => [], 'badge' => 0,
                    'total' => ['Tổng', array_sum(array_column($out, 1))]];
        case 'BB06':
            $rows = db_all("SELECT DATE_FORMAT(Ngay,'%Y-%m') AS Thang, LoaiYeuCau, TrangThai, SUM(SoYeuCau) AS N
                              FROM vw_BB06_BaoHanhDoiTra WHERE Ngay BETWEEN ? AND ?
                             GROUP BY Thang, LoaiYeuCau, TrangThai ORDER BY Thang DESC, LoaiYeuCau", [$from, $to]);
            $acc = [];
            foreach ($rows as $r) {
                $k = $r['Thang'] . '|' . $r['LoaiYeuCau'];
                $acc[$k] ??= ['t' => $r['Thang'], 'l' => $r['LoaiYeuCau'], 'n' => array_fill_keys(WARRANTY_STATUSES, 0)];
                $acc[$k]['n'][$r['TrangThai']] = (int) $r['N'];
            }
            $out = [];
            $tot = array_fill_keys(WARRANTY_STATUSES, 0);
            foreach ($acc as $a) {
                $out[] = array_merge([$a['t'], $a['l'], array_sum($a['n'])], array_values($a['n']));
                foreach ($a['n'] as $st => $n) {
                    $tot[$st] += $n;
                }
            }
            return ['headers' => array_merge(['Tháng', 'Loại yêu cầu', 'Số yêu cầu'], WARRANTY_STATUSES),
                    'rows' => $out, 'right' => [2, 3, 4, 5, 6], 'money' => [],
                    'total' => array_merge(['Tổng', '', array_sum($tot)], array_values($tot))];
        case 'BB07':
            $rows = db_all("SELECT DATE_FORMAT(Ngay,'%Y-%m') AS Thang, SUM(SoYeuCau) AS a, SUM(SoDuocChapNhan) AS b, SUM(SoHetHan) AS c, SUM(SoConLai) AS d
                              FROM vw_BB07_BaoGia WHERE Ngay BETWEEN ? AND ? GROUP BY Thang ORDER BY Thang DESC", [$from, $to]);
            $out = array_map(static fn($r) => [$r['Thang'], (int) $r['a'], (int) $r['b'], (int) $r['c'], (int) $r['d']], $rows);
            return ['headers' => ['Tháng', 'Số yêu cầu', 'Được chấp nhận', 'Hết hạn', 'Còn lại'], 'rows' => $out,
                    'right' => [1, 2, 3, 4], 'money' => [],
                    'total' => ['Tổng', array_sum(array_column($out, 1)), array_sum(array_column($out, 2)),
                                array_sum(array_column($out, 3)), array_sum(array_column($out, 4))]];
    }
    return ['headers' => [], 'rows' => [], 'right' => [], 'money' => []];
}

/** Xuất báo biểu ra CSV (có BOM để Excel đọc đúng tiếng Việt) rồi dừng. */
function admin_report_csv(string $code, array $rep): void
{
    $name = strtolower($code) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $rep['headers'], ',', '"', '');
    foreach ($rep['rows'] as $row) {
        // Chặn công thức Excel (CSV injection) với ô văn bản bắt đầu bằng = + - @
        fputcsv($out, array_map(static fn($c) => is_string($c) && $c !== '' && strpos("=+-@\t\r", $c[0]) !== false ? "'" . $c : $c, $row), ',', '"', '');
    }
    if (!empty($rep['total'])) {
        fputcsv($out, $rep['total'], ',', '"', '');
    }
    fclose($out);
    exit;
}

/* ============================================================================
 * Hàng hóa, nội dung, tài khoản, báo giá, bảo hành (giai đoạn 4)
 * ========================================================================== */

const ADMIN_PRODUCT_FILTERS = ['Đang bán', 'Đã ẩn', 'Liên hệ báo giá'];
const QUOTE_STATUSES        = ['Mới', 'Đã báo giá', 'Đã chấp nhận', 'Đã từ chối', 'Hết hạn', 'Không báo giá được'];
const WARRANTY_STATUSES     = ['Mới', 'Đã bảo hành', 'Đã đổi sản phẩm', 'Từ chối'];

/** Lấy số thứ tự từ mã hiển thị: "BG000031", "bg 31" hoặc "31" => 31 (không hợp lệ => 0). */
function admin_code_id(string $raw, string $prefix): int
{
    return preg_match('/^(?:' . preg_quote($prefix, '/') . ')?\s*0*(\d{1,9})$/i', trim($raw), $m) ? (int) $m[1] : 0;
}

/* ================================== Sản phẩm ================================ */

/** Danh sách sản phẩm (cả hàng đã ẩn) có tìm theo tên, mã hàng, lọc, phân trang. */
function admin_products(string $q, string $filter, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($filter === 'Đang bán') {
        $where .= ' AND s.TrangThai = 1';
    } elseif ($filter === 'Đã ẩn') {
        $where .= ' AND s.TrangThai = 0';
    } elseif ($filter === 'Liên hệ báo giá') {
        $where .= ' AND s.LienHeBaoGia = 1';
    }
    $q = trim($q);
    if ($q !== '') {
        $like   = like_escape($q);
        $where .= ' AND (s.TenSP LIKE ? OR s.MaHang LIKE ?)';
        array_push($params, $like, $like);
    }
    $total = (int) db_val("SELECT COUNT(*) FROM SANPHAM s WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT s.MaSP, s.MaHang, s.TenSP, s.GiaNiemYet, s.GiaBan, s.LienHeBaoGia, s.ThoiGianBH, s.TrangThai,
                d.TenDM, g.TenDong
           FROM SANPHAM s JOIN DANHMUC d ON d.MaDM = s.MaDM LEFT JOIN DONGSANPHAM g ON g.MaDong = s.MaDong
          WHERE $where ORDER BY s.MaSP DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Ẩn hoặc hiện lại sản phẩm (không xóa, để giữ lịch sử đơn hàng). */
function admin_product_toggle(int $id): array
{
    $row = db_one('SELECT TenSP, TrangThai FROM SANPHAM WHERE MaSP = ?', [$id]);
    if (!$row) {
        return ['ok' => false, 'msg' => 'Không tìm thấy sản phẩm.'];
    }
    $new = (int) $row['TrangThai'] === 1 ? 0 : 1;
    db_exec('UPDATE SANPHAM SET TrangThai = ? WHERE MaSP = ?', [$new, $id]);
    return ['ok' => true, 'msg' => ($new === 1 ? 'Đã hiện lại sản phẩm: ' : 'Đã ẩn sản phẩm: ') . $row['TenSP']];
}

/** Một sản phẩm đầy đủ để xem và sửa trong form (null nếu không có). */
function admin_product(int $id): ?array
{
    return db_one(
        'SELECT MaSP, MaHang, TenSP, MaDM, MaDong, GiaNiemYet, GiaBan, LienHeBaoGia, ThoiGianBH, MoTa, TrangThai, TinhTrangKD,
                XuatXu, NamRaMat, PhuKien, VideoUrl, MoTaNgan, HinhThucBH, ChinhSachHauMai,
                (SELECT h.DuongDan FROM HINHANH h WHERE h.MaSP = SANPHAM.MaSP ORDER BY h.LaAnhChinh DESC, h.MaHA LIMIT 1) AS Anh
           FROM SANPHAM WHERE MaSP = ?',
        [$id]
    );
}

/** Số tiền nguyên từ chuỗi nhập tay: "45.000", "45,000 ₫", "45000" => 45000; rỗng hoặc sai => null. */
function admin_money($raw): ?int
{
    $digits = preg_replace('/[\s.,₫đ]/u', '', trim((string) $raw));
    return preg_match('/^\d{1,12}$/', (string) $digits) ? (int) $digits : null;
}

/** Bỏ dấu tiếng Việt, chỉ giữ a-z, 0-9 và dấu gạch ngang (dùng cho cột Slug). */
function admin_slugify(string $text): string
{
    $groups = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ',
    ];
    $map = [];
    foreach ($groups as $plain => $chars) {
        foreach (mb_str_split($chars, 1, 'UTF-8') as $ch) {
            $map[$ch] = $plain;
        }
    }
    $text = strtr(mb_strtolower($text, 'UTF-8'), $map);
    $text = trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');
    return substr($text, 0, 200);
}

/** Slug chưa trùng: thêm -2, -3... nếu đã có sản phẩm khác dùng. */
function admin_unique_slug(string $base, int $exceptId = 0): string
{
    $base = $base !== '' ? $base : 'san-pham';
    $slug = $base;
    for ($i = 2; (int) db_val('SELECT COUNT(*) FROM SANPHAM WHERE Slug = ? AND MaSP <> ?', [$slug, $exceptId]) > 0; $i++) {
        $slug = substr($base, 0, 200) . '-' . $i;
    }
    return $slug;
}

/**
 * Lưu một tệp ảnh sản phẩm tải lên vào uploads/products/.
 * Chỉ nhận JPG, PNG, WebP (kiểm tra nội dung thật bằng getimagesize, không tin đuôi tệp), tối đa 2 MB; tên tệp do máy chủ đặt.
 * @param array|null $file một phần tử của $_FILES
 * @return array{ok:bool,path:string,msg:string} path rỗng nếu không chọn tệp
 */
function admin_product_image_store(?array $file): array
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($file === null || $err === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => '', 'msg' => ''];
    }
    $fail = static fn(string $m): array => ['ok' => false, 'path' => '', 'msg' => $m];
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return $fail('Ảnh quá lớn (tối đa 2 MB).');
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
        return $fail('Không tải được ảnh lên. Vui lòng thử lại.');
    }
    if ((int) $file['size'] > 2 * 1024 * 1024) {
        return $fail('Ảnh quá lớn (tối đa 2 MB).');
    }
    $info  = @getimagesize((string) $file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($types[$info[2]])) {
        return $fail('Chỉ nhận ảnh JPG, PNG hoặc WebP.');
    }
    if ($info[0] < 100 || $info[1] < 100 || $info[0] > 6000 || $info[1] > 6000) {
        return $fail('Kích thước ảnh phải từ 100 đến 6000 điểm ảnh mỗi cạnh.');
    }
    $dir = ROOT_PATH . '/uploads/products';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return $fail('Không tạo được thư mục lưu ảnh.');
    }
    $name = bin2hex(random_bytes(8)) . '.' . $types[$info[2]];
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
        return $fail('Không lưu được ảnh lên máy chủ.');
    }
    return ['ok' => true, 'path' => 'uploads/products/' . $name, 'msg' => ''];
}

/**
 * Thêm ($id = 0) hoặc sửa sản phẩm: thông tin chung, giá, bảo hành, thông số theo danh mục và ảnh chính.
 * Mọi bước ghi CSDL chạy trong một giao dịch; ảnh vừa tải lên bị xóa nếu giao dịch lỗi.
 * Sản phẩm mới tự có dòng tồn kho 0 (để đặt hàng và nhập kho hoạt động).
 * Ảnh tải lên trở thành ảnh chính, ảnh cũ vẫn giữ làm ảnh phụ.
 * @param array{ma:string,ten:string,dm:int,dong:int,mota:string,gia:string,gb:string,bg:bool,bh:string,tt:string,hien:bool,ts:array} $in
 * @return array{ok:bool,msg:string,id:int}
 */
function admin_product_save(int $id, array $in, ?array $file = null): array
{
    $fail  = static fn(string $m): array => ['ok' => false, 'msg' => $m, 'id' => $id];
    $isNew = $id === 0;

    $ma   = trim((string) $in['ma']);
    $ten  = trim((string) $in['ten']);
    $mota = trim((string) $in['mota']);
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,49}$/', $ma)) {
        return $fail('Mã hàng gồm chữ, số, dấu chấm, gạch ngang hoặc gạch dưới, tối đa 50 ký tự (ví dụ 282434-C).');
    }
    if (mb_strlen($ten, 'UTF-8') < 3 || mb_strlen($ten, 'UTF-8') > 200) {
        return $fail('Tên sản phẩm từ 3 đến 200 ký tự.');
    }
    if (mb_strlen($mota, 'UTF-8') > 10000) {
        return $fail('Mô tả tối đa 10.000 ký tự.');
    }
    $maDM = (int) $in['dm'];
    if (db_val('SELECT 1 FROM DANHMUC WHERE MaDM = ?', [$maDM]) === null) {
        return $fail('Hãy chọn danh mục.');
    }
    $maDong = (int) $in['dong'];
    if ($maDong > 0 && db_val('SELECT 1 FROM DONGSANPHAM WHERE MaDong = ?', [$maDong]) === null) {
        return $fail('Dòng sản phẩm không tồn tại.');
    }
    if ($id > 0 && db_val('SELECT 1 FROM SANPHAM WHERE MaSP = ?', [$id]) === null) {
        return $fail('Không tìm thấy sản phẩm.');
    }
    if (db_val('SELECT 1 FROM SANPHAM WHERE MaHang = ? AND MaSP <> ?', [$ma, $id]) !== null) {
        return $fail('Mã hàng "' . $ma . '" đã có ở sản phẩm khác.');
    }
    // Thông tin bổ sung (không bắt buộc): rỗng thì lưu NULL.
    $extra = [
        'xx'   => trim((string) ($in['xx'] ?? '')),
        'nam'  => trim((string) ($in['nam'] ?? '')),
        'pk'   => trim((string) ($in['pk'] ?? '')),
        'vid'  => trim((string) ($in['vid'] ?? '')),
        'mtn'  => trim((string) ($in['mtn'] ?? '')),
        'htbh' => trim((string) ($in['htbh'] ?? '')),
        'cshm' => trim((string) ($in['cshm'] ?? '')),
    ];
    $len = static fn(string $v): int => mb_strlen($v, 'UTF-8');
    if ($len($extra['xx']) > 150)   { return $fail('Xuất xứ tối đa 150 ký tự.'); }
    if ($len($extra['pk']) > 1000)  { return $fail('Phụ kiện đi kèm tối đa 1.000 ký tự.'); }
    if ($len($extra['mtn']) > 500)  { return $fail('Mô tả ngắn tối đa 500 ký tự.'); }
    if ($len($extra['htbh']) > 100) { return $fail('Hình thức bảo hành tối đa 100 ký tự.'); }
    if ($len($extra['cshm']) > 2000) { return $fail('Chính sách hậu mãi tối đa 2.000 ký tự.'); }
    $nam = null;
    if ($extra['nam'] !== '') {
        $nam = admin_int($extra['nam']);
        if ($nam === null || $nam < 1900 || $nam > (int) date('Y') + 1) {
            return $fail('Năm ra mắt không hợp lệ.');
        }
    }
    if ($extra['vid'] !== '' && (strlen($extra['vid']) > 255 || !preg_match('#^https?://#i', $extra['vid']) || !filter_var($extra['vid'], FILTER_VALIDATE_URL))) {
        return $fail('Đường dẫn video phải bắt đầu bằng http:// hoặc https:// (tối đa 255 ký tự).');
    }
    $nz = static fn(string $v) => $v === '' ? null : $v;

    $bh = admin_int($in['bh']);
    if ($bh === null || $bh > 120) {
        return $fail('Thời hạn bảo hành là số tháng từ 0 đến 120.');
    }
    if (!in_array($in['tt'], ['Đang kinh doanh', 'Ngừng kinh doanh', 'Hàng sắp về'], true)) {
        return $fail('Tình trạng kinh doanh không hợp lệ.');
    }

    // Giá: sản phẩm bán theo báo giá không lưu giá; còn lại cần giá niêm yết, giá bán không vượt giá niêm yết.
    $baoGia = (bool) $in['bg'];
    $niemYet = $giaBan = null;
    $giam    = 0.0;
    if (!$baoGia) {
        $niemYet = admin_money($in['gia']);
        if ($niemYet === null || $niemYet < 1) {
            return $fail('Hãy nhập giá niêm yết là số lớn hơn 0 (hoặc bật "Liên hệ báo giá").');
        }
        $giaBan = trim((string) $in['gb']) === '' ? $niemYet : admin_money($in['gb']);
        if ($giaBan === null || $giaBan < 1) {
            return $fail('Giá bán phải là số lớn hơn 0 (để trống nếu bằng giá niêm yết).');
        }
        if ($giaBan > $niemYet) {
            return $fail('Giá bán không được cao hơn giá niêm yết.');
        }
        $giam = round(($niemYet - $giaBan) * 100 / $niemYet, 2);
    }

    // Thông số: chỉ nhận loại thông số thuộc danh mục đã chọn, giá trị tối đa 100 ký tự.
    $specs = [];
    foreach (db_all('SELECT MaTS, TenTS FROM THONGSO WHERE MaDM = ?', [$maDM]) as $t) {
        $raw = $in['ts'][$t['MaTS']] ?? '';
        $val = is_scalar($raw) ? trim((string) $raw) : '';
        if (mb_strlen($val, 'UTF-8') > 100) {
            return $fail('Thông số "' . $t['TenTS'] . '" tối đa 100 ký tự.');
        }
        $specs[(int) $t['MaTS']] = $val;
    }

    $img = admin_product_image_store($file);
    if (!$img['ok']) {
        return $fail($img['msg']);
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        if ($id > 0) {
            db_exec(
                'UPDATE SANPHAM SET MaHang = ?, TenSP = ?, MaDM = ?, MaDong = ?, GiaNiemYet = ?, GiaBan = ?, PhanTramGiam = ?,
                        LienHeBaoGia = ?, TinhTrangKD = ?, MoTa = ?, ThoiGianBH = ?, TrangThai = ?,
                        XuatXu = ?, NamRaMat = ?, PhuKien = ?, VideoUrl = ?, MoTaNgan = ?, HinhThucBH = ?, ChinhSachHauMai = ?
                  WHERE MaSP = ?',
                [$ma, $ten, $maDM, $maDong > 0 ? $maDong : null, $niemYet, $giaBan, $giam,
                 $baoGia ? 1 : 0, $in['tt'], $mota !== '' ? $mota : null, $bh, $in['hien'] ? 1 : 0,
                 $nz($extra['xx']), $nam, $nz($extra['pk']), $nz($extra['vid']), $nz($extra['mtn']), $nz($extra['htbh']), $nz($extra['cshm']), $id]
            );
        } else {
            db_exec(
                'INSERT INTO SANPHAM (MaHang, TenSP, Slug, MaDM, MaDong, GiaNiemYet, GiaBan, PhanTramGiam, LienHeBaoGia,
                                      TinhTrangKD, MoTa, ThoiGianBH, TrangThai,
                                      XuatXu, NamRaMat, PhuKien, VideoUrl, MoTaNgan, HinhThucBH, ChinhSachHauMai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ma, $ten, admin_unique_slug(admin_slugify($ten . ' ' . $ma)), $maDM, $maDong > 0 ? $maDong : null,
                 $niemYet, $giaBan, $giam, $baoGia ? 1 : 0, $in['tt'], $mota !== '' ? $mota : null, $bh, $in['hien'] ? 1 : 0,
                 $nz($extra['xx']), $nam, $nz($extra['pk']), $nz($extra['vid']), $nz($extra['mtn']), $nz($extra['htbh']), $nz($extra['cshm'])]
            );
            $id = (int) $pdo->lastInsertId();
            db_exec('INSERT INTO TONKHO (MaSP, SoLuongTon, NguongCanhBao) VALUES (?, 0, 10)', [$id]);
        }
        // Đổi danh mục thì bỏ các thông số của danh mục cũ.
        db_exec('DELETE FROM CT_THONGSO WHERE MaSP = ? AND MaTS NOT IN (SELECT MaTS FROM THONGSO WHERE MaDM = ?)', [$id, $maDM]);
        foreach ($specs as $maTS => $val) {
            if ($val === '') {
                db_exec('DELETE FROM CT_THONGSO WHERE MaSP = ? AND MaTS = ?', [$id, $maTS]);
            } else {
                db_exec('INSERT INTO CT_THONGSO (MaSP, MaTS, GiaTri) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE GiaTri = VALUES(GiaTri)', [$id, $maTS, $val]);
            }
        }
        if ($img['path'] !== '') {
            db_exec('UPDATE HINHANH SET LaAnhChinh = 0 WHERE MaSP = ?', [$id]);
            db_exec('INSERT INTO HINHANH (MaSP, DuongDan, LaAnhChinh) VALUES (?, ?, 1)', [$id, $img['path']]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($img['path'] !== '') {
            @unlink(ROOT_PATH . '/' . $img['path']);
        }
        if ($e->getCode() === '23000') {   // trùng mã hàng/slug do hai người lưu cùng lúc, hoặc vi phạm ràng buộc
            return $fail('Không lưu được: mã hàng bị trùng hoặc dữ liệu vi phạm ràng buộc. Hãy kiểm tra lại.');
        }
        error_log('[DB] product_save: ' . $e->getMessage());
        return $fail('Không lưu được sản phẩm do lỗi hệ thống. Vui lòng thử lại.');
    }
    return ['ok' => true, 'msg' => ($isNew ? 'Đã thêm sản phẩm ' : 'Đã lưu sản phẩm ') . $ma . '.', 'id' => $id];
}

/** Thông số của một danh mục kèm giá trị của sản phẩm $productId (0 = chưa có). */
function admin_specs_for(int $categoryId, int $productId = 0): array
{
    return db_all(
        'SELECT t.MaTS, t.TenTS, t.DonVi, c.GiaTri
           FROM THONGSO t LEFT JOIN CT_THONGSO c ON c.MaTS = t.MaTS AND c.MaSP = ?
          WHERE t.MaDM = ? ORDER BY t.MaTS',
        [$productId, $categoryId]
    );
}

/* ================================== Danh mục ================================ */

/** Mọi danh mục (gốc trước, con ngay sau gốc của nó) kèm tên cha và số sản phẩm. */
function admin_categories(): array
{
    return db_all(
        'SELECT d.MaDM, d.TenDM, d.MaDMCha, p.TenDM AS Cha,
                (SELECT COUNT(*) FROM SANPHAM s WHERE s.MaDM = d.MaDM) AS Dem
           FROM DANHMUC d LEFT JOIN DANHMUC p ON p.MaDM = d.MaDMCha
          ORDER BY COALESCE(d.MaDMCha, d.MaDM), d.MaDMCha IS NOT NULL, d.MaDM'
    );
}

/** Thêm danh mục ($parentId = 0 là danh mục gốc). */
function admin_category_add(string $name, int $parentId): array
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Tên danh mục từ 1 đến 100 ký tự.'];
    }
    if ($parentId > 0 && db_val('SELECT MaDM FROM DANHMUC WHERE MaDM = ?', [$parentId]) === null) {
        return ['ok' => false, 'msg' => 'Danh mục cha không tồn tại.'];
    }
    if ($parentId > 0 && db_val('SELECT MaDMCha FROM DANHMUC WHERE MaDM = ?', [$parentId]) !== null) {
        return ['ok' => false, 'msg' => 'Chỉ hỗ trợ hai cấp danh mục: hãy chọn một danh mục gốc làm danh mục cha.'];
    }
    $dup = $parentId > 0
        ? db_val('SELECT COUNT(*) FROM DANHMUC WHERE TenDM = ? AND MaDMCha = ?', [$name, $parentId])
        : db_val('SELECT COUNT(*) FROM DANHMUC WHERE TenDM = ? AND MaDMCha IS NULL', [$name]);
    if ((int) $dup > 0) {
        return ['ok' => false, 'msg' => 'Danh mục này đã có.'];
    }
    db_exec('INSERT INTO DANHMUC (TenDM, MaDMCha) VALUES (?, ?)', [$name, $parentId > 0 ? $parentId : null]);
    return ['ok' => true, 'msg' => 'Đã thêm danh mục: ' . $name];
}

/** Đổi tên danh mục (không đổi danh mục cha, nên không thể tạo vòng lặp). */
function admin_category_rename(int $id, string $name): array
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Tên danh mục từ 1 đến 100 ký tự.'];
    }
    $row = db_one('SELECT MaDMCha FROM DANHMUC WHERE MaDM = ?', [$id]);
    if ($row === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy danh mục.'];
    }
    $dup = $row['MaDMCha'] === null
        ? db_val('SELECT COUNT(*) FROM DANHMUC WHERE TenDM = ? AND MaDMCha IS NULL AND MaDM <> ?', [$name, $id])
        : db_val('SELECT COUNT(*) FROM DANHMUC WHERE TenDM = ? AND MaDMCha = ? AND MaDM <> ?', [$name, $row['MaDMCha'], $id]);
    if ((int) $dup > 0) {
        return ['ok' => false, 'msg' => 'Đã có danh mục cùng tên ở cùng cấp.'];
    }
    db_exec('UPDATE DANHMUC SET TenDM = ? WHERE MaDM = ?', [$name, $id]);
    return ['ok' => true, 'msg' => 'Đã đổi tên danh mục thành: ' . $name];
}

/** Đổi tên dòng sản phẩm. */
function admin_series_rename(int $id, string $name): array
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Tên dòng sản phẩm từ 1 đến 100 ký tự.'];
    }
    if (db_val('SELECT 1 FROM DONGSANPHAM WHERE MaDong = ?', [$id]) === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy dòng sản phẩm.'];
    }
    if ((int) db_val('SELECT COUNT(*) FROM DONGSANPHAM WHERE TenDong = ? AND MaDong <> ?', [$name, $id]) > 0) {
        return ['ok' => false, 'msg' => 'Đã có dòng sản phẩm cùng tên.'];
    }
    db_exec('UPDATE DONGSANPHAM SET TenDong = ? WHERE MaDong = ?', [$name, $id]);
    return ['ok' => true, 'msg' => 'Đã đổi tên dòng sản phẩm thành: ' . $name];
}

/* ============================ Dòng sản phẩm, thông số ======================= */

/** Dòng sản phẩm kèm số mã hàng. */
function admin_series_list(): array
{
    return db_all(
        'SELECT g.MaDong, g.TenDong, (SELECT COUNT(*) FROM SANPHAM s WHERE s.MaDong = g.MaDong) AS Dem
           FROM DONGSANPHAM g ORDER BY g.TenDong'
    );
}

function admin_series_add(string $name): array
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Tên dòng sản phẩm từ 1 đến 100 ký tự.'];
    }
    if ((int) db_val('SELECT COUNT(*) FROM DONGSANPHAM WHERE TenDong = ?', [$name]) > 0) {
        return ['ok' => false, 'msg' => 'Dòng sản phẩm này đã có.'];
    }
    db_exec('INSERT INTO DONGSANPHAM (TenDong) VALUES (?)', [$name]);
    return ['ok' => true, 'msg' => 'Đã thêm dòng sản phẩm: ' . $name];
}

/** Loại thông số theo danh mục. */
function admin_spec_types(): array
{
    return db_all(
        'SELECT t.MaTS, t.TenTS, t.DonVi, t.NhomTS, t.MaDM, d.TenDM,
                (SELECT COUNT(*) FROM CT_THONGSO c WHERE c.MaTS = t.MaTS) AS Dem
           FROM THONGSO t JOIN DANHMUC d ON d.MaDM = t.MaDM ORDER BY d.TenDM, t.MaTS'
    );
}

/** Tài liệu kỹ thuật kèm nguồn gắn (dòng sản phẩm hoặc mã hàng) và dung lượng file nếu có trên đĩa. */
function admin_documents(): array
{
    $rows = db_all(
        'SELECT l.MaTL, l.TenTL, l.DuongDan, l.MaDong, l.MaSP, g.TenDong, s.MaHang
           FROM TAILIEU l LEFT JOIN DONGSANPHAM g ON g.MaDong = l.MaDong LEFT JOIN SANPHAM s ON s.MaSP = l.MaSP
          ORDER BY l.MaTL'
    );
    foreach ($rows as &$r) {
        $file        = ROOT_PATH . '/' . ltrim((string) $r['DuongDan'], '/');
        $r['Loai']   = strtoupper(pathinfo((string) $r['DuongDan'], PATHINFO_EXTENSION)) ?: 'FILE';
        $r['DungLuong'] = is_file($file) ? number_format(filesize($file) / 1048576, 1, ',', '.') . ' MB' : 'Chưa có file';
    }
    unset($r);
    return $rows;
}

/* ===================== Sửa, xóa danh mục / dòng sản phẩm; thông số; tài liệu ===================== */

/** Sửa tên và danh mục cha ($parentId = 0 là danh mục gốc). Chỉ hai cấp, không tạo vòng lặp. */
function admin_category_update(int $id, string $name, int $parentId): array
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Tên danh mục từ 1 đến 100 ký tự.'];
    }
    if (db_val('SELECT 1 FROM DANHMUC WHERE MaDM = ?', [$id]) === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy danh mục.'];
    }
    if ($parentId === $id) {
        return ['ok' => false, 'msg' => 'Danh mục không thể là cha của chính nó.'];
    }
    if ($parentId > 0) {
        $p = db_one('SELECT MaDMCha FROM DANHMUC WHERE MaDM = ?', [$parentId]);
        if ($p === null) {
            return ['ok' => false, 'msg' => 'Danh mục cha không tồn tại.'];
        }
        if ($p['MaDMCha'] !== null) {
            return ['ok' => false, 'msg' => 'Chỉ hỗ trợ hai cấp danh mục: hãy chọn một danh mục gốc làm danh mục cha.'];
        }
        if ((int) db_val('SELECT COUNT(*) FROM DANHMUC WHERE MaDMCha = ?', [$id]) > 0) {
            return ['ok' => false, 'msg' => 'Danh mục này đang có danh mục con nên không thể chuyển thành danh mục con.'];
        }
    }
    $dup = $parentId > 0
        ? db_val('SELECT COUNT(*) FROM DANHMUC WHERE TenDM = ? AND MaDMCha = ? AND MaDM <> ?', [$name, $parentId, $id])
        : db_val('SELECT COUNT(*) FROM DANHMUC WHERE TenDM = ? AND MaDMCha IS NULL AND MaDM <> ?', [$name, $id]);
    if ((int) $dup > 0) {
        return ['ok' => false, 'msg' => 'Đã có danh mục cùng tên ở cấp này.'];
    }
    db_exec('UPDATE DANHMUC SET TenDM = ?, MaDMCha = ? WHERE MaDM = ?', [$name, $parentId > 0 ? $parentId : null, $id]);
    return ['ok' => true, 'msg' => 'Đã cập nhật danh mục: ' . $name];
}

/** Xóa danh mục: chỉ khi không còn danh mục con, sản phẩm và thông số đang có giá trị. Loại thông số trống được xóa theo. */
function admin_category_delete(int $id): array
{
    $row = db_one('SELECT TenDM FROM DANHMUC WHERE MaDM = ?', [$id]);
    if ($row === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy danh mục.'];
    }
    if ((int) db_val('SELECT COUNT(*) FROM DANHMUC WHERE MaDMCha = ?', [$id]) > 0) {
        return ['ok' => false, 'msg' => 'Không xóa được: danh mục còn danh mục con.'];
    }
    $n = (int) db_val('SELECT COUNT(*) FROM SANPHAM WHERE MaDM = ?', [$id]);
    if ($n > 0) {
        return ['ok' => false, 'msg' => "Không xóa được: còn $n sản phẩm thuộc danh mục này. Hãy chuyển sản phẩm sang danh mục khác trước."];
    }
    try {
        db()->beginTransaction();
        db_exec('DELETE FROM THONGSO WHERE MaDM = ?', [$id]);
        db_exec('DELETE FROM DANHMUC WHERE MaDM = ?', [$id]);
        db()->commit();
    } catch (PDOException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        error_log('[admin_category_delete] ' . $ex->getMessage());
        return ['ok' => false, 'msg' => 'Không xóa được: danh mục đang được dùng ở nơi khác.'];
    }
    return ['ok' => true, 'msg' => 'Đã xóa danh mục: ' . $row['TenDM']];
}

/** Xóa dòng sản phẩm: chỉ khi không còn sản phẩm và tài liệu gắn với dòng này. */
function admin_series_delete(int $id): array
{
    $row = db_one('SELECT TenDong FROM DONGSANPHAM WHERE MaDong = ?', [$id]);
    if ($row === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy dòng sản phẩm.'];
    }
    $n = (int) db_val('SELECT COUNT(*) FROM SANPHAM WHERE MaDong = ?', [$id]);
    if ($n > 0) {
        return ['ok' => false, 'msg' => "Không xóa được: còn $n mã hàng thuộc dòng này."];
    }
    $d = (int) db_val('SELECT COUNT(*) FROM TAILIEU WHERE MaDong = ?', [$id]);
    if ($d > 0) {
        return ['ok' => false, 'msg' => "Không xóa được: còn $d tài liệu gắn với dòng này. Hãy xóa tài liệu trước."];
    }
    try {
        db_exec('DELETE FROM DONGSANPHAM WHERE MaDong = ?', [$id]);
    } catch (PDOException $ex) {
        error_log('[admin_series_delete] ' . $ex->getMessage());
        return ['ok' => false, 'msg' => 'Không xóa được: dòng sản phẩm đang được dùng ở nơi khác.'];
    }
    return ['ok' => true, 'msg' => 'Đã xóa dòng sản phẩm: ' . $row['TenDong']];
}

/** Thêm loại thông số cho một danh mục. */
function admin_spec_add(int $categoryId, string $name, string $unit, string $group): array
{
    $name = trim($name);
    $unit = trim($unit);
    $group = trim($group);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Tên thông số từ 1 đến 100 ký tự.'];
    }
    if (mb_strlen($unit, 'UTF-8') > 20) {
        return ['ok' => false, 'msg' => 'Đơn vị tối đa 20 ký tự.'];
    }
    if (mb_strlen($group, 'UTF-8') > 50) {
        return ['ok' => false, 'msg' => 'Nhóm thông số tối đa 50 ký tự.'];
    }
    if (db_val('SELECT 1 FROM DANHMUC WHERE MaDM = ?', [$categoryId]) === null) {
        return ['ok' => false, 'msg' => 'Hãy chọn danh mục áp dụng.'];
    }
    if ((int) db_val('SELECT COUNT(*) FROM THONGSO WHERE MaDM = ? AND TenTS = ?', [$categoryId, $name]) > 0) {
        return ['ok' => false, 'msg' => 'Danh mục này đã có thông số cùng tên.'];
    }
    db_exec('INSERT INTO THONGSO (TenTS, DonVi, NhomTS, MaDM) VALUES (?, ?, ?, ?)', [$name, $unit !== '' ? $unit : null, $group !== '' ? $group : null, $categoryId]);
    return ['ok' => true, 'msg' => 'Đã thêm thông số: ' . $name];
}

/** Sửa tên, đơn vị, nhóm của loại thông số (không đổi danh mục vì sản phẩm đã nhập giá trị theo danh mục cũ). */
function admin_spec_update(int $id, string $name, string $unit, string $group): array
{
    $name = trim($name);
    $unit = trim($unit);
    $group = trim($group);
    $row = db_one('SELECT MaDM FROM THONGSO WHERE MaTS = ?', [$id]);
    if ($row === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy thông số.'];
    }
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100 || mb_strlen($unit, 'UTF-8') > 20 || mb_strlen($group, 'UTF-8') > 50) {
        return ['ok' => false, 'msg' => 'Tên tối đa 100 ký tự, đơn vị 20, nhóm 50.'];
    }
    if ((int) db_val('SELECT COUNT(*) FROM THONGSO WHERE MaDM = ? AND TenTS = ? AND MaTS <> ?', [$row['MaDM'], $name, $id]) > 0) {
        return ['ok' => false, 'msg' => 'Danh mục này đã có thông số cùng tên.'];
    }
    db_exec('UPDATE THONGSO SET TenTS = ?, DonVi = ?, NhomTS = ? WHERE MaTS = ?', [$name, $unit !== '' ? $unit : null, $group !== '' ? $group : null, $id]);
    return ['ok' => true, 'msg' => 'Đã cập nhật thông số: ' . $name];
}

/** Xóa loại thông số khi chưa sản phẩm nào nhập giá trị. */
function admin_spec_delete(int $id): array
{
    $row = db_one('SELECT TenTS FROM THONGSO WHERE MaTS = ?', [$id]);
    if ($row === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy thông số.'];
    }
    $n = (int) db_val('SELECT COUNT(*) FROM CT_THONGSO WHERE MaTS = ?', [$id]);
    if ($n > 0) {
        return ['ok' => false, 'msg' => "Không xóa được: $n sản phẩm đang có giá trị cho thông số này."];
    }
    db_exec('DELETE FROM THONGSO WHERE MaTS = ?', [$id]);
    return ['ok' => true, 'msg' => 'Đã xóa thông số: ' . $row['TenTS']];
}

/**
 * Thêm tài liệu kỹ thuật (PDF, tối đa 10 MB) gắn với dòng sản phẩm hoặc một mã hàng.
 * Kiểm tra nội dung thật (đầu tệp "%PDF-"), tên tệp do máy chủ đặt, lưu ở uploads/docs/.
 * @param string $target 'dong' hoặc 'sp'
 */
function admin_document_add(string $title, string $target, int $targetId, ?array $file): array
{
    $title = trim($title);
    if ($title === '' || mb_strlen($title, 'UTF-8') > 200) {
        return ['ok' => false, 'msg' => 'Tên tài liệu từ 1 đến 200 ký tự.'];
    }
    if ($target === 'dong') {
        if (db_val('SELECT 1 FROM DONGSANPHAM WHERE MaDong = ?', [$targetId]) === null) {
            return ['ok' => false, 'msg' => 'Hãy chọn dòng sản phẩm.'];
        }
    } elseif ($target === 'sp') {
        if (db_val('SELECT 1 FROM SANPHAM WHERE MaSP = ?', [$targetId]) === null) {
            return ['ok' => false, 'msg' => 'Hãy chọn sản phẩm.'];
        }
    } else {
        return ['ok' => false, 'msg' => 'Hãy chọn nơi gắn tài liệu.'];
    }
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($file === null || $err === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'msg' => 'Hãy chọn tệp PDF.'];
    }
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE || (int) $file['size'] > 10 * 1024 * 1024) {
        return ['ok' => false, 'msg' => 'Tệp quá lớn (tối đa 10 MB). Nếu vẫn báo lỗi, tăng upload_max_filesize và post_max_size trong php.ini.'];
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
        return ['ok' => false, 'msg' => 'Không tải được tệp lên. Vui lòng thử lại.'];
    }
    $fh   = @fopen((string) $file['tmp_name'], 'rb');
    $head = $fh ? (string) fread($fh, 5) : '';
    if ($fh) {
        fclose($fh);
    }
    if ($head !== '%PDF-') {
        return ['ok' => false, 'msg' => 'Chỉ nhận tệp PDF hợp lệ.'];
    }
    $dir = ROOT_PATH . '/uploads/docs';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['ok' => false, 'msg' => 'Không tạo được thư mục lưu tài liệu.'];
    }
    $name = bin2hex(random_bytes(8)) . '.pdf';
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'msg' => 'Không lưu được tệp lên máy chủ.'];
    }
    try {
        db_exec(
            'INSERT INTO TAILIEU (MaDong, MaSP, TenTL, DuongDan) VALUES (?, ?, ?, ?)',
            [$target === 'dong' ? $targetId : null, $target === 'sp' ? $targetId : null, $title, 'uploads/docs/' . $name]
        );
    } catch (PDOException $ex) {
        @unlink($dir . '/' . $name);
        error_log('[admin_document_add] ' . $ex->getMessage());
        return ['ok' => false, 'msg' => 'Không lưu được tài liệu. Vui lòng thử lại.'];
    }
    return ['ok' => true, 'msg' => 'Đã thêm tài liệu: ' . $title];
}

/** Xóa tài liệu; xóa luôn tệp nếu nằm trong uploads/docs/ do hệ thống tải lên. */
function admin_document_delete(int $id): array
{
    $row = db_one('SELECT TenTL, DuongDan FROM TAILIEU WHERE MaTL = ?', [$id]);
    if ($row === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy tài liệu.'];
    }
    db_exec('DELETE FROM TAILIEU WHERE MaTL = ?', [$id]);
    $path = (string) $row['DuongDan'];
    if (preg_match('#^uploads/docs/[a-f0-9]{16}\.pdf$#', $path) && (int) db_val('SELECT COUNT(*) FROM TAILIEU WHERE DuongDan = ?', [$path]) === 0) {
        @unlink(ROOT_PATH . '/' . $path);
    }
    return ['ok' => true, 'msg' => 'Đã xóa tài liệu: ' . $row['TenTL']];
}

/* =================================== Tin tức ================================ */

/** Danh sách bài viết (cả nháp), tìm theo tiêu đề, lọc "Đã đăng" / "Nháp". */
function admin_news_list(string $q, string $filter, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($filter === 'Đã đăng') {
        $where .= ' AND t.TrangThai = 1';
    } elseif ($filter === 'Nháp') {
        $where .= ' AND t.TrangThai = 0';
    }
    $q = trim($q);
    if ($q !== '') {
        $where   .= ' AND t.TieuDe LIKE ?';
        $params[] = like_escape($q);
    }
    $total = (int) db_val("SELECT COUNT(*) FROM TINTUC t WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT t.MaTin, t.TieuDe, t.NgayDang, t.TrangThai, u.HoTen
           FROM TINTUC t JOIN NGUOIDUNG u ON u.MaND = t.MaND
          WHERE $where ORDER BY t.NgayDang DESC, t.MaTin DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

function admin_news_one(int $id): ?array
{
    return db_one('SELECT MaTin, TieuDe, NoiDung, TrangThai FROM TINTUC WHERE MaTin = ?', [$id]);
}

/** Đăng bài mới ($id = 0) hoặc sửa bài; $published: 1 = đăng, 0 = nháp. */
function admin_news_save(int $id, int $staffId, string $title, string $body, int $published): array
{
    $title = trim($title);
    $body  = trim($body);
    if ($title === '' || mb_strlen($title, 'UTF-8') > 200) {
        return ['ok' => false, 'msg' => 'Tiêu đề từ 1 đến 200 ký tự.'];
    }
    if ($body === '') {
        return ['ok' => false, 'msg' => 'Hãy nhập nội dung bài viết.'];
    }
    $published = $published === 1 ? 1 : 0;
    if ($id > 0) {
        if (admin_news_one($id) === null) {
            return ['ok' => false, 'msg' => 'Không tìm thấy bài viết.'];
        }
        db_exec('UPDATE TINTUC SET TieuDe = ?, NoiDung = ?, TrangThai = ? WHERE MaTin = ?', [$title, $body, $published, $id]);
        return ['ok' => true, 'msg' => 'Đã lưu bài viết.', 'id' => $id];
    }
    db_exec('INSERT INTO TINTUC (MaND, TieuDe, NoiDung, TrangThai) VALUES (?, ?, ?, ?)', [$staffId, $title, $body, $published]);
    return ['ok' => true, 'msg' => $published ? 'Đã đăng bài viết.' : 'Đã lưu bài nháp.', 'id' => (int) db()->lastInsertId()];
}

/* ============================== Tài khoản, phân quyền ======================= */

function admin_role_names(): array
{
    return array_column(db_all('SELECT TenVT FROM VAITRO ORDER BY MaVT'), 'TenVT');
}

/** Danh sách tài khoản, tìm theo tên hoặc email, lọc vai trò. */
function admin_users(string $q, string $role, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($role !== '' && in_array($role, admin_role_names(), true)) {
        $where   .= ' AND v.TenVT = ?';
        $params[] = $role;
    }
    $q = trim($q);
    if ($q !== '') {
        $like   = like_escape($q);
        $where .= ' AND (n.HoTen LIKE ? OR n.Email LIKE ?)';
        array_push($params, $like, $like);
    }
    $total = (int) db_val("SELECT COUNT(*) FROM NGUOIDUNG n JOIN VAITRO v ON v.MaVT = n.MaVT WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT n.MaND, n.HoTen, n.Email, n.TrangThai, v.TenVT
           FROM NGUOIDUNG n JOIN VAITRO v ON v.MaVT = n.MaVT
          WHERE $where ORDER BY n.MaVT, n.MaND LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Khóa hoặc mở khóa tài khoản (không tự khóa chính mình). */
function admin_user_toggle(int $id, int $selfId): array
{
    if ($id === $selfId) {
        return ['ok' => false, 'msg' => 'Bạn không thể tự khóa tài khoản của mình.'];
    }
    $row = db_one('SELECT HoTen, TrangThai FROM NGUOIDUNG WHERE MaND = ?', [$id]);
    if (!$row) {
        return ['ok' => false, 'msg' => 'Không tìm thấy tài khoản.'];
    }
    $new = (int) $row['TrangThai'] === 1 ? 0 : 1;
    db_exec('UPDATE NGUOIDUNG SET TrangThai = ?, SoLanSaiLienTiep = 0, KhoaDen = NULL WHERE MaND = ?', [$new, $id]);
    return ['ok' => true, 'msg' => ($new === 1 ? 'Đã mở khóa tài khoản: ' : 'Đã khóa tài khoản: ') . $row['HoTen']];
}

/** Tạo tài khoản (mọi vai trò) do quản trị viên nhập (UC16). */
function admin_user_create(string $name, string $email, string $phone, string $role, string $password): array
{
    $name  = trim($name);
    $email = mb_strtolower(trim($email), 'UTF-8');
    $phone = normalize_phone($phone);
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
        return ['ok' => false, 'msg' => 'Họ tên là bắt buộc (tối đa 100 ký tự).'];
    }
    if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'msg' => 'Email không hợp lệ.'];
    }
    if ($phone === '') {
        return ['ok' => false, 'msg' => 'Số điện thoại không hợp lệ.'];
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        return ['ok' => false, 'msg' => 'Mật khẩu phải từ 8 đến 72 ký tự.'];
    }
    $maVT = db_val('SELECT MaVT FROM VAITRO WHERE TenVT = ?', [$role]);
    if ($maVT === null) {
        return ['ok' => false, 'msg' => 'Vai trò không hợp lệ.'];
    }
    if (db_val('SELECT 1 FROM NGUOIDUNG WHERE Email = ?', [$email])) {
        return ['ok' => false, 'msg' => 'Email này đã có tài khoản.'];
    }
    try {
        db_exec(
            'INSERT INTO NGUOIDUNG (HoTen, Email, SoDienThoai, MatKhauBam, MaVT) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), (int) $maVT]
        );
    } catch (PDOException $e) {
        return ['ok' => false, 'msg' => db_error_message($e)];
    }
    return ['ok' => true, 'msg' => 'Đã tạo tài khoản ' . $email . ' (' . $role . ').'];
}

/** Đổi vai trò tài khoản. Không tự đổi vai trò của mình, không hạ vai trò quản trị viên cuối cùng còn hoạt động. */
function admin_user_set_role(int $id, string $role, int $selfId): array
{
    if ($id === $selfId) {
        return ['ok' => false, 'msg' => 'Bạn không thể tự đổi vai trò của mình.'];
    }
    $maVT = db_val('SELECT MaVT FROM VAITRO WHERE TenVT = ?', [$role]);
    if ($maVT === null) {
        return ['ok' => false, 'msg' => 'Vai trò không hợp lệ.'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $row = db_one('SELECT n.HoTen, n.MaVT, n.TrangThai, v.TenVT FROM NGUOIDUNG n JOIN VAITRO v ON v.MaVT = n.MaVT WHERE n.MaND = ? FOR UPDATE', [$id]);
        if (!$row) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Không tìm thấy tài khoản.'];
        }
        if ((int) $row['MaVT'] === (int) $maVT) {
            $pdo->rollBack();
            return ['ok' => true, 'msg' => 'Vai trò không thay đổi.'];
        }
        if ($row['TenVT'] === 'Quản trị viên' && (int) $row['TrangThai'] === 1) {
            $left = (int) db_val("SELECT COUNT(*) FROM NGUOIDUNG n JOIN VAITRO v ON v.MaVT = n.MaVT
                                   WHERE v.TenVT = 'Quản trị viên' AND n.TrangThai = 1 AND n.MaND <> ?", [$id]);
            if ($left < 1) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'Phải còn ít nhất một quản trị viên đang hoạt động.'];
            }
        }
        db_exec('UPDATE NGUOIDUNG SET MaVT = ? WHERE MaND = ?', [(int) $maVT, $id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => db_error_message($e)];
    }
    return ['ok' => true, 'msg' => 'Đã đổi vai trò của ' . $row['HoTen'] . ' thành ' . $role . '.'];
}

/** Quản trị viên đặt lại mật khẩu cho tài khoản (UC03: khách quên mật khẩu liên hệ cửa hàng). Cũng mở khóa đăng nhập sai. */
function admin_user_reset_password(int $id, string $password): array
{
    if (strlen($password) < 8 || strlen($password) > 72) {
        return ['ok' => false, 'msg' => 'Mật khẩu mới phải từ 8 đến 72 ký tự.'];
    }
    $name = db_val('SELECT HoTen FROM NGUOIDUNG WHERE MaND = ?', [$id]);
    if ($name === null) {
        return ['ok' => false, 'msg' => 'Không tìm thấy tài khoản.'];
    }
    db_exec('UPDATE NGUOIDUNG SET MatKhauBam = ?, SoLanSaiLienTiep = 0, KhoaDen = NULL WHERE MaND = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    return ['ok' => true, 'msg' => 'Đã đặt lại mật khẩu cho ' . $name . '. Hãy báo mật khẩu mới cho người dùng.'];
}

/* ================================== Báo giá ================================= */

/** Danh sách yêu cầu báo giá; tìm theo mã (BG000031) hoặc tên khách; lọc trạng thái. */
function admin_quotes(string $q, string $status, int $page, int $perPage = 15): array
{
    db_exec("UPDATE BAOGIA SET TrangThai = 'Hết hạn' WHERE TrangThai = 'Đã báo giá' AND HanHieuLuc IS NOT NULL AND HanHieuLuc < NOW()");
    $where  = '1=1';
    $params = [];
    if ($status !== '' && in_array($status, QUOTE_STATUSES, true)) {
        $where   .= ' AND b.TrangThai = ?';
        $params[] = $status;
    }
    $q = trim($q);
    if ($q !== '') {
        $where   .= ' AND (u.HoTen LIKE ? OR u.Email LIKE ?';
        array_push($params, like_escape($q), like_escape($q));
        $id = admin_code_id($q, 'BG');
        if ($id > 0) {
            $where   .= ' OR b.MaBG = ?';
            $params[] = $id;
        }
        $where .= ')';
    }
    $total = (int) db_val("SELECT COUNT(*) FROM BAOGIA b JOIN NGUOIDUNG u ON u.MaND = b.MaND WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT b.MaBG, b.NgayGui, b.TrangThai, b.HanHieuLuc, u.HoTen,
                (SELECT COUNT(*) FROM CT_BAOGIA c WHERE c.MaBG = b.MaBG) AS SoSP
           FROM BAOGIA b JOIN NGUOIDUNG u ON u.MaND = b.MaND
          WHERE $where ORDER BY b.NgayGui DESC, b.MaBG DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Một báo giá kèm khách, nhân viên xử lý và các dòng sản phẩm (null nếu không có). */
function admin_quote(int $id): ?array
{
    $b = db_one(
        'SELECT b.MaBG, b.NgayGui, b.GhiChu, b.TrangThai, b.HanHieuLuc, b.NgayTraLoi, b.LyDoKhongBG,
                u.HoTen, u.Email, u.SoDienThoai, nv.HoTen AS NhanVien
           FROM BAOGIA b JOIN NGUOIDUNG u ON u.MaND = b.MaND LEFT JOIN NGUOIDUNG nv ON nv.MaND = b.MaNVXuLy
          WHERE b.MaBG = ?',
        [$id]
    );
    if (!$b) {
        return null;
    }
    $b['items'] = db_all(
        'SELECT c.MaSP, s.MaHang, s.TenSP, c.SoLuong, c.DonGiaBao
           FROM CT_BAOGIA c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaBG = ? ORDER BY s.TenSP',
        [$id]
    );
    return $b;
}

/** Nhân viên nhập đơn giá từng dòng và hạn hiệu lực, gửi lại cho khách. $prices: [mã SP => đơn giá]. */
function admin_quote_reply(int $id, int $staffId, array $prices, string $expiry): array
{
    $q = admin_quote($id);
    if (!$q) {
        return ['ok' => false, 'msg' => 'Không tìm thấy báo giá.'];
    }
    if ($q['TrangThai'] !== 'Mới') {
        return ['ok' => false, 'msg' => 'Báo giá này đã được xử lý.'];
    }
    $until = admin_date($expiry);
    if ($until === '' || $until < date('Y-m-d')) {
        return ['ok' => false, 'msg' => 'Hãy chọn hạn hiệu lực từ hôm nay trở đi.'];
    }
    $clean = [];
    foreach ($q['items'] as $it) {
        $p = admin_int($prices[$it['MaSP']] ?? '');
        if ($p === null || $p < 1) {
            return ['ok' => false, 'msg' => 'Hãy nhập đơn giá (số nguyên lớn hơn 0) cho mọi dòng: ' . $it['TenSP']];
        }
        $clean[(int) $it['MaSP']] = $p;
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        foreach ($clean as $sp => $p) {
            db_exec('UPDATE CT_BAOGIA SET DonGiaBao = ? WHERE MaBG = ? AND MaSP = ?', [$p, $id, $sp]);
        }
        $n = db_exec(
            "UPDATE BAOGIA SET TrangThai = 'Đã báo giá', MaNVXuLy = ?, NgayTraLoi = NOW(), HanHieuLuc = ?, LyDoKhongBG = NULL
              WHERE MaBG = ? AND TrangThai = 'Mới'",
            [$staffId, $until . ' 23:59:00', $id]
        );
        if ($n !== 1) {
            throw new RuntimeException('Báo giá vừa được người khác xử lý.');
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => $ex instanceof PDOException ? db_error_message($ex) : $ex->getMessage()];
    }
    return ['ok' => true, 'msg' => 'Đã gửi báo giá ' . quote_code($id) . ' cho khách.'];
}

/** Nhân viên báo không báo giá được (bắt buộc lý do). */
function admin_quote_decline(int $id, int $staffId, string $reason): array
{
    $reason = trim($reason);
    if ($reason === '' || mb_strlen($reason, 'UTF-8') > 255) {
        return ['ok' => false, 'msg' => 'Hãy nhập lý do (tối đa 255 ký tự).'];
    }
    $n = db_exec(
        "UPDATE BAOGIA SET TrangThai = 'Không báo giá được', MaNVXuLy = ?, NgayTraLoi = NOW(), LyDoKhongBG = ?
          WHERE MaBG = ? AND TrangThai = 'Mới'",
        [$staffId, $reason, $id]
    );
    return $n === 1
        ? ['ok' => true, 'msg' => 'Đã báo khách: không báo giá được ' . quote_code($id) . '.']
        : ['ok' => false, 'msg' => 'Không tìm thấy báo giá ở trạng thái "Mới".'];
}

/* ============================== Bảo hành, đổi trả =========================== */

/** Danh sách yêu cầu; tìm theo mã BH, mã đơn, tên sản phẩm, tên khách; lọc trạng thái. */
function admin_warranties(string $q, string $status, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($status !== '' && in_array($status, WARRANTY_STATUSES, true)) {
        $where   .= ' AND h.TrangThai = ?';
        $params[] = $status;
    }
    $q = trim($q);
    if ($q !== '') {
        $where .= ' AND (s.TenSP LIKE ? OR u.HoTen LIKE ?';
        array_push($params, like_escape($q), like_escape($q));
        if (preg_match('/^BH/i', $q)) {
            $id = admin_code_id($q, 'BH');
            if ($id > 0) {
                $where   .= ' OR h.MaBH = ?';
                $params[] = $id;
            }
        } else {
            $id = parse_order_id($q);
            if ($id > 0 && preg_match('/^(DH)?\s*\d+$/i', $q)) {
                $where   .= ' OR h.MaDH = ?';
                $params[] = $id;
            }
        }
        $where .= ')';
    }
    $from  = 'FROM BAOHANH h JOIN DONHANG d ON d.MaDH = h.MaDH JOIN NGUOIDUNG u ON u.MaND = d.MaND JOIN SANPHAM s ON s.MaSP = h.MaSP';
    $total = (int) db_val("SELECT COUNT(*) $from WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT h.MaBH, h.MaDH, h.LoaiYeuCau, h.MoTa, h.TrangThai, h.NgayTao, h.PhanHoi, s.TenSP, u.HoTen $from
          WHERE $where ORDER BY (h.TrangThai = 'Mới') DESC, h.NgayTao DESC, h.MaBH DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Nhân viên phản hồi: bảo hành, đổi sản phẩm hoặc từ chối (từ chối bắt buộc có lý do). */
function admin_warranty_respond(int $id, int $staffId, string $result, string $feedback): array
{
    $feedback = trim($feedback);
    if (!in_array($result, ['Đã bảo hành', 'Đã đổi sản phẩm', 'Từ chối'], true)) {
        return ['ok' => false, 'msg' => 'Hãy chọn kết quả xử lý.'];
    }
    if ($result === 'Từ chối' && $feedback === '') {
        return ['ok' => false, 'msg' => 'Từ chối thì phải ghi lý do cho khách.'];
    }
    if (mb_strlen($feedback, 'UTF-8') > 500) {
        return ['ok' => false, 'msg' => 'Phản hồi tối đa 500 ký tự.'];
    }
    $n = db_exec(
        "UPDATE BAOHANH SET TrangThai = ?, MaNVXuLy = ?, PhanHoi = ?, NgayXuLy = NOW() WHERE MaBH = ? AND TrangThai = 'Mới'",
        [$result, $staffId, $feedback !== '' ? $feedback : null, $id]
    );
    return $n === 1
        ? ['ok' => true, 'msg' => 'Đã phản hồi yêu cầu ' . warranty_code($id) . '.']
        : ['ok' => false, 'msg' => 'Không tìm thấy yêu cầu ở trạng thái "Mới".'];
}

/* ============================== Đánh giá sản phẩm (MH24) ============================= */

/** Danh sách đánh giá của khách: tìm theo sản phẩm, khách, nội dung; lọc Chưa phản hồi / Đã phản hồi; phân trang. */
function admin_reviews(string $q, string $filter, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($filter === 'Chưa phản hồi') {
        $where .= ' AND d.PhanHoi IS NULL';
    } elseif ($filter === 'Đã phản hồi') {
        $where .= ' AND d.PhanHoi IS NOT NULL';
    }
    $q = trim($q);
    if ($q !== '') {
        $like   = like_escape($q);
        $where .= ' AND (s.TenSP LIKE ? OR s.MaHang LIKE ? OR n.HoTen LIKE ? OR d.NoiDung LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $total = (int) db_val(
        "SELECT COUNT(*) FROM DANHGIA d JOIN SANPHAM s ON s.MaSP = d.MaSP JOIN NGUOIDUNG n ON n.MaND = d.MaND WHERE $where",
        $params
    );
    $pg   = admin_paging($total, $perPage, $page);
    $rows = db_all(
        "SELECT d.MaDG, d.SoSao, d.NoiDung, d.NgayDG, d.PhanHoi, d.NgayPhanHoi, s.TenSP, s.MaHang, n.HoTen
           FROM DANHGIA d JOIN SANPHAM s ON s.MaSP = d.MaSP JOIN NGUOIDUNG n ON n.MaND = d.MaND
          WHERE $where ORDER BY (d.PhanHoi IS NOT NULL), d.NgayDG DESC, d.MaDG DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Nhân viên nhập (hoặc sửa) phản hồi của cửa hàng cho một đánh giá. Để trống là xóa phản hồi. */
function admin_review_reply(int $id, string $reply): array
{
    $reply = trim($reply);
    if ($id <= 0) {
        return ['ok' => false, 'msg' => 'Không tìm thấy đánh giá.'];
    }
    if (mb_strlen($reply, 'UTF-8') > 500) {
        return ['ok' => false, 'msg' => 'Phản hồi tối đa 500 ký tự.'];
    }
    if (!db_val('SELECT 1 FROM DANHGIA WHERE MaDG = ?', [$id])) {
        return ['ok' => false, 'msg' => 'Không tìm thấy đánh giá.'];
    }
    db_exec(
        'UPDATE DANHGIA SET PhanHoi = ?, NgayPhanHoi = ? WHERE MaDG = ?',
        $reply === '' ? [null, null, $id] : [$reply, date('Y-m-d H:i:s'), $id]
    );
    return ['ok' => true, 'msg' => $reply === '' ? 'Đã xóa phản hồi.' : 'Đã lưu phản hồi của cửa hàng.'];
}

/* ============================== Tin nhắn liên hệ ============================= */

/** Danh sách tin nhắn liên hệ: tìm theo tên, email, số điện thoại, nội dung; lọc Chưa xử lý / Đã xử lý; phân trang. */
function admin_contacts(string $q, string $filter, int $page, int $perPage = 15): array
{
    $where  = '1=1';
    $params = [];
    if ($filter === 'Chưa xử lý') {
        $where .= ' AND DaXuLy = 0';
    } elseif ($filter === 'Đã xử lý') {
        $where .= ' AND DaXuLy = 1';
    }
    $q = trim($q);
    if ($q !== '') {
        $like   = like_escape($q);
        $where .= ' AND (HoTen LIKE ? OR Email LIKE ? OR SoDienThoai LIKE ? OR NoiDung LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $total = (int) db_val("SELECT COUNT(*) FROM LIENHE WHERE $where", $params);
    $pg    = admin_paging($total, $perPage, $page);
    $rows  = db_all(
        "SELECT MaLH, HoTen, SoDienThoai, Email, NoiDung, NgayGui, DaXuLy
           FROM LIENHE WHERE $where ORDER BY DaXuLy, NgayGui DESC, MaLH DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $pg['offset'],
        $params
    );
    return ['rows' => $rows, 'total' => $total] + $pg;
}

/** Đổi tin nhắn giữa Chưa xử lý và Đã xử lý. */
function admin_contact_toggle(int $id): array
{
    $n = db_exec('UPDATE LIENHE SET DaXuLy = 1 - DaXuLy WHERE MaLH = ?', [$id]);
    return $n > 0 ? ['ok' => true, 'msg' => 'Đã cập nhật trạng thái tin nhắn.'] : ['ok' => false, 'msg' => 'Không tìm thấy tin nhắn.'];
}
