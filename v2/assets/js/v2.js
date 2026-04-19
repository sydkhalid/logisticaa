(function (window, document) {
  var loaderElement = null;
  var activeAjaxRequests = 0;

  function getLoaderElement() {
    if (!loaderElement) {
      loaderElement = document.getElementById('v2-page-loader');
    }

    return loaderElement;
  }

  function setLoaderCopy(title, subtitle) {
    var element = getLoaderElement();

    if (!element) {
      return;
    }

    var titleNode = element.querySelector('.v2-page-loader__title-text') || element.querySelector('strong');
    var subtitleNode = element.querySelector('.v2-page-loader__subtitle') || element.querySelector('small');

    if (titleNode) {
      titleNode.textContent = title || 'Loading data';
    }

    if (subtitleNode) {
      subtitleNode.textContent = subtitle || 'Preparing the next view.';
    }
  }

  function showLoader(title, subtitle) {
    var element = getLoaderElement();

    if (!element) {
      return;
    }

    setLoaderCopy(title, subtitle);
    element.removeAttribute('hidden');
    element.classList.add('is-visible');
    document.body.classList.add('v2-loading');
  }

  function hideLoader() {
    var element = getLoaderElement();

    if (!element) {
      return;
    }

    element.classList.remove('is-visible');
    element.setAttribute('hidden', 'hidden');
    document.body.classList.remove('v2-loading');
  }

  function swalIcon(type) {
    if (type === 'danger') {
      return 'error';
    }

    if (type === 'warning') {
      return 'warning';
    }

    if (type === 'success') {
      return 'success';
    }

    return 'info';
  }

  function fireAlert(options) {
    if (window.Swal) {
      return window.Swal.fire(options);
    }

    if (options.showCancelButton) {
      return Promise.resolve({
        isConfirmed: window.confirm(options.text || options.title || 'Are you sure?')
      });
    }

    window.alert(options.text || options.title || '');

    return Promise.resolve({ isConfirmed: true });
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function readFlashMessages() {
    var nodes = document.querySelectorAll('.v2-flash');

    Array.prototype.forEach.call(nodes, function (node) {
      var type = node.getAttribute('data-type') || 'info';
      var title = node.getAttribute('data-title') || 'Notice';
      var message = node.getAttribute('data-message');
      var messagesJson = node.getAttribute('data-messages');
      var options = {
        icon: swalIcon(type),
        title: title,
        confirmButtonText: 'Close'
      };

      if (messagesJson) {
        try {
          var messages = JSON.parse(messagesJson) || [];
          var html = '<ul class="v2-alert-list">';

          Array.prototype.forEach.call(messages, function (item) {
            html += '<li>' + escapeHtml(item) + '</li>';
          });

          html += '</ul>';
          options.html = html;
        } catch (error) {
          options.text = 'There are validation errors.';
        }
      } else {
        options.toast = type === 'success' || type === 'warning';
        options.position = options.toast ? 'top-end' : 'center';
        options.showConfirmButton = !options.toast;
        options.timer = options.toast ? 3200 : undefined;
        options.timerProgressBar = !!options.toast;
        options.text = message || '';
      }

      fireAlert(options);
    });
  }

  function initGlobalAjaxHandlers() {
    if (!window.jQuery) {
      return;
    }

    window.jQuery(document)
      .ajaxSend(function () {
        activeAjaxRequests += 1;
        showLoader('Loading data', 'Fetching the latest records.');
      })
      .ajaxComplete(function () {
        activeAjaxRequests = Math.max(0, activeAjaxRequests - 1);

        if (activeAjaxRequests === 0) {
          hideLoader();
        }
      })
      .ajaxError(function (event, xhr) {
        activeAjaxRequests = Math.max(0, activeAjaxRequests - 1);
        hideLoader();

        if (xhr && xhr.status >= 500) {
          fireAlert({
            icon: 'error',
            title: 'Server Error',
            text: 'The server could not complete that request.'
          });
        }
      });
  }

  function initFormLoader() {
    document.addEventListener('submit', function (event) {
      var form = event.target;

      if (!form || form.tagName !== 'FORM') {
        return;
      }

      window.setTimeout(function () {
        if (!event.defaultPrevented && form.getAttribute('data-skip-loader') !== '1') {
          showLoader('Saving changes', 'Submitting your request.');
        }
      }, 0);
    });
  }

  function initDataTable(selector, options) {
    var table;
    var settings;

    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
      return null;
    }

    if (!document.querySelector(selector)) {
      return null;
    }

    if (window.jQuery.fn.dataTable.isDataTable(selector)) {
      return window.jQuery(selector).DataTable();
    }

    settings = window.jQuery.extend(true, {
      autoWidth: false,
      responsive: true,
      deferRender: true,
      processing: false,
      pageLength: 25,
      searchDelay: 450,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      order: [[0, 'desc']],
      dom: "<'row align-items-center mb-3'<'col-md-5'l><'col-md-7'f>>" +
        "<'row'<'col-12'tr>>" +
        "<'row align-items-center mt-3'<'col-md-5'i><'col-md-7'p>>",
      language: {
        search: '',
        searchPlaceholder: 'Search records',
        processing: '<div class=\"v2-table-processing\"><span></span><strong>Loading records...</strong></div>',
        emptyTable: 'No records found'
      },
      columnDefs: [
        {
          targets: '_all',
          defaultContent: '-'
        }
      ]
    }, options || {});

    table = window.jQuery(selector).DataTable(settings);

    window.jQuery(selector).on('processing.dt', function (event, dtSettings, processing) {
      if (processing) {
        showLoader('Loading data', 'Fetching records for this table.');
      } else if (activeAjaxRequests === 0) {
        hideLoader();
      }
    });

    window.jQuery(selector).on('error.dt', function (event, dtSettings, techNote, message) {
      hideLoader();
      fireAlert({
        icon: 'error',
        title: 'Table Load Failed',
        text: message || 'Unable to load table data.'
      });
    });

    return table;
  }

  function confirmDelete(form, message) {
    if (!window.Swal) {
      return window.confirm(message || 'Are you sure?');
    }

    fireAlert({
      icon: 'warning',
      title: 'Please confirm',
      text: message || 'Are you sure you want to continue?',
      showCancelButton: true,
      confirmButtonText: 'Yes, continue',
      cancelButtonText: 'Cancel',
      reverseButtons: true
    }).then(function (result) {
      if (result.isConfirmed) {
        showLoader('Applying changes', 'Completing the requested action.');
        form.submit();
      }
    });

    return false;
  }

  document.addEventListener('DOMContentLoaded', function () {
    initGlobalAjaxHandlers();
    initFormLoader();
    readFlashMessages();
  });

  window.V2 = {
    confirmDelete: confirmDelete,
    fireAlert: fireAlert,
    hideLoader: hideLoader,
    initDataTable: initDataTable,
    showLoader: showLoader
  };
})(window, document);
