<?php
/**
 * Bộ khung khu quản trị: admin_begin() ở đầu trang, admin_end() ở cuối.
 * Mỗi trang con (vd admin/san-pham/index.php) chỉ cần require bootstrap rồi gọi hai hàm này.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/data.php';        // truy vấn khu quản trị: đơn hàng, kho, báo biểu

/**
 * @param string $title   Tiêu đề trang
 * @param string $active  Khóa mục menu đang mở (xem admin/includes/sidebar.php)
 * @param string $sub     Mô tả ngắn dưới tiêu đề
 * @param string $action  HTML nút hành động ở góc phải tiêu đề (tùy chọn)
 */
function admin_begin(string $title, string $active = '', string $sub = '', string $action = ''): void
{
    global $adminActive, $adminTitle, $adminSub, $adminAction, $adminUser;
    $adminUser   = admin_guard($active);   // phải chạy trước khi in bất kỳ nội dung nào
    $adminActive = $active;
    $adminTitle  = $title;
    $adminSub    = $sub;
    $adminAction = $action;
    require ROOT_PATH . '/admin/includes/head.php';
    require ROOT_PATH . '/admin/includes/sidebar.php';
    echo '<div class="admin-main">';
    require ROOT_PATH . '/admin/includes/topbar.php';
    echo '<main class="admin-content" id="main">';
    require ROOT_PATH . '/admin/includes/page_header.php';
    echo flash_render();
}

function admin_end(): void
{
    echo '</main></div>';
    require ROOT_PATH . '/admin/includes/footer.php';
}
