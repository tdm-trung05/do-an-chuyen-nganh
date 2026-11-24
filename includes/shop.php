<?php
/**
 * Lớp dữ liệu khu bán hàng (giai đoạn 2): danh mục, tìm kiếm, chi tiết sản phẩm,
 * giỏ hàng, yêu thích, đơn hàng. Mọi truy vấn dùng prepared statement qua includes/db.php.
 *
 * Quy ước: hàm trả về sản phẩm luôn có cùng các khóa (id, code, name, category, brand, series,
 * price, old_price, stock, warranty, rating, tag, image) để product_card() dùng như dữ liệu mẫu cũ.
 */

/* ============================== Hằng, tiện ích ============================== */

const SHOP_CATEGORY_ICONS = ['bi-toggles', 'bi-lightning-charge', 'bi-shield-check', 'bi-hdd-rack', 'bi-box-seam', 'bi-droplet', 'bi-plug'];

/** Mã đơn hiển thị: 125 => DH000125 */
function order_code(int $id): string
{
    return 'DH' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

/** Đọc số nguyên dương từ chuỗi như "DH000125", "125". */
function parse_order_id($raw): int
{
    return (int) preg_replace('/\D/', '', (string) $raw);
}

/** Nhãn tồn kho theo số lượng. */
function stock_label(int $stock, int $threshold = 10): string
{
    return $stock <= 0 ? 'Hết hàng' : ($stock < $threshold ? 'Sắp hết' : 'Còn hàng');   // cùng quy tắc admin_stock_label()
}

/** Lớp CSS theo mức tồn, dùng cùng ngưỡng cảnh báo như phía quản trị. */
function stock_class(array $p): string
{
    return $p['stock'] <= 0 ? 'out' : ($p['stock'] < $p['low_at'] ? 'low' : 'in');
}

/** Ảnh sản phẩm: dùng ảnh tải lên nếu tệp tồn tại, không thì ảnh giữ chỗ. */
function product_image(?string $path): string
{
    if ($path !== null && $path !== '' && is_file(ROOT_PATH . '/' . ltrim($path, '/'))) {
        return url($path);
    }
    return asset('images/placeholder-product.svg');
}

/** Chuỗi dùng trong LIKE, đã thoát % _ \ */
function like_escape(string $s): string
{
    return '%' . addcslashes($s, '%_\\') . '%';
}

/** Danh sách số nguyên dương duy nhất từ mảng đầu vào (bộ lọc th[], dong[]...). */
function int_list($raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $v) {
        $i = (int) $v;
        if ($i > 0) {
            $out[$i] = $i;
        }
    }
    return array_values($out);
}

/** Cửa hàng chỉ bán hàng Legrand, nên thương hiệu hiển thị cố định (không còn bảng THUONGHIEU). */
const SHOP_BRAND = 'Legrand';

/* ================================= Danh mục ================================= */

/**
 * Danh mục gốc kèm số sản phẩm đang bán (tính cả danh mục con) và danh mục con.
 * @return array<int, array{id:int,name:string,icon:string,count:int,children:array}>
 */
function shop_categories(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $rows = db_all(
        'SELECT d.MaDM, d.TenDM, d.MaDMCha,
                (SELECT COUNT(*) FROM SANPHAM s WHERE s.TrangThai = 1 AND s.MaDM = d.MaDM) AS Dem
           FROM DANHMUC d ORDER BY d.MaDM'
    );
    $roots = [];
    $children = [];
    foreach ($rows as $r) {
        if ($r['MaDMCha'] === null) {
            $roots[(int) $r['MaDM']] = ['id' => (int) $r['MaDM'], 'name' => $r['TenDM'], 'own' => (int) $r['Dem'], 'children' => []];
        } else {
            $children[] = $r;
        }
    }
    foreach ($children as $c) {
        $pid = (int) $c['MaDMCha'];
        if (isset($roots[$pid])) {
            $roots[$pid]['children'][] = ['id' => (int) $c['MaDM'], 'name' => $c['TenDM'], 'count' => (int) $c['Dem']];
        }
    }
    $i = 0;
    $cache = [];
    foreach ($roots as $r) {
        $count = $r['own'];
        foreach ($r['children'] as $c) {
            $count += $c['count'];
        }
        $cache[] = [
            'id'       => $r['id'],
            'name'     => $r['name'],
            'icon'     => SHOP_CATEGORY_ICONS[$i % count(SHOP_CATEGORY_ICONS)],
            'count'    => $count,
            'children' => $r['children'],
        ];
        $i++;
    }
    return $cache;
}

/** Tên danh mục (cả gốc lẫn con) theo mã, hoặc null. */
function shop_category_name(int $id): ?string
{
    $v = db_val('SELECT TenDM FROM DANHMUC WHERE MaDM = ?', [$id]);
    return $v === null ? null : (string) $v;
}

/** @return array<int, array{id:int,name:string,brand:string}> */
function shop_series(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = array_map(
            static fn($r) => ['id' => (int) $r['MaDong'], 'name' => $r['TenDong'], 'brand' => SHOP_BRAND],
            db_all('SELECT MaDong, TenDong FROM DONGSANPHAM ORDER BY TenDong')
        );
    }
    return $cache;
}

/* ================================== Sản phẩm ================================ */

/** Phần SELECT ... FROM dùng chung cho danh sách và chi tiết. */
function product_base_sql(): string
{
    return 'SELECT s.MaSP, s.MaHang, s.TenSP, s.MaDM, s.MaDong, s.GiaNiemYet, s.GiaBan, s.LienHeBaoGia,
                   s.ThoiGianBH, s.SoNgayDoiTra, s.MoTa, s.TrangThai, s.TinhTrangKD,
                   d.TenDM, g.TenDong,
                   COALESCE(k.SoLuongTon, 0) AS SoLuongTon,
                   COALESCE(k.NguongCanhBao, 10) AS NguongCanhBao,
                   COALESCE(r.TB, 0) AS DiemTB, COALESCE(r.SoDG, 0) AS SoDG,
                   (SELECT h.DuongDan FROM HINHANH h WHERE h.MaSP = s.MaSP
                     ORDER BY h.LaAnhChinh DESC, h.MaHA LIMIT 1) AS Anh
              FROM SANPHAM s
              JOIN DANHMUC d     ON d.MaDM = s.MaDM
              LEFT JOIN DONGSANPHAM g ON g.MaDong = s.MaDong
              LEFT JOIN TONKHO k ON k.MaSP = s.MaSP
              LEFT JOIN (SELECT MaSP, AVG(SoSao) AS TB, COUNT(*) AS SoDG FROM DANHGIA GROUP BY MaSP) r ON r.MaSP = s.MaSP';
}

