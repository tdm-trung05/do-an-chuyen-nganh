<?php
/**
 * Bộ khung trang: mỗi trang gọi page_begin() ở đầu và page_end() ở cuối.
 * Khu quản trị dùng admin_begin() / admin_end() trong admin/includes/layout.php.
 */

require_once INC_PATH . '/components/product_card.php';
require_once INC_PATH . '/components/pagination.php';
require_once INC_PATH . '/components/filter_sidebar.php';
require_once INC_PATH . '/components/account_nav.php';
require_once INC_PATH . '/components/page_title.php';

/**
 * Mở trang khách hàng: <head>, thanh đầu trang, đường dẫn breadcrumb.
 *
 * @param string $title   Tiêu đề trang (thẻ <title>)
 * @param array  $crumbs  Danh sách [tên => đường dẫn|null]
 * @param string $navKey  Khóa menu đang mở (xem includes/header.php)
 */
function page_begin(string $title, array $crumbs = [], string $navKey = ''): void
{
    $pageTitle = $title;
    $bodyClass = 'site';
    require INC_PATH . '/head.php';
    require INC_PATH . '/header.php';
    echo '<main id="main">';
    if ($crumbs) {
        require INC_PATH . '/components/breadcrumb.php';
    }
    $flash = flash_render();
    if ($flash !== '') {
        echo '<div class="container pt-3">' . $flash . '</div>';
    }
}

/** Đóng trang khách hàng: chân trang, hộp xem nhanh, script. */
function page_end(): void
{
    echo '</main>';
    require INC_PATH . '/components/quick_view_modal.php';
    require INC_PATH . '/footer.php';
}
