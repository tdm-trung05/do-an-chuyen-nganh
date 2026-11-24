<?php
/**
 * File cấu hình mẫu.
 * Cách dùng: sao chép thành config/config.php rồi sửa lại cho khớp máy của bạn.
 * config/config.php nằm trong .gitignore nên không bị đẩy lên GitHub.
 */

// Thông tin website
define('SITE_NAME',    'ĐiệnNhà');
define('SITE_TAGLINE', 'Thiết bị điện gia dụng chính hãng');
define('SITE_HOTLINE', '0900 000 000');
define('SITE_SUPPORT', '');   // số hỗ trợ kỹ thuật; để trống thì trang Liên hệ ẩn dòng này
define('SITE_ZALO',    '');   // số Zalo; để trống thì trang Liên hệ ẩn dòng này
define('SITE_EMAIL',   'lienhe@diennha.local');
define('SITE_ADDRESS', 'TP. Hồ Chí Minh');

// Đường dẫn gốc của dự án trên localhost (không có dấu / ở cuối)
// Ví dụ đặt trong C:\wamp64\www\thiet-bi-dien => '/thiet-bi-dien'
define('BASE_URL', '/thiet-bi-dien');

// Kết nối cơ sở dữ liệu MySQL (tên CSDL do database/schema.sql tự tạo)
define('DB_HOST',    'localhost');
define('DB_NAME',    'quanly_thietbidien');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Múi giờ dùng chung cho PHP và MySQL (mặc định Asia/Ho_Chi_Minh nếu bỏ dòng này).
define('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');

// Quy ước nghiệp vụ lấy từ báo cáo (mục 2.3.1.4 và 4.3)
define('RETURN_DAYS',        3);   // chỉ là giá trị dự phòng; thời hạn thật là SoNgayDoiTra của từng sản phẩm
define('PAYMENT_HOLD_HOURS', 24);  // thời hạn giữ đơn chuyển khoản chưa xác nhận
define('LOGIN_MAX_ATTEMPTS', 5);   // số lần nhập sai mật khẩu tối đa
define('LOGIN_LOCK_MINUTES', 15);  // thời gian khóa tạm tài khoản
define('PAGE_SIZE',          12);  // số sản phẩm mỗi trang
define('SHIPPING_FEE',       30000); // phí giao hàng mặc định (đồng), nhân viên vẫn chỉnh được ở chi tiết đơn

// Thông tin nhận chuyển khoản (hiện ở trang đặt hàng thành công). Sửa lại cho đúng tài khoản thật.
define('BANK_NAME',    'Ngân hàng [tên ngân hàng]');
define('BANK_ACCOUNT', '[số tài khoản]');
define('BANK_HOLDER',  '[tên chủ tài khoản]');