/** Chuẩn hóa một dòng CSDL thành mảng dùng cho giao diện. */
function product_row(array $r): array
{
    $quote = (int) $r['LienHeBaoGia'] === 1 || $r['GiaNiemYet'] === null;
    $stock = (int) $r['SoLuongTon'];
    return [
        'id'         => (int) $r['MaSP'],
        'code'       => $r['MaHang'],
        'name'       => $r['TenSP'],
        'category'   => $r['TenDM'],
        'category_id'=> (int) $r['MaDM'],
        'brand'      => SHOP_BRAND,
        'series'     => (string) ($r['TenDong'] ?? ''),
        'series_id'  => $r['MaDong'] === null ? null : (int) $r['MaDong'],
        'price'      => $quote ? null : (int) ($r['GiaBan'] ?? $r['GiaNiemYet']),
        'old_price'  => (!$quote && $r['GiaBan'] !== null && (int) $r['GiaBan'] < (int) $r['GiaNiemYet']) ? (int) $r['GiaNiemYet'] : null,
        'stock'      => $stock,
        'low_at'     => max(0, (int) $r['NguongCanhBao']),
        'warranty'   => (int) $r['ThoiGianBH'],
        'return_days'=> (int) $r['SoNgayDoiTra'],
        'rating'     => (int) round((float) $r['DiemTB']),
        'rating_avg' => round((float) $r['DiemTB'], 1),
        'rating_n'   => (int) $r['SoDG'],
        'tag'        => $quote ? 'Báo giá' : '',
        'desc'       => (string) ($r['MoTa'] ?? ''),
        'image'      => product_image($r['Anh'] ?? null),
        'visible'    => (int) $r['TrangThai'] === 1,
        'status'     => (string) ($r['TinhTrangKD'] ?? 'Đang kinh doanh'),   // Đang kinh doanh / Ngừng kinh doanh / Hàng sắp về
    ];
}

/** Một sản phẩm theo mã (null nếu không có). $onlyVisible=false cho phép xem cả hàng đã ẩn. */
function shop_product(int $id, bool $onlyVisible = true): ?array
{
    $row = db_one(product_base_sql() . ' WHERE s.MaSP = ?' . ($onlyVisible ? ' AND s.TrangThai = 1' : ''), [$id]);
    return $row ? product_row($row) : null;
}

/** Nhiều sản phẩm theo danh sách mã, giữ thứ tự đầu vào. */
function shop_products_by_ids(array $ids): array
{
    $ids = int_list($ids);
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(product_base_sql() . " WHERE s.TrangThai = 1 AND s.MaSP IN ($in)", $ids);
    $by = [];
    foreach ($rows as $r) {
        $by[(int) $r['MaSP']] = product_row($r);
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($by[$id])) {
            $out[] = $by[$id];
        }
    }
    return $out;
}

/** Sản phẩm theo MaHang chính xác (không phân biệt hoa thường). */
function shop_product_by_code(string $code): ?array
{
    $row = db_one(product_base_sql() . ' WHERE s.MaHang = ? AND s.TrangThai = 1', [$code]);
    return $row ? product_row($row) : null;
}

/**
 * Tìm và lọc sản phẩm đang bán.
 * $f: q, danh-muc, th[], dong[], gia_tu, gia_den, con_hang, bao_gia, sort
 * @return array{rows:array,total:int,pages:int,page:int}
 */
function shop_search(array $f, int $page = 1, int $perPage = PAGE_SIZE): array
{
    $where  = ['s.TrangThai = 1'];
    $params = [];

    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $where[]  = '(s.MaHang LIKE ? OR s.TenSP LIKE ?)';
        $params[] = like_escape($q);
        $params[] = like_escape($q);
    }
    $dm = (int) ($f['danh-muc'] ?? 0);
    if ($dm > 0) {
        $where[]  = '(s.MaDM = ? OR s.MaDM IN (SELECT MaDM FROM DANHMUC WHERE MaDMCha = ?))';
        $params[] = $dm;
        $params[] = $dm;
    }
    foreach (['dong' => 's.MaDong'] as $key => $col) {
        $list = int_list($f[$key] ?? []);
        if ($list) {
            $where[] = $col . ' IN (' . implode(',', array_fill(0, count($list), '?')) . ')';
            array_push($params, ...$list);
        }
    }
    $from = trim((string) ($f['gia_tu'] ?? ''));
    $to   = trim((string) ($f['gia_den'] ?? ''));
    if ($from !== '' && ctype_digit($from)) {
        $where[]  = '(s.LienHeBaoGia = 0 AND s.GiaBan >= ?)';
        $params[] = (int) $from;
    }
    if ($to !== '' && ctype_digit($to)) {
        $where[]  = '(s.LienHeBaoGia = 0 AND s.GiaBan <= ?)';
        $params[] = (int) $to;
    }
    if (!empty($f['con_hang'])) {
        $where[] = 'COALESCE(k.SoLuongTon, 0) > 0';
    }
    if (!empty($f['bao_gia'])) {
        $where[] = 's.LienHeBaoGia = 1';
    }

    $sorts = [
        'moi'       => 's.MaSP DESC',
        'gia-tang'  => 's.LienHeBaoGia ASC, s.GiaBan ASC, s.MaSP DESC',
        'gia-giam'  => 's.LienHeBaoGia ASC, s.GiaBan DESC, s.MaSP DESC',
        'ban-chay'  => 's.DaBan DESC, s.MaSP DESC',
        'xem-nhieu' => 's.LuotXem DESC, s.MaSP DESC',
        'ma'        => 's.MaHang ASC',
    ];
    $sortKey = (string) ($f['sort'] ?? 'moi');
    $order   = $sorts[$sortKey] ?? $sorts['moi'];

    $whereSql = implode(' AND ', $where);
    $total = (int) db_val(
        "SELECT COUNT(*) FROM SANPHAM s LEFT JOIN TONKHO k ON k.MaSP = s.MaSP WHERE $whereSql",
        $params
    );
    $perPage = max(1, $perPage);
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = min(max(1, $page), $pages);
    $offset  = ($page - 1) * $perPage;

    // LIMIT/OFFSET được ép kiểu số nguyên rồi nối trực tiếp (không thể dùng dấu ? với prepared statement thật)
    $sql = "SELECT s.MaSP FROM SANPHAM s
              LEFT JOIN TONKHO k ON k.MaSP = s.MaSP
             WHERE $whereSql
             ORDER BY " . $order . '
             LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $ids = array_map('intval', array_column(db_all($sql, $params), 'MaSP'));

    return ['rows' => shop_products_by_ids($ids), 'total' => $total, 'pages' => $pages, 'page' => $page];
}

/** Sản phẩm bán chạy (theo số lượng đã đặt, bỏ đơn hủy); bổ sung bằng hàng mới nếu chưa đủ. */
function shop_featured(int $limit = 8): array
{
    $limit = max(1, $limit);
    $ids = array_map('intval', array_column(db_all(
        "SELECT s.MaSP FROM SANPHAM s
          WHERE s.TrangThai = 1
          ORDER BY s.DaBan DESC, s.MaSP DESC
          LIMIT " . $limit
    ), 'MaSP'));
    return shop_products_by_ids($ids);
}

/** Các mã hàng cùng dòng và cùng danh mục (màu mặt, số module khác nhau). */
function shop_variants(array $p): array
{
    if ($p['series_id'] === null) {
        return [];
    }
    return db_all(
        'SELECT MaSP AS id, MaHang AS code FROM SANPHAM
          WHERE TrangThai = 1 AND MaDong = ? AND MaDM = ? ORDER BY MaHang',
        [$p['series_id'], $p['category_id']]
    );
}

