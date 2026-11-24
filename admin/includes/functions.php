<?php
/** Hàm hiển thị riêng cho khu quản trị. */

/**
 * Đánh dấu một đoạn HTML đã tin cậy (tự dựng, đã e() mọi dữ liệu người dùng) để data_table() in nguyên.
 * Dùng đối tượng thay cho tiền tố chuỗi để dữ liệu người dùng không thể giả mạo cờ này.
 */
final class RawHtml
{
    public function __construct(private string $html) {}
    public function __toString(): string { return $this->html; }
}

function raw(string $html): RawHtml
{
    return new RawHtml($html);
}

/** Đường dẫn trong khu quản trị: aurl('san-pham/index.php') */
function aurl(string $path = ''): string
{
    return BASE_URL . '/admin/' . ltrim($path, '/');
}

/** Thẻ số liệu ở bảng điều khiển. */
function stat_card(string $icon, string $label, string $value, string $note = ''): void
{
    ?>
    <div class="stat-card">
        <span class="icon"><i class="bi <?= e($icon) ?>"></i></span>
        <div><div class="label"><?= e($label) ?></div><div class="value"><?= e($value) ?></div><?php if ($note): ?><div class="note"><?= e($note) ?></div><?php endif; ?></div>
    </div>
    <?php
}

/**
 * Bảng dữ liệu chung.
 * @param array $headers  ['Tiêu đề', ...]
 * @param array $rows     mỗi dòng là mảng ô; ô là raw('...') được in nguyên (đã tin cậy), ô khác luôn được e()
 * @param array $opts     'right' => [chỉ số cột căn phải]
 */
