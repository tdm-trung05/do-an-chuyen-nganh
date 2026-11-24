# ĐiệnNhà – Hệ thống quản lý và kinh doanh thiết bị điện gia dụng trực tuyến

Đồ án tốt nghiệp – Trường Đại học Công nghệ Sài Gòn, Khoa Công nghệ Thông tin (2026).
Người hướng dẫn: ThS. Trịnh Thanh Duy. Sinh viên: Trương Đặng Minh Trung (DH52302155), Lê Đinh Văn Ron (DH52301685).

Website bán thiết bị điện gia dụng Legrand, gồm khu khách hàng và khu quản trị phân quyền theo vai trò. Toàn bộ số liệu lấy từ MySQL.

## Chức năng

**Khách hàng**
- Xem sản phẩm: tìm theo tên hoặc mã hàng, lọc theo danh mục, dòng sản phẩm, khoảng giá, tồn kho; sắp xếp, phân trang, xem nhanh bằng AJAX.
- Chi tiết sản phẩm: ảnh, thông số, tài liệu, đánh giá (chỉ khách có đơn hoàn tất), sản phẩm liên quan.
- Giỏ hàng lưu theo tài khoản, yêu thích, thanh toán (khi nhận hàng hoặc chuyển khoản), theo dõi và hủy đơn.
- Yêu cầu báo giá cho sản phẩm bán theo báo giá, chấp nhận hoặc từ chối báo giá nhận được.
- Yêu cầu bảo hành, đổi trả cho sản phẩm trong đơn đã hoàn tất (hệ thống kiểm tra thời hạn).
- Tin tức, liên hệ, đăng ký nhận tin, quản lý tài khoản và sổ địa chỉ.

**Quản trị** (QT: quản trị viên, BH: nhân viên bán hàng, TK: thủ kho)
- Đơn hàng (QT/BH): chuyển trạng thái đúng thứ tự, hủy đơn có lý do, xác nhận đã nhận tiền, chỉnh phí giao.
- Kho (QT/TK): tồn kho và ngưỡng cảnh báo, phiếu nhập, kiểm kê, nhà cung cấp (chỉ QT).
- Báo giá, bảo hành và đổi trả, liên hệ (QT/BH).
- Hàng hóa (QT): sản phẩm, danh mục, dòng sản phẩm, loại thông số, tài liệu PDF.
- Tin tức, tài khoản (khóa/mở khóa, đổi vai trò, đặt lại mật khẩu) (QT).
- Bảng điều khiển hiển thị theo vai trò.
- Báo biểu BB01–BB07, xuất CSV mở được bằng Excel:

| Mã | Nội dung | Vai trò xem |
| --- | --- | --- |
| BB01 | Doanh thu theo ngày hoặc theo tháng | QT |
| BB02 | Sản phẩm bán chạy | QT |
| BB03 | Tồn kho và cảnh báo | QT, TK |
| BB04 | Phiếu nhập theo kỳ | QT, TK |
| BB05 | Đơn hàng theo trạng thái | QT, BH |
| BB06 | Bảo hành, đổi trả (theo ngày tạo, nhóm theo tháng, mỗi trạng thái một cột) | QT, BH |
| BB07 | Yêu cầu báo giá (theo ngày gửi, nhóm theo tháng) | QT, BH |

**Hạn chế hiện tại**
- Chưa có quên mật khẩu tự động (cần gửi email): khách liên hệ cửa hàng để quản trị đặt lại.
- Form đánh giá chỉ có số sao và nhận xét, chưa đính kèm ảnh.

Cửa hàng chỉ bán hàng Legrand nên không có bảng thương hiệu; tên thương hiệu là hằng số `SHOP_BRAND` trong `includes/shop.php`.

## Công nghệ
PHP (PDO) · MySQL 8.0.16+ (InnoDB) · HTML/CSS/JavaScript · Bootstrap 5.3 · WampServer · Git/GitHub.
Bootstrap, Bootstrap Icons và phông Be Vietnam Pro nạp qua CDN nên máy cần có Internet khi xem giao diện.

