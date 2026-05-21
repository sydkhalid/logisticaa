(function (window, document) {
  var loaderElement = null;
  var activeAjaxRequests = 0;
  var activeFetchRequests = 0;
  var pageTransitionActive = false;

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

  function hasPendingLoaderActivity() {
    return activeAjaxRequests > 0 || activeFetchRequests > 0 || pageTransitionActive;
  }

  function maybeHideLoader() {
    if (!hasPendingLoaderActivity()) {
      hideLoader();
    }
  }

  function beginPageTransition(title, subtitle) {
    pageTransitionActive = true;
    showLoader(title || 'Loading page', subtitle || 'Opening the next view.');
  }

  function resetLoaderState() {
    activeAjaxRequests = 0;
    activeFetchRequests = 0;
    pageTransitionActive = false;
    hideLoader();
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
        maybeHideLoader();
      })
      .ajaxError(function (event, xhr) {
        maybeHideLoader();

        if (xhr && xhr.status >= 500) {
          fireAlert({
            icon: 'error',
            title: 'Server Error',
            text: 'The server could not complete that request.'
          });
        }
      });
  }

  function readHeaderValue(headers, name) {
    var lowerName = name.toLowerCase();
    var index;

    if (!headers) {
      return null;
    }

    if (typeof headers.get === 'function') {
      return headers.get(name);
    }

    if (headers[name]) {
      return headers[name];
    }

    if (headers[lowerName]) {
      return headers[lowerName];
    }

    if (Object.prototype.toString.call(headers) === '[object Array]') {
      for (index = 0; index < headers.length; index += 1) {
        if (headers[index] && headers[index][0] && String(headers[index][0]).toLowerCase() === lowerName) {
          return headers[index][1];
        }
      }
    }

    return null;
  }

  function initFetchLoader() {
    var originalFetch;

    if (!window.fetch) {
      return;
    }

    originalFetch = window.fetch;
    window.fetch = function (input, init) {
      var requestInit = init || {};
      var skipLoader = requestInit.skipLoader === true;

      if (!skipLoader && typeof Request !== 'undefined' && input instanceof Request) {
        skipLoader = readHeaderValue(input.headers, 'X-Skip-Loader') === '1';
      }

      if (!skipLoader) {
        skipLoader = readHeaderValue(requestInit.headers, 'X-Skip-Loader') === '1';
      }

      if (skipLoader) {
        return originalFetch.apply(window, arguments);
      }

      activeFetchRequests += 1;
      showLoader('Loading data', 'Fetching the latest records.');

      return originalFetch.apply(window, arguments).then(function (response) {
        activeFetchRequests = Math.max(0, activeFetchRequests - 1);
        maybeHideLoader();
        return response;
      }, function (error) {
        activeFetchRequests = Math.max(0, activeFetchRequests - 1);
        maybeHideLoader();
        throw error;
      });
    };
  }

  function shouldHandleLinkNavigation(link, event) {
    var href = link.getAttribute('href');
    var target = link.getAttribute('target');
    var destination;

    if (!href || href === '#' || href.indexOf('javascript:') === 0) {
      return false;
    }

    if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
      return false;
    }

    if (link.getAttribute('data-skip-loader') === '1' || link.hasAttribute('download')) {
      return false;
    }

    if (target && target !== '_self') {
      return false;
    }

    if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0)) {
      return false;
    }

    try {
      destination = new window.URL(link.href, window.location.href);
    } catch (error) {
      return false;
    }

    if (destination.origin !== window.location.origin) {
      return false;
    }

    if (
      destination.pathname === window.location.pathname &&
      destination.search === window.location.search &&
      destination.hash
    ) {
      return false;
    }

    return true;
  }

  function initLinkLoader() {
    document.addEventListener('click', function (event) {
      var link = event.target.closest ? event.target.closest('a[href]') : null;

      if (!link || event.defaultPrevented || !shouldHandleLinkNavigation(link, event)) {
        return;
      }

      window.setTimeout(function () {
        if (!event.defaultPrevented) {
          beginPageTransition(
            link.getAttribute('data-loader-title') || 'Loading page',
            link.getAttribute('data-loader-subtitle') || 'Opening the next view.'
          );
        }
      }, 0);
    });
  }

  function initFormLoader() {
    document.addEventListener('submit', function (event) {
      var form = event.target;
      var method;
      var title;
      var subtitle;

      if (!form || form.tagName !== 'FORM') {
        return;
      }

      method = String(form.getAttribute('method') || 'GET').toUpperCase();
      title = form.getAttribute('data-loader-title') || (method === 'GET' ? 'Loading page' : 'Saving changes');
      subtitle = form.getAttribute('data-loader-subtitle') || (method === 'GET' ? 'Preparing the next view.' : 'Submitting your request.');

      window.setTimeout(function () {
        if (!event.defaultPrevented && form.getAttribute('data-skip-loader') !== '1') {
          beginPageTransition(title, subtitle);
        }
      }, 0);
    });
  }

  function initSelect2(root) {
    var $;
    var selector = 'select.form-select, select.form-control, select[data-v2-select2="1"]';
    var $scope;
    var $selects;

    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
      return;
    }

    $ = window.jQuery;
    $scope = root ? $(root) : $(document);
    $selects = $scope.find(selector);

    if ($scope.is(selector)) {
      $selects = $selects.add($scope);
    }

    $selects
      .not('[data-no-select2="1"], .select2-hidden-accessible')
      .each(function () {
        var $select = $(this);
        var $blankOption = $select.find('option[value=""]').first();
        var placeholder = $select.data('placeholder') || ($blankOption.length ? String($blankOption.text()).trim() : null);
        var isTableLength = $select.closest('.dataTables_length').length > 0;
        var options = {
          theme: 'bootstrap',
          width: isTableLength ? '8rem' : '100%'
        };

        if (placeholder) {
          options.placeholder = placeholder;
        }

        if ($select.prop('multiple')) {
          options.closeOnSelect = false;
        }

        if (!$select.prop('required') && $blankOption.length) {
          options.allowClear = true;
        }

        if (isTableLength) {
          options.minimumResultsForSearch = Infinity;
        }

        $select.select2(options);
      });
  }

  function formNeedsValidation(form) {
    return form &&
      form.tagName === 'FORM' &&
      form.getAttribute('data-skip-validation') !== '1' &&
      (form.hasAttribute('data-v2-validate') || form.classList.contains('forms-sample'));
  }

  function firstInvalidField(form) {
    return form.querySelector(':invalid');
  }

  function setFormButtonsDisabled(form, disabled) {
    var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');

    Array.prototype.forEach.call(buttons, function (button) {
      if (disabled) {
        if (!button.hasAttribute('data-original-label')) {
          button.setAttribute('data-original-label', button.textContent || button.value || '');
        }

        if (button.tagName === 'BUTTON') {
          button.textContent = button.getAttribute('data-loading-label') || 'Please wait...';
        } else {
          button.value = button.getAttribute('data-loading-label') || 'Please wait...';
        }

        button.disabled = true;
      } else {
        if (button.hasAttribute('data-original-label')) {
          if (button.tagName === 'BUTTON') {
            button.textContent = button.getAttribute('data-original-label');
          } else {
            button.value = button.getAttribute('data-original-label');
          }
        }

        button.disabled = false;
      }
    });
  }

  function initFormValidation() {
    Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
      if (formNeedsValidation(form)) {
        form.setAttribute('novalidate', 'novalidate');
      }
    });

    document.addEventListener('submit', function (event) {
      var form = event.target;
      var invalidField;

      if (!formNeedsValidation(form)) {
        return;
      }

      form.setAttribute('novalidate', 'novalidate');
      form.classList.add('was-validated');

      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopImmediatePropagation();
        invalidField = firstInvalidField(form);

        if (invalidField && typeof invalidField.focus === 'function') {
          invalidField.focus({ preventScroll: false });
        }

        fireAlert({
          icon: 'warning',
          title: 'Check Required Fields',
          text: 'Please complete the highlighted fields before continuing.',
          confirmButtonText: 'Close'
        });

        return;
      }

      if (form.getAttribute('data-disable-submit') !== '0') {
        window.setTimeout(function () {
          if (!event.defaultPrevented) {
            setFormButtonsDisabled(form, true);
          }
        }, 0);
      }
    });
  }

  function tableExportTitle(selector) {
    var tableNode = document.querySelector(selector);
    var card = tableNode ? tableNode.closest('.card') : null;
    var titleNode = card ? card.querySelector('.card-title') : null;

    return titleNode && titleNode.textContent.trim()
      ? titleNode.textContent.trim()
      : document.title.replace(/\s+\|.*$/, '').trim() || 'logisticaa-export';
  }

  function tableExportFilename(title) {
    return String(title || 'logisticaa-export')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'logisticaa-export';
  }

  function exportableColumn(index, data, node) {
    return String(window.jQuery(node).text() || '').trim().toLowerCase() !== 'actions';
  }

  function dataTableButtons(selector) {
    var title = tableExportTitle(selector);
    var filename = tableExportFilename(title);
    var exportOptions = {
      columns: exportableColumn,
      modifier: {
        search: 'applied',
        order: 'applied'
      }
    };

    return [
      {
        text: '<i class=\"ti-reload\" aria-hidden=\"true\"></i><span>Refresh</span>',
        className: 'v2-dt-button v2-dt-button--refresh',
        action: function (event, dt) {
          if (dt.ajax && typeof dt.ajax.reload === 'function') {
            dt.ajax.reload(null, false);
            return;
          }

          dt.draw(false);
        }
      },
      {
        extend: 'excelHtml5',
        text: '<i class=\"ti-file\" aria-hidden=\"true\"></i><span>Excel</span>',
        className: 'v2-dt-button v2-dt-button--excel',
        title: title,
        filename: filename,
        exportOptions: exportOptions
      },
      {
        extend: 'pdfHtml5',
        text: '<i class=\"ti-files\" aria-hidden=\"true\"></i><span>PDF</span>',
        className: 'v2-dt-button v2-dt-button--pdf',
        title: title,
        filename: filename,
        orientation: 'landscape',
        pageSize: 'A4',
        exportOptions: exportOptions
      },
      {
        extend: 'csvHtml5',
        text: '<i class=\"ti-download\" aria-hidden=\"true\"></i><span>Download</span>',
        className: 'v2-dt-button v2-dt-button--download',
        title: title,
        filename: filename,
        exportOptions: exportOptions
      }
    ];
  }

  function applyTableAlignmentClasses(selector) {
    var tableNode = document.querySelector(selector);
    var headers = tableNode ? tableNode.querySelectorAll('thead th') : [];
    var firstHeader;
    var lastHeader;

    if (!tableNode || !headers.length) {
      return;
    }

    firstHeader = String(headers[0].textContent || '').trim();
    lastHeader = String(headers[headers.length - 1].textContent || '').trim().toLowerCase();

    if (firstHeader === '#') {
      tableNode.classList.add('v2-table--indexed');
    }

    if (lastHeader === 'actions' || lastHeader === 'action') {
      tableNode.classList.add('v2-table--actions');
    }
  }

  function initDataTable(selector, options) {
    var table;
    var settings;
    var hasButtons;

    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
      return null;
    }

    if (!document.querySelector(selector)) {
      return null;
    }

    if (window.jQuery.fn.dataTable.isDataTable(selector)) {
      return window.jQuery(selector).DataTable();
    }

    applyTableAlignmentClasses(selector);

    hasButtons = window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.Buttons;

    settings = window.jQuery.extend(true, {
      autoWidth: false,
      responsive: true,
      deferRender: true,
      processing: false,
      pageLength: 10,
      searchDelay: 450,
      lengthMenu: [[10, 25, 50, 100, -1], ['10 rows', '25 rows', '50 rows', '100 rows', 'Show all']],
      order: [[0, 'desc']],
      dom: (hasButtons
        ? "<'v2-dt-shell'<'v2-dt-toolbar'<'v2-dt-left'<'v2-dt-length'l><'v2-dt-buttons'B>><'v2-dt-search'f>>"
        : "<'v2-dt-shell'<'v2-dt-toolbar'<'v2-dt-length'l><'v2-dt-search'f>>") +
        "<'v2-dt-table'tr>" +
        "<'v2-dt-footer'<'v2-dt-info'i><'v2-dt-pager'p>>>",
      buttons: hasButtons ? dataTableButtons(selector) : [],
      language: {
        search: '',
        searchPlaceholder: 'Search records',
        lengthMenu: '_MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_ records',
        infoEmpty: 'No records to show',
        processing: '<div class=\"v2-table-processing\"><span></span><strong>Loading records...</strong></div>',
        emptyTable: 'No records found',
        zeroRecords: 'No matching records found',
        paginate: {
          previous: '<i class=\"ti-angle-left\" aria-hidden=\"true\"></i>',
          next: '<i class=\"ti-angle-right\" aria-hidden=\"true\"></i>'
        }
      },
      columnDefs: [
        {
          targets: '_all',
          defaultContent: '-'
        }
      ]
    }, options || {});

    table = window.jQuery(selector).DataTable(settings);

    var tableContainer = window.jQuery(table.table().container());
    tableContainer.addClass('v2-dt-theme');
    tableContainer.closest('.table-responsive').addClass('v2-table-frame');
    tableContainer.closest('.card').addClass('v2-table-card');
    initSelect2(tableContainer.get(0));

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

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      resetLoaderState();
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    resetLoaderState();
    initGlobalAjaxHandlers();
    initFetchLoader();
    initLinkLoader();
    initFormValidation();
    initFormLoader();
    initSelect2();
    readFlashMessages();
  });

  window.V2 = {
    beginPageTransition: beginPageTransition,
    confirmDelete: confirmDelete,
    fireAlert: fireAlert,
    hideLoader: hideLoader,
    initDataTable: initDataTable,
    initSelect2: initSelect2,
    setFormButtonsDisabled: setFormButtonsDisabled,
    resetLoaderState: resetLoaderState,
    showLoader: showLoader
  };
})(window, document);