function data_table(array $headers, array $rows, array $opts = []): void
{
    $right   = $opts['right'] ?? [];
    ?>
    <div class="table-responsive">
        <table class="table align-middle data-table mb-0">
            <thead><tr>
                <?php foreach ($headers as $i => $h): ?><th class="<?= in_array($i, $right, true) ? 'text-end' : '' ?>"><?= e($h) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $i => $cell): ?>
                    <td class="<?= in_array($i, $right, true) ? 'text-end' : '' ?>"><?= $cell instanceof RawHtml ? (string) $cell : e($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** Nút hành động tiêu đề: admin_button('Thêm sản phẩm', 'san-pham/form.php') */
function admin_button(string $label, string $href, string $icon = 'bi-plus-lg'): string
{
    return '<a class="btn btn-accent" href="' . e(aurl($href)) . '"><i class="bi ' . e($icon) . '"></i> ' . e($label) . '</a>';
}

/**
 * Menu khu quản trị, nhóm theo vai trò (mục 2.3.2): QT = Quản trị viên, BH = Nhân viên bán hàng, TK = Thủ kho.
 * Cột thứ 4 của mỗi mục là các vai trò được vào trang đó; admin_guard() và sidebar.php dùng chung bảng này.
 */
function admin_menu(): array
{
    return [
        'Tổng quan' => [
            'dashboard' => ['Bảng điều khiển', 'bi-speedometer2', 'index.php', 'QT BH TK'],
        ],
        'Bán hàng' => [
            'don-hang' => ['Đơn hàng',            'bi-box-seam',         'don-hang/index.php', 'QT BH'],
            'bao-gia'  => ['Yêu cầu báo giá',     'bi-file-earmark-text','bao-gia/index.php',  'QT BH'],
            'bao-hanh' => ['Bảo hành, đổi trả',   'bi-patch-check',      'bao-hanh/index.php', 'QT BH'],
            'danh-gia' => ['Đánh giá sản phẩm',   'bi-star',             'danh-gia/index.php', 'QT BH'],
            'lien-he'  => ['Tin nhắn liên hệ',    'bi-envelope',         'lien-he/index.php',  'QT BH'],
        ],
        'Kho' => [
            'ton-kho'      => ['Tồn kho',        'bi-boxes',           'kho/ton-kho.php',      'QT TK'],
            'phieu-nhap'   => ['Phiếu nhập kho', 'bi-box-arrow-in-down','kho/phieu-nhap.php',  'QT TK'],
            'kiem-ke'      => ['Kiểm kê',        'bi-clipboard-check', 'kho/kiem-ke.php',      'QT TK'],
            'nha-cung-cap' => ['Nhà cung cấp',   'bi-truck',           'kho/nha-cung-cap.php', 'QT'],
        ],
        'Hàng hóa' => [
            'san-pham'    => ['Sản phẩm',          'bi-lightning-charge','san-pham/index.php',     'QT'],
            'danh-muc'    => ['Danh mục',          'bi-diagram-3',       'danh-muc/index.php',     'QT'],
            'dong-sp'     => ['Dòng sản phẩm',     'bi-collection',      'dong-san-pham/index.php','QT'],
            'thong-so'    => ['Thông số',          'bi-sliders',         'thong-so/index.php',     'QT'],
            'tai-lieu'    => ['Tài liệu kỹ thuật', 'bi-file-earmark-pdf','tai-lieu/index.php',     'QT'],
        ],
        'Nội dung và hệ thống' => [
            'tin-tuc'   => ['Tin tức',               'bi-newspaper', 'tin-tuc/index.php',   'QT'],
            'tai-khoan' => ['Tài khoản, phân quyền', 'bi-people',    'tai-khoan/index.php', 'QT'],
            'bao-cao'   => ['Báo cáo',               'bi-graph-up',  'bao-cao/index.php',   'QT BH TK'],
        ],
    ];
}

/** Các vai trò được vào trang có khóa menu $key (khóa lạ: mọi nhân viên). */
function admin_roles_for(string $key): array
{
    foreach (admin_menu() as $items) {
        if (isset($items[$key])) {
            return explode(' ', $items[$key][3]);
        }
    }
    return AUTH_STAFF_ROLES;
}

/**
 * Chặn truy cập khu quản trị ở máy chủ. Gọi trước khi in bất kỳ nội dung nào
 * (admin_begin() đã tự gọi; trang có xử lý POST nên gọi sớm hơn bằng admin_guard('khóa-menu')).
 * - Chưa đăng nhập, hoặc là khách hàng: chuyển tới admin/dang-nhap.php.
 * - Là nhân viên nhưng sai vai trò: trả 403.
 */
function admin_guard(string $key = 'dashboard'): array
{
    no_cache_headers();
    $u = auth_user();
    if (!auth_is_staff($u)) {
        flash_set($u === null ? 'info' : 'warning',
            $u === null ? 'Vui lòng đăng nhập để vào khu quản trị.' : 'Tài khoản này không có quyền vào khu quản trị.');
        redirect(aurl('dang-nhap.php?quay-lai=' . rawurlencode(request_uri())));
    }
    if (!in_array($u['role'], admin_roles_for($key), true)) {
        admin_forbidden($u);
    }
    return $u;
}

/** Trang 403 cho nhân viên không đủ quyền với mục này. */
function admin_forbidden(array $u): void
{
    http_response_code(403);
    $adminTitle = 'Không có quyền truy cập';
    require ROOT_PATH . '/admin/includes/head.php';
    ?>
    <div class="login-admin">
        <div class="panel text-center">
            <h1 class="h5">Không có quyền truy cập</h1>
            <p class="text-muted">Vai trò <strong><?= e($u['TenVT']) ?></strong> không được dùng mục này.</p>
            <a class="btn btn-ink" href="<?= aurl('index.php') ?>">Về bảng điều khiển</a>
        </div>
    </div>
    <?php
    require ROOT_PATH . '/admin/includes/footer.php';
    exit;
}

/** Địa chỉ trang hiện tại, đổi/xóa một số tham số: request_uri_with(['xem' => 5]) hoặc ['xem' => null] để bỏ. */
function request_uri_with(array $change): string
{
    $q = $_GET;
    foreach ($change as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return strtok(request_uri(), '?') . ($q ? '?' . http_build_query($q) : '');
}
