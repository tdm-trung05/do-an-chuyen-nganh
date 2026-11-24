<?php
/**
 * Xác thực và phân quyền (giai đoạn 1): phiên làm việc, đăng nhập, khóa tài khoản,
 * kiểm tra vai trò ở máy chủ, chống CSRF, thông báo flash.
 *
 * Quy ước sử dụng:
 *   - Trang chỉ dành cho khách hàng đã đăng nhập:  require_customer();   (ngay sau bootstrap.php)
 *   - Trang quản trị: admin_begin() tự gọi admin_guard() theo khóa menu (xem admin/includes/functions.php)
 *   - Mọi <form method="post">: chèn <?= csrf_field() ?> và gọi csrf_verify() trước khi xử lý.
 *   - Người dùng hiện tại: $u = auth_user();  // null nếu chưa đăng nhập, ngược lại có 'MaND','HoTen','Email','role'...
 *
 * Phiên chỉ lưu mã người dùng (uid). Họ tên, vai trò, trạng thái luôn đọc lại từ CSDL,
 * nên khóa tài khoản hoặc đổi vai trò có hiệu lực ngay ở lần tải trang kế tiếp.
 */

/** Tên vai trò trong bảng VAITRO => mã ngắn dùng trong code. */
const AUTH_ROLE_CODES = [
    'Khách hàng'         => 'KH',
    'Nhân viên bán hàng' => 'BH',
    'Thủ kho'            => 'TK',
    'Quản trị viên'      => 'QT',
];
/** Các vai trò được vào khu quản trị. */
const AUTH_STAFF_ROLES = ['QT', 'BH', 'TK'];
/** Nhân viên không thao tác quá thời gian này (giây) thì phải đăng nhập lại. */
const AUTH_STAFF_IDLE_SECONDS = 7200;
/** Số ngày giữ đăng nhập khi khách chọn "Ghi nhớ". */
const AUTH_REMEMBER_DAYS = 30;
/** Băm giả để so sánh khi email không tồn tại (tránh lộ email nào đã đăng ký qua thời gian phản hồi). */
const AUTH_DUMMY_HASH = '$2b$10$XZFJ0vzMiWvKMD2dS./l0eT4IotVIVfry22tVP4Xhn.KjD3IOlTRi';

/* ============================== Phiên làm việc ============================== */

/** Tham số cookie phiên; $lifetime = 0 nghĩa là hết khi đóng trình duyệt. */
function auth_cookie_params(int $lifetime = 0): array
{
    return [
        'lifetime' => $lifetime,
        'path'     => BASE_URL . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

/** Mở phiên với cấu hình an toàn. Được gọi một lần trong bootstrap.php. */
function auth_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) (AUTH_REMEMBER_DAYS * 86400));
    session_name('DIENNHA_SID');
    session_set_cookie_params(auth_cookie_params(0));
    session_start();
}

/** Địa chỉ trang hiện tại (gồm query), dùng làm đích quay lại sau khi đăng nhập. */
function request_uri(): string
{
    return (string) ($_SERVER['REQUEST_URI'] ?? url(''));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Chuyển hướng rồi dừng script. */
function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

/**
 * Đích quay lại (tham số quay-lai) nếu hợp lệ, ngược lại trả về chuỗi rỗng.
 * Chỉ chấp nhận đường dẫn nội bộ bắt đầu bằng $prefix (mặc định BASE_URL + "/") để tránh open redirect.
 */
function safe_next_raw(string $prefix = ''): string
{
    $n = $_POST['quay-lai'] ?? $_GET['quay-lai'] ?? '';
    if (!is_string($n) || $n === '' || strlen($n) > 500) {
        return '';
    }
    if ($prefix === '') {
        $prefix = BASE_URL . '/';
    }
    if ($n[0] !== '/' || strncmp($n, '//', 2) === 0 || preg_match('/[\\\\\x00-\x1f\x7f]/', $n)) {
        return '';
    }
    return strncmp($n, $prefix, strlen($prefix)) === 0 ? $n : '';
}

function safe_next(string $default, string $prefix = ''): string
{
    $n = safe_next_raw($prefix);
    return $n !== '' ? $n : $default;
}

/* ================================== CSRF ==================================== */

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Trường ẩn đặt trong mọi form POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Kiểm tra token gửi lên khớp với token trong phiên. */
function csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && $sent !== ''
        && isset($_SESSION['csrf']) && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $sent);
}

/* ============================ Thông báo (flash) ============================= */

/** Lưu thông báo hiện ở lần tải trang kế tiếp. $type: success | info | warning | danger */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

