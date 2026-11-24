<?php
/**
 * Phân trang. Dùng: pagination(2, 8) hoặc pagination($page, $pages, 'trang').
 * Giữ nguyên các tham số lọc đang có trên địa chỉ (chỉ đổi tham số trang).
 */
function pagination(int $current = 1, int $total = 5, string $param = 'trang'): void
{
    if ($total <= 1) {
        return;
    }
    $href = static function (int $page) use ($param): string {
        $q = $_GET;
        unset($q[$param]);
        if ($page > 1) {
            $q[$param] = $page;
        }
        $path = strtok(request_uri(), '?');
        return e($path . ($q ? '?' . http_build_query($q) : ''));
    };
    // Hiện tối đa 7 số trang quanh trang hiện tại
    $from = max(1, min($current - 3, $total - 6));
    $to   = min($total, $from + 6);
    ?>
    <nav aria-label="Phân trang">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item<?= $current <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= $href(max(1, $current - 1)) ?>" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a></li>
            <?php for ($i = $from; $i <= $to; $i++): ?>
            <li class="page-item<?= $i === $current ? ' active' : '' ?>"><a class="page-link" href="<?= $href($i) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item<?= $current >= $total ? ' disabled' : '' ?>"><a class="page-link" href="<?= $href(min($total, $current + 1)) ?>" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a></li>
        </ul>
    </nav>
    <?php
}
