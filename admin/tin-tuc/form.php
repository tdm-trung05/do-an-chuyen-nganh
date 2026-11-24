<?php
/** MH19 – Soạn và sửa bài viết (bảng TINTUC: tiêu đề, nội dung, trạng thái). */
require_once __DIR__ . '/../../includes/bootstrap.php';
$me = admin_guard('tin-tuc');

$id   = (int) (admin_int($_GET['ma'] ?? $_POST['id'] ?? '') ?? 0);
$post = $id > 0 ? admin_news_one($id) : null;
if ($id > 0 && $post === null) {
    flash_set('danger', 'Không tìm thấy bài viết.');
    redirect(aurl('tin-tuc/index.php'));
}
$old = ['TieuDe' => $post['TieuDe'] ?? '', 'NoiDung' => $post['NoiDung'] ?? '', 'TrangThai' => (int) ($post['TrangThai'] ?? 0)];

if (is_post()) {
    $old = ['TieuDe' => trim((string) ($_POST['tieu-de'] ?? '')), 'NoiDung' => trim((string) ($_POST['noi-dung'] ?? '')), 'TrangThai' => ($_POST['trang-thai'] ?? '') === '1' ? 1 : 0];
    if (!csrf_verify()) {
        flash_set('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } else {
        $res = admin_news_save($id, (int) $me['MaND'], $old['TieuDe'], $old['NoiDung'], $old['TrangThai']);
        if ($res['ok']) {
            flash_set('success', $res['msg']);
            redirect(aurl('tin-tuc/index.php'));
        }
        flash_set('danger', $res['msg']);
    }
}
admin_begin($id > 0 ? 'Sửa bài viết' : 'Viết bài mới', 'tin-tuc', 'Nội dung là văn bản thuần; cách một dòng trống để tách đoạn.');
?>
<form action="<?= e(aurl('tin-tuc/form.php' . ($id > 0 ? '?ma=' . $id : ''))) ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <div class="row g-3">
        <div class="col-lg-8"><section class="card-box"><div class="body">
            <div class="mb-3"><label class="form-label" for="td">Tiêu đề</label><input id="td" name="tieu-de" class="form-control form-control-lg" type="text" maxlength="200" value="<?= e($old['TieuDe']) ?>" required></div>
            <div><label class="form-label" for="nd">Nội dung</label><textarea id="nd" name="noi-dung" class="form-control" rows="16" required><?= e($old['NoiDung']) ?></textarea></div>
        </div></section></div>
        <div class="col-lg-4">
            <section class="card-box mb-3"><div class="head"><h2>Đăng bài</h2></div><div class="body">
                <label class="form-label" for="st">Trạng thái</label>
                <select id="st" name="trang-thai" class="form-select mb-3"><option value="0"<?= $old['TrangThai'] === 0 ? ' selected' : '' ?>>Nháp</option><option value="1"<?= $old['TrangThai'] === 1 ? ' selected' : '' ?>>Đã đăng</option></select>
                <div class="d-grid gap-2"><button class="btn btn-ink" type="submit">Lưu bài viết</button><a class="btn btn-outline-ink" href="<?= e(aurl('tin-tuc/index.php')) ?>">Hủy</a></div>
            </div></section>
        </div>
    </div>
</form>
<?php admin_end(); ?>