/** Sản phẩm liên quan: ưu tiên danh sách quản trị chọn (SANPHAM_LIENQUAN), rồi cùng dòng, rồi cùng danh mục. */
function shop_related(array $p, int $limit = 4): array
{
    $ids = [];
    foreach (db_all(
        'SELECT l.MaSPLienQuan FROM SANPHAM_LIENQUAN l JOIN SANPHAM s ON s.MaSP = l.MaSPLienQuan
          WHERE l.MaSP = ? AND s.TrangThai = 1 ORDER BY l.MaSPLienQuan LIMIT ' . (int) $limit,
        [$p['id']]
    ) as $r) {
        $ids[] = (int) $r['MaSPLienQuan'];
    }
    if ($p['series_id'] !== null && count($ids) < $limit) {
        foreach (db_all('SELECT MaSP FROM SANPHAM WHERE TrangThai = 1 AND MaDong = ? AND MaSP <> ? ORDER BY MaSP LIMIT ' . (int) $limit, [$p['series_id'], $p['id']]) as $r) {
            $id = (int) $r['MaSP'];
            if (!in_array($id, $ids, true) && count($ids) < $limit) {
                $ids[] = $id;
            }
        }
    }
    if (count($ids) < $limit) {
        foreach (db_all('SELECT MaSP FROM SANPHAM WHERE TrangThai = 1 AND MaDM = ? AND MaSP <> ? ORDER BY MaSP DESC LIMIT ' . (int) ($limit * 2), [$p['category_id'], $p['id']]) as $r) {
            $id = (int) $r['MaSP'];
            if (!in_array($id, $ids, true) && count($ids) < $limit) {
                $ids[] = $id;
            }
        }
    }
    return shop_products_by_ids($ids);
}

/** Thông số kỹ thuật: [[tên, giá trị kèm đơn vị], ...] */
function shop_specs(int $productId): array
{
    $rows = db_all(
        'SELECT t.TenTS, t.DonVi, c.GiaTri FROM CT_THONGSO c JOIN THONGSO t ON t.MaTS = c.MaTS
          WHERE c.MaSP = ? ORDER BY t.MaTS',
        [$productId]
    );
    return array_map(static fn($r) => [$r['TenTS'], $r['GiaTri'] . ($r['DonVi'] ? ' ' . $r['DonVi'] : '')], $rows);
}

/** Tài liệu kỹ thuật của sản phẩm và của dòng sản phẩm. */
function shop_documents(array $p): array
{
    $rows = db_all(
        'SELECT TenTL, DuongDan FROM TAILIEU WHERE MaSP = ? OR (MaDong IS NOT NULL AND MaDong = ?) ORDER BY MaTL',
        [$p['id'], $p['series_id'] ?? 0]
    );
    $out = [];
    foreach ($rows as $r) {
        $file   = ROOT_PATH . '/' . ltrim($r['DuongDan'], '/');
        $exists = is_file($file);
        $out[] = [
            'title'  => $r['TenTL'],
            'type'   => strtoupper(pathinfo($r['DuongDan'], PATHINFO_EXTENSION)) ?: 'FILE',
            'size'   => $exists ? number_format(filesize($file) / 1048576, 1, ',', '.') . ' MB' : '',
            'url'    => $exists ? url($r['DuongDan']) : null,
        ];
    }
    return $out;
}

/** Các cột phụ của trang chi tiết (MH03): giá niêm yết, % giảm, xuất xứ, năm ra mắt, phụ kiện, video, mô tả ngắn, hình thức bảo hành, hậu mãi. */
function shop_product_extra(int $id): array
{
    $r = db_one('SELECT PhanTramGiam, XuatXu, NamRaMat, PhuKien, VideoUrl, MoTaNgan, HinhThucBH, ChinhSachHauMai FROM SANPHAM WHERE MaSP = ?', [$id]) ?? [];
    $video = (string) ($r['VideoUrl'] ?? '');
    return [
        'percent'   => (float) ($r['PhanTramGiam'] ?? 0),
        'origin'    => (string) ($r['XuatXu'] ?? ''),
        'year'      => $r['NamRaMat'] ?? null,
        'accessory' => (string) ($r['PhuKien'] ?? ''),
        'video'     => preg_match('#^https?://#i', $video) ? $video : '',   // chỉ nhận liên kết http(s)
        'short'     => (string) ($r['MoTaNgan'] ?? ''),
        'wtype'     => (string) ($r['HinhThucBH'] ?? ''),
        'aftercare' => (string) ($r['ChinhSachHauMai'] ?? ''),
    ];
}

/** Ảnh của sản phẩm (đường dẫn đầy đủ); luôn có ít nhất một ảnh giữ chỗ. */
function shop_images(int $productId): array
{
    $out = [];
    foreach (db_all('SELECT DuongDan FROM HINHANH WHERE MaSP = ? ORDER BY LaAnhChinh DESC, MaHA', [$productId]) as $r) {
        $u = product_image($r['DuongDan']);
        if (!in_array($u, $out, true)) {
            $out[] = $u;
        }
    }
    return $out ?: [asset('images/placeholder-product.svg')];
}

/* ================================ Đánh giá ================================== */

function shop_reviews(int $productId, int $limit = 20): array
{
    return db_all(
        'SELECT n.HoTen, d.SoSao, d.NoiDung, d.NgayDG, d.PhanHoi, d.NgayPhanHoi
           FROM DANHGIA d JOIN NGUOIDUNG n ON n.MaND = d.MaND
          WHERE d.MaSP = ? ORDER BY d.NgayDG DESC, d.MaDG DESC LIMIT ' . (int) $limit,
        [$productId]
    );
}

/** Khách chỉ được đánh giá sản phẩm đã mua và nhận (đơn hoàn tất). */
function review_state(int $userId, int $productId): string
{
    $bought = db_val(
        "SELECT 1 FROM CT_DONHANG c JOIN DONHANG d ON d.MaDH = c.MaDH
          WHERE d.MaND = ? AND c.MaSP = ? AND d.TrangThai = 'Hoàn tất' LIMIT 1",
        [$userId, $productId]
    );
    if (!$bought) {
        return 'chua-mua';
    }
    $done = db_val('SELECT 1 FROM DANHGIA WHERE MaND = ? AND MaSP = ? LIMIT 1', [$userId, $productId]);
    return $done ? 'da-danh-gia' : 'duoc-danh-gia';
}

/* ================================== Giỏ hàng ================================ */

/** Mã giỏ hàng của khách; tạo mới nếu $create. */
function cart_id(int $userId, bool $create = false): ?int
{
    $id = db_val('SELECT MaGH FROM GIOHANG WHERE MaND = ?', [$userId]);
    if ($id === null && $create) {
        db_exec('INSERT IGNORE INTO GIOHANG (MaND) VALUES (?)', [$userId]);
        $id = db_val('SELECT MaGH FROM GIOHANG WHERE MaND = ?', [$userId]);
    }
    return $id === null ? null : (int) $id;
}

/**
 * Dòng giỏ hàng kèm sản phẩm và tình trạng mua được hay không.
 * @return array<int, array{product:array,qty:int,line:int,problem:string}>
 */
