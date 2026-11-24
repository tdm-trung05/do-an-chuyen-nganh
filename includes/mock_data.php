<?php
/**
 * Dữ liệu mẫu tĩnh để dựng giao diện (chưa nối cơ sở dữ liệu).
 * Số liệu lấy theo thông tin công khai trên sgm.com.vn và legrandfr.com, chỉ dùng cho học tập.
 * Khi nối MySQL, thay từng mảng này bằng truy vấn PDO tương ứng.
 */

// DANHMUC (có thể lồng nhau, mục 3.1.1)
$mockCategories = [
    ['id' => 1, 'name' => 'Công tắc - Ổ cắm', 'icon' => 'bi-toggles',        'count' => 148],
    ['id' => 2, 'name' => 'Aptomat MCB',      'icon' => 'bi-lightning-charge', 'count' => 64],
    ['id' => 3, 'name' => 'MCCB - RCBO - RCD','icon' => 'bi-shield-check',    'count' => 41],
    ['id' => 4, 'name' => 'Tủ điện',          'icon' => 'bi-hdd-rack',        'count' => 27],
    ['id' => 5, 'name' => 'Hộp âm sàn',       'icon' => 'bi-box-seam',        'count' => 18],
    ['id' => 6, 'name' => 'Chống nước Plexo', 'icon' => 'bi-droplet',         'count' => 22],
    ['id' => 7, 'name' => 'Ổ cắm công nghiệp','icon' => 'bi-plug',            'count' => 15],
];

// DONGSANPHAM
$mockSeries = [
    ['id' => 1, 'name' => 'Galion',        'brand' => 'Legrand'],
    ['id' => 2, 'name' => 'Arteor',        'brand' => 'Legrand'],
    ['id' => 3, 'name' => 'Mallia Senses', 'brand' => 'Legrand'],
    ['id' => 4, 'name' => 'Eloe',          'brand' => 'Legrand'],
    ['id' => 5, 'name' => 'Living Now',    'brand' => 'Legrand'],
    ['id' => 6, 'name' => 'Axolute',       'brand' => 'Legrand'],
];

// SANPHAM (mỗi mã hàng là một dòng, mục 1.2)
$mockProducts = [
    ['id' => 1,  'code' => '282434-C',  'name' => 'Ổ cắm đơn đa năng Galion',        'category' => 'Công tắc - Ổ cắm', 'brand' => 'Legrand', 'series' => 'Galion',     'price' => 86000,  'old_price' => null,  'stock' => 120, 'warranty' => 24, 'rating' => 5, 'tag' => 'Bán chạy'],
    ['id' => 2,  'code' => '282434-C1', 'name' => 'Ổ cắm đơn đa năng Galion (màu 1)', 'category' => 'Công tắc - Ổ cắm', 'brand' => 'Legrand', 'series' => 'Galion',   'price' => 89000,  'old_price' => null,  'stock' => 64,  'warranty' => 24, 'rating' => 4, 'tag' => ''],
    ['id' => 3,  'code' => 'KA4804KM',  'name' => 'Mặt công tắc 4 module Living Now – màu cát', 'category' => 'Công tắc - Ổ cắm', 'brand' => 'Legrand', 'series' => 'Living Now', 'price' => 215000, 'old_price' => 240000, 'stock' => 18, 'warranty' => 12, 'rating' => 4, 'tag' => 'Giảm giá'],
    ['id' => 4,  'code' => 'KA4804KG',  'name' => 'Mặt công tắc 4 module Living Now – màu đen', 'category' => 'Công tắc - Ổ cắm', 'brand' => 'Legrand', 'series' => 'Living Now', 'price' => 215000, 'old_price' => null,  'stock' => 7,   'warranty' => 12, 'rating' => 5, 'tag' => ''],
    ['id' => 5,  'code' => '419676',    'name' => 'Aptomat MCB 1P 20A 6kA',          'category' => 'Aptomat MCB',      'brand' => 'Legrand', 'series' => '',           'price' => 98000,  'old_price' => null,  'stock' => 200, 'warranty' => 24, 'rating' => 5, 'tag' => 'Bán chạy'],
    ['id' => 6,  'code' => '419702',    'name' => 'Aptomat MCB 2P 32A 6kA',          'category' => 'Aptomat MCB',      'brand' => 'Legrand', 'series' => '',           'price' => 245000, 'old_price' => null,  'stock' => 3,   'warranty' => 24, 'rating' => 4, 'tag' => ''],
    ['id' => 7,  'code' => '401201',    'name' => 'Tủ điện âm tường 12 module',      'category' => 'Tủ điện',          'brand' => 'Legrand', 'series' => '',           'price' => null,   'old_price' => null,  'stock' => 12,  'warranty' => 12, 'rating' => 0, 'tag' => 'Báo giá'],
    ['id' => 8,  'code' => '088031',    'name' => 'Hộp âm sàn 6 module',             'category' => 'Hộp âm sàn',       'brand' => 'Legrand', 'series' => '',           'price' => null,   'old_price' => null,  'stock' => 9,   'warranty' => 12, 'rating' => 0, 'tag' => 'Báo giá'],
    ['id' => 9,  'code' => '575151',    'name' => 'Công tắc 1 chiều Arteor',         'category' => 'Công tắc - Ổ cắm', 'brand' => 'Legrand', 'series' => 'Arteor',     'price' => 132000, 'old_price' => null,  'stock' => 85,  'warranty' => 24, 'rating' => 5, 'tag' => 'Mới'],
    ['id' => 10, 'code' => '572170',    'name' => 'Ổ cắm đôi 2 chấu 16A Mallia Senses', 'category' => 'Công tắc - Ổ cắm', 'brand' => 'Legrand', 'series' => 'Mallia Senses', 'price' => 178000, 'old_price' => null, 'stock' => 0, 'warranty' => 24, 'rating' => 4, 'tag' => ''],
    ['id' => 11, 'code' => '092325',    'name' => 'Ổ cắm chống nước Plexo IP55',     'category' => 'Chống nước Plexo', 'brand' => 'Legrand', 'series' => '',           'price' => 154000, 'old_price' => null,  'stock' => 40,  'warranty' => 24, 'rating' => 4, 'tag' => ''],
    ['id' => 12, 'code' => '411582',    'name' => 'RCBO 1P+N 20A 30mA',              'category' => 'MCCB - RCBO - RCD', 'brand' => 'Legrand', 'series' => '',          'price' => 520000, 'old_price' => null,  'stock' => 25,  'warranty' => 24, 'rating' => 5, 'tag' => ''],
];

