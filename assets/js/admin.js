/**
 * admin.js – khu quản trị.
 * Hiện chỉ mở/đóng menu trên điện thoại. Các chức năng khác sẽ bổ sung khi nối cơ sở dữ liệu.
 */
(function () {
  'use strict';
  var toggle = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('adminSidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () { sidebar.classList.toggle('open'); });
  }
  // TODO: xác nhận trước khi ẩn/xóa, sắp xếp cột, lọc bảng bằng AJAX
})();