function cart_items(int $userId): array
{
    $gh = cart_id($userId);
    if ($gh === null) {
        return [];
    }
    $lines = db_all('SELECT MaSP, SoLuong FROM CT_GIOHANG WHERE MaGH = ? ORDER BY MaSP', [$gh]);
    $qtyBy = [];
    foreach ($lines as $l) {
        $qtyBy[(int) $l['MaSP']] = (int) $l['SoLuong'];
    }
    $in = $qtyBy ? implode(',', array_fill(0, count($qtyBy), '?')) : '';
    $rows = $qtyBy ? db_all(product_base_sql() . " WHERE s.MaSP IN ($in)", array_keys($qtyBy)) : [];
    $out = [];
    foreach ($rows as $r) {
        $p   = product_row($r);
        $qty = $qtyBy[$p['id']];
        $problem = '';
        if (!$p['visible']) {
            $problem = 'Sản phẩm đã ngừng bán.';
        } elseif ($p['status'] === 'Ngừng kinh doanh') {
            $problem = 'Sản phẩm đã ngừng kinh doanh.';
        } elseif ($p['status'] === 'Hàng sắp về') {
            $problem = 'Sản phẩm sắp về hàng, chưa đặt được.';
        } elseif ($p['price'] === null) {
            $problem = 'Sản phẩm này bán theo báo giá, hãy gửi yêu cầu báo giá.';
        } elseif ($p['stock'] <= 0) {
            $problem = 'Tạm hết hàng.';
        } elseif ($qty > $p['stock']) {
            $problem = 'Chỉ còn ' . $p['stock'] . ' sản phẩm trong kho.';
        }
        $out[] = ['product' => $p, 'qty' => $qty, 'line' => ($p['price'] ?? 0) * $qty, 'problem' => $problem];
    }
    usort($out, static fn($a, $b) => $a['product']['id'] <=> $b['product']['id']);
    return $out;
}

/** Tổng số lượng trong giỏ của khách đang đăng nhập (0 nếu chưa đăng nhập hoặc là nhân viên). */
function cart_count(): int
{
    static $n = null;
    if ($n !== null) {
        return $n;
    }
    $u = auth_user();
    if ($u === null || $u['role'] !== 'KH') {
        return $n = 0;
    }
    return $n = (int) db_val(
        'SELECT COALESCE(SUM(c.SoLuong), 0) FROM CT_GIOHANG c JOIN GIOHANG g ON g.MaGH = c.MaGH WHERE g.MaND = ?',
        [(int) $u['MaND']]
    );
}

/** Số lượng đang có của một sản phẩm trong giỏ. */
function cart_qty(int $userId, int $productId): int
{
    return (int) db_val(
        'SELECT COALESCE(SUM(c.SoLuong), 0) FROM CT_GIOHANG c JOIN GIOHANG g ON g.MaGH = c.MaGH WHERE g.MaND = ? AND c.MaSP = ?',
        [$userId, $productId]
    );
}

/**
 * Kiểm tra sản phẩm có bỏ vào giỏ được không. Trả về chuỗi lỗi, '' nếu được.
 */
function cart_check_product(?array $p): string
{
    if ($p === null || !$p['visible']) {
        return 'Sản phẩm không tồn tại hoặc đã ngừng bán.';
    }
    if ($p['status'] === 'Ngừng kinh doanh') {
        return 'Sản phẩm đã ngừng kinh doanh.';
    }
    if ($p['status'] === 'Hàng sắp về') {
        return 'Sản phẩm sắp về hàng, chưa đặt được.';
    }
    if ($p['price'] === null) {
        return 'Sản phẩm này bán theo báo giá, không thêm vào giỏ được. Hãy gửi yêu cầu báo giá.';
    }
    if ($p['stock'] <= 0) {
        return 'Sản phẩm tạm hết hàng.';
    }
    return '';
}

/** Thêm vào giỏ (cộng dồn). Trả về ['ok'=>bool,'msg'=>string]. */
function cart_add(int $userId, int $productId, int $qty): array
{
    $qty = max(1, min(999, $qty));
    $p   = shop_product($productId);
    $err = cart_check_product($p);
    if ($err !== '') {
        return ['ok' => false, 'msg' => $err];
    }
    $gh   = cart_id($userId, true);
    $have = cart_qty($userId, $productId);
    $want = $have + $qty;
    $note = '';
    if ($want > $p['stock']) {
        $want = $p['stock'];
        $note = ' (đã giới hạn theo tồn kho: ' . $p['stock'] . ')';
        if ($want <= $have) {
            return ['ok' => false, 'msg' => 'Bạn đã có tối đa số lượng còn trong kho (' . $p['stock'] . ') trong giỏ.'];
        }
    }
    db_exec(
        'INSERT INTO CT_GIOHANG (MaGH, MaSP, SoLuong) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE SoLuong = VALUES(SoLuong)',
        [$gh, $productId, $want]
    );
    return ['ok' => true, 'msg' => 'Đã thêm “' . $p['name'] . '” vào giỏ hàng' . $note . '.'];
}

/** Đặt số lượng (<=0 là xóa). Giữ trong khoảng tồn kho. */
function cart_set(int $userId, int $productId, int $qty): array
{
    $gh = cart_id($userId);
    if ($gh === null) {
        return ['ok' => false, 'msg' => 'Giỏ hàng trống.'];
    }
    if ($qty <= 0) {
        db_exec('DELETE FROM CT_GIOHANG WHERE MaGH = ? AND MaSP = ?', [$gh, $productId]);
        return ['ok' => true, 'msg' => 'Đã xóa sản phẩm khỏi giỏ hàng.'];
    }
    $p = shop_product($productId, false);
    if ($p === null) {
        return ['ok' => false, 'msg' => 'Sản phẩm không tồn tại.'];
    }
    $qty = min(999, $qty);
    $note = '';
    if ($p['stock'] > 0 && $qty > $p['stock']) {
        $qty  = $p['stock'];
        $note = ' Số lượng đã giới hạn theo tồn kho.';
    }
    db_exec('UPDATE CT_GIOHANG SET SoLuong = ? WHERE MaGH = ? AND MaSP = ?', [$qty, $gh, $productId]);
    return ['ok' => true, 'msg' => 'Đã cập nhật giỏ hàng.' . $note];
}

function cart_remove(int $userId, int $productId): array
{
    return cart_set($userId, $productId, 0);
}

/* ================================== Yêu thích =============================== */

/** Tập mã sản phẩm yêu thích của khách đang đăng nhập (dùng đệm trong một request). */
function wish_ids(bool $refresh = false): array
{
    static $ids = null;
    if ($ids !== null && !$refresh) {
        return $ids;
    }
    $u = auth_user();
    if ($u === null || $u['role'] !== 'KH') {
        return $ids = [];
    }
    return $ids = array_map('intval', array_column(
        db_all('SELECT MaSP FROM YEUTHICH WHERE MaND = ? ORDER BY NgayThem DESC', [(int) $u['MaND']]),
        'MaSP'
    ));
}

/** Bật/tắt yêu thích. Trả về ['ok'=>bool,'added'=>bool,'msg'=>string]. */
function wish_toggle(int $userId, int $productId): array
{
    if (shop_product($productId, false) === null) {
        return ['ok' => false, 'added' => false, 'msg' => 'Sản phẩm không tồn tại.'];
    }
    $removed = db_exec('DELETE FROM YEUTHICH WHERE MaND = ? AND MaSP = ?', [$userId, $productId]);
    if ($removed > 0) {
        return ['ok' => true, 'added' => false, 'msg' => 'Đã bỏ khỏi danh sách yêu thích.'];
    }
    db_exec('INSERT IGNORE INTO YEUTHICH (MaND, MaSP) VALUES (?, ?)', [$userId, $productId]);
    return ['ok' => true, 'added' => true, 'msg' => 'Đã thêm vào danh sách yêu thích.'];
}

