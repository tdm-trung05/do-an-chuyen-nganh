<?php /** Hộp "Xem nhanh" sản phẩm (modal Bootstrap). Nội dung nạp bằng AJAX từ xem-nhanh.php khi mở (assets/js/main.js). */ ?>
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title visually-hidden" id="quickViewTitle">Xem nhanh sản phẩm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="quickViewBody">
                <div class="text-center text-muted py-5">Đang tải…</div>
            </div>
        </div>
    </div>
</div>