// THONGSO + CT_THONGSO của sản phẩm mẫu (mục 1.2: thông số mỗi loại một khác)
$mockSpecs = [
    ['Dòng sản phẩm', 'Galion'],
    ['Số chấu',       '2 chấu + tiếp địa'],
    ['Dòng điện',     '16A'],
    ['Điện áp',       '250V'],
    ['Màu mặt',       'Trắng'],
    ['Bảo hành',      '24 tháng'],
];

// TAILIEU
$mockDocuments = [
    ['title' => 'Catalogue Galion 2025', 'type' => 'PDF', 'size' => '4,2 MB'],
    ['title' => 'Bản vẽ kỹ thuật 282434', 'type' => 'PDF', 'size' => '0,6 MB'],
];

// DANHGIA
$mockReviews = [
    ['user' => 'Nguyễn Minh Hoàng', 'stars' => 5, 'date' => '28/10/2026', 'text' => 'Mặt ổ cắm lắp khít, hàng đúng mã như đã đặt.'],
    ['user' => 'Trần Thu Hà',       'stars' => 4, 'date' => '19/10/2026', 'text' => 'Giao nhanh, đóng gói kỹ. Mong shop có thêm nhiều màu.'],
];

// TINTUC
$mockNews = [
    ['id' => 1, 'title' => 'Cách đọc mã hàng công tắc, ổ cắm để mua đúng lần đầu',       'date' => '02/11/2026', 'summary' => 'Cùng một kiểu sản phẩm có nhiều mã theo màu mặt và số module. Đọc đúng mã giúp tránh mua nhầm khi sửa nhà.', 'category' => 'Hướng dẫn'],
    ['id' => 2, 'title' => 'Chọn aptomat cho căn hộ: dòng định mức bao nhiêu là đủ?',    'date' => '28/10/2026', 'summary' => 'Gợi ý chọn aptomat tổng và aptomat nhánh theo công suất sử dụng thực tế của từng khu vực trong nhà.', 'category' => 'Tư vấn'],
    ['id' => 3, 'title' => 'Dòng Arteor và Galion khác nhau thế nào?',                    'date' => '20/10/2026', 'summary' => 'So sánh nhanh kiểu dáng, màu sắc và phân khúc giá của hai dòng công tắc – ổ cắm phổ biến.', 'category' => 'So sánh'],
    ['id' => 4, 'title' => 'Thời hạn đổi trả và bảo hành: những điều cần biết',           'date' => '12/10/2026', 'summary' => 'Hàng lỗi khi nhận được đổi trả trong thời hạn quy định, còn bảo hành tính từ ngày đơn hoàn tất.', 'category' => 'Chính sách'],
];