/* ================================== Địa chỉ ================================= */

function user_addresses(int $userId): array
{
    return db_all(
        'SELECT MaDC, NguoiNhan, SoDienThoai, DiaChiChiTiet, MacDinh FROM DIACHI WHERE MaND = ? ORDER BY MacDinh DESC, MaDC',
        [$userId]
    );
}

/** Số điện thoại Việt Nam: bỏ khoảng trắng, chấm, gạch; chấp nhận 0xxxxxxxxx hoặc +84xxxxxxxxx. Trả về '' nếu sai. */
function normalize_phone(string $raw): string
{
    $p = preg_replace('/[\s.\-]/', '', $raw);
    if (preg_match('/^\+84(\d{9})$/', $p, $m)) {
        $p = '0' . $m[1];
    }
    return preg_match('/^0\d{9,10}$/', $p) ? $p : '';
}

/* ================================== Đơn hàng ================================ */

const ORDER_STATUSES = ['Chờ thanh toán', 'Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Hoàn tất', 'Đã hủy'];

/** Dịch thông báo lỗi của thủ tục lưu trữ (viết không dấu trong schema.sql) sang tiếng Việt. */
function db_error_message(PDOException $e): string
{
    $m = (string) ($e->errorInfo[2] ?? $e->getMessage());
    $map = [
        'Gio hang trong'                         => 'Giỏ hàng đang trống.',
        'Dia chi giao hang khong hop le'         => 'Địa chỉ giao hàng không hợp lệ.',
        'Gio hang co san pham chua niem yet'     => 'Giỏ hàng có sản phẩm bán theo báo giá hoặc đã ngừng bán. Hãy xóa sản phẩm đó khỏi giỏ.',
        'Don hang khong the huy'                 => 'Đơn hàng không thể hủy ở trạng thái hiện tại.',
        'Khach hang phai lien he'                => 'Đơn đã được xác nhận. Vui lòng liên hệ cửa hàng để hủy đơn.',
        'Gia san pham da thay doi'               => 'Giá một số sản phẩm đã thay đổi. Giỏ hàng đã được cập nhật giá mới, vui lòng kiểm tra lại rồi đặt hàng.',
        'Khong tim thay don hang'                => 'Không tìm thấy đơn hàng.',
        'Chuyen trang thai don hang khong hop le'=> 'Không thể chuyển trạng thái đơn hàng như vậy.',
    ];
    foreach ($map as $needle => $vi) {
        if (strpos($m, $needle) !== false) {
            return $vi;
        }
    }
    if (strpos($m, 'Khong du ton kho') !== false) {
        return 'Không đủ tồn kho: ' . trim(substr($m, strlen('Khong du ton kho:')));
    }
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        return 'Dữ liệu này đã tồn tại (trùng với bản ghi có sẵn).';
    }
    error_log('[DB] ' . $m);
    return 'Không thực hiện được do lỗi hệ thống. Vui lòng thử lại sau.';
}

/**
 * Đặt hàng từ giỏ (gọi thủ tục sp_DatHang: khóa tồn, tạo đơn, trừ tồn, xóa giỏ trong một giao dịch).
 * @return array{ok:bool,id:int,msg:string}
 */
function place_order(int $userId, int $addressId, string $method, string $note = ''): array
{
    $method = $method === 'Chuyển khoản' ? 'Chuyển khoản' : 'Thanh toán khi nhận hàng';
    try {
        $st = db()->prepare('CALL sp_DatHang(?, ?, ?, ?, ?, @ma_dh)');
        $st->execute([$userId, $addressId, $method, (int) SHIPPING_FEE, (int) PAYMENT_HOLD_HOURS]);   // phí giao mặc định, nhân viên có thể chỉnh sau
        $st->closeCursor();
        $id = (int) db_val('SELECT @ma_dh');
    } catch (PDOException $e) {
        return ['ok' => false, 'id' => 0, 'msg' => db_error_message($e)];
    }
    if ($id <= 0) {
        return ['ok' => false, 'id' => 0, 'msg' => 'Không tạo được đơn hàng. Vui lòng thử lại.'];
    }
    $note = trim($note);
    if ($note !== '') {
        db_exec(
            'UPDATE LICHSU_DONHANG SET GhiChu = ? WHERE MaDH = ? AND TrangThaiCu IS NULL',
            [mb_substr('Tạo đơn. Ghi chú của khách: ' . $note, 0, 250, 'UTF-8'), $id]
        );
    }
    return ['ok' => true, 'id' => $id, 'msg' => ''];
}

/** Khách tự hủy đơn (thủ tục sp_HuyDon kiểm tra trạng thái và cộng lại tồn kho). */
function cancel_order(int $userId, int $orderId, string $reason = 'Khách hàng hủy đơn'): array
{
    $own = db_val('SELECT 1 FROM DONHANG WHERE MaDH = ? AND MaND = ?', [$orderId, $userId]);
    if (!$own) {
        return ['ok' => false, 'msg' => 'Không tìm thấy đơn hàng.'];
    }
    try {
        $st = db()->prepare('CALL sp_HuyDon(?, ?, ?)');
        $st->execute([$orderId, $userId, $reason]);
        $st->closeCursor();
    } catch (PDOException $e) {
        return ['ok' => false, 'msg' => db_error_message($e)];
    }
    return ['ok' => true, 'msg' => 'Đã hủy đơn ' . order_code($orderId) . '.'];
}

/** Khách chỉ tự hủy được đơn chưa xác nhận. */
function order_customer_can_cancel(string $status): bool
{
    return in_array($status, ['Chờ thanh toán', 'Chờ xác nhận'], true);
}

/**
 * Danh sách đơn của khách (có lọc trạng thái, phân trang).
 * @return array{rows:array,total:int,pages:int,page:int}
 */
