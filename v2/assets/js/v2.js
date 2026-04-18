(function (window) {
  function initDataTable(selector) {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
      return;
    }

    if (!document.querySelector(selector)) {
      return;
    }

    if (window.jQuery.fn.dataTable.isDataTable(selector)) {
      return;
    }

    window.jQuery(selector).DataTable({
      order: [[0, 'desc']],
      pageLength: 10,
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Search'
      }
    });
  }

  function confirmDelete(form, message) {
    return window.confirm(message || 'Are you sure?');
  }

  window.V2 = {
    initDataTable: initDataTable,
    confirmDelete: confirmDelete
  };
})(window);
