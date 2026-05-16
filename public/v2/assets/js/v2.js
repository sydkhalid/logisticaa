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
    readFlashMessages();
  });

  window.V2 = {
    beginPageTransition: beginPageTransition,
    confirmDelete: confirmDelete,
    fireAlert: fireAlert,
    hideLoader: hideLoader,
    initDataTable: initDataTable,
    setFormButtonsDisabled: setFormButtonsDisabled,
    resetLoaderState: resetLoaderState,
    showLoader: showLoader
  };
})(window, document);
