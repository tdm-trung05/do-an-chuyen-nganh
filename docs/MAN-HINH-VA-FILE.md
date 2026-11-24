# Bảng ánh xạ màn hình ↔ file (mục 3.3 và 3.4 của báo cáo)

## Khu khách hàng
| Mã | Màn hình | File | Ghi chú |
| --- | --- | --- | --- |
| MH01 | Trang chủ | `index.php` | Hero tra mã hàng, danh mục, dòng sản phẩm, nổi bật, tin mới |
| MH02 | Danh sách sản phẩm | `san-pham.php` | Dùng `components/filter_sidebar.php`, `product_card.php`, `quick_view_modal.php` |
| MH03 | Chi tiết sản phẩm | `san-pham-chi-tiet.php` | Thông số, tài liệu, đánh giá; nút báo giá cho hàng chưa niêm yết |
| MH04 | Danh sách yêu thích | `yeu-thich.php` | |
| MH05 | Giỏ hàng | `gio-hang.php` | |
| MH06 | Thanh toán | `thanh-toan.php`, `dat-hang-thanh-cong.php` | Khi nhận hàng hoặc chuyển khoản |
| MH07 | Đăng ký, đăng nhập | `dang-ky.php`, `dang-nhap.php`, `quen-mat-khau.php` | |
| MH08 | Tài khoản và đơn hàng | `tai-khoan.php`, `don-hang.php`, `don-hang-chi-tiet.php` | |
| MH09 | Bảo hành, đổi trả | `bao-hanh.php` | |
| MH17 | Yêu cầu báo giá | `bao-gia.php` | Hai tab: gửi mới, báo giá đã nhận |
| MH18 | Tin tức | `tin-tuc.php`, `tin-tuc-chi-tiet.php` | |
| – | Trang phụ | `gioi-thieu.php`, `lien-he.php`, `chinh-sach.php`, `404.php` | Theo các trang chính sách của hai website tham khảo |

## Khu quản trị
| Mã | Màn hình | Vai trò | File |
| --- | --- | --- | --- |
| MH10 | Bảng điều khiển | QT, BH, TK | `admin/index.php` |
| MH11 | Quản lý sản phẩm | QT | `admin/san-pham/index.php`, `form.php` (và `danh-muc/`, `dong-san-pham/`, `thong-so/`, `tai-lieu/`) |
| MH12 | Quản lý đơn hàng | QT, BH | `admin/don-hang/index.php`, `chi-tiet.php` |
| MH13 | Nhập kho và tồn kho | QT, TK | `admin/kho/ton-kho.php`, `phieu-nhap.php`, `nhap-kho.php`, `kiem-ke.php`, `nha-cung-cap.php` |
| MH14 | Quản lý báo giá | QT, BH | `admin/bao-gia/index.php`, `chi-tiet.php` |
| MH15 | Quản lý bảo hành | QT, BH | `admin/bao-hanh/index.php` |
| MH16 | Tài khoản, phân quyền | QT | `admin/tai-khoan/index.php` |
| MH19 | Quản lý tin tức | QT | `admin/tin-tuc/index.php`, `form.php` |
| BB01–BB07 | Báo biểu | theo vai trò | `admin/bao-cao/index.php` |

QT = Quản trị viên, BH = Nhân viên bán hàng, TK = Thủ kho.

## Use case ↔ file sẽ cần nối chức năng
UC01 → `san-pham.php` · UC02 → `yeu-thich.php` + modal · UC03 → `dang-nhap.php`, `dang-ky.php` · UC04 → `gio-hang.php`
UC05 → `thanh-toan.php` · UC06 → `don-hang.php` · UC07 → tab đánh giá ở `san-pham-chi-tiet.php` · UC08 → `bao-hanh.php`
UC09–10 → `admin/san-pham/`, `danh-muc/`, … · UC11 → `bao-gia.php` · UC12 → `admin/don-hang/` · UC13 → `admin/bao-hanh/`
UC14 → `admin/kho/nhap-kho.php` · UC15 → `admin/kho/kiem-ke.php` · UC16 → `admin/tai-khoan/` · UC17 → `admin/bao-cao/`
UC18 → `admin/bao-gia/chi-tiet.php` · UC19 → `admin/tin-tuc/`

## Gợi ý thứ tự nối chức năng
1. ~~`includes/db.php` (PDO) và `includes/auth.php` (session, kiểm tra vai trò ở máy chủ).~~ Đã xong, kèm UC03 (đăng ký, đăng nhập, đăng xuất) và đăng nhập khu quản trị.
2. ~~Danh sách và chi tiết sản phẩm, tìm theo mã hàng.~~ Đã xong (`includes/shop.php`, `san-pham.php`, `san-pham-chi-tiet.php`).
3. ~~Giỏ hàng, đặt hàng trong một transaction với `SELECT ... FOR UPDATE` (mục 1.2).~~ Đã xong: gọi thủ tục `sp_DatHang`, hủy qua `sp_HuyDon`; kèm yêu thích, đánh giá, đơn hàng của tôi, tài khoản và sổ địa chỉ.
4. Khu quản trị: ~~đơn hàng~~ (xong) → ~~kho~~ (xong) → sản phẩm (xem, tìm, ẩn/hiện xong; thêm/sửa chưa) → ~~báo giá, bảo hành~~ (xong) → ~~báo cáo~~ (xong, dùng các view `vw_BB01…07`). Còn lại: lưu form sản phẩm, sửa/xóa danh mục và dòng sản phẩm, thêm loại thông số, tải tài liệu, tạo tài khoản.
5. Còn lại phía khách: quên mật khẩu, form liên hệ. (Báo giá, bảo hành, đổi trả, tin tức đã xong.)
