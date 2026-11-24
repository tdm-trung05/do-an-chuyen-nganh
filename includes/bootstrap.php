<?php
/**
 * Khởi tạo chung cho mọi trang: nạp cấu hình, hàm hỗ trợ, kết nối CSDL, phiên đăng nhập, bố cục.
 * Mỗi trang chỉ cần: require_once __DIR__ . '/includes/bootstrap.php';
 */
define('ROOT_PATH', dirname(__DIR__));
define('INC_PATH',  ROOT_PATH . '/includes');

$configFile = ROOT_PATH . '/config/config.php';
if (!file_exists($configFile)) {
    $configFile = ROOT_PATH . '/config/config.sample.php';
}
require_once $configFile;

// Múi giờ chung cho PHP và MySQL (xem db()): date(), time(), strtotime() và NOW() phải cùng một nguồn giờ.
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');
}
date_default_timezone_set(APP_TIMEZONE);

require_once INC_PATH . '/helpers.php';
require_once INC_PATH . '/db.php';       // kết nối MySQL (PDO), chỉ kết nối khi gọi db()
require_once INC_PATH . '/auth.php';     // phiên, đăng nhập, phân quyền, CSRF, thông báo flash
require_once INC_PATH . '/shop.php';     // sản phẩm, giỏ hàng, yêu thích, đơn hàng (truy vấn MySQL)
require_once INC_PATH . '/layout.php';
require_once ROOT_PATH . '/admin/includes/layout.php';

/**
 * Bắt chung PDOException chưa được xử lý (thêm danh mục/nhà cung cấp trùng, v.v.):
 * ghi log, rồi với thao tác POST thì báo lỗi dễ hiểu và quay lại trang trước; còn lại trả trang lỗi 500.
 */
set_exception_handler(static function (Throwable $ex): void {
    error_log('[EXC] ' . get_class($ex) . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    $isDb = $ex instanceof PDOException;
    if (!headers_sent()) {
        if ($isDb && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && session_status() === PHP_SESSION_ACTIVE) {
            $ref  = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            $host = parse_url($ref, PHP_URL_HOST);
            if ($host !== null && $host !== false && strcasecmp($host, parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?: '') === 0) {
                flash_set('danger', db_error_message($ex));
                redirect($ref);
            }
        }
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset="utf-8"><title>Lỗi hệ thống</title>'
       . '<p style="font:16px sans-serif;margin:2rem">' . ($isDb ? htmlspecialchars(db_error_message($ex), ENT_QUOTES, 'UTF-8') : 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.') . '</p>';
});

auth_start();   // mở phiên (trước khi trang in bất kỳ nội dung nào)
