<?php
/** Liên hệ: hotline, Zalo, email, địa chỉ, biểu mẫu. */
require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$old = ['ht' => '', 'dt' => '', 'em' => '', 'nd' => ''];
$u = auth_user();
if ($u) {
    $old['ht'] = (string) ($u['HoTen'] ?? '');
    $old['em'] = (string) ($u['Email'] ?? '');
    $old['dt'] = (string) ($u['SoDienThoai'] ?? '');
}
if (is_post()) {
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if (!csrf_verify()) {
        $errors[] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $r = contact_send($u ? (int) $u['MaND'] : null, $old['ht'], $old['dt'], $old['em'], $old['nd']);
        if ($r['ok']) {
            flash_set('success', 'Đã gửi tin nhắn. Chúng tôi sẽ phản hồi qua email hoặc điện thoại sớm nhất.');
            redirect(url('lien-he.php'));
        }
        $errors = $r['errors'];
    }
}
page_begin('Liên hệ', ['Liên hệ' => null], 'lien-he');
?>
<div class="container">
    <?php page_title('Liên hệ', 'Gọi hotline, nhắn Zalo hoặc gửi biểu mẫu bên dưới.'); ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <section class="panel">
                <h2 class="panel-title">Thông tin liên hệ</h2>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-telephone"></i> Hotline: <strong><?= e(SITE_HOTLINE) ?></strong></li>
                    <?php if (SITE_SUPPORT !== ''): ?><li class="mb-2"><i class="bi bi-wrench-adjustable"></i> Hỗ trợ kỹ thuật: <strong><?= e(SITE_SUPPORT) ?></strong></li><?php endif; ?>
                    <?php if (SITE_ZALO !== ''): ?><li class="mb-2"><i class="bi bi-chat-dots"></i> Zalo: <strong><?= e(SITE_ZALO) ?></strong></li><?php endif; ?>
                    <li class="mb-2"><i class="bi bi-envelope"></i> <?= e(SITE_EMAIL) ?></li>
                    <li><i class="bi bi-geo-alt"></i> <?= e(SITE_ADDRESS) ?></li>
                </ul>
            </section>
            <div class="map-box mt-3"><span><i class="bi bi-map"></i> Bản đồ cửa hàng</span></div>
        </div>
        <div class="col-lg-7">
            <section class="panel">
                <h2 class="panel-title">Gửi tin nhắn</h2>
                <?php if ($errors): ?>
                <div class="alert alert-danger small" role="alert"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form action="<?= url('lien-he.php') ?>" method="post" class="row g-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="col-md-6"><label class="form-label" for="ht">Họ và tên</label><input id="ht" name="ht" class="form-control" type="text" maxlength="100" value="<?= e($old['ht']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="dt">Số điện thoại</label><input id="dt" name="dt" class="form-control" type="tel" maxlength="15" value="<?= e($old['dt']) ?>"></div>
                    <div class="col-12"><label class="form-label" for="em">Email</label><input id="em" name="em" class="form-control" type="email" maxlength="150" value="<?= e($old['em']) ?>" required></div>
                    <div class="col-12"><label class="form-label" for="nd">Nội dung</label><textarea id="nd" name="nd" class="form-control" rows="5" maxlength="1000" required><?= e($old['nd']) ?></textarea></div>
                    <div class="col-12"><button class="btn btn-ink" type="submit">Gửi tin nhắn</button></div>
                </form>
            </section>
        </div>
    </div>
</div>
<?php page_end(); ?>
