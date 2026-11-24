-- =====================================================================
-- HỆ THỐNG QUẢN LÝ VÀ KINH DOANH THIẾT BỊ ĐIỆN GIA DỤNG TRỰC TUYẾN
-- Cơ sở dữ liệu MySQL (InnoDB, utf8mb4) - chạy trên WampServer / phpMyAdmin
-- Dựa theo báo cáo đồ án: mục 3.1 (mô hình dữ liệu) và 3.4 (báo biểu)
--
-- Lưu ý: đơn mẫu #3 (chờ thanh toán) và báo giá mẫu #1 dùng ngày tương đối so với lúc import (NOW()), nên luôn còn hạn:
--   báo giá còn 14 ngày, đơn chờ thanh toán còn 23 giờ (hết hạn sẽ bị tự hủy như thường).
-- Dữ liệu mẫu: 20 sản phẩm (17 sản phẩm bán thẳng, trong đó 2 có giá khuyến mãi; 3 sản phẩm bán theo báo giá).
-- Cách dùng: phpMyAdmin -> tab Import -> chọn file này (hoặc dán vào tab SQL).
-- File tự tạo CSDL quanly_thietbidien. Yêu cầu MySQL 8.0.16+ (CHECK có hiệu lực).
-- Tài khoản mẫu: mật khẩu của tất cả là 123456 (bcrypt, tương thích password_verify của PHP)
-- =====================================================================

-- CẢNH BÁO: file này chỉ dành cho lần cài đầu tiên. Import vào CSDL đã có dữ liệu sẽ báo lỗi "table already exists"
-- chứ không xóa dữ liệu. Muốn cài lại từ đầu (MẤT TOÀN BỘ DỮ LIỆU), tự chạy dòng sau rồi import lại:
--   DROP DATABASE IF EXISTS quanly_thietbidien;
CREATE DATABASE IF NOT EXISTS quanly_thietbidien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanly_thietbidien;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================ 1. TẠO BẢNG ============================

CREATE TABLE VAITRO (
  MaVT  INT AUTO_INCREMENT PRIMARY KEY,
  TenVT VARCHAR(50) NOT NULL,
  CONSTRAINT UQ_VAITRO_TenVT UNIQUE (TenVT)
) ENGINE=InnoDB;

CREATE TABLE NGUOIDUNG (
  MaND             INT AUTO_INCREMENT PRIMARY KEY,
  HoTen            VARCHAR(100) NOT NULL,
  Email            VARCHAR(100) NOT NULL,
  SoDienThoai      VARCHAR(15)  NOT NULL,
  MatKhauBam       VARCHAR(255) NOT NULL,
  TrangThai        TINYINT(1)   NOT NULL DEFAULT 1,
  SoLanSaiLienTiep INT          NOT NULL DEFAULT 0,
  KhoaDen          DATETIME     NULL,
  MaVT             INT          NOT NULL,
  CONSTRAINT UQ_NGUOIDUNG_Email UNIQUE (Email),
  CONSTRAINT CK_NGUOIDUNG_SaiLienTiep CHECK (SoLanSaiLienTiep >= 0),
  CONSTRAINT FK_NGUOIDUNG_VAITRO FOREIGN KEY (MaVT) REFERENCES VAITRO(MaVT)
) ENGINE=InnoDB;

