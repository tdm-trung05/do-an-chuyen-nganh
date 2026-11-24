<?php
/** Quản lý thương hiệu (Legrand, Bticino, Vimar...). */
require_once __DIR__ . '/../../includes/bootstrap.php';
admin_begin('Thương hiệu', 'thuong-hieu', '', '<button class="btn btn-accent" type="button"><i class="bi bi-plus-lg"></i> Thêm thương hiệu</button>');
$rows = [];
foreach ($mockBrands as $b) { $rows[] = [$b['id'], $b['name'], count(array_filter($GLOBALS['mockSeries'], fn($s) => $s['brand'] === $b['name']))]; }
?>
<section class="card-box"><?php data_table(['ID', 'Tên thương hiệu', 'Số dòng sản phẩm'], $rows, ['right' => [2]]); ?></section>
<?php admin_end(); ?>
