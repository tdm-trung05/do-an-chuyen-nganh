<?php
/**
 * Xử lý thao tác giỏ hàng và yêu thích (chỉ nhận POST + CSRF).
 *   action=them        id, so_luong      thêm vào giỏ (cộng dồn)
 *   action=cap-nhat    so_luong[id]=n    đặt lại số lượng nhiều dòng (trang giỏ hàng)
 *   action=xoa         id                xóa một dòng (hoặc nút name="xoa" value="id")
 *   action=yeu-thich   id                bật/tắt yêu thích
 * Gọi bằng AJAX (header X-Requested-With: fetch) thì trả JSON; gọi bằng form thường thì chuyển hướng kèm thông báo.
 */
require_once __DIR__ . '/includes/bootstrap.php';
no_cache_headers();

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

/** Kết thúc: JSON cho AJAX, chuyển hướng cho form thường. */
function cart_respond(bool $ajax, int $status, array $payload, string $fallback): void
{
    if ($ajax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!empty($payload['msg'])) {
        flash_set(!empty($payload['ok']) ? 'success' : 'danger', $payload['msg']);
    }
    redirect($fallback);
}

/** Địa chỉ quay lại: chỉ nhận đường dẫn nội bộ của website (dùng lại safe_next trong includes/auth.php). */
function cart_back(string $default): string
{
    return safe_next($default);
}

if (!is_post()) {
    redirect(url('gio-hang.php'));
}
$back = cart_back(url('gio-hang.php'));

if (!csrf_verify()) {
    cart_respond($ajax, 419, ['ok' => false, 'msg' => 'Phiên làm việc đã hết hạn. Hãy tải lại trang rồi thử lại.'], $back);
}

$u = auth_user();
if ($u === null) {
    flash_set('info', 'Vui lòng đăng nhập để tiếp tục.');
    $login = url('dang-nhap.php?quay-lai=' . rawurlencode($back));
    if ($ajax) {
        cart_respond(true, 401, ['ok' => false, 'msg' => 'Vui lòng đăng nhập để tiếp tục.', 'login' => $login], $back);
    }
    redirect($login);
}
if ($u['role'] !== 'KH') {
    cart_respond($ajax, 403, ['ok' => false, 'msg' => 'Tài khoản nhân viên không dùng được giỏ hàng. Hãy đăng nhập bằng tài khoản khách hàng.'], $back);
}
$uid = (int) $u['MaND'];

$action = (string) ($_POST['action'] ?? '');
$id     = (int) ($_POST['id'] ?? 0);
if (isset($_POST['xoa'])) {          // nút xóa trong form giỏ hàng: <button name="xoa" value="mã">
    $action = 'xoa';
    $id     = (int) $_POST['xoa'];
}

$result = ['ok' => false, 'msg' => 'Yêu cầu không hợp lệ.'];
switch ($action) {
    case 'them':
        $result = cart_add($uid, $id, (int) ($_POST['so_luong'] ?? 1));
        break;
    case 'cap-nhat':
        $qtys = $_POST['so_luong'] ?? [];
        $notes = [];
        if (is_array($qtys)) {
            foreach ($qtys as $pid => $q) {
                $r = cart_set($uid, (int) $pid, (int) $q);
                if (!$r['ok']) {
                    $notes[] = $r['msg'];
                }
            }
        }
        $result = $notes ? ['ok' => false, 'msg' => implode(' ', array_unique($notes))] : ['ok' => true, 'msg' => 'Đã cập nhật giỏ hàng.'];
        break;
    case 'xoa':
        $result = cart_remove($uid, $id);
        break;
    case 'yeu-thich':
        $r = wish_toggle($uid, $id);
        $result = ['ok' => $r['ok'], 'msg' => $r['msg'], 'added' => $r['added'], 'wishCount' => count(wish_ids(true))];
        break;
}

$result['count'] = (int) db_val(
    'SELECT COALESCE(SUM(c.SoLuong), 0) FROM CT_GIOHANG c JOIN GIOHANG g ON g.MaGH = c.MaGH WHERE g.MaND = ?',
    [$uid]
);
$fallback = $action === 'yeu-thich' ? $back : ($action === 'them' ? $back : url('gio-hang.php'));
cart_respond($ajax, $result['ok'] ? 200 : 422, $result, $fallback);
