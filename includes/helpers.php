<?php
/** Các hàm hỗ trợ dùng chung (chưa có nghiệp vụ, chỉ phục vụ hiển thị). */

/** Mã hóa ký tự khi xuất ra trang (chống XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Đường dẫn tuyệt đối trong website: url('san-pham.php') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Đường dẫn tới tài nguyên tĩnh: asset('css/style.css') */
function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/** Định dạng tiền Việt: 125000 => 125.000 ₫ ; null => Liên hệ báo giá */
function money($amount): string
{
    if ($amount === null) {
        return 'Liên hệ báo giá';
    }
    return number_format((float) $amount, 0, ',', '.') . ' ₫';
}

/** Trả về class Bootstrap cho nhãn trạng thái. */
function badge_class(string $status): string
{
    $map = [
        // Đơn hàng (mục 2.3.1.2)
        'Chờ thanh toán' => 'text-bg-warning',
        'Chờ xác nhận'   => 'text-bg-secondary',
        'Đã xác nhận'    => 'text-bg-info',
        'Đang giao'      => 'text-bg-primary',
        'Hoàn tất'       => 'text-bg-success',
        'Đã hủy'         => 'text-bg-danger',
        // Báo giá (BAOGIA.TrangThai) và bảo hành, đổi trả (BAOHANH.TrangThai)
        'Mới'                => 'text-bg-secondary',
        'Đã báo giá'         => 'text-bg-info',
        'Đã chấp nhận'       => 'text-bg-success',
        'Đã từ chối'         => 'text-bg-danger',
        'Hết hạn'            => 'text-bg-dark',
        'Không báo giá được' => 'text-bg-warning',
        'Đã bảo hành'        => 'text-bg-success',
        'Đã đổi sản phẩm'    => 'text-bg-success',
        'Từ chối'            => 'text-bg-danger',
        // Tin nhắn liên hệ
        'Chưa xử lý'     => 'text-bg-warning',
        'Đã xử lý'       => 'text-bg-success',
        // Đánh giá sản phẩm
        'Chưa phản hồi'  => 'text-bg-warning',
        'Đã phản hồi'    => 'text-bg-success',
        // Chung
        'Đang bán'       => 'text-bg-success',
        'Đã ẩn'          => 'text-bg-secondary',
        'Hoạt động'      => 'text-bg-success',
        'Bị khóa'        => 'text-bg-danger',
        'Đã đăng'        => 'text-bg-success',
        'Nháp'           => 'text-bg-secondary',
        'Sắp hết'        => 'text-bg-warning',
        'Còn hàng'       => 'text-bg-success',
        'Hết hàng'       => 'text-bg-danger',
    ];
    return $map[$status] ?? 'text-bg-light';
}

/** Hiển thị nhãn trạng thái. */
function badge(string $status): string
{
    return '<span class="badge ' . badge_class($status) . '">' . e($status) . '</span>';
}

/** Hiển thị số sao (đánh giá), ví dụ stars(4) */
function stars(int $n): string
{
    $out = '<span class="stars" aria-label="' . $n . ' trên 5 sao">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="bi ' . ($i <= $n ? 'bi-star-fill' : 'bi-star') . '"></i>';
    }
    return $out . '</span>';
}

/** Gắn class active cho mục menu đang mở. */
function is_active(string $current, string $key): string
{
    return $current === $key ? ' active' : '';
}