function orders_of_user(int $userId, string $status = '', int $page = 1, int $perPage = 10): array
{
    $where  = 'd.MaND = ?';
    $params = [$userId];
    if ($status !== '' && in_array($status, ORDER_STATUSES, true)) {
        $where   .= ' AND d.TrangThai = ?';
        $params[] = $status;
    }
    $total   = (int) db_val("SELECT COUNT(*) FROM DONHANG d WHERE $where", $params);
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = min(max(1, $page), $pages);
    $rows = db_all(
        "SELECT d.MaDH, d.NgayDat, d.TrangThai, d.TongTien,
                (SELECT COALESCE(SUM(c.SoLuong), 0) FROM CT_DONHANG c WHERE c.MaDH = d.MaDH) AS SoSP,
                (SELECT t.PhuongThuc FROM THANHTOAN t WHERE t.MaDH = d.MaDH ORDER BY t.MaTT LIMIT 1) AS PhuongThuc
           FROM DONHANG d WHERE $where
          ORDER BY d.NgayDat DESC, d.MaDH DESC
          LIMIT " . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage),
        $params
    );
    return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

/** Đơn hàng đầy đủ của khách (null nếu không phải đơn của khách đó). */
function order_of_user(int $userId, int $orderId): ?array
{
    $o = db_one('SELECT * FROM DONHANG WHERE MaDH = ? AND MaND = ?', [$orderId, $userId]);
    if ($o === null) {
        return null;
    }
    $o['items'] = db_all(
        'SELECT c.MaSP, c.SoLuong, c.DonGia, s.MaHang, s.TenSP, s.ThoiGianBH, s.SoNgayDoiTra
           FROM CT_DONHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaDH = ? ORDER BY c.MaSP',
        [$orderId]
    );
    $o['payment'] = db_one('SELECT PhuongThuc, TrangThai, ThoiGian FROM THANHTOAN WHERE MaDH = ? ORDER BY MaTT LIMIT 1', [$orderId]);
    $o['history'] = db_all(
        'SELECT TrangThaiCu, TrangThaiMoi, ThoiGian, GhiChu FROM LICHSU_DONHANG WHERE MaDH = ? ORDER BY ThoiGian, MaLS',
        [$orderId]
    );
    return $o;
}

/* ================================= Tin tức ================================== */

/** Tóm tắt ngắn lấy từ nội dung bài viết (bỏ thẻ HTML, cắt ở $len ký tự). */
function news_summary(string $body, int $len = 160): string
{
    $t = trim((string) preg_replace('/\s+/u', ' ', strip_tags($body)));
    return mb_strlen($t, 'UTF-8') > $len ? rtrim(mb_substr($t, 0, $len, 'UTF-8')) . '…' : $t;
}

function news_base_sql(): string
{
    return 'SELECT t.MaTin, t.TieuDe, t.NoiDung, t.NgayDang, t.TrangThai, u.HoTen
              FROM TINTUC t JOIN NGUOIDUNG u ON u.MaND = t.MaND';
}

/** Chuẩn hóa một dòng TINTUC cho giao diện. */
function news_row(array $r): array
{
    return [
        'id'      => (int) $r['MaTin'],
        'title'   => $r['TieuDe'],
        'body'    => (string) $r['NoiDung'],
        'summary' => news_summary((string) $r['NoiDung']),
        'date'    => date('d/m/Y', strtotime($r['NgayDang'])),
        'author'  => $r['HoTen'],
        'visible' => (int) $r['TrangThai'] === 1,
    ];
}

/** Bài đang đăng, mới nhất trước, có phân trang. */
function news_list(int $page = 1, int $perPage = 9): array
{
    $total = (int) db_val('SELECT COUNT(*) FROM TINTUC WHERE TrangThai = 1');
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    $rows  = db_all(
        news_base_sql() . ' WHERE t.TrangThai = 1 ORDER BY t.NgayDang DESC, t.MaTin DESC LIMIT '
        . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage)
    );
    return ['rows' => array_map('news_row', $rows), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

/** Vài bài mới nhất (trang chủ). */
function news_latest(int $limit = 3): array
{
    $rows = db_all(news_base_sql() . ' WHERE t.TrangThai = 1 ORDER BY t.NgayDang DESC, t.MaTin DESC LIMIT ' . (int) $limit);
    return array_map('news_row', $rows);
}

/** Một bài đang đăng theo mã, hoặc null. */
function news_one(int $id): ?array
{
    $r = db_one(news_base_sql() . ' WHERE t.MaTin = ? AND t.TrangThai = 1', [$id]);
    return $r ? news_row($r) : null;
}

/** Các bài đang đăng khác (cột bên trang chi tiết). */
function news_others(int $exceptId, int $limit = 5): array
{
    $rows = db_all(
        news_base_sql() . ' WHERE t.TrangThai = 1 AND t.MaTin <> ? ORDER BY t.NgayDang DESC, t.MaTin DESC LIMIT ' . (int) $limit,
        [$exceptId]
    );
    return array_map('news_row', $rows);
}

/** In nội dung bài viết dạng văn bản thuần: mỗi đoạn cách nhau một dòng trống. */
function news_render_body(string $body): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/u', trim($body)) as $para) {
        $para = trim($para);
        if ($para !== '') {
            $out .= '<p>' . nl2br(e($para)) . '</p>';
        }
    }
    return $out;
}

/* ============================ Báo giá (khách hàng) ============================ */

