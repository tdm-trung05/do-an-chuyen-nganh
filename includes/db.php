<?php
/**
 * Kết nối MySQL bằng PDO (dùng thông tin DB_* trong config/config.php).
 *
 * Cách dùng trong bất kỳ trang nào (đã được nạp sẵn qua includes/bootstrap.php):
 *
 *   $sp   = db_all('SELECT * FROM SANPHAM WHERE MaDM = ?', [$maDM]);   // nhiều dòng
 *   $one  = db_one('SELECT * FROM SANPHAM WHERE MaSP = ?', [$id]);     // một dòng (hoặc null)
 *   $tong = db_val('SELECT COUNT(*) FROM SANPHAM');                    // một giá trị
 *   $n    = db_exec('UPDATE SANPHAM SET TrangThai = ? WHERE MaSP = ?', [0, $id]); // số dòng bị ảnh hưởng
 *   $newId = db()->lastInsertId();
 *
 * Luôn truyền giá trị qua mảng tham số (dấu ?), không nối chuỗi vào câu SQL (chống SQL injection).
 * Tên bảng/cột viết HOA như trong schema.sql (Linux phân biệt hoa thường, Windows thì không).
 */

/** Trả về đối tượng PDO dùng chung; chỉ kết nối ở lần gọi đầu tiên. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    // Đồng bộ múi giờ phiên MySQL với PHP để NOW() và time()/date() cho cùng một giờ.
    $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P'); // vd: +07:00
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lỗi SQL ném ngoại lệ
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // dòng trả về là mảng kết hợp
            PDO::ATTR_EMULATE_PREPARES   => false,                    // dùng prepared statement thật
            // COLLATE khớp với CSDL (utf8mb4_unicode_ci); thiếu thì PHP dùng utf8mb4_general_ci và so sánh chuỗi với cột view/thủ tục báo lỗi "Illegal mix of collations".
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '" . $offset . "'",
        ]);
    } catch (PDOException $ex) {
        http_response_code(500);
        error_log('[DB] ' . $ex->getMessage());
        exit('Không kết nối được cơ sở dữ liệu. Hãy bật MySQL trong WampServer, import database/schema.sql '
           . 'và kiểm tra DB_* trong config/config.php.');
    }
    return $pdo;
}

/** Chạy SELECT, trả về mọi dòng. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Chạy SELECT, trả về một dòng (hoặc null nếu không có). */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Chạy SELECT, trả về giá trị ở cột đầu của dòng đầu (hoặc null). */
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

/** Chạy INSERT/UPDATE/DELETE, trả về số dòng bị ảnh hưởng. */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