// GIOHANG (mẫu hiển thị)
$mockCart = [
    ['product' => $mockProducts[0], 'qty' => 4],
    ['product' => $mockProducts[4], 'qty' => 2],
    ['product' => $mockProducts[8], 'qty' => 1],
];

// DIACHI
$mockAddresses = [
    ['name' => 'Nguyễn Văn An', 'phone' => '0901 234 567', 'address' => '12 Nguyễn Văn Linh, Phường Tân Phong, Quận 7, TP. Hồ Chí Minh', 'default' => true],
    ['name' => 'Nguyễn Văn An', 'phone' => '0901 234 567', 'address' => '45 Lê Văn Việt, Phường Hiệp Phú, TP. Thủ Đức, TP. Hồ Chí Minh', 'default' => false],
];

// DONHANG
$mockOrders = [
    ['code' => 'DH000125', 'customer' => 'Nguyễn Văn An',   'date' => '03/11/2026', 'items' => 3, 'total' => 667000,  'payment' => 'Chuyển khoản',        'status' => 'Chờ thanh toán'],
    ['code' => 'DH000124', 'customer' => 'Lê Thị Bích',     'date' => '03/11/2026', 'items' => 1, 'total' => 245000,  'payment' => 'Khi nhận hàng',      'status' => 'Chờ xác nhận'],
    ['code' => 'DH000123', 'customer' => 'Phạm Quốc Dũng',  'date' => '02/11/2026', 'items' => 6, 'total' => 1280000, 'payment' => 'Chuyển khoản',        'status' => 'Đã xác nhận'],
    ['code' => 'DH000122', 'customer' => 'Trần Thu Hà',     'date' => '01/11/2026', 'items' => 2, 'total' => 430000,  'payment' => 'Khi nhận hàng',      'status' => 'Đang giao'],
    ['code' => 'DH000121', 'customer' => 'Võ Minh Khôi',    'date' => '29/10/2026', 'items' => 4, 'total' => 912000,  'payment' => 'Chuyển khoản',        'status' => 'Hoàn tất'],
    ['code' => 'DH000120', 'customer' => 'Đỗ Thanh Tâm',    'date' => '27/10/2026', 'items' => 1, 'total' => 98000,   'payment' => 'Khi nhận hàng',      'status' => 'Đã hủy'],
];

// Các bước trạng thái đơn hàng (mục 2.3.1.2)
$mockOrderSteps = ['Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Hoàn tất'];

// BAOGIA
$mockQuotes = [
    ['code' => 'BG000031', 'customer' => 'Công ty XD Hưng Phát', 'date' => '03/11/2026', 'items' => 3, 'valid_until' => '—',          'status' => 'Mới'],
    ['code' => 'BG000030', 'customer' => 'Nguyễn Văn An',        'date' => '31/10/2026', 'items' => 2, 'valid_until' => '14/11/2026', 'status' => 'Đã báo giá'],
    ['code' => 'BG000029', 'customer' => 'Lê Hoàng Nam',         'date' => '22/10/2026', 'items' => 5, 'valid_until' => '05/11/2026', 'status' => 'Đã chấp nhận'],
    ['code' => 'BG000028', 'customer' => 'Trần Bảo Ngọc',        'date' => '10/10/2026', 'items' => 1, 'valid_until' => '24/10/2026', 'status' => 'Hết hạn'],
];

// BAOHANH
$mockWarranty = [
    ['code' => 'BH000012', 'order' => 'DH000121', 'product' => 'Aptomat MCB 2P 32A 6kA',  'type' => 'Bảo hành', 'date' => '03/11/2026', 'status' => 'Mới'],
    ['code' => 'BH000011', 'order' => 'DH000110', 'product' => 'Ổ cắm đơn đa năng Galion', 'type' => 'Đổi trả',  'date' => '30/10/2026', 'status' => 'Đang xử lý'],
    ['code' => 'BH000010', 'order' => 'DH000098', 'product' => 'Công tắc 1 chiều Arteor',  'type' => 'Bảo hành', 'date' => '18/10/2026', 'status' => 'Đã phản hồi'],
    ['code' => 'BH000009', 'order' => 'DH000071', 'product' => 'RCBO 1P+N 20A 30mA',       'type' => 'Đổi trả',  'date' => '05/10/2026', 'status' => 'Từ chối'],
];