/** HTML các thông báo đang chờ (và xóa chúng khỏi phiên). */
function flash_render(): string
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    if (!$items) {
        return '';
    }
    $allowed = ['success', 'info', 'warning', 'danger'];
    $html = '';
    foreach ($items as $f) {
        $type = in_array($f['type'], $allowed, true) ? $f['type'] : 'info';
        $html .= '<div class="alert alert-' . $type . ' small" role="alert">' . e($f['msg']) . '</div>';
    }
    return $html;
}

/* ============================ Người dùng hiện tại =========================== */

/**
 * Người dùng đang đăng nhập (đọc từ CSDL, lưu đệm trong một request) hoặc null.
 * Tự đăng xuất nếu tài khoản bị khóa/vô hiệu hóa hoặc nhân viên không thao tác quá lâu.
 */
/** Dấu vân tay của mật khẩu hiện hành; đổi mật khẩu thì dấu này đổi, các phiên cũ không còn khớp. */
function auth_pw_fingerprint(string $hash): string
{
    return substr(hash('sha256', $hash), 0, 32);
}

function auth_user(bool $refresh = false): ?array
{
    static $loaded = false, $user = null;
    if ($loaded && !$refresh) {
        return $user;
    }
    $loaded = true;
    $user   = null;

    $id = (int) ($_SESSION['uid'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $row = db_one(
        'SELECT n.MaND, n.HoTen, n.Email, n.SoDienThoai, n.TrangThai, n.MatKhauBam, v.TenVT
           FROM NGUOIDUNG n JOIN VAITRO v ON v.MaVT = n.MaVT
          WHERE n.MaND = ?',
        [$id]
    );
    $role = $row ? (AUTH_ROLE_CODES[$row['TenVT']] ?? '') : '';
    if (!$row || (int) $row['TrangThai'] !== 1 || $role === '') {
        unset($_SESSION['uid'], $_SESSION['last'], $_SESSION['pwf']);
        return null;
    }
    $fp = auth_pw_fingerprint((string) $row['MatKhauBam']);
    unset($row['MatKhauBam']);
    if (!isset($_SESSION['pwf'])) {
        $_SESSION['pwf'] = $fp;   // phiên cấp trước khi có cơ chế này: gắn với mật khẩu hiện tại
    } elseif (!hash_equals((string) $_SESSION['pwf'], $fp)) {
        unset($_SESSION['uid'], $_SESSION['last'], $_SESSION['pwf']);   // mật khẩu đã đổi ở nơi khác
        return null;
    }
    if (in_array($role, AUTH_STAFF_ROLES, true)
        && time() - (int) ($_SESSION['last'] ?? 0) > AUTH_STAFF_IDLE_SECONDS) {
        unset($_SESSION['uid'], $_SESSION['last']);
        flash_set('info', 'Phiên làm việc đã hết hạn do không thao tác. Vui lòng đăng nhập lại.');
        return null;
    }
    $_SESSION['last'] = time();
    $row['role'] = $role;
    $user = $row;
    return $user;
}

function auth_is_staff(?array $u): bool
{
    return $u !== null && in_array($u['role'], AUTH_STAFF_ROLES, true);
}

/* ================================ Đăng nhập ================================= */

/**
 * Kiểm tra email + mật khẩu, có đếm sai liên tiếp và khóa tạm (LOGIN_MAX_ATTEMPTS, LOGIN_LOCK_MINUTES).
 * $area: 'khach' (trang bán hàng, chỉ khách hàng) hoặc 'quantri' (khu quản trị, chỉ nhân viên).
 * Chưa tạo phiên; nếu ok thì gọi auth_login().
 *
 * @return array{ok:bool,error:string,user:?array}
 */
function auth_attempt(string $email, string $password, string $area): array
{
    $generic = 'Email hoặc mật khẩu không đúng.';
    $fail    = static function (string $msg): array {
        return ['ok' => false, 'error' => $msg, 'user' => null];
    };

    $email = strtolower(trim($email));
    if ($email === '' || $password === '' || strlen($email) > 100 || strlen($password) > 1000) {
        return $fail($generic);
    }

    $u = db_one(
        'SELECT n.MaND, n.HoTen, n.Email, n.MatKhauBam, n.TrangThai, n.SoLanSaiLienTiep, n.KhoaDen, v.TenVT,
                CASE WHEN n.KhoaDen IS NOT NULL AND n.KhoaDen > NOW()
                     THEN CEIL(TIMESTAMPDIFF(SECOND, NOW(), n.KhoaDen) / 60) ELSE 0 END AS PhutConLai
           FROM NGUOIDUNG n JOIN VAITRO v ON v.MaVT = n.MaVT
          WHERE n.Email = ?',
        [$email]
    );

    if ($u === null) {
        password_verify($password, AUTH_DUMMY_HASH); // giữ thời gian phản hồi tương đương
        return $fail($generic);
    }

    $id = (int) $u['MaND'];

    // Đang bị khóa tạm: không kiểm tra mật khẩu.
    if ((int) $u['PhutConLai'] > 0) {
        return $fail('Tài khoản đang bị khóa tạm do nhập sai nhiều lần. Vui lòng thử lại sau '
            . (int) $u['PhutConLai'] . ' phút.');
    }
    // Hết thời gian khóa: xóa dấu vết để được thử lại từ đầu.
    if ($u['KhoaDen'] !== null) {
        db_exec('UPDATE NGUOIDUNG SET SoLanSaiLienTiep = 0, KhoaDen = NULL WHERE MaND = ?', [$id]);
        $u['SoLanSaiLienTiep'] = 0;
    }

    if (!password_verify($password, (string) $u['MatKhauBam'])) {
        db_exec('UPDATE NGUOIDUNG SET SoLanSaiLienTiep = SoLanSaiLienTiep + 1 WHERE MaND = ?', [$id]);
        $n = (int) db_val('SELECT SoLanSaiLienTiep FROM NGUOIDUNG WHERE MaND = ?', [$id]);
        if ($n >= LOGIN_MAX_ATTEMPTS) {
            db_exec(
                'UPDATE NGUOIDUNG SET SoLanSaiLienTiep = 0, KhoaDen = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE MaND = ?',
                [(int) LOGIN_LOCK_MINUTES, $id]
            );
            return $fail('Bạn đã nhập sai ' . (int) LOGIN_MAX_ATTEMPTS . ' lần liên tiếp. Tài khoản bị khóa tạm '
                . (int) LOGIN_LOCK_MINUTES . ' phút.');
        }
        return $fail($generic);
    }

    // Mật khẩu đúng.
    if ((int) $u['TrangThai'] !== 1) {
        return $fail('Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ cửa hàng để được hỗ trợ.');
    }
    $role = AUTH_ROLE_CODES[$u['TenVT']] ?? '';
    if ($role === '') {
        return $fail($generic);
    }
    if ($area === 'khach' && $role !== 'KH') {
        return $fail('Đây là tài khoản nhân viên. Vui lòng đăng nhập tại khu quản trị.');
    }
    if ($area === 'quantri' && $role === 'KH') {
        return $fail('Tài khoản này không có quyền vào khu quản trị.');
    }

    if ((int) $u['SoLanSaiLienTiep'] !== 0) {
        db_exec('UPDATE NGUOIDUNG SET SoLanSaiLienTiep = 0, KhoaDen = NULL WHERE MaND = ?', [$id]);
    }
    if (password_needs_rehash((string) $u['MatKhauBam'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE NGUOIDUNG SET MatKhauBam = ? WHERE MaND = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    return ['ok' => true, 'error' => '', 'user' => ['MaND' => $id, 'HoTen' => $u['HoTen'], 'role' => $role]];
}

/** Tạo phiên đăng nhập (đổi mã phiên để chống session fixation). "Ghi nhớ" chỉ áp dụng cho khách hàng. */
function auth_login(int $userId, string $role, bool $remember = false): void
{
    if ($remember && $role === 'KH') {
        session_write_close();
        session_set_cookie_params(auth_cookie_params(AUTH_REMEMBER_DAYS * 86400));
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['uid']  = $userId;
    $_SESSION['last'] = time();
    unset($_SESSION['pwf']);   // auth_user() gắn lại với mật khẩu hiện tại
    auth_user(true);
}

/** Xóa phiên hiện tại và cấp mã phiên mới (để còn hiện được thông báo sau khi đăng xuất). */
function auth_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    auth_user(true);
}

/* ============================= Bảo vệ trang ================================= */

/** Gửi tiêu đề chống lưu đệm để nút Back sau khi đăng xuất không hiện lại trang riêng tư. */
function no_cache_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

/**
 * Trang dành cho khách hàng đã đăng nhập (giỏ hàng thanh toán, đơn hàng, tài khoản...).
 * Chưa đăng nhập: chuyển tới trang đăng nhập rồi quay lại đúng trang này.
 * Đang đăng nhập bằng tài khoản nhân viên: chuyển về trang chủ kèm thông báo.
 */
function require_customer(): array
{
    no_cache_headers();
    $u = auth_user();
    if ($u === null) {
        flash_set('info', 'Vui lòng đăng nhập để tiếp tục.');
        redirect(url('dang-nhap.php?quay-lai=' . rawurlencode(request_uri())));
    }
    if ($u['role'] !== 'KH') {
        flash_set('warning', 'Bạn đang đăng nhập bằng tài khoản nhân viên. Hãy dùng tài khoản khách hàng để mua hàng.');
        redirect(url('index.php'));
    }
    return $u;
}