CREATE TABLE DIACHI (
  MaDC          INT AUTO_INCREMENT PRIMARY KEY,
  MaND          INT          NOT NULL,
  NguoiNhan     VARCHAR(100) NOT NULL,
  SoDienThoai   VARCHAR(15)  NOT NULL,
  DiaChiChiTiet VARCHAR(255) NOT NULL,
  MacDinh       TINYINT(1)   NOT NULL DEFAULT 0,
  CONSTRAINT FK_DIACHI_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

CREATE TABLE DONGSANPHAM (
  MaDong  INT AUTO_INCREMENT PRIMARY KEY,
  TenDong VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE DANHMUC (
  MaDM    INT AUTO_INCREMENT PRIMARY KEY,
  TenDM   VARCHAR(100) NOT NULL,
  MaDMCha INT NULL,
  CONSTRAINT FK_DANHMUC_Cha FOREIGN KEY (MaDMCha) REFERENCES DANHMUC(MaDM)
) ENGINE=InnoDB;

CREATE TABLE SANPHAM (
  MaSP            INT AUTO_INCREMENT PRIMARY KEY,
  MaHang          VARCHAR(50)  NOT NULL,
  TenSP           VARCHAR(200) NOT NULL,
  Slug            VARCHAR(220) NOT NULL,
  MaDM            INT NOT NULL,
  MaDong          INT NULL,
  XuatXu          VARCHAR(150) NULL,
  NamRaMat        SMALLINT NULL,
  GiaNhap         DECIMAL(18,0) NULL,
  GiaNiemYet      DECIMAL(18,0) NULL,
  GiaBan          DECIMAL(18,0) NULL,
  PhanTramGiam    DECIMAL(5,2) NOT NULL DEFAULT 0,
  DonViTinh       VARCHAR(20)  NOT NULL DEFAULT 'Cái',
  LienHeBaoGia    TINYINT(1) NOT NULL DEFAULT 0,
  TinhTrangKD     VARCHAR(20)  NOT NULL DEFAULT 'Đang kinh doanh',
  LuotXem         INT NOT NULL DEFAULT 0,
  DaBan           INT NOT NULL DEFAULT 0,
  PhuKien         TEXT NULL,
  VideoUrl        VARCHAR(255) NULL,
  MoTaNgan        VARCHAR(500) NULL,
  MoTa            TEXT NULL,
  ThoiGianBH      INT NOT NULL DEFAULT 0,
  HinhThucBH      VARCHAR(100) NULL,
  SoNgayDoiTra    INT NOT NULL DEFAULT 3,
  ChinhSachHauMai TEXT NULL,
  TrangThai       TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT UQ_SANPHAM_MaHang UNIQUE (MaHang),
  CONSTRAINT UQ_SANPHAM_Slug UNIQUE (Slug),
  CONSTRAINT CK_SANPHAM_GiaNhap CHECK (GiaNhap IS NULL OR GiaNhap >= 0),
  CONSTRAINT CK_SANPHAM_Gia CHECK (GiaNiemYet IS NULL OR GiaNiemYet >= 0),
  CONSTRAINT CK_SANPHAM_GiaBan CHECK (GiaBan IS NULL OR (GiaBan >= 0 AND (GiaNiemYet IS NULL OR GiaBan <= GiaNiemYet))),
  CONSTRAINT CK_SANPHAM_Giam CHECK (PhanTramGiam BETWEEN 0 AND 100),
  CONSTRAINT CK_SANPHAM_BanHang CHECK (LienHeBaoGia = 1 OR (GiaNiemYet IS NOT NULL AND GiaBan IS NOT NULL)),
  CONSTRAINT CK_SANPHAM_TinhTrangKD CHECK (TinhTrangKD IN ('Đang kinh doanh','Ngừng kinh doanh','Hàng sắp về')),
  CONSTRAINT CK_SANPHAM_LuotXem CHECK (LuotXem >= 0),
  CONSTRAINT CK_SANPHAM_DaBan CHECK (DaBan >= 0),
  CONSTRAINT CK_SANPHAM_BH CHECK (ThoiGianBH >= 0),
  CONSTRAINT CK_SANPHAM_DoiTra CHECK (SoNgayDoiTra >= 0),
  CONSTRAINT FK_SANPHAM_DANHMUC FOREIGN KEY (MaDM) REFERENCES DANHMUC(MaDM),
  CONSTRAINT FK_SANPHAM_DONGSANPHAM FOREIGN KEY (MaDong) REFERENCES DONGSANPHAM(MaDong),
  INDEX IX_SANPHAM_TenSP (TenSP)
) ENGINE=InnoDB;

CREATE TABLE SANPHAM_LIENQUAN (
  MaSP         INT NOT NULL,
  MaSPLienQuan INT NOT NULL,
  LoaiGoiY     VARCHAR(30) NOT NULL,
  PRIMARY KEY (MaSP, MaSPLienQuan),
  CONSTRAINT CK_SPLQ_Loai CHECK (LoaiGoiY IN ('Cùng phân khúc','Cùng dòng sản phẩm','Mua kèm')),
  CONSTRAINT FK_SPLQ_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE,
  CONSTRAINT FK_SPLQ_LIENQUAN FOREIGN KEY (MaSPLienQuan) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE THONGSO (
  MaTS  INT AUTO_INCREMENT PRIMARY KEY,
  TenTS VARCHAR(100) NOT NULL,
  DonVi VARCHAR(20) NULL,
  NhomTS VARCHAR(50) NULL,
  MaDM  INT NOT NULL,
  CONSTRAINT FK_THONGSO_DANHMUC FOREIGN KEY (MaDM) REFERENCES DANHMUC(MaDM)
) ENGINE=InnoDB;

CREATE TABLE CT_THONGSO (
  MaSP   INT NOT NULL,
  MaTS   INT NOT NULL,
  GiaTri VARCHAR(100) NOT NULL,
  PRIMARY KEY (MaSP, MaTS),
  CONSTRAINT FK_CTTS_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE,
  CONSTRAINT FK_CTTS_THONGSO FOREIGN KEY (MaTS) REFERENCES THONGSO(MaTS)
) ENGINE=InnoDB;

CREATE TABLE HINHANH (
  MaHA       INT AUTO_INCREMENT PRIMARY KEY,
  MaSP       INT NOT NULL,
  DuongDan   VARCHAR(255) NOT NULL,
  LaAnhChinh TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT FK_HINHANH_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE TAILIEU (
  MaTL     INT AUTO_INCREMENT PRIMARY KEY,
  MaDong   INT NULL,
  MaSP     INT NULL,
  TenTL    VARCHAR(200) NOT NULL,
  DuongDan VARCHAR(255) NOT NULL,
  CONSTRAINT CK_TAILIEU_CoNguon CHECK (MaDong IS NOT NULL OR MaSP IS NOT NULL),
  CONSTRAINT FK_TAILIEU_DONGSANPHAM FOREIGN KEY (MaDong) REFERENCES DONGSANPHAM(MaDong),
  CONSTRAINT FK_TAILIEU_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP)
) ENGINE=InnoDB;

CREATE TABLE NHACUNGCAP (
  MaNCC       INT AUTO_INCREMENT PRIMARY KEY,
  TenNCC      VARCHAR(200) NOT NULL,
  SoDienThoai VARCHAR(15)  NULL,
  DiaChi      VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE PHIEUNHAP (
  MaPN     INT AUTO_INCREMENT PRIMARY KEY,
  MaNCC    INT NOT NULL,
  MaND     INT NOT NULL,
  NgayNhap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TongTien DECIMAL(18,0) NOT NULL DEFAULT 0,
  CONSTRAINT CK_PHIEUNHAP_Tong CHECK (TongTien >= 0),
  CONSTRAINT FK_PHIEUNHAP_NCC FOREIGN KEY (MaNCC) REFERENCES NHACUNGCAP(MaNCC),
  CONSTRAINT FK_PHIEUNHAP_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

CREATE TABLE CT_PHIEUNHAP (
  MaPN    INT NOT NULL,
  MaSP    INT NOT NULL,
  SoLuong INT NOT NULL,
  GiaNhap DECIMAL(18,0) NOT NULL,
  PRIMARY KEY (MaPN, MaSP),
  CONSTRAINT CK_CTPN_SoLuong CHECK (SoLuong > 0),
  CONSTRAINT CK_CTPN_Gia CHECK (GiaNhap >= 0),
  CONSTRAINT FK_CTPN_PHIEUNHAP FOREIGN KEY (MaPN) REFERENCES PHIEUNHAP(MaPN) ON DELETE CASCADE,
  CONSTRAINT FK_CTPN_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP)
) ENGINE=InnoDB;

CREATE TABLE TONKHO (
  MaSP          INT PRIMARY KEY,
  SoLuongTon    INT NOT NULL DEFAULT 0,
  NguongCanhBao INT NOT NULL DEFAULT 0,
  CONSTRAINT CK_TONKHO_Ton CHECK (SoLuongTon >= 0),
  CONSTRAINT CK_TONKHO_Nguong CHECK (NguongCanhBao >= 0),
  CONSTRAINT FK_TONKHO_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE PHIEUKIEMKE (
  MaKK  INT AUTO_INCREMENT PRIMARY KEY,
  MaND  INT NOT NULL,
  NgayKK DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_PHIEUKIEMKE_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

CREATE TABLE CT_PHIEUKIEMKE (
  MaKK           INT NOT NULL,
  MaSP           INT NOT NULL,
  SoLuongHeThong INT NOT NULL,
  SoLuongThucTe  INT NOT NULL,
  LyDo           VARCHAR(255) NULL,
  PRIMARY KEY (MaKK, MaSP),
  CONSTRAINT CK_CTKK_HeThong CHECK (SoLuongHeThong >= 0),
  CONSTRAINT CK_CTKK_ThucTe CHECK (SoLuongThucTe >= 0),
  CONSTRAINT CK_CTKK_LyDo CHECK (SoLuongHeThong = SoLuongThucTe OR (LyDo IS NOT NULL AND LyDo <> '')),
  CONSTRAINT FK_CTKK_PHIEUKIEMKE FOREIGN KEY (MaKK) REFERENCES PHIEUKIEMKE(MaKK) ON DELETE CASCADE,
  CONSTRAINT FK_CTKK_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP)
) ENGINE=InnoDB;

CREATE TABLE YEUTHICH (
  MaND     INT NOT NULL,
  MaSP     INT NOT NULL,
  NgayThem DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (MaND, MaSP),
  CONSTRAINT FK_YEUTHICH_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND),
  CONSTRAINT FK_YEUTHICH_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE GIOHANG (
  MaGH INT AUTO_INCREMENT PRIMARY KEY,
  MaND INT NOT NULL,
  CONSTRAINT UQ_GIOHANG_MaND UNIQUE (MaND),
  CONSTRAINT FK_GIOHANG_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

CREATE TABLE CT_GIOHANG (
  MaGH    INT NOT NULL,
  MaSP    INT NOT NULL,
  SoLuong INT NOT NULL,
  DonGia  DECIMAL(18,0) NULL,
  PRIMARY KEY (MaGH, MaSP),
  CONSTRAINT CK_CTGH_SoLuong CHECK (SoLuong > 0),
  CONSTRAINT CK_CTGH_DonGia CHECK (DonGia IS NULL OR DonGia >= 0),
  CONSTRAINT FK_CTGH_GIOHANG FOREIGN KEY (MaGH) REFERENCES GIOHANG(MaGH) ON DELETE CASCADE,
  CONSTRAINT FK_CTGH_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE DONHANG (
  MaDH            INT AUTO_INCREMENT PRIMARY KEY,
  MaND            INT NOT NULL,
  MaDC            INT NULL,
  NguoiNhan       VARCHAR(100) NOT NULL,
  SoDienThoaiNhan VARCHAR(15)  NOT NULL,
  DiaChiGiao      VARCHAR(255) NOT NULL,
  NgayDat         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NgayHoanTat     DATETIME NULL,
  TrangThai       VARCHAR(20) NOT NULL,
  PhiGiao         DECIMAL(18,0) NOT NULL DEFAULT 0,
  TongTien        DECIMAL(18,0) NOT NULL DEFAULT 0,
  HanThanhToan    DATETIME NULL,
  CONSTRAINT CK_DONHANG_TrangThai CHECK (TrangThai IN
    ('Chờ thanh toán','Chờ xác nhận','Đã xác nhận','Đang giao','Hoàn tất','Đã hủy')),
  CONSTRAINT CK_DONHANG_Phi CHECK (PhiGiao >= 0),
  CONSTRAINT CK_DONHANG_Tong CHECK (TongTien >= 0),
  CONSTRAINT FK_DONHANG_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND),
  -- Khách xóa địa chỉ trong sổ thì đơn cũ vẫn giữ bản sao NguoiNhan/SoDienThoaiNhan/DiaChiGiao
  CONSTRAINT FK_DONHANG_DIACHI FOREIGN KEY (MaDC) REFERENCES DIACHI(MaDC) ON DELETE SET NULL,
  INDEX IX_DONHANG_TrangThai (TrangThai),
  INDEX IX_DONHANG_NgayDat (NgayDat)
) ENGINE=InnoDB;

CREATE TABLE CT_DONHANG (
  MaDH    INT NOT NULL,
  MaSP    INT NOT NULL,
  SoLuong INT NOT NULL,
  DonGia  DECIMAL(18,0) NOT NULL,
  PRIMARY KEY (MaDH, MaSP),
  CONSTRAINT CK_CTDH_SoLuong CHECK (SoLuong > 0),
  CONSTRAINT CK_CTDH_DonGia CHECK (DonGia >= 0),
  CONSTRAINT FK_CTDH_DONHANG FOREIGN KEY (MaDH) REFERENCES DONHANG(MaDH) ON DELETE CASCADE,
  CONSTRAINT FK_CTDH_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP)
) ENGINE=InnoDB;

CREATE TABLE THANHTOAN (
  MaTT       INT AUTO_INCREMENT PRIMARY KEY,
  MaDH       INT NOT NULL,
  PhuongThuc VARCHAR(30) NOT NULL,
  SoTien     DECIMAL(18,0) NOT NULL,
  TrangThai  VARCHAR(20) NOT NULL DEFAULT 'Chưa thanh toán',
  ThoiGian   DATETIME NULL,
  CONSTRAINT CK_THANHTOAN_PhuongThuc CHECK (PhuongThuc IN ('Thanh toán khi nhận hàng','Chuyển khoản')),
  CONSTRAINT CK_THANHTOAN_TrangThai CHECK (TrangThai IN ('Chưa thanh toán','Đã thanh toán')),
  CONSTRAINT CK_THANHTOAN_SoTien CHECK (SoTien >= 0),
  CONSTRAINT FK_THANHTOAN_DONHANG FOREIGN KEY (MaDH) REFERENCES DONHANG(MaDH) ON DELETE CASCADE
) ENGINE=InnoDB;

-- MaND = NULL nghĩa là hệ thống tự thực hiện (ví dụ tự hủy đơn quá hạn 24 giờ)
CREATE TABLE LICHSU_DONHANG (
  MaLS         INT AUTO_INCREMENT PRIMARY KEY,
  MaDH         INT NOT NULL,
  MaND         INT NULL,
  TrangThaiCu  VARCHAR(20) NULL,
  TrangThaiMoi VARCHAR(20) NOT NULL,
  ThoiGian     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  GhiChu       VARCHAR(255) NULL,
  CONSTRAINT FK_LSDH_DONHANG FOREIGN KEY (MaDH) REFERENCES DONHANG(MaDH) ON DELETE CASCADE,
  CONSTRAINT FK_LSDH_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

CREATE TABLE BAOGIA (
  MaBG        INT AUTO_INCREMENT PRIMARY KEY,
  MaND        INT NOT NULL,
  MaNVXuLy    INT NULL,
  MaDH        INT NULL,
  NgayGui     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  GhiChu      VARCHAR(255) NULL,
  TrangThai   VARCHAR(20) NOT NULL DEFAULT 'Mới',
  HanHieuLuc  DATETIME NULL,
  NgayTraLoi  DATETIME NULL,
  LyDoKhongBG VARCHAR(255) NULL,
  CONSTRAINT CK_BAOGIA_TrangThai CHECK (TrangThai IN
    ('Mới','Đã báo giá','Đã chấp nhận','Đã từ chối','Hết hạn','Không báo giá được')),
  CONSTRAINT FK_BAOGIA_KHACH FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND),
  CONSTRAINT FK_BAOGIA_NHANVIEN FOREIGN KEY (MaNVXuLy) REFERENCES NGUOIDUNG(MaND),
  CONSTRAINT FK_BAOGIA_DONHANG FOREIGN KEY (MaDH) REFERENCES DONHANG(MaDH)
) ENGINE=InnoDB;

CREATE TABLE CT_BAOGIA (
  MaBG      INT NOT NULL,
  MaSP      INT NOT NULL,
  SoLuong   INT NOT NULL,
  DonGiaBao DECIMAL(18,0) NULL,
  PRIMARY KEY (MaBG, MaSP),
  CONSTRAINT CK_CTBG_SoLuong CHECK (SoLuong > 0),
  CONSTRAINT CK_CTBG_Gia CHECK (DonGiaBao IS NULL OR DonGiaBao >= 0),
  CONSTRAINT FK_CTBG_BAOGIA FOREIGN KEY (MaBG) REFERENCES BAOGIA(MaBG) ON DELETE CASCADE,
  CONSTRAINT FK_CTBG_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP)
) ENGINE=InnoDB;

CREATE TABLE DANHGIA (
  MaDG   INT AUTO_INCREMENT PRIMARY KEY,
  MaND   INT NOT NULL,
  MaSP   INT NOT NULL,
  SoSao  TINYINT NOT NULL,
  NoiDung VARCHAR(500) NULL,
  HinhAnh VARCHAR(255) NULL,
  NgayDG DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PhanHoi VARCHAR(500) NULL,
  NgayPhanHoi DATETIME NULL,
  CONSTRAINT CK_DANHGIA_SoSao CHECK (SoSao BETWEEN 1 AND 5),
  CONSTRAINT UQ_DANHGIA_ND_SP UNIQUE (MaND, MaSP),
  CONSTRAINT FK_DANHGIA_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND),
  CONSTRAINT FK_DANHGIA_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE BAOHANH (
  MaBH       INT AUTO_INCREMENT PRIMARY KEY,
  MaDH       INT NOT NULL,
  MaSP       INT NOT NULL,
  MaNVXuLy   INT NULL,
  LoaiYeuCau VARCHAR(20) NOT NULL,
  MoTa       TEXT NOT NULL,
  TrangThai  VARCHAR(20) NOT NULL DEFAULT 'Mới',
  NgayTao    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PhanHoi    VARCHAR(500) NULL,
  NgayXuLy   DATETIME NULL,
  CONSTRAINT CK_BAOHANH_Loai CHECK (LoaiYeuCau IN ('Bảo hành','Đổi trả')),
  CONSTRAINT CK_BAOHANH_TrangThai CHECK (TrangThai IN ('Mới','Đã bảo hành','Đã đổi sản phẩm','Từ chối')),
  CONSTRAINT CK_BAOHANH_PhanHoi CHECK (TrangThai <> 'Từ chối' OR (PhanHoi IS NOT NULL AND PhanHoi <> '')),
  CONSTRAINT FK_BAOHANH_DONHANG FOREIGN KEY (MaDH) REFERENCES DONHANG(MaDH),
  CONSTRAINT FK_BAOHANH_SANPHAM FOREIGN KEY (MaSP) REFERENCES SANPHAM(MaSP),
  CONSTRAINT FK_BAOHANH_NHANVIEN FOREIGN KEY (MaNVXuLy) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

CREATE TABLE TINTUC (
  MaTin     INT AUTO_INCREMENT PRIMARY KEY,
  MaND      INT NOT NULL,
  TieuDe    VARCHAR(200) NOT NULL,
  NoiDung   TEXT NOT NULL,
  NgayDang  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TrangThai TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT FK_TINTUC_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND)
) ENGINE=InnoDB;

-- Tin nhắn từ biểu mẫu Liên hệ (MH Liên hệ); MaND có giá trị nếu người gửi đã đăng nhập
CREATE TABLE LIENHE (
  MaLH        INT AUTO_INCREMENT PRIMARY KEY,
  MaND        INT NULL,
  HoTen       VARCHAR(100)  NOT NULL,
  SoDienThoai VARCHAR(15)   NULL,
  Email       VARCHAR(150)  NOT NULL,
  NoiDung     VARCHAR(1000) NOT NULL,
  NgayGui     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  DaXuLy      TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT FK_LIENHE_NGUOIDUNG FOREIGN KEY (MaND) REFERENCES NGUOIDUNG(MaND) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Email đăng ký nhận tin và báo giá ở chân trang
CREATE TABLE NHANTIN (
  MaNT        INT AUTO_INCREMENT PRIMARY KEY,
  Email       VARCHAR(150) NOT NULL,
  NgayDangKy  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT UQ_NHANTIN_Email UNIQUE (Email)
) ENGINE=InnoDB;

-- ===================== 2. TRIGGER (quy tắc nhất quán) =====================
DELIMITER $$

-- Chỉ cho phép các chuyển trạng thái đơn hàng ở Bảng 2-3; tự ghi NgayHoanTat khi hoàn tất
CREATE TRIGGER trg_DONHANG_ChuyenTrangThai BEFORE UPDATE ON DONHANG
FOR EACH ROW
BEGIN
  IF NEW.TrangThai <> OLD.TrangThai THEN
    IF NOT (
         (OLD.TrangThai = 'Chờ thanh toán' AND NEW.TrangThai IN ('Chờ xác nhận','Đã hủy'))
      OR (OLD.TrangThai = 'Chờ xác nhận'   AND NEW.TrangThai IN ('Đã xác nhận','Đã hủy'))
      OR (OLD.TrangThai = 'Đã xác nhận'    AND NEW.TrangThai IN ('Đang giao','Đã hủy'))
      OR (OLD.TrangThai = 'Đang giao'      AND NEW.TrangThai = 'Hoàn tất')
    ) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chuyen trang thai don hang khong hop le';
    END IF;
    IF NEW.TrangThai = 'Hoàn tất' AND NEW.NgayHoanTat IS NULL THEN
      SET NEW.NgayHoanTat = NOW();
    END IF;
    -- DaBan cộng thêm khi đơn chuyển sang Hoàn tất (chỉ dùng hiển thị, sắp xếp bán chạy)
    IF NEW.TrangThai = 'Hoàn tất' THEN
      UPDATE SANPHAM s JOIN CT_DONHANG c ON c.MaSP = s.MaSP
         SET s.DaBan = s.DaBan + c.SoLuong
       WHERE c.MaDH = NEW.MaDH;
    END IF;
  END IF;
END$$

-- Giỏ hàng lưu đơn giá bán hiện hành lúc khách thêm hoặc cập nhật sản phẩm (Bảng 3-28)
CREATE TRIGGER trg_CTGIOHANG_DonGia_Them BEFORE INSERT ON CT_GIOHANG
FOR EACH ROW
BEGIN
  SET NEW.DonGia = (SELECT GiaBan FROM SANPHAM WHERE MaSP = NEW.MaSP);
END$$

CREATE TRIGGER trg_CTGIOHANG_DonGia_Sua BEFORE UPDATE ON CT_GIOHANG
FOR EACH ROW
BEGIN
  SET NEW.DonGia = (SELECT GiaBan FROM SANPHAM WHERE MaSP = NEW.MaSP);
END$$

-- Một sản phẩm không được tự liên quan đến chính nó. Không dùng CHECK vì MySQL cấm CHECK trên cột có
-- khóa ngoại ON DELETE CASCADE (lỗi 3823).
CREATE TRIGGER trg_SPLQ_KhacNhau_Them BEFORE INSERT ON SANPHAM_LIENQUAN
FOR EACH ROW
BEGIN
  IF NEW.MaSP = NEW.MaSPLienQuan THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'San pham lien quan phai khac san pham goc';
  END IF;
END$$

CREATE TRIGGER trg_SPLQ_KhacNhau_Sua BEFORE UPDATE ON SANPHAM_LIENQUAN
FOR EACH ROW
BEGIN
  IF NEW.MaSP = NEW.MaSPLienQuan THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'San pham lien quan phai khac san pham goc';
  END IF;
END$$

-- ===================== 3. THỦ TỤC LƯU TRỮ =====================

-- Đặt hàng từ giỏ hàng: kiểm tra + khóa tồn kho + tạo đơn + trừ tồn trong MỘT giao dịch (UC05)
CREATE PROCEDURE sp_DatHang(
  IN  p_MaND       INT,
  IN  p_MaDC       INT,
  IN  p_PhuongThuc VARCHAR(30),   -- 'Thanh toán khi nhận hàng' hoặc 'Chuyển khoản'
  IN  p_PhiGiao    DECIMAL(18,0),
  IN  p_GiuGio     INT,            -- số giờ giữ đơn chuyển khoản (PAYMENT_HOLD_HOURS trong config)
  OUT p_MaDH       INT)
BEGIN
  DECLARE v_MaGH   INT;
  DECLARE v_Thieu  VARCHAR(500) DEFAULT NULL;
  DECLARE v_Hang   DECIMAL(18,0);
  DECLARE v_Tong   DECIMAL(18,0);
  DECLARE v_Nguoi  VARCHAR(100);
  DECLARE v_Sdt    VARCHAR(15);
  DECLARE v_DiaChi VARCHAR(255);
  DECLARE v_TT     VARCHAR(20);
  DECLARE v_Han    DATETIME DEFAULT NULL;
  DECLARE v_Msg    VARCHAR(200);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  START TRANSACTION;

  SELECT MaGH INTO v_MaGH FROM GIOHANG WHERE MaND = p_MaND;
  IF v_MaGH IS NULL OR NOT EXISTS (SELECT 1 FROM CT_GIOHANG WHERE MaGH = v_MaGH) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Gio hang trong';
  END IF;

  SELECT NguoiNhan, SoDienThoai, DiaChiChiTiet INTO v_Nguoi, v_Sdt, v_DiaChi
    FROM DIACHI WHERE MaDC = p_MaDC AND MaND = p_MaND;
  IF v_Nguoi IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dia chi giao hang khong hop le';
  END IF;

  -- Khóa dòng tồn kho của các sản phẩm trong giỏ (chống âm tồn khi đặt đồng thời)
  SELECT COUNT(*) INTO v_Hang
    FROM CT_GIOHANG c JOIN TONKHO t ON t.MaSP = c.MaSP
   WHERE c.MaGH = v_MaGH FOR UPDATE;

  IF EXISTS (SELECT 1 FROM CT_GIOHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP
              WHERE c.MaGH = v_MaGH AND (s.LienHeBaoGia = 1 OR s.TrangThai = 0 OR s.TinhTrangKD <> 'Đang kinh doanh')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Gio hang co san pham chua niem yet gia hoac da an';
  END IF;

  SELECT LEFT(GROUP_CONCAT(s.TenSP SEPARATOR ', '), 90) INTO v_Thieu
    FROM CT_GIOHANG c
    JOIN SANPHAM s ON s.MaSP = c.MaSP
    JOIN TONKHO  t ON t.MaSP = c.MaSP
   WHERE c.MaGH = v_MaGH AND t.SoLuongTon < c.SoLuong
     FOR UPDATE OF t;   -- đọc có khóa: SELECT thường ở REPEATABLE READ sẽ đọc snapshot cũ (trước khi đơn khác commit)
  IF v_Thieu IS NOT NULL THEN
    SET v_Msg = CONCAT('Khong du ton kho: ', v_Thieu);
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_Msg;
  END IF;

  -- Giá đổi sau khi khách thêm vào giỏ: cập nhật giá mới trong giỏ, báo khách kiểm tra lại (UC05, luồng 6b)
  IF EXISTS (SELECT 1 FROM CT_GIOHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP
              WHERE c.MaGH = v_MaGH AND (c.DonGia IS NULL OR c.DonGia <> s.GiaBan)) THEN
    UPDATE CT_GIOHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP
       SET c.DonGia = s.GiaBan WHERE c.MaGH = v_MaGH;
    COMMIT;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Gia san pham da thay doi, vui long kiem tra lai gio hang';
  END IF;

  SELECT SUM(c.SoLuong * s.GiaBan) INTO v_Hang
    FROM CT_GIOHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaGH = v_MaGH
     FOR SHARE OF s;   -- giá mới nhất, khớp với DonGia ghi vào CT_DONHANG bên dưới
  SET v_Tong = v_Hang + p_PhiGiao;

  IF p_PhuongThuc = 'Chuyển khoản' THEN
    SET v_TT = 'Chờ thanh toán';
    SET v_Han = NOW() + INTERVAL p_GiuGio HOUR;
  ELSE
    SET v_TT = 'Chờ xác nhận';
  END IF;

  INSERT INTO DONHANG (MaND, MaDC, NguoiNhan, SoDienThoaiNhan, DiaChiGiao, NgayDat,
                       TrangThai, PhiGiao, TongTien, HanThanhToan)
  VALUES (p_MaND, p_MaDC, v_Nguoi, v_Sdt, v_DiaChi, NOW(), v_TT, p_PhiGiao, v_Tong, v_Han);
  SET p_MaDH = LAST_INSERT_ID();

  INSERT INTO CT_DONHANG (MaDH, MaSP, SoLuong, DonGia)
  SELECT p_MaDH, c.MaSP, c.SoLuong, s.GiaBan
    FROM CT_GIOHANG c JOIN SANPHAM s ON s.MaSP = c.MaSP WHERE c.MaGH = v_MaGH;

  UPDATE TONKHO t JOIN CT_GIOHANG c ON c.MaSP = t.MaSP
     SET t.SoLuongTon = t.SoLuongTon - c.SoLuong
   WHERE c.MaGH = v_MaGH;

  INSERT INTO THANHTOAN (MaDH, PhuongThuc, SoTien, TrangThai, ThoiGian)
  VALUES (p_MaDH, p_PhuongThuc, v_Tong, 'Chưa thanh toán', NULL);

  INSERT INTO LICHSU_DONHANG (MaDH, MaND, TrangThaiCu, TrangThaiMoi, ThoiGian, GhiChu)
  VALUES (p_MaDH, p_MaND, NULL, v_TT, NOW(), 'Tạo đơn');

  DELETE FROM CT_GIOHANG WHERE MaGH = v_MaGH;

  COMMIT;
END$$

-- Hủy đơn: cộng lại tồn kho + ghi lịch sử (UC06, UC12). p_MaND = NULL là hệ thống tự hủy.
-- Khách hàng (MaVT = 1) chỉ hủy được đơn ở "Chờ thanh toán" hoặc "Chờ xác nhận".
CREATE PROCEDURE sp_HuyDon(IN p_MaDH INT, IN p_MaND INT, IN p_LyDo VARCHAR(255))
BEGIN
  DECLARE v_TT  VARCHAR(20);
  DECLARE v_VT  INT DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  START TRANSACTION;
  SELECT TrangThai INTO v_TT FROM DONHANG WHERE MaDH = p_MaDH FOR UPDATE;
  IF v_TT IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Khong tim thay don hang';
  END IF;
  IF v_TT NOT IN ('Chờ thanh toán','Chờ xác nhận','Đã xác nhận') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Don hang khong the huy o trang thai hien tai';
  END IF;
  IF p_MaND IS NOT NULL THEN
    SELECT MaVT INTO v_VT FROM NGUOIDUNG WHERE MaND = p_MaND;
    IF v_VT = 1 AND v_TT = 'Đã xác nhận' THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Khach hang phai lien he cua hang de huy don da xac nhan';
    END IF;
  END IF;

  UPDATE TONKHO t JOIN CT_DONHANG d ON d.MaSP = t.MaSP
     SET t.SoLuongTon = t.SoLuongTon + d.SoLuong
   WHERE d.MaDH = p_MaDH;

  UPDATE DONHANG SET TrangThai = 'Đã hủy' WHERE MaDH = p_MaDH;

  INSERT INTO LICHSU_DONHANG (MaDH, MaND, TrangThaiCu, TrangThaiMoi, ThoiGian, GhiChu)
  VALUES (p_MaDH, p_MaND, v_TT, 'Đã hủy', NOW(), p_LyDo);
  COMMIT;
END$$

-- Tự hủy các đơn chuyển khoản quá 24 giờ chưa thanh toán
CREATE PROCEDURE sp_HuyDonQuaHan()
BEGIN
  DECLARE v_id INT;
  vong: LOOP
    SET v_id = NULL;
    SELECT MaDH INTO v_id FROM DONHANG
     WHERE TrangThai = 'Chờ thanh toán' AND HanThanhToan < NOW() LIMIT 1;
    IF v_id IS NULL THEN LEAVE vong; END IF;
    CALL sp_HuyDon(v_id, NULL, 'Quá hạn thanh toán');
  END LOOP;
END$$

DELIMITER ;

-- ============================ 4. BÁO BIỂU (VIEW) ============================
-- Lọc theo kỳ: SELECT * FROM vw_BB01_DoanhThuNgay WHERE Ngay BETWEEN '2026-10-01' AND '2026-10-31';

CREATE VIEW vw_BB01_DoanhThuNgay AS
SELECT DATE(NgayHoanTat) AS Ngay, COUNT(*) AS SoDonHoanTat, SUM(TongTien) AS DoanhThu
  FROM DONHANG WHERE TrangThai = 'Hoàn tất'
 GROUP BY DATE(NgayHoanTat);

CREATE VIEW vw_BB01_DoanhThuThang AS
SELECT DATE_FORMAT(NgayHoanTat, '%Y-%m') AS Thang, COUNT(*) AS SoDonHoanTat, SUM(TongTien) AS DoanhThu
  FROM DONHANG WHERE TrangThai = 'Hoàn tất'
 GROUP BY DATE_FORMAT(NgayHoanTat, '%Y-%m');

-- BB02: tính trên mọi đơn hoàn tất; lọc kỳ thì thêm WHERE d.NgayHoanTat BETWEEN ... vào câu truy vấn trong PHP
CREATE VIEW vw_BB02_SanPhamBanChay AS
SELECT RANK() OVER (ORDER BY SUM(c.SoLuong) DESC) AS Hang,
       s.MaHang, s.TenSP,
       SUM(c.SoLuong) AS SoLuongBan,
       SUM(c.SoLuong * c.DonGia) AS TienHang
  FROM CT_DONHANG c
  JOIN DONHANG d ON d.MaDH = c.MaDH AND d.TrangThai = 'Hoàn tất'
  JOIN SANPHAM s ON s.MaSP = c.MaSP
 GROUP BY s.MaSP, s.MaHang, s.TenSP;

CREATE VIEW vw_BB03_TonKho AS
SELECT s.MaHang, s.TenSP, t.SoLuongTon, t.NguongCanhBao,
       CASE WHEN t.SoLuongTon < t.NguongCanhBao THEN 'Cảnh báo' ELSE 'Bình thường' END AS TinhTrang
  FROM TONKHO t JOIN SANPHAM s ON s.MaSP = t.MaSP;

CREATE VIEW vw_BB04_PhieuNhap AS
SELECT p.MaPN, p.NgayNhap, n.TenNCC AS NhaCungCap, u.HoTen AS NguoiLap, p.TongTien
  FROM PHIEUNHAP p
  JOIN NHACUNGCAP n ON n.MaNCC = p.MaNCC
  JOIN NGUOIDUNG u ON u.MaND = p.MaND;

-- Lọc kỳ theo NgayDat: SELECT TrangThai, SUM(SoDon) FROM vw_BB05_DonHangTheoTrangThai WHERE Ngay BETWEEN ... GROUP BY TrangThai;
CREATE VIEW vw_BB05_DonHangTheoTrangThai AS
SELECT DATE(NgayDat) AS Ngay, TrangThai, COUNT(*) AS SoDon
  FROM DONHANG GROUP BY DATE(NgayDat), TrangThai;

CREATE VIEW vw_BB06_BaoHanhDoiTra AS
SELECT DATE(NgayTao) AS Ngay, LoaiYeuCau, TrangThai, COUNT(*) AS SoYeuCau
  FROM BAOHANH GROUP BY DATE(NgayTao), LoaiYeuCau, TrangThai;

CREATE VIEW vw_BB07_BaoGia AS
SELECT DATE(NgayGui) AS Ngay,
       COUNT(*) AS SoYeuCau,
       SUM(TrangThai = 'Đã chấp nhận') AS SoDuocChapNhan,
       SUM(TrangThai = 'Hết hạn')      AS SoHetHan,
       SUM(TrangThai IN ('Mới','Đã báo giá','Đã từ chối','Không báo giá được')) AS SoConLai
  FROM BAOGIA GROUP BY DATE(NgayGui);

-- ============================ 5. DỮ LIỆU MẪU ============================
-- Lưu ý: mã hàng có đuôi "DEMO" và các giá/thông số là dữ liệu giả để chạy thử,
-- cần thay bằng dữ liệu thật (có sự cho phép) trước khi đưa vào sử dụng.

INSERT INTO VAITRO (TenVT) VALUES ('Khách hàng'),('Nhân viên bán hàng'),('Thủ kho'),('Quản trị viên');

INSERT INTO NGUOIDUNG (HoTen, Email, SoDienThoai, MatKhauBam, MaVT) VALUES
 ('Quản Trị Viên',     'admin@demo.vn',    '0900000001', '$2b$10$XZFJ0vzMiWvKMD2dS./l0eT4IotVIVfry22tVP4Xhn.KjD3IOlTRi', 4),
 ('Nhân Viên Bán Hàng','nhanvien@demo.vn', '0900000002', '$2b$10$XZFJ0vzMiWvKMD2dS./l0eT4IotVIVfry22tVP4Xhn.KjD3IOlTRi', 2),
 ('Thủ Kho',           'thukho@demo.vn',   '0900000003', '$2b$10$XZFJ0vzMiWvKMD2dS./l0eT4IotVIVfry22tVP4Xhn.KjD3IOlTRi', 3),
 ('Nguyễn Văn An',     'khach1@demo.vn',   '0900000004', '$2b$10$XZFJ0vzMiWvKMD2dS./l0eT4IotVIVfry22tVP4Xhn.KjD3IOlTRi', 1),
 ('Trần Thị Bình',     'khach2@demo.vn',   '0900000005', '$2b$10$XZFJ0vzMiWvKMD2dS./l0eT4IotVIVfry22tVP4Xhn.KjD3IOlTRi', 1);

INSERT INTO DIACHI (MaND, NguoiNhan, SoDienThoai, DiaChiChiTiet, MacDinh) VALUES
 (4, 'Nguyễn Văn An', '0900000004', '12 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh', 1),
 (4, 'Nguyễn Văn An', '0900000004', '45 Lê Lợi, Quận 1, TP. Hồ Chí Minh', 0),
 (5, 'Trần Thị Bình', '0900000005', '88 Trần Hưng Đạo, Quận 5, TP. Hồ Chí Minh', 1);

INSERT INTO DONGSANPHAM (TenDong) VALUES
 ('Galion'),('Mallia Senses'),('Arteor'),('Living Now'),('ELOÉ');

INSERT INTO DANHMUC (TenDM, MaDMCha) VALUES
 ('Công tắc - ổ cắm', NULL),   -- 1
 ('Ổ cắm', 1),                 -- 2
 ('Mặt công tắc', 1),          -- 3
 ('MCB', NULL),                -- 4
 ('Tủ điện', NULL),            -- 5
 ('Hộp âm sàn', NULL);         -- 6

INSERT INTO THONGSO (TenTS, DonVi, NhomTS, MaDM) VALUES
 ('Số chấu',NULL,'Tính năng và tiện ích',2),
 ('Dòng định mức','A','Thông số điện năng',2),
 ('Số module','module','Kích thước và vật liệu',3),
 ('Số cực','cực','Thông số điện năng',4),
 ('Dòng định mức','A','Thông số điện năng',4),
 ('Số module','module','Kích thước và vật liệu',5),
 ('Điện áp định mức','V','Thông số điện năng',2),
 ('Chất liệu',NULL,'Kích thước và vật liệu',3),
 ('Điện áp định mức','V','Thông số điện năng',4),
 ('Chất liệu',NULL,'Kích thước và vật liệu',6);

INSERT INTO SANPHAM (MaHang, TenSP, Slug, MaDM, MaDong, GiaNhap, GiaNiemYet, GiaBan, PhanTramGiam, DonViTinh, LienHeBaoGia, LuotXem, DaBan, PhuKien, MoTaNgan, MoTa, ThoiGianBH, HinhThucBH, SoNgayDoiTra, ChinhSachHauMai) VALUES
 ('282434-C','Ổ cắm đơn đa năng Galion 282434-C','o-cam-don-da-nang-galion-282434-c',2,1,30000,45000,45000,0,'Cái',0,120,5,'Vít, khung đỡ','Ổ cắm đa năng, dòng 16A, dòng Galion','Ổ cắm đơn đa năng dòng Galion, lắp âm tường. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('282434-C1','Ổ cắm đơn đa năng Galion 282434-C1','o-cam-don-da-nang-galion-282434-c1',2,1,30000,45000,45000,0,'Cái',0,85,0,'Vít, khung đỡ','Ổ cắm đa năng, dòng 16A, dòng Galion','Biến thể màu mặt C1 của ổ cắm đơn đa năng Galion. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('282434-C2','Ổ cắm đơn đa năng Galion 282434-C2','o-cam-don-da-nang-galion-282434-c2',2,1,30000,45000,45000,0,'Cái',0,60,0,'Vít, khung đỡ','Ổ cắm đa năng, dòng 16A, dòng Galion','Biến thể màu mặt C2 của ổ cắm đơn đa năng Galion. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('282434-C3','Ổ cắm đơn đa năng Galion 282434-C3','o-cam-don-da-nang-galion-282434-c3',2,1,30000,45000,45000,0,'Cái',0,54,0,'Vít, khung đỡ','Ổ cắm đa năng, dòng 16A, dòng Galion','Biến thể màu mặt C3 của ổ cắm đơn đa năng Galion. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('KA4804KM','Mặt công tắc Legrand Living Now 4 module (màu cát)','mat-cong-tac-living-now-4-module-ka4804km',3,4,80000,120000,120000,0,'Cái',0,210,2,'Khung đỡ, vít','Mặt 4 module, màu cát, dòng Living Now','Mặt công tắc 4 module dòng Living Now, màu cát. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('KA4804KG','Mặt công tắc Legrand Living Now 4 module (màu đen)','mat-cong-tac-living-now-4-module-ka4804kg',3,4,80000,120000,120000,0,'Cái',0,175,0,'Khung đỡ, vít','Mặt 4 module, màu đen, dòng Living Now','Mặt công tắc 4 module dòng Living Now, màu đen. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('MCB-DEMO-1P-16A','Aptomat MCB 1 cực 16A (mẫu)','aptomat-mcb-1-cuc-16a-mau',4,NULL,38000,NULL,NULL,0,'Cái',1,95,0,NULL,'MCB 1 cực, 16A, bán theo báo giá','Aptomat MCB 1 cực 16A. Sản phẩm bán theo báo giá.',24,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('MCB-DEMO-2P-32A','Aptomat MCB 2 cực 32A (mẫu)','aptomat-mcb-2-cuc-32a-mau',4,NULL,120000,185000,185000,0,'Cái',0,140,0,NULL,'MCB 2 cực, 32A, cắt quá tải, ngắn mạch','Aptomat MCB 2 cực 32A. Dữ liệu mẫu.',24,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('TUDIEN-DEMO-12M','Tủ điện âm tường 12 module (mẫu)','tu-dien-am-tuong-12-module-mau',5,NULL,380000,NULL,NULL,0,'Cái',1,70,0,NULL,'Tủ âm tường 12 module, bán theo báo giá','Tủ điện âm tường 12 module. Sản phẩm bán theo báo giá.',12,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('TUDIEN-DEMO-18M','Tủ điện âm tường 18 module (mẫu)','tu-dien-am-tuong-18-module-mau',5,NULL,450000,650000,650000,0,'Cái',0,88,0,NULL,'Tủ âm tường 18 module, vỏ nhựa chống cháy','Tủ điện âm tường 18 module. Dữ liệu mẫu.',12,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('AMSAN-DEMO-01','Hộp âm sàn (mẫu)','hop-am-san-mau',6,NULL,600000,890000,890000,0,'Bộ',0,46,0,'Nắp đậy, vít','Hộp âm sàn, lắp được ổ cắm điện và dữ liệu','Hộp âm sàn dùng cho văn phòng, nhà ở. Dữ liệu mẫu.',12,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('ARTEOR-DEMO-02','Ổ cắm đôi Arteor (mẫu)','o-cam-doi-arteor-mau',2,3,65000,98000,98000,0,'Cái',0,102,0,'Vít, khung đỡ','Ổ cắm đôi, 16A, dòng Arteor','Ổ cắm đôi dòng Arteor. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('GALION-DEMO-CT1','Công tắc 1 chiều Galion (mẫu)','cong-tac-1-chieu-galion-mau',3,1,25000,38000,38000,0,'Cái',0,77,0,'Vít, khung đỡ','Công tắc 1 module, 1 chiều, dòng Galion','Công tắc 1 chiều dòng Galion. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('ARTEOR-DEMO-CT2','Công tắc đôi Arteor (mẫu)','cong-tac-doi-arteor-mau',3,3,45000,65000,65000,0,'Cái',0,64,0,'Vít, khung đỡ','Công tắc 2 module, dòng Arteor','Công tắc đôi dòng Arteor. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('MALLIA-DEMO-OC','Ổ cắm đơn Mallia Senses (mẫu)','o-cam-don-mallia-senses-mau',2,2,60000,95000,85000,10.53,'Cái',0,58,0,'Vít, khung đỡ','Ổ cắm đơn, 16A, dòng Mallia Senses, đang giảm giá','Ổ cắm đơn dòng Mallia Senses. Dữ liệu mẫu, có giá khuyến mãi.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('ELOE-DEMO-OC3','Ổ cắm 3 chấu ELOÉ (mẫu)','o-cam-3-chau-eloe-mau',2,5,50000,72000,72000,0,'Cái',0,49,0,'Vít, khung đỡ','Ổ cắm 3 chấu, 16A, dòng ELOÉ','Ổ cắm 3 chấu dòng ELOÉ. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('MCB-DEMO-1P-10A','Aptomat MCB 1 cực 10A (mẫu)','aptomat-mcb-1-cuc-10a-mau',4,NULL,52000,78000,78000,0,'Cái',0,81,0,NULL,'MCB 1 cực, 10A, dùng cho mạch chiếu sáng','Aptomat MCB 1 cực 10A. Dữ liệu mẫu.',24,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('MCB-DEMO-3P-63A','Aptomat MCB 3 cực 63A (mẫu)','aptomat-mcb-3-cuc-63a-mau',4,NULL,NULL,NULL,NULL,0,'Cái',1,39,0,NULL,'MCB 3 cực, 63A, bán theo báo giá','Aptomat MCB 3 cực 63A. Sản phẩm bán theo báo giá.',24,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('TUDIEN-DEMO-24M','Tủ điện âm tường 24 module (mẫu)','tu-dien-am-tuong-24-module-mau',5,NULL,900000,1400000,1250000,10.71,'Cái',0,52,0,NULL,'Tủ âm tường 24 module, đang giảm giá','Tủ điện âm tường 24 module. Dữ liệu mẫu, có giá khuyến mãi.',12,'Bảo hành tại trung tâm hãng',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.'),
 ('LIVING-DEMO-KA2','Mặt công tắc Legrand Living Now 2 module (mẫu)','mat-cong-tac-living-now-2-module-mau',3,4,65000,95000,95000,0,'Cái',0,43,0,'Khung đỡ, vít','Mặt 2 module, dòng Living Now','Mặt công tắc 2 module dòng Living Now. Dữ liệu mẫu.',12,'Bảo hành điện tử',3,'Đổi trả trong 3 ngày nếu lỗi do nhà sản xuất, giao hàng toàn quốc.');

INSERT INTO CT_THONGSO (MaSP, MaTS, GiaTri) VALUES
 (1,1,'2'),
 (1,2,'16'),
 (1,7,'250'),
 (2,1,'2'),
 (2,2,'16'),
 (2,7,'250'),
 (3,1,'2'),
 (3,2,'16'),
 (3,7,'250'),
 (4,1,'2'),
 (4,2,'16'),
 (4,7,'250'),
 (5,3,'4'),
 (5,8,'Nhựa PC'),
 (6,3,'4'),
 (6,8,'Nhựa PC'),
 (7,4,'1'),
 (7,5,'16'),
 (7,9,'230'),
 (8,4,'2'),
 (8,5,'32'),
 (8,9,'230'),
 (9,6,'12'),
 (10,6,'18'),
 (11,10,'Thép sơn tĩnh điện'),
 (12,1,'3'),
 (12,2,'16'),
 (12,7,'250'),
 (13,3,'1'),
 (13,8,'Nhựa PC'),
 (14,3,'2'),
 (14,8,'Nhựa PC'),
 (15,1,'3'),
 (15,2,'16'),
 (15,7,'250'),
 (16,1,'3'),
 (16,2,'16'),
 (16,7,'250'),
 (17,4,'1'),
 (17,5,'10'),
 (17,9,'230'),
 (18,4,'3'),
 (18,5,'63'),
 (18,9,'400'),
 (19,6,'24'),
 (20,3,'2'),
 (20,8,'Nhựa PC');

INSERT INTO HINHANH (MaSP, DuongDan, LaAnhChinh) VALUES
 (1,'uploads/products/282434-C.jpg',1),
 (2,'uploads/products/282434-C1.jpg',1),
 (3,'uploads/products/282434-C2.jpg',1),
 (5,'uploads/products/KA4804KM.jpg',1),
 (6,'uploads/products/KA4804KG.jpg',1),
 (8,'uploads/products/MCB-DEMO-2P-32A.jpg',1),
 (8,'uploads/products/MCB-DEMO-2P-32A-2.jpg',0),
 (10,'uploads/products/TUDIEN-DEMO-18M.jpg',1),
 (11,'uploads/products/AMSAN-DEMO-01.jpg',1),
 (12,'uploads/products/ARTEOR-DEMO-02.jpg',1),
 (13,'uploads/products/GALION-DEMO-CT1.jpg',1),
 (14,'uploads/products/ARTEOR-DEMO-CT2.jpg',1),
 (15,'uploads/products/MALLIA-DEMO-OC.jpg',1),
 (16,'uploads/products/ELOE-DEMO-OC3.jpg',1),
 (17,'uploads/products/MCB-DEMO-1P-10A.jpg',1),
 (19,'uploads/products/TUDIEN-DEMO-24M.jpg',1),
 (20,'uploads/products/LIVING-DEMO-KA2.jpg',1);

INSERT INTO TAILIEU (MaDong, MaSP, TenTL, DuongDan) VALUES
 (1, NULL, 'Catalogue dòng Galion',        'uploads/docs/catalogue-galion.pdf'),
 (2, NULL, 'Catalogue dòng Mallia Senses', 'uploads/docs/catalogue-mallia-senses.pdf'),
 (3, NULL, 'Catalogue dòng Arteor',        'uploads/docs/catalogue-arteor.pdf'),
 (4, NULL, 'Catalogue dòng Living Now',    'uploads/docs/catalogue-living-now.pdf'),
 (5, NULL, 'Catalogue dòng ELOÉ',          'uploads/docs/catalogue-eloe.pdf'),
 (NULL, 8, 'Thông số kỹ thuật MCB 2P 32A', 'uploads/docs/mcb-2p-32a.pdf'),
 (NULL, 10,'Bản vẽ kỹ thuật tủ điện 18 module', 'uploads/docs/tu-dien-18m.pdf'),
 (NULL, 18,'Thông số kỹ thuật MCB 3P 63A', 'uploads/docs/mcb-3p-63a.pdf');

INSERT INTO SANPHAM_LIENQUAN (MaSP, MaSPLienQuan, LoaiGoiY) VALUES
 (1,2,'Cùng dòng sản phẩm'),
 (1,3,'Cùng dòng sản phẩm'),
 (1,4,'Cùng dòng sản phẩm'),
 (1,12,'Cùng phân khúc'),
 (1,15,'Cùng phân khúc'),
 (1,13,'Mua kèm'),
 (2,1,'Cùng dòng sản phẩm'),
 (2,3,'Cùng dòng sản phẩm'),
 (3,1,'Cùng dòng sản phẩm'),
 (3,4,'Cùng dòng sản phẩm'),
 (4,1,'Cùng dòng sản phẩm'),
 (4,2,'Cùng dòng sản phẩm'),
 (5,6,'Cùng dòng sản phẩm'),
 (5,20,'Cùng dòng sản phẩm'),
 (5,1,'Mua kèm'),
 (6,5,'Cùng dòng sản phẩm'),
 (6,20,'Cùng dòng sản phẩm'),
 (6,14,'Cùng phân khúc'),
 (7,8,'Cùng phân khúc'),
 (7,17,'Cùng phân khúc'),
 (7,9,'Mua kèm'),
 (8,7,'Cùng phân khúc'),
 (8,17,'Cùng phân khúc'),
 (8,10,'Mua kèm'),
 (17,7,'Cùng phân khúc'),
 (17,8,'Cùng phân khúc'),
 (18,8,'Cùng phân khúc'),
 (9,10,'Cùng phân khúc'),
 (9,19,'Cùng phân khúc'),
 (10,9,'Cùng phân khúc'),
 (10,19,'Cùng phân khúc'),
 (10,8,'Mua kèm'),
 (19,10,'Cùng phân khúc'),
 (19,9,'Cùng phân khúc'),
 (11,1,'Mua kèm'),
 (12,14,'Cùng dòng sản phẩm'),
 (12,1,'Cùng phân khúc'),
 (13,14,'Cùng phân khúc'),
 (13,1,'Mua kèm'),
 (14,13,'Cùng phân khúc'),
 (14,12,'Cùng dòng sản phẩm'),
 (15,16,'Cùng phân khúc'),
 (15,1,'Cùng phân khúc'),
 (16,15,'Cùng phân khúc'),
 (16,1,'Cùng phân khúc'),
 (20,5,'Cùng dòng sản phẩm'),
 (20,6,'Cùng dòng sản phẩm');

INSERT INTO NHACUNGCAP (TenNCC, SoDienThoai, DiaChi) VALUES
 ('Công ty TNHH Phân phối Thiết bị Điện Mẫu A','0281234567','10 Võ Văn Kiệt, Quận 1, TP. Hồ Chí Minh'),
 ('Công ty CP Thiết bị Điện Mẫu B','0287654321','25 Nguyễn Thị Minh Khai, Quận 3, TP. Hồ Chí Minh');

INSERT INTO PHIEUNHAP (MaNCC, MaND, NgayNhap, TongTien) VALUES
 (1, 3, '2026-10-01 08:30:00', 14000000),
 (2, 3, '2026-10-15 09:00:00', 22200000),
 (1, 3, '2026-10-25 10:00:00', 26700000);

INSERT INTO CT_PHIEUNHAP (MaPN, MaSP, SoLuong, GiaNhap) VALUES
 (1,1,100,30000),(1,2,100,30000),(1,5,50,80000),(1,6,50,80000),
 (2,8,60,120000),(2,10,20,450000),(2,11,10,600000),
 (3,13,80,25000),(3,14,40,45000),(3,15,30,60000),(3,16,50,50000),
 (3,17,100,52000),(3,19,12,900000),(3,20,40,65000);

-- Tồn kho = nhập - bán (đơn chưa hủy) +/- kiểm kê; SP 3,4,7,9,12,18 lấy số tồn ban đầu
INSERT INTO TONKHO (MaSP, SoLuongTon, NguongCanhBao) VALUES
 (1,94,10),(2,97,10),(3,40,10),(4,30,10),(5,48,10),(6,50,10),
 (7,15,10),(8,56,10),(9,8,10),(10,19,5),(11,10,3),(12,25,10),
 (13,80,10),(14,40,10),(15,30,10),(16,50,10),(17,100,10),(18,12,5),(19,12,3),(20,40,10);

INSERT INTO PHIEUKIEMKE (MaND, NgayKK) VALUES (3, '2026-11-01 17:00:00');
INSERT INTO CT_PHIEUKIEMKE (MaKK, MaSP, SoLuongHeThong, SoLuongThucTe, LyDo) VALUES
 (1,1,95,94,'Hỏng 1 cái khi vận chuyển'),
 (1,2,97,97,NULL);

INSERT INTO YEUTHICH (MaND, MaSP, NgayThem) VALUES (4,6,'2026-10-02 20:00:00'),(5,1,'2026-10-03 21:15:00');

INSERT INTO GIOHANG (MaND) VALUES (4),(5);
INSERT INTO CT_GIOHANG (MaGH, MaSP, SoLuong) VALUES (1,8,2),(1,12,1);

INSERT INTO DONHANG (MaND, MaDC, NguoiNhan, SoDienThoaiNhan, DiaChiGiao, NgayDat, NgayHoanTat, TrangThai, PhiGiao, TongTien, HanThanhToan) VALUES
 (4,1,'Nguyễn Văn An','0900000004','12 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh','2026-10-05 09:30:00','2026-10-08 15:00:00','Hoàn tất',30000,495000,NULL),
 (5,3,'Trần Thị Bình','0900000005','88 Trần Hưng Đạo, Quận 5, TP. Hồ Chí Minh','2026-11-03 14:00:00',NULL,'Đang giao',30000,770000,'2026-11-04 14:00:00'),
 (4,1,'Nguyễn Văn An','0900000004','12 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh',NOW() - INTERVAL 1 HOUR,NULL,'Chờ thanh toán',30000,680000,NOW() + INTERVAL 23 HOUR),
 (5,3,'Trần Thị Bình','0900000005','88 Trần Hưng Đạo, Quận 5, TP. Hồ Chí Minh','2026-10-20 10:00:00',NULL,'Đã hủy',30000,150000,NULL),
 (4,2,'Nguyễn Văn An','0900000004','45 Lê Lợi, Quận 1, TP. Hồ Chí Minh','2026-11-09 16:20:00',NULL,'Chờ xác nhận',30000,263000,NULL);

INSERT INTO CT_DONHANG (MaDH, MaSP, SoLuong, DonGia) VALUES
 (1,1,5,45000),(1,5,2,120000),
 (2,8,4,185000),
 (3,10,1,650000),
 (4,6,1,120000),
 (5,2,3,45000),(5,12,1,98000);

INSERT INTO THANHTOAN (MaDH, PhuongThuc, SoTien, TrangThai, ThoiGian) VALUES
 (1,'Thanh toán khi nhận hàng',495000,'Đã thanh toán','2026-10-08 15:00:00'),
 (2,'Chuyển khoản',770000,'Đã thanh toán','2026-11-03 15:10:00'),
 (3,'Chuyển khoản',680000,'Chưa thanh toán',NULL),
 (4,'Thanh toán khi nhận hàng',150000,'Chưa thanh toán',NULL),
 (5,'Thanh toán khi nhận hàng',263000,'Chưa thanh toán',NULL);

INSERT INTO LICHSU_DONHANG (MaDH, MaND, TrangThaiCu, TrangThaiMoi, ThoiGian, GhiChu) VALUES
 (1,4,NULL,'Chờ xác nhận','2026-10-05 09:30:00','Tạo đơn'),
 (1,2,'Chờ xác nhận','Đã xác nhận','2026-10-05 10:00:00',NULL),
 (1,2,'Đã xác nhận','Đang giao','2026-10-06 08:00:00',NULL),
 (1,2,'Đang giao','Hoàn tất','2026-10-08 15:00:00',NULL),
 (2,5,NULL,'Chờ thanh toán','2026-11-03 14:00:00','Tạo đơn'),
 (2,2,'Chờ thanh toán','Chờ xác nhận','2026-11-03 15:10:00','Đã đối chiếu tiền chuyển khoản'),
 (2,2,'Chờ xác nhận','Đã xác nhận','2026-11-03 15:30:00',NULL),
 (2,2,'Đã xác nhận','Đang giao','2026-11-04 09:00:00',NULL),
 (3,4,NULL,'Chờ thanh toán',NOW() - INTERVAL 1 HOUR,'Tạo đơn'),
 (4,5,NULL,'Chờ xác nhận','2026-10-20 10:00:00','Tạo đơn'),
 (4,5,'Chờ xác nhận','Đã hủy','2026-10-20 11:00:00','Khách đổi ý'),
 (5,4,NULL,'Chờ xác nhận','2026-11-09 16:20:00','Tạo đơn');

INSERT INTO BAOGIA (MaND, MaNVXuLy, MaDH, NgayGui, GhiChu, TrangThai, HanHieuLuc, NgayTraLoi, LyDoKhongBG) VALUES
 (4,2,NULL,'2026-11-05 09:00:00','Công trình nhà phố 3 tầng','Đã báo giá',TIMESTAMP(CURDATE() + INTERVAL 14 DAY, '23:59:00'),NOW() - INTERVAL 1 DAY,NULL),
 (5,NULL,NULL,'2026-11-09 11:00:00','Cần gấp cho căn hộ','Mới',NULL,NULL,NULL),
 (5,2,NULL,'2026-10-12 14:00:00',NULL,'Không báo giá được',NULL,'2026-10-13 09:00:00','Mã hàng hiện chưa có nguồn cung');

INSERT INTO CT_BAOGIA (MaBG, MaSP, SoLuong, DonGiaBao) VALUES
 (1,7,10,55000),(1,9,2,480000),
 (2,7,20,NULL),
 (3,9,1,NULL);

INSERT INTO DANHGIA (MaND, MaSP, SoSao, NoiDung, HinhAnh, NgayDG, PhanHoi, NgayPhanHoi) VALUES
 (4,1,5,'Hàng đúng mô tả, giao nhanh',NULL,'2026-10-10 19:00:00','Cảm ơn bạn đã ủng hộ cửa hàng.','2026-10-11 08:30:00'),
 (4,5,4,NULL,NULL,'2026-10-10 19:05:00',NULL,NULL);

INSERT INTO BAOHANH (MaDH, MaSP, MaNVXuLy, LoaiYeuCau, MoTa, TrangThai, NgayTao, PhanHoi, NgayXuLy) VALUES
 (1,5,NULL,'Bảo hành','Mặt công tắc bị nứt góc sau khi lắp','Mới','2026-11-08 10:00:00',NULL,NULL),
 (1,1,2,'Đổi trả','Ổ cắm không vừa phích','Từ chối','2026-11-09 09:00:00','Quá thời hạn đổi trả 3 ngày kể từ ngày hoàn tất','2026-11-09 11:00:00');

INSERT INTO TINTUC (MaND, TieuDe, NoiDung, NgayDang) VALUES
 (1,'Chọn aptomat đúng dòng định mức cho gia đình','Bài viết mẫu về cách chọn aptomat theo công suất sử dụng.','2026-10-01 08:00:00'),
 (1,'Khai trương cửa hàng thiết bị điện trực tuyến','Bài viết mẫu giới thiệu website.','2026-10-02 08:00:00');

-- ===================== 6. TÙY CHỌN: tự hủy đơn quá hạn mỗi 5 phút =====================
-- Đặt ở cuối tệp để nếu tài khoản MySQL không có quyền EVENT (hosting dùng chung) thì chỉ riêng câu này báo lỗi,
-- các bảng, view và dữ liệu mẫu phía trên đã được tạo xong. Cần event_scheduler = ON (mặc định MySQL 8).
-- Bỏ qua cũng được: các trang đơn hàng và thanh toán vẫn tự gọi sp_HuyDonQuaHan khi được mở.
CREATE EVENT IF NOT EXISTS ev_HuyDonQuaHan
  ON SCHEDULE EVERY 5 MINUTE
  DO CALL sp_HuyDonQuaHan();
