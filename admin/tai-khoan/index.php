<?php
/** MH16 – Tài khoản và phân quyền (4 vai trò: khách hàng, nhân viên bán hàng, thủ kho, quản trị viên). */
require_once __DIR__ . '/../../includes/bootstrap.php';
$me = admin_guard('tai-khoan');

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $id = (int) (admin_int($_POST['id'] ?? '') ?? 0);
        switch ($_POST['hanh-dong'] ?? 'khoa') {
            case 'tao':
                $res = admin_user_create((string) ($_POST['ho-ten'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['sdt'] ?? ''), (string) ($_POST['vai-tro'] ?? ''), (string) ($_POST['mat-khau'] ?? ''));
                break;
            case 'doi-vai-tro':
                $res = admin_user_set_role($id, (string) ($_POST['vai-tro'] ?? ''), (int) $me['MaND']);
                break;
            case 'dat-lai-mk':
                $res = admin_user_reset_password($id, (string) ($_POST['mat-khau'] ?? ''));
                break;
            default:
                $res = admin_user_toggle($id, (int) $me['MaND']);
        }
        flash_set($res['ok'] ? 'success' : 'danger', $res['msg']);
    }
    redirect(request_uri());
}

$q     = trim((string) ($_GET['q'] ?? ''));
$roles = admin_role_names();
$role  = (string) ($_GET['tt'] ?? '');
$role  = in_array($role, $roles, true) ? $role : '';
$list  = admin_users($q, $role, max(1, (int) ($_GET['trang'] ?? 1)));

admin_begin('Tài khoản, phân quyền', 'tai-khoan', 'Tạo tài khoản, đổi vai trò, đặt lại mật khẩu, khóa hoặc mở khóa.');
$rows = [];
foreach ($list['rows'] as $u) {
    $active = (int) $u['TrangThai'] === 1;
    $mid = (int) $u['MaND'];
    if ($mid === (int) $me['MaND']) {
        $action = '<span class="text-muted small">Tài khoản của bạn</span>';
    } else {
        $opts = '';
        foreach ($roles as $r) {
            $opts .= '<option' . ($r === $u['TenVT'] ? ' selected' : '') . '>' . e($r) . '</option>';
        }
        $action = '<form method="post" class="d-inline-flex gap-1 mb-1">' . csrf_field() . '<input type="hidden" name="hanh-dong" value="doi-vai-tro"><input type="hidden" name="id" value="' . $mid . '">'
            . '<select name="vai-tro" class="form-select form-select-sm w-auto" aria-label="Vai trò">' . $opts . '</select>'
            . '<button class="btn btn-sm btn-outline-ink" type="submit">Đổi vai trò</button></form><br>'
            . '<form method="post" class="d-inline-flex gap-1 mb-1" onsubmit="return confirm(\'Đặt lại mật khẩu cho tài khoản này?\');">' . csrf_field() . '<input type="hidden" name="hanh-dong" value="dat-lai-mk"><input type="hidden" name="id" value="' . $mid . '">'
            . '<input type="password" name="mat-khau" class="form-control form-control-sm w-auto" minlength="8" maxlength="72" placeholder="Mật khẩu mới" autocomplete="new-password" required aria-label="Mật khẩu mới">'
            . '<button class="btn btn-sm btn-outline-ink" type="submit">Đặt lại MK</button></form><br>'
            . '<form method="post" class="d-inline" onsubmit="return confirm(\'' . ($active ? 'Khóa' : 'Mở khóa') . ' tài khoản này?\');">' . csrf_field()
            . '<input type="hidden" name="id" value="' . $mid . '"><button class="btn btn-sm btn-outline-ink" type="submit">' . ($active ? 'Khóa' : 'Mở khóa') . '</button></form>';
    }
    $rows[] = [$u['HoTen'], $u['Email'], $u['TenVT'], raw(badge($active ? 'Hoạt động' : 'Bị khóa')), raw($action)];
}
$perms = [
    ['Xem sản phẩm, tìm kiếm', 1, 1, 1, 1], ['Đặt hàng, yêu thích, báo giá, bảo hành', 1, 0, 0, 0],
    ['Xử lý đơn, báo giá, bảo hành', 0, 1, 0, 1], ['Nhập kho, kiểm kê', 0, 0, 1, 1],
    ['Quản lý hàng hóa, tin tức', 0, 0, 0, 1], ['Tài khoản, phân quyền', 0, 0, 0, 1],
    ['Báo cáo BB01–02 (doanh thu, bán chạy)', 0, 0, 0, 1], ['Báo cáo BB03–04 (tồn kho, phiếu nhập)', 0, 0, 1, 1],
    ['Báo cáo BB05–07 (đơn hàng, bảo hành, báo giá)', 0, 1, 0, 1],
];
?>
<section class="card-box mb-3">
    <div class="head"><h2>Tạo tài khoản mới</h2></div>
    <form method="post" class="body row g-2 align-items-end">
        <?= csrf_field() ?><input type="hidden" name="hanh-dong" value="tao">
        <div class="col-md-3"><label class="form-label small mb-1" for="tk-ten">Họ tên</label><input id="tk-ten" name="ho-ten" class="form-control form-control-sm" maxlength="100" required></div>
        <div class="col-md-3"><label class="form-label small mb-1" for="tk-email">Email</label><input id="tk-email" name="email" type="email" class="form-control form-control-sm" maxlength="100" required></div>
        <div class="col-md-2"><label class="form-label small mb-1" for="tk-sdt">Số điện thoại</label><input id="tk-sdt" name="sdt" type="tel" class="form-control form-control-sm" maxlength="15" required></div>
        <div class="col-md-2"><label class="form-label small mb-1" for="tk-vt">Vai trò</label><select id="tk-vt" name="vai-tro" class="form-select form-select-sm"><?php foreach ($roles as $r): ?><option><?= e($r) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label small mb-1" for="tk-mk">Mật khẩu (từ 8 ký tự)</label><input id="tk-mk" name="mat-khau" type="password" class="form-control form-control-sm" minlength="8" maxlength="72" autocomplete="new-password" required></div>
        <div class="col-12"><button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-person-plus"></i> Tạo tài khoản</button></div>
    </form>
</section>
<section class="card-box mb-3">
    <?php admin_filter_form('Tìm theo tên hoặc email', $q, $roles, $role); ?>
    <?php if (!$rows): ?>
    <div class="body text-center text-muted py-4">Không có tài khoản nào khớp bộ lọc.</div>
    <?php else: ?>
    <?php data_table(['Họ tên', 'Email', 'Vai trò', 'Trạng thái', 'Thao tác'], $rows); ?>
    <?php endif; ?>
    <div class="card-foot d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= (int) $list['total'] ?> tài khoản</small>
        <?php pagination($list['page'], $list['pages']); ?>
    </div>
</section>
<section class="card-box">
    <div class="head"><h2>Bảng quyền theo vai trò</h2></div>
    <div class="table-responsive"><table class="table align-middle mb-0 data-table perm-table">
        <thead><tr><th>Chức năng</th><th>Khách hàng</th><th>Nhân viên bán hàng</th><th>Thủ kho</th><th>Quản trị viên</th></tr></thead>
        <tbody><?php foreach ($perms as $p): ?><tr><td><?= e($p[0]) ?></td><?php for ($i = 1; $i <= 4; $i++): ?><td><i class="bi <?= $p[$i] ? 'bi-check-circle-fill text-success' : 'bi-dash text-muted' ?>"></i></td><?php endfor; ?></tr><?php endforeach; ?></tbody>
    </table></div>
</section>
<?php admin_end(); ?>