## Cài đặt và chạy thử trên WampServer
1. Cài WampServer, chờ biểu tượng chuyển xanh.
2. Đặt thư mục dự án vào `C:\wamp64\www\thiet-bi-dien` (đúng tên này, hoặc sửa `BASE_URL`).
3. Sao chép `config/config.sample.php` thành `config/config.php` và sửa cho khớp máy: `BASE_URL`, thông tin MySQL, `SHIPPING_FEE`, thông tin chuyển khoản `BANK_*`, `SITE_SUPPORT`, `SITE_ZALO`.
4. Mở `http://localhost/phpmyadmin` → tab **Import** → chọn `database/schema.sql`. File tự tạo CSDL `quanly_thietbidien` kèm dữ liệu mẫu, không cần tạo CSDL trước. File không tự xóa CSDL: nếu CSDL đã có bảng, import sẽ báo lỗi và dữ liệu giữ nguyên. Muốn cài lại từ đầu (mất toàn bộ dữ liệu), chạy `DROP DATABASE quanly_thietbidien;` trong tab SQL rồi import lại.
5. Mở `http://localhost/thiet-bi-dien/` (khách hàng) và `http://localhost/thiet-bi-dien/admin/` (quản trị).

Nếu đặt tên thư mục khác, chỉ cần sửa `BASE_URL` (`.htaccess` dùng đường dẫn tương đối, cần bật `mod_rewrite` như mặc định của WampServer).

Lưu ý khi dùng CSDL đã có từ bản cũ: các view báo biểu BB06, BB07 nay gộp theo ngày, cần chạy lại hai câu `CREATE OR REPLACE VIEW` của chúng trong `database/schema.sql`, hoặc import lại toàn bộ schema.

Sự kiện `ev_HuyDonQuaHan` ở cuối `schema.sql` (tự hủy đơn chuyển khoản quá hạn mỗi 5 phút) là tùy chọn; thiếu quyền EVENT thì bỏ qua câu này. Khi đó đơn quá hạn vẫn được hủy mỗi khi có người mở trang đơn hàng hoặc thanh toán.

## Dữ liệu mẫu và tài khoản dùng thử
`database/schema.sql` có 20 sản phẩm (17 bán thẳng, 3 bán theo báo giá), 5 dòng sản phẩm, 3 phiếu nhập, 5 đơn hàng đủ các trạng thái. Mã hàng đuôi "DEMO", giá và thông số là dữ liệu giả, cần thay bằng dữ liệu thật trước khi đưa vào sử dụng.

Mật khẩu chung của các tài khoản mẫu là `123456`. **Chỉ dùng để chạy thử; phải đổi hoặc xóa khi triển khai thật.**

| Email | Vai trò | Đăng nhập tại |
| --- | --- | --- |
| `admin@demo.vn` | Quản trị viên | `/admin/dang-nhap.php` |
| `nhanvien@demo.vn` | Nhân viên bán hàng | `/admin/dang-nhap.php` |
| `thukho@demo.vn` | Thủ kho | `/admin/dang-nhap.php` |
| `khach1@demo.vn`, `khach2@demo.vn` | Khách hàng | `/dang-nhap.php` |

Nhân viên chỉ đăng nhập ở trang quản trị; trang đăng nhập của khách từ chối tài khoản nhân viên.