function quote_code(int $id): string
{
    return 'BG' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

function warranty_code(int $id): string
{
    return 'BH' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

/** Sản phẩm đang bán để chọn trong form báo giá (hàng chưa niêm yết xếp trước). */
function quote_pick_products(): array
{
    return db_all('SELECT MaSP, MaHang, TenSP FROM SANPHAM WHERE TrangThai = 1 ORDER BY LienHeBaoGia DESC, TenSP');
}

/** Báo giá của khách (mới nhất trước). Bản đã quá hiệu lực được chuyển sang "Hết hạn". */
function quotes_of_user(int $userId): array
{
    db_exec("UPDATE BAOGIA SET TrangThai = 'Hết hạn' WHERE MaND = ? AND TrangThai = 'Đã báo giá' AND HanHieuLuc IS NOT NULL AND HanHieuLuc < NOW()", [$userId]);
    return db_all(
        'SELECT b.MaBG, b.MaDH, b.NgayGui, b.TrangThai, b.HanHieuLuc, b.GhiChu, b.LyDoKhongBG,
                (SELECT COUNT(*) FROM CT_BAOGIA c WHERE c.MaBG = b.MaBG) AS SoSP
           FROM BAOGIA b WHERE b.MaND = ? ORDER BY b.NgayGui DESC, b.MaBG DESC',
        [$userId]
    );
}

/** Các dòng của một báo giá. */
function quote_items(int $quoteId): array
{
    return db_all(
        'SELECT s.MaHang, s.TenSP, c.SoLuong, c.DonGiaBao
           FROM CT_BAOGIA c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaBG = ? ORDER BY s.TenSP',
        [$quoteId]
    );
}

/** Khách gửi yêu cầu báo giá. $lines: [mã sản phẩm => số lượng]. */
function quote_create(int $userId, array $lines, string $note): array
{
    $clean = [];
    foreach ($lines as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;
        if ($pid > 0 && $qty > 0) {
            $clean[$pid] = min(9999, ($clean[$pid] ?? 0) + $qty);   // dòng trùng sản phẩm được gộp
        }
    }
    if (!$clean) {
        return ['ok' => false, 'msg' => 'Hãy chọn ít nhất một sản phẩm và số lượng.'];
    }
    if (mb_strlen($note, 'UTF-8') > 255) {
        return ['ok' => false, 'msg' => 'Ghi chú tối đa 255 ký tự.'];
    }
    $in    = implode(',', array_fill(0, count($clean), '?'));
    $found = (int) db_val("SELECT COUNT(*) FROM SANPHAM WHERE TrangThai = 1 AND MaSP IN ($in)", array_keys($clean));
    if ($found !== count($clean)) {
        return ['ok' => false, 'msg' => 'Có sản phẩm không còn bán, hãy chọn lại.'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        db_exec('INSERT INTO BAOGIA (MaND, GhiChu) VALUES (?, ?)', [$userId, $note !== '' ? $note : null]);
        $id = (int) $pdo->lastInsertId();
        foreach ($clean as $pid => $qty) {
            db_exec('INSERT INTO CT_BAOGIA (MaBG, MaSP, SoLuong) VALUES (?, ?, ?)', [$id, $pid, $qty]);
        }
        $pdo->commit();
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => db_error_message($ex)];
    }
    return ['ok' => true, 'msg' => 'Đã gửi yêu cầu báo giá ' . quote_code($id) . '. Nhân viên sẽ phản hồi trong tài khoản của bạn.'];
}

/**
 * Thời hạn đổi trả hàng lỗi để hiển thị, lấy từ SoNgayDoiTra thật của sản phẩm đang bán
 * (RETURN_DAYS chỉ là giá trị dự phòng khi chưa có sản phẩm).
 */
function return_days_text(): string
{
    $r  = db_one('SELECT MIN(SoNgayDoiTra) AS a, MAX(SoNgayDoiTra) AS b FROM SANPHAM WHERE TrangThai = 1');
    $a  = $r && $r['a'] !== null ? (int) $r['a'] : (int) RETURN_DAYS;
    $b  = $r && $r['b'] !== null ? (int) $r['b'] : (int) RETURN_DAYS;
    return $a === $b ? $a . ' ngày' : 'từ ' . $a . ' đến ' . $b . ' ngày tùy sản phẩm';
}

/** Khách từ chối báo giá (chỉ khi đang "Đã báo giá" và còn hiệu lực). */
function quote_reject(int $userId, int $quoteId): array
{
    $n = db_exec(
        "UPDATE BAOGIA SET TrangThai = 'Đã từ chối' WHERE MaBG = ? AND MaND = ? AND TrangThai = 'Đã báo giá'
            AND (HanHieuLuc IS NULL OR HanHieuLuc >= NOW())",
        [$quoteId, $userId]
    );
    if ($n < 1) {
        return ['ok' => false, 'msg' => 'Báo giá này không còn ở trạng thái chờ phản hồi hoặc đã hết hạn.'];
    }
    return ['ok' => true, 'msg' => 'Bạn đã từ chối báo giá ' . quote_code($quoteId) . '.'];
}

/**
 * Khách chấp nhận báo giá (UC11): kiểm tra tồn kho, tạo đơn hàng theo đơn giá đã báo, trừ tồn,
 * ghi BAOGIA.MaDH và chuyển báo giá sang "Đã chấp nhận" trong một giao dịch.
 * @return array{ok:bool,id:int,msg:string}   id = mã đơn vừa tạo
 */
function quote_accept(int $userId, int $quoteId, int $addressId, string $method): array
{
    $fail   = static fn(string $msg): array => ['ok' => false, 'id' => 0, 'msg' => $msg];
    $method = $method === 'Chuyển khoản' ? 'Chuyển khoản' : 'Thanh toán khi nhận hàng';
    $pdo    = db();
    try {
        $pdo->beginTransaction();

        $q = db_one('SELECT TrangThai, HanHieuLuc, MaDH FROM BAOGIA WHERE MaBG = ? AND MaND = ? FOR UPDATE', [$quoteId, $userId]);
        if ($q === null || $q['MaDH'] !== null || $q['TrangThai'] !== 'Đã báo giá') {
            $pdo->rollBack();
            return $fail('Báo giá này không còn ở trạng thái chờ phản hồi.');
        }
        if ($q['HanHieuLuc'] !== null && strtotime($q['HanHieuLuc']) < time()) {
            db_exec("UPDATE BAOGIA SET TrangThai = 'Hết hạn' WHERE MaBG = ?", [$quoteId]);
            $pdo->commit();
            return $fail('Báo giá đã hết hạn nên không thể chấp nhận.');
        }
        $addr = db_one('SELECT NguoiNhan, SoDienThoai, DiaChiChiTiet FROM DIACHI WHERE MaDC = ? AND MaND = ?', [$addressId, $userId]);
        if ($addr === null) {
            $pdo->rollBack();
            return $fail('Hãy chọn địa chỉ giao hàng trong sổ địa chỉ của bạn.');
        }

        $items = db_all(
            'SELECT c.MaSP, c.SoLuong, c.DonGiaBao, s.TenSP, s.TrangThai AS Ban, s.TinhTrangKD
               FROM CT_BAOGIA c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaBG = ? ORDER BY c.MaSP',
            [$quoteId]
        );
        if (!$items) {
            $pdo->rollBack();
            return $fail('Báo giá không có dòng sản phẩm nào.');
        }
        $goods = 0;
        $short = [];
        foreach ($items as $it) {
            if ($it['DonGiaBao'] === null) {
                $pdo->rollBack();
                return $fail('Báo giá chưa có đủ đơn giá cho mọi sản phẩm.');
            }
            if ((int) $it['Ban'] !== 1) {
                $pdo->rollBack();
                return $fail('Sản phẩm "' . $it['TenSP'] . '" đã ngừng bán, không thể đặt theo báo giá này.');
            }
            if ($it['TinhTrangKD'] !== 'Đang kinh doanh') {   // cùng quy tắc với sp_DatHang
                $pdo->rollBack();
                return $fail('Sản phẩm "' . $it['TenSP'] . '" ' . ($it['TinhTrangKD'] === 'Hàng sắp về'
                    ? 'sắp về hàng, chưa đặt được' : 'đã ngừng kinh doanh') . ', không thể đặt theo báo giá này.');
            }
            // Khóa dòng tồn kho theo thứ tự MaSP để tránh khóa chéo khi đặt đồng thời
            $stock = (int) db_val('SELECT SoLuongTon FROM TONKHO WHERE MaSP = ? FOR UPDATE', [(int) $it['MaSP']]);
            if ($stock < (int) $it['SoLuong']) {
                $short[] = $it['TenSP'];
            }
            $goods += (int) $it['SoLuong'] * (int) $it['DonGiaBao'];
        }
        if ($short) {
            $pdo->rollBack();
            return $fail('Không đủ tồn kho: ' . implode(', ', $short) . '. Vui lòng liên hệ cửa hàng.');
        }

        $fee   = (int) SHIPPING_FEE;
        $total = $goods + $fee;
        $status = $method === 'Chuyển khoản' ? 'Chờ thanh toán' : 'Chờ xác nhận';
        $hold   = $method === 'Chuyển khoản' ? date('Y-m-d H:i:s', time() + (int) PAYMENT_HOLD_HOURS * 3600) : null;

        db_exec(
            'INSERT INTO DONHANG (MaND, MaDC, NguoiNhan, SoDienThoaiNhan, DiaChiGiao, NgayDat, TrangThai, PhiGiao, TongTien, HanThanhToan)
             VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)',
            [$userId, $addressId, $addr['NguoiNhan'], $addr['SoDienThoai'], $addr['DiaChiChiTiet'], $status, $fee, $total, $hold]
        );
        $orderId = (int) $pdo->lastInsertId();
        foreach ($items as $it) {
            db_exec('INSERT INTO CT_DONHANG (MaDH, MaSP, SoLuong, DonGia) VALUES (?, ?, ?, ?)', [$orderId, (int) $it['MaSP'], (int) $it['SoLuong'], (int) $it['DonGiaBao']]);
            db_exec('UPDATE TONKHO SET SoLuongTon = SoLuongTon - ? WHERE MaSP = ?', [(int) $it['SoLuong'], (int) $it['MaSP']]);
        }
        db_exec("INSERT INTO THANHTOAN (MaDH, PhuongThuc, SoTien, TrangThai, ThoiGian) VALUES (?, ?, ?, 'Chưa thanh toán', NULL)", [$orderId, $method, $total]);
        db_exec(
            'INSERT INTO LICHSU_DONHANG (MaDH, MaND, TrangThaiCu, TrangThaiMoi, ThoiGian, GhiChu) VALUES (?, ?, NULL, ?, NOW(), ?)',
            [$orderId, $userId, $status, 'Tạo đơn từ báo giá ' . quote_code($quoteId)]
        );
        db_exec("UPDATE BAOGIA SET TrangThai = 'Đã chấp nhận', MaDH = ? WHERE MaBG = ?", [$orderId, $quoteId]);
        $pdo->commit();
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return $fail(db_error_message($ex));
    }
    return ['ok' => true, 'id' => $orderId, 'msg' => 'Đã chấp nhận báo giá ' . quote_code($quoteId) . ' và tạo đơn ' . order_code($orderId) . '.'];
}

/* ========================== Bảo hành, đổi trả (khách) ========================= */

/** Các sản phẩm trong đơn đã hoàn tất của khách, dùng để chọn khi gửi yêu cầu. */
function warranty_eligible_items(int $userId): array
{
    return db_all(
        "SELECT d.MaDH, d.NgayHoanTat, c.MaSP, s.MaHang, s.TenSP
           FROM DONHANG d JOIN CT_DONHANG c ON c.MaDH = d.MaDH JOIN SANPHAM s ON s.MaSP = c.MaSP
          WHERE d.MaND = ? AND d.TrangThai = 'Hoàn tất' AND d.NgayHoanTat IS NOT NULL
          ORDER BY d.NgayHoanTat DESC, d.MaDH DESC, s.TenSP",
        [$userId]
    );
}

/** Yêu cầu bảo hành, đổi trả của khách. */
function warranty_of_user(int $userId): array
{
    return db_all(
        'SELECT h.MaBH, h.MaDH, h.LoaiYeuCau, h.TrangThai, h.NgayTao, h.PhanHoi, s.TenSP
           FROM BAOHANH h JOIN DONHANG d ON d.MaDH = h.MaDH JOIN SANPHAM s ON s.MaSP = h.MaSP
          WHERE d.MaND = ? ORDER BY h.NgayTao DESC, h.MaBH DESC',
        [$userId]
    );
}

/** Khách gửi yêu cầu bảo hành hoặc đổi trả: đơn phải hoàn tất và còn trong thời hạn. */
function warranty_create(int $userId, int $orderId, int $productId, string $type, string $desc): array
{
    if (!in_array($type, ['Bảo hành', 'Đổi trả'], true)) {
        return ['ok' => false, 'msg' => 'Hãy chọn loại yêu cầu.'];
    }
    if ($desc === '' || mb_strlen($desc, 'UTF-8') > 2000) {
        return ['ok' => false, 'msg' => 'Hãy mô tả lỗi (tối đa 2000 ký tự).'];
    }
    $row = db_one(
        "SELECT d.NgayHoanTat, s.ThoiGianBH, s.SoNgayDoiTra
           FROM DONHANG d JOIN CT_DONHANG c ON c.MaDH = d.MaDH JOIN SANPHAM s ON s.MaSP = c.MaSP
          WHERE d.MaDH = ? AND d.MaND = ? AND d.TrangThai = 'Hoàn tất' AND c.MaSP = ?",
        [$orderId, $userId, $productId]
    );
    if (!$row || $row['NgayHoanTat'] === null) {
        return ['ok' => false, 'msg' => 'Chỉ nhận yêu cầu cho sản phẩm thuộc đơn hàng đã hoàn tất của bạn.'];
    }
    $done  = new DateTimeImmutable($row['NgayHoanTat']);
    $limit = $type === 'Đổi trả'
        ? $done->modify('+' . (int) $row['SoNgayDoiTra'] . ' days')
        : $done->modify('+' . (int) $row['ThoiGianBH'] . ' months');
    if (new DateTimeImmutable('now') > $limit) {
        return ['ok' => false, 'msg' => $type === 'Đổi trả'
            ? 'Đã quá thời hạn đổi trả (' . (int) $row['SoNgayDoiTra'] . ' ngày kể từ ngày hoàn tất đơn, hết hạn ngày ' . $limit->format('d/m/Y') . ').'
            : 'Sản phẩm đã hết thời hạn bảo hành (' . (int) $row['ThoiGianBH'] . ' tháng kể từ ngày hoàn tất đơn, hết hạn ngày ' . $limit->format('d/m/Y') . ').'];
    }
    if ((int) db_val("SELECT COUNT(*) FROM BAOHANH WHERE MaDH = ? AND MaSP = ? AND TrangThai = 'Mới'", [$orderId, $productId]) > 0) {
        return ['ok' => false, 'msg' => 'Sản phẩm này đã có yêu cầu đang chờ xử lý.'];
    }
    try {
        db_exec('INSERT INTO BAOHANH (MaDH, MaSP, LoaiYeuCau, MoTa) VALUES (?, ?, ?, ?)', [$orderId, $productId, $type, $desc]);
    } catch (PDOException $ex) {
        return ['ok' => false, 'msg' => db_error_message($ex)];
    }
    return ['ok' => true, 'msg' => 'Đã gửi yêu cầu ' . warranty_code((int) db()->lastInsertId()) . '. Nhân viên sẽ phản hồi sớm.'];
}

/* ============================ Liên hệ, nhận tin ============================== */

/**
 * Lưu tin nhắn từ biểu mẫu Liên hệ. Chặn gửi dồn dập: mỗi phiên cách nhau ít nhất 30 giây.
 * @return array{ok:bool,errors:string[]}
 */
function contact_send(?int $userId, string $name, string $phone, string $email, string $message): array
{
    $errors = [];
    $name    = trim($name);
    $email   = trim($email);
    $message = trim($message);
    $len = static fn(string $v): int => mb_strlen($v, 'UTF-8');

    if ($len($name) < 2 || $len($name) > 100)                        { $errors[] = 'Hãy nhập họ và tên (2–100 ký tự).'; }
    if ($email === '' || $len($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email không hợp lệ.'; }
    $phoneNorm = null;
    if (trim($phone) !== '') {
        $phoneNorm = normalize_phone($phone);
        if ($phoneNorm === '') { $errors[] = 'Số điện thoại không hợp lệ (ví dụ 0901234567).'; }
    }
    if ($len($message) < 10 || $len($message) > 1000)                { $errors[] = 'Nội dung từ 10 đến 1000 ký tự.'; }
    if (!$errors && time() - (int) ($_SESSION['contact_last'] ?? 0) < 30) {
        $errors[] = 'Bạn vừa gửi tin nhắn, vui lòng đợi 30 giây rồi gửi lại.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    db_exec(
        'INSERT INTO LIENHE (MaND, HoTen, SoDienThoai, Email, NoiDung) VALUES (?, ?, ?, ?, ?)',
        [$userId, $name, $phoneNorm, $email, $message]
    );
    $_SESSION['contact_last'] = time();
    return ['ok' => true, 'errors' => []];
}

/** Đăng ký nhận tin bằng email; email đã có thì vẫn báo thành công (không lộ ai đã đăng ký). */
function newsletter_subscribe(string $email): array
{
    $email = trim($email);
    if ($email === '' || mb_strlen($email, 'UTF-8') > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'msg' => 'Email không hợp lệ.'];
    }
    db_exec('INSERT IGNORE INTO NHANTIN (Email) VALUES (?)', [$email]);
    return ['ok' => true, 'msg' => 'Đã đăng ký nhận tin. Cảm ơn bạn!'];
}