// TONKHO
$mockStock = [
    ['code' => '282434-C',  'name' => 'Ổ cắm đơn đa năng Galion',  'qty' => 120, 'threshold' => 20, 'status' => 'Còn hàng'],
    ['code' => 'KA4804KG',  'name' => 'Mặt công tắc Living Now – đen', 'qty' => 7,  'threshold' => 10, 'status' => 'Sắp hết'],
    ['code' => '419702',    'name' => 'Aptomat MCB 2P 32A 6kA',    'qty' => 3,   'threshold' => 10, 'status' => 'Sắp hết'],
    ['code' => '572170',    'name' => 'Ổ cắm đôi Mallia Senses',   'qty' => 0,   'threshold' => 10, 'status' => 'Hết hàng'],
    ['code' => '419676',    'name' => 'Aptomat MCB 1P 20A 6kA',    'qty' => 200, 'threshold' => 30, 'status' => 'Còn hàng'],
];

// PHIEUNHAP
$mockPurchases = [
    ['code' => 'PN000018', 'supplier' => 'Công ty Phân phối Điện Miền Nam', 'staff' => 'Thủ kho 01', 'date' => '01/11/2026', 'items' => 8,  'total' => 18450000],
    ['code' => 'PN000017', 'supplier' => 'Công ty Phân phối Điện Miền Nam', 'staff' => 'Thủ kho 01', 'date' => '20/10/2026', 'items' => 5,  'total' => 9210000],
    ['code' => 'PN000016', 'supplier' => 'Đại lý Bticino Sài Gòn',           'staff' => 'Thủ kho 02', 'date' => '09/10/2026', 'items' => 3,  'total' => 4120000],
];

// NHACUNGCAP
$mockSuppliers = [
    ['id' => 1, 'name' => 'Công ty Phân phối Điện Miền Nam', 'phone' => '028 0000 0001', 'address' => 'TP. Hồ Chí Minh'],
    ['id' => 2, 'name' => 'Đại lý Bticino Sài Gòn',           'phone' => '028 0000 0002', 'address' => 'TP. Hồ Chí Minh'],
];

// NGUOIDUNG + VAITRO
$mockUsers = [
    ['name' => 'Quản trị viên',      'email' => 'admin@diennha.local',  'role' => 'Quản trị viên',     'status' => 'Hoạt động'],
    ['name' => 'Nhân viên bán hàng', 'email' => 'banhang@diennha.local','role' => 'Nhân viên bán hàng','status' => 'Hoạt động'],
    ['name' => 'Thủ kho 01',         'email' => 'thukho@diennha.local', 'role' => 'Thủ kho',           'status' => 'Hoạt động'],
    ['name' => 'Nguyễn Văn An',      'email' => 'an@example.com',       'role' => 'Khách hàng',        'status' => 'Hoạt động'],
    ['name' => 'Đỗ Thanh Tâm',       'email' => 'tam@example.com',      'role' => 'Khách hàng',        'status' => 'Bị khóa'],
];
$mockRoles = ['Quản trị viên', 'Nhân viên bán hàng', 'Thủ kho', 'Khách hàng'];

// Báo biểu BB01–BB07 (mục 3.4)
$mockReports = [
    ['code' => 'BB01', 'name' => 'Doanh thu theo ngày, tháng',     'viewer' => 'Quản trị viên',       'desc' => 'Tổng tiền các đơn hoàn tất theo kỳ'],
    ['code' => 'BB02', 'name' => 'Sản phẩm bán chạy',              'viewer' => 'Quản trị viên',       'desc' => 'Sản phẩm xếp theo số lượng bán'],
    ['code' => 'BB03', 'name' => 'Tồn kho và cảnh báo',            'viewer' => 'Thủ kho',             'desc' => 'Số tồn, sản phẩm dưới ngưỡng'],
    ['code' => 'BB04', 'name' => 'Phiếu nhập theo kỳ',             'viewer' => 'Thủ kho',             'desc' => 'Danh sách và tổng tiền nhập'],
    ['code' => 'BB05', 'name' => 'Đơn hàng theo trạng thái',       'viewer' => 'Nhân viên bán hàng',  'desc' => 'Số đơn ở từng trạng thái'],
    ['code' => 'BB06', 'name' => 'Bảo hành, đổi trả theo kỳ',      'viewer' => 'Nhân viên bán hàng',  'desc' => 'Số yêu cầu và kết quả xử lý'],
    ['code' => 'BB07', 'name' => 'Yêu cầu báo giá theo kỳ',        'viewer' => 'Nhân viên bán hàng',  'desc' => 'Số yêu cầu, số được chấp nhận, số hết hạn'],
];
