/**
 * main.js – khu khách hàng.
 * - Thêm vào giỏ, yêu thích bằng AJAX (không tải lại trang)
 * - Nút tăng/giảm số lượng
 * - Hộp "Xem nhanh" nạp nội dung từ xem-nhanh.php
 * Token CSRF và địa chỉ gốc lấy từ thẻ <meta> trong includes/head.php.
 */
(function () {
  'use strict';

  var metaCsrf = document.querySelector('meta[name="csrf-token"]');
  var metaBase = document.querySelector('meta[name="base-url"]');
  var CSRF = metaCsrf ? metaCsrf.content : '';
  var BASE = metaBase ? metaBase.content : '';

  /* ------------------------------ Thông báo nhỏ ------------------------------ */
  function toast(message, type) {
    var holder = document.getElementById('toastHolder');
    if (!holder) {
      holder = document.createElement('div');
      holder.id = 'toastHolder';
      holder.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      holder.style.zIndex = 1090;
      document.body.appendChild(holder);
    }
    var el = document.createElement('div');
    el.className = 'toast align-items-center border-0 text-bg-' + (type === 'danger' ? 'danger' : 'dark');
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    var inner = document.createElement('div');
    inner.className = 'd-flex';
    var body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = message;          // textContent: không chèn HTML
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close btn-close-white me-2 m-auto';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', 'Đóng');
    inner.appendChild(body);
    inner.appendChild(close);
    el.appendChild(inner);
    holder.appendChild(el);
    if (window.bootstrap && bootstrap.Toast) {
      var t = new bootstrap.Toast(el, { delay: 3500 });
      el.addEventListener('hidden.bs.toast', function () { el.remove(); });
      t.show();
    } else {
      setTimeout(function () { el.remove(); }, 3500);
    }
  }

  /* ------------------------------ Gọi máy chủ ------------------------------- */
  function post(data) {
    var fd = new FormData();
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    fd.append('_csrf', CSRF);
    fd.append('quay-lai', window.location.pathname + window.location.search);   // sau khi đăng nhập quay lại đúng trang
    return fetch(BASE + '/gio-hang-xu-ly.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
    }).then(function (res) {
      return res.json().catch(function () { return { ok: false, msg: 'Máy chủ trả về dữ liệu không hợp lệ.' }; })
        .then(function (json) { json.status = res.status; return json; });
    });
  }

  function setBadge(selector, n) {
    document.querySelectorAll(selector).forEach(function (el) {
      el.textContent = n;
      el.hidden = !(n > 0);
    });
  }

  function handleLoginRedirect(json) {
    if (json.status === 401 && json.login) {
      window.location.href = json.login;
      return true;
    }
    return false;
  }

  /* ------------------------------ Thêm vào giỏ ------------------------------ */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-add-to-cart]');
    if (!btn || btn.disabled) { return; }
    ev.preventDefault();
    var scope = btn.closest('[data-qty-scope]');
    var input = scope ? scope.querySelector('.qty-input input') : null;
    var qty = input ? parseInt(input.value, 10) : 1;
    if (!qty || qty < 1) { qty = 1; }
    btn.disabled = true;
    post({ action: 'them', id: btn.getAttribute('data-product-id'), so_luong: qty })
      .then(function (json) {
        if (handleLoginRedirect(json)) { return; }
        if (typeof json.count === 'number') { setBadge('.cart-count', json.count); }
        toast(json.msg || (json.ok ? 'Đã thêm vào giỏ hàng.' : 'Không thêm được vào giỏ hàng.'), json.ok ? 'ok' : 'danger');
      })
      .catch(function () { toast('Không kết nối được máy chủ. Vui lòng thử lại.', 'danger'); })
      .then(function () { btn.disabled = false; });
  });

  /* ------------------------------- Yêu thích -------------------------------- */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-wish]');
    if (!btn) { return; }
    ev.preventDefault();
    btn.disabled = true;
    post({ action: 'yeu-thich', id: btn.getAttribute('data-product-id') })
      .then(function (json) {
        if (handleLoginRedirect(json)) { return; }
        if (!json.ok) { toast(json.msg || 'Không cập nhật được yêu thích.', 'danger'); return; }
        if (typeof json.wishCount === 'number') { setBadge('.wish-count', json.wishCount); }
        document.querySelectorAll('[data-wish][data-product-id="' + btn.getAttribute('data-product-id') + '"]').forEach(function (b) {
          var icon = b.querySelector('i');
          if (icon) { icon.className = 'bi ' + (json.added ? 'bi-heart-fill' : 'bi-heart'); }
          b.setAttribute('aria-pressed', json.added ? 'true' : 'false');
          b.title = json.added ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';
        });
        if (!json.added && btn.hasAttribute('data-remove-card')) {
          var col = btn.closest('[class*="col-"]');
          if (col) { col.remove(); }
          if (!document.querySelector('[data-wish][data-remove-card]')) {
            var empty = document.getElementById('wishEmpty');
            if (empty) { empty.classList.remove('d-none'); }
          }
        }
        toast(json.msg, 'ok');
      })
      .catch(function () { toast('Không kết nối được máy chủ. Vui lòng thử lại.', 'danger'); })
      .then(function () { btn.disabled = false; });
  });

  /* ------------------------- Nút tăng/giảm số lượng ------------------------- */
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('.qty-input button');
    if (!b) { return; }
    var wrap = b.closest('.qty-input');
    var input = wrap.querySelector('input');
    var step = b.getAttribute('data-step') ? parseInt(b.getAttribute('data-step'), 10) : (b.textContent.trim() === '+' ? 1 : -1);
    var min = parseInt(input.getAttribute('data-min') || '1', 10);
    var max = parseInt(input.getAttribute('data-max') || '999', 10);
    var v = (parseInt(input.value, 10) || min) + step;
    input.value = Math.max(min, Math.min(max, v));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
  document.addEventListener('change', function (ev) {
    var input = ev.target.closest('.qty-input input');
    if (!input) { return; }
    var min = parseInt(input.getAttribute('data-min') || '1', 10);
    var max = parseInt(input.getAttribute('data-max') || '999', 10);
    var v = parseInt(input.value, 10);
    input.value = isNaN(v) ? min : Math.max(min, Math.min(max, v));
  });

  /* ------------------------------- Xem nhanh -------------------------------- */
  var qv = document.getElementById('quickViewModal');
  if (qv) {
    qv.addEventListener('show.bs.modal', function (ev) {
      var trigger = ev.relatedTarget;
      var id = trigger ? trigger.getAttribute('data-product-id') : null;
      var body = document.getElementById('quickViewBody');
      if (!id || !body) { return; }
      body.innerHTML = '<div class="text-center text-muted py-5">Đang tải…</div>';
      fetch(BASE + '/xem-nhanh.php?id=' + encodeURIComponent(id), { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
        .then(function (res) { if (!res.ok) { throw new Error('http'); } return res.text(); })
        .then(function (html) { body.innerHTML = html; })     // HTML do máy chủ dựng, đã mã hóa bằng e()
        .catch(function () { body.innerHTML = '<div class="text-center text-danger py-5">Không tải được sản phẩm.</div>'; });
    });
  }


  /* --------------------- Ảnh nhỏ trên trang chi tiết sản phẩm ---------------- */
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-gallery-thumb]');
    if (!t) { return; }
    var main = document.getElementById('galleryMain');
    if (main) { main.src = t.getAttribute('data-gallery-thumb'); }
    document.querySelectorAll('[data-gallery-thumb]').forEach(function (b) { b.classList.toggle('active', b === t); });
  });

  /* Liên kết "n đánh giá" mở tab đánh giá */
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest('[data-open-tab]');
    if (!a || !window.bootstrap) { return; }
    var btn = document.querySelector('[data-bs-target="' + a.getAttribute('data-open-tab') + '"]');
    if (btn) { ev.preventDefault(); bootstrap.Tab.getOrCreateInstance(btn).show(); btn.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  });

  /* Mở tab theo #hash trên URL (vd: bao-gia.php#da-nhan, san-pham-chi-tiet.php?id=1#danh-gia) */
  function openTabFromHash() {
    var h = window.location.hash;
    if (!h || h.length < 2 || !window.bootstrap || !bootstrap.Tab) { return; }
    var btn = null;
    document.querySelectorAll('[data-bs-target]').forEach(function (b) {
      if (!btn && b.getAttribute('data-bs-target') === h && b.getAttribute('data-bs-toggle') === 'tab') { btn = b; }
    });
    if (btn) {
      bootstrap.Tab.getOrCreateInstance(btn).show();
      btn.scrollIntoView({ block: 'center' });
    }
  }
  if (document.readyState === 'complete') { openTabFromHash(); } else { window.addEventListener('load', openTabFromHash); }
  window.addEventListener('hashchange', openTabFromHash);

  /* --------------------- Xác nhận trước thao tác nguy hiểm ------------------- */
  document.addEventListener('submit', function (ev) {
    var msg = ev.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); }
  });
})();