## Cấu trúc thư mục
```
thiet-bi-dien/
├── index.php                    MH01  Trang chủ
├── san-pham.php                 MH02  Danh sách, tìm, lọc, xem nhanh
├── san-pham-chi-tiet.php        MH03  Chi tiết sản phẩm
├── yeu-thich.php                MH04  Yêu thích
├── gio-hang.php                 MH05  Giỏ hàng
├── thanh-toan.php               MH06  Thanh toán
├── dat-hang-thanh-cong.php            Kết quả đặt hàng
├── dang-nhap.php · dang-ky.php · quen-mat-khau.php      MH07
├── tai-khoan.php · don-hang.php · don-hang-chi-tiet.php MH08
├── bao-hanh.php                 MH09  Yêu cầu bảo hành, đổi trả
├── bao-gia.php                  MH17  Yêu cầu báo giá, báo giá đã nhận
├── tin-tuc.php · tin-tuc-chi-tiet.php                   MH18
├── gioi-thieu.php · lien-he.php · chinh-sach.php · 404.php
├── admin/                       Khu quản trị (MH10–MH16, MH19, báo biểu BB01–BB07)
│   ├── index.php · dang-nhap.php
│   ├── san-pham/  danh-muc/  dong-san-pham/  thong-so/  tai-lieu/
│   ├── don-hang/  bao-gia/  bao-hanh/  kho/  tai-khoan/  tin-tuc/  lien-he/  bao-cao/
│   └── includes/                layout, head, sidebar, topbar, footer, hàm riêng
├── gio-hang-xu-ly.php           Giỏ hàng, yêu thích (POST + CSRF, trả JSON cho AJAX)
├── xem-nhanh.php                Nội dung hộp xem nhanh (AJAX)
├── dang-ky-nhan-tin.php         Đăng ký nhận tin ở chân trang
├── dang-xuat.php                Đăng xuất (chỉ nhận POST + CSRF)
├── includes/                    Phần dùng chung của khu khách hàng
│   ├── bootstrap.php            Điểm vào chung: nạp cấu hình, hàm, kết nối CSDL, bố cục, mở phiên
│   ├── auth.php                 Phiên, đăng nhập, khóa tài khoản, vai trò, CSRF, flash
│   ├── shop.php                 Sản phẩm, tìm kiếm, giỏ hàng, yêu thích, đặt hàng, đơn hàng
│   ├── helpers.php · db.php · layout.php
│   ├── head.php · header.php · footer.php
│   └── components/              product_card, breadcrumb, pagination, filter_sidebar, account_nav, quick_view_modal, page_title
├── config/                      config.sample.php (mẫu) · config.php (không đưa lên GitHub)
├── assets/                      css/ · js/ · images/ · fonts/
├── uploads/                     Ảnh sản phẩm, tin tức, tài liệu PDF tải lên
├── database/schema.sql          Lược đồ MySQL, thủ tục, trigger, view, dữ liệu mẫu
└── docs/                        Bảng ánh xạ màn hình và hướng dẫn nối chức năng
```

## Quy ước viết trang
- Trang khách hàng: `require_once __DIR__ . '/includes/bootstrap.php'; page_begin(...); ... page_end();`
- Trang quản trị: `require_once __DIR__ . '/../../includes/bootstrap.php'; admin_begin(...); ... admin_end();`
- Mọi dữ liệu in ra trang dùng `e()` (chống XSS). Trong `data_table()`, ô chỉ được in nguyên khi bọc bằng `raw('...')`, và dữ liệu người dùng bên trong phải qua `e()`.
- Trang chỉ cho khách đã đăng nhập: gọi `require_customer();` ngay sau dòng `require_once ... bootstrap.php`.
- Trang quản trị: `admin_begin()` tự gọi `admin_guard()` theo khóa menu (bảng vai trò ở `admin_menu()` trong `admin/includes/functions.php`). Trang có xử lý POST nên gọi `admin_guard('khóa-menu')` trước khi xử lý.
- Mọi `<form method="post">` phải có `<?= csrf_field() ?>` và gọi `csrf_verify()` trước khi ghi dữ liệu.
- Người dùng hiện tại: `auth_user()` (null nếu chưa đăng nhập; có `MaND`, `HoTen`, `Email`, `role` = KH/BH/TK/QT). Thông báo sau chuyển hướng: `flash_set('success', '...')`.
- Đường dẫn dùng `url()`, `asset()`, `aurl()`, không viết cứng.
- Truy vấn MySQL qua PDO prepared statement với `db_all / db_one / db_val / db_exec` trong `includes/db.php`; truy vấn khu khách ở `includes/shop.php`, khu quản trị ở `admin/includes/data.php`.

## Quy trình làm việc với Git
Nhánh `main` luôn chạy được. Mỗi chức năng làm trên một nhánh riêng, gộp bằng pull request. Không đẩy `config/config.php` lên GitHub (đã có trong `.gitignore`).

## Tham khảo giao diện
Bố cục tham khảo [sgm.com.vn](https://sgm.com.vn) (menu danh mục nhiều tầng, chia theo dòng sản phẩm) và [legrandfr.com](https://legrandfr.com) (giỏ hàng, tài khoản, xem nhanh, đánh giá).
